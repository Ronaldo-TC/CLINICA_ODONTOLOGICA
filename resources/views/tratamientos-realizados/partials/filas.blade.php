@forelse ($registros as $registro)
@php
    $tono = match ($registro->estado) {
        'pendiente', 'propuesta' => 'warning',
        'culminado', 'realizado', 'completado' => 'success',
        'rechazado', 'cancelado' => 'danger',
        default => 'info',
    };
@endphp
<tr data-id="{{ $registro->id }}">
    <td>#{{ $registro->id }}</td>
    <td><strong>{{ $registro->historiaClinica?->paciente?->nombre_completo ?? 'Sin paciente' }}</strong><br><small class="text-muted">CI: {{ $registro->historiaClinica?->paciente?->ci ?? '—' }}</small></td>
    <td>
        <strong>{{ $registro->historiaClinica?->estudiante?->nombre_completo ?? 'Sin estudiante asignado' }}</strong>
        <br><small class="text-muted">Docente: {{ $registro->historiaClinica?->docente?->nombre_completo ?? 'Sin docente asignado' }}</small>
        @if ($registro->autorizador)
        <br><small class="text-muted">Tratamiento autorizado por: {{ $registro->autorizador->nombre_completo }}</small>
        @endif
    </td>
    <td><strong>{{ $registro->tratamiento?->nombre ?? 'Sin tratamiento' }}</strong><br><small class="text-muted">{{ $registro->clinica?->especialidad ?? '—' }}</small></td>
    <td>{{ $registro->pieza_dental ?: 'Sin especificar' }}</td>
    <td class="table-value">{{ number_format((float) $registro->costo_cobrado, 2) }}</td>
    <td>
        <span class="badge badge-{{ $tono }}">{{ $estados[$registro->estado] ?? ucfirst(str_replace('_', ' ', $registro->estado)) }}</span>
        @if ($registro->observacion)
        <br><small class="text-muted">{{ $registro->observacion }}</small>
        @endif
    </td>
    <td><strong>{{ $registro->sesionesAtencion->count() }}</strong><br><small class="text-muted">{{ $registro->sesionesAtencion->where('autorizado', true)->count() }} autorizados</small></td>
    <td><div class="actions">
        <button type="button" class="btn btn-sm btn-secondary" data-avances-toggle="{{ $registro->id }}" aria-expanded="false" aria-controls="avances-{{ $registro->id }}"><x-icono nombre="avance" /><span data-avances-label>Ver avances</span></button>
        @canany(['historias.ver', 'historias.gestionar'])
        <a class="btn btn-sm btn-secondary" href="{{ route('historias.show', $registro->historia_clinica_id) }}"><x-icono nombre="historia" /> Historia</a>
        @endcanany
        @if (in_array($registro->estado, ['pendiente', 'propuesta']))
        @canany(['atenciones.autorizar', 'atenciones.gestionar'])
        <form action="{{ route('tratamientos-realizados.autorizar', $registro->id) }}" method="POST" data-form-atencion>@csrf<button class="btn btn-sm btn-primary"><x-icono nombre="autorizar" /> Autorizar para Cajas</button></form>
        <form action="{{ route('tratamientos-realizados.rechazar', $registro->id) }}" method="POST" data-form-atencion class="form-grid">
            @csrf
            <div class="form-group">
                <label class="sr-only" for="motivo-rechazo-{{ $registro->id }}">Motivo del rechazo</label>
                <input id="motivo-rechazo-{{ $registro->id }}" name="motivo" class="form-control" placeholder="Motivo del rechazo" minlength="5" maxlength="2000" required>
            </div>
            <button class="btn btn-sm btn-danger"><x-icono nombre="rechazar" /> Rechazar</button>
        </form>
        @endcanany
        @endif
        @if ($registro->estado === 'en_proceso')
        @canany(['atenciones.editar', 'atenciones.gestionar'])
        <form action="{{ route('tratamientos-realizados.finalizar', $registro->id) }}" method="POST" data-form-atencion>@csrf<button class="btn btn-sm btn-primary"><x-icono nombre="finalizar" /> Finalizar como estudiante</button></form>
        @endcanany
        @endif
        @if ($registro->estado === 'finalizado')
        @canany(['atenciones.autorizar', 'atenciones.gestionar'])
        <form action="{{ route('tratamientos-realizados.culminar', $registro->id) }}" method="POST" data-form-atencion>@csrf<button class="btn btn-sm btn-primary"><x-icono nombre="finalizar" /> Confirmar culminación</button></form>
        @endcanany
        @endif
    </div></td>
</tr>
<tr class="realizado-detalle" id="avances-{{ $registro->id }}" hidden>
    <td colspan="9">@include('tratamientos-realizados.partials.avances', ['registro' => $registro])</td>
</tr>
@empty
<tr><td colspan="9"><div class="empty-state"><p>No se encontraron tratamientos realizados.</p></div></td></tr>
@endforelse
