<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Usuario;
use App\Services\AccesoClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EsterilizacionController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass = Esterilizacion::class;

    protected string $viewPrefix = 'esterilizacion';

    protected string $resourceName = 'Esterilización';

    protected string $pluralName = 'Esterilizaciones';

    protected string $permissionPrefix = 'esterilizaciones';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Esterilizacion::query()
            ->when(AccesoClinico::esEstudiante($request->user()), fn ($q) => $q->where('estudiante_id', $request->user()->id))
            ->when(! AccesoClinico::esAdministrador($request->user()) && $request->user()->hasAnyRole(['docente', 'jefe-catedra']), fn ($q) => $q->where('docente_id', $request->user()->id))
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
            'docentes' => Usuario::where('habilitado', true)->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'jefe-catedra']))->orderBy('nombre_completo')->get(['id', 'nombre_completo']),
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
            'estudiantes' => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['estudiante', 'interno']))
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
            'remision_id' => ['nullable', 'integer', 'exists:remisiones,id'],
            'docente_id' => ['required', 'integer', 'exists:usuarios,id'],
            'cantidad_sobres' => ['required', 'integer', 'min:0'],
            'cantidad_paquetes' => ['required', 'integer', 'min:0'],
            'habilitada' => ['nullable', 'boolean'],
            'info' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'fecha_entrega' => ['nullable', 'date'],
            'fecha_recojo' => ['nullable', 'date', 'after_or_equal:fecha_entrega'],
            'estudiante_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'cantidad_sobres.required' => 'La cantidad de sobres es obligatoria.',
            'cantidad_paquetes.required' => 'La cantidad de paquetes es obligatoria.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
            'usuario_id.required' => 'Debe seleccionar un responsable.',
            'periodo_id.required' => 'Debe seleccionar un periodo.',
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
        if (AccesoClinico::esEstudiante(request()->user())) {
            $data['estudiante_id'] = request()->user()->id;
        }
        if (! empty($data['remision_id'])) {
            $remision = AccesoClinico::remisiones(Remision::query(), request()->user())
                ->where('habilitado', true)->findOrFail($data['remision_id']);
            foreach (['estudiante_id', 'docente_id', 'clinica_id', 'periodo_id'] as $campo) {
                if ((int) $remision->$campo !== (int) ($data[$campo] ?? 0)) {
                    throw ValidationException::withMessages([$campo => 'El control debe corresponder a la asignación del paciente.']);
                }
            }
        }
        foreach (['estudiante' => ['estudiante', 'interno'], 'docente' => ['docente', 'jefe-catedra']] as $tipo => $roles) {
            $persona = Usuario::find($data[$tipo.'_id'] ?? null);
            if (! $persona?->habilitado || ! $persona->hasAnyRole($roles) || ! $persona->especialidades()->where('clinicas.id', $data['clinica_id'])->wherePivot('habilitado', true)->exists()) {
                throw ValidationException::withMessages([$tipo.'_id' => 'Seleccione personal habilitado de la especialidad.']);
            }
        }
        if ($data['cantidad_sobres'] + $data['cantidad_paquetes'] < 1) {
            throw ValidationException::withMessages(['cantidad_sobres' => 'Registre al menos un sobre o paquete.']);
        }
        $data['autorizado'] = false;
        $data['fecha_entrega'] = null;
        $data['fecha_recojo'] = null;
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada', true) ? 1 : 0;
        // $data['usuario_id'] = $data['usuario_id'] ?? auth()->id();
        $data['usuario_id'] = $data['usuario_id'] ?? request()->user()?->getAuthIdentifier();

        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        abort_if($model->autorizado || $model->fecha_entrega, 422, 'El control autorizado se conserva; registre entrega y recojo desde su ficha.');
        $data = $this->beforeStore($data);
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada') ? 1 : 0;

        return $data;
    }

    protected function findOrFail($id): Model
    {
        return $this->buildIndexQuery(request())->findOrFail($id);
    }

    public function control(Request $request, int $esterilizacion): View
    {
        return view('esterilizacion.control', ['registro' => $this->findOrFail($esterilizacion)]);
    }

    public function autorizar(Request $request, int $esterilizacion): RedirectResponse
    {
        $registro = $this->findOrFail($esterilizacion);
        abort_unless($request->user()->hasAnyRole(['docente', 'jefe-catedra']) && (int) $registro->docente_id === $request->user()->id, 403, 'La firma corresponde al docente asignado.');
        $data = $request->validate(['firma_docente' => ['required', 'string', 'max:2000000', 'starts_with:data:image/png;base64,']]);
        abort_if($registro->autorizado, 422, 'El control ya está autorizado.');
        $registro->update($data + ['autorizado' => true, 'fecha_autorizacion' => now()]);

        return back()->with('success', 'Material autorizado por el docente.');
    }

    public function movimiento(Request $request, int $esterilizacion): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'subadmin', 'esterilizacion']), 403);
        $data = $request->validate(['accion' => ['required', Rule::in(['recibir', 'entregar'])]]);
        DB::transaction(function () use ($request, $esterilizacion, $data): void {
            $visible = $this->findOrFail($esterilizacion);
            $registro = Esterilizacion::lockForUpdate()->findOrFail($visible->id);
            abort_unless($registro->autorizado && $registro->firma_docente, 422, 'Falta autorización y firma docente.');
            if ($data['accion'] === 'recibir') {
                abort_if($registro->fecha_entrega, 422, 'La recepción ya se registró.');
                $registro->update(['fecha_entrega' => today(), 'hora_entrega' => now()->format('H:i:s'), 'recibido_por' => $request->user()->id]);
            } else {
                abort_unless($registro->fecha_entrega && ! $registro->fecha_recojo, 422, 'Registre primero la recepción; el recojo no puede repetirse.');
                $registro->update(['fecha_recojo' => today(), 'hora_recojo' => now()->format('H:i:s'), 'entregado_por' => $request->user()->id]);
            }
        });

        return back()->with('success', 'Movimiento registrado.');
    }

    protected function canDestroy($model): array
    {
        if ($model->autorizado || $model->fecha_entrega || $model->fecha_recojo) {
            return [false, 'El control de esterilización ya tiene movimientos y debe conservarse.'];
        }
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
        $q = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'responsable' => ['docente', 'jefe-catedra', 'esterilizacion'],
            default => [],
        };

        if (empty($roles)) {
            return response()->json([]);
        }

        $usuarios = Usuario::where('habilitado', 1)
            ->whereHas('roles', fn ($r) => $r->whereIn('name', $roles))
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
            ->map(fn ($u) => [
                'id' => $u->id,
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
                ->map(fn ($c) => ['id' => $c->id, 'texto' => "{$c->especialidad} ({$c->modulo})"]),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre'])
                ->map(fn ($p) => ['id' => $p->id, 'texto' => "{$p->nombre} ({$p->gestion}-{$p->semestre})"]),
        ]);
    }
}
