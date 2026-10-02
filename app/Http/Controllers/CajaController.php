<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\Usuario;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Caja::class;
    protected string $viewPrefix       = 'cajas';
    protected string $resourceName     = 'Caja';
    protected string $pluralName       = 'Cajas';
    protected string $permissionPrefix = 'cajas';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Caja::query()
            ->with([
                'tratamiento:id,nombre,costo',
                'clinica:id,especialidad,modulo',
                'remision:id,id',
                'usuario:id,nombre_completo',
            ])
            ->withCount('detalles')
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('paciente_nombre_completo', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%")
                    ->orWhere('recibo', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($tipoPago = $request->input('tipo_pago')) {
            $q->where('tipo_pago', $tipoPago);
        }

        if (($estado = $request->input('estado')) !== null && $estado !== '') {
            $q->where('habilitada', (int) $estado);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'clinicas'    => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'tiposPago'   => Caja::query()
                ->select('tipo_pago')
                ->whereNotNull('tipo_pago')
                ->distinct()
                ->orderBy('tipo_pago')
                ->pluck('tipo_pago'),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas'     => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'tratamientos' => Tratamiento::orderBy('nombre')
                ->get(['id', 'nombre', 'costo', 'clinica_id']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'recibo'                  => ['nullable', 'integer'],
            'monto'                   => ['nullable', 'integer', 'min:0'],
            'costo_total'             => ['required', 'numeric', 'min:0'],
            'cancelado'               => ['nullable', 'numeric', 'min:0'],
            'habilitada'              => ['nullable', 'boolean'],
            'descripcion'             => ['nullable', 'string', 'max:100'],
            'ci'                      => ['nullable', 'integer'],
            'tipo'                    => ['nullable', 'string', 'max:100'],
            'tipo_pago'               => ['nullable', 'string', 'max:100'],
            'paciente_nombre_completo' => ['nullable', 'string', 'max:100'],
            'grado'                   => ['nullable', 'string', 'max:100'],
            'tratamiento_id'          => ['required', 'integer', 'exists:tratamientos,id'],
            'clinica_id'              => ['required', 'integer', 'exists:clinicas,id'],
            'remision_id'             => ['nullable', 'integer', 'exists:remisiones,id'],
            'usuario_id'              => ['required', 'integer', 'exists:usuarios,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'costo_total.required'   => 'El costo total es obligatorio.',
            'costo_total.numeric'    => 'El costo debe ser un número.',
            'tratamiento_id.required' => 'Debe seleccionar un tratamiento.',
            'clinica_id.required'    => 'Debe seleccionar una clínica.',
            'usuario_id.required'    => 'Debe asignar un responsable de caja.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['descripcion', 'tipo', 'tipo_pago', 'paciente_nombre_completo', 'grado'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada', true) ? 1 : 0;
        $data['cancelado']  = $data['cancelado'] ?? 0;
        $data['monto']      = $data['monto'] ?? 0;
        $data['ci']         = $data['ci'] ?? 0;
        $data['usuario_id'] = $data['usuario_id'] ?? request()->user()?->getAuthIdentifier();
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada') ? 1 : 0;
        return $data;
    }

    protected function canDestroy($model): array
    {
        if ($model->detalles()->count() > 0) {
            $count = $model->detalles()->count();
            return [false, "No se puede eliminar: tiene {$count} detalle(s) asociado(s)."];
        }
        return [true, ''];
    }
}
