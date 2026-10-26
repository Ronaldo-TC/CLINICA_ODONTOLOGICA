<?php

namespace App\Http\Controllers\Concerns;

use App\Models\HistoriaClinica;
use App\Models\Remision;
use App\Models\Usuario;
use App\Services\ReporteModulo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * HasCrudAjax
 *
 * Trait genérico para CRUD con respuestas AJAX.
 *
 * Cada controlador que use este trait DEBE definir:
 *   protected string $modelClass;
 *   protected string $viewPrefix;
 *   protected string $resourceName;
 *   protected string $pluralName;
 *   protected string $permissionPrefix;
 *   protected int    $perPage = 15;
 *
 * @property string $modelClass
 * @property string $viewPrefix
 * @property string $resourceName
 * @property string $pluralName
 * @property string $permissionPrefix
 * @property int $perPage
 */
trait HasCrudAjax
{
    public function reporte(Request $request, ReporteModulo $reporte): Response
    {
        $this->authorizeAction('ver');

        $reporte->validarFiltros($request);

        return $reporte->generar($this->buildTablaQuery($request), $this->viewPrefix, $request);
    }

    /* ============================================================
       ACCIONES GENÉRICAS
       ============================================================ */

    /**
     * GET /recurso
     * Vista principal con datos iniciales.
     */
    public function index(Request $request)
    {
        $this->authorizeAction('ver');

        $registros = $this->buildIndexQuery($request)
            ->paginate($this->perPage)
            ->withQueryString();

        $viewData = array_merge(
            [
                'registros' => $registros,
                'items' => $registros,   // alias por comodidad
            ],
            $this->extraIndexData($request)
        );

        return view("{$this->viewPrefix}.index", $viewData);
    }

    /**
     * GET /recurso/tabla
     * Devuelve JSON con HTML parcial + paginación + total.
     */
    public function tabla(Request $request): JsonResponse
    {
        $this->authorizeAction('ver');

        $registros = $this->buildTablaQuery($request)
            ->paginate($this->perPage)
            ->withQueryString();

        return response()->json([
            'filas' => view("{$this->viewPrefix}.partials.filas", [
                'registros' => $registros,
                'items' => $registros,
            ])->render(),
            'paginacion' => $registros->links()->toHtml(),
            'total' => $registros->total(),
        ]);
    }

    /**
     * GET /recurso/{id}/datos
     * JSON con datos del registro para el modal de edición.
     */
    public function datos($id): JsonResponse
    {
        $this->authorizeAction('ver');

        $model = $this->findOrFail($id);

        return response()->json(array_merge(
            [
                'success' => true,
                'es_edicion' => true,
                'registro' => $model,
                'id' => $model->id,
            ],
            $this->extraDatosData($model)
        ));
    }

    /**
     * GET /recurso/{id}
     */
    public function show($id)
    {
        $this->authorizeAction('ver');

        $model = $this->findOrFail($id);

        if ($this->esAjax()) {
            return response()->json([
                'success' => true,
                'registro' => $model,
            ]);
        }

        return view("{$this->viewPrefix}.show", array_merge(
            ['registro' => $model, 'model' => $model],
            $this->extraShowData($model)
        ));
    }

