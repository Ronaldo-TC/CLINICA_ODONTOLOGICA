@forelse ($items as $bitacora)
<tr data-id="{{ $bitacora->id }}">
    <td>
        <small>{{ $bitacora->fecha_formateada ?? $bitacora->fecha?->format('d/m/Y H:i') }}</small>
    </td>

    <td>
        <strong>{{ $bitacora->usuario_nombre }}</strong>
        @if ($bitacora->usuario_cuenta)
        <br>
        <small style="color:var(--muted);">
            {{ $bitacora->usuario_cuenta }}
        </small>
        @endif
    </td>

    <td>
        <span class="badge badge-info">{{ $bitacora->tipo }}</span>
    </td>

    <td>
        <code class="table-code-wrap" style="font-size:11px;">
            {{ \Illuminate\Support\Str::limit($bitacora->descripcion, 120) }}
        </code>
    </td>

    <td>
        @if (in_array($bitacora->estado, ['exito', 'success', 'ok'], true))
        <span class="badge badge-success">{{ $bitacora->estado_nombre }}</span>
        @elseif ($bitacora->estado === 'error' || $bitacora->estado === 'fallo')
        <span class="badge badge-danger">{{ $bitacora->estado_nombre }}</span>
        @else
        <span class="badge badge-muted">{{ $bitacora->estado_nombre }}</span>
        @endif
    </td>

    <td>
        <code style="font-size:11px;">{{ $bitacora->ip }}</code>
    </td>
    <td>
        <a class="btn btn-sm btn-secondary" href="{{ route('bitacoras.show', $bitacora->id) }}">
            <x-icono nombre="ver" /> Ver detalle
        </a>
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="9" y1="15" x2="15" y2="15" />
            </svg>
            <p>No hay eventos en la bitácora</p>
        </div>
    </td>
</tr>
@endforelse
