<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Services\AccesoClinico;
use App\Services\FlujoTratamiento;
use App\Services\ReporteModulo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TratamientoController extends Controller
{
    use HasCrudAjax;

    private const ESTADOS_REALIZADOS = [
        'pendiente' => 'Pendiente', 'propuesta' => 'Propuesta', 'autorizado' => 'Autorizado',
        'en_proceso' => 'En proceso', 'finalizado' => 'Finalizado', 'culminado' => 'Culminado',
        'realizado' => 'Realizado', 'completado' => 'Completado', 'rechazado' => 'Rechazado', 'cancelado' => 'Cancelado',
    ];

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Tratamiento::class;

    protected string $viewPrefix = 'tratamientos';

    protected string $resourceName = 'Tratamiento';

    protected string $pluralName = 'Tratamientos';

    protected string $permissionPrefix = 'tratamientos';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request): Builder
    {
        return Tratamiento::query()
            ->with('clinica:id,especialidad,modulo')
            ->withCount(['tratamientosRealizados', 'insumos'])
            ->when($request->filled('clinica_id'), fn (Builder $query) => $query->where('clinica_id', $request->integer('clinica_id')))
            ->orderBy('nombre')
            ->orderBy('id');
    }

    protected function buildTablaQuery(Request $request): Builder
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
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
        ];
    }

    protected function extraDatosData(Model $model): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tratamientos', 'nombre')
                    ->where('clinica_id', request('clinica_id'))
                    ->ignore($id),
            ],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre del tratamiento es obligatorio.',
            'nombre.unique' => 'Ya existe ese tratamiento en esta clínica.',
            'costo.required' => 'El costo es obligatorio.',
            'costo.numeric' => 'El costo debe ser un número.',
            'costo.min' => 'El costo no puede ser negativo.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
            'clinica_id.exists' => 'La clínica no existe.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        if (array_key_exists('descripcion', $data) && $data['descripcion'] === null) {
            $data['descripcion'] = '';
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data['precio_asignado'] = isset($data['costo']);
        $data['costo'] = $data['costo'] ?? 0;

        return $this->normalizarStrings($data);
    }

    protected function beforeUpdate(Model $model, array $data): array
    {
        if ((int) $model->clinica_id !== (int) $data['clinica_id'] && ($model->insumos()->exists() || $model->tratamientosRealizados()->exists())) {
            throw ValidationException::withMessages(['clinica_id' => 'No puede cambiar la clínica de un tratamiento con insumos o atenciones registradas.']);
        }
        if (isset($data['costo'])) {
            $data['precio_asignado'] = true;
        } else {
            unset($data['costo']);
        }

        return $this->normalizarStrings($data);
    }

    protected function canDestroy(Model $model): array
    {
        if ($model->tratamientosRealizados()->count() > 0) {
            $count = $model->tratamientosRealizados()->count();

            return [false, "No se puede eliminar: {$count} tratamiento(s) realizado(s) usan este."];
        }

        return [true, ''];
    }

    public function show(Request $request, Tratamiento $tratamiento): View|JsonResponse
    {
        $this->authorizeAction('ver');
        $tratamiento->load(['clinica', 'insumos' => fn ($query) => $query->orderBy('nombre')])
            ->loadCount('tratamientosRealizados');

        $insumosDisponibles = Gate::allows('tratamientos.insumos')
            ? Insumo::query()->where('clinica_id', $tratamiento->clinica_id)->orderBy('nombre')->get()
            : collect();

        if ($this->esAjax()) {
            $modo = $request->input('modal');
            if ($modo !== null) {
                $request->validate(['modal' => ['required', Rule::in(['ver', 'precio', 'insumos'])]]);

                if ($modo === 'precio') {
                    Gate::authorize('tratamientos.precios');
                }
                if ($modo === 'insumos') {
                    Gate::authorize('tratamientos.insumos');
                }

                return response()->json([
                    'success' => true,
                    'titulo' => match ($modo) {
                        'precio' => 'Precio del tratamiento',
                        'insumos' => 'Insumos del tratamiento',
                        default => 'Detalle del tratamiento',
                    },
                    'html' => view('tratamientos.partials.modal-contenido', compact('tratamiento', 'insumosDisponibles', 'modo'))->render(),
                ]);
            }

            return response()->json(['success' => true, 'registro' => $tratamiento]);
        }

        return view('tratamientos.show', [
            'tratamiento' => $tratamiento,
            'insumosDisponibles' => $insumosDisponibles,
        ]);
    }

    public function actualizarPrecio(Request $request, Tratamiento $tratamiento): RedirectResponse|JsonResponse
    {
        Gate::authorize('tratamientos.precios');
        $data = $request->validate([
            'costo' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ]);
        $tratamiento->update([...$data, 'precio_asignado' => true]);

        return $this->respuestaConfiguracion($request, $tratamiento, 'Precio del tratamiento actualizado.');
    }

    public function actualizarInsumos(Request $request, Tratamiento $tratamiento): RedirectResponse|JsonResponse
    {
        Gate::authorize('tratamientos.insumos');
        if (! $request->expectsJson()) {
            $request->mergeIfMissing(['insumos' => []]);
        }
        DB::transaction(function () use ($request, $tratamiento): void {
            $tratamiento = Tratamiento::query()->lockForUpdate()->findOrFail($tratamiento->id);
            $data = $request->validate([
                'insumos' => ['present', 'array'],
                'insumos.*' => ['array:insumo_id,cantidad'],
                'insumos.*.insumo_id' => ['required', 'integer', 'distinct', Rule::exists('insumos', 'id')->where('clinica_id', $tratamiento->clinica_id)],
                'insumos.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            ], [
                'insumos.*.insumo_id.exists' => 'Todos los insumos deben pertenecer a la misma clínica del tratamiento.',
                'insumos.*.insumo_id.distinct' => 'No puede repetir un insumo en el tratamiento.',
                'insumos.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
            ]);
            $asignaciones = [];
            foreach ($data['insumos'] as $insumo) {
                $asignaciones[$insumo['insumo_id']] = ['cantidad' => $insumo['cantidad']];
            }
            $tratamiento->insumos()->sync($asignaciones);
        });

        return $this->respuestaConfiguracion($request, $tratamiento, 'Insumos del tratamiento actualizados.');
    }

    private function respuestaConfiguracion(Request $request, Tratamiento $tratamiento, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('tratamientos.show', $tratamiento)->with('success', $message);
    }

    public function __construct(private FlujoTratamiento $flujo) {}

    public function reporteRealizados(Request $request, ReporteModulo $reporte): Response
    {
        $this->flujo->exigirPermiso($request->user(), ['atenciones.ver', 'atenciones.gestionar']);
        $reporte->validarFiltros($request);

        return $reporte->generar($this->consultaRealizados($request), 'tratamientos-realizados', $request);
    }

    public function realizados(Request $request): View
    {
        $this->flujo->exigirPermiso($request->user(), ['atenciones.ver', 'atenciones.gestionar']);

        return view('tratamientos-realizados.index', [
            'registros' => $this->consultaRealizados($request)->paginate(15)->withQueryString(),
            'historias' => AccesoClinico::historias(HistoriaClinica::query(), $request->user())->where('autorizado', true)->with('paciente')->get(),
            'catalogo' => Tratamiento::orderBy('nombre')->get(),
            'estados' => self::ESTADOS_REALIZADOS,
            'resumenRevision' => $request->user()->hasRole('jefe-catedra')
                ? $this->resumenRevision($request)
                : null,
            'revisionActual' => $request->user()->hasRole('jefe-catedra')
                ? $request->query('revision', 'pendientes')
                : $request->query('revision', 'todos'),
        ]);
    }

    public function tablaRealizados(Request $request): JsonResponse
    {
        $this->flujo->exigirPermiso($request->user(), ['atenciones.ver', 'atenciones.gestionar']);
        $registros = $this->consultaRealizados($request)->paginate(15)->withQueryString();

        return response()->json([
            'filas' => view('tratamientos-realizados.partials.filas', ['registros' => $registros, 'estados' => self::ESTADOS_REALIZADOS])->render(),
            'paginacion' => $registros->links()->toHtml(),
            'total' => $registros->total(),
            'resumenRevision' => $request->user()->hasRole('jefe-catedra') ? $this->resumenRevision($request) : null,
        ]);
    }

    private function consultaRealizados(Request $request): Builder
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', Rule::in(array_keys(self::ESTADOS_REALIZADOS))],
            'revision' => ['nullable', Rule::in(['pendientes', 'propuestas', 'avances', 'culminaciones', 'todos'])],
        ]);

        return $this->flujo->tratamientosVisibles($request->user())
            ->with([
                'tratamiento',
                'clinica',
                'historiaClinica.paciente',
                'historiaClinica.estudiante',
                'historiaClinica.docente',
                'autorizador',
                'sesionesAtencion.docente',
                'sesionesAtencion.estudiante',
                'sesionesAtencion.autorizador',
            ])
            ->when(trim($filtros['buscar'] ?? '') !== '', function (Builder $query) use ($filtros): void {
                $buscar = '%'.trim($filtros['buscar']).'%';
                $query->where(function (Builder $query) use ($buscar): void {
                    $query->where('pieza_dental', 'like', $buscar)
                        ->orWhereHas('tratamiento', fn (Builder $tratamiento) => $tratamiento->where('nombre', 'like', $buscar))
                        ->orWhereHas('historiaClinica.paciente', function (Builder $paciente) use ($buscar): void {
                            $paciente->where('ci', 'like', $buscar)->orWhere('nombre', 'like', $buscar)
                                ->orWhere('apellido_paterno', 'like', $buscar)->orWhere('apellido_materno', 'like', $buscar);
                        });
                });
            })
            ->when($filtros['estado'] ?? null, fn (Builder $query, string $estado) => $query->where('estado', $estado))
            ->when($filtros['revision'] ?? ($request->user()->hasRole('jefe-catedra') ? 'pendientes' : 'todos'), fn (Builder $query, string $revision) => $this->filtrarRevision($query, $revision))
            ->orderByDesc('id');
    }

    /** @return array{propuestas: int, avances: int, culminaciones: int} */
    private function resumenRevision(Request $request): array
    {
        $base = $this->flujo->tratamientosVisibles($request->user());

        return [
            'propuestas' => $this->filtrarRevision(clone $base, 'propuestas')->count(),
            'avances' => $this->filtrarRevision(clone $base, 'avances')->count(),
            'culminaciones' => $this->filtrarRevision(clone $base, 'culminaciones')->count(),
        ];
    }

    private function filtrarRevision(Builder $query, string $revision): Builder
    {
        return match ($revision) {
            'pendientes' => $query->where(function (Builder $query): void {
                $query->whereIn('estado', ['pendiente', 'propuesta'])
                    ->orWhere('estado', 'finalizado')
                    ->orWhereHas('sesionesAtencion', fn (Builder $sesiones) => $this->sesionesPendientes($sesiones));
            }),
            'propuestas' => $query->whereIn('estado', ['pendiente', 'propuesta']),
            'avances' => $query->whereHas('sesionesAtencion', fn (Builder $sesiones) => $this->sesionesPendientes($sesiones)),
            'culminaciones' => $query->where('estado', 'finalizado'),
            default => $query,
        };
    }

    private function sesionesPendientes(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('autorizado', false)
                ->orWhereNull('autorizado_por')
                ->orWhereNull('fecha_autorizacion');
        });
    }

    public function proponer(Request $request): RedirectResponse|JsonResponse
    {
        $registro = $this->flujo->proponer($request->user(), $request->validate([
            'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
            'tratamiento_id' => ['required', 'integer', 'exists:tratamientos,id'],
            'pieza_dental' => ['nullable', 'string', 'max:20'], 'observacion' => ['nullable', 'string', 'max:2000'],
        ]));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'id' => $registro->id, 'message' => 'Propuesta enviada para autorización docente.'], 201)
            : back()->with('success', 'Propuesta enviada para autorización docente.');
    }

    public function actualizarPropuesta(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $registro = $this->flujo->editarPropuesta($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user(), $request->validate([
            'tratamiento_id' => ['required', 'integer', 'exists:tratamientos,id'],
            'pieza_dental' => ['nullable', 'string', 'max:20'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'id' => $registro->id, 'message' => 'Propuesta actualizada y enviada para revisión docente.'])
            : back()->with('success', 'Propuesta actualizada y enviada para revisión docente.');
    }

    public function autorizarRealizado(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $this->flujo->autorizar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Tratamiento autorizado para Cajas.'])
            : back()->with('success', 'Tratamiento autorizado para Cajas.');
    }

    public function rechazarRealizado(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motivo.required' => 'Debe indicar el motivo del rechazo.',
            'motivo.min' => 'El motivo del rechazo debe tener al menos 5 caracteres.',
        ]);
        $this->flujo->rechazar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user(), $data['motivo']);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Propuesta rechazada. El estudiante puede corregirla y reenviarla.'])
            : back()->with('success', 'Propuesta rechazada. El estudiante puede corregirla y reenviarla.');
    }

    public function eliminarPropuesta(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $this->flujo->eliminarPropuesta($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Propuesta eliminada.'])
            : back()->with('success', 'Propuesta eliminada.');
    }

    public function finalizar(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $this->flujo->finalizar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Tratamiento finalizado por el estudiante.'])
            : back()->with('success', 'Tratamiento finalizado por el estudiante.');
    }

    public function culminar(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $this->flujo->culminar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Culminación confirmada por el docente.'])
            : back()->with('success', 'Culminación confirmada por el docente.');
    }

    private function tratamientoRealizadoVisible(Request $request, int $id): TratamientoRealizado
    {
        return $this->flujo->tratamientosVisibles($request->user())->findOrFail($id);
    }

    public function registrarSesion(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $registro = $this->flujo->tratamientosVisibles($request->user())->findOrFail($tratamiento);
        $sesion = $this->flujo->registrarSesion($registro, $request->user(), $request->validate(['fecha' => ['required', 'date', 'before_or_equal:today'], 'descripcion' => ['required', 'string', 'max:5000']]));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'id' => $sesion->id, 'message' => 'Avance registrado para revisión docente.'], 201)
            : back()->with('success', 'Avance registrado para revisión docente.');
    }

    public function autorizarSesion(Request $request, int $sesion): RedirectResponse|JsonResponse
    {
        $registro = SesionAtencion::whereIn('tratamiento_realizado_id', $this->flujo->tratamientosVisibles($request->user())->select('id'))->findOrFail($sesion);
        $data = $request->validate(['observacion_docente' => ['nullable', 'string', 'max:2000']]);
        $this->flujo->autorizarSesion($registro, $request->user(), $data['observacion_docente'] ?? null);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Avance autorizado para solicitar insumos.'])
            : back()->with('success', 'Avance autorizado para solicitar insumos.');
    }
}
