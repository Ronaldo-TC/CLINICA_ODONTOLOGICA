<?php

namespace App\Services;

use App\Models\Especialidad;
use App\Models\HistoriaClinica;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FlujoTratamiento
{
    /** @param array<int, string> $permisos */
    public function exigirPermiso(Usuario $usuario, array $permisos): void
    {
        foreach ($permisos as $permiso) {
            if ($usuario->can($permiso)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para esta operación clínica.');
    }

    public function tratamientosVisibles(Usuario $usuario): Builder
    {
        return TratamientoRealizado::query()->whereHas('historiaClinica', function (Builder $query) use ($usuario): void {
            AccesoClinico::historias($query, $usuario);
        });
    }

    public function exigirLectura(TratamientoRealizado $tratamiento, Usuario $usuario): void
    {
        $this->exigirPermiso($usuario, ['atenciones.ver', 'atenciones.gestionar']);
        abort_unless(AccesoClinico::puedeVerHistoria($tratamiento->historiaClinica, $usuario), 403);
    }

    /** @param array<string, mixed> $datos */
    public function proponer(Usuario $usuario, array $datos): TratamientoRealizado
    {
        $this->exigirPermiso($usuario, ['atenciones.crear', 'atenciones.gestionar']);

        return DB::transaction(function () use ($usuario, $datos): TratamientoRealizado {
            $historia = HistoriaClinica::query()->lockForUpdate()->findOrFail($datos['historia_clinica_id']);
            $this->exigirEstudiante($historia, $usuario);
            $this->exigirHistoriaActiva($historia);
            $catalogo = Tratamiento::query()->findOrFail($datos['tratamiento_id']);
            $this->exigir($catalogo->precio_asignado, 'tratamiento_id', 'Caja debe asignar el precio del tratamiento antes de registrar una atención.');
            $this->exigir((int) $catalogo->clinica_id === (int) $historia->clinica_id, 'tratamiento_id', 'El tratamiento debe pertenecer a la especialidad de la historia clínica.');

            return TratamientoRealizado::query()->create([
                'historia_clinica_id' => $historia->id,
                'tratamiento_id' => $catalogo->id,
                'clinica_id' => $historia->clinica_id,
                'pieza_dental' => $datos['pieza_dental'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
                'costo_cobrado' => $catalogo->costo,
                'estado' => 'pendiente',
            ]);
        });
    }

    /** @param array<string, mixed> $datos */
    public function editarPropuesta(TratamientoRealizado $tratamiento, Usuario $usuario, array $datos): TratamientoRealizado
    {
        $this->exigirPermiso($usuario, ['atenciones.editar', 'atenciones.gestionar']);

        return DB::transaction(function () use ($tratamiento, $usuario, $datos): TratamientoRealizado {
            $tratamiento = $this->bloquear($tratamiento);
            $this->exigirEstudiante($tratamiento->historiaClinica, $usuario);
            $this->exigirHistoriaActiva($tratamiento->historiaClinica);
            $this->exigir(in_array($tratamiento->estado, ['pendiente', 'propuesta'], true) && ! $tratamiento->estaAutorizado(), 'estado', 'Solo puede editar una propuesta pendiente de aprobación.');
            $catalogo = Tratamiento::query()->findOrFail($datos['tratamiento_id']);
            $this->exigir($catalogo->precio_asignado, 'tratamiento_id', 'Caja debe asignar el precio del tratamiento antes de registrar una atención.');
            $this->exigir((int) $catalogo->clinica_id === (int) $tratamiento->clinica_id, 'tratamiento_id', 'El tratamiento debe pertenecer a la especialidad de la historia clínica.');
            $tratamiento->update([
                'tratamiento_id' => $catalogo->id,
                'costo_cobrado' => $catalogo->costo,
                'pieza_dental' => $datos['pieza_dental'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            return $tratamiento;
        });
    }

    public function autorizar(TratamientoRealizado $tratamiento, Usuario $usuario): TratamientoRealizado
    {
        $this->exigirPermiso($usuario, ['atenciones.autorizar', 'atenciones.gestionar']);

        return DB::transaction(function () use ($tratamiento, $usuario): TratamientoRealizado {
            $tratamiento = $this->bloquear($tratamiento);
            $this->exigirDocente($tratamiento->historiaClinica, $usuario);
            $this->exigirHistoriaActiva($tratamiento->historiaClinica);
            $this->exigir(in_array($tratamiento->estado, ['pendiente', 'propuesta'], true) && ! $tratamiento->estaAutorizado(), 'estado', 'Solo puede aprobar una propuesta pendiente.');
            $tratamiento->update(['estado' => 'autorizado', 'autorizado_por' => $usuario->id, 'fecha_autorizacion' => now()]);

            return $tratamiento;
        });
    }

    public function tienePago(TratamientoRealizado $tratamiento): bool
    {
        return $tratamiento->cajaDetalles()->where('monto', '>', 0)
            ->whereHas('caja', function (Builder $query): void {
                $query->where('habilitada', true)->where('cancelado', '>', 0)
                    ->whereNotNull('recibo')->where('recibo', '!=', '');
            })->exists();
    }

    /** @param array<string, mixed> $datos */
    public function registrarSesion(TratamientoRealizado $tratamiento, Usuario $usuario, array $datos): SesionAtencion
    {
        $this->exigirPermiso($usuario, ['historias.editar', 'historias.gestionar']);

        return DB::transaction(function () use ($tratamiento, $usuario, $datos): SesionAtencion {
            $tratamiento = $this->bloquear($tratamiento);
            $historia = $tratamiento->historiaClinica;
            $this->exigirEstudiante($historia, $usuario);
            $this->exigirAtencion($tratamiento);
            $sesion = $tratamiento->sesionesAtencion()->create([
                'fecha' => $datos['fecha'],
                'descripcion' => $datos['descripcion'],
                'estudiante_id' => $historia->estudiante_id,
                'docente_id' => $historia->docente_id,
                'autorizado' => false,
            ]);
            $tratamiento->update(['estado' => 'en_proceso', 'fecha_inicio' => $tratamiento->fecha_inicio ?? $sesion->fecha]);

            return $sesion;
        });
    }

    /** @param array<string, mixed> $datos */
    public function editarSesion(SesionAtencion $sesion, Usuario $usuario, array $datos): SesionAtencion
    {
        $this->exigirPermiso($usuario, ['historias.editar', 'historias.gestionar']);

        return DB::transaction(function () use ($sesion, $usuario, $datos): SesionAtencion {
            $tratamiento = $this->bloquear($sesion->tratamientoRealizado);
            $sesion = SesionAtencion::query()->lockForUpdate()->findOrFail($sesion->id);
            $this->exigirEstudiante($tratamiento->historiaClinica, $usuario);
            $this->exigirAtencion($tratamiento);
            $this->exigir(! $sesion->autorizado && $sesion->autorizado_por === null, 'autorizado', 'Un avance autorizado forma parte del historial y no puede modificarse.');
            $sesion->update(['fecha' => $datos['fecha'], 'descripcion' => $datos['descripcion']]);

            return $sesion;
        });
    }

    public function autorizarSesion(SesionAtencion $sesion, Usuario $usuario, ?string $observacion): SesionAtencion
    {
        $this->exigirPermiso($usuario, ['historias.autorizar', 'historias.gestionar']);

        return DB::transaction(function () use ($sesion, $usuario, $observacion): SesionAtencion {
            $tratamiento = $this->bloquear($sesion->tratamientoRealizado);
            $sesion = SesionAtencion::query()->lockForUpdate()->findOrFail($sesion->id);
            $this->exigirDocente($tratamiento->historiaClinica, $usuario, $sesion->docente_id);
            $this->exigirAtencion($tratamiento);
            $this->exigir(! $sesion->autorizado && $sesion->autorizado_por === null, 'autorizado', 'El avance ya fue autorizado.');
            $sesion->update([
                'autorizado' => true,
                'autorizado_por' => $usuario->id,
                'fecha_autorizacion' => now(),
                'observacion_docente' => $observacion,
            ]);

            return $sesion;
        });
    }

    public function finalizar(TratamientoRealizado $tratamiento, Usuario $usuario): TratamientoRealizado
    {
        $this->exigirPermiso($usuario, ['atenciones.editar', 'atenciones.gestionar']);

        return DB::transaction(function () use ($tratamiento, $usuario): TratamientoRealizado {
            $tratamiento = $this->bloquear($tratamiento);
            $this->exigirEstudiante($tratamiento->historiaClinica, $usuario, false);
            $this->exigirAtencion($tratamiento);
            $this->exigir($tratamiento->sesionesAtencion()->exists(), 'estado', 'Registre al menos un avance antes de finalizar.');
            $this->exigir(! $tratamiento->sesionesAtencion()->where(function (Builder $query): void {
                $query->where('autorizado', false)->orWhereNull('autorizado_por')->orWhereNull('fecha_autorizacion');
            })->exists(), 'estado', 'El docente debe autorizar todos los avances antes de finalizar.');
            $tratamiento->update(['estado' => 'finalizado', 'finalizado_por' => $usuario->id, 'fecha_finalizacion' => now()]);

            return $tratamiento;
        });
    }

    public function culminar(TratamientoRealizado $tratamiento, Usuario $usuario): TratamientoRealizado
    {
        $this->exigirPermiso($usuario, ['atenciones.autorizar', 'atenciones.gestionar']);

        return DB::transaction(function () use ($tratamiento, $usuario): TratamientoRealizado {
            $tratamiento = $this->bloquear($tratamiento);
            $this->exigirDocente($tratamiento->historiaClinica, $usuario);
            $this->exigir($tratamiento->estado === 'finalizado' && $tratamiento->finalizado_por !== null && $tratamiento->fecha_finalizacion !== null, 'estado', 'El estudiante debe finalizar el tratamiento antes de su culminación docente.');
            $tratamiento->update(['estado' => 'culminado', 'culminado_por' => $usuario->id, 'fecha_culminacion' => now(), 'fecha_fin' => today()]);

            return $tratamiento;
        });
    }

    private function bloquear(TratamientoRealizado $tratamiento): TratamientoRealizado
    {
        return TratamientoRealizado::query()->lockForUpdate()->findOrFail($tratamiento->id);
    }

    private function exigirEstudiante(HistoriaClinica $historia, Usuario $usuario, bool $permitirAdministrador = true): void
    {
        if ($permitirAdministrador && $usuario->hasAnyRole(['admin', 'subadmin'])) {
            return;
        }
        abort_unless((int) $historia->estudiante_id === (int) $usuario->id && $usuario->hasAnyRole(['estudiante', 'interno']), 403, 'Esta atención corresponde a otro estudiante.');
        $this->exigirEspecialidad($historia, $usuario);
    }

    private function exigirDocente(HistoriaClinica $historia, Usuario $usuario, ?int $docenteSesion = null): void
    {
        if ($usuario->hasAnyRole(['admin', 'subadmin'])) {
            return;
        }
        abort_unless($usuario->hasAnyRole(['docente', 'jefe-catedra']) && ((int) $historia->docente_id === (int) $usuario->id || $docenteSesion === $usuario->id), 403, 'Solo el docente asignado puede autorizar esta atención.');
        $this->exigirEspecialidad($historia, $usuario);
    }

    private function exigirEspecialidad(HistoriaClinica $historia, Usuario $usuario): void
    {
        abort_unless(Especialidad::query()->where('usuario_id', $usuario->id)->where('clinica_id', $historia->clinica_id)->where('habilitado', true)->exists(), 403, 'Su especialidad debe estar habilitada.');
    }

    private function exigirHistoriaActiva(HistoriaClinica $historia): void
    {
        $this->exigir((bool) $historia->autorizado && $historia->fecha_fin === null
            && ! in_array($historia->estado, ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado'], true),
            'historia_clinica_id', 'La historia clínica debe estar autorizada y abierta.');
    }

    private function exigirAtencion(TratamientoRealizado $tratamiento): void
    {
        $this->exigirHistoriaActiva($tratamiento->historiaClinica);
        $this->exigir($tratamiento->estaAutorizado() && in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true), 'estado', 'El tratamiento debe estar autorizado y abierto para registrar avances.');
        $this->exigir($this->tienePago($tratamiento), 'pago', 'Registre en Cajas un recibo con pago para este tratamiento antes de iniciar la atención.');
    }

    private function exigir(bool $condicion, string $campo, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
    }
}
