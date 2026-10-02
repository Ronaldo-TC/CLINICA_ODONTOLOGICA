<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Bitacora::class;
    protected string $viewPrefix       = 'bitacoras';
    protected string $resourceName     = 'Bitácora';
    protected string $pluralName       = 'Bitácoras';
    protected string $permissionPrefix = 'bitacoras';
    protected int    $perPage          = 25;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Bitacora::query()
            ->with('usuario:id,nombre_usuario,nombre_completo')
            ->orderByDesc('fecha');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('datos', 'like', "%{$buscar}%")
                    ->orWhere('ip', 'like', "%{$buscar}%")
                    ->orWhereHas('usuario', function ($u) use ($buscar) {
                        $u->where('nombre_completo', 'like', "%{$buscar}%")
                            ->orWhere('nombre_usuario', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($tipo = trim((string) $request->input('tipo'))) {
            $q->where('tipo', $tipo);
        }

        if ($estado = trim((string) $request->input('estado'))) {
            $q->where('estado', $estado);
        }

        if ($desde = $request->input('desde')) {
            $q->whereDate('fecha', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $q->whereDate('fecha', '<=', $hasta);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA PARA VISTAS
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'tipos' => Bitacora::query()
                ->select('tipo')
                ->distinct()
                ->orderBy('tipo')
                ->pluck('tipo'),

            'estados' => Bitacora::query()
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
