@extends('layouts.app')
@section('title', 'Seguimiento de historia clínica')
@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">Historia clínica #{{ $registro->id }} · Ficha #{{ $registro->admision_id }}</div>
    <div class="toolbar-actions"><a class="btn btn-secondary" href="{{ route('historias.index') }}">Volver a historias</a></div>
</div>
<div class="card">
    <div class="card-header"><h2>{{ $registro->paciente?->nombre_completo }}</h2></div>
    <div class="card-body">
        <p>Clínica: {{ $registro->clinica?->especialidad }} · Periodo: {{ $registro->periodo?->gestion }}-{{ $registro->periodo?->semestre }}</p>
        <p>Estudiante: {{ $registro->estudiante?->nombre_completo }} · Docente: {{ $registro->docente?->nombre_completo }}</p>
        <div class="actions">
            <a class="btn btn-secondary" href="#tratamientos">Tratamientos</a>
            @canany(['cajas.ver', 'cajas.gestionar'])<a class="btn btn-secondary" href="#cajas">Cajas</a>@endcanany
            @canany(['solicitudes-insumos.ver', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])<a class="btn btn-secondary" href="#insumos">Insumos</a>@endcanany
            @canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])<a class="btn btn-secondary" href="#esterilizaciones">Esterilizaciones</a>@endcanany
        </div>
        @if($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
    </div>
</div>

<div class="card" id="tratamientos" style="margin-top:20px;">
    <div class="card-header"><h2>Tratamientos de esta historia</h2></div>
    <div class="card-body"><p>El docente asignado autoriza el tratamiento. Después, el encargado de Caja puede buscar la ficha #{{ $registro->admision_id }} y emitir el recibo.</p></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Tratamiento</th><th>Pieza dental</th><th>Costo (Bs)</th><th>Estado</th><th>Autorización docente</th><th>Acciones</th></tr></thead>
        <tbody>
        @forelse($registro->tratamientosRealizados as $tratamiento)
            <tr>
                <td>#{{ $tratamiento->id }} · {{ $tratamiento->tratamiento?->nombre }} @if($tratamiento->observacion)<br><small>{{ $tratamiento->observacion }}</small>@endif</td>
                <td>{{ $tratamiento->pieza_dental ?: 'Sin especificar' }}</td>
                <td>{{ $tratamiento->costo_cobrado }}</td>
                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $tratamiento->estado)) }}</span></td>
                <td>@if($tratamiento->estaAutorizado()){{ $tratamiento->autorizador?->nombre_completo }}<br><small>{{ $tratamiento->fecha_autorizacion?->format('d/m/Y H:i') }}</small>@else Pendiente @endif</td>
                <td>
                    @if($puedeAutorizarTratamientos && in_array($tratamiento->estado, ['pendiente', 'propuesta'], true) && !$tratamiento->estaAutorizado())
                        <form action="{{ route('tratamientos-realizados.autorizar', $tratamiento->id) }}" method="POST">
                            @csrf<button type="submit" class="btn btn-sm btn-primary">Autorizar tratamiento</button>
                        </form>
                    @elseif($tratamiento->estaAutorizado() && in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true))
                        <small>Disponible para emitir recibo en Caja.</small>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="6">Esta historia todavía no tiene tratamientos propuestos.</td></tr>@endforelse
        </tbody>
    </table></div>
</div>

@canany(['cajas.ver', 'cajas.gestionar'])
<div class="card" id="cajas" style="margin-top:20px;">
    <div class="card-header"><h2>Cajas · Recibos de esta historia</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Recibo</th><th>Tratamiento</th><th>Pagado por esta historia (Bs)</th><th>Tipo de pago</th><th>Fecha</th></tr></thead>
        <tbody>
        @forelse($pagos as $pago)
            <tr>
                <td>{{ $pago->recibo }}</td>
                <td>@if($pago->detalles->isNotEmpty())
                    @foreach($pago->detalles as $detalle)<div>{{ $detalle->tratamientoRealizado?->tratamiento?->nombre ?? $detalle->concepto }}</div>@endforeach
                    @else {{ $pago->tratamiento?->nombre ?? $pago->descripcion }} @endif</td>
                <td>{{ number_format($pago->detalles->isNotEmpty() ? $pago->detalles->sum('monto') : $pago->cancelado, 2) }}</td>
                <td>{{ $pago->tipo_pago }}</td><td>{{ $pago->created_at?->format('d/m/Y') }}</td>
            </tr>
        @empty<tr><td colspan="5">No hay recibos activos registrados para esta historia.</td></tr>@endforelse
        </tbody>
    </table></div>
