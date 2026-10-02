<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Http\Request;

class EsterilizacionController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = Esterilizacion::class;
    protected string $viewPrefix       = 'esterilizacion';
    protected string $resourceName     = 'Esterilización';
    protected string $pluralName       = 'Esterilizaciones';
    protected string $permissionPrefix = 'esterilizaciones';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Esterilizacion::query()
            ->with([
                'clinica:id,especialidad,modulo',
                'periodo:id,nombre,gestion,semestre',
                'estudiante:id,nombre_completo',
                'usuario:id,nombre_completo',
            ])
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('info', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($periodoId = $request->input('periodo_id')) {
            $q->where('periodo_id', $periodoId);
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
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas'    => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos'    => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
            'estudiantes' => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['estudiante', 'interno']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'cantidad_sobres'   => ['required', 'integer', 'min:0'],
            'cantidad_paquetes' => ['required', 'integer', 'min:0'],
            'habilitada'        => ['nullable', 'boolean'],
            'info'              => ['nullable', 'string', 'max:200'],
            'descripcion'       => ['nullable', 'string', 'max:200'],
            'fecha_entrega'     => ['nullable', 'date'],
            'fecha_recojo'      => ['nullable', 'date', 'after_or_equal:fecha_entrega'],
            'estudiante_id'     => ['nullable', 'integer', 'exists:usuarios,id'],
            'clinica_id'        => ['required', 'integer', 'exists:clinicas,id'],
            'usuario_id'        => ['required', 'integer', 'exists:usuarios,id'],
            'periodo_id'        => ['required', 'integer', 'exists:periodos,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'cantidad_sobres.required'    => 'La cantidad de sobres es obligatoria.',
            'cantidad_paquetes.required'  => 'La cantidad de paquetes es obligatoria.',
            'clinica_id.required'         => 'Debe seleccionar una clínica.',
            'usuario_id.required'         => 'Debe seleccionar un responsable.',
            'periodo_id.required'         => 'Debe seleccionar un periodo.',
            'fecha_recojo.after_or_equal' => 'La fecha de recojo debe ser igual o posterior a la entrega.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['info', 'descripcion'] as $col) {
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
        // $data['usuario_id'] = $data['usuario_id'] ?? auth()->id();
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
        if ($model->autorizaciones()->count() > 0) {
            return [false, 'No se puede eliminar: tiene autorizaciones asociadas.'];
        }
        return [true, ''];
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');
        $q   = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        $roles = match ($rol) {
            'estudiante'  => ['estudiante', 'interno'],
            'responsable' => ['docente', 'jefe-catedra', 'esterilizacion'],
            default       => [],
        };

        if (empty($roles)) return response()->json([]);

        $usuarios = Usuario::where('habilitado', 1)
            ->whereHas('roles', fn($r) => $r->whereIn('name', $roles))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('nombre_usuario', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'ci', 'nombre_completo'])
            ->map(fn($u) => [
                'id'    => $u->id,
                'texto' => $u->nombre_completo,
                'extra' => $u->ci ? "CI: {$u->ci}" : '',
            ]);

        return response()->json($usuarios);
    }

    public function datosIniciales()
    {
        $this->authorizeAction('ver');

        return response()->json([
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo'])
                ->map(fn($c) => ['id' => $c->id, 'texto' => "{$c->especialidad} ({$c->modulo})"]),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre'])
                ->map(fn($p) => ['id' => $p->id, 'texto' => "{$p->nombre} ({$p->gestion}-{$p->semestre})"]),
        ]);
    }
}