    /**
     * POST /recurso
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('crear');
        $transactionLevel = DB::transactionLevel();

        try {
            $data = $this->validateRequest($request, null);

            DB::beginTransaction();

            $data = $this->beforeStore($data);
            $model = $this->modelClass::create($data);
            $this->afterStore($model);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$this->resourceName} creado correctamente.",
                'id' => $model->id,
                'registro' => $model->fresh(),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack($transactionLevel);

            return $this->errorValidacion($e);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            report($e);

            return $this->errorServidor($e);
        }
    }

    /**
     * PUT /recurso/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorizeAction('editar');
        $model = $this->findOrFail($id);
        $transactionLevel = DB::transactionLevel();

        try {
            $data = $this->validateRequest($request, $model->id);

            DB::beginTransaction();

            $data = $this->beforeUpdate($model, $data);
            $model->update($data);
            $this->afterUpdate($model);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$this->resourceName} actualizado correctamente.",
                'id' => $model->id,
                'registro' => $model->fresh(),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack($transactionLevel);

            return $this->errorValidacion($e);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            report($e);

            return $this->errorServidor($e);
        }
    }

    /**
     * DELETE /recurso/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorizeAction('eliminar');
        $model = $this->findOrFail($id);
        $transactionLevel = DB::transactionLevel();

        try {
            $puede = $this->canDestroy($model);
            if (is_array($puede) && $puede[0] === false) {
                return response()->json([
                    'success' => false,
                    'message' => $puede[1] ?? 'No se puede eliminar el registro.',
                ], 422);
            }

            if ($this->beforeDestroy($model) === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el registro.',
                ], 422);
            }

            DB::beginTransaction();
            $model->delete();
            $this->afterDestroy($model);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$this->resourceName} eliminado correctamente.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack($transactionLevel);
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            report($e);

            return $this->errorServidor($e);
        }
    }

    /* ============================================================
       HOOKS OVERRIDEABLES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return $this->modelClass::query();
    }

    protected function buildTablaQuery(Request $request)
    {
        return $this->buildIndexQuery($request);
    }

    protected function validationRules(?int $id): array
    {
        return [];
    }

    protected function validationMessages(): array
    {
        return [];
    }

    protected function beforeStore(array $data): array
    {
        return $data;
    }

    protected function afterStore(Model $model): void {}

    protected function beforeUpdate(Model $model, array $data): array
    {
        return $data;
    }

    protected function afterUpdate(Model $model): void {}

    protected function beforeDestroy(Model $model): bool
    {
        return true;
    }

    protected function afterDestroy(Model $model): void {}

    protected function canDestroy(Model $model): array
    {
        return [true, ''];
    }

    protected function extraIndexData(Request $request): array
    {
        return [];
    }

    protected function extraDatosData(Model $model): array
    {
        return [];
    }

    protected function extraShowData(Model $model): array
    {
        return [];
    }

    /* ============================================================
       HELPERS — Auth por sesión
       ============================================================ */

    /**
     * Autoriza según permiso `{permissionPrefix}.{accion}`.
     * Soporta tanto auth() como la sesión custom (session('user_id')).
     */
    protected function authorizeAction(string $accion): void
    {
        if (! $this->permissionPrefix) {
            return;
        }

        $user = auth()->user() ?? Usuario::find(session('user_id'));

        $permiso = "{$this->permissionPrefix}.{$accion}";

        abort_unless(
            $user?->can($permiso) || $user?->can("{$this->permissionPrefix}.gestionar") || $user?->hasRole('admin'),
            403,
            "No tienes permiso {$permiso}"
        );
    }

    /**
     * Encuentra el modelo o lanza 404.
     */
    protected function findOrFail($id): Model
    {
        return $this->modelClass::findOrFail($id);
    }

    /**
     * Valida la request con reglas y mensajes del controlador.
     */
    protected function validateRequest(Request $request, ?int $id): array
    {
        return Validator::make(
            $request->all(),
            $this->validationRules($id),
            $this->validationMessages()
        )->validate();
    }

    /**
     * ¿La request es AJAX o espera JSON?
     */
    protected function esAjax(): bool
    {
        return request()->ajax() || request()->wantsJson();
    }

    /**
     * Respuesta de error de validación.
     */
    protected function errorValidacion(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Revisa los campos del formulario.',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Respuesta de error genérico.
     */
    protected function errorServidor(\Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => app()->environment('production')
                ? 'Ocurrió un error al procesar la solicitud.'
                : 'Error: '.$e->getMessage(),
        ], 500);
    }

    protected function respuestaListadoEstudiante(Request $request, string $nombreVista, array $datos): View|JsonResponse
    {
        $vista = view($nombreVista, $datos);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'tabla' => $vista->fragment('tabla-estudiante'), 'total' => $datos['registros']->total()])
            : $vista;
    }

    protected function datosListadoEstudiante(Request $request, Builder $query): array
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'clinica_id' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::in(['revision', 'abierta', 'pendiente', 'asignada', 'autorizada', ...['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado']])],
        ]);
        if ($buscar = trim($filtros['buscar'] ?? '')) {
            $query->whereHas('paciente', function (Builder $paciente) use ($buscar): void {
                $paciente->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")->orWhere('ci', 'like', "%{$buscar}%");
            });
        }
        foreach (['clinica_id', 'estado'] as $campo) {
            if ($request->filled($campo) && ($campo !== 'clinica_id' || $query->getModel() instanceof HistoriaClinica || $query->getModel() instanceof Remision)) {
                $query->where($campo, $filtros[$campo]);
            }
        }

        return [
            'registros' => $query->orderByDesc('id')->paginate(15)->withQueryString(),
            'clinicas' => $request->user()->especialidades()->wherePivot('habilitado', true)->orderBy('especialidad')->get(),
        ];
    }
}
