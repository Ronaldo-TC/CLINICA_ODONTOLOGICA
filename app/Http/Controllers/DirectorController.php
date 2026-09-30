<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Radiologia;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * DirectorController
 *
 * Rol: Director de Carrera (pág. 2, 6, 9 del PDF).
 * Acceso de SOLO LECTURA a todos los módulos + reportes globales.
 * No crea, no edita, no elimina.
 */
class DirectorController extends Controller
{
    // ============================================================
    // HELPERS
    // ============================================================

    private function userId(): int
    {
        return (int) session('user_id');
    }

    private function toBool($value, ?bool $default = null): ?bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function bitacora(string $tipo, array $datos, Request $request): void
    {
        try {
            Bitacora::create([
                'usuario_id' => $this->userId(),
                'ip'         => $request->ip() ?? '0.0.0.0',
                'fecha'      => now(),
                'tipo'       => substr($tipo, 0, 20),
                'datos'      => json_encode($datos, JSON_UNESCAPED_UNICODE),
                'estado'     => 'OK',
            ]);
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    private function esperaJson(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson() || $request->expectsJson();
    }

    /**
     * Respuesta estándar de tabla AJAX (igual que AdminController).
     */
    private function responderTabla(string $html, $paginador): JsonResponse
    {
        return response()->json([
            'filas' => $html,
            'paginacion' => $paginador->hasPages()
                ? '<span class="info">Mostrando ' . $paginador->firstItem() . '–' . $paginador->lastItem()
                . ' de ' . $paginador->total() . ' registros</span>'
                . '<div>' . $paginador->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $paginador->total(),
        ]);
    }

    /**
     * Totales de caja reutilizables (para la vista y para la tabla AJAX).
     */
    private function totalesCajas($cajas): array
    {
        return [
            'total'     => (float) $cajas->sum('costo_total'),
            'cancelado' => (float) $cajas->sum('cancelado'),
            'saldo'     => (float) $cajas->sum('costo_total') - (float) $cajas->sum('cancelado'),
        ];
    }

    // ============================================================
    // 1. DASHBOARD
    // ============================================================

    public function index(Request $request)
    {
        $stats = $this->calcularStats();

        $periodoActivo = Periodo::where('habilitado', true)->first();

        $afluenciaClinicas = DB::table('remisiones as r')
            ->join('clinicas as c', 'c.id', '=', 'r.clinica_id')
            ->where('r.habilitado', true)
            ->when($periodoActivo, fn($q) => $q->where('r.periodo_id', $periodoActivo->id))
            ->select('c.especialidad', DB::raw('COUNT(DISTINCT r.paciente_id) as total_pacientes'))
            ->groupBy('c.id', 'c.especialidad')
            ->orderByDesc('total_pacientes')
            ->limit(10)
            ->get();

        $cajasPeriodo = DB::table('cajas as ca')
            ->leftJoin('remisiones as r', 'r.id', '=', 'ca.remision_id')
            ->leftJoin('periodos as pe', 'pe.id', '=', 'r.periodo_id')
            ->when($periodoActivo, fn($q) => $q->where('pe.id', $periodoActivo->id))
            ->select(
                'pe.nombre as periodo',
                DB::raw('COUNT(ca.id) as total_recibos'),
                DB::raw('COALESCE(SUM(ca.costo_total), 0) as total_cobrado'),
                DB::raw('COALESCE(SUM(ca.cancelado), 0) as total_cancelado')
            )
            ->groupBy('pe.id', 'pe.nombre')
            ->orderByDesc('pe.id')
            ->limit(5)
            ->get();

        $tratamientosPorEstado = TratamientoRealizado::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        if ($this->esperaJson($request)) {
            return response()->json([
                'success'               => true,
                'stats'                 => $stats,
                'afluencia_clinicas'    => $afluenciaClinicas,
                'cajas_periodo'         => $cajasPeriodo,
                'tratamientos_estado'   => $tratamientosPorEstado,
                'periodo_activo'        => $periodoActivo,
            ]);
        }

        return view('director.dashboard', compact(
            'stats',
            'afluenciaClinicas',
            'cajasPeriodo',
            'tratamientosPorEstado',
            'periodoActivo'
        ));
    }

    public function statsDashboard(): JsonResponse
    {
        return response()->json($this->calcularStats());
    }

    private function calcularStats(): array
    {
        $periodoActivo = Periodo::where('habilitado', true)->first();

        return [
            'pacientes_total'        => Paciente::count(),
            'pacientes_periodo'      => HistoriaClinica::when($periodoActivo, fn($q) => $q->where('periodo_id', $periodoActivo->id))
                ->distinct('paciente_id')
                ->count('paciente_id'),
            'clinicas_total'         => Clinica::count(),
            'clinicas_activas'       => Clinica::whereHas('remisiones', fn($q) => $q->where('habilitado', true))->count(),
            'docentes_activos'       => Usuario::where('tipo', 'docente')->where('habilitado', true)->count(),
            'estudiantes_activos'    => Usuario::where('tipo', 'estudiante')->where('habilitado', true)->count(),
            'historias_total'        => HistoriaClinica::count(),
            'historias_autorizadas'  => HistoriaClinica::where('autorizado', true)->count(),
            'historias_pendientes'   => HistoriaClinica::where('autorizado', false)->count(),
            'tratamientos_total'     => TratamientoRealizado::count(),
            'tratamientos_completados' => TratamientoRealizado::where('estado', 'completado')->count(),
            'ingresos_total'         => (float) Caja::sum('cancelado'),
            'ingresos_periodo'       => (float) Caja::when($periodoActivo, function ($q) use ($periodoActivo) {
                $q->whereHas('remision', fn($sub) => $sub->where('periodo_id', $periodoActivo->id));
            })->sum('cancelado'),
            'insumos_total'          => Insumo::count(),
            'insumos_bajo_stock'     => Insumo::whereRaw('cantidad_numerica <= stock_minimo')->count(),
            'radiologias_total'      => Radiologia::count(),
            'esterilizaciones_total' => Esterilizacion::count(),
        ];
    }

    // ============================================================
    // 2. PACIENTES (solo lectura)
    // ============================================================

    public function pacientes(Request $request): View
    {
        $pacientes = $this->queryPacientes($request)->paginate(20);

        return view('director.pacientes.index', compact('pacientes'));
    }

    public function tablaPacientes(Request $request): JsonResponse
    {
        $pacientes = $this->queryPacientes($request)->paginate(20);

        return $this->responderTabla(
            view('director.pacientes.partials.filas', compact('pacientes'))->render(),
            $pacientes
        );
    }

    public function pacienteJson(int $id): JsonResponse
    {
        return response()->json(['paciente' => Paciente::findOrFail($id)]);
    }

    private function queryPacientes(Request $request)
    {
        $query = Paciente::query();

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->where('ci', 'LIKE', "%{$b}%")
                    ->orWhere('nombre', 'LIKE', "%{$b}%")
                    ->orWhere('apellido_paterno', 'LIKE', "%{$b}%")
                    ->orWhere('apellido_materno', 'LIKE', "%{$b}%");
            });
        }
        if ($request->filled('genero')) {
            $query->where('genero', $request->input('genero'));
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // 3. CLÍNICAS (solo lectura)
    // ============================================================

    public function clinicas(Request $request): View
    {
        $clinicas = $this->queryClinicas($request)->paginate(20);

        return view('director.clinicas.index', compact('clinicas'));
    }

    public function tablaClinicas(Request $request): JsonResponse
    {
        $clinicas = $this->queryClinicas($request)->paginate(20);

        return $this->responderTabla(
            view('director.clinicas.partials.filas', compact('clinicas'))->render(),
            $clinicas
        );
    }

    public function clinicaJson(int $id): JsonResponse
    {
        return response()->json(['clinica' => Clinica::findOrFail($id)]);
    }

    private function queryClinicas(Request $request)
    {
        $query = Clinica::query();

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->where('especialidad', 'LIKE', "%{$b}%")
                    ->orWhere('modulo', 'LIKE', "%{$b}%");
            });
        }

        return $query->orderBy('especialidad');
    }

    // ============================================================
    // 4. PERIODOS (solo lectura)
    // ============================================================

    public function periodos(Request $request): View
    {
        $periodos = $this->queryPeriodos($request)->paginate(20);

        return view('director.periodos.index', compact('periodos'));
    }

    public function tablaPeriodos(Request $request): JsonResponse
    {
        $periodos = $this->queryPeriodos($request)->paginate(20);

        return $this->responderTabla(
            view('director.periodos.partials.filas', compact('periodos'))->render(),
            $periodos
        );
    }

    public function periodoJson(int $id): JsonResponse
    {
        return response()->json(['periodo' => Periodo::findOrFail($id)]);
    }

    private function queryPeriodos(Request $request)
    {
        $query = Periodo::query();

        if ($request->filled('buscar')) {
            $query->where('nombre', 'LIKE', '%' . $request->input('buscar') . '%');
        }
        if ($request->filled('gestion')) {
            $query->where('gestion', $request->input('gestion'));
        }
        if ($request->filled('semestre')) {
            $query->where('semestre', $request->input('semestre'));
        }
        if ($request->filled('habilitado')) {
            $query->where('habilitado', $this->toBool($request->input('habilitado'), true));
        }

        return $query->orderByDesc('gestion')->orderByDesc('semestre');
    }

    // ============================================================
    // 5. TRATAMIENTOS (solo lectura)
    // ============================================================

    public function tratamientos(Request $request): View
    {
        $tratamientos = $this->queryTratamientos($request)->paginate(20);
        $clinicas     = Clinica::orderBy('especialidad')->get();   // ✅ FIX

        return view('director.tratamientos.index', compact('tratamientos', 'clinicas'));
    }

    public function tablaTratamientos(Request $request): JsonResponse
    {
        $tratamientos = $this->queryTratamientos($request)->paginate(20);

        return $this->responderTabla(
            view('director.tratamientos.partials.filas', compact('tratamientos'))->render(),
            $tratamientos
        );
    }

    public function tratamientoJson(int $id): JsonResponse
    {
        return response()->json(['tratamiento' => Tratamiento::with('clinica')->findOrFail($id)]);
    }

    private function queryTratamientos(Request $request)
    {
        $query = Tratamiento::with('clinica');

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('buscar')) {
            $query->where('nombre', 'LIKE', '%' . $request->input('buscar') . '%');
        }

        return $query->orderBy('nombre');
    }

    // ============================================================
    // 6. HISTORIAS CLÍNICAS (solo lectura)
    // ============================================================

    public function historias(Request $request): View
    {
        $historias   = $this->queryHistorias($request)->paginate(20);
        $clinicas    = Clinica::orderBy('especialidad')->get();
        $periodos    = Periodo::orderByDesc('gestion')->get();
        $docentes    = Usuario::where('tipo', 'docente')
            ->where('habilitado', true)
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo']);
        $estudiantes = Usuario::where(function ($q) {
            $q->where('tipo', 'estudiante')->orWhere('tipo', 'interno');
        })
            ->where('habilitado', true)
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo']);

        return view('director.historias.index', compact(
            'historias',
            'clinicas',
            'periodos',
            'docentes',
            'estudiantes'
        ));
    }

    public function historiaJson(int $id): JsonResponse
    {
        $historia = HistoriaClinica::with([
            'paciente',
            'admision',
            'clinica',
            'estudiante',
            'docente',
            'tratamientosRealizados.tratamiento',
        ])->findOrFail($id);

        return response()->json(['historia' => $historia]);
    }

    private function queryHistorias(Request $request)
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('pacientes as p', 'p.id', '=', 'hc.paciente_id')
            ->join('clinicas as c', 'c.id', '=', 'hc.clinica_id')
            ->join('periodos as pe', 'pe.id', '=', 'hc.periodo_id')
            ->join('usuarios as est', 'est.id', '=', 'hc.estudiante_id')
            ->join('usuarios as doc', 'doc.id', '=', 'hc.docente_id')
            ->select(
                'hc.*',
                'p.nombre as paciente_nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'c.especialidad as clinica',
                'pe.nombre as periodo',
                'est.nombre_completo as estudiante',
                'doc.nombre_completo as docente'
            );

        if ($request->filled('estado_revision')) {
            $query->where('hc.estado_revision', $this->toBool($request->input('estado_revision'), false));
        }
        if ($request->filled('autorizado')) {
            $query->where('hc.autorizado', $this->toBool($request->input('autorizado'), false));
        }
        if ($request->filled('clinica_id')) {
            $query->where('hc.clinica_id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('periodo_id')) {
            $query->where('hc.periodo_id', (int) $request->input('periodo_id'));
        }

        return $query->orderByDesc('hc.created_at');
    }

    // ============================================================
    // 7. CAJAS (solo lectura)
    // ============================================================

    public function cajas(Request $request): View
    {
        $cajas        = $this->queryCajas($request)->paginate(20);
        $totales      = $this->totalesCajas($cajas);
        $clinicas     = Clinica::orderBy('especialidad')->get();      // ✅ FIX
        $tratamientos = Tratamiento::orderBy('nombre')->get();        // ✅ FIX

        return view('director.cajas.index', compact('cajas', 'totales', 'clinicas', 'tratamientos'));
    }

    public function tablaCajas(Request $request): JsonResponse
    {
        $cajas = $this->queryCajas($request)->paginate(20);

        return response()->json([
            'filas' => view('director.cajas.partials.filas', compact('cajas'))->render(),
            'paginacion' => $cajas->hasPages()
                ? '<span class="info">Mostrando ' . $cajas->firstItem() . '–' . $cajas->lastItem()
                . ' de ' . $cajas->total() . ' registros</span>'
                . '<div>' . $cajas->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total'   => $cajas->total(),
            'totales' => $this->totalesCajas($cajas),
        ]);
    }

    public function cajaJson(int $id): JsonResponse
    {
        $caja = Caja::with(['tratamiento', 'clinica', 'usuario'])->findOrFail($id);

        return response()->json(['caja' => $caja]);
    }

    private function queryCajas(Request $request)
    {
        $query = Caja::with(['tratamiento', 'clinica']);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('fecha_inicio'))->startOfDay(),
                Carbon::parse($request->input('fecha_fin'))->endOfDay(),
            ]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('fecha_inicio'))->startOfDay());
        } elseif ($request->filled('fecha_fin')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('fecha_fin'))->endOfDay());
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->input('tipo_pago'));
        }
        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->where('paciente_nombre_completo', 'LIKE', "%{$b}%")
                    ->orWhereRaw('CAST(recibo AS CHAR) LIKE ?', ["%{$b}%"]);
            });
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // 8. INSUMOS (solo lectura)
    // ============================================================

    public function insumos(Request $request): View
    {
        $insumos            = $this->queryInsumos($request)->paginate(20);
        $clinicas           = Clinica::orderBy('especialidad')->get();              // ✅ FIX
        $insumoTratamientos = InsumoTratamiento::orderBy('nombre')->get();         // ✅ FIX

        return view('director.insumos.index', compact('insumos', 'clinicas', 'insumoTratamientos'));
    }

    public function tablaInsumos(Request $request): JsonResponse
    {
        $insumos = $this->queryInsumos($request)->paginate(20);

        return $this->responderTabla(
            view('director.insumos.partials.filas', compact('insumos'))->render(),
            $insumos
        );
    }

    public function insumoJson(int $id): JsonResponse
    {
        return response()->json(['insumo' => Insumo::with('clinica', 'insumoTratamiento')->findOrFail($id)]);
    }

    private function queryInsumos(Request $request)
    {
        $query = DB::table('insumos as i')
            ->leftJoin('insumos_tratamiento as it', 'it.id', '=', 'i.insumo_tratamiento_id')
            ->join('clinicas as c', 'c.id', '=', 'i.clinica_id')
            ->select('i.*', 'it.nombre as tipo_insumo', 'c.especialidad as clinica');

        if ($request->filled('clinica_id')) {
            $query->where('i.clinica_id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->where('i.nombre', 'LIKE', "%{$b}%")
                    ->orWhere('it.nombre', 'LIKE', "%{$b}%");
            });
        }
        if ($request->filled('bajo_stock')) {
            $query->whereRaw('i.cantidad_numerica <= i.stock_minimo');
        }

        return $query->orderBy('c.especialidad')->orderBy('i.nombre');
    }

    // ============================================================
    // 9. ESTERILIZACIONES (solo lectura)
    // ============================================================

    public function esterilizaciones(Request $request): View
    {
        $esterilizaciones = $this->queryEsterilizaciones($request)->paginate(20);
        $clinicas         = Clinica::orderBy('especialidad')->get();              // ✅ FIX
        $estudiantes      = Usuario::where(function ($q) {
            $q->where('tipo', 'estudiante')->orWhere('tipo', 'interno');
        })
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo']);                                    // ✅ FIX

        return view('director.esterilizaciones.index', compact(
            'esterilizaciones',
            'clinicas',
            'estudiantes'
        ));
    }

    public function tablaEsterilizaciones(Request $request): JsonResponse
    {
        $esterilizaciones = $this->queryEsterilizaciones($request)->paginate(20);

        return $this->responderTabla(
            view('director.esterilizaciones.partials.filas', compact('esterilizaciones'))->render(),
            $esterilizaciones
        );
    }

    private function queryEsterilizaciones(Request $request)
    {
        $query = Esterilizacion::with(['estudiante', 'clinica', 'periodo', 'autorizaciones']);

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', (int) $request->input('estudiante_id'));
        }
        if ($request->filled('habilitada')) {
            $query->where('habilitada', $this->toBool($request->input('habilitada'), true));
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // 10. RADIOLOGÍAS (solo lectura)
    // ============================================================

    public function radiologias(Request $request): View
    {
        $radiologias = $this->queryRadiologias($request)->paginate(20);
        $clinicas    = Clinica::orderBy('especialidad')->get();                   // ✅ FIX
        $estados     = ['pendiente', 'en_proceso', 'completado', 'cancelado'];    // ✅ FIX

        return view('director.radiologias.index', compact('radiologias', 'clinicas', 'estados'));
    }

    public function tablaRadiologias(Request $request): JsonResponse
    {
        $radiologias = $this->queryRadiologias($request)->paginate(20);

        return $this->responderTabla(
            view('director.radiologias.partials.filas', compact('radiologias'))->render(),
            $radiologias
        );
    }

    private function queryRadiologias(Request $request)
    {
        $query = Radiologia::with(['paciente', 'usuario', 'remision', 'caja', 'clinica']);

        if ($request->filled('paciente_id')) {
            $query->where('paciente_id', (int) $request->input('paciente_id'));
        }
        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', (int) $request->input('clinica_id'));      // ✅ FIX
        }
        if ($request->filled('estado')) {
            $estado = $request->input('estado');
            // Validar valores permitidos (espejo de la validación del RadiologiaController)
            if (in_array($estado, ['pendiente', 'en_proceso', 'completado', 'cancelado'], true)) {
                $query->where('estado', $estado);
            }
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('fecha_inicio'))->startOfDay(),
                Carbon::parse($request->input('fecha_fin'))->endOfDay(),
            ]);
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // 11. BITÁCORAS (solo lectura)
    // ============================================================

    public function bitacoras(Request $request): View
    {
        $bitacoras = $this->queryBitacoras($request)->paginate(30);
        $usuarios  = Usuario::select('id', 'nombre_completo')->get();
        $tipos     = Bitacora::distinct()->pluck('tipo');

        return view('director.bitacoras.index', compact('bitacoras', 'usuarios', 'tipos'));
    }

    public function tablaBitacoras(Request $request): JsonResponse
    {
        $bitacoras = $this->queryBitacoras($request)->paginate(30);

        return $this->responderTabla(
            view('director.bitacoras.partials.filas', compact('bitacoras'))->render(),
            $bitacoras
        );
    }

    private function queryBitacoras(Request $request)
    {
        $query = Bitacora::with('usuario');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', (int) $request->input('usuario_id'));
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [
                Carbon::parse($request->input('fecha_inicio'))->startOfDay(),
                Carbon::parse($request->input('fecha_fin'))->endOfDay(),
            ]);
        }

        return $query->orderByDesc('fecha');
    }

    // ============================================================
    // 12. REPORTES (los 12 del PDF, todos solo lectura)
    // ============================================================

    public function reportes(): View
    {
        return view('director.reportes.index');
    }

    /**
     * Reporte 1: Pacientes atendidos por periodo.
     */
    public function reportePacientesPorPeriodo(Request $request): JsonResponse
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('periodos as pe', 'pe.id', '=', 'hc.periodo_id')
            ->select(
                'pe.id as periodo_id',
                'pe.nombre as periodo',
                'pe.gestion',
                'pe.semestre',
                DB::raw('COUNT(DISTINCT hc.paciente_id) as total_pacientes'),
                DB::raw('COUNT(hc.id) as total_historias')
            )
            ->groupBy('pe.id', 'pe.nombre', 'pe.gestion', 'pe.semestre');

        if ($request->filled('periodo_id')) {
            $query->where('pe.id', (int) $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('pe.gestion')->orderByDesc('pe.semestre')->get());
    }

    /**
     * Reporte 2: Tratamientos por especialidad.
     */
    public function reporteTratamientosPorEspecialidad(Request $request): JsonResponse
    {
        $query = DB::table('tratamientos_realizados as tr')
            ->join('clinicas as c', 'c.id', '=', 'tr.clinica_id')
            ->select(
                'c.id as clinica_id',
                'c.especialidad',
                DB::raw('COUNT(tr.id) as total_tratamientos'),
                DB::raw('COALESCE(SUM(tr.costo_cobrado), 0) as monto_total')
            )
            ->groupBy('c.id', 'c.especialidad');

        if ($request->filled('clinica_id')) {
            $query->where('c.id', (int) $request->input('clinica_id'));
        }

        return response()->json($query->orderByDesc('total_tratamientos')->get());
    }

    /**
     * Reporte 3: Atenciones por docente.
     */
    public function reporteAtencionesPorDocente(Request $request): JsonResponse
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('usuarios as u', 'u.id', '=', 'hc.docente_id')
            ->select(
                'u.id as docente_id',
                'u.nombre_completo as docente',
                DB::raw('COUNT(DISTINCT hc.paciente_id) as total_pacientes'),
                DB::raw('COUNT(hc.id) as total_historias')
            )
            ->groupBy('u.id', 'u.nombre_completo');

        if ($request->filled('periodo_id')) {
            $query->where('hc.periodo_id', (int) $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('total_pacientes')->get());
    }

    /**
     * Reporte 4: Atenciones por estudiante.
     */
    public function reporteAtencionesPorEstudiante(Request $request): JsonResponse
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('usuarios as u', 'u.id', '=', 'hc.estudiante_id')
            ->select(
                'u.id as estudiante_id',
                'u.nombre_completo as estudiante',
                DB::raw('COUNT(DISTINCT hc.paciente_id) as total_pacientes'),
                DB::raw('COUNT(hc.id) as total_historias')
            )
            ->groupBy('u.id', 'u.nombre_completo');

        if ($request->filled('periodo_id')) {
            $query->where('hc.periodo_id', (int) $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('total_pacientes')->get());
    }

    /**
     * Reporte 5: Afluencia por clínica / especialidad.
     */
    public function reporteAfluenciaClinicas(Request $request): JsonResponse
    {
        $query = DB::table('remisiones as r')
            ->join('clinicas as c', 'c.id', '=', 'r.clinica_id')
            ->join('periodos as pe', 'pe.id', '=', 'r.periodo_id')
            ->where('r.habilitado', true)
            ->select(
                'pe.id as periodo_id',
                'pe.nombre as periodo',
                'pe.gestion',
                'c.id as clinica_id',
                'c.especialidad',
                DB::raw('COUNT(DISTINCT r.paciente_id) as total_pacientes')
            )
            ->groupBy('pe.id', 'pe.nombre', 'pe.gestion', 'c.id', 'c.especialidad');

        if ($request->filled('periodo_id')) {
            $query->where('pe.id', (int) $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('total_pacientes')->get());
    }

    /**
     * Reporte 6: Pacientes por grupo etario (MySQL: TIMESTAMPDIFF).
     */
    public function reportePacientesGrupoEtario(): JsonResponse
    {
        $caseEdad = "
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 6  THEN '0-5'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 13 THEN '6-12'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18 THEN '13-17'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 30 THEN '18-29'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 60 THEN '30-59'
                ELSE '60+'
            END
        ";

        $data = DB::table('pacientes')
            ->select(DB::raw("{$caseEdad} AS grupo_etario"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($caseEdad))
            ->orderBy('grupo_etario')
            ->get();

        return response()->json($data);
    }

    /**
     * Reporte 7: Pacientes por sexo.
     */
    public function reportePacientesPorSexo(): JsonResponse
    {
        $data = DB::table('pacientes')
            ->select('genero', DB::raw('COUNT(*) as total'))
            ->groupBy('genero')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }

    /**
     * Reporte 8: Motivos de consulta más frecuentes.
     */
    public function reporteMotivosConsulta(Request $request): JsonResponse
    {
        $query = DB::table('admisiones as a')
            ->join('motivos_consulta as mc', 'mc.id', '=', 'a.motivo_consulta_id')
            ->select(
                'mc.id as motivo_id',
                'mc.nombre as motivo',
                DB::raw('COUNT(a.id) as total')
            )
            ->groupBy('mc.id', 'mc.nombre');

        if ($request->filled('periodo_id')) {
            $query->where('a.periodo_id', (int) $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('total')->limit(20)->get());
    }

    /**
     * Reporte 9: Tratamientos y afluencia de cajas.
     * Usa COALESCE para periodos huérfanos (remisiones eliminadas).
     */
    public function reporteCajasPorPeriodo(Request $request): JsonResponse
    {
        $query = DB::table('cajas as ca')
            ->leftJoin('remisiones as r', 'r.id', '=', 'ca.remision_id')
            ->leftJoin('periodos as pe', 'pe.id', '=', 'r.periodo_id')
            ->leftJoin('tratamientos as t', 't.id', '=', 'ca.tratamiento_id')
            ->leftJoin('clinicas as c', 'c.id', '=', 'ca.clinica_id')
            ->select(
                DB::raw("COALESCE(pe.id, 0) as periodo_id"),
                DB::raw("COALESCE(pe.nombre, 'Sin periodo') as periodo"),   // ✅ FIX
                'c.especialidad',
                't.nombre as tratamiento',
                DB::raw('COUNT(ca.id) as total_recibos'),
                DB::raw('COALESCE(SUM(ca.costo_total), 0) as total_cobrado'),
                DB::raw('COALESCE(SUM(ca.cancelado), 0) as total_cancelado')
            )
            ->groupBy(
                DB::raw('COALESCE(pe.id, 0)'),
                DB::raw("COALESCE(pe.nombre, 'Sin periodo')"),
                'c.especialidad',
                't.nombre'
            );

        if ($request->filled('periodo_id')) {
            $query->where('pe.id', (int) $request->input('periodo_id'));
        }
        if ($request->filled('clinica_id')) {
            $query->where('c.id', (int) $request->input('clinica_id'));
        }

        return response()->json($query->orderByDesc('total_cancelado')->get());
    }

    /**
     * Reporte 10: Insumos utilizados por clínica.
     * Columna correcta: insumos_tratamiento_usados.cantidad_usada
     */
    public function reporteInsumosUtilizados(Request $request): JsonResponse
    {
        $query = DB::table('insumos_tratamiento_usados as itu')
            ->join('insumos as i', 'i.id', '=', 'itu.insumo_id')
            ->join('clinicas as c', 'c.id', '=', 'i.clinica_id')
            ->select(
                'c.id as clinica_id',
                'c.especialidad',
                'i.id as insumo_id',
                'i.nombre as insumo',
                DB::raw('COALESCE(SUM(itu.cantidad_usada), 0) as total_usado')   // ✅ FIX
            )
            ->groupBy('c.id', 'c.especialidad', 'i.id', 'i.nombre');

        if ($request->filled('clinica_id')) {
            $query->where('c.id', (int) $request->input('clinica_id'));
        }

        return response()->json($query->orderByDesc('total_usado')->get());
    }

    /**
     * Reporte 11: Afluencia y servicios de radiología.
     * Usa radiologias.clinica_id (agregado en la migración).
     */
    public function reporteRadiologiaAfluencia(Request $request): JsonResponse
    {
        $query = DB::table('radiologias as ra')
            ->join('clinicas as c', 'c.id', '=', 'ra.clinica_id')     // ✅ FIX: join directo
            ->select(
                'c.id as clinica_id',
                'c.especialidad',
                DB::raw('COUNT(ra.id) as total_radiografias'),
                DB::raw('COUNT(DISTINCT ra.paciente_id) as total_pacientes')
            )
            ->groupBy('c.id', 'c.especialidad');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('ra.created_at', [
                Carbon::parse($request->input('fecha_inicio'))->startOfDay(),
                Carbon::parse($request->input('fecha_fin'))->endOfDay(),
            ]);
        }

        return response()->json($query->orderByDesc('total_radiografias')->get());
    }

    /**
     * Reporte 12: Afluencia del servicio de esterilización.
     * Columna correcta: esterilizaciones.cantidad_sobres
     */
    public function reporteEsterilizacionAfluencia(Request $request): JsonResponse
    {
        $query = DB::table('esterilizaciones as e')
            ->join('clinicas as c', 'c.id', '=', 'e.clinica_id')
            ->select(
                'c.id as clinica_id',
                'c.especialidad',
                DB::raw('COUNT(e.id) as total_esterilizaciones'),
                DB::raw('COALESCE(SUM(e.cantidad_sobres), 0) as total_sobres')   // ✅ FIX
            )
            ->groupBy('c.id', 'c.especialidad');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('e.created_at', [
                Carbon::parse($request->input('fecha_inicio'))->startOfDay(),
                Carbon::parse($request->input('fecha_fin'))->endOfDay(),
            ]);
        }

        return response()->json($query->orderByDesc('total_esterilizaciones')->get());
    }

    /**
     * Exportación a PDF (placeholder: implementar con DomPDF o Snappy).
     */
    public function exportarPdf(string $reporte, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => "Exportación PDF del reporte '{$reporte}' aún no implementada.",
        ], 501);
    }

    /**
     * Exportación a Excel (placeholder: implementar con maatwebsite/excel).
     */
    public function exportarExcel(string $reporte, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => "Exportación Excel del reporte '{$reporte}' aún no implementada.",
        ], 501);
    }

    // ============================================================
    // 13. INSTITUTO DE INVESTIGACIÓN (datos anonimizados)
    // ============================================================

    public function investigacion(Request $request): View
    {
        $clinicas = Clinica::orderBy('especialidad')->get();       // ✅ FIX
        $periodos = Periodo::orderByDesc('gestion')->get();        // ✅ FIX

        return view('director.investigacion.index', compact('clinicas', 'periodos'));
    }

    public function tablaInvestigacion(Request $request): JsonResponse
    {
        $data = $this->datosAnonimizadosQuery($request)->paginate(20);

        return response()->json([
            'filas' => view('director.investigacion.partials.filas', compact('data'))->render(),
            'paginacion' => $data->hasPages()
                ? '<span class="info">Mostrando ' . $data->firstItem() . '–' . $data->lastItem()
                . ' de ' . $data->total() . ' registros</span>'
                . '<div>' . $data->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $data->total(),
        ]);
    }

    public function datosAnonimizados(Request $request): JsonResponse
    {
        return response()->json($this->datosAnonimizadosQuery($request)->limit(500)->get());
    }

    /**
     * Consulta de datos anonimizados: sin CI, sin nombre, sin apellidos.
     */
    private function datosAnonimizadosQuery(Request $request)
    {
        $caseEdad = "
            CASE
                WHEN TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) < 6  THEN '0-5'
                WHEN TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) < 13 THEN '6-12'
                WHEN TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) < 18 THEN '13-17'
                WHEN TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) < 30 THEN '18-29'
                WHEN TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) < 60 THEN '30-59'
                ELSE '60+'
            END
        ";

        $query = DB::table('historias_clinicas as hc')
            ->join('pacientes as p', 'p.id', '=', 'hc.paciente_id')
            ->join('clinicas as c', 'c.id', '=', 'hc.clinica_id')
            ->join('periodos as pe', 'pe.id', '=', 'hc.periodo_id')
            ->leftJoin('admisiones as a', 'a.id', '=', 'hc.admision_id')
            ->leftJoin('motivos_consulta as mc', 'mc.id', '=', 'a.motivo_consulta_id')
            ->select(
                'p.genero',
                DB::raw("{$caseEdad} AS grupo_etario"),
                'c.especialidad',
                'pe.nombre as periodo',
                'pe.gestion',
                'pe.semestre',
                'mc.nombre as motivo_consulta',
                'hc.estado',
                'hc.autorizado'
            );

        if ($request->filled('clinica_id')) {
            $query->where('c.id', (int) $request->input('clinica_id'));
        }
        if ($request->filled('periodo_id')) {
            $query->where('pe.id', (int) $request->input('periodo_id'));
        }
        if ($request->filled('genero')) {
            $query->where('p.genero', $request->input('genero'));
        }

        return $query->orderByDesc('hc.created_at');
    }
}