</div>
@endcanany

@canany(['solicitudes-insumos.ver', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
<div class="card" id="insumos" style="margin-top:20px;">
    <div class="card-header"><h2>Insumos · Solicitudes del estudiante</h2></div>
    <div class="card-body"><p>El docente autoriza la solicitud y después el encargado de Insumos la aprueba o rechaza.</p></div>
    @forelse($solicitudes as $solicitud)
        <div class="card-body" style="border-top:1px solid var(--border);">
            <h3>Solicitud #{{ $solicitud->id }} · {{ $solicitud->tratamiento?->nombre ?? $solicitud->tratamientoRealizado?->tratamiento?->nombre }}</h3>
            <p>Estudiante: {{ $solicitud->estudiante?->nombre_completo }} · {{ ['pendiente' => 'Pendiente de autorización docente', 'autorizado' => 'Pendiente de aprobación en Insumos', 'aprobado' => 'Aprobada por Insumos', 'parcial' => 'Entrega parcial', 'entregado' => 'Entregada', 'rechazado' => 'Rechazada', 'cancelado' => 'Cancelada'][$solicitud->estado] ?? $solicitud->estado }}</p>
            <p>Docente que autorizó: {{ $solicitud->docente?->nombre_completo ?? 'Pendiente' }} · Aprobación de Insumos: {{ $solicitud->encargado?->nombre_completo ?? 'Pendiente' }}</p>
            @if($solicitud->motivo_rechazo)<p>Motivo del rechazo: {{ $solicitud->motivo_rechazo }}</p>@endif
            <div class="table-wrap"><table>
                <thead><tr><th>Insumo</th><th>Medida</th><th>Solicitado</th><th>Disponible</th><th>Entregado</th></tr></thead>
                <tbody>@foreach($solicitud->detalles as $detalle)<tr><td>{{ $detalle->insumo?->nombre }}</td><td>{{ $detalle->insumo?->cantidad }}</td><td>{{ $detalle->cantidad_solicitada }}</td><td>{{ $detalle->insumo?->cantidad_numerica }}</td><td>{{ $detalle->cantidad_entregada }}</td></tr>@endforeach</tbody>
            </table></div>
            <a class="btn btn-sm btn-secondary" href="{{ route('solicitudes-insumos.show', $solicitud->id) }}">Ver solicitud completa</a>
            @include('solicitudes-insumos.partials.revision', ['solicitud' => $solicitud])
        </div>
    @empty<div class="card-body"><p>Este estudiante no tiene solicitudes visibles para esta historia.</p></div>@endforelse
</div>
@endcanany

@canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
<div class="card" id="esterilizaciones" style="margin-top:20px;">
    <div class="card-header"><h2>Esterilizaciones · Controles del estudiante</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Control</th><th>Material</th><th>Correspondencia</th><th>Estado</th><th>Entrega</th><th>Recojo</th><th>Acciones</th></tr></thead>
        <tbody>@forelse($esterilizaciones as $control)<tr>
            <td>{{ $control->id }}</td><td>{{ $control->cantidad_sobres }} sobres · {{ $control->cantidad_paquetes }} paquetes<br>{{ $control->descripcion }}</td>
            <td>{{ $control->remision_id ? 'De este paciente' : 'Material general de la clínica y periodo' }}</td>
            <td>{{ $control->estado_proceso }}</td>
            <td>{{ $control->fecha_entrega?->format('d/m/Y') ?? 'Pendiente' }} {{ $control->hora_entrega }}</td>
            <td>{{ $control->fecha_recojo?->format('d/m/Y') ?? 'Pendiente' }} {{ $control->hora_recojo }}</td>
            <td><a class="btn btn-sm btn-secondary" href="{{ route('esterilizacion.control', $control->id) }}">Ver ficha</a></td>
        </tr>@empty<tr><td colspan="7">No hay controles de esterilización visibles para esta historia.</td></tr>@endforelse</tbody>
    </table></div>
</div>
@endcanany
@endsection
