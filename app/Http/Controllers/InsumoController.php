<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Http\Resources\InsumoResource;
use App\Http\Resources\SolicitudResource;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Services\AccesoClinico;
use App\Services\FlujoTratamiento;
use App\Services\ReporteModulo;
use App\Services\SolicitudInsumoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InsumoController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass = Insumo::class;

    protected string $viewPrefix = 'insumos';

    protected string $resourceName = 'Insumo';

    protected string $pluralName = 'Insumos';

    protected string $permissionPrefix = 'insumos';

    protected int $perPage = 15;

    private ?int $tratamientoSeleccionadoId = null;

    public function reposiciones(Request $request): View
    {
        return view('insumos.reposiciones', [
            'registros' => $this->consultaReposiciones($request)->paginate(15)->withQueryString(),
            'insumos' => Insumo::with('clinica')
                ->whereColumn('cantidad_numerica', '<=', 'stock_minimo')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function tablaReposiciones(Request $request): JsonResponse
    {
        $registros = $this->consultaReposiciones($request)->paginate(15)->withQueryString();

        return response()->json(['filas' => view('insumos.partials.reposiciones', compact('registros'))->render(),
            'paginacion' => $registros->links()->toHtml(), 'total' => $registros->total()]);
    }

    private function consultaReposiciones(Request $request): Builder
    {
        abort_unless($request->user()->hasAnyRole(['insumos', 'jefatura-clinicas', 'director', 'admin']), 403);
        $data = $request->validate(['estado' => ['nullable', Rule::in(['pendiente', 'autorizado', 'aprobado', 'parcial', 'recibido', 'rechazado'])]]);

        return InsumoSolicitado::where('tipo_solicitud', 'reposicion')
            ->with(['estudiante', 'aprobadorJefatura', 'encargado', 'receptor', 'detalles.insumo.clinica', 'entregas.usuario'])
            ->when($data['estado'] ?? null, fn (Builder $query, string $estado) => $query->where('estado', $estado))
            ->orderByDesc('id');
    }

    public function crearReposicion(Request $request): JsonResponse
    {
        $solicitud = $this->solicitudes->guardarReposicion($request->user(), $request->all());

        return response()->json(['success' => true, 'id' => $solicitud->id, 'message' => 'Reposición enviada a Jefatura y Dirección.'], 201);
    }

    public function revisarReposicion(Request $request, int $solicitud): JsonResponse
    {
        $data = $request->validate(['accion' => ['required', Rule::in(['jefatura', 'director', 'rechazar', 'recibir'])]]);
        $registro = InsumoSolicitado::where('tipo_solicitud', 'reposicion')->findOrFail($solicitud);
        $this->solicitudes->revisarReposicion($request->user(), $registro, $data['accion'], $request->all());

        return response()->json(['success' => true, 'message' => 'Reposición actualizada.']);
    }

    /* ============================================================
       QUERIES — Catálogo de insumos
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Insumo::query()
            ->with(['insumoTratamiento:id,nombre', 'clinica:id,especialidad,modulo', 'tratamientos:id,nombre'])
            ->orderBy('nombre');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('nombre', 'like', "%{$buscar}%")
                    ->orWhereHas('insumoTratamiento', fn ($t) => $t->where('nombre', 'like', "%{$buscar}%"));
                $x->orWhereHas('tratamientos', fn (Builder $tratamientos) => $tratamientos->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($request->filled('stock_bajo')) {
            $q->whereColumn('cantidad_numerica', '<=', 'stock_minimo');
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'stats' => [
                'total' => Insumo::count(),
                'stock_bajo' => Insumo::whereColumn('cantidad_numerica', '<=', 'stock_minimo')->count(),
                'agotados' => Insumo::where('cantidad_numerica', '<=', 0)->count(),
                'valor' => (float) Insumo::selectRaw('COALESCE(SUM(cantidad_numerica * costo_unitario), 0) as total')->value('total'),
            ],
        ];
    }

    protected function extraDatosData(Model $model): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'tiposInsumo' => InsumoTratamiento::where('clinica_id', $model->clinica_id ?? 0)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'tratamientosAsignados' => $model->tratamientos()->orderBy('nombre')->get(['tratamientos.id', 'nombre']),
        ];
    }

    /* ============================================================
       VALIDACIÓN — Catálogo
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'cantidad_numerica' => ['required', 'numeric', 'min:0'],
            'cantidad' => ['nullable', 'string', 'max:100'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'insumo_tratamiento_id' => ['nullable', 'integer', Rule::exists('insumos_tratamiento', 'id')->where('clinica_id', request('clinica_id'))],
            'tratamiento_id' => [$id === null ? 'required' : 'nullable', 'integer', Rule::exists('tratamientos', 'id')->where('clinica_id', request('clinica_id'))],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre del insumo es obligatorio.',
            'cantidad_numerica.required' => 'La cantidad es obligatoria.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'costo_unitario.required' => 'El costo unitario es obligatorio.',
            'insumo_tratamiento_id.exists' => 'El tipo de insumo debe pertenecer a la clínica seleccionada.',
            'tratamiento_id.required' => 'Debe seleccionar el tratamiento para el insumo.',
            'tratamiento_id.exists' => 'El tratamiento debe pertenecer a la clínica seleccionada.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
        ];
    }

    /* ============================================================
       HOOKS — Catálogo
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        if (empty($data['insumo_tratamiento_id'])) {
            $data['insumo_tratamiento_id'] = InsumoTratamiento::query()->firstOrCreate(
                ['clinica_id' => $data['clinica_id'], 'nombre' => 'General'],
                ['descripcion' => 'Materiales generales de la clínica']
            )->id;
        }
        if (array_key_exists('cantidad', $data) && $data['cantidad'] === null) {
            $data['cantidad'] = '';
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'subadmin', 'insumos']), 403);

        $this->tratamientoSeleccionadoId = (int) $data['tratamiento_id'];
        unset($data['tratamiento_id']);

        return $this->normalizarStrings($data);
    }

    protected function afterStore(Model $model): void
    {
        $model->tratamientos()->attach($this->tratamientoSeleccionadoId, ['cantidad' => 1]);
    }

    protected function beforeUpdate(Model $model, array $data): array
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'subadmin', 'insumos']), 403);
        if ((int) $model->clinica_id !== (int) $data['clinica_id'] && $model->tratamientos()->exists()) {
            throw ValidationException::withMessages(['clinica_id' => 'No puede cambiar la clínica de un insumo asignado a tratamientos.']);
        }
        if (! array_key_exists('insumo_tratamiento_id', $data) && (int) $model->clinica_id === (int) $data['clinica_id']) {
            $data['insumo_tratamiento_id'] = $model->insumo_tratamiento_id;
        }

        $this->tratamientoSeleccionadoId = ! empty($data['tratamiento_id']) ? (int) $data['tratamiento_id'] : null;
        unset($data['tratamiento_id']);

        return $this->normalizarStrings($data);
    }

    protected function afterUpdate(Model $model): void
    {
        if ($this->tratamientoSeleccionadoId === null) {
            return;
        }

        $asignados = $model->tratamientos()->get();
        if ($asignados->contains('id', $this->tratamientoSeleccionadoId)) {
            return;
        }

        $asignacion = [$this->tratamientoSeleccionadoId => ['cantidad' => 1]];
        if ($asignados->count() > 1) {
            $model->tratamientos()->syncWithoutDetaching($asignacion);
        } else {
            $model->tratamientos()->sync($asignacion);
        }
    }

    protected function canDestroy($model): array
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'subadmin', 'insumos']), 403);
        if ($model->detalles()->whereHas('solicitud', fn ($q) => $q->whereIn('estado', ['pendiente', 'autorizado']))->count() > 0) {
            return [false, 'No se puede eliminar: tiene solicitudes pendientes.'];
        }

        return [true, ''];
    }

    /* ============================================================
       TIPOS DE INSUMO POR CLÍNICA (AJAX)
       ============================================================ */

    public function tiposPorClinica(Request $request, $clinicaId)
    {
        $this->authorizeCatalogLookup($request);

        return response()->json(
            InsumoTratamiento::where('clinica_id', $clinicaId)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
        );
    }

    public function tratamientosPorClinica(Clinica $clinica): JsonResponse
    {
        $this->authorizeAction('ver');

        return response()->json(Tratamiento::query()->where('clinica_id', $clinica->id)->orderBy('nombre')->get(['id', 'nombre']));
    }

    public function buscarInsumos(Request $request, $clinicaId)
    {
        $this->authorizeCatalogLookup($request);
        $q = trim((string) $request->input('q'));

        $insumos = Insumo::where('clinica_id', $clinicaId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%");
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'cantidad_numerica', 'cantidad', 'costo_unitario'])
            ->map(fn ($i) => [
                'id' => $i->id,
                'texto' => $i->nombre,
                'extra' => 'Stock: '.number_format($i->cantidad_numerica, 2).' '.$i->cantidad,
                'stock' => (float) $i->cantidad_numerica,
                'unidad' => $i->cantidad,
                'costo' => (float) $i->costo_unitario,
            ]);

        return response()->json($insumos);
    }

    /* ============================================================
       AJUSTE RÁPIDO DE STOCK
       ============================================================ */

    public function ajustarStock(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $validated = $request->validate([
            'cantidad' => ['required', 'numeric'],
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);

        $insumo = Insumo::findOrFail($id);
        $nuevo = (float) $insumo->cantidad_numerica + (float) $validated['cantidad'];

        if ($nuevo < 0) {
            return response()->json(['success' => false, 'message' => 'El stock no puede ser negativo.'], 422);
        }

        $insumo->update(['cantidad_numerica' => $nuevo]);

        return response()->json([
            'success' => true,
            'message' => "Stock ajustado. Nuevo stock: {$nuevo}",
            'nuevo_stock' => $nuevo,
        ]);
    }

    public function __construct(private SolicitudInsumoService $solicitudes) {}

    private function authorizeSolicitudAction(string $action): void
    {
        $user = request()->user();
        abort_unless($user?->hasRole('admin') || $user?->canAny([
            'solicitudes-insumos.'.$action, 'solicitudes-insumos.gestionar', 'insumos.gestionar',
        ]), 403);
    }

    private function authorizeCatalogLookup(Request $request): void
    {
        if ($request->routeIs('solicitudes-insumos.*')) {
            $this->authorizeSolicitudAction('ver');
        } else {
            $this->authorizeAction('ver');
        }
    }

    public function solicitudes(Request $request): View
    {
        $this->authorizeSolicitudAction('ver');
        $registros = $this->consultaSolicitudes($request)->paginate($this->perPage)->withQueryString();

        return view('solicitudes-insumos.index', ['registros' => $registros, 'items' => $registros] + $this->datosListadoSolicitudes($request));
    }

    public function tablaSolicitudes(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');
        $registros = $this->filtrarSolicitudes($request)->paginate($this->perPage)->withQueryString();

        return response()->json([
            'filas' => view('solicitudes-insumos.partials.filas', ['registros' => $registros, 'items' => $registros])->render(),
            'paginacion' => $registros->links()->toHtml(),
            'total' => $registros->total(),
        ]);
    }

    public function datosSolicitud(int $id): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');
        $registro = $this->solicitudVisible($id);

        return response()->json(['success' => true, 'es_edicion' => true, 'registro' => $registro, 'id' => $registro->id] + $this->detalleSolicitud($registro));
    }

    public function verSolicitud(int $id): View|JsonResponse
    {
        $this->authorizeSolicitudAction('ver');
        $registro = $this->solicitudVisible($id);

        return $this->esAjax()
            ? response()->json(['success' => true, 'registro' => $registro])
            : view('solicitudes-insumos.show', ['registro' => $registro, 'model' => $registro]);
    }

    public function eliminarSolicitud(Request $request, int $id): JsonResponse
    {
        $this->authorizeSolicitudAction('eliminar');

        return DB::transaction(function () use ($request, $id): JsonResponse {
            $registro = $this->consultaSolicitudes($request)->lockForUpdate()->findOrFail($id);
            [$permitido, $motivo] = $this->puedeEliminarSolicitud($registro);
            if (! $permitido) {
                return response()->json(['success' => false, 'message' => $motivo], 422);
            }
            $registro->delete();

            return response()->json(['success' => true, 'message' => 'Solicitud eliminada correctamente.']);
        });
    }

    public function reporteSolicitudes(Request $request, ReporteModulo $reporte): Response
    {
        $this->authorizeSolicitudAction('ver');
        $reporte->validarFiltros($request);

        return $reporte->generar($this->filtrarSolicitudes($request), 'solicitudes-insumos', $request);
    }

    private function consultaSolicitudes(Request $request): Builder
    {
        $user = $request->user();

        $query = InsumoSolicitado::query()
            ->with([
                'estudiante:id,nombre_completo',
                'docente:id,nombre_completo',
                'clinica:id,especialidad',
                'detalles.insumo:id,nombre,cantidad,cantidad_numerica', 'detalles.consumos', 'sesionAtencion', 'tratamientoRealizado.tratamiento', 'tratamientoRealizado.historiaClinica',
                'historiaClinica.paciente', 'tratamiento', 'encargado',
            ])
            ->withCount(['detalles', 'entregas'])
            ->orderByDesc('id');

        return AccesoClinico::solicitudes($query, $user);
    }

    private function filtrarSolicitudes(Request $request): Builder
    {
        $q = $this->consultaSolicitudes($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->whereHas('estudiante', fn ($e) => $e->where('nombre_completo', 'like', "%{$buscar}%"))
                    ->orWhere('pieza', 'like', "%{$buscar}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        return $q;
    }

    protected function datosListadoSolicitudes(Request $request): array
    {
        $totales = AccesoClinico::solicitudes(InsumoSolicitado::query(), $request->user())
            ->select('estado')->selectRaw('COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad']),
            'pacientesDesignados' => $request->user()->hasAnyRole(['estudiante', 'interno'])
                ? $this->historiasDesignadasActivas($request)->where('estudiante_id', $request->user()->id)
                    ->with('paciente')->orderByDesc('id')->get()->unique('paciente_id')
                    ->sortBy(fn (HistoriaClinica $historia): string => $historia->paciente->nombre_completo)->values()
                : collect(),
            'stats' => [
                'pendientes' => (int) $totales->get('pendiente', 0),
                'autorizadas' => (int) $totales->get('autorizado', 0) + (int) $totales->get('aprobado', 0) + (int) $totales->get('parcial', 0),
                'entregadas' => (int) $totales->get('entregado', 0),
                'rechazadas' => (int) $totales->get('rechazado', 0),
            ],
        ];
    }

    protected function detalleSolicitud(InsumoSolicitado $model): array
    {
        return [
            'detalles' => $model->detalles->map(fn ($d) => [
                'id' => $d->id,
                'insumo_id' => $d->insumo_id,
                'insumo_nombre' => $d->insumo?->nombre,
                'cantidad_solicitada' => (float) $d->cantidad_solicitada,
                'cantidad_entregada' => (float) $d->cantidad_entregada,
                'stock_actual' => (float) ($d->insumo?->cantidad_numerica ?? 0),
                'unidad' => $d->insumo?->cantidad,
            ]),
        ];
    }

    public function crearSolicitud(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('crear');
        $registro = $this->solicitudes->guardar($request->user(), $request->all());

        return response()->json(['success' => true, 'id' => $registro->id, 'message' => 'Solicitud registrada.'], 201);
    }

    public function actualizarSolicitud(Request $request, int $id): JsonResponse
    {
        $this->authorizeSolicitudAction('editar');
        $registro = $this->solicitudes->guardar($request->user(), $request->all(), $this->solicitudVisible($id));

        return response()->json(['success' => true, 'id' => $registro->id, 'message' => 'Solicitud actualizada.']);
    }

    public function autorizarSolicitud(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->solicitudes->autorizar($request->user(), $this->solicitudVisible($id));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Solicitud autorizada.'])
            : back()->with('success', 'Solicitud autorizada. Pendiente de aprobación en Insumos.');
    }

    public function aprobarSolicitud(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->solicitudes->aprobar($request->user(), $this->solicitudVisible($id));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Solicitud aprobada por Insumos.'])
            : back()->with('success', 'Solicitud aprobada por Insumos.');
    }

    public function catalogoDesignado(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');
        $data = $request->validate([
            'historia_clinica_id' => ['required', 'integer'],
            'clinica_id' => ['nullable', 'integer'],
        ]);
        $origen = AccesoClinico::historias(HistoriaClinica::query(), $request->user())
            ->with(['paciente', 'docente', 'clinica'])->findOrFail($data['historia_clinica_id']);
        $historias = $this->historiasDesignadasActivas($request)
            ->where('paciente_id', $origen->paciente_id)->where('estudiante_id', $origen->estudiante_id)
            ->with(['paciente', 'docente', 'clinica'])->orderByDesc('id')->get();
        $historia = isset($data['clinica_id'])
            ? ($historias->first(fn (HistoriaClinica $asignacion): bool => $asignacion->id === $origen->id && (int) $asignacion->clinica_id === (int) $data['clinica_id'])
                ?? $historias->firstWhere('clinica_id', $data['clinica_id']))
            : ($historias->firstWhere('id', $origen->id) ?? $historias->first());

        if (! $historia) {
            throw ValidationException::withMessages([
                'clinica_id' => 'Seleccione una clínica con atención activa asignada a este estudiante para el paciente.',
            ]);
        }

        $catalogo = Tratamiento::query()->where('clinica_id', $historia->clinica_id)->with('insumos')->orderBy('nombre')->get();

        return response()->json([
            'clinicas' => $historias->unique('clinica_id')->map(fn (HistoriaClinica $asignacion): array => [
                'id' => $asignacion->clinica_id, 'nombre' => $asignacion->clinica->especialidad,
            ])->sortBy('nombre')->values(),
            'historia' => ['id' => $historia->id, 'ficha' => $historia->admision_id, 'clinica_id' => $historia->clinica_id,
                'clinica' => $historia->clinica->especialidad, 'paciente' => $historia->paciente->nombre_completo,
                'docente' => $historia->docente?->nombre_completo],
            'tratamientos' => $catalogo->map(fn (Tratamiento $tratamiento): array => [
                'id' => $tratamiento->id, 'nombre' => $tratamiento->nombre,
                'insumos' => $tratamiento->insumos->where('clinica_id', $historia->clinica_id)->map(fn ($insumo): array => [
                    'id' => $insumo->id, 'nombre' => $insumo->nombre, 'unidad' => $insumo->cantidad,
                    'stock' => $insumo->cantidad_numerica, 'cantidad' => $insumo->pivot->cantidad,
                ])->values(),
            ]),
        ]);
    }

    private function historiasDesignadasActivas(Request $request): Builder
    {
        return AccesoClinico::historias(HistoriaClinica::query(), $request->user())
            ->whereNull('fecha_fin')
            ->whereNotIn('estado', ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado']);
    }

    public function rechazarSolicitud(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['motivo' => ['nullable', 'string', 'max:2000']]);
        $this->solicitudes->rechazar($request->user(), $this->solicitudVisible($id), $data['motivo'] ?? null);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Solicitud rechazada.'])
            : back()->with('success', 'Solicitud rechazada.');
    }

    public function entregarSolicitud(Request $request, int $id): JsonResponse
    {
        $this->solicitudes->entregar($request->user(), $this->solicitudVisible($id), $request->all());

        return response()->json(['success' => true, 'message' => 'Entrega registrada.']);
    }

    public function consumirSolicitud(Request $request, int $id): JsonResponse
    {
        $this->solicitudes->consumir($request->user(), $this->solicitudVisible($id), $request->all());

        return response()->json(['success' => true, 'message' => 'Uso registrado.']);
    }

    private function solicitudVisible(int|string $id): InsumoSolicitado
    {
        return $this->consultaSolicitudes(request())->findOrFail($id);
    }

    protected function puedeEliminarSolicitud(InsumoSolicitado $model): array
    {
        $this->solicitudes->assertOwner(request()->user(), $model);

        return in_array($model->estado, ['pendiente', 'rechazado'], true) ? [true, ''] : [false, 'La solicitud ya tiene autorización o entregas y debe conservarse.'];
    }

    public function buscarSesiones(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');
        $tratamientos = app(FlujoTratamiento::class)->tratamientosVisibles($request->user())->select('id');

        return response()->json(SesionAtencion::whereIn('tratamiento_realizado_id', $tratamientos)->where('autorizado', true)->where('tratamiento_realizado_id', $request->integer('tratamiento_realizado_id'))->orderByDesc('fecha')->get()->map(fn ($sesion) => ['id' => $sesion->id, 'texto' => '#'.$sesion->id.' ? '.$sesion->fecha->format('d/m/Y').' ? '.mb_substr($sesion->descripcion, 0, 80)]));
    }

    public function buscarTratamientos(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');

        $request->validate(['historia_clinica_id' => ['nullable', 'integer']]);
        $origen = $request->filled('historia_clinica_id')
            ? AccesoClinico::historias(HistoriaClinica::query(), $request->user())->findOrFail($request->integer('historia_clinica_id'))
            : null;

        $q = trim((string) $request->input('q'));
        $clinica = $request->input('clinica_id');

        return response()->json(
            app(FlujoTratamiento::class)->tratamientosVisibles($request->user())
                ->whereIn('estado', ['autorizado', 'en_proceso'])
                ->when($origen, fn (Builder $query) => $query->whereHas('historiaClinica', fn (Builder $historia) => $historia
                    ->where('paciente_id', $origen->paciente_id)->where('estudiante_id', $origen->estudiante_id)
                    ->whereNull('fecha_fin')->whereNotIn('estado', ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado'])))
                ->with(['tratamiento:id,nombre', 'historiaClinica:id,paciente_id'])
                ->when($clinica, fn ($query) => $query->where('clinica_id', $clinica))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->whereHas('tratamiento', fn ($t) => $t->where('nombre', 'like', "%{$q}%"))
                            ->orWhere('pieza_dental', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (TratamientoRealizado $t) => [
                    'id' => $t->id,
                    'texto' => trim(($t->tratamiento?->nombre ?? 'Tratamiento').($t->pieza_dental ? " · pieza {$t->pieza_dental}" : '')),
                    'extra' => 'Realizado #'.$t->id.' · '.($t->estado ?? ''),
                    'pieza' => $t->pieza_dental,
                    'clinica_id' => $t->clinica_id,
                ])
        );
    }

    public function catalogoApi(Request $request): JsonResponse
    {
        $this->autorizarApi($request, 'insumos.ver');

        $buscar = trim((string) $request->input('buscar'));

        $paginador = Insumo::query()
            ->with(['clinica:id,especialidad', 'insumoTratamiento:id,nombre'])
            ->when($buscar !== '', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->when($request->filled('clinica_id'), fn ($q) => $q->where('clinica_id', $request->input('clinica_id')))
            ->when($request->boolean('stock_bajo'), fn ($q) => $q->whereColumn('cantidad_numerica', '<=', 'stock_minimo'))
            ->orderBy('nombre')
            ->paginate($this->porPaginaApi($request));

        return response()->json([
            'success' => true,
            'data' => InsumoResource::collection($paginador->items()),
            'paginacion' => $this->paginacionApi($paginador),
        ]);
    }

    public function solicitudesApi(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('ver');

        $usuario = $request->user();

        $paginador = AccesoClinico::solicitudes(InsumoSolicitado::query(), $usuario)
            ->with(['clinica:id,especialidad', 'estudiante:id,nombre_completo', 'docente:id,nombre_completo', 'detalles.insumo:id,nombre,cantidad'])
            ->when(
                $usuario?->hasRole('estudiante') || $usuario?->hasRole('interno'),
                fn ($q) => $q->where('usuario_id', $usuario->id)
            )
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->orderByDesc('id')
            ->paginate($this->porPaginaApi($request));

        return response()->json([
            'success' => true,
            'data' => SolicitudResource::collection($paginador->items()),
            'paginacion' => $this->paginacionApi($paginador),
        ]);
    }

    public function crearSolicitudApi(Request $request): JsonResponse
    {
        $this->authorizeSolicitudAction('crear');
        try {
            $solicitud = $this->solicitudes->guardar($request->user(), $request->all());
        } catch (ValidationException $exception) {
            return $this->errorValidacion($exception);
        }

        return response()->json(['success' => true, 'message' => 'Solicitud creada correctamente.', 'data' => new SolicitudResource($solicitud->load(['clinica', 'detalles.insumo']))], 201);
    }
}
