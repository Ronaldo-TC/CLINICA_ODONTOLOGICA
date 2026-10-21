<?php

namespace App\Http\Controllers;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Radiologia;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use App\Services\AccesoClinico;
use App\Services\ReporteModulo;
use App\Services\ReportePdf;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * ReporteController
 *
 * Módulo REPORTES — gráficos y estadísticas para la Dirección.
 *
 * No es un CRUD: no administra un modelo ni una tabla. Por eso NO usa el trait
 * HasCrudAjax (que exige $modelClass / $viewPrefix / $resourceName), pero sí
 * replica su patrón:
 *   - helper authorizeAction() con prefijo de permiso `reportes`
 *   - respuestas JSON uniformes y predecibles
 *
 * Todos los endpoints GET devuelven:
 *   { "labels": [...], "valores": [...], "total": n }
 *
 * Filtros globales opcionales (query string), aplicados en todos los endpoints:
 *   desde=Y-m-d   hasta=Y-m-d   periodo_id=<int>   clinica_id=<int>
 *
 * NOTA sobre filtros: solo se aplican sobre columnas que EXISTEN en el esquema.
 *   - `cajas` y `radiologias` no tienen `periodo_id`, por lo que el filtro
 *     periodo_id no se aplica en `pagos-por-tipo`, `ingresos-por-mes` ni
 *     `radiografias-por-mes` (no se inventan columnas ni rutas de join dudosas).
 *   - El resto de reportes filtran por `created_at` o por la columna `fecha_*`
 *     correspondiente (fecha_inicio / fecha_toma / fecha).
 */
class ReporteController extends Controller
{
    /* ============================================================
       CATÁLOGO DE REPORTES
       Fuente única de verdad para la vista, el CSV y el imprimible.
       ============================================================ */
    public static function catalogo(): array
    {
        return [
            'pacientes-por-periodo' => [
                'titulo' => 'Admisiones por periodo',
                'grafico' => 'barra',
                'etiqueta' => 'Periodo',
                'valor' => 'Admisiones',
                'ruta' => 'reportes.pacientes-por-periodo',
            ],
            'pacientes-por-especialidad' => [
                'titulo' => 'Admisiones por clínica',
                'grafico' => 'torta',
                'etiqueta' => 'Clínica',
                'valor' => 'Admisiones',
                'ruta' => 'reportes.pacientes-por-especialidad',
            ],
            'pacientes-por-edad' => [
                'titulo' => 'Pacientes por grupo de edad',
                'grafico' => 'barra',
                'etiqueta' => 'Grupo de edad',
                'valor' => 'Pacientes',
                'ruta' => 'reportes.pacientes-por-edad',
            ],
            'pacientes-por-sexo' => [
                'titulo' => 'Pacientes por sexo',
                'grafico' => 'dona',
                'etiqueta' => 'Sexo',
                'valor' => 'Pacientes',
                'ruta' => 'reportes.pacientes-por-sexo',
            ],
            'motivos-consulta' => [
                'titulo' => 'Motivos de consulta más frecuentes (top 10)',
                'grafico' => 'barra-horizontal',
                'etiqueta' => 'Motivo de consulta',
                'valor' => 'Admisiones',
                'ruta' => 'reportes.motivos-consulta',
            ],
            'tratamientos-por-especialidad' => [
                'titulo' => 'Tratamientos realizados por clínica',
                'grafico' => 'barra',
                'etiqueta' => 'Clínica',
                'valor' => 'Tratamientos',
                'ruta' => 'reportes.tratamientos-por-especialidad',
            ],
            'atenciones-por-docente' => [
                'titulo' => 'Atenciones por docente (top 10, historias autorizadas)',
                'grafico' => 'barra',
                'etiqueta' => 'Docente',
                'valor' => 'Historias autorizadas',
                'ruta' => 'reportes.atenciones-por-docente',
            ],
            'atenciones-por-estudiante' => [
                'titulo' => 'Atenciones por estudiante (top 10, tratamientos realizados)',
                'grafico' => 'barra',
                'etiqueta' => 'Estudiante',
                'valor' => 'Tratamientos',
                'ruta' => 'reportes.atenciones-por-estudiante',
            ],
            'insumos-utilizados' => [
                'titulo' => 'Insumos más utilizados (top 10)',
                'grafico' => 'barra',
                'etiqueta' => 'Insumo',
                'valor' => 'Cantidad usada',
                'ruta' => 'reportes.insumos-utilizados',
            ],
            'radiografias-por-mes' => [
                'titulo' => 'Radiografías por mes',
                'grafico' => 'linea',
                'etiqueta' => 'Mes',
                'valor' => 'Radiografías',
                'ruta' => 'reportes.radiografias-por-mes',
            ],
            'esterilizaciones-por-mes' => [
                'titulo' => 'Ciclos de esterilización por mes',
                'grafico' => 'linea',
                'etiqueta' => 'Mes',
                'valor' => 'Ciclos',
                'ruta' => 'reportes.esterilizaciones-por-mes',
            ],
            'pagos-por-tipo' => [
                'titulo' => 'Pagos por tipo (monto cancelado)',
                'grafico' => 'torta',
                'etiqueta' => 'Tipo de pago',
                'valor' => 'Cancelado (Bs)',
                'ruta' => 'reportes.pagos-por-tipo',
            ],
            'ingresos-por-mes' => [
                'titulo' => 'Ingresos por mes (monto cancelado)',
                'grafico' => 'linea',
                'etiqueta' => 'Mes',
                'valor' => 'Cancelado (Bs)',
                'ruta' => 'reportes.ingresos-por-mes',
            ],
        ];
    }

