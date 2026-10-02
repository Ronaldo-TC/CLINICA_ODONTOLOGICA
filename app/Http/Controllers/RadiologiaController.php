<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\Usuario;
use Illuminate\Http\Request;

class RadiologiaController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = Radiologia::class;
    protected string $viewPrefix       = 'radiologia';
    protected string $resourceName     = 'Radiología';
    protected string $pluralName       = 'Radiologías';
    protected string $permissionPrefix = 'radiologias';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Radiologia::query()
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'clinica:id,especialidad,modulo',
                'usuario:id,nombre_completo',
                'caja:id,recibo,costo_total',
            ])
            ->orderByDesc('fecha_toma')
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('observacion', 'like', "%{$buscar}%")
                    ->orWhereHas('paciente', function ($p) use ($buscar) {
                        $p->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('ci', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
        }

        if ($desde = $request->input('desde')) {
            $q->whereDate('fecha_toma', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $q->whereDate('fecha_toma', '<=', $hasta);
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
            'estados'  => Radiologia::query()
                ->select('estado')
                ->whereNotNull('estado')
                ->distinct()
                ->orderBy('estado')
                ->pluck('estado'),
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
            'descripcion' => ['required', 'string', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:100'],
            'estado'      => ['required', 'string', 'max:50'],
            'usuario_id'  => ['required', 'integer', 'exists:usuarios,id'],
            'caja_id'     => ['required', 'integer', 'exists:cajas,id'],
            'clinica_id'  => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:pacientes,id'],
            'remision_id' => ['nullable', 'integer', 'exists:remisiones,id'],
            'archivo_ruta' => ['nullable', 'string', 'max:500'],
            'fecha_toma'  => ['nullable', 'date'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'descripcion.required' => 'La descripción es obligatoria.',
            'estado.required'      => 'El estado es obligatorio.',
            'usuario_id.required'  => 'Debe asignar un responsable.',
            'caja_id.required'     => 'Debe asignar una caja.',
            'clinica_id.required'  => 'Debe seleccionar una clínica.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['observacion', 'archivo_ruta'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['usuario_id'] = $data['usuario_id'] ?? auth()->id();
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        return $this->normalizarStrings($data);
    }

    protected function canDestroy($model): array
    {
        return [true, ''];
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    public function buscarPacientes(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $pacientes = Paciente::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->map(fn($p) => [
                'id'        => $p->id,
                'texto'     => $p->nombre_completo,
                'extra'     => $p->ci ? "CI: {$p->ci}" : '',
                'iniciales' => $p->iniciales,
            ]);

        return response()->json($pacientes);
    }

    public function buscarCajas(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $cajas = Caja::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('recibo', 'like', "%{$q}%")
                        ->orWhere('paciente_nombre_completo', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'recibo', 'paciente_nombre_completo', 'costo_total'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'texto' => 'Recibo #' . ($c->recibo ?: $c->id) . ' - ' . ($c->paciente_nombre_completo ?: 'Sin paciente'),
                'extra' => 'Costo: ' . number_format($c->costo_total, 2) . ' Bs',
            ]);

        return response()->json($cajas);
    }

    public function datosIniciales()
    {
        $this->authorizeAction('ver');

        return response()->json([
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo'])
                ->map(fn($c) => ['id' => $c->id, 'texto' => "{$c->especialidad} ({$c->modulo})"]),
        ]);
    }
}
