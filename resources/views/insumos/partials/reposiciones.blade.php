@forelse($registros as $registro)
@php($etiquetas = ['pendiente' => 'Pendiente de Jefatura', 'autorizado' => 'Pendiente de Dirección', 'aprobado' => 'Pendiente de recepción', 'parcial' => 'Recepción parcial', 'recibido' => 'Recibida', 'rechazado' => 'Rechazada'])
<tr data-reposicion="{{ $registro->id }}">
    <td><strong>#{{ $registro->id }}</strong><br>{{ $registro->estudiante?->nombre_completo }}<br><small>{{ $registro->fecha_solicitud?->format('d/m/Y') }}</small><p>{{ $registro->observaciones }}</p></td>
    <td>
        @foreach($registro->detalles as $detalle)
            <p><strong>{{ $detalle->insumo?->nombre }}</strong> · {{ $detalle->insumo?->clinica?->especialidad }}<br>Solicitado: {{ $detalle->cantidad_solicitada }} · Recibido: {{ $detalle->cantidad_entregada }} {{ $detalle->insumo?->cantidad }}</p>
        @endforeach
    </td>
    <td><span class="badge badge-{{ $registro->estado === 'recibido' ? 'success' : 'warning' }}">{{ $etiquetas[$registro->estado] }}</span>@if($registro->motivo_rechazo)<p>{{ $registro->motivo_rechazo }}</p>@endif</td>
    <td>
        <p>Jefatura: {{ $registro->aprobadorJefatura?->nombre_completo ?? 'Pendiente' }}<br><small>{{ $registro->fecha_aprobacion_jefatura?->format('d/m/Y H:i') }}</small></p>
        <p>Dirección: {{ $registro->encargado?->nombre_completo ?? 'Pendiente' }}<br><small>{{ $registro->fecha_aprobacion?->format('d/m/Y H:i') }}</small></p>
        @foreach($registro->entregas as $recepcion)
            <small>Recepción: {{ $recepcion->usuario?->nombre_completo }} · {{ $recepcion->fecha_entrega?->format('d/m/Y H:i') }} · {{ $recepcion->cantidad }}</small><br>
        @endforeach
    </td>
    <td>
        @if($registro->estado === 'pendiente' && auth()->user()->hasAnyRole(['jefatura-clinicas', 'admin']))
            <form action="{{ route('insumos.reposiciones-revisar', $registro->id) }}" method="POST" data-reposicion-form>@csrf<input type="hidden" name="accion" value="jefatura"><button class="btn btn-primary btn-sm">Validar necesidad</button></form>
        @endif
        @if($registro->estado === 'autorizado' && auth()->user()->hasAnyRole(['director', 'admin']) && (int) $registro->jefatura_aprobado_por !== auth()->id())
            <form action="{{ route('insumos.reposiciones-revisar', $registro->id) }}" method="POST" data-reposicion-form>@csrf<input type="hidden" name="accion" value="director"><button class="btn btn-primary btn-sm">Aprobar abastecimiento</button></form>
        @endif
        @if(in_array($registro->estado, ['pendiente', 'autorizado']) && auth()->user()->hasAnyRole(['jefatura-clinicas', 'director', 'admin']))
            <details><summary>Rechazar solicitud</summary><form action="{{ route('insumos.reposiciones-revisar', $registro->id) }}" method="POST" data-reposicion-form>@csrf<input type="hidden" name="accion" value="rechazar"><label>Motivo del rechazo<input name="motivo" class="form-control" minlength="5" maxlength="2000" required></label><button class="btn btn-secondary btn-sm">Registrar rechazo</button></form></details>
        @endif
        @if(in_array($registro->estado, ['aprobado', 'parcial']) && auth()->user()->hasAnyRole(['insumos', 'director', 'admin']))
            <details><summary>Registrar recepción</summary><form action="{{ route('insumos.reposiciones-revisar', $registro->id) }}" method="POST" data-reposicion-form>@csrf<input type="hidden" name="accion" value="recibir">
                @foreach($registro->detalles as $detalle)
                    <input type="hidden" name="detalles[{{ $loop->index }}][detalle_id]" value="{{ $detalle->id }}">
                    <label>{{ $detalle->insumo?->nombre }}<input name="detalles[{{ $loop->index }}][cantidad_entregada]" class="form-control" type="number" min="0" max="{{ $detalle->pendiente }}" step="0.01" value="{{ $detalle->pendiente }}" required></label>
                @endforeach
                <button class="btn btn-primary btn-sm">Confirmar material recibido</button>
            </form></details>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="5"><div class="empty-state">No hay solicitudes de reposición para este filtro.</div></td></tr>
@endforelse
