<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistoriaController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = HistoriaClinica::class;
    protected string $viewPrefix       = 'historias';
    protected string $resourceName     = 'Historia Clínica';
    protected string $pluralName       = 'Historias Clínicas';
    protected string $permissionPrefix = 'historias';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return HistoriaClinica::query()
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'clinica:id,especialidad,modulo',
                'periodo:id,nombre,gestion,semestre',
                'admision:id,id',
                'estudiante:id,nombre_completo',
                'docente:id,nombre_completo',
            ])
            ->withCount('tratamientosRealizados')
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->whereHas('paciente', function ($p) use ($buscar) {
                    $p->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                })->orWhere('archivo_nombre', 'like', "%{$buscar}%");
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($periodoId = $request->input('periodo_id')) {
            $q->where('periodo_id', $periodoId);
        }

        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
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
            'admisiones'  => Admision::with('paciente:id,nombre,apellido_paterno,apellido_materno,ci')
                ->orderByDesc('id')
                ->limit(500)
                ->get(['id', 'paciente_id']),
            'clinicas'    => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos'    => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
            'estudiantes' => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['estudiante', 'interno']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
            'docentes'    => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['docente', 'jefe-catedra']))
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
            'admision_id'              => ['required', 'integer', 'exists:admisiones,id'],
            'paciente_id'              => ['required', 'integer', 'exists:pacientes,id'],
            'clinica_id'               => ['required', 'integer', 'exists:clinicas,id'],
            'periodo_id'               => ['required', 'integer', 'exists:periodos,id'],
            'estado'                   => ['nullable', 'string', 'max:50'],
            'archivo_nombre'           => ['nullable', 'string', 'max:100'],
            'autorizado'               => ['nullable', 'boolean'],
            'estudiante_id'            => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_estudiante'  => ['required', 'integer', 'exists:clinicas,id'],
            'docente_id'               => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_docente'     => ['required', 'integer', 'exists:clinicas,id'],
            'fecha_inicio'             => ['nullable', 'date'],
            'fecha_fin'                => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'admision_id.required'             => 'Debe seleccionar una admisión.',
            'admision_id.exists'               => 'La admisión seleccionada no existe.',
            'paciente_id.required'             => 'Debe seleccionar un paciente.',
            'clinica_id.required'              => 'Debe seleccionar una clínica.',
            'periodo_id.required'              => 'Debe seleccionar un periodo.',
            'estudiante_id.required'           => 'Debe seleccionar un estudiante.',
            'especialidad_estudiante.required' => 'Debe seleccionar la clínica del estudiante.',
            'docente_id.required'              => 'Debe seleccionar un docente.',
            'especialidad_docente.required'    => 'Debe seleccionar la clínica del docente.',
            'fecha_fin.after_or_equal'         => 'La fecha de fin debe ser igual o posterior al inicio.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['estado', 'archivo_nombre', 'archivo', 'revision_docente'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['autorizado']        = request()->boolean('autorizado') ? 1 : 0;
        $data['estado_revision']   = 0;
        $data['revision_cualquiera'] = 0;
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['autorizado'] = request()->boolean('autorizado') ? 1 : 0;
        return $data;
    }

    protected function canDestroy($model): array
    {
        if ($model->tratamientosRealizados()->count() > 0) {
            $count = $model->tratamientosRealizados()->count();
            return [false, "No se puede eliminar: {$count} tratamiento(s) dependen de esta historia."];
        }
        return [true, ''];
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    /**
     * GET /historias/buscar-admisiones?q=&paciente_id=
     */
    public function buscarAdmisiones(Request $request)
    {
        $this->authorizeAction('ver');

        $q          = trim((string) $request->input('q'));
        $pacienteId = $request->input('paciente_id');

        $query = Admision::with(['paciente:id,ci,nombre,apellido_paterno,apellido_materno'])
            ->orderByDesc('id');

        if ($pacienteId) {
            $query->where('paciente_id', $pacienteId);
        } elseif ($q !== '') {
            $query->whereHas('paciente', function ($p) use ($q) {
                $p->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('apellido_materno', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%");
            });
        }

        $admisiones = $query->limit(20)->get()->map(fn($a) => [
            'id'         => $a->id,
            'paciente_id' => $a->paciente_id,
            'texto'      => $a->paciente?->nombre_completo ?? "Admisión #{$a->id}",
            'extra'      => 'CI: ' . ($a->paciente?->ci ?? '—') . ' · Adm #' . $a->id,
            'iniciales'  => $a->paciente?->iniciales ?? '??',
        ]);

        return response()->json($admisiones);
    }

    /**
     * GET /historias/buscar-usuarios?q=&rol=
     */
    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');

        $q   = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'docente'    => ['docente', 'jefe-catedra'],
            default      => [],
        };

        if (empty($roles)) {
            return response()->json([]);
        }

        $query = Usuario::where('habilitado', 1)
            ->whereHas('roles', fn($r) => $r->whereIn('name', $roles));

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('nombre_completo', 'like', "%{$q}%")
                    ->orWhere('nombre_usuario', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%");
            });
        }

        $usuarios = $query->orderBy('nombre_completo')->limit(20)->get(['id', 'ci', 'nombre_completo'])
            ->map(fn($u) => [
                'id'    => $u->id,
                'texto' => $u->nombre_completo,
                'extra' => $u->ci ? "CI: {$u->ci}" : '',
            ]);

        return response()->json($usuarios);
    }

    /**
     * GET /historias/datos-iniciales
     */
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
