<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Remision;
use Illuminate\Http\Request;

class RemisionController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Remision::class;
    protected string $viewPrefix       = 'remisiones';
    protected string $resourceName     = 'Remisión';
    protected string $pluralName       = 'Remisiones';
    protected string $permissionPrefix = 'remisiones';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Remision::query()
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'admision:id,id',
                'clinica:id,especialidad,modulo',
                'periodo:id,nombre,gestion,semestre',
            ])
            ->withCount(['historiasClinicas', 'insumosSolicitados'])
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->whereHas('paciente', function ($p) use ($buscar) {
                $p->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($periodoId = $request->input('periodo_id')) {
            $q->where('periodo_id', $periodoId);
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
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'admision_id'  => ['required', 'integer', 'exists:admisiones,id'],
            'paciente_id'  => ['required', 'integer', 'exists:pacientes,id'],
            'clinica_id'   => ['required', 'integer', 'exists:clinicas,id'],
            'periodo_id'   => ['required', 'integer', 'exists:periodos,id'],
            'habilitado'   => ['nullable', 'boolean'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'admision_id.required' => 'Debe seleccionar una admisión.',
            'paciente_id.required' => 'Debe seleccionar un paciente.',
            'clinica_id.required'  => 'Debe seleccionar una clínica destino.',
            'periodo_id.required'  => 'Debe seleccionar un periodo.',
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
        if ($model->historiasClinicas()->count() > 0) {
            return [false, 'No se puede eliminar: la remisión tiene historias clínicas asociadas.'];
        }
        if ($model->insumosSolicitados()->count() > 0) {
            return [false, 'No se puede eliminar: la remisión tiene insumos solicitados.'];
        }
        return [true, ''];
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    public function buscarAdmisiones(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $admisiones = Admision::with('paciente:id,ci,nombre,apellido_paterno,apellido_materno')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('paciente', function ($p) use ($q) {
                    $p->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'paciente_id' => $a->paciente_id,
                'texto'       => $a->paciente?->nombre_completo ?? "Admisión #{$a->id}",
                'extra'       => 'CI: ' . ($a->paciente?->ci ?? '—'),
                'iniciales'   => $a->paciente?->iniciales ?? '??',
            ]);

        return response()->json($admisiones);
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
