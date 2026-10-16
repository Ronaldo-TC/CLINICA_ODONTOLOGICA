<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\EsterilizacionCiclo;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Usuario;
use App\Services\AccesoClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        return AccesoClinico::esterilizaciones(Esterilizacion::query(), $request->user())
            ->with([
                'clinica:id,especialidad,modulo',
                'periodo:id,nombre,gestion,semestre',
                'estudiante:id,nombre_completo,habilitado',
                'usuario:id,nombre_completo',
                'ultimoCiclo',
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
            'estudianteActual' => AccesoClinico::esEstudiante($request->user())
                ? $request->user()->only(['id', 'nombre_completo']) : null,
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
            'cantidad_sobres' => ['required', 'integer', 'min:0', 'max:1000'],
            'cantidad_paquetes' => ['required', 'integer', 'min:0', 'max:1000'],
            'habilitada' => ['nullable', 'boolean'],
            'info' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'fecha_entrega' => ['prohibited'],
            'fecha_recojo' => ['prohibited'],
            'estudiante_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
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
        $data['usuario_id'] = request()->user()->id;

        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $model = Esterilizacion::lockForUpdate()->findOrFail($model->id);
        abort_if($model->autorizado || $model->fecha_entrega || $model->fecha_recojo, 422, 'El control autorizado se conserva; registre entrega y recojo desde su ficha.');
        $data = $this->beforeStore($data);
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada', (bool) $model->habilitada) ? 1 : 0;
        $data['usuario_id'] = $model->usuario_id;

        return $data;
    }

    protected function findOrFail($id): Model
    {
        return $this->buildIndexQuery(request())->findOrFail($id);
    }

    public function control(Request $request, int $esterilizacion): View
    {
        $registro = $this->findOrFail($esterilizacion);
        $registro->load(['receptor', 'preparador', 'entregador', 'recogedor',
            'ciclos.operador', 'ciclos.revisor', 'ciclos.responsableLiberacion']);

        return view('esterilizacion.control', [
            'registro' => $registro,
            'cicloActual' => $registro->ultimoCiclo,
            'puedeAutorizar' => ($request->user()->hasAnyRole(['docente', 'jefe-catedra']) && (int) $registro->docente_id === $request->user()->id)
                || AccesoClinico::supervisaClinica($request->user(), $registro->clinica_id),
            'puedeGestionar' => $request->user()->can('esterilizaciones.gestionar')
                && $request->user()->hasAnyRole(['admin', 'subadmin', 'director', 'esterilizacion']),
        ]);
    }

    public function show(int $esterilizacion): View|JsonResponse
    {
        if ($this->esAjax()) {
            return response()->json(['success' => true, 'registro' => $this->findOrFail($esterilizacion)]);
        }

        return $this->control(request(), $esterilizacion);
    }

    public function destroy(Request $request, int $esterilizacion): JsonResponse
    {
        $this->authorizeAction('eliminar');

        return DB::transaction(function () use ($esterilizacion): JsonResponse {
            $registro = $this->registroBloqueado($esterilizacion);
            [$puedeEliminar, $mensaje] = $this->canDestroy($registro);
            if (! $puedeEliminar) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            $registro->delete();

            return response()->json(['success' => true, 'message' => 'Registro de esterilización eliminado correctamente.']);
        });
    }

    public function autorizar(Request $request, int $esterilizacion): RedirectResponse
    {
        $data = $request->validate(['firma_docente' => ['required', 'string', 'max:2000000', 'starts_with:data:image/png;base64,']]);
        DB::transaction(function () use ($request, $esterilizacion, $data): void {
            $registro = $this->registroBloqueado($esterilizacion);
            abort_unless($request->user()->hasAnyRole(['admin', 'subadmin', 'director']) || ($request->user()->hasAnyRole(['docente', 'jefe-catedra']) && (int) $registro->docente_id === $request->user()->id)
                || AccesoClinico::supervisaClinica($request->user(), $registro->clinica_id), 403, 'La firma corresponde al docente asignado o al jefe de cátedra de la clínica.');
            abort_unless($registro->habilitada, 422, 'El control está deshabilitado.');
            abort_if($registro->autorizado || $registro->fecha_recojo, 422, 'El control ya está autorizado o cerrado.');
            $registro->update($data + ['autorizado' => true, 'fecha_autorizacion' => now()]);
        });

        return back()->with('success', 'Material autorizado por el docente.');
    }

    public function movimiento(Request $request, int $esterilizacion): RedirectResponse
    {
        $this->autorizarServicio($request);
        $data = $request->validate(['accion' => ['required', Rule::in(['recibir', 'entregar'])]]);
        DB::transaction(function () use ($request, $esterilizacion, $data): void {
            $registro = $this->registroBloqueado($esterilizacion);
            $this->validarControlActivo($registro);
            if ($data['accion'] === 'recibir') {
                abort_if($registro->fecha_entrega, 422, 'La recepción ya se registró.');
                $material = $this->validarMaterialRecibido($request, $registro);
                $registro->update($material + ['fecha_entrega' => today(), 'hora_entrega' => now()->format('H:i:s'), 'recibido_por' => $request->user()->id]);
            } else {
                abort_unless($registro->fecha_entrega && ! $registro->fecha_recojo, 422, 'Registre primero la recepción; el recojo no puede repetirse.');
                $ciclo = $registro->ultimoCiclo;
                abort_unless($ciclo?->estado === 'liberado' && $ciclo->fecha_liberacion && $ciclo->controlesConformes(), 422, 'El material debe tener un ciclo revisado y liberado antes del recojo.');
                abort_unless($registro->estudiante?->habilitado, 422, 'El estudiante debe estar habilitado para recoger el material.');
                $request->validate(['confirmar_recojo' => ['required', 'accepted']]);
                $registro->update(['fecha_recojo' => today(), 'hora_recojo' => now()->format('H:i:s'),
                    'entregado_por' => $request->user()->id, 'recogido_por' => $registro->estudiante_id]);
            }
        });

        return back()->with('success', 'Movimiento registrado.');
    }

    public function preparar(Request $request, int $esterilizacion): RedirectResponse
    {
        $this->autorizarServicio($request);
        $data = $request->validate([
            'limpieza_confirmada' => ['required', 'accepted'],
            'inspeccion_confirmada' => ['required', 'accepted'],
            'empaque_confirmado' => ['required', 'accepted'],
            'observacion_preparacion' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $esterilizacion, $data): void {
            $registro = $this->registroBloqueado($esterilizacion);
            $this->validarControlActivo($registro);
            abort_unless($registro->fecha_entrega, 422, 'Registre primero la recepción del material.');
            $ultimoCiclo = $registro->ultimoCiclo;
            abort_if($registro->fecha_preparacion && (! $ultimoCiclo || $ultimoCiclo->estado !== 'rechazado'
                || count($registro->preparaciones ?? []) > $ultimoCiclo->version_preparacion), 422, 'La preparación ya está registrada.');
            $material = empty($registro->codigos_material) ? $this->validarMaterialRecibido($request, $registro) : [];
            $preparaciones = $registro->preparaciones ?? [];
            $preparaciones[] = ['fecha' => now()->toIso8601String(), 'usuario_id' => $request->user()->id,
                'responsable' => $request->user()->nombre_completo, 'observacion' => $data['observacion_preparacion'] ?? null];
            $registro->update($material + ['fecha_preparacion' => now(), 'preparado_por' => $request->user()->id,
                'observacion_preparacion' => $data['observacion_preparacion'] ?? null, 'preparaciones' => $preparaciones]);
        });

        return back()->with('success', 'Limpieza, inspección y empaque registrados.');
    }

    public function registrarCiclo(Request $request, int $esterilizacion): RedirectResponse
    {
        $this->autorizarServicio($request);
        $data = $request->validate([
            'equipo' => ['required', 'string', 'max:100'],
            'numero_carga' => ['required', 'string', 'max:100'],
            'protocolo' => ['required', 'string', 'max:200'],
            'inicio' => ['required', 'date', 'before_or_equal:now'],
            'fin' => ['required', 'date', 'after_or_equal:inicio', 'before_or_equal:now'],
            'temperatura' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:500'],
            'presion' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:1000'],
            'minutos_exposicion' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:10080'],
        ]);

        DB::transaction(function () use ($request, $esterilizacion, $data): void {
            $registro = $this->registroBloqueado($esterilizacion);
            $this->validarControlActivo($registro);
            abort_unless($registro->fecha_entrega && $registro->fecha_preparacion && filled($registro->codigos_material), 422, 'Registre la recepción y la preparación antes del ciclo.');
            $ultimoCiclo = $registro->ultimoCiclo;
            abort_if($ultimoCiclo && $ultimoCiclo->estado !== 'rechazado', 422, 'El control ya tiene un ciclo vigente.');
            abort_if($ultimoCiclo && count($registro->preparaciones ?? []) <= $ultimoCiclo->version_preparacion, 422, 'Registre una nueva preparación antes de reprocesar el material.');
            $inicio = Carbon::parse($data['inicio']);
            $fin = Carbon::parse($data['fin']);
            if ($inicio->lt($registro->fecha_preparacion)) {
                throw ValidationException::withMessages(['inicio' => 'El ciclo debe iniciar después de la preparación registrada.']);
            }
            if ((float) $data['minutos_exposicion'] > $inicio->diffInSeconds($fin) / 60) {
                throw ValidationException::withMessages(['minutos_exposicion' => 'La exposición no puede superar la duración del ciclo.']);
            }
            $registro->ciclos()->create($data + ['registrado_por' => $request->user()->id,
                'codigos_material' => $registro->codigos_material, 'estado' => 'registrado',
                'version_preparacion' => count($registro->preparaciones ?? []),
                'preparacion' => collect($registro->preparaciones ?? [])->last()]);
        });

        return back()->with('success', 'Ciclo registrado. Revise sus controles antes de liberar el material.');
    }

    public function revisarCiclo(Request $request, int $esterilizacion, int $ciclo): RedirectResponse
    {
        $this->autorizarServicio($request);
        $data = $request->validate([
            'control_fisico' => ['required', 'boolean'],
            'control_quimico' => ['required', 'boolean'],
            'control_biologico' => ['required', Rule::in(['pendiente', 'conforme', 'no_conforme', 'no_aplica'])],
            'justificacion_biologico' => ['nullable', 'required_if:control_biologico,no_aplica', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'required_if:control_fisico,0', 'required_if:control_quimico,0',
                'required_if:control_biologico,no_conforme', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $esterilizacion, $ciclo, $data): void {
            $registro = $this->registroBloqueado($esterilizacion);
            $this->validarControlActivo($registro);
            $cicloActual = $this->cicloActual($registro, $ciclo);
            abort_unless(in_array($cicloActual->estado, ['registrado', 'pendiente_resultados'], true), 422, 'La revisión final del ciclo se conserva; un ciclo rechazado requiere reprocesamiento.');
            $data['control_fisico'] = $request->boolean('control_fisico');
            $data['control_quimico'] = $request->boolean('control_quimico');
            $rechazado = ! $data['control_fisico'] || ! $data['control_quimico'] || $data['control_biologico'] === 'no_conforme';
            if ($rechazado && blank($data['observaciones'] ?? null)) {
                throw ValidationException::withMessages(['observaciones' => 'Describa el motivo de los controles no conformes.']);
            }
            $estado = $rechazado ? 'rechazado' : ($data['control_biologico'] === 'pendiente' ? 'pendiente_resultados' : 'revisado');
            $revisiones = $cicloActual->revisiones ?? [];
            $revisiones[] = $data + ['fecha' => now()->toIso8601String(), 'usuario_id' => $request->user()->id,
                'responsable' => $request->user()->nombre_completo, 'estado' => $estado];
            $cicloActual->update($data + ['estado' => $estado, 'revisiones' => $revisiones,
                'revisado_por' => $request->user()->id, 'fecha_revision' => now()]);
        });

        return back()->with('success', 'Revisión registrada. Los resultados pendientes o no conformes bloquean la liberación.');
    }

    public function liberar(Request $request, int $esterilizacion, int $ciclo): RedirectResponse
    {
        $this->autorizarServicio($request);
        $request->validate(['empaque_integro' => ['required', 'accepted'], 'confirmar_liberacion' => ['required', 'accepted']]);
        DB::transaction(function () use ($request, $esterilizacion, $ciclo): void {
            $registro = $this->registroBloqueado($esterilizacion);
            $this->validarControlActivo($registro);
            $cicloActual = $this->cicloActual($registro, $ciclo);
            abort_unless($cicloActual->estado === 'revisado' && $cicloActual->fecha_revision && $cicloActual->controlesConformes(), 422, 'El ciclo debe tener controles conformes y una revisión completa.');
            $cicloActual->update(['estado' => 'liberado', 'fecha_liberacion' => now(), 'liberado_por' => $request->user()->id]);
        });

        return back()->with('success', 'Material liberado y disponible para recojo.');
    }

    private function autorizarServicio(Request $request): void
    {
        abort_unless($request->user()->can('esterilizaciones.gestionar')
            && $request->user()->hasAnyRole(['admin', 'subadmin', 'director', 'esterilizacion']), 403);
    }

    private function registroBloqueado(int $id): Esterilizacion
    {
        return $this->buildIndexQuery(request())->lockForUpdate()->findOrFail($id);
    }

    private function validarControlActivo(Esterilizacion $registro): void
    {
        abort_unless($registro->habilitada, 422, 'El control está deshabilitado.');
        abort_unless($registro->autorizado && $registro->firma_docente, 422, 'Falta autorización y firma docente.');
        abort_if($registro->fecha_recojo, 422, 'El material ya fue recogido; el control está cerrado.');
    }

    private function cicloActual(Esterilizacion $registro, int $id): EsterilizacionCiclo
    {
        $ciclo = $registro->ciclos()->lockForUpdate()->findOrFail($id);
        abort_unless($registro->ultimoCiclo?->id === $ciclo->id, 422, 'Solo puede operar sobre el último ciclo del control.');

        return $ciclo;
    }

    /** @return array{sobres_recibidos: int, paquetes_recibidos: int, observacion_recepcion: ?string, codigos_material: list<string>} */
    private function validarMaterialRecibido(Request $request, Esterilizacion $registro): array
    {
        $data = $request->validate([
            'sobres_recibidos' => ['required', 'integer', 'min:0', 'max:'.min(1000, $registro->cantidad_sobres)],
            'paquetes_recibidos' => ['required', 'integer', 'min:0', 'max:'.min(1000, $registro->cantidad_paquetes)],
            'observacion_recepcion' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['sobres_recibidos'] + $data['paquetes_recibidos'] < 1) {
            throw ValidationException::withMessages(['sobres_recibidos' => 'Reciba al menos un sobre o paquete.']);
        }
        $codigos = [];
        foreach (['S' => (int) $data['sobres_recibidos'], 'P' => (int) $data['paquetes_recibidos']] as $tipo => $cantidad) {
            for ($numero = 1; $numero <= $cantidad; $numero++) {
                $codigos[] = 'EST-'.$registro->id.'-'.$tipo.$numero;
            }
        }

        return ['sobres_recibidos' => (int) $data['sobres_recibidos'], 'paquetes_recibidos' => (int) $data['paquetes_recibidos'],
            'observacion_recepcion' => $data['observacion_recepcion'] ?? null, 'codigos_material' => $codigos];
    }

    protected function canDestroy($model): array
    {
        $model = Esterilizacion::findOrFail($model->id);
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
