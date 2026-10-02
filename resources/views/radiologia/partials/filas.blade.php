@forelse ($items as $rad)
<tr data-id="{{ $rad->id }}">
    <td><code>{{ $rad->id }}</code></td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">
                {{ $rad->paciente?->iniciales ?? '??' }}
            </div>
            <div>
                <strong>{{ $rad->paciente?->nombre_completo ?? 'Sin paciente' }}</strong>
                <br>
                <small style="color:var(--muted);">
                    CI: {{ $rad->paciente?->ci ?? '—' }}
                </small>
            </div>
        </div>
    </td>

    <td>
        <strong>{{ $rad->descripcion }}</strong>
        @if ($rad->observacion)
        <br>
        <small style="color:var(--muted);">{{ $rad->observacion }}</small>
        @endif
    </td>

    <td>
        <span class="badge badge-info">
            {{ $rad->clinica?->especialidad ?? '—' }}
        </span>
    </td>

    <td>
        <small>
            {{ $rad->fecha_toma?->format('d/m/Y H:i') ?? '—' }}
        </small>
    </td>

    <td>
        @if ($rad->caja)
        <code>#{{ $rad->caja->recibo ?: $rad->caja_id }}</code>
        @else
        —
        @endif
    </td>

    <td>
        @if ($rad->estado === 'realizado' || $rad->estado === 'completado')
        <span class="badge badge-success">{{ $rad->estado }}</span>
        @elseif ($rad->estado === 'pendiente')
        <span class="badge badge-warning">{{ $rad->estado }}</span>
        @else
        <span class="badge badge-muted">{{ $rad->estado }}</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @can('radiologias.editar')
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $rad->id }}">
                Editar
            </button>
            @endcan

            @can('radiologias.eliminar')
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $rad->id }}"
                data-nombre="Estudio #{{ $rad->id }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <p>No hay estudios radiológicos</p>
        </div>
    </td>
</tr>
@endforelse