    /* ============================================================
       VISTA PRINCIPAL
       ============================================================ */

    public function index(Request $request)
    {
        $this->authorizeAction('ver');

        return view('reportes.index', [
            'catalogo' => self::catalogo(),
            'periodos' => Periodo::query()
                ->orderByDesc('gestion')
                ->get(['id', 'nombre', 'gestion']),
            'clinicas' => Clinica::query()
                ->orderBy('especialidad')
                ->get(['id', 'especialidad', 'modulo']),
            'filtros' => $this->filtros($request),
        ]);
    }

    /* ============================================================
       ENDPOINTS JSON — un método público por reporte
       ============================================================ */

    public function pacientesPorPeriodo(Request $request): JsonResponse
    {
        return $this->respuestaJson('pacientes-por-periodo', $request);
    }

    public function pacientesPorEspecialidad(Request $request): JsonResponse
    {
        return $this->respuestaJson('pacientes-por-especialidad', $request);
    }

    public function pacientesPorEdad(Request $request): JsonResponse
    {
        return $this->respuestaJson('pacientes-por-edad', $request);
    }

    public function pacientesPorSexo(Request $request): JsonResponse
    {
        return $this->respuestaJson('pacientes-por-sexo', $request);
    }

    public function motivosConsulta(Request $request): JsonResponse
    {
        return $this->respuestaJson('motivos-consulta', $request);
    }

    public function tratamientosPorEspecialidad(Request $request): JsonResponse
    {
        return $this->respuestaJson('tratamientos-por-especialidad', $request);
    }

    public function atencionesPorDocente(Request $request): JsonResponse
    {
        return $this->respuestaJson('atenciones-por-docente', $request);
    }

    public function atencionesPorEstudiante(Request $request): JsonResponse
    {
        return $this->respuestaJson('atenciones-por-estudiante', $request);
    }

    public function insumosUtilizados(Request $request): JsonResponse
    {
        return $this->respuestaJson('insumos-utilizados', $request);
    }

    public function radiografiasPorMes(Request $request): JsonResponse
    {
        return $this->respuestaJson('radiografias-por-mes', $request);
    }

    public function esterilizacionesPorMes(Request $request): JsonResponse
    {
        return $this->respuestaJson('esterilizaciones-por-mes', $request);
    }

    public function pagosPorTipo(Request $request): JsonResponse
    {
        return $this->respuestaJson('pagos-por-tipo', $request);
    }

    public function ingresosPorMes(Request $request): JsonResponse
    {
        return $this->respuestaJson('ingresos-por-mes', $request);
    }

    /**
     * POST /reportes
     *
     * La ruta ya existía en el esqueleto (permiso `reportes.crear`).
     * Se conserva para no romper el contrato de rutas, pero el módulo de
     * reportes es de SOLO LECTURA: no se crean registros.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('crear');

        return response()->json([
            'success' => false,
            'message' => 'El módulo de reportes es de solo lectura: no admite creación de registros.',
        ], 405);
    }

    /* ============================================================
       EXPORTACIONES
       ============================================================ */

