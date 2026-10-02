@forelse ($items as $periodo)
<tr data-id="{{ $periodo->id }}">
    <td>
        <strong>{{ $periodo->nombre }}</strong>
    </td>

    <td>
        <span class="badge badge-info">{{ $periodo->gestion }}</span>
    </td>

    <td>
        <span class="badge badge-muted">{{ $periodo->semestre }}°</span>
    </td>

    <td>
        <small>
            {{ $periodo->fecha_inicio?->format('d/m/Y') }}
            —
            {{ $periodo->fecha_fin?->format('d/m/Y') }}
        </small>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $periodo->admisiones_count ?? 0 }}
        </span>
    </td>

    <td>
        @if ($periodo->habilitado)
        <span class="badge badge-success">Activo</span>
        @else
        <span class="badge badge-danger">Inactivo</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @can('periodos.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $periodo->id }}">
                Editar
            </button>
            @endcan

            @can('periodos.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $periodo->id }}"
                data-nombre="{{ $periodo->nombre }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <p>No hay periodos registrados</p>
        </div>
    </td>
</tr>
@endforelse