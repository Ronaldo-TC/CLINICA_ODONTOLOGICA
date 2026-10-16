<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\HistoriaClinica;
use App\Models\Notificacion;
use App\Models\Radiologia;
use App\Models\SolicitudRadiologica;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitudRadiologicaWorkflow
{
    public function crear(HistoriaClinica $historia, Usuario $actor, string $descripcion, ?float $costo): SolicitudRadiologica
    {
        $esDocenteResponsable = $actor->hasRole('docente') && (int) $historia->docente_id === (int) $actor->id;
        $esEstudianteAsignado = AccesoClinico::esEstudiante($actor) && (int) $historia->estudiante_id === (int) $actor->id;
        if ((! $esDocenteResponsable && ! $esEstudianteAsignado) || ! AccesoClinico::puedeVerHistoria($historia, $actor)) {
            abort(403);
        }

        $solicitud = DB::transaction(function () use ($historia, $actor, $descripcion, $costo, $esDocenteResponsable): SolicitudRadiologica {
            $historia = HistoriaClinica::query()->lockForUpdate()->findOrFail($historia->id);
            $this->asegurarHistoriaAbierta($historia);
            if (! $historia->autorizado) {
                throw ValidationException::withMessages(['historia' => 'La historia clínica debe estar autorizada antes de solicitar el estudio.']);
            }
            if ($esDocenteResponsable && ($costo === null || $costo <= 0)) {
                throw ValidationException::withMessages(['costo_autorizado' => 'El docente debe indicar el costo al generar directamente la solicitud.']);
            }

            return SolicitudRadiologica::create([
                'historia_clinica_id' => $historia->id,
                'paciente_id' => $historia->paciente_id,
                'clinica_id' => $historia->clinica_id,
                'solicitante_id' => $actor->id,
                'docente_id' => $historia->docente_id,
                'revisado_por' => $esDocenteResponsable ? $actor->id : null,
                'estado' => $esDocenteResponsable ? SolicitudRadiologica::AUTORIZADA : SolicitudRadiologica::PENDIENTE,
                'descripcion' => trim($descripcion),
                'observacion' => null,
                'costo_autorizado' => $esDocenteResponsable ? $costo : null,
                'fecha_revision' => $esDocenteResponsable ? now() : null,
            ]);
        });

        if ($solicitud->estado === SolicitudRadiologica::PENDIENTE) {
            NotificacionService::a(
                $solicitud->docente_id,
                Notificacion::TIPO_SOLICITUD_CREADA,
                'Solicitud radiológica pendiente',
                'Revise la solicitud #'.$solicitud->id.' de la ficha #'.$solicitud->historiaClinica->admision_id.'.',
                route('historias.show', $solicitud->historia_clinica_id),
            );
        }

        return $solicitud;
    }

    public function revisar(
        SolicitudRadiologica $solicitud,
        Usuario $actor,
        string $accion,
        ?string $observacion,
        ?float $costo
    ): SolicitudRadiologica {
        $solicitud = DB::transaction(function () use ($solicitud, $actor, $accion, $observacion, $costo): SolicitudRadiologica {
            $solicitud = SolicitudRadiologica::query()->with('historiaClinica')->lockForUpdate()->findOrFail($solicitud->id);
            if (! AccesoClinico::supervisaHistoria($actor, $solicitud->historiaClinica)) {
                abort(403);
            }
            if (! in_array($solicitud->estado, [SolicitudRadiologica::PENDIENTE, SolicitudRadiologica::OBSERVADA], true)) {
                throw ValidationException::withMessages(['accion' => 'La solicitud ya fue revisada y no admite otra decisión.']);
            }

            if ($accion === 'autorizar') {
                if ($costo === null || $costo <= 0) {
                    throw ValidationException::withMessages(['costo_autorizado' => 'Indique un costo autorizado mayor a cero.']);
                }
                $solicitud->estado = SolicitudRadiologica::AUTORIZADA;
                $solicitud->costo_autorizado = $costo;
                $solicitud->observacion = null;
            } elseif ($accion === 'observar') {
                $solicitud->estado = SolicitudRadiologica::OBSERVADA;
                $solicitud->observacion = trim((string) $observacion);
                $solicitud->costo_autorizado = null;
            } elseif ($accion === 'rechazar') {
                $solicitud->estado = SolicitudRadiologica::RECHAZADA;
                $solicitud->observacion = trim((string) $observacion);
                $solicitud->costo_autorizado = null;
            } else {
                throw ValidationException::withMessages(['accion' => 'La acción de revisión no es válida.']);
            }

            $solicitud->revisado_por = $actor->id;
            $solicitud->fecha_revision = now();
            $solicitud->save();

            return $solicitud;
        });

        NotificacionService::a(
            $solicitud->solicitante_id,
            match ($solicitud->estado) {
                SolicitudRadiologica::AUTORIZADA => Notificacion::TIPO_SOLICITUD_AUTORIZADA,
                SolicitudRadiologica::OBSERVADA => Notificacion::TIPO_SOLICITUD_OBSERVADA,
                default => Notificacion::TIPO_SOLICITUD_RECHAZADA,
            },
            'Actualización de solicitud radiológica',
            'La solicitud #'.$solicitud->id.' fue '.match ($solicitud->estado) {
                SolicitudRadiologica::AUTORIZADA => 'autorizada',
                SolicitudRadiologica::OBSERVADA => 'observada',
                default => 'rechazada',
            }.($solicitud->observacion ? ': '.$solicitud->observacion : '.'),
            route('historias.show', $solicitud->historia_clinica_id),
        );

        return $solicitud;
    }

    public function reenviar(SolicitudRadiologica $solicitud, Usuario $actor, string $descripcion): SolicitudRadiologica
    {
        $solicitud = DB::transaction(function () use ($solicitud, $actor, $descripcion): SolicitudRadiologica {
            $solicitud = SolicitudRadiologica::query()->lockForUpdate()->findOrFail($solicitud->id);
            if ((int) $solicitud->solicitante_id !== (int) $actor->id
                || ! AccesoClinico::esEstudiante($actor)
                || $solicitud->estado !== SolicitudRadiologica::OBSERVADA) {
                abort(403);
            }

            $solicitud->update([
                'estado' => SolicitudRadiologica::PENDIENTE,
                'descripcion' => trim($descripcion),
                'observacion' => null,
                'costo_autorizado' => null,
                'revisado_por' => null,
                'fecha_revision' => null,
            ]);

            return $solicitud;
        });

        NotificacionService::a(
            $solicitud->docente_id,
            Notificacion::TIPO_SOLICITUD_CREADA,
            'Solicitud radiológica reenviada',
            'El estudiante corrigió y reenvió la solicitud #'.$solicitud->id.'.',
            route('historias.show', $solicitud->historia_clinica_id),
        );

        return $solicitud;
    }

    public function registrarPago(
        SolicitudRadiologica $solicitud,
        Usuario $actor,
        float $monto,
        ?string $tipoPago
    ): Caja {
        $recibo = DB::transaction(function () use ($solicitud, $actor, $monto, $tipoPago): Caja {
            $solicitud = SolicitudRadiologica::query()->with(['historiaClinica.paciente'])->lockForUpdate()->findOrFail($solicitud->id);
            if ($solicitud->estado !== SolicitudRadiologica::AUTORIZADA || $solicitud->costo_autorizado === null) {
                throw ValidationException::withMessages(['solicitud' => 'Solo se puede cobrar un estudio radiológico autorizado.']);
            }
            if ($solicitud->estudio()->exists()) {
                throw ValidationException::withMessages(['solicitud' => 'El estudio ya fue registrado y no admite nuevos cobros.']);
            }
            if ($monto <= 0 || $this->centavos($solicitud->montoPagado()) + $this->centavos($monto) > $this->centavos((float) $solicitud->costo_autorizado)) {
                throw ValidationException::withMessages(['monto' => 'El pago debe ser mayor a cero y no puede superar el saldo del estudio solicitado.']);
            }

            $historia = $solicitud->historiaClinica;
            $paciente = $historia->paciente;
            $numeroRecibo = Caja::siguienteNumeroRecibo()
                ?? throw ValidationException::withMessages(['recibo' => 'Se alcanzó el límite de numeración de recibos. Contacte a un administrador.']);
            $recibo = Caja::create([
                'recibo' => $numeroRecibo,
                'monto' => 0,
                'costo_total' => $solicitud->costo_autorizado,
                'cancelado' => $monto,
                'habilitada' => true,
                'descripcion' => mb_substr($solicitud->descripcion, 0, 100),
                'ci' => ctype_digit((string) $paciente->ci) ? (int) $paciente->ci : 0,
                'tipo' => 'radiologia',
                'tipo_pago' => $tipoPago,
                'paciente_id' => $solicitud->paciente_id,
                'paciente_nombre_completo' => mb_substr($paciente->nombre_completo, 0, 100),
                'tratamiento_id' => null,
                'tratamiento_realizado_id' => null,
                'clinica_id' => $solicitud->clinica_id,
                'remision_id' => $historia->getAttribute('remision_id'),
                'periodo_id' => $historia->periodo_id,
                'usuario_id' => $actor->id,
                'solicitud_radiologica_id' => $solicitud->id,
            ]);

            $recibo->detalles()->create([
                'tratamiento_realizado_id' => null,
                'concepto' => 'Estudio radiológico: '.$solicitud->descripcion,
                'cantidad' => 1,
                'precio_unitario' => $monto,
                'monto' => $monto,
                'fecha' => today(),
            ]);

            return $recibo;
        });

        $solicitud->loadMissing('historiaClinica');
        foreach (array_unique([$solicitud->solicitante_id, $solicitud->docente_id]) as $usuarioId) {
            NotificacionService::a(
                (int) $usuarioId,
                Notificacion::TIPO_PAGO_RADIOLOGIA,
                'Pago registrado para el estudio radiológico',
                'Recibo #'.$recibo->recibo.' · '.$solicitud->descripcion.' · Estado: '.$solicitud->fresh()->resumenPago()['estado'].'.',
                route('historias.show', $solicitud->historia_clinica_id),
            );
        }
        if ($solicitud->fresh()->resumenPago()['estado'] === 'pagado') {
            NotificacionService::aRol(
                'radiologia',
                Notificacion::TIPO_PAGO_RADIOLOGIA,
                'Estudio radiológico autorizado y pagado',
                'Ficha #'.$solicitud->historiaClinica->admision_id.' · '.$solicitud->descripcion.'.',
                route('radiologia.index'),
            );
        }

        return $recibo;
    }

    public function registrarEstudio(SolicitudRadiologica $solicitud, Usuario $actor): Radiologia
    {
        return DB::transaction(function () use ($solicitud, $actor): Radiologia {
            $solicitud = SolicitudRadiologica::query()->with('historiaClinica')->lockForUpdate()->findOrFail($solicitud->id);
            if (! $actor->hasAnyRole(['radiologia', 'admin', 'subadmin', 'director'])
                || ! $actor->canAny(['radiologias.gestionar', 'radiologias.crear'])) {
                abort(403);
            }
            if ($solicitud->estado !== SolicitudRadiologica::AUTORIZADA
                || $solicitud->resumenPago()['estado'] !== 'pagado') {
                throw ValidationException::withMessages(['solicitud' => 'Radiología requiere autorización docente y pago completo del estudio específico.']);
            }
            if ($solicitud->estudio()->exists()) {
                throw ValidationException::withMessages(['solicitud' => 'El estudio radiológico ya fue registrado.']);
            }

            $recibo = $solicitud->recibos()->where('habilitada', true)->orderByDesc('id')->firstOrFail();

            return Radiologia::create([
                'descripcion' => mb_substr($solicitud->descripcion, 0, 100),
                'observacion' => '',
                'estado' => 'realizado',
                'usuario_id' => $actor->id,
                'caja_id' => $recibo->id,
                'clinica_id' => $solicitud->clinica_id,
                'paciente_id' => $solicitud->paciente_id,
                'remision_id' => $solicitud->historiaClinica->getAttribute('remision_id'),
                'fecha_toma' => now(),
                'periodo_id' => $solicitud->historiaClinica->periodo_id,
                'solicitud_radiologica_id' => $solicitud->id,
            ]);
        });
    }

    private function asegurarHistoriaAbierta(HistoriaClinica $historia): void
    {
        if ($historia->fecha_fin !== null || in_array($historia->estado, ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada'], true)) {
            throw ValidationException::withMessages(['historia' => 'No se puede solicitar un estudio en una historia clínica cerrada.']);
        }
    }

    private function centavos(float $monto): int
    {
        return (int) round($monto * 100);
    }
}
