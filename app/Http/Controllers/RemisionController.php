<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Http\Requests\AsignarRemisionRequest;
use App\Models\Admision;
use App\Models\Clinica;
use App\Models\Remision;
use App\Services\AccesoClinico;
use App\Services\AsignacionRemision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RemisionController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Remision::class;

    protected string $viewPrefix = 'remisiones';

    protected string $resourceName = 'Remisión';

    protected string $pluralName = 'Remisiones';

    protected string $permissionPrefix = 'remisiones';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    public function index(Request $request): View
    {
        $this->authorizeAction('ver');
        $registros = $this->buildTablaQuery($request)->paginate($this->perPage)->withQueryString();

        return view('remisiones.index', ['registros' => $registros, 'items' => $registros] + $this->extraIndexData($request));
    }

    protected function buildIndexQuery(Request $request): Builder
    {
        return AccesoClinico::remisiones(Remision::query(), $request->user())
            ->conDetalle()
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request): Builder
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->whereHas('paciente', function (Builder $p) use ($buscar): void {
                $p->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if (($estado = $request->input('estado')) !== null && $estado !== '') {
            $q->where('habilitado', (int) $estado);
        }

        if (in_array($request->input('flujo_estado'), ['pendiente', 'asignada'], true)) {
            $q->where('estado', $request->input('flujo_estado'));
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
        $model->paciente?->append('nombre_completo');

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
            'admision_id' => ['required', 'integer', 'exists:admisiones,id'],
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
            'habilitado' => ['nullable', 'boolean'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'admision_id.required' => 'Debe seleccionar una admisión.',
            'paciente_id.required' => 'Debe seleccionar un paciente.',
            'clinica_id.required' => 'Debe seleccionar una clínica destino.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    protected function beforeStore(array $data): array
    {
        $admision = AccesoClinico::admisiones(Admision::query(), request()->user())->findOrFail($data['admision_id']);
        if ((int) $admision->paciente_id !== (int) $data['paciente_id']) {
            throw ValidationException::withMessages(['paciente_id' => 'El paciente debe corresponder a la ficha de admisión.']);
        }
        $data['estado'] = 'pendiente';
        $data['habilitado'] = request()->boolean('habilitado', true) ? 1 : 0;

        return $data;
    }

    protected function beforeUpdate(Model $model, array $data): array
    {
        $registro = Remision::query()->lockForUpdate()->findOrFail($model->id);
        if ($registro->estado !== 'pendiente' || $registro->estudiante_id || $registro->docente_id || $registro->historiasClinicas()->exists()) {
            foreach (['admision_id', 'paciente_id', 'clinica_id'] as $campo) {
                if ((string) $registro->$campo !== (string) $data[$campo]) {
                    throw ValidationException::withMessages([$campo => 'La remisión ya tiene una asignación o historia clínica.']);
                }
            }
        }
        $this->beforeStore($data);
        $data['habilitado'] = request()->boolean('habilitado') ? 1 : 0;

        return $data;
    }

    protected function findOrFail(int|string $id): Model
    {
        return $this->buildIndexQuery(request())->findOrFail($id);
    }

    public function asignacion(Request $request, int $remision, AsignacionRemision $asignacion): View|JsonResponse
    {
        $registro = $this->findOrFail($remision);
        $datos = $asignacion->datos($registro, $request->user());

        if ($request->routeIs('pacientes-remitidos.*') && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'titulo' => 'Asignar estudiante',
                'html' => view('pacientes-remitidos.partials.modal-asignacion-contenido', $datos)->render(),
            ]);
        }

        return view($request->routeIs('pacientes-remitidos.*') ? 'pacientes-remitidos.asignar' : 'remisiones.asignar', $datos);
    }

    public function asignar(AsignarRemisionRequest $request, int $remision, AsignacionRemision $asignacion): RedirectResponse|JsonResponse
    {
        $historia = $asignacion->asignar(
            $this->findOrFail($remision),
            $request->user(),
            $request->integer('estudiante_id'),
            $request->docenteResponsableId(),
        );

        $mensaje = 'Asignación registrada. La historia clínica fue creada y autorizada automáticamente.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'historia_id' => $historia->id,
            ]);
        }

        return redirect()->route($request->routeIs('pacientes-remitidos.*') ? 'pacientes-remitidos.index' : 'historias.index')->with('success', $mensaje);
    }

    protected function canDestroy(Model $model): array
    {
        if ($model->historiasClinicas()->count() > 0) {
            return [false, 'No se puede eliminar: la remisión tiene historias clínicas asociadas.'];
        }
        if ($model->insumosSolicitados()->count() > 0) {
            return [false, 'No se puede eliminar: la remisión tiene insumos solicitados.'];
        }

        return [true, ''];
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    public function buscarAdmisiones(Request $request): JsonResponse
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $admisiones = AccesoClinico::admisiones(Admision::query(), $request->user())->with('paciente:id,ci,nombre,apellido_paterno,apellido_materno')
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->whereHas('paciente', function (Builder $p) use ($q): void {
                    $p->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'paciente_id' => $a->paciente_id,
                'texto' => $a->paciente?->nombre_completo ?? "Admisión #{$a->id}",
                'extra' => 'CI: '.($a->paciente?->ci ?? '—'),
                'iniciales' => $a->paciente?->iniciales ?? '??',
            ]);

        return response()->json($admisiones);
    }

    public function datosIniciales(): JsonResponse
    {
        $this->authorizeAction('ver');

        return response()->json([
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo'])
                ->map(fn ($c) => ['id' => $c->id, 'texto' => "{$c->especialidad} ({$c->modulo})"]),
        ]);
    }

    public function remitidos(Request $request): View
    {
        $registros = $this->consultaRemitidos($request);

        return view('pacientes-remitidos.index', [
            'registros' => $registros,
            'items' => $registros,
            'clinicas' => $this->clinicasRemitidos($request),
        ]);
    }

    public function buscarRemitidos(Request $request): JsonResponse
    {
        $registros = $this->consultaRemitidos($request);

        // Renderizamos solo el fragmento de la tabla
        $tablaHtml = view('pacientes-remitidos.partials.tabla', ['items' => $registros])->render();
        $paginacionHtml = (string) $registros->withQueryString()->links();

        return response()->json([
            'success' => true,
            'total' => $registros->total(),
            'tabla' => $tablaHtml,
            'paginacion' => $paginacionHtml,
            'query' => $request->only(['buscar', 'clinica_id', 'flujo_estado', 'page']),
        ]);
    }

    private function consultaRemitidos(Request $request): LengthAwarePaginator
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'clinica_id' => ['nullable', 'integer'],
            'flujo_estado' => ['nullable', Rule::in(['pendiente', 'asignada'])],
        ]);

        $usuario = $request->user();

        $query = AccesoClinico::remisiones(Remision::query(), $usuario)
            ->conDetalle()
            ->where('habilitado', true)
            ->orderByDesc('id');

        if ($buscar = trim($filtros['buscar'] ?? '')) {
            $query->whereHas('paciente', function (Builder $paciente) use ($buscar): void {
                $paciente->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%");
            });
        }

        foreach (['clinica_id'] as $campo) {
            if ($request->filled($campo)) {
                $query->where($campo, $request->integer($campo));
            }
        }

        if ($request->filled('flujo_estado')) {
            $query->where('estado', $filtros['flujo_estado']);
        }

        return $query->paginate(15)->withQueryString();
    }

    private function clinicasRemitidos(Request $request): Collection
    {
        $usuario = $request->user();

        return AccesoClinico::esAdministrador($usuario)
            ? Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo'])
            : $usuario->especialidades()
                ->wherePivot('habilitado', true)
                ->orderBy('especialidad')
                ->get(['clinicas.id', 'especialidad', 'modulo']);
    }

    public function derivadosEstudiante(Request $request): View|JsonResponse
    {
        $query = Remision::query()->where('estudiante_id', $request->user()->id)->where('habilitado', true)
            ->with(['paciente', 'clinica', 'docente', 'admision:id,motivo_consulta', 'historiasClinicas:id,remision_id,estudiante_id']);

        return $this->respuestaListadoEstudiante($request, 'remisiones.estudiante', $this->datosListadoEstudiante($request, $query));
    }
}
