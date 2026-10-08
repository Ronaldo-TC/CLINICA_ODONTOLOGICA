<?php

namespace App\Services;

use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoEntrega;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\InsumoTratamiento;
use App\Models\InsumoTratamientoUsado;
use App\Models\Notificacion;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudInsumoService
{
    /** @param array<string, mixed> $input */
    public function guardar(Usuario $actor, array $input, ?InsumoSolicitado $solicitud = null): InsumoSolicitado
    {
        if (isset($input['historia_clinica_id']) && empty($input['tratamiento_realizado_id'])) {
            return $this->guardarDesignado($actor, $input, $solicitud);
        }
        $data = Validator::make($input, [
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
            'insumo_tratamiento_id' => ['required', 'integer', 'exists:insumos_tratamiento,id'],
            'tratamiento_realizado_id' => ['required', 'integer', 'exists:tratamientos_realizados,id'],
            'sesion_atencion_id' => ['required', 'integer', 'exists:sesiones_atencion,id'],
            'pieza' => ['nullable', 'integer', 'min:0', 'max:99'],
            'insumos' => ['nullable', 'string', 'max:2000'],
            'remision_id' => ['nullable', 'integer', 'exists:remisiones,id'],
            'detalles' => ['required', 'array', 'min:1', 'max:100'],
            'detalles.*.insumo_id' => ['required', 'integer', 'distinct', 'exists:insumos,id'],
            'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
        ])->validate();

        $saved = DB::transaction(function () use ($actor, $data, $solicitud): InsumoSolicitado {
            if ($solicitud) {
                $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
                $this->assertOwner($actor, $solicitud);
                $this->requireState($solicitud, [InsumoSolicitado::ESTADO_PENDIENTE, InsumoSolicitado::ESTADO_RECHAZADO]);
            }

            $tratamiento = TratamientoRealizado::query()->with('historiaClinica')->lockForUpdate()->findOrFail($data['tratamiento_realizado_id']);
            $sesion = SesionAtencion::query()->lockForUpdate()->findOrFail($data['sesion_atencion_id']);
            $this->assertClinicalLink($tratamiento, $sesion, (int) $data['clinica_id']);
            $this->assertSessionAuthorized($sesion);
            if (! in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true)) {
                $this->invalid('tratamiento_realizado_id', 'El tratamiento debe estar autorizado y en curso.');
            }
            abort_unless($actor->hasAnyRole(['admin', 'subadmin']) || (int) $tratamiento->historiaClinica?->estudiante_id === $actor->id || $this->isSupervisor($actor, $tratamiento, $sesion), 403, 'No puedes solicitar insumos para este tratamiento.');

            $tipo = InsumoTratamiento::query()->findOrFail($data['insumo_tratamiento_id']);
            if ((int) $tipo->clinica_id !== (int) $data['clinica_id']) {
                $this->invalid('insumo_tratamiento_id', 'El tipo de insumo no pertenece a la clínica.');
            }

            $insumos = Insumo::query()->whereIn('id', array_column($data['detalles'], 'insumo_id'))->get()->keyBy('id');
            foreach ($data['detalles'] as $index => $detalle) {
                $insumo = $insumos->get($detalle['insumo_id']);
                if (! $insumo || (int) $insumo->clinica_id !== (int) $data['clinica_id'] || (int) $insumo->insumo_tratamiento_id !== $tipo->id) {
                    $this->invalid("detalles.{$index}.insumo_id", 'El insumo debe pertenecer a la clínica y al tipo seleccionados.');
                }
            }
            if (! empty($data['remision_id']) && (int) $tratamiento->historiaClinica?->remision_id !== (int) $data['remision_id']) {
                $this->invalid('remision_id', 'La remisión no corresponde a la historia del tratamiento.');
            }

            $detalles = $data['detalles'];
            unset($data['detalles']);
            $data['pieza'] ??= is_numeric($tratamiento->pieza_dental) ? (int) $tratamiento->pieza_dental : 0;
            $data['estado'] = InsumoSolicitado::ESTADO_PENDIENTE;
            $data['historia_clinica_id'] = $tratamiento->historia_clinica_id;
            $data['tratamiento_id'] = $tratamiento->tratamiento_id;
            $data['aprobado_por'] = null;
            $data['fecha_aprobacion'] = null;
            $data['autorizado'] = false;
            $data['docente_autoriza'] = false;
            $data['docente_id'] = null;
            $data['fecha_autorizacion'] = null;
            $data['rechazado_por'] = null;
            $data['fecha_rechazo'] = null;
            $data['motivo_rechazo'] = null;
            if ($solicitud) {
                $solicitud->update($data);
                $solicitud->detalles()->delete();
            } else {
                $solicitud = InsumoSolicitado::query()->create($data + ['usuario_id' => $tratamiento->historiaClinica->estudiante_id, 'fecha_solicitud' => today()]);
            }
            foreach ($detalles as $detalle) {
                $solicitud->detalles()->create($detalle + ['cantidad_entregada' => 0]);
            }

            return $solicitud->load(['clinica', 'estudiante', 'docente', 'detalles.insumo', 'sesionAtencion', 'tratamientoRealizado.tratamiento']);
        });

        $supervisor = $saved->sesionAtencion?->docente_id ?? $saved->tratamientoRealizado?->historiaClinica?->docente_id;
        NotificacionService::a($supervisor, Notificacion::TIPO_SOLICITUD_CREADA, 'Solicitud de insumos #'.$saved->id, 'Un avance autorizado requiere materiales.', $this->url($saved));

        return $saved;
    }

    /** @param array<string, mixed> $input */
    private function guardarDesignado(Usuario $actor, array $input, ?InsumoSolicitado $solicitud): InsumoSolicitado
    {
        $saved = DB::transaction(function () use ($actor, $input, $solicitud): InsumoSolicitado {
            $data = Validator::make($input, [
                'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
                'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
                'tratamiento_id' => ['required', 'integer', Rule::exists('tratamientos', 'id')->where('clinica_id', $input['clinica_id'] ?? null)],
                'pieza' => ['nullable', 'integer', 'min:0', 'max:99'],
                'detalles' => ['required', 'array', 'min:1', 'max:100'],
                'detalles.*.insumo_id' => ['required', 'integer', 'distinct', 'exists:insumos,id'],
                'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            ])->validate();
            $historia = HistoriaClinica::query()->lockForUpdate()->findOrFail($data['historia_clinica_id']);
            abort_unless((int) $historia->estudiante_id === $actor->id || $actor->hasAnyRole(['admin', 'subadmin']), 403, 'Solo puede solicitar insumos para sus pacientes designados.');
            $this->exigirHistoriaVigente($historia);
            if ((int) $historia->clinica_id !== (int) $data['clinica_id']) {
                $this->invalid('clinica_id', 'Seleccione la clínica del paciente designado.');
            }
            if ($solicitud) {
                $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
                $this->assertOwner($actor, $solicitud);
                $this->requireState($solicitud, [InsumoSolicitado::ESTADO_PENDIENTE, InsumoSolicitado::ESTADO_RECHAZADO]);
                if ($solicitud->entregas()->exists()) {
                    $this->invalid('estado', 'Una solicitud con entregas debe conservarse.');
                }
            }
            $catalogo = Tratamiento::query()->findOrFail($data['tratamiento_id']);
            $materiales = $catalogo->insumos()->where('clinica_id', $historia->clinica_id)->get()->keyBy('id');
            foreach ($data['detalles'] as $index => $detalle) {
                $insumo = $materiales->get($detalle['insumo_id']);
                if (! $insumo) {
                    $this->invalid("detalles.{$index}.insumo_id", 'Seleccione únicamente los insumos configurados para este tratamiento y clínica.');
                }
                if ($this->cents((float) $detalle['cantidad_solicitada']) > $this->cents((float) $insumo->cantidad_numerica)) {
                    $this->invalid("detalles.{$index}.cantidad_solicitada", 'La cantidad solicitada supera las existencias disponibles.');
                }
            }
            $detalles = $data['detalles'];
            unset($data['detalles']);
            $data = [...$data, 'usuario_id' => $historia->estudiante_id, 'remision_id' => $historia->remision_id,
                'tratamiento_realizado_id' => null, 'sesion_atencion_id' => null, 'insumo_tratamiento_id' => null,
                'pieza' => $data['pieza'] ?? 0, 'estado' => InsumoSolicitado::ESTADO_PENDIENTE,
                'autorizado' => false, 'docente_autoriza' => false, 'docente_id' => null, 'fecha_autorizacion' => null,
                'aprobado_por' => null, 'fecha_aprobacion' => null, 'rechazado_por' => null, 'fecha_rechazo' => null, 'motivo_rechazo' => null];
            if ($solicitud) {
                $solicitud->update($data);
                $solicitud->detalles()->delete();
            } else {
                $solicitud = InsumoSolicitado::query()->create([...$data, 'fecha_solicitud' => today()]);
            }
            $solicitud->detalles()->createMany($detalles);

            return $solicitud->load(['historiaClinica', 'tratamiento', 'detalles.insumo']);
        });
        NotificacionService::a($saved->historiaClinica->docente_id, Notificacion::TIPO_SOLICITUD_CREADA, 'Solicitud de insumos #'.$saved->id, 'Revise los materiales solicitados para su paciente designado.', $this->url($saved));

        return $saved;
    }

    private function exigirHistoriaVigente(HistoriaClinica $historia): void
    {
        if ($historia->fecha_fin || in_array($historia->estado, ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado'], true)) {
            $this->invalid('historia_clinica_id', 'La historia del paciente designado está cerrada.');
        }
    }

    private function supervisorSolicitud(Usuario $actor, InsumoSolicitado $solicitud): bool
    {
        if (! $solicitud->sesion_atencion_id && $solicitud->historia_clinica_id) {
            $this->exigirHistoriaVigente($solicitud->historiaClinica);

            return $actor->hasAnyRole(['docente', 'jefe-catedra']) && $actor->id === (int) $solicitud->historiaClinica->docente_id;
        }
        [$tratamiento, $sesion] = $this->clinicalModels($solicitud);

        return $this->isSupervisor($actor, $tratamiento, $sesion);
    }

    public function aprobar(Usuario $actor, InsumoSolicitado $solicitud): InsumoSolicitado
    {
        abort_unless($actor->hasAnyRole(['insumos', 'admin', 'subadmin']), 403, 'Solo el encargado de insumos puede aprobar la entrega.');

        return DB::transaction(function () use ($actor, $solicitud): InsumoSolicitado {
            $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->requireState($solicitud, [InsumoSolicitado::ESTADO_AUTORIZADO]);
            if (! $solicitud->autorizado || ! $solicitud->docente_id || ! $solicitud->fecha_autorizacion) {
                $this->invalid('estado', 'El docente debe autorizar la solicitud antes de aprobarla en Insumos.');
            }
            $solicitud->update(['estado' => InsumoSolicitado::ESTADO_APROBADO, 'aprobado_por' => $actor->id, 'fecha_aprobacion' => now()]);

            return $solicitud;
        });
    }

    public function autorizar(Usuario $actor, InsumoSolicitado $solicitud): InsumoSolicitado
    {
        $saved = DB::transaction(function () use ($actor, $solicitud): InsumoSolicitado {
            $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->requireState($solicitud, [InsumoSolicitado::ESTADO_PENDIENTE]);
            abort_unless($this->supervisorSolicitud($actor, $solicitud), 403, 'Solo el docente supervisor puede autorizar los insumos.');
            $solicitud->update(['estado' => InsumoSolicitado::ESTADO_AUTORIZADO, 'autorizado' => true, 'docente_autoriza' => true, 'docente_id' => $actor->id, 'fecha_autorizacion' => now()]);

            return $solicitud;
        });
        NotificacionService::a($saved->usuario_id, Notificacion::TIPO_SOLICITUD_AUTORIZADA, 'Solicitud #'.$saved->id.' autorizada por el docente', 'El encargado de insumos revisará los materiales solicitados.', $this->url($saved));
        NotificacionService::aRol(['insumos', 'admin', 'subadmin'], Notificacion::TIPO_SOLICITUD_AUTORIZADA, 'Solicitud #'.$saved->id.' pendiente de revisión en Insumos', null, $this->url($saved));

        return $saved;
    }

    public function rechazar(Usuario $actor, InsumoSolicitado $solicitud, ?string $motivo): InsumoSolicitado
    {
        return DB::transaction(function () use ($actor, $solicitud, $motivo): InsumoSolicitado {
            $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
            if ($actor->hasAnyRole(['insumos', 'admin', 'subadmin']) && in_array($solicitud->estado, [InsumoSolicitado::ESTADO_AUTORIZADO, InsumoSolicitado::ESTADO_APROBADO], true)) {
                abort_if($solicitud->entregas()->exists(), 422, 'No puede rechazar una solicitud con entregas.');
            } else {
                $this->requireState($solicitud, [InsumoSolicitado::ESTADO_PENDIENTE]);
                abort_unless($this->supervisorSolicitud($actor, $solicitud), 403, 'Solo el docente supervisor puede rechazar esta solicitud pendiente.');
            }
            $solicitud->update(['estado' => InsumoSolicitado::ESTADO_RECHAZADO, 'rechazado_por' => $actor->id, 'fecha_rechazo' => now(), 'motivo_rechazo' => $motivo]);
            NotificacionService::a($solicitud->usuario_id, Notificacion::TIPO_SOLICITUD_RECHAZADA, 'Solicitud #'.$solicitud->id.' rechazada', $motivo, $this->url($solicitud));

            return $solicitud;
        });
    }

    /** @param array<string, mixed> $input */
    public function entregar(Usuario $actor, InsumoSolicitado $solicitud, array $input): InsumoSolicitado
    {
        abort_unless($actor->hasAnyRole(['insumos', 'admin', 'subadmin']), 403, 'Solo el encargado de insumos puede registrar entregas.');
        $data = $this->validateQuantities($input, 'cantidad_entregada');

        $saved = DB::transaction(function () use ($actor, $solicitud, $data): InsumoSolicitado {
            $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->requireState($solicitud, [InsumoSolicitado::ESTADO_AUTORIZADO, InsumoSolicitado::ESTADO_APROBADO, InsumoSolicitado::ESTADO_PARCIAL]);
            if ($solicitud->sesion_atencion_id) {
                $this->clinicalModels($solicitud);
            } elseif ($solicitud->historia_clinica_id) {
                $this->exigirHistoriaVigente($solicitud->historiaClinica);
            } else {
                $this->invalid('historia_clinica_id', 'La solicitud debe estar vinculada a un paciente designado.');
            }
            if ($solicitud->historia_clinica_id && ! $solicitud->sesion_atencion_id && ! $solicitud->aprobado_por) {
                $this->invalid('estado', 'El encargado de insumos debe aprobar la solicitud antes de entregar.');
            }
            if (! $solicitud->autorizado || ! $solicitud->docente_id || ! $solicitud->fecha_autorizacion) {
                $this->invalid('estado', 'La solicitud necesita una autorización docente registrada.');
            }
            $detalles = $solicitud->detalles()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $insumos = Insumo::query()->whereIn('id', $detalles->pluck('insumo_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['detalles'] as $item) {
                $cantidad = (float) $item['cantidad_entregada'];
                if ($cantidad === 0.0) {
                    continue;
                }
                $detalle = $detalles->get($item['detalle_id']);
                if (! $detalle) {
                    $this->invalid('detalles', 'El detalle no pertenece a esta solicitud.');
                }
                $insumo = $insumos->get($detalle->insumo_id);
                if ($this->cents($cantidad) > $this->cents($detalle->pendiente)) {
                    $this->invalid('detalles', "La entrega de {$insumo->nombre} supera la cantidad pendiente.");
                }
                if ($this->cents($cantidad) > $this->cents((float) $insumo->cantidad_numerica)) {
                    $this->invalid('detalles', "Stock insuficiente de {$insumo->nombre}.");
                }
                $insumo->decrement('cantidad_numerica', $cantidad);
                $detalle->update(['cantidad_entregada' => round((float) $detalle->cantidad_entregada + $cantidad, 2), 'fecha' => today()]);
                InsumoEntrega::query()->create(['insumo_solicitado_id' => $solicitud->id, 'insumo_solicitado_detalle_id' => $detalle->id, 'insumo_id' => $insumo->id, 'tratamiento_realizado_id' => $solicitud->tratamiento_realizado_id, 'sesion_atencion_id' => $solicitud->sesion_atencion_id, 'usuario_id' => $actor->id, 'cantidad' => $cantidad, 'costo_unitario' => $insumo->costo_unitario, 'fecha_entrega' => now()]);
            }
            $completa = $detalles->every(fn (InsumoSolicitadoDetalle $detalle): bool => $detalle->entregado_completo);
            $solicitud->update(['estado' => $completa ? InsumoSolicitado::ESTADO_ENTREGADO : InsumoSolicitado::ESTADO_PARCIAL]);

            return $solicitud->load('detalles.insumo');
        });
        NotificacionService::a($saved->usuario_id, Notificacion::TIPO_SOLICITUD_ENTREGADA, 'Entrega de solicitud #'.$saved->id, $saved->estado === InsumoSolicitado::ESTADO_PARCIAL ? 'Entrega parcial: todavía quedan materiales pendientes.' : 'Entrega completada.', $this->url($saved));
        foreach ($saved->detalles as $detalle) {
            if ((float) $detalle->insumo->cantidad_numerica <= (float) $detalle->insumo->stock_minimo) {
                NotificacionService::stockBajo($detalle->insumo->nombre, (float) $detalle->insumo->cantidad_numerica, $detalle->insumo->cantidad);
            }
        }

        return $saved;
    }

    /** @param array<string, mixed> $input */
    public function consumir(Usuario $actor, InsumoSolicitado $solicitud, array $input): InsumoSolicitado
    {
        $data = $this->validateQuantities($input, 'cantidad_usada');

        return DB::transaction(function () use ($actor, $solicitud, $data): InsumoSolicitado {
            $solicitud = InsumoSolicitado::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->requireState($solicitud, [InsumoSolicitado::ESTADO_PARCIAL, InsumoSolicitado::ESTADO_ENTREGADO]);
            [$tratamiento, $sesion] = $this->clinicalModels($solicitud);
            abort_unless($actor->hasAnyRole(['admin', 'subadmin', 'insumos']) || $actor->id === (int) $solicitud->usuario_id || $this->isSupervisor($actor, $tratamiento, $sesion), 403, 'No puedes registrar el consumo de esta solicitud.');
            $detalles = $solicitud->detalles()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['detalles'] as $item) {
                $cantidad = (float) $item['cantidad_usada'];
                if ($cantidad === 0.0) {
                    continue;
                }
                $detalle = $detalles->get($item['detalle_id']);
                if (! $detalle || $this->cents($cantidad) > $this->cents($detalle->disponible_uso)) {
                    $this->invalid('detalles', 'El consumo supera lo entregado y disponible en la solicitud.');
                }
                $costo = $solicitud->entregas()->where('insumo_solicitado_detalle_id', $detalle->id)->orderByDesc('id')->value('costo_unitario');
                InsumoTratamientoUsado::query()->create(['insumo_solicitado_id' => $solicitud->id, 'insumo_solicitado_detalle_id' => $detalle->id, 'insumo_id' => $detalle->insumo_id, 'tratamiento_realizado_id' => $tratamiento->id, 'sesion_atencion_id' => $sesion->id, 'usuario_id' => $actor->id, 'cantidad_solicitada' => $detalle->cantidad_solicitada, 'cantidad_usada' => $cantidad, 'costo_al_momento' => $costo ?? 0, 'fecha_uso' => today()]);
            }

            return $solicitud->load('detalles.consumos');
        });
    }

    public function assertOwner(Usuario $actor, InsumoSolicitado $solicitud): void
    {
        abort_unless($actor->id === (int) $solicitud->usuario_id || $actor->hasAnyRole(['admin', 'subadmin']), 403, 'Solo el solicitante puede modificar la solicitud.');
    }

    private function isSupervisor(Usuario $actor, TratamientoRealizado $tratamiento, SesionAtencion $sesion): bool
    {
        return $actor->hasAnyRole(['docente', 'jefe-catedra']) && ($actor->id === (int) $sesion->docente_id || $actor->id === (int) $tratamiento->historiaClinica?->docente_id);
    }

    private function assertClinicalLink(TratamientoRealizado $tratamiento, SesionAtencion $sesion, int $clinicaId): void
    {
        if ((int) $sesion->tratamiento_realizado_id !== $tratamiento->id || (int) $tratamiento->clinica_id !== $clinicaId) {
            $this->invalid('sesion_atencion_id', 'El avance debe pertenecer al tratamiento y a la clínica seleccionados.');
        }
    }

    private function assertSessionAuthorized(SesionAtencion $sesion): void
    {
        if (! $sesion->autorizado || ! $sesion->autorizado_por || ! $sesion->fecha_autorizacion) {
            $this->invalid('sesion_atencion_id', 'El avance necesita aprobación docente antes de solicitar o entregar insumos.');
        }
    }

    /** @return array{TratamientoRealizado, SesionAtencion} */
    private function clinicalModels(InsumoSolicitado $solicitud): array
    {
        if (! $solicitud->tratamiento_realizado_id || ! $solicitud->sesion_atencion_id) {
            $this->invalid('sesion_atencion_id', 'Vincula la solicitud con su tratamiento y avance autorizado.');
        }
        $tratamiento = TratamientoRealizado::query()->with('historiaClinica')->lockForUpdate()->findOrFail($solicitud->tratamiento_realizado_id);
        $sesion = SesionAtencion::query()->lockForUpdate()->findOrFail($solicitud->sesion_atencion_id);
        $this->assertClinicalLink($tratamiento, $sesion, (int) $solicitud->clinica_id);
        $this->assertSessionAuthorized($sesion);

        return [$tratamiento, $sesion];
    }

    /** @param array<int, string> $states */
    private function requireState(InsumoSolicitado $solicitud, array $states): void
    {
        if (! in_array($solicitud->estado, $states, true)) {
            $this->invalid('estado', 'La solicitud no permite esta acción en su estado actual.');
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function validateQuantities(array $input, string $column): array
    {
        $data = Validator::make($input, ['detalles' => ['required', 'array', 'min:1', 'max:100'], 'detalles.*.detalle_id' => ['required', 'integer', 'distinct'], 'detalles.*.'.$column => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2']])->validate();
        if (array_sum(array_column($data['detalles'], $column)) <= 0) {
            $this->invalid('detalles', 'Indica al menos una cantidad mayor que cero.');
        }

        return $data;
    }

    private function cents(float $value): int
    {
        return (int) round($value * 100);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function url(InsumoSolicitado $solicitud): ?string
    {
        return Route::has('solicitudes-insumos.show') ? route('solicitudes-insumos.show', $solicitud->id) : null;
    }
}
