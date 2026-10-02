<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TratamientoController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Tratamiento::class;
    protected string $viewPrefix       = 'tratamientos';
    protected string $resourceName     = 'Tratamiento';
    protected string $pluralName       = 'Tratamientos';
    protected string $permissionPrefix = 'tratamientos';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Tratamiento::query()
            ->with('clinica:id,especialidad,modulo')
            ->withCount('tratamientosRealizados')
            ->orderBy('nombre');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
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

    protected function extraDatosData($model): array
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
            'costo'         => ['required', 'numeric', 'min:0'],
            'porcentaje_pg' => ['nullable', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string', 'max:100'],
            'clinica_id'    => ['required', 'integer', 'exists:clinicas,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'nombre.required'    => 'El nombre del tratamiento es obligatorio.',
            'nombre.unique'      => 'Ya existe ese tratamiento en esta clínica.',
            'costo.required'     => 'El costo es obligatorio.',
            'costo.numeric'      => 'El costo debe ser un número.',
            'costo.min'          => 'El costo no puede ser negativo.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
            'clinica_id.exists'  => 'La clínica no existe.',
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
        return $this->normalizarStrings($data);
    }

    protected function beforeUpdate($model, array $data): array
    {
        return $this->normalizarStrings($data);
    }

    protected function canDestroy($model): array
    {
        if ($model->tratamientosRealizados()->count() > 0) {
            $count = $model->tratamientosRealizados()->count();
            return [false, "No se puede eliminar: {$count} tratamiento(s) realizado(s) usan este."];
        }
        return [true, ''];
    }
}
