<?php

namespace App\Services;

use App\Models\Admision;
use App\Models\Caja;
use App\Models\HistoriaClinica;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class ReportePaciente
{
    public function generar(Admision $admision, Usuario $usuario, bool $soloFicha = false): Response
    {
        $paciente = $admision->paciente()->firstOrFail();
        $admisiones = AccesoClinico::admisiones(Admision::query(), $usuario)
            ->where('paciente_id', $paciente->id)
            ->when($soloFicha, fn (Builder $query) => $query->whereKey($admision->id))
            ->with(['periodo', 'motivoConsulta', 'estudiante', 'responsable', 'clinicaEstudiante', 'clinicaResponsable', 'registradoPor', 'remisiones.clinica'])
            ->orderByDesc('fecha_admision')->orderByDesc('id')->get();
        $permisos = collect(['historias', 'remisiones', 'radiologias', 'cajas'])
            ->mapWithKeys(fn (string $modulo): array => [$modulo => ! $soloFicha && ($usuario->hasRole('admin') || $usuario->canAny([$modulo.'.ver', $modulo.'.gestionar']))])->all();
        $historias = $permisos['historias']
            ? AccesoClinico::historias(HistoriaClinica::query(), $usuario)->where('paciente_id', $paciente->id)
                ->with(['clinica', 'periodo', 'estudiante', 'docente', 'tratamientosRealizados.tratamiento', 'tratamientosRealizados.sesionesAtencion.docente'])
                ->orderByDesc('id')->get()
            : collect();
        $remisiones = $permisos['remisiones']
            ? AccesoClinico::remisiones(Remision::query(), $usuario)->where('paciente_id', $paciente->id)
                ->with(['clinica', 'periodo'])->orderByDesc('id')->get()
            : collect();
        $radiologias = $permisos['radiologias']
            ? AccesoClinico::radiologias(Radiologia::query(), $usuario)->where('paciente_id', $paciente->id)
                ->with('usuario')->orderByDesc('id')->get()
            : collect();
        $pagos = $permisos['cajas']
            ? Caja::query()->where('habilitada', true)->where(function (Builder $query) use ($paciente): void {
                $query->where('paciente_id', $paciente->id)
                    ->orWhere(function (Builder $legacy) use ($paciente): void {
                        $legacy->whereNull('paciente_id')->where(function (Builder $vinculo) use ($paciente): void {
                            $vinculo->whereHas('detalles.tratamientoRealizado.historiaClinica', fn (Builder $historia) => $historia->where('paciente_id', $paciente->id))
                                ->orWhere(function (Builder $ci) use ($paciente): void {
                                    $ci->where('ci', '>', 0)->whereRaw('CAST(ci AS CHAR) = ?', [$paciente->ci]);
                                });
                        });
                    });
            })->with('usuario')->orderByDesc('id')->get()
            : collect();
        $hallazgos = [];
        $condiciones = config('odontograma.condiciones', []);
        $caras = ['o' => 'Oclusal / incisal', 'm' => 'Mesial', 'd' => 'Distal', 'v' => 'Vestibular', 'l' => 'Lingual / palatino'];
        foreach ($admisiones as $ficha) {
            $hallazgos[$ficha->id] = [];
            $datos = json_decode((string) $ficha->odontograma_pintado, true);
            foreach (is_array($datos) ? $datos : [] as $pieza => $detalle) {
                if (! preg_match('/^(?:[1-4][1-8]|[5-8][1-5])$/', (string) $pieza) || ! is_array($detalle)) {
                    continue;
                }
                foreach ($caras as $cara => $etiqueta) {
                    $estado = $detalle[$cara] ?? null;
                    if (is_string($estado) && $estado !== 'sano' && isset($condiciones[$estado])) {
                        $hallazgos[$ficha->id][] = [$pieza, $etiqueta, $condiciones[$estado]['etiqueta']];
                    }
                }
                foreach (is_array($detalle['marcas'] ?? null) ? $detalle['marcas'] : [] as $marca) {
                    $estado = is_array($marca) ? ($marca['estado'] ?? null) : null;
                    if (is_string($estado) && $estado !== 'sano' && isset($condiciones[$estado])) {
                        $hallazgos[$ficha->id][] = [$pieza, 'Pieza completa', $condiciones[$estado]['etiqueta'].' · '.(($marca['color'] ?? '') === 'azul' ? 'Azul' : 'Rojo')];
                    }
                }
            }
        }

        return app(ReportePdf::class)->responder('reportes.paciente', [
            'titulo' => $soloFicha ? 'Ficha de admisión' : 'Reporte clínico por paciente',
            'codigo' => ($soloFicha ? 'FIC-' : 'PAC-').str_pad((string) ($soloFicha ? $admision->id : $paciente->id), 6, '0', STR_PAD_LEFT),
            'tamanoPapel' => 'oficio',
            'volver' => route('admisiones.show', $admision->id),
            'emitidoPor' => $usuario->nombre_completo,
            'emitidoEn' => now(),
            'paciente' => $paciente, 'admisiones' => $admisiones, 'historias' => $historias,
            'remisiones' => $remisiones, 'radiologias' => $radiologias, 'pagos' => $pagos,
            'hallazgos' => $hallazgos, 'permisos' => $permisos,
            'soloFicha' => $soloFicha,
            'imagenesOdontograma' => request('formato') === 'pdf' ? app(ImagenOdontograma::class)->incrustadas() : [],
        ], request(), ($soloFicha ? 'ficha-'.$admision->id : 'paciente-'.$paciente->id).'-'.now()->format('Ymd-His').'.pdf');
    }
}
