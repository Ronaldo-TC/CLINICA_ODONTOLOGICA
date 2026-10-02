@forelse ($items as $bitacora)
<tr data-id="{{ $bitacora->id }}">
    <td>
        <small>{{ $bitacora->fecha_formateada ?? $bitacora->fecha?->format('d/m/Y H:i') }}</small>
    </td>

    <td>
        @if ($bitacora->usuario)
        <strong>{{ $bitacora->usuario->nombre_completo }}</strong>
        <br>
        <small style="color:var(--muted);">
            {{ $bitacora->usuario->nombre_usuario }}
        </small>
        @else
        <span class="badge badge-muted">Sistema</span>
        @endif
    </td>

    <td>
        <span class="badge badge-info">{{ $bitacora->tipo }}</span>
    </td>

    <td>
        <code style="font-size:11px; word-break: break-all;">
            {{ \Illuminate\Support\Str::limit($bitacora->datos, 100) }}
        </code>
    </td>

    <td>
        @if ($bitacora->estado === 'exito' || $bitacora->estado === 'success')
        <span class="badge badge-success">{{ $bitacora->estado }}</span>
        @elseif ($bitacora->estado === 'error' || $bitacora->estado === 'fallo')
        <span class="badge badge-danger">{{ $bitacora->estado }}</span>
        @else
        <span class="badge badge-muted">{{ $bitacora->estado ?: '—' }}</span>
        @endif
    </td>

    <td>
        <code style="font-size:11px;">{{ $bitacora->ip }}</code>
    </td>
</tr>
@empty
<tr>
    <td colspan="6">
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