    /**
     * GET /reportes/exportar/csv
     *
     * CSV UTF-8 con BOM, generado con fputcsv sobre php://output.
     * Un solo archivo con TODOS los reportes: cada bloque lleva un encabezado
     * "# <título>" y se separa del siguiente con una línea en blanco.
     * Admite ?reporte=<clave> para exportar un único reporte.
     *
     * El separador es ';' (no ',') porque Excel en configuración regional
     * española usa ';' como separador de lista; con ',' todo caería en una
     * sola columna.
     */
    public function exportarCsv(Request $request)
    {
        $this->authorizeAction('ver');

        $catalogo = self::catalogo();
        $solicitado = $request->input('reporte');
        $claves = ($solicitado && isset($catalogo[$solicitado]))
            ? [$solicitado]
            : array_keys($catalogo);

        $nombre = 'reportes_clinica_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($claves, $catalogo, $request) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8: sin esto Excel muestra mal las tildes y la ñ.
            fwrite($salida, "\xEF\xBB\xBF");

            $primero = true;

            foreach ($claves as $clave) {
                $definicion = $catalogo[$clave];
                $datos = $this->datos($clave, $request);

                if (! $primero) {
                    fputcsv($salida, [], ';');   // línea en blanco separadora
                }
                $primero = false;

                fputcsv($salida, ['# '.$definicion['titulo']], ';');
                fputcsv($salida, ['Filtros', $this->descripcionFiltros($request)], ';');
                fputcsv($salida, [$definicion['etiqueta'], $definicion['valor']], ';');

                foreach ($datos['labels'] as $i => $etiqueta) {
                    fputcsv($salida, [$etiqueta, $datos['valores'][$i] ?? 0], ';');
                }

                fputcsv($salida, ['TOTAL', $datos['total']], ';');
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function imprimible(Request $request, ReporteModulo $reporteModulo, ReportePdf $reportePdf): Response
    {
        return $this->exportarPdf($request, $reporteModulo, $reportePdf);
    }

    public function exportarPdf(Request $request, ReporteModulo $reporteModulo, ReportePdf $reportePdf): Response
    {
        $this->authorizeAction('ver');
        $reporteModulo->validarFiltros($request);
        $catalogo = self::catalogo();
        $request->validate(['reporte' => ['nullable', Rule::in(array_keys($catalogo))]]);
        if ($request->filled('reporte')) {
            $catalogo = [$request->input('reporte') => $catalogo[$request->input('reporte')]];
        }
        $datos = [];
        foreach (array_keys($catalogo) as $clave) {
            $datos[$clave] = $this->datos($clave, $request);
        }
        $request->merge(['formato' => 'pdf']);

        return $reportePdf->responder('reportes.estadisticas', [
            'titulo' => 'Reporte estadístico de la clínica', 'codigo' => 'EST-'.now()->format('Ymd'),
            'emitidoPor' => $request->user()->nombre_completo, 'emitidoEn' => now(),
            'volver' => route('reportes.index'), 'catalogo' => $catalogo, 'datos' => $datos,
            'descripcion' => $this->descripcionFiltros($request),
        ], $request, 'estadisticas-clinica-'.now()->format('Ymd-His').'.pdf');
    }

    /* ============================================================
       DESPACHADOR
       ============================================================ */

    /**
     * Datos de un reporte concreto en formato {labels, valores, total}.
     */
    public function datos(string $clave, Request $request): array
    {
        return match ($clave) {
            'pacientes-por-periodo' => $this->datosPacientesPorPeriodo($request),
            'pacientes-por-especialidad' => $this->datosPacientesPorEspecialidad($request),
            'pacientes-por-edad' => $this->datosPacientesPorEdad($request),
            'pacientes-por-sexo' => $this->datosPacientesPorSexo($request),
            'motivos-consulta' => $this->datosMotivosConsulta($request),
            'tratamientos-por-especialidad' => $this->datosTratamientosPorEspecialidad($request),
            'atenciones-por-docente' => $this->datosAtencionesPorDocente($request),
            'atenciones-por-estudiante' => $this->datosAtencionesPorEstudiante($request),
            'insumos-utilizados' => $this->datosInsumosUtilizados($request),
            'radiografias-por-mes' => $this->datosRadiografiasPorMes($request),
            'esterilizaciones-por-mes' => $this->datosEsterilizacionesPorMes($request),
            'pagos-por-tipo' => $this->datosPagosPorTipo($request),
            'ingresos-por-mes' => $this->datosIngresosPorMes($request),
            default => ['labels' => [], 'valores' => [], 'total' => 0],
        };
    }

    protected function respuestaJson(string $clave, Request $request): JsonResponse
    {
        $this->authorizeAction('ver');

        return response()->json($this->datos($clave, $request));
    }

    /* ============================================================
       CONSTRUCTORES DE DATOS
       ============================================================ */

    /** Barras: admisiones agrupadas por periodo. */
    protected function datosPacientesPorPeriodo(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'admisiones as a')
            ->leftJoin('periodos as p', 'p.id', '=', 'a.periodo_id')
            ->selectRaw("COALESCE(NULLIF(p.nombre, ''), 'Sin periodo') AS etiqueta, COUNT(*) AS total, MAX(p.fecha_inicio) AS orden")
            ->groupBy('a.periodo_id', 'p.nombre');

        if ($f['periodo_id']) {
            $query->where('a.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, 'a.created_at');

        $filas = $query->get()->sortBy(static function ($fila) {
            return ($fila->orden ?: '9999-12-31').'|'.$fila->etiqueta;
        })->values();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Torta: admisiones agrupadas por clínica (vía historia clínica). */
    protected function datosPacientesPorEspecialidad(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'historias_clinicas as h')
            ->leftJoin('clinicas as c', 'c.id', '=', 'h.clinica_id')
            ->selectRaw("COALESCE(NULLIF(c.especialidad, ''), NULLIF(c.modulo, ''), 'Sin clínica') AS etiqueta, COUNT(DISTINCT h.admision_id) AS total")
            ->groupBy('h.clinica_id', 'c.especialidad', 'c.modulo')
            ->orderByDesc('total');

        if ($f['clinica_id']) {
            $query->where('h.clinica_id', $f['clinica_id']);
        }
        if ($f['periodo_id']) {
            $query->where('h.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, 'h.created_at');

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Barras: pacientes por grupo de edad (0-5, 6-12, 13-18, 19-30, 31-50, 51+). */
    protected function datosPacientesPorEdad(Request $request): array
    {
        $f = $this->filtros($request);

        $grupos = ['0-5', '6-12', '13-18', '19-30', '31-50', '51+'];

        $caso = "CASE
            WHEN pa.fecha_nacimiento IS NULL THEN 'Sin dato'
            WHEN TIMESTAMPDIFF(YEAR, pa.fecha_nacimiento, CURDATE()) <= 5  THEN '0-5'
            WHEN TIMESTAMPDIFF(YEAR, pa.fecha_nacimiento, CURDATE()) <= 12 THEN '6-12'
            WHEN TIMESTAMPDIFF(YEAR, pa.fecha_nacimiento, CURDATE()) <= 18 THEN '13-18'
            WHEN TIMESTAMPDIFF(YEAR, pa.fecha_nacimiento, CURDATE()) <= 30 THEN '19-30'
            WHEN TIMESTAMPDIFF(YEAR, pa.fecha_nacimiento, CURDATE()) <= 50 THEN '31-50'
            ELSE '51+' END";

        $query = $this->consultaReporte($request, 'pacientes as pa')
            ->selectRaw("{$caso} AS etiqueta, COUNT(*) AS total")
            ->groupByRaw($caso);

        if ($f['clinica_id']) {
            $clinicaId = $f['clinica_id'];
            $query->whereExists(static function ($sub) use ($clinicaId) {
                $sub->selectRaw('1')
                    ->from('historias_clinicas as hc')
                    ->whereColumn('hc.paciente_id', 'pa.id')
                    ->where('hc.clinica_id', $clinicaId);
            });
        }

        if ($f['periodo_id']) {
            $periodoId = $f['periodo_id'];
            $query->whereExists(static function ($sub) use ($periodoId) {
                $sub->selectRaw('1')
                    ->from('admisiones as ad')
                    ->whereColumn('ad.paciente_id', 'pa.id')
                    ->where('ad.periodo_id', $periodoId);
            });
        }

        $this->rango($query, $f, 'pa.created_at');

        $mapa = $query->get()->pluck('total', 'etiqueta');

        $labels = $grupos;
        $valores = array_map(static fn ($grupo) => (int) ($mapa[$grupo] ?? 0), $grupos);

        $sinDato = (int) ($mapa['Sin dato'] ?? 0);
        if ($sinDato > 0) {
            $labels[] = 'Sin dato';
            $valores[] = $sinDato;
        }

        return $this->armar($labels, $valores);
    }

    /** Dona: pacientes por sexo (normaliza el texto libre de `pacientes.genero`). */
    protected function datosPacientesPorSexo(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'pacientes as pa')
            ->selectRaw('pa.genero AS etiqueta, COUNT(*) AS total')
            ->groupBy('pa.genero');

        if ($f['clinica_id']) {
            $clinicaId = $f['clinica_id'];
            $query->whereExists(static function ($sub) use ($clinicaId) {
                $sub->selectRaw('1')
                    ->from('historias_clinicas as hc')
                    ->whereColumn('hc.paciente_id', 'pa.id')
                    ->where('hc.clinica_id', $clinicaId);
            });
        }

        if ($f['periodo_id']) {
            $periodoId = $f['periodo_id'];
            $query->whereExists(static function ($sub) use ($periodoId) {
                $sub->selectRaw('1')
                    ->from('admisiones as ad')
                    ->whereColumn('ad.paciente_id', 'pa.id')
                    ->where('ad.periodo_id', $periodoId);
            });
        }

        $this->rango($query, $f, 'pa.created_at');

        $conteo = ['Masculino' => 0, 'Femenino' => 0, 'Otro' => 0];

        foreach ($query->get() as $fila) {
            $conteo[$this->normalizarSexo($fila->etiqueta)] += (int) $fila->total;
        }

        return $this->armar(array_keys($conteo), array_values($conteo));
    }

    /** Barras horizontales: top 10 motivos de consulta. */
    protected function datosMotivosConsulta(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'admisiones as a')
            ->leftJoin('motivos_consulta as m', 'm.id', '=', 'a.motivo_consulta_id')
            ->selectRaw("COALESCE(NULLIF(m.nombre, ''), MAX(NULLIF(a.motivo_consulta, '')), 'Sin especificar') AS etiqueta, COUNT(*) AS total")
            ->groupBy('a.motivo_consulta_id', 'm.nombre')
            ->orderByDesc('total')
            ->limit(10);

        if ($f['periodo_id']) {
            $query->where('a.periodo_id', $f['periodo_id']);
        }

        $this->filtrarPorClinicaDeAdmision($query, $f);

        $this->rango($query, $f, 'a.created_at');

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Barras: tratamientos realizados por clínica. */
    protected function datosTratamientosPorEspecialidad(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'tratamientos_realizados as tr')
            ->leftJoin('clinicas as c', 'c.id', '=', 'tr.clinica_id')
            ->selectRaw("COALESCE(NULLIF(c.especialidad, ''), NULLIF(c.modulo, ''), 'Sin clínica') AS etiqueta, COUNT(*) AS total")
            ->groupBy('tr.clinica_id', 'c.especialidad', 'c.modulo')
            ->orderByDesc('total');

        if ($f['clinica_id']) {
            $query->where('tr.clinica_id', $f['clinica_id']);
        }

        $this->filtrarPorPeriodoDeHistoria($query, $f, 'tr.historia_clinica_id');

        $this->rango($query, $f, DB::raw('COALESCE(tr.fecha_inicio, tr.created_at)'));

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Barras: top 10 docentes por historias clínicas autorizadas. */
    protected function datosAtencionesPorDocente(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'historias_clinicas as h')
            ->join('usuarios as u', 'u.id', '=', 'h.docente_id')
            ->where('h.autorizado', 1)
            ->selectRaw('u.nombre_completo AS etiqueta, COUNT(*) AS total')
            ->groupBy('h.docente_id', 'u.nombre_completo')
            ->orderByDesc('total')
            ->limit(10);

        if ($f['clinica_id']) {
            $query->where('h.clinica_id', $f['clinica_id']);
        }
        if ($f['periodo_id']) {
            $query->where('h.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, 'h.created_at');

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Barras: top 10 estudiantes por tratamientos realizados. */
    protected function datosAtencionesPorEstudiante(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'tratamientos_realizados as tr')
            ->join('historias_clinicas as h', 'h.id', '=', 'tr.historia_clinica_id')
            ->join('usuarios as u', 'u.id', '=', 'h.estudiante_id')
            ->selectRaw('u.nombre_completo AS etiqueta, COUNT(*) AS total')
            ->groupBy('h.estudiante_id', 'u.nombre_completo')
            ->orderByDesc('total')
            ->limit(10);

        if ($f['clinica_id']) {
            $query->where('tr.clinica_id', $f['clinica_id']);
        }
        if ($f['periodo_id']) {
            $query->where('h.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, DB::raw('COALESCE(tr.fecha_inicio, tr.created_at)'));

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all());
    }

    /** Barras: top 10 insumos por SUM(cantidad_usada). */
    protected function datosInsumosUtilizados(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'insumos_tratamiento_usados as iu')
            ->join('insumos as i', 'i.id', '=', 'iu.insumo_id')
            ->leftJoin('tratamientos_realizados as tr', 'tr.id', '=', 'iu.tratamiento_realizado_id')
            ->leftJoin('historias_clinicas as h', 'h.id', '=', 'tr.historia_clinica_id')
            ->selectRaw('i.nombre AS etiqueta, SUM(iu.cantidad_usada) AS total')
            ->groupBy('iu.insumo_id', 'i.nombre')
            ->orderByDesc('total')
            ->limit(10);

        if ($f['clinica_id']) {
            $query->whereRaw('COALESCE(tr.clinica_id, i.clinica_id) = ?', [$f['clinica_id']]);
        }
        if ($f['periodo_id']) {
            $query->where('h.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, DB::raw('COALESCE(iu.fecha_uso, iu.created_at)'));

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all(), true);
    }

    /** Línea: radiografías por mes (fecha_toma o created_at). */
    protected function datosRadiografiasPorMes(Request $request): array
    {
        $f = $this->filtros($request);

        $expresion = "DATE_FORMAT(COALESCE(r.fecha_toma, r.created_at), '%Y-%m')";

        $query = $this->consultaReporte($request, 'radiologias as r')
            ->selectRaw("{$expresion} AS periodo, COUNT(*) AS total")
            ->groupByRaw($expresion)
            ->orderBy('periodo');

        if ($f['clinica_id']) {
            $query->where('r.clinica_id', $f['clinica_id']);
        }

        // `radiologias` no tiene periodo_id -> ese filtro no se aplica aquí.
        $this->rango($query, $f, DB::raw('COALESCE(r.fecha_toma, r.created_at)'));

        $filas = $query->get();

        return $this->armar(
            $filas->pluck('periodo')->map(fn ($p) => $this->etiquetaMes($p))->all(),
            $filas->pluck('total')->all()
        );
    }

    /** Línea: ciclos de esterilización por mes. */
    protected function datosEsterilizacionesPorMes(Request $request): array
    {
        $f = $this->filtros($request);

        $expresion = "DATE_FORMAT(e.created_at, '%Y-%m')";

        $query = $this->consultaReporte($request, 'esterilizaciones as e')
            ->selectRaw("{$expresion} AS periodo, COUNT(*) AS total")
            ->groupByRaw($expresion)
            ->orderBy('periodo');

        if ($f['clinica_id']) {
            $query->where('e.clinica_id', $f['clinica_id']);
        }
        if ($f['periodo_id']) {
            $query->where('e.periodo_id', $f['periodo_id']);
        }

        $this->rango($query, $f, 'e.created_at');

        $filas = $query->get();

        return $this->armar(
            $filas->pluck('periodo')->map(fn ($p) => $this->etiquetaMes($p))->all(),
            $filas->pluck('total')->all()
        );
    }

    /** Torta: SUM(cancelado) por tipo de pago. */
    protected function datosPagosPorTipo(Request $request): array
    {
        $f = $this->filtros($request);

        $query = $this->consultaReporte($request, 'cajas as ca')
            ->selectRaw("COALESCE(NULLIF(ca.tipo_pago, ''), 'Sin especificar') AS etiqueta, SUM(ca.cancelado) AS total")
            ->groupBy('ca.tipo_pago')
            ->orderByDesc('total');

        if ($f['clinica_id']) {
            $query->where('ca.clinica_id', $f['clinica_id']);
        }

        // `cajas` no tiene periodo_id -> ese filtro no se aplica aquí.
        $this->rango($query, $f, 'ca.created_at');

        $filas = $query->get();

        return $this->armar($filas->pluck('etiqueta')->all(), $filas->pluck('total')->all(), true);
    }

    /** Línea: SUM(cancelado) por mes. */
    protected function datosIngresosPorMes(Request $request): array
    {
        $f = $this->filtros($request);

        $expresion = "DATE_FORMAT(ca.created_at, '%Y-%m')";

        $query = $this->consultaReporte($request, 'cajas as ca')
            ->selectRaw("{$expresion} AS periodo, SUM(ca.cancelado) AS total")
            ->groupByRaw($expresion)
            ->orderBy('periodo');

        if ($f['clinica_id']) {
            $query->where('ca.clinica_id', $f['clinica_id']);
        }

        // `cajas` no tiene periodo_id -> ese filtro no se aplica aquí.
        $this->rango($query, $f, 'ca.created_at');

        $filas = $query->get();

        return $this->armar(
            $filas->pluck('periodo')->map(fn ($p) => $this->etiquetaMes($p))->all(),
            $filas->pluck('total')->all(),
            true
        );
    }

    /* ============================================================
       HELPERS — filtros
       ============================================================ */

    /**
     * Normaliza los filtros globales del query string.
     *
     * @return array{desde: ?string, hasta: ?string, periodo_id: ?int, clinica_id: ?int}
     */
    protected function filtros(Request $request): array
    {
        return [
            'desde' => $this->fechaValida($request->input('desde')),
            'hasta' => $this->fechaValida($request->input('hasta')),
            'periodo_id' => $this->enteroValido($request->input('periodo_id')),
            'clinica_id' => $this->enteroValido($request->input('clinica_id')),
        ];
    }

    private function consultaReporte(Request $request, string $tabla): Builder
    {
        $query = DB::table($tabla);
        $usuario = $request->user();
        if (AccesoClinico::esAdministrador($usuario) || ! $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            return $query;
        }

        $historias = AccesoClinico::historias(HistoriaClinica::query(), $usuario)->select('id');

        return match ($tabla) {
            'admisiones as a' => $query->whereIn('a.id', AccesoClinico::admisiones(Admision::query(), $usuario)->select('id')),
            'historias_clinicas as h' => $query->whereIn('h.id', $historias),
            'pacientes as pa' => $query->whereIn('pa.id', AccesoClinico::pacientes(Paciente::query(), $usuario)->select('id')),
            'tratamientos_realizados as tr' => $query->whereIn('tr.historia_clinica_id', $historias),
            'insumos_tratamiento_usados as iu' => $query->whereIn('iu.tratamiento_realizado_id',
                TratamientoRealizado::query()->whereIn('historia_clinica_id', $historias)->select('id')),
            'radiologias as r' => $query->whereIn('r.id', AccesoClinico::radiologias(Radiologia::query(), $usuario)->select('id')),
            'esterilizaciones as e' => $query->whereIn('e.id', AccesoClinico::esterilizaciones(Esterilizacion::query(), $usuario)->select('id')),
            'cajas as ca' => $query->whereIn('ca.tratamiento_realizado_id',
                TratamientoRealizado::query()->whereIn('historia_clinica_id', $historias)->select('id')),
        };
    }

    protected function fechaValida($valor): ?string
    {
        $valor = is_string($valor) ? trim($valor) : '';

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : null;
    }

    protected function enteroValido($valor): ?int
    {
        if (! is_numeric($valor)) {
            return null;
        }

        $valor = (int) $valor;

        return $valor > 0 ? $valor : null;
    }

    /** Aplica el rango desde/hasta sobre una columna (o expresión) de fecha. */
    protected function rango($query, array $filtros, $columna)
    {
        if ($filtros['desde']) {
            $query->whereDate($columna, '>=', $filtros['desde']);
        }
        if ($filtros['hasta']) {
            $query->whereDate($columna, '<=', $filtros['hasta']);
        }

        return $query;
    }

    /**
     * Filtra admisiones por clínica a través de su historia clínica.
     * Se usa whereExists (y no un join) para no multiplicar filas.
     */
    protected function filtrarPorClinicaDeAdmision($query, array $filtros): void
    {
        if (! $filtros['clinica_id']) {
            return;
        }

        $clinicaId = $filtros['clinica_id'];

        $query->whereExists(static function ($sub) use ($clinicaId) {
            $sub->selectRaw('1')
                ->from('historias_clinicas as hc')
                ->whereColumn('hc.admision_id', 'a.id')
                ->where('hc.clinica_id', $clinicaId);
        });
    }

    /**
     * Filtra tratamientos realizados por periodo a través de su historia clínica.
     * whereExists evita duplicar filas cuando un tratamiento tiene varias historias.
     */
    protected function filtrarPorPeriodoDeHistoria($query, array $filtros, string $columnaHistoria): void
    {
        if (! $filtros['periodo_id']) {
            return;
        }

        $periodoId = $filtros['periodo_id'];

        $query->whereExists(static function ($sub) use ($periodoId, $columnaHistoria) {
            $sub->selectRaw('1')
                ->from('historias_clinicas as hc')
                ->whereColumn('hc.id', $columnaHistoria)
                ->where('hc.periodo_id', $periodoId);
        });
    }

    /* ============================================================
       HELPERS — formato
       ============================================================ */

    /**
     * Empaqueta el resultado en el contrato {labels, valores, total}.
     */
    protected function armar(array $labels, array $valores, bool $decimal = false): array
    {
        $labels = array_values($labels);
        $valores = array_map(static function ($valor) use ($decimal) {
            $valor = (float) ($valor ?? 0);

            return $decimal ? round($valor, 2) : (int) round($valor);
        }, array_values($valores));

        return [
            'labels' => $labels,
            'valores' => $valores,
            'total' => $decimal ? round(array_sum($valores), 2) : array_sum($valores),
        ];
    }

    /** '2026-03' -> 'Mar 2026' (locale español, sin tocar la config global). */
    protected function etiquetaMes(?string $periodo): string
    {
        if (! $periodo) {
            return 'Sin fecha';
        }

        try {
            return ucfirst(Carbon::createFromFormat('Y-m', $periodo)->locale('es')->translatedFormat('M Y'));
        } catch (\Throwable $e) {
            return $periodo;
        }
    }

    /**
     * Normaliza el texto libre de `pacientes.genero` a Masculino/Femenino/Otro.
     */
    protected function normalizarSexo($valor): string
    {
        $texto = mb_strtolower(trim((string) $valor));
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú'],
            ['a', 'e', 'i', 'o', 'u'],
            $texto
        );

        if ($texto === '') {
            return 'Otro';
        }

        if (in_array($texto, ['m', 'masc', 'masculino', 'hombre', 'varon', 'male', 'h'], true)) {
            return 'Masculino';
        }

        if (in_array($texto, ['f', 'fem', 'femenino', 'mujer', 'female'], true)) {
            return 'Femenino';
        }

        return 'Otro';
    }

    /** Texto legible con los filtros activos (para CSV e imprimible). */
    protected function descripcionFiltros(Request $request): string
    {
        $f = $this->filtros($request);
        $partes = [];

        if ($f['desde']) {
            $partes[] = 'desde '.$f['desde'];
        }
        if ($f['hasta']) {
            $partes[] = 'hasta '.$f['hasta'];
        }
        if ($f['periodo_id']) {
            $partes[] = 'periodo #'.$f['periodo_id'];
        }
        if ($f['clinica_id']) {
            $partes[] = 'clínica #'.$f['clinica_id'];
        }

        return $partes ? implode(', ', $partes) : 'sin filtros (todo el histórico)';
    }

    /* ============================================================
       HELPERS — permisos (mismo patrón que HasCrudAjax::authorizeAction)
       ============================================================ */

    /**
     * Autoriza según permiso `reportes.{accion}`.
     * Soporta tanto auth() como la sesión custom (session('user_id')).
     */
    protected function authorizeAction(string $accion = 'ver'): void
    {
        $user = auth()->user() ?? Usuario::find(session('user_id'));

        $permiso = "reportes.{$accion}";

        abort_unless(
            $user?->can($permiso) || $user?->hasRole('admin'),
            403,
            "No tienes permiso {$permiso}"
        );
    }
}
