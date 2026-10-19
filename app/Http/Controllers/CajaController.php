<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\SolicitudRadiologica;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use App\Services\NotificacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Caja::class;

    protected string $viewPrefix = 'cajas';

    protected string $resourceName = 'Caja';

    protected string $pluralName = 'Cajas';

    protected string $permissionPrefix = 'cajas';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request): Builder
    {
        return Caja::query()
            ->with([
                'tratamiento:id,nombre,costo',
                'clinica:id,especialidad,modulo',
                'remision:id,id',
                'usuario:id,nombre_completo',
                'tratamientoRealizado.historiaClinica.estudiante',
                'solicitudRadiologica.historiaClinica.estudiante',
                'solicitudRadiologica.historiaClinica.admision',
            ])
            ->withSum('recibosAtencion as cancelado_acumulado', 'cancelado')
            ->withSum('recibosRadiologicos as pago_radiologico_acumulado', 'cancelado')
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
            'atencionesAutorizadas' => TratamientoRealizado::whereIn('estado', ['autorizado', 'en_proceso'])->whereNotNull('autorizado_por')->with(['tratamiento', 'historiaClinica.paciente'])->orderByDesc('id')->get(),
            'solicitudesRadiologicas' => SolicitudRadiologica::query()
                ->where('estado', SolicitudRadiologica::AUTORIZADA)
                ->whereDoesntHave('estudio')
                ->whereRaw('COALESCE((SELECT SUM(cajas.cancelado) FROM cajas WHERE cajas.solicitud_radiologica_id = solicitudes_radiologicas.id AND cajas.habilitada = 1), 0) < solicitudes_radiologicas.costo_autorizado')
                ->with(['historiaClinica.estudiante', 'paciente', 'clinica', 'recibos' => fn (HasMany $query) => $query->where('habilitada', true)])
                ->orderBy('created_at')
                ->limit(100)
                ->get(),
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'catalogoTratamientos' => Tratamiento::query()->orderBy('nombre')->get(['id', 'nombre', 'clinica_id', 'costo', 'precio_asignado']),
            'tiposPago' => Caja::query()
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
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'tratamientos' => Tratamiento::orderBy('nombre')
                ->get(['id', 'nombre', 'costo', 'clinica_id']),
            'atencion' => $model->tratamientoRealizado?->load(['historiaClinica.estudiante', 'historiaClinica.paciente']),
            'cancelado_anterior' => $model->tratamiento_realizado_id
                ? Caja::query()->where('tratamiento_realizado_id', $model->tratamiento_realizado_id)->where('habilitada', true)->where('id', '!=', $model->id)->sum('cancelado')
                : 0,
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'tratamiento_realizado_id' => ['required', 'integer', 'exists:tratamientos_realizados,id'],
            'ficha' => ['nullable', 'integer', 'min:1'],
            'monto' => ['nullable', 'integer', 'min:0'],
            'costo_total' => ['nullable', 'numeric', 'min:0'],
            'cancelado' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'habilitada' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'ci' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::in(['tratamiento', 'radiologia'])],
            'tipo_pago' => ['nullable', 'string', 'max:100'],
            'paciente_nombre_completo' => ['nullable', 'string', 'max:100'],
            'grado' => ['nullable', 'string', 'max:100'],
            'tratamiento_id' => ['nullable', 'integer', 'exists:tratamientos,id'],
            'clinica_id' => ['nullable', 'integer', 'exists:clinicas,id'],
            'remision_id' => ['nullable', 'integer', 'exists:remisiones,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'costo_total.required' => 'El costo total es obligatorio.',
            'costo_total.numeric' => 'El costo debe ser un número.',
            'tratamiento_id.required' => 'Debe seleccionar un tratamiento.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
            'usuario_id.required' => 'Debe asignar un responsable de caja.',
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
        $data = $this->vincularAtencion($data);
        $data = $this->normalizarStrings($data);
        $data['recibo'] = Caja::siguienteNumeroRecibo()
            ?? throw ValidationException::withMessages(['recibo' => 'Se alcanzó el límite de numeración de recibos. Contacte a un administrador.']);
        $data['habilitada'] = request()->boolean('habilitada', true) ? 1 : 0;
        $data['cancelado'] = $data['cancelado'] ?? 0;
        $data['tipo'] = ($data['tipo'] ?? '') !== '' ? $data['tipo'] : 'tratamiento';
        $data['monto'] = $data['monto'] ?? 0;
        $data['ci'] = $data['ci'] ?? 0;
        $data['usuario_id'] = $data['usuario_id'] ?? request()->user()?->getAuthIdentifier();

        return $data;
    }

    private function vincularAtencion(array $data): array
    {
        $caso = TratamientoRealizado::with(['historiaClinica.paciente', 'tratamiento'])->lockForUpdate()->findOrFail($data['tratamiento_realizado_id']);
        if (! $caso->estaAutorizado() || ! in_array($caso->estado, ['autorizado', 'en_proceso'], true)) {
            throw ValidationException::withMessages(['tratamiento_realizado_id' => 'El docente debe autorizar el tratamiento antes de emitir el recibo.']);
        }
        if (! $caso->tratamiento?->precio_asignado || $caso->costo_cobrado === null) {
            throw ValidationException::withMessages(['tratamiento_realizado_id' => 'Caja debe configurar el precio del concepto antes de emitir el recibo.']);
        }
        if (isset($data['ficha']) && (int) $data['ficha'] !== (int) $caso->historiaClinica->admision_id) {
            throw ValidationException::withMessages(['ficha' => 'La atención no corresponde al número de ficha buscado.']);
        }
        foreach (['clinica_id', 'tratamiento_id'] as $campo) {
            if (isset($data[$campo]) && (int) $data[$campo] !== (int) $caso->{$campo}) {
                throw ValidationException::withMessages([$campo => 'La selección no corresponde a la atención del paciente.']);
            }
        }
        unset($data['ficha']);
        $data['tratamiento_id'] = $caso->tratamiento_id;
        $data['clinica_id'] = $caso->clinica_id;
        $data['paciente_id'] = $caso->historiaClinica->paciente_id;
        $data['periodo_id'] = $caso->historiaClinica->periodo_id;
        $data['remision_id'] = $caso->historiaClinica->remision_id;
        $data['paciente_nombre_completo'] = mb_substr($caso->historiaClinica->paciente->nombre_completo, 0, 100);
        $data['costo_total'] = $caso->costo_cobrado;
        $data['usuario_id'] = request()->user()->id;
        $anterior = Caja::query()->where('tratamiento_realizado_id', $caso->id)->where('habilitada', true)->sum('cancelado');
        if (request()->boolean('habilitada', true) && $this->centavos((float) $anterior) + $this->centavos((float) $data['cancelado']) > $this->centavos((float) $data['costo_total'])) {
            throw ValidationException::withMessages(['cancelado' => 'El pago supera el saldo pendiente del tratamiento.']);
        }

        return $data;
    }

    protected function afterStore($model): void
    {
        $model->detalles()->create(['tratamiento_realizado_id' => $model->tratamiento_realizado_id, 'concepto' => $model->tratamiento->nombre, 'cantidad' => 1, 'precio_unitario' => $model->costo_total, 'monto' => $model->costo_total, 'fecha' => today()]);
        $this->notificarPago($model);
    }

    protected function afterUpdate($model): void
    {
        $this->notificarPago($model);
    }

    private function notificarPago(Caja $recibo): void
    {
        $atencion = $recibo->tratamientoRealizado()->with('historiaClinica')->first();
        if (! $atencion) {
            return;
        }
        $pago = $atencion->resumenPago();
        foreach (array_unique([$atencion->historiaClinica->estudiante_id, $atencion->historiaClinica->docente_id]) as $usuarioId) {
            NotificacionService::a($usuarioId, 'pago_registrado', 'Pago actualizado del tratamiento #'.$atencion->id,
                'Estado: '.$pago['estado'].'. Pagado Bs '.number_format($pago['pagado'], 2).'; saldo Bs '.number_format($pago['saldo'], 2).'.', route('historias.show', $atencion->historia_clinica_id));
        }
        if ($pago['estado'] === 'pagado' && $recibo->tipo === 'radiologia') {
            NotificacionService::aRol('radiologia', 'pago_radiologia', 'Estudio radiológico pagado',
                'Recibo #'.$recibo->recibo.' para la ficha #'.$atencion->historiaClinica->admision_id.'.', route('radiologia.index'));
        }
    }

    protected function beforeUpdate($model, array $data): array
    {
        if ($model->solicitud_radiologica_id !== null) {
            throw ValidationException::withMessages(['caja' => 'Los recibos de estudios radiológicos se conservan como registro de pago y no se editan desde esta pantalla.']);
        }
        if ($model->radiologias()->exists() && ((isset($data['tipo']) && $data['tipo'] !== $model->tipo)
            || ! request()->boolean('habilitada') || (float) $data['cancelado'] < (float) $model->cancelado)) {
            throw ValidationException::withMessages(['cancelado' => 'El recibo respalda un estudio radiológico y debe conservarse.']);
        }
        if ((int) $data['tratamiento_realizado_id'] !== (int) $model->tratamiento_realizado_id) {
            throw ValidationException::withMessages(['tratamiento_realizado_id' => 'El recibo no puede reasignarse a otro tratamiento.']);
        }
        TratamientoRealizado::query()->lockForUpdate()->findOrFail($model->tratamiento_realizado_id);
        $anterior = Caja::query()->where('tratamiento_realizado_id', $model->tratamiento_realizado_id)->where('habilitada', true)->where('id', '!=', $model->id)->sum('cancelado');
        if (request()->boolean('habilitada') && $this->centavos((float) $anterior) + $this->centavos((float) $data['cancelado']) > $this->centavos((float) $model->costo_total)) {
            throw ValidationException::withMessages(['cancelado' => 'El pago supera el saldo pendiente del tratamiento.']);
        }
        unset($data['ficha']);
        foreach (['tratamiento_id', 'clinica_id', 'remision_id', 'usuario_id', 'costo_total', 'paciente_nombre_completo'] as $campo) {
            unset($data[$campo]);
        }
        $data = $this->normalizarStrings($data);
        $data['habilitada'] = request()->boolean('habilitada') ? 1 : 0;

        return $data;
    }

    private function centavos(float $monto): int
    {
        return (int) round($monto * 100);
    }

    public function buscarPacientes(Request $request): JsonResponse
    {
        $this->authorizeLookup();
        $data = $request->validate([
            'buscar' => ['required', 'string', 'min:2', 'max:100'],
            'clinica_id' => ['nullable', 'integer', 'exists:clinicas,id'],
        ], ['buscar.min' => 'Ingrese al menos dos caracteres del CI o nombre del estudiante o paciente.']);
        $terminos = preg_split('/\s+/u', trim($data['buscar']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $coincideEstudiante = static function (Builder|Relation $query) use ($terminos): void {
            foreach ($terminos as $termino) {
                $query->where(fn (Builder $query) => $query->where('ci', 'like', '%'.$termino.'%')
                    ->orWhere('nombre_completo', 'like', '%'.$termino.'%'));
            }
        };
        $coincidePaciente = static function (Builder|Relation $query) use ($terminos): void {
            foreach ($terminos as $termino) {
                $query->where(function (Builder $query) use ($termino): void {
                    $query->where('ci', 'like', "%{$termino}%")
                        ->orWhere('nombre', 'like', "%{$termino}%")
                        ->orWhere('apellido_paterno', 'like', "%{$termino}%")
                        ->orWhere('apellido_materno', 'like', "%{$termino}%");
                });
            }
        };
        $fichas = Admision::query()
            ->whereHas('historiasClinicas', fn (Builder $query) => $query
                ->when(isset($data['clinica_id']), fn (Builder $query) => $query->where('clinica_id', $data['clinica_id'])))
            ->where(function (Builder $ficha) use ($coincideEstudiante, $coincidePaciente, $data): void {
                $ficha->whereHas('historiasClinicas.estudiante', $coincideEstudiante)
                    ->orWhereHas('paciente', $coincidePaciente);
                if (! isset($data['clinica_id'])) {
                    $ficha->orWhereHas('estudiante', $coincideEstudiante);
                }
            })
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'estudiante:id,ci,nombre_completo',
                'historiasClinicas' => fn (Builder|Relation $query) => $query
                    ->when(isset($data['clinica_id']), fn (Builder $query) => $query->where('clinica_id', $data['clinica_id']))
                    ->with('estudiante:id,ci,nombre_completo'),
            ])
            ->orderByDesc('id')->limit(21)->get(['id', 'paciente_id', 'estudiante_id', 'fecha_admision']);

        return response()->json([
            'fichas' => $fichas->take(20)->map(function (Admision $admision) use ($data): array {
                $estudiantes = $admision->historiasClinicas->pluck('estudiante');
                if (! isset($data['clinica_id'])) {
                    $estudiantes->push($admision->estudiante);
                }

                return [
                    'ficha' => $admision->id,
                    'paciente' => $admision->paciente->nombre_completo,
                    'ci' => (string) $admision->paciente->ci,
                    'estudiantes' => $estudiantes->filter()->unique('id')->map(fn (Usuario $estudiante): array => [
                        'nombre' => $estudiante->nombre_completo,
                        'ci' => (string) $estudiante->ci,
                    ])->values(),
                    'fecha' => $admision->fecha_admision?->format('d/m/Y'),
                ];
            })->values(),
            'hay_mas' => $fichas->count() > 20,
        ]);
    }

    public function buscarFicha(Request $request): JsonResponse
    {
        $this->authorizeLookup();
        $data = $request->validate(['ficha' => ['required', 'integer', 'min:1'], 'clinica_id' => ['nullable', 'integer', 'exists:clinicas,id']]);
        $historias = HistoriaClinica::query()->where('admision_id', $data['ficha'])
            ->when($request->filled('clinica_id'), fn (Builder $query) => $query->where('clinica_id', $data['clinica_id']))
            ->with(['paciente', 'estudiante', 'clinica', 'tratamientosRealizados' => fn ($query) => $query->whereIn('estado', ['autorizado', 'en_proceso'])->whereNotNull('autorizado_por'), 'tratamientosRealizados.tratamiento'])
            ->orderByDesc('id')->get();
        abort_if($historias->isEmpty(), 404, 'No hay pacientes designados con esta ficha en la clínica seleccionada.');
        $estudianteIds = $historias->pluck('estudiante_id')->unique();
        $clinicaIds = $historias->pluck('clinica_id')->unique();
        $atenciones = $historias->flatMap->tratamientosRealizados;
        $pagos = Caja::query()->whereIn('tratamiento_realizado_id', $atenciones->pluck('id'))->where('habilitada', true)
            ->select('tratamiento_realizado_id')->selectRaw('SUM(cancelado) as total')->groupBy('tratamiento_realizado_id')->pluck('total', 'tratamiento_realizado_id');

        return response()->json([
            'ficha' => (int) $data['ficha'],
            'paciente' => $historias->first()->paciente->nombre_completo,
            'estudiante' => $estudianteIds->count() === 1 ? $historias->first()->estudiante?->nombre_completo : null,
            'clinica_id' => $clinicaIds->count() === 1 ? (int) $clinicaIds->first() : null,
            'atenciones' => $historias->flatMap(function (HistoriaClinica $historia) use ($pagos): array {
                return $historia->tratamientosRealizados->map(fn (TratamientoRealizado $atencion): array => [
                    'id' => $atencion->id, 'clinica_id' => $historia->clinica_id, 'clinica' => $historia->clinica->especialidad,
                    'tratamiento_id' => $atencion->tratamiento_id, 'tratamiento' => $atencion->tratamiento->nombre,
                    'estudiante' => $historia->estudiante->nombre_completo, 'paciente' => $historia->paciente->nombre_completo,
                    'costo' => $atencion->costo_cobrado, 'cancelado_anterior' => $pagos->get($atencion->id, '0.00'),
                    'saldo' => round((float) $atencion->costo_cobrado - (float) $pagos->get($atencion->id, 0), 2),
                ])->all();
            })->values(),
        ]);
    }

    public function recibo(Caja $caja): View
    {
        $this->authorizeReceipt();

        $caja->load([
            'clinica:id,especialidad,modulo',
            'detalles:id,caja_id,concepto,cantidad,precio_unitario,monto',
            'solicitudRadiologica.historiaClinica.estudiante:id,ci,nombre_completo',
            'solicitudRadiologica.historiaClinica.paciente:id,ci,nombre,apellido_paterno,apellido_materno',
            'tratamiento:id,nombre',
            'tratamientoRealizado.historiaClinica.estudiante:id,ci,nombre_completo',
            'tratamientoRealizado.historiaClinica.paciente:id,ci,nombre,apellido_paterno,apellido_materno',
            'usuario:id,nombre_completo',
        ]);

        $historia = $caja->solicitudRadiologica?->historiaClinica
            ?? $caja->tratamientoRealizado?->historiaClinica;
        $concepto = $caja->solicitudRadiologica?->descripcion
            ?: ($caja->tratamiento?->nombre
                ?: ($caja->detalles->first()?->concepto ?: $caja->descripcion));
        $recibosRelacionados = Caja::query()->where('habilitada', true);

        if ($caja->solicitud_radiologica_id !== null) {
            $recibosRelacionados->where('solicitud_radiologica_id', $caja->solicitud_radiologica_id);
        } elseif ($caja->tratamiento_realizado_id !== null) {
            $recibosRelacionados->where('tratamiento_realizado_id', $caja->tratamiento_realizado_id);
        } else {
            $recibosRelacionados->whereKey($caja->id);
        }

        $totalPagado = (float) $recibosRelacionados->sum('cancelado');

        return view('cajas.recibo', [
            'caja' => $caja,
            'concepto' => $concepto ?: 'Servicio odontológico',
            'estudiante' => $historia?->estudiante,
            'ficha' => $historia?->admision_id,
            'paciente' => $historia?->paciente,
            'saldo' => max(0, (float) $caja->costo_total - $totalPagado),
            'totalPagado' => $totalPagado,
        ]);
    }

    private function authorizeLookup(): void
    {
        $user = auth()->user() ?? Usuario::find(session('user_id'));

        abort_unless(
            $user?->canAny(['cajas.ver', 'cajas.crear', 'cajas.gestionar']) || $user?->hasRole('admin'),
            403,
            'No tienes permiso para consultar pacientes de caja.'
        );
    }

    private function authorizeReceipt(): void
    {
        $user = auth()->user() ?? Usuario::find(session('user_id'));

        abort_unless(
            $user?->canAny(['cajas.ver', 'cajas.crear', 'cajas.gestionar']) || $user?->hasRole('admin'),
            403,
            'No tienes permiso para imprimir recibos de caja.'
        );
    }

    protected function canDestroy($model): array
    {
        if ($model->solicitud_radiologica_id !== null) {
            return [false, 'El recibo forma parte del historial de pago de un estudio radiológico y debe conservarse.'];
        }
        if ($model->detalles()->count() > 0) {
            $count = $model->detalles()->count();

            return [false, "No se puede eliminar: tiene {$count} detalle(s) asociado(s)."];
        }

        return [true, ''];
    }
}
