<?php

namespace App\Services;

use App\Models\HistoriaClinica;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionRemision
{
    /** @return array{registro: Remision, personas: Collection<int, Usuario>, periodos: Collection<int, Periodo>, docenteActual: ?Usuario} */
    public function datos(Remision $remision, Usuario $usuario): array
    {
        $this->verificarPendiente($remision);

        return [
            'registro' => $remision->loadMissing(['paciente', 'clinica', 'periodo']),
            'personas' => Usuario::query()->where('habilitado', true)
                ->whereHas('especialidades', fn (Builder $query) => $query->where('clinicas.id', $remision->clinica_id)->where('especialidades.habilitado', true))
                ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['estudiante', 'interno', 'docente', 'jefe-catedra']))
                ->with('roles')->orderBy('nombre_completo')->get(),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')->get(['id', 'nombre', 'gestion', 'semestre']),
            'docenteActual' => ! $usuario->hasRole('admin') && $usuario->hasAnyRole(['docente', 'jefe-catedra']) ? $usuario : null,
        ];
    }

    public function asignar(Remision $remision, Usuario $usuario, int $estudianteId, int $docenteId, ?int $periodoId): HistoriaClinica
    {
        return DB::transaction(function () use ($remision, $usuario, $estudianteId, $docenteId, $periodoId): HistoriaClinica {
            $registro = AccesoClinico::remisiones(Remision::query(), $usuario)->lockForUpdate()->findOrFail($remision->id);
            $this->verificarPendiente($registro);

            if ($registro->periodo_id && $periodoId && (int) $registro->periodo_id !== $periodoId) {
                throw ValidationException::withMessages(['periodo_id' => 'Debe conservar el periodo de la remisión.']);
            }
            $periodoId = $registro->periodo_id ?? $periodoId;
            if (! $periodoId) {
                throw ValidationException::withMessages(['periodo_id' => 'Seleccione el periodo antes de asignar una historia clínica.']);
            }

            foreach (['estudiante' => [$estudianteId, ['estudiante', 'interno']], 'docente' => [$docenteId, ['docente', 'jefe-catedra']]] as $tipo => [$personaId, $roles]) {
                $persona = Usuario::findOrFail($personaId);
                if (! $persona->habilitado || ! $persona->hasAnyRole($roles) || ! $persona->especialidades()->where('clinicas.id', $registro->clinica_id)->wherePivot('habilitado', true)->exists()) {
                    throw ValidationException::withMessages([$tipo.'_id' => 'Seleccione personal habilitado de esta especialidad.']);
                }
            }

            $asignacion = ['estudiante_id' => $estudianteId, 'docente_id' => $docenteId, 'periodo_id' => $periodoId];
            $registro->update($asignacion + ['asignado_por' => $usuario->id, 'fecha_asignacion' => now(), 'estado' => 'asignada']);

            return HistoriaClinica::create($asignacion + [
                'remision_id' => $registro->id,
                'admision_id' => $registro->admision_id,
                'paciente_id' => $registro->paciente_id,
                'clinica_id' => $registro->clinica_id,
                'especialidad_estudiante' => $registro->clinica_id,
                'especialidad_docente' => $registro->clinica_id,
                'estado' => 'revision',
                'autorizado' => false,
            ]);
        });
    }

    private function verificarPendiente(Remision $remision): void
    {
        abort_unless($remision->habilitado, 422, 'La remisión está inhabilitada.');
        abort_if($remision->estado !== 'pendiente' || $remision->historiasClinicas()->exists(), 422, 'La remisión ya tiene una asignación o historia clínica.');
    }
}
