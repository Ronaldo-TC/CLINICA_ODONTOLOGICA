<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicaController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Clinica::class;
    protected string $viewPrefix       = 'clinicas';
    protected string $resourceName     = 'Clínica';
    protected string $pluralName       = 'Clínicas';
    protected string $permissionPrefix = 'clinicas';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Clinica::query()
            ->withCount(['especialidades', 'tratamientos', 'insumosTratamiento'])
            ->orderBy('modulo')
            ->orderBy('especialidad');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('especialidad', 'like', "%{$buscar}%")
                    ->orWhere('modulo', 'like', "%{$buscar}%");
            });
        }

        if ($modulo = trim((string) $request->input('modulo'))) {
            $q->where('modulo', $modulo);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'modulos' => Clinica::query()
                ->select('modulo')
                ->distinct()
                ->orderBy('modulo')
                ->pluck('modulo'),
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
            'modulo' => ['required', 'string', 'max:200'],
            'especialidad' => [
                'required',
                'string',
                'max:200',
                Rule::unique('clinicas', 'especialidad')->ignore($id),
            ],
            'curso' => ['nullable', 'integer', 'min:-1', 'max:10'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'modulo.required'        => 'El módulo es obligatorio.',
            'modulo.max'             => 'El módulo no puede exceder 200 caracteres.',
            'especialidad.required'  => 'La especialidad es obligatoria.',
            'especialidad.unique'    => 'Ya existe una clínica con esa especialidad.',
            'especialidad.max'       => 'La especialidad no puede exceder 200 caracteres.',
            'curso.integer'          => 'El curso debe ser un número.',
            'curso.min'              => 'El curso mínimo es -1.',
            'curso.max'              => 'El curso máximo es 10.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    protected function beforeStore(array $data): array
    {
        $data['curso'] = $data['curso'] ?? -1;
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data['curso'] = $data['curso'] ?? -1;
        return $data;
    }

    protected function canDestroy($model): array
    {
        // No eliminar si tiene tratamientos
        if ($model->tratamientos()->count() > 0) {
            $count = $model->tratamientos()->count();
            return [false, "No se puede eliminar: {$count} tratamiento(s) dependen de esta clínica."];
        }

        // No eliminar si tiene insumos
        if ($model->insumosTratamiento()->count() > 0) {
            $count = $model->insumosTratamiento()->count();
            return [false, "No se puede eliminar: {$count} tipo(s) de insumo dependen de esta clínica."];
        }

        return [true, ''];
    }
}
