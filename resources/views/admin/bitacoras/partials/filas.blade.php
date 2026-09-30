@forelse ($bitacoras as $b)
<tr>
    <td>{{ $b->id }}</td>

    <td>
        @if ($b->fecha)
        <span title="{{ \Carbon\Carbon::parse($b->fecha)->format('d/m/Y H:i:s') }}">
            {{ \Carbon\Carbon::parse($b->fecha)->format('d/m/Y H:i') }}
        </span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>

    <td>
        @if ($b->usuario)
        <strong>{{ $b->usuario->nombre_completo }}</strong>
        <br>
        <small class="text-muted">{{ $b->usuario->nombre_usuario }}</small>
        @else
        <span class="text-muted">Sistema</span>
        @endif
    </td>

    <td><code>{{ $b->ip ?? '—' }}</code></td>

    <td>
        @php
        $tipo = trim((string) ($b->tipo ?? ''));
        // Color según prefijo de la acción
        $badgeClass = 'badge-secondary';
        if (\Illuminate\Support\Str::startsWith($tipo, 'CREAR')) $badgeClass = 'badge-success';
        elseif (\Illuminate\Support\Str::startsWith($tipo, 'ACTUALIZAR')) $badgeClass = 'badge-info';
        elseif (\Illuminate\Support\Str::startsWith($tipo, 'ELIMINAR')) $badgeClass = 'badge-danger';
        elseif (\Illuminate\Support\Str::startsWith($tipo, 'DESHABILITAR')) $badgeClass = 'badge-warning';
        elseif (\Illuminate\Support\Str::startsWith($tipo, 'AUTORIZAR')) $badgeClass = 'badge-success';
        @endphp
        <span class="badge {{ $badgeClass }}">{{ $tipo !== '' ? $tipo : '—' }}</span>
    </td>

    <td>
        <span title="{{ $b->datos }}">
            {{ \Illuminate\Support\Str::limit($b->datos ?? '', 80, '…') }}
        </span>
    </td>

    <td>
        @if (($b->estado ?? '') === 'OK')
        <span class="badge badge-success">OK</span>
        @else
        <span class="badge badge-danger">{{ $b->estado ?? '—' }}</span>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
                <polyline points="10 9 9 9 8 9" />
            </svg>
            No se encontraron registros de bitácora con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse