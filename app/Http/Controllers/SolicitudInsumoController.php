<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Clinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\Notificacion;
use App\Models\TratamientoRealizado;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudInsumoController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = InsumoSolicitado::class;
    protected string $viewPrefix       = 'solicitudes-insumos';
    protected string $resourceName     = 'Solicitud';
    protected string $pluralName       = 'Solicitudes';
    protected string $permissionPrefix = 'insumos';   // reutiliza permisos de insumos
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        $user = auth()->user() ?? \App\Models\Usuario::find(session('user_id'));

        $query = InsumoSolicitado::query()
            ->with([
                'estudiante:id,nombre_completo',
                'docente:id,nombre_completo',
                'clinica:id,especialidad',
                'detalles.insumo:id,nombre,cantidad',
            ])
            ->withCount('detalles')
            ->orderByDesc('id');

        // Si el usuario es estudiante, solo ve SUS solicitudes
        if ($user?->hasRole('estudiante') || $user?->hasRole('interno')) {
            $query->where('usuario_id', $user->id);
        }
        // Si es docente, ve las solicitudes de su clínica
        elseif ($user?->hasRole('docente') || $user?->hasRole('jefe-catedra')) {
            // Sin filtro específico, ve todas las de su clínica
        }

        return $query;
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->whereHas('estudiante', fn($e) => $e->where('nombre_completo', 'like', "%{$buscar}%"))
                    ->orWhere('pieza', 'like', "%{$buscar}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad']),
            'stats'    => [
                'pendientes'  => InsumoSolicitado::where('estado', 'pendiente')->count(),
                'autorizadas' => InsumoSolicitado::where('estado', 'autorizado')->count(),
                'entregadas'  => InsumoSolicitado::where('estado', 'entregado')->count(),
                'rechazadas'  => InsumoSolicitado::where('estado', 'rechazado')->count(),
            ],
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'detalles' => $model->detalles->map(fn($d) => [
                'id'                  => $d->id,
                'insumo_id'           => $d->insumo_id,
                'insumo_nombre'       => $d->insumo?->nombre,
                'cantidad_solicitada' => (float) $d->cantidad_solicitada,
                'cantidad_entregada'  => (float) $d->cantidad_entregada,
                'stock_actual'        => (float) ($d->insumo?->cantidad_numerica ?? 0),
                'unidad'              => $d->insumo?->cantidad,
            ]),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'clinica_id'              => ['required', 'integer', 'exists:clinicas,id'],
            'insumo_tratamiento_id'   => ['required', 'integer', 'exists:insumos_tratamiento,id'],
            'pieza'                   => ['nullable', 'integer', 'min:0', 'max:99'],
            'insumos'                 => ['nullable', 'string'],
            'tratamiento_realizado_id' => ['nullable', 'integer', 'exists:tratamientos_realizados,id'],
            'remision_id'             => ['nullable', 'integer', 'exists:remisiones,id'],

            // Detalles
            'detalles'                     => ['required', 'array', 'min:1'],
            'detalles.*.insumo_id'         => ['required', 'integer', 'exists:insumos,id'],
            'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'clinica_id.required'            => 'Debe seleccionar una clínica.',
            'insumo_tratamiento_id.required' => 'Debe seleccionar un tipo de insumo.',
            'detalles.required'              => 'Debe agregar al menos un insumo.',
            'detalles.min'                   => 'Debe agregar al menos un insumo.',
            'detalles.*.insumo_id.required'  => 'El insumo es obligatorio en cada línea.',
            'detalles.*.cantidad_solicitada.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad_solicitada.min'      => 'La cantidad debe ser mayor a 0.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    protected function beforeStore(array $data): array
    {
        $user = auth()->user() ?? \App\Models\Usuario::find(session('user_id'));

        $data['usuario_id']  = $user->id;
        $data['estado']      = InsumoSolicitado::ESTADO_PENDIENTE;
        $data['autorizado']  = 0;
        $data['docente_autoriza'] = 0;
        $data['pieza']       = $data['pieza'] ?? 0;

        return $data;
    }

    protected function afterStore($model): void
    {
        $this->guardarDetalles($model, request()->input('detalles', []));

        // Avisar a los docentes que deben autorizar la solicitud
        NotificacionService::aRol(
            ['docente', 'jefe-catedra'],
            Notificacion::TIPO_SOLICITUD_CREADA,
            'Nueva solicitud de insumos #' . $model->id,
            $this->resumenSolicitud($model),
            $this->urlSolicitud(),
            $model->usuario_id
        );
    }

    /**
     * PUT /solicitudes-insumos/{id}
     * Sólo se pueden editar solicitudes pendientes o rechazadas.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $solicitud = InsumoSolicitado::findOrFail($id);

        if (! in_array($solicitud->estado, [
            InsumoSolicitado::ESTADO_PENDIENTE,
            InsumoSolicitado::ESTADO_RECHAZADO,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar una solicitud autorizada, entregada o cancelada.',
            ], 422);
        }

        return parent::update($request, $id);
    }

    protected function beforeUpdate($model, array $data): array
    {
        // No permitir editar solicitudes ya autorizadas/entregadas
        if (! in_array($model->estado, [
            InsumoSolicitado::ESTADO_PENDIENTE,
            InsumoSolicitado::ESTADO_RECHAZADO,
        ], true)) {
            abort(403, 'No se puede editar una solicitud autorizada o entregada.');
        }

        unset($data['detalles']);

        return $data;
    }

    protected function afterUpdate($model): void
    {
        // Actualizar detalles si vienen
        $detalles = request()->input('detalles', []);
        if (! empty($detalles)) {
            $model->detalles()->delete();
            $this->guardarDetalles($model, $detalles);
        }
    }

    protected function canDestroy($model): array
    {
        if ($model->estado === 'entregado') {
            return [false, 'No se puede eliminar una solicitud ya entregada.'];
        }
        return [true, ''];
    }

    /* ============================================================
       ACCIONES DE FLUJO
       ============================================================ */

    /**
     * POST /solicitudes-insumos/{id}/autorizar
     * El docente autoriza la solicitud.
     */
    public function autorizar(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $user = auth()->user() ?? \App\Models\Usuario::find(session('user_id'));

        $solicitud = InsumoSolicitado::findOrFail($id);

        if ($solicitud->estado !== InsumoSolicitado::ESTADO_PENDIENTE) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden autorizar solicitudes pendientes.',
            ], 422);
        }

        $solicitud->update([
            'estado'              => InsumoSolicitado::ESTADO_AUTORIZADO,
            'autorizado'          => 1,
            'docente_id'          => $user->id,
            'docente_autoriza'    => 1,
            'fecha_autorizacion'  => now(),
        ]);

        // Avisar al solicitante y al encargado de insumos
        NotificacionService::a(
            $solicitud->usuario_id,
            Notificacion::TIPO_SOLICITUD_AUTORIZADA,
            'Tu solicitud #' . $solicitud->id . ' fue autorizada',
            'Autorizó: ' . ($user->nombre_completo ?? 'docente') . '. Ya puede entregarse en insumos.',
            $this->urlSolicitud()
        );

        NotificacionService::aRol(
            ['insumos', 'admin', 'subadmin'],
            Notificacion::TIPO_SOLICITUD_AUTORIZADA,
            'Solicitud #' . $solicitud->id . ' lista para entregar',
            $this->resumenSolicitud($solicitud),
            $this->urlSolicitud(),
            $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitud autorizada correctamente.',
        ]);
    }

    /**
     * POST /solicitudes-insumos/{id}/rechazar
     */
    public function rechazar(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $request->validate([
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);

        $solicitud = InsumoSolicitado::findOrFail($id);

        if ($solicitud->estado !== InsumoSolicitado::ESTADO_PENDIENTE) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden rechazar solicitudes pendientes.',
            ], 422);
        }

        $solicitud->update([
            'estado' => InsumoSolicitado::ESTADO_RECHAZADO,
            'insumos' => ($solicitud->insumos ?? '') . "\n[RECHAZADO] " . ($request->motivo ?? 'Sin motivo'),
        ]);

        NotificacionService::a(
            $solicitud->usuario_id,
            Notificacion::TIPO_SOLICITUD_RECHAZADA,
            'Tu solicitud #' . $solicitud->id . ' fue rechazada',
            'Motivo: ' . ($request->motivo ?? 'sin especificar'),
            $this->urlSolicitud()
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitud rechazada.',
        ]);
    }

    /**
     * POST /solicitudes-insumos/{id}/entregar
     * El encargado de insumos confirma la entrega y descuenta stock.
     */
    public function entregar(Request $request, $id)
    {
        $this->authorizeAction('gestionar');

        $request->validate([
            'detalles'                        => ['required', 'array'],
            'detalles.*.detalle_id'           => ['required', 'integer', 'exists:insumos_solicitados_detalle,id'],
            'detalles.*.cantidad_entregada'   => ['required', 'numeric', 'min:0'],
        ], [
            'detalles.required'                     => 'Debe indicar los insumos a entregar.',
            'detalles.*.detalle_id.required'        => 'Falta el identificador del detalle.',
            'detalles.*.detalle_id.exists'          => 'El detalle indicado no existe.',
            'detalles.*.cantidad_entregada.required' => 'La cantidad entregada es obligatoria.',
            'detalles.*.cantidad_entregada.min'     => 'La cantidad entregada no puede ser negativa.',
        ]);

        $solicitud = InsumoSolicitado::findOrFail($id);

        if ($solicitud->estado !== InsumoSolicitado::ESTADO_AUTORIZADO) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden entregar solicitudes autorizadas.',
            ], 422);
        }

        // Consolidar posibles detalle_id repetidos en el payload
        $cantidades = [];
        foreach ($request->detalles as $d) {
            $detalleId = (int) $d['detalle_id'];
            $cantidades[$detalleId] = ($cantidades[$detalleId] ?? 0) + (float) $d['cantidad_entregada'];
        }

        $cantidades = array_filter($cantidades, static fn (float $c): bool => $c > 0);

        if ($cantidades === []) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar al menos una cantidad a entregar.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Verificar stock y que no se entregue más de lo solicitado
            foreach ($cantidades as $detalleId => $cantidad) {
                $detalle = InsumoSolicitadoDetalle::where('insumo_solicitado_id', $solicitud->id)
                    ->findOrFail($detalleId);

                $insumo  = Insumo::findOrFail($detalle->insumo_id);
                $tope    = (float) $detalle->cantidad_solicitada - (float) $detalle->cantidad_entregada;

                if ($cantidad > $tope) {
                    throw new \Exception(
                        "La cantidad de '{$insumo->nombre}' supera lo pendiente ({$tope})."
                    );
                }

                if ($insumo->cantidad_numerica < $cantidad) {
                    throw new \Exception(
                        "Stock insuficiente de '{$insumo->nombre}'. Disponible: {$insumo->cantidad_numerica}"
                    );
                }
            }

            // Descontar stock y actualizar detalles
            foreach ($cantidades as $detalleId => $cantidad) {
                $detalle = InsumoSolicitadoDetalle::where('insumo_solicitado_id', $solicitud->id)
                    ->findOrFail($detalleId);

                $insumo = Insumo::findOrFail($detalle->insumo_id);

                $insumo->decrement('cantidad_numerica', $cantidad);

                $detalle->update([
                    'cantidad_entregada' => (float) $detalle->cantidad_entregada + $cantidad,
                    'fecha'              => now(),
                ]);
            }

            $solicitud->update([
                'estado' => InsumoSolicitado::ESTADO_ENTREGADO,
            ]);

            DB::commit();

            // Avisar al solicitante y revisar stock de lo entregado
            NotificacionService::a(
                $solicitud->usuario_id,
                Notificacion::TIPO_SOLICITUD_ENTREGADA,
                'Tus insumos de la solicitud #' . $solicitud->id . ' fueron entregados',
                'Revisa el detalle de la entrega en el módulo de solicitudes.',
                $this->urlSolicitud()
            );

            foreach ($cantidades as $detalleId => $cantidad) {
                $detalle = InsumoSolicitadoDetalle::with('insumo')->find($detalleId);
                $insumo  = $detalle?->insumo;

                if ($insumo && (float) $insumo->cantidad_numerica <= (float) $insumo->stock_minimo) {
                    NotificacionService::stockBajo(
                        (string) $insumo->nombre,
                        (float) $insumo->cantidad_numerica,
                        (string) $insumo->cantidad,
                        \Illuminate\Support\Facades\Route::has('insumos.index') ? route('insumos.index') : null
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Insumos entregados correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    /**
     * Resumen legible de una solicitud para las notificaciones.
     */
    private function resumenSolicitud(InsumoSolicitado $solicitud): string
    {
        $partes = [];

        if ($solicitud->estudiante?->nombre_completo) {
            $partes[] = 'Solicita: ' . $solicitud->estudiante->nombre_completo;
        }

        if ($solicitud->clinica?->especialidad) {
            $partes[] = $solicitud->clinica->especialidad;
        }

        $lineas = $solicitud->detalles()->count();
        if ($lineas > 0) {
            $partes[] = $lineas . ' insumo(s)';
        }

        return implode(' · ', $partes);
    }

    /**
     * URL del módulo de solicitudes (o de la bandeja si no existiera).
     */
    private function urlSolicitud(): ?string
    {
        return \Illuminate\Support\Facades\Route::has('solicitudes-insumos.index')
            ? route('solicitudes-insumos.index')
            : null;
    }

    /**
     * GET /solicitudes-insumos/buscar-tratamientos?q=&clinica_id=
     * Autocomplete de tratamientos realizados (opcional en la solicitud).
     */
    public function buscarTratamientos(Request $request)
    {
        $this->authorizeAction('ver');

        $q        = trim((string) $request->input('q'));
        $clinica  = $request->input('clinica_id');

        return response()->json(
            TratamientoRealizado::query()
                ->with(['tratamiento:id,nombre', 'historiaClinica:id,paciente_id'])
                ->when($clinica, fn ($query) => $query->where('clinica_id', $clinica))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->whereHas('tratamiento', fn ($t) => $t->where('nombre', 'like', "%{$q}%"))
                            ->orWhere('pieza_dental', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (TratamientoRealizado $t) => [
                    'id'     => $t->id,
                    'texto'  => trim(($t->tratamiento?->nombre ?? 'Tratamiento') . ($t->pieza_dental ? " · pieza {$t->pieza_dental}" : '')),
                    'extra'  => 'Realizado #' . $t->id . ' · ' . ($t->estado ?? ''),
                    'pieza'  => $t->pieza_dental,
                    'clinica_id' => $t->clinica_id,
                ])
        );
    }

    private function guardarDetalles(InsumoSolicitado $solicitud, array $detalles): void    {
        foreach ($detalles as $d) {
            InsumoSolicitadoDetalle::create([
                'insumo_solicitado_id' => $solicitud->id,
                'insumo_id'            => $d['insumo_id'],
                'cantidad_solicitada'  => $d['cantidad_solicitada'],
                'cantidad_entregada'   => 0,
                'fecha'                => now(),
            ]);
        }
    }
}
