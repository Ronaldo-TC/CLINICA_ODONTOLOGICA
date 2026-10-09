<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Http\Resources\PacienteResource;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Paciente::class;

    protected string $viewPrefix = 'pacientes';

    protected string $resourceName = 'Paciente';

    protected string $pluralName = 'Pacientes';

    protected string $permissionPrefix = 'pacientes';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Paciente::query()
            ->withCount(['admisiones', 'historiasClinicas'])
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('ci', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%");
            });
        }

        if ($genero = $request->input('genero')) {
            $q->where('genero', $genero);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA PARA VISTAS
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'generos' => Paciente::query()
                ->select('genero')
                ->whereNotNull('genero')
                ->distinct()
                ->orderBy('genero')
                ->pluck('genero'),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'ci' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('pacientes', 'ci')->ignore($id),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['nullable', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'genero' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado_civil' => ['nullable', 'string', 'max:200'],
            'escuela' => ['nullable', 'string', 'max:200'],
            'ocupacion' => ['nullable', 'string', 'max:200'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'emergencia' => ['nullable', 'string', 'max:200'],
            'telefono_emergencia' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'ci.unique' => 'Ya existe un paciente con esa cédula.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['ci', 'apellido_paterno', 'apellido_materno', 'genero'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        return $this->normalizarStrings($data);
    }

    protected function beforeUpdate($model, array $data): array
    {
        return $this->normalizarStrings($data);
    }

    protected function canDestroy($model): array
    {
        // No eliminar si tiene admisiones o historias clínicas
        if ($model->admisiones()->count() > 0) {
            $count = $model->admisiones()->count();

            return [false, "No se puede eliminar: {$count} admisión(es) dependen de este paciente."];
        }

        if ($model->historiasClinicas()->count() > 0) {
            $count = $model->historiasClinicas()->count();

            return [false, "No se puede eliminar: {$count} historia(s) clínica(s) dependen de este paciente."];
        }

        return [true, ''];
    }

    public function indexApi(Request $request): JsonResponse
    {
        $this->autorizarApi($request, 'pacientes.ver');

        $buscar = trim((string) $request->input('buscar'));

        $paginador = Paciente::query()
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($x) use ($buscar) {
                    $x->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombre')
            ->paginate($this->porPaginaApi($request));

        return response()->json([
            'success' => true,
            'data' => PacienteResource::collection($paginador->items()),
            'paginacion' => $this->paginacionApi($paginador),
        ]);
    }

    public function showApi(Request $request, int $id): JsonResponse
    {
        $this->autorizarApi($request, 'pacientes.ver');

        $paciente = Paciente::query()
            ->withCount(['admisiones', 'historiasClinicas'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                (new PacienteResource($paciente))->toArray($request),
                [
                    'total_admisiones' => $paciente->admisiones_count,
                    'total_historias' => $paciente->historias_clinicas_count,
                ]
            ),
        ]);
    }
}
