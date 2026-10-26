<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\TratamientoRealizado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * KardexController
 *
 * Módulo KARDEX (solo lectura): historial consolidado de pacientes con sus
 * totales de admisiones, historias clínicas, tratamientos y pagos.
 *
 * La tabla `cajas` NO tiene `paciente_id`; los pagos se asocian al paciente por
 * la cadena:
 *   cajas -> caja_detalles.tratamiento_realizado_id
 *         -> tratamientos_realizados.historia_clinica_id
 *         -> historias_clinicas.paciente_id
 * y como respaldo por `cajas.ci = pacientes.ci`.
 *
 * Se usan SUBCONSULTAS correlacionadas (no JOIN + SUM(DISTINCT)) para no
 * duplicar ni colapsar importes cuando una caja tiene varios detalles.
 */
class KardexController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Paciente::class;

    protected string $viewPrefix = 'kardex';

    protected string $resourceName = 'Kardex';

    protected string $pluralName = 'Kardex';

    protected string $permissionPrefix = 'reportes';

    protected int $perPage = 15;

    /* ============================================================
       EXPRESIONES SQL REUTILIZABLES (subconsultas correlacionadas)
       ============================================================ */

    /** Total de admisiones del paciente. */
    protected function sqlTotalAdmisiones(): string
    {
        return '(SELECT COUNT(*) FROM admisiones adm WHERE adm.paciente_id = pacientes.id)';
    }

    /** Total de historias clínicas del paciente. */
    protected function sqlTotalHistorias(): string
    {
        return '(SELECT COUNT(*) FROM historias_clinicas his WHERE his.paciente_id = pacientes.id)';
    }

    /** Total de tratamientos realizados del paciente. */
    protected function sqlTotalTratamientos(): string
    {
        return '(SELECT COUNT(*) FROM tratamientos_realizados tra'
            .' WHERE tra.historia_clinica_id IN ('
            .'SELECT h.id FROM historias_clinicas h WHERE h.paciente_id = pacientes.id))';
    }

    /**
     * IDs de las cajas enlazadas al paciente por la cadena de detalles.
     */
    protected function sqlCajasPorDetalle(): string
    {
        return '(SELECT cd.caja_id FROM caja_detalles cd'
            .' WHERE cd.tratamiento_realizado_id IN ('
            .'SELECT tr.id FROM tratamientos_realizados tr'
            .' WHERE tr.historia_clinica_id IN ('
            .'SELECT h2.id FROM historias_clinicas h2 WHERE h2.paciente_id = pacientes.id)))';
    }

    /** Total pagado (`cancelado`) de las cajas del paciente. */
    protected function sqlTotalPagado(): string
    {
        return '(SELECT COALESCE(SUM(caj.cancelado), 0) FROM cajas caj'
            .' WHERE '.$this->sqlCajasDelPaciente().')';
    }

    /** Costo total facturado de las cajas del paciente. */
    protected function sqlTotalCosto(): string
    {
        return '(SELECT COALESCE(SUM(caj.costo_total), 0) FROM cajas caj'
            .' WHERE '.$this->sqlCajasDelPaciente().')';
    }

    /** La relación directa prevalece; los recibos antiguos se enlazan por detalle o CI exacto. */
    protected function sqlCajasDelPaciente(): string
    {
        return '(caj.paciente_id = pacientes.id OR (caj.paciente_id IS NULL AND ('
            .'caj.id IN '.$this->sqlCajasPorDetalle()
            .' OR (caj.ci > 0 AND CAST(caj.ci AS CHAR) = pacientes.ci))))';
    }

    /**
     * Condición de "tiene movimientos" evaluada sobre los alias ya calculados
     * por la tabla derivada `kardex_pacientes`.
     */
    protected function sqlConMovimientos(): string
    {
        return '(COALESCE(kardex_pacientes.total_admisiones, 0)'
            .' + COALESCE(kardex_pacientes.total_historias, 0)'
            .' + COALESCE(kardex_pacientes.total_tratamientos, 0)'
            .' + COALESCE(kardex_pacientes.total_costo, 0)'
            .' + COALESCE(kardex_pacientes.total_pagado, 0)) > 0';
    }

    /* ============================================================
       QUERIES
       ============================================================ */

    /**
     * Consulta base del listado: un paciente por fila con sus totales.
     * Se usa una tabla derivada para poder filtrar por los alias calculados
     * sin depender de GROUP BY / HAVING.
     */
    protected function buildIndexQuery(Request $request): Builder
    {
        $sub = Paciente::query()
            ->select('pacientes.*')
            ->selectRaw($this->sqlTotalAdmisiones().' AS total_admisiones')
            ->selectRaw($this->sqlTotalHistorias().' AS total_historias')
            ->selectRaw($this->sqlTotalTratamientos().' AS total_tratamientos')
            ->selectRaw($this->sqlTotalCosto().' AS total_costo')
            ->selectRaw($this->sqlTotalPagado().' AS total_pagado');

        return Paciente::query()
            ->fromSub($sub, 'kardex_pacientes')
            ->whereRaw($this->sqlConMovimientos());
    }

    protected function buildTablaQuery(Request $request): Builder
    {
        $q = $this->buildIndexQuery($request);

        // Búsqueda por nombre completo o CI
        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function (Builder $x) use ($buscar) {
                $x->where('kardex_pacientes.ci', 'like', "%{$buscar}%")
                    ->orWhere('kardex_pacientes.nombre', 'like', "%{$buscar}%")
                    ->orWhere('kardex_pacientes.apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('kardex_pacientes.apellido_materno', 'like', "%{$buscar}%");
            });
        }

        // Filtro por clínica: por historias clínicas o por tratamientos realizados
        if ($clinicaId = $request->input('clinica_id')) {
            $q->where(function (Builder $x) use ($clinicaId) {
                $x->whereIn('kardex_pacientes.id', function ($sub) use ($clinicaId) {
                    $sub->select('his.paciente_id')
                        ->from('historias_clinicas as his')
                        ->where('his.clinica_id', $clinicaId);
                })->orWhereIn('kardex_pacientes.id', function ($sub) use ($clinicaId) {
                    $sub->select('his2.paciente_id')
                        ->from('tratamientos_realizados as tra')
                        ->join('historias_clinicas as his2', 'his2.id', '=', 'tra.historia_clinica_id')
                        ->where('tra.clinica_id', $clinicaId);
                });
            });
        }

        return $q->orderByDesc('kardex_pacientes.id');
    }

    /* ============================================================
       DATOS EXTRA PARA LAS VISTAS
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'stats' => $this->metricas(),
            'clinicas' => Clinica::query()
                ->orderBy('especialidad')
                ->get(['id', 'especialidad', 'modulo']),
        ];
    }

    /**
     * Métricas de la cabecera: pacientes con movimientos, total pagado,
     * saldo por cobrar y total de tratamientos realizados.
     */
    protected function metricas(): array
    {
        $pacientesConMovimientos = (int) $this->buildIndexQuery(request())->count();

        $sumaCosto = (float) Caja::query()->sum('costo_total');
        $sumaPagado = (float) Caja::query()->sum('cancelado');

        return [
            'pacientes' => $pacientesConMovimientos,
            'total_pagado' => $sumaPagado,
            'saldo_pendiente' => max(0, $sumaCosto - $sumaPagado),
            'total_tratamientos' => (int) TratamientoRealizado::query()->count(),
        ];
    }

    /**
     * Totales de un paciente concreto (para el modal de detalle).
     */
    protected function totalesDePaciente(int $pacienteId): array
    {
        $fila = DB::table('pacientes')
            ->where('pacientes.id', $pacienteId)
            ->selectRaw($this->sqlTotalAdmisiones().' AS total_admisiones')
            ->selectRaw($this->sqlTotalHistorias().' AS total_historias')
            ->selectRaw($this->sqlTotalTratamientos().' AS total_tratamientos')
            ->selectRaw($this->sqlTotalCosto().' AS total_costo')
            ->selectRaw($this->sqlTotalPagado().' AS total_pagado')
            ->first();

        $costo = (float) ($fila->total_costo ?? 0);
        $pagado = (float) ($fila->total_pagado ?? 0);

        return [
            'admisiones' => (int) ($fila->total_admisiones ?? 0),
            'historias' => (int) ($fila->total_historias ?? 0),
            'tratamientos' => (int) ($fila->total_tratamientos ?? 0),
            'costo_total' => $costo,
            'pagado' => $pagado,
            'saldo' => $costo - $pagado,
        ];
    }

    /**
     * Detalle completo del paciente para el modal (pestañas: admisiones,
     * historias clínicas, tratamientos realizados y pagos).
     */
    protected function extraDatosData($model): array
    {
        /** @var Paciente $model */
        $pacienteId = (int) $model->id;
        $totales = $this->totalesDePaciente($pacienteId);

        // ---- Admisiones del paciente ----
        $admisiones = DB::table('admisiones as adm')
            ->where('adm.paciente_id', $pacienteId)
            ->orderByDesc('adm.id')
            ->get([
                'adm.id',
                'adm.estado_civil',
                'adm.ocupacion',
                'adm.escuela',
                'adm.diagnostico',
                'adm.created_at',
            ]);

        // ---- Historias clínicas ----
        $historias = DB::table('historias_clinicas as his')
            ->leftJoin('clinicas as cli', 'cli.id', '=', 'his.clinica_id')
            ->where('his.paciente_id', $pacienteId)
            ->orderByDesc('his.id')
            ->get([
                'his.id',
                'his.estado',
                'his.autorizado',
                'his.fecha_inicio',
                'his.fecha_fin',
                'his.created_at',
                DB::raw('cli.especialidad AS clinica'),
            ]);

        // ---- Tratamientos realizados ----
        $tratamientos = DB::table('tratamientos_realizados as tra')
            ->leftJoin('tratamientos as t', 't.id', '=', 'tra.tratamiento_id')
            ->leftJoin('clinicas as cli', 'cli.id', '=', 'tra.clinica_id')
            ->whereIn('tra.historia_clinica_id', function ($sub) use ($pacienteId) {
                $sub->select('his.id')
                    ->from('historias_clinicas as his')
                    ->where('his.paciente_id', $pacienteId);
            })
            ->orderByDesc('tra.id')
            ->get([
                'tra.id',
                'tra.pieza_dental',
                'tra.estado',
                'tra.costo_cobrado',
                'tra.fecha_inicio',
                'tra.fecha_fin',
                'tra.observacion',
                DB::raw('t.nombre AS tratamiento'),
                DB::raw('cli.especialidad AS clinica'),
            ]);

        // ---- Pagos (cajas) ----
        $subCajas = $this->subCajasDePacienteId($pacienteId);

        $pagos = Caja::query()
            ->where(function (Builder $x) use ($subCajas, $pacienteId, $model): void {
                $x->where('cajas.paciente_id', $pacienteId)
                    ->orWhere(function (Builder $legacy) use ($subCajas, $model): void {
                        $legacy->whereNull('cajas.paciente_id')->where(function (Builder $links) use ($subCajas, $model): void {
                            $links->whereIn('cajas.id', $subCajas)
                                ->orWhereRaw('cajas.ci > 0 AND CAST(cajas.ci AS CHAR) = ?', [(string) $model->ci]);
                        });
                    });
            })
            ->orderByDesc('cajas.id')
            ->get([
                'cajas.id',
                'cajas.recibo',
                'cajas.monto',
                'cajas.costo_total',
                'cajas.cancelado',
                'cajas.tipo_pago',
                'cajas.descripcion',
                'cajas.created_at',
            ]);

        return [
            'es_edicion' => false,
            'total_admisiones' => $totales['admisiones'],
            'total_historias' => $totales['historias'],
            'total_tratamientos' => $totales['tratamientos'],
            'total_costo' => $totales['costo_total'],
            'total_pagado' => $totales['pagado'],
            'saldo_pendiente' => $totales['saldo'],
            'admisiones' => $admisiones,
            'historias' => $historias,
            'tratamientos' => $tratamientos,
            'pagos' => $pagos,
        ];
    }

    /**
     * Subconsulta con los IDs de cajas enlazadas a un paciente por la cadena
     * caja_detalles -> tratamientos_realizados -> historias_clinicas.
     */
    protected function subCajasDePacienteId(int $pacienteId): \Closure
    {
        return function ($sub) use ($pacienteId) {
            $sub->select('cd.caja_id')
                ->from('caja_detalles as cd')
                ->whereIn('cd.tratamiento_realizado_id', function ($tr) use ($pacienteId) {
                    $tr->select('tra.id')
                        ->from('tratamientos_realizados as tra')
                        ->whereIn('tra.historia_clinica_id', function ($his) use ($pacienteId) {
                            $his->select('h.id')
                                ->from('historias_clinicas as h')
                                ->where('h.paciente_id', $pacienteId);
                        });
                });
        };
    }

    /* ============================================================
       MÓDULO DE SOLO LECTURA: se bloquean las escrituras
       ============================================================ */

    public function store(Request $request): JsonResponse
    {
        abort(403, 'El módulo Kardex es de solo lectura.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        abort(403, 'El módulo Kardex es de solo lectura.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        abort(403, 'El módulo Kardex es de solo lectura.');
    }
}
