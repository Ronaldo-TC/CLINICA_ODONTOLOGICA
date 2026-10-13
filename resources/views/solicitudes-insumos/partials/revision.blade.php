@php
    $usuarioRevision = auth()->user();
    $historiaSolicitud = $solicitud->historiaClinica ?? $solicitud->tratamientoRealizado?->historiaClinica;
    $historiaVigente = $historiaSolicitud && !$historiaSolicitud->fecha_fin
        && !in_array($historiaSolicitud->estado, ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado'], true);
    $supervisor = $usuarioRevision->hasAnyRole(['docente', 'jefe-catedra'])
        && ((int) $historiaSolicitud?->docente_id === $usuarioRevision->id
            || ($solicitud->sesion_atencion_id && (int) $solicitud->sesionAtencion?->docente_id === $usuarioRevision->id));
    $encargado = $usuarioRevision->hasAnyRole(['insumos', 'admin', 'subadmin']);
@endphp

<div class="actions" style="margin-top:14px;">
    @if($solicitud->estado === 'pendiente' && $supervisor && $historiaVigente)
        @canany(['solicitudes-insumos.autorizar', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
            <form method="POST" action="{{ route('solicitudes-insumos.autorizar', $solicitud->id) }}">
                @csrf
                <button class="btn btn-primary">Autorizar solicitud</button>
            </form>
        @endcanany
    @endif
    @if($solicitud->estado === 'autorizado' && $encargado)
        @canany(['solicitudes-insumos.aprobar', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
            <form method="POST" action="{{ route('solicitudes-insumos.aprobar', $solicitud->id) }}">
                @csrf
                <button class="btn btn-primary">Aprobar en Insumos</button>
            </form>
        @endcanany
    @endif
    @if(($solicitud->estado === 'pendiente' && $supervisor && $historiaVigente)
        || ($encargado && in_array($solicitud->estado, ['autorizado', 'aprobado'], true) && $solicitud->entregas_count === 0))
        @canany(['solicitudes-insumos.rechazar', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
            <form method="POST" action="{{ route('solicitudes-insumos.rechazar', $solicitud->id) }}">
                @csrf
                <label class="form-group">Motivo del rechazo (opcional)
                    <input class="form-control" name="motivo" maxlength="2000">
                </label>
                <button class="btn btn-danger">Rechazar solicitud</button>
            </form>
        @endcanany
    @endif
</div>
