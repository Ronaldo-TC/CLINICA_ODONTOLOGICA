<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Clinica;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * InvestigacionController
 *
 * Módulo INVESTIGACIÓN — Instituto de Investigación.
 *
 * Entrega datos ANONIMIZADOS de pacientes para estudios académicos.
 * Reglas de anonimización:
 *   - Sólo se incluyen registros cuya admisión tenga `consentimiento_investigacion = 1`.
 *   - La identidad del paciente se sustituye por un código irreversible:
 *     substr(hash('sha256', $pacienteId . config('app.key')), 0, 12).
 *   - NUNCA se devuelven nombre, CI, apellidos, teléfono, dirección ni el id real.
 */
class InvestigacionController extends Controller
{
    /** Registros por página del dataset anonimizado. */
    private const PER_PAGE = 15;

    /** Filtros aceptados por el módulo (query string). */
    private const FILTROS = ['anio', 'clinica', 'edad_min', 'edad_max', 'genero', 'motivo'];

    /** Cabeceras del CSV exportado. */
    private const CSV_HEADERS = [
        'codigo',
        'edad',
        'genero',
        'motivo',
        'especialidad',
        'tratamiento',
        'periodo',
        'estado',
    ];

    /* ============================================================
       ACCIONES
       ============================================================ */

    /**
     * GET /investigacion
     * Vista de cohortes anonimizadas.
     */
    public function index(Request $request)
    {
        $this->autorizar(['investigacion.ver']);

        $registros = $this->paginar($request);

        $this->registrarBitacora($request, 'consulta_dataset', $registros->total());

        return view('investigacion.index', [
            'registros' => $registros,
            'items'     => $registros,   // alias por comodidad (igual que HasCrudAjax)
            'anios'     => $this->aniosDisponibles(),
            'clinicas'  => Clinica::query()->orderBy('especialidad')->get(['id', 'modulo', 'especialidad']),
            'generos'   => $this->generosDisponibles(),
            'motivos'   => $this->motivosDisponibles(),
        ]);
    }

    /**
     * GET /investigacion/tabla
     * HTML parcial + paginación + total (mismo contrato que los demás módulos).
     */
    public function tabla(Request $request): JsonResponse
    {
        $this->autorizar(['investigacion.ver']);

        $registros = $this->paginar($request);

        return response()->json([
            'filas'      => view('investigacion.partials.filas', [
                'registros' => $registros,
                'items'     => $registros,
            ])->render(),
            'paginacion' => $registros->links()->toHtml(),
            'total'      => $registros->total(),
        ]);
    }

    /**
     * GET /investigacion/estadisticas
     * Estadística descriptiva por variable sobre el dataset anonimizado.
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $this->autorizar(['investigacion.ver']);

        $filas = $this->datasetCompleto($request);

        return response()->json($this->calcularEstadisticas($filas));
    }

    /**
     * GET /investigacion/exportar
     * CSV UTF-8 con BOM (compatible con Excel) del dataset anonimizado.
     */
    public function exportar(Request $request)
    {
        $this->autorizar(['investigacion.ver', 'investigacion.gestionar']);

        $filas = $this->datasetCompleto($request);

        $this->registrarBitacora($request, 'exportacion_dataset', $filas->count());

        $nombre = 'dataset_anonimizado_' . now()->format('Ymd') . '.csv';

        $response = response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8: Excel abre el archivo con acentos correctos.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, self::CSV_HEADERS);

