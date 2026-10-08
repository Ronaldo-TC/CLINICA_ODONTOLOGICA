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
            'porcentaje_pg' => ['nullable', 'string', 'max:100'],
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
        foreach (['porcentaje_pg', 'descripcion'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $this->autorizarPrecio($data);
        $data['precio_asignado'] = isset($data['costo']);
        $data['costo'] = $data['costo'] ?? 0;

        return $this->normalizarStrings($data);
    }

    protected function beforeUpdate(Model $model, array $data): array
    {
        $this->autorizarPrecio($data);
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

    private function autorizarPrecio(array $data): void
    {
        if (isset($data['costo']) || array_key_exists('porcentaje_pg', $data)) {
            abort_unless(Gate::allows('tratamientos.precios'), 403, 'No tiene permiso para asignar precios.');
        }
    }

    public function show(Tratamiento $tratamiento): View|JsonResponse
    {
        $this->authorizeAction('ver');
        $tratamiento->load(['clinica', 'insumos' => fn ($query) => $query->orderBy('nombre')]);

        if ($this->esAjax()) {
            return response()->json(['success' => true, 'registro' => $tratamiento]);
        }

        return view('tratamientos.show', [
            'tratamiento' => $tratamiento,
            'insumosDisponibles' => Gate::allows('tratamientos.insumos')
                ? Insumo::query()->where('clinica_id', $tratamiento->clinica_id)->orderBy('nombre')->get()
                : collect(),
        ]);
    }

    public function actualizarPrecio(Request $request, Tratamiento $tratamiento): RedirectResponse|JsonResponse
    {
        Gate::authorize('tratamientos.precios');
        $data = $request->validate([
            'costo' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'porcentaje_pg' => ['nullable', 'string', 'max:100'],
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

        return $reporte->generar($this->flujo->tratamientosVisibles($request->user())->orderByDesc('id'), 'tratamientos-realizados', $request);
    }

    public function realizados(Request $request): View
    {
        $this->flujo->exigirPermiso($request->user(), ['atenciones.ver', 'atenciones.gestionar']);

        return view('tratamientos-realizados.index', [
            'registros' => $this->flujo->tratamientosVisibles($request->user())->with(['tratamiento', 'historiaClinica.paciente', 'sesionesAtencion.docente'])->orderByDesc('id')->paginate(15),
            'historias' => AccesoClinico::historias(HistoriaClinica::query(), $request->user())->where('autorizado', true)->with('paciente')->get(),
            'catalogo' => Tratamiento::orderBy('nombre')->get(),
        ]);
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

    public function autorizarRealizado(Request $request, int $tratamiento): RedirectResponse
    {
        $this->flujo->autorizar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return back()->with('success', 'Tratamiento autorizado para Cajas.');
    }

    public function finalizar(Request $request, int $tratamiento): RedirectResponse|JsonResponse
    {
        $this->flujo->finalizar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Tratamiento finalizado por el estudiante.'])
            : back()->with('success', 'Tratamiento finalizado por el estudiante.');
    }

    public function culminar(Request $request, int $tratamiento): RedirectResponse
    {
        $this->flujo->culminar($this->tratamientoRealizadoVisible($request, $tratamiento), $request->user());

        return back()->with('success', 'Culminación confirmada por el docente.');
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

    public function autorizarSesion(Request $request, int $sesion): RedirectResponse
    {
        $registro = SesionAtencion::whereIn('tratamiento_realizado_id', $this->flujo->tratamientosVisibles($request->user())->select('id'))->findOrFail($sesion);
        $data = $request->validate(['observacion_docente' => ['nullable', 'string', 'max:2000']]);
        $this->flujo->autorizarSesion($registro, $request->user(), $data['observacion_docente'] ?? null);

        return back()->with('success', 'Avance autorizado para solicitar insumos.');
    }
}
