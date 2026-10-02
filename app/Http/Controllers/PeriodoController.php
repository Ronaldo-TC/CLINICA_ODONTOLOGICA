<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodoController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Periodo::class;
    protected string $viewPrefix       = 'periodos';
    protected string $resourceName     = 'Periodo';
    protected string $pluralName       = 'Periodos';
    protected string $permissionPrefix = 'periodos';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Periodo::query()
            ->withCount(['admisiones', 'remisiones', 'historiasClinicas'])
            ->orderByDesc('gestion')
            ->orderByDesc('semestre');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where('nombre', 'like', "%{$buscar}%");
        }

        if ($gestion = $request->input('gestion')) {
            $q->where('gestion', (int) $gestion);
        }

        if (($estado = $request->input('estado')) !== null && $estado !== '') {
            $q->where('habilitado', (int) $estado);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'gestiones' => Periodo::query()
                ->select('gestion')
                ->distinct()
                ->orderByDesc('gestion')
                ->pluck('gestion'),
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
            'nombre' => ['required', 'string', 'max:100'],
            'gestion' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('periodos')->where(function ($query) use ($id) {
                    return $query->where('semestre', request('semestre'))
                        ->when($id, fn($q) => $q->where('id', '!=', $id));
                }),
            ],
            'semestre'     => ['required', 'integer', 'in:1,2'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after:fecha_inicio'],
            'habilitado'   => ['nullable', 'boolean'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'nombre.required'         => 'El nombre del periodo es obligatorio.',
            'nombre.max'              => 'El nombre no puede exceder 100 caracteres.',
            'gestion.required'        => 'La gestión es obligatoria.',
            'gestion.integer'         => 'La gestión debe ser un número.',
            'gestion.min'             => 'La gestión mínima es 2000.',
            'gestion.max'             => 'La gestión máxima es 2100.',
            'gestion.unique'          => 'Ya existe un periodo con esa gestión y semestre.',
            'semestre.required'       => 'El semestre es obligatorio.',
            'semestre.in'             => 'El semestre debe ser 1 o 2.',
            'fecha_inicio.required'   => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'       => 'La fecha de inicio no es válida.',
            'fecha_fin.required'      => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'          => 'La fecha de fin no es válida.',
            'fecha_fin.after'         => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    protected function beforeStore(array $data): array
    {
        $data['habilitado'] = request()->boolean('habilitado', true) ? 1 : 0;
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data['habilitado'] = request()->boolean('habilitado') ? 1 : 0;
        return $data;
    }

    protected function canDestroy($model): array
    {
        // No eliminar si tiene admisiones
        if ($model->admisiones()->count() > 0) {
            $count = $model->admisiones()->count();
            return [false, "No se puede eliminar: {$count} admisión(es) usan este periodo."];
        }

        // No eliminar si tiene remisiones
        if ($model->remisiones()->count() > 0) {
            $count = $model->remisiones()->count();
            return [false, "No se puede eliminar: {$count} remisión(es) usan este periodo."];
        }

        return [true, ''];
    }
}