            foreach ($filas as $fila) {
                fputcsv($out, [
                    $fila['codigo'],
                    $fila['edad'] ?? '',
                    $fila['genero'],
                    $fila['motivo'],
                    $fila['especialidad'],
                    $fila['tratamiento'],
                    $fila['periodo'],
                    $fila['estado'],
                ]);
            }

            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $nombre);

        return $response;
    }

    /* ============================================================
       DATASET ANONIMIZADO
       ============================================================ */

    /**
     * Query base del dataset. Una fila del dataset = un tratamiento realizado
     * de una historia clínica cuya admisión autorizó la investigación.
     * Los pacientes sin tratamientos registrados también aparecen (LEFT JOIN).
     */
    private function datasetQuery(Request $request)
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('pacientes as p', 'p.id', '=', 'hc.paciente_id')
            ->join('admisiones as a', 'a.id', '=', 'hc.admision_id')
            ->leftJoin('clinicas as c', 'c.id', '=', 'hc.clinica_id')
            ->leftJoin('periodos as pe', 'pe.id', '=', 'hc.periodo_id')
            ->leftJoin('motivos_consulta as mc', 'mc.id', '=', 'a.motivo_consulta_id')
            ->leftJoin('tratamientos_realizados as tr', 'tr.historia_clinica_id', '=', 'hc.id')
            ->leftJoin('tratamientos as t', 't.id', '=', 'tr.tratamiento_id')
            ->where('a.consentimiento_investigacion', true)
            ->select([
                'p.id as paciente_ref',
                'p.fecha_nacimiento as fecha_nacimiento',
                'p.genero as genero',
                'a.motivo_consulta as motivo_texto',
                'mc.nombre as motivo_catalogo',
                'c.especialidad as especialidad',
                't.nombre as tratamiento',
                'pe.nombre as periodo_nombre',
                'pe.gestion as periodo_gestion',
                'pe.semestre as periodo_semestre',
                'hc.estado as estado_historia',
                'tr.estado as estado_tratamiento',
            ])
            ->orderBy('hc.id')
            ->orderBy('tr.id');

        $this->aplicarFiltros($query, $request);

        return $query;
    }

    /**
     * Aplica los filtros del formulario a la query del dataset.
     */
    private function aplicarFiltros($query, Request $request): void
    {
        if ($request->filled('anio')) {
            $query->whereYear('hc.created_at', (int) $request->input('anio'));
        }

        if ($request->filled('clinica')) {
            $query->where('hc.clinica_id', (int) $request->input('clinica'));
        }

        if ($request->filled('edad_min')) {
            $query->whereRaw(
                'TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) >= ?',
                [(int) $request->input('edad_min')]
            );
        }

        if ($request->filled('edad_max')) {
            $query->whereRaw(
                'TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) <= ?',
                [(int) $request->input('edad_max')]
            );
        }

        if ($request->filled('genero')) {
            $query->where('p.genero', $request->input('genero'));
        }

        if ($request->filled('motivo')) {
            $query->whereRaw(
                "COALESCE(NULLIF(mc.nombre, ''), NULLIF(a.motivo_consulta, '')) = ?",
                [$request->input('motivo')]
            );
        }
    }

    /**
     * Paginador con las filas ya anonimizadas (sin id real, sin hash completo).
     */
    private function paginar(Request $request)
    {
        $paginator = $this->datasetQuery($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($fila) => $this->anonimizar($fila))->values()
        );

        return $paginator;
    }

    /**
     * Colección completa (sin paginar) de filas anonimizadas.
     */
    private function datasetCompleto(Request $request)
    {
        return $this->datasetQuery($request)
            ->get()
            ->map(fn ($fila) => $this->anonimizar($fila))
            ->values();
    }

    /**
     * Convierte una fila cruda en una fila anonimizada.
     * Sólo se conservan variables clínicas y académicas no identificables.
     */
    private function anonimizar(object $fila): array
    {
        return [
            'codigo'       => $this->codigoAnonimo((int) $fila->paciente_ref),
            'edad'         => $this->edad($fila->fecha_nacimiento),
            'genero'       => $this->texto($fila->genero, 'No especificado'),
            'motivo'       => $this->texto($fila->motivo_catalogo ?: $fila->motivo_texto, 'No especificado'),
            'especialidad' => $this->texto($fila->especialidad, 'Sin asignar'),
            'tratamiento'  => $this->texto($fila->tratamiento, 'Sin tratamiento registrado'),
            'periodo'      => $this->periodo($fila),
            'estado'       => $this->texto($fila->estado_tratamiento ?: $fila->estado_historia, 'Sin estado'),
        ];
    }

    /**
     * Identidad anonimizada e irreversible del paciente.
     * NUNCA se expone el id real ni el hash completo.
     */
    private function codigoAnonimo(int $pacienteId): string
    {
        return substr(hash('sha256', $pacienteId . config('app.key')), 0, 12);
    }

    private function edad($fechaNacimiento): ?int
    {
        if (empty($fechaNacimiento)) {
            return null;
        }

        try {
            return Carbon::parse($fechaNacimiento)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function periodo(object $fila): string
    {
        if ($texto = $this->texto($fila->periodo_nombre ?? null, '')) {
            return $texto;
        }

        if ($fila->periodo_gestion !== null) {
            return trim($fila->periodo_gestion . '-' . ($fila->periodo_semestre ?? ''));
        }

        return 'Sin periodo';
    }

    private function texto($valor, string $defecto): string
    {
        $valor = is_string($valor) ? trim($valor) : $valor;

        return ($valor === null || $valor === '') ? $defecto : (string) $valor;
    }

    /* ============================================================
       ESTADÍSTICA DESCRIPTIVA
       ============================================================ */

    private function calcularEstadisticas($filas): array
    {
        $edades = $filas
            ->pluck('edad')
            ->filter(fn ($edad) => $edad !== null)
            ->map(fn ($edad) => (int) $edad)
            ->values()
            ->all();

        return [
            'edad'         => $this->estadisticasEdad($edades),
            'genero'       => $this->distribucion($filas, 'genero'),
            'especialidad' => $this->distribucion($filas, 'especialidad'),
            'periodo'      => $this->distribucion($filas, 'periodo'),
            'total'        => $filas->count(),
        ];
    }

    private function estadisticasEdad(array $edades): array
    {
        $n = count($edades);

        if ($n === 0) {
            return [
                'n'          => 0,
                'media'      => null,
                'mediana'    => null,
                'moda'       => null,
                'min'        => null,
                'max'        => null,
                'desviacion' => null,
            ];
        }

        sort($edades);

        $media = array_sum($edades) / $n;

        $mediana = ($n % 2 === 1)
            ? (float) $edades[intdiv($n, 2)]
            : ($edades[intdiv($n, 2) - 1] + $edades[intdiv($n, 2)]) / 2;

        $frecuencias   = array_count_values($edades);
        $maxFrecuencia = max($frecuencias);
        $modas         = array_keys($frecuencias, $maxFrecuencia);
        sort($modas);

        $varianza = $n > 1
            ? array_sum(array_map(fn ($edad) => ($edad - $media) ** 2, $edades)) / ($n - 1)
            : 0.0;

        return [
            'n'          => $n,
            'media'      => round($media, 2),
            'mediana'    => round($mediana, 2),
            'moda'       => (int) $modas[0],
            'min'        => min($edades),
            'max'        => max($edades),
            'desviacion' => round(sqrt($varianza), 2),
        ];
    }

    private function distribucion($filas, string $campo): object
    {
        $conteo = [];

        foreach ($filas as $fila) {
            $clave = (string) $fila[$campo];
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        arsort($conteo);

        return (object) $conteo;
    }

    /* ============================================================
       CATÁLOGOS PARA LOS FILTROS
       ============================================================ */

    private function aniosDisponibles()
    {
        return DB::table('historias_clinicas as hc')
            ->join('admisiones as a', 'a.id', '=', 'hc.admision_id')
            ->where('a.consentimiento_investigacion', true)
            ->whereNotNull('hc.created_at')
            ->selectRaw('YEAR(hc.created_at) as anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');
    }

    private function generosDisponibles()
    {
        return Paciente::query()
            ->whereNotNull('genero')
            ->where('genero', '<>', '')
            ->distinct()
            ->orderBy('genero')
            ->pluck('genero');
    }

    private function motivosDisponibles()
    {
        $catalogo = MotivoConsulta::query()
            ->whereNotNull('nombre')
            ->where('nombre', '<>', '')
            ->orderBy('nombre')
            ->pluck('nombre');

        $texto = DB::table('admisiones')
            ->where('consentimiento_investigacion', true)
            ->whereNotNull('motivo_consulta')
            ->where('motivo_consulta', '<>', '')
            ->distinct()
            ->pluck('motivo_consulta');

        return $catalogo->merge($texto)->unique()->sort()->values();
    }

    /* ============================================================
       PERMISOS Y BITÁCORA
       ============================================================ */

    /**
     * Autoriza según permisos `investigacion.*` (admin siempre puede).
     */
    private function autorizar(array $permisos): void
    {
        $usuario = auth()->user() ?? Usuario::find(session('user_id'));

        abort_unless(
            $usuario && ($usuario->hasRole('admin') || $usuario->canAny($permisos)),
            403,
            'No tienes permiso para consultar los datos de investigación.'
        );
    }

    /**
     * Registra la consulta del dataset en `bitacoras`.
     * Sólo index y exportar; la tabla AJAX no se registra.
     */
    private function registrarBitacora(Request $request, string $accion, int $registros): void
    {
        try {
            $filtros = array_filter(
                $request->only(self::FILTROS),
                fn ($valor) => $valor !== null && $valor !== ''
            );

            Bitacora::create([
                'usuario_id' => auth()->id() ?? session('user_id'),
                'ip'         => $request->ip() ?? '0.0.0.0',
                'fecha'      => now(),
                'tipo'       => 'investigacion',
                'datos'      => json_encode([
                    'accion'    => $accion,
                    'filtros'   => $filtros,
                    'registros' => $registros,
                ], JSON_UNESCAPED_UNICODE),
                'estado'     => 'ok',
            ]);
        } catch (\Throwable $e) {
            // La bitácora nunca debe interrumpir la consulta del dataset.
        }
    }
}
