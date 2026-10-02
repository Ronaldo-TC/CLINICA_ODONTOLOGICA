<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\InsumoTratamiento;
use App\Models\TratamientoRealizado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsumoController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = Insumo::class;
    protected string $viewPrefix       = 'insumos';
    protected string $resourceName     = 'Insumo';
    protected string $pluralName       = 'Insumos';
    protected string $permissionPrefix = 'insumos';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES — Catálogo de insumos
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Insumo::query()
            ->with(['insumoTratamiento:id,nombre', 'clinica:id,especialidad,modulo'])
            ->orderBy('nombre');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('nombre', 'like', "%{$buscar}%")
                    ->orWhereHas('insumoTratamiento', fn($t) => $t->where('nombre', 'like', "%{$buscar}%"));
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
            'stats'    => [
                'total'      => Insumo::count(),
                'stock_bajo' => Insumo::whereColumn('cantidad_numerica', '<=', 'stock_minimo')->count(),
                'agotados'   => Insumo::where('cantidad_numerica', '<=', 0)->count(),
                'valor'      => (float) Insumo::selectRaw('COALESCE(SUM(cantidad_numerica * costo_unitario), 0) as total')->value('total'),
            ],
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas'    => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'tiposInsumo' => InsumoTratamiento::where('clinica_id', $model->clinica_id ?? 0)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ];
    }

    /* ============================================================
       VALIDACIÓN — Catálogo
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'nombre'                => ['required', 'string', 'max:100'],
            'cantidad_numerica'     => ['required', 'numeric', 'min:0'],
            'cantidad'              => ['nullable', 'string', 'max:100'],
            'stock_minimo'          => ['required', 'numeric', 'min:0'],
            'costo_unitario'        => ['required', 'numeric', 'min:0'],
            'insumo_tratamiento_id' => ['required', 'integer', 'exists:insumos_tratamiento,id'],
            'clinica_id'            => ['required', 'integer', 'exists:clinicas,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'nombre.required'                => 'El nombre del insumo es obligatorio.',
            'cantidad_numerica.required'     => 'La cantidad es obligatoria.',
            'stock_minimo.required'          => 'El stock mínimo es obligatorio.',
            'costo_unitario.required'        => 'El costo unitario es obligatorio.',
            'insumo_tratamiento_id.required' => 'Debe seleccionar un tipo de insumo.',
            'clinica_id.required'            => 'Debe seleccionar una clínica.',
        ];
    }

    /* ============================================================
       HOOKS — Catálogo
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        if (array_key_exists('cantidad', $data) && $data['cantidad'] === null) {
            $data['cantidad'] = '';
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
        if ($model->detalles()->whereHas('solicitud', fn($q) => $q->whereIn('estado', ['pendiente', 'autorizado']))->count() > 0) {
            return [false, 'No se puede eliminar: tiene solicitudes pendientes.'];
        }
        return [true, ''];
    }

    /* ============================================================
       TIPOS DE INSUMO POR CLÍNICA (AJAX)
       ============================================================ */

    public function tiposPorClinica(Request $request, $clinicaId)
    {
        $this->authorizeAction('ver');

        return response()->json(
            InsumoTratamiento::where('clinica_id', $clinicaId)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
        );
    }

    public function buscarInsumos(Request $request, $clinicaId)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $insumos = Insumo::where('clinica_id', $clinicaId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%");
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'cantidad_numerica', 'cantidad', 'costo_unitario'])
            ->map(fn($i) => [
                'id'       => $i->id,
                'texto'    => $i->nombre,
                'extra'    => 'Stock: ' . number_format($i->cantidad_numerica, 2) . ' ' . $i->cantidad,
                'stock'    => (float) $i->cantidad_numerica,
                'unidad'   => $i->cantidad,
                'costo'    => (float) $i->costo_unitario,
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
            'motivo'   => ['nullable', 'string', 'max:200'],
        ]);

        $insumo = Insumo::findOrFail($id);
        $nuevo  = (float) $insumo->cantidad_numerica + (float) $validated['cantidad'];

        if ($nuevo < 0) {
            return response()->json(['success' => false, 'message' => 'El stock no puede ser negativo.'], 422);
        }

        $insumo->update(['cantidad_numerica' => $nuevo]);

        return response()->json([
            'success'     => true,
            'message'     => "Stock ajustado. Nuevo stock: {$nuevo}",
            'nuevo_stock' => $nuevo,
        ]);
    }
}
