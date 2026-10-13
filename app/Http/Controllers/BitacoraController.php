<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Bitacora;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Bitacora::class;

    protected string $viewPrefix = 'bitacoras';

    protected string $resourceName = 'Bitácora';

    protected string $pluralName = 'Bitácoras';

    protected string $permissionPrefix = 'bitacoras';

    protected int $perPage = 25;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request): Builder
    {
        return $this->buildTablaQuery($request);
    }

    protected function buildTablaQuery(Request $request): Builder
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:200'],
            'tipo' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
        ], [
            'desde.date_format' => 'La fecha Desde debe tener el formato año-mes-día.',
            'hasta.date_format' => 'La fecha Hasta debe tener el formato año-mes-día.',
            'hasta.after_or_equal' => 'La fecha Hasta debe ser igual o posterior a Desde.',
        ]);
        $q = Bitacora::query()->visiblePara($request->user())->with('usuario:id,nombre_usuario,nombre_completo')
            ->orderByDesc('fecha')->orderByDesc('id');

        if ($buscar = trim($filters['buscar'] ?? '')) {
            $q->where(function (Builder $x) use ($buscar): void {
                $x->where('datos', 'like', "%{$buscar}%")
                    ->orWhere('ip', 'like', "%{$buscar}%")
                    ->orWhere('tipo', 'like', "%{$buscar}%")
                    ->orWhereHas('usuario', function (Builder $u) use ($buscar): void {
                        $u->where('nombre_completo', 'like', "%{$buscar}%")
                            ->orWhere('nombre_usuario', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($tipo = trim($filters['tipo'] ?? '')) {
            $q->where('tipo', $tipo);
        }

        if ($estado = trim($filters['estado'] ?? '')) {
            $q->where('estado', $estado);
        }

        if ($desde = $filters['desde'] ?? null) {
            $q->whereDate('fecha', '>=', $desde);
        }

        if ($hasta = $filters['hasta'] ?? null) {
            $q->whereDate('fecha', '<=', $hasta);
        }

        return $q;
    }

    public function show(Request $request, int|string $id): View|JsonResponse
    {
        $this->authorizeAction('ver');
        $registro = Bitacora::query()->visiblePara($request->user())
            ->with('usuario:id,nombre_usuario,nombre_completo')->findOrFail($id);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'registro' => $registro->append(['descripcion', 'usuario_nombre', 'usuario_cuenta', 'estado_nombre'])])
            : view('bitacoras.show', compact('registro'));
    }

    /* ============================================================
       DATOS EXTRA PARA VISTAS
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'tipos' => Bitacora::query()->visiblePara($request->user())
                ->select('tipo')
                ->distinct()
                ->orderBy('tipo')
                ->pluck('tipo'),

            'estados' => Bitacora::query()->visiblePara($request->user())
                ->select('estado')
                ->distinct()
                ->orderBy('estado')
                ->pluck('estado'),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [];
    }

    /* ============================================================
       SIN VALIDACIÓN (solo lectura)
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [];
    }

    /* ============================================================
       BLOQUEAR ESCRITURA — Solo lectura
       ============================================================ */

    public function store(Request $request)
    {
        abort(405, 'Las bitácoras son de solo lectura.');
    }

    public function update(Request $request, $id)
    {
        abort(405, 'Las bitácoras son de solo lectura.');
    }

    public function destroy(Request $request, $id)
    {
        abort(405, 'Las bitácoras son de solo lectura.');
    }
}
