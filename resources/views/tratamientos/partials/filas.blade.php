@forelse ($items as $tratamiento)
<tr data-id="{{ $tratamiento->id }}">
    <td>
        <strong>{{ $tratamiento->nombre }}</strong>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $tratamiento->clinica?->especialidad ?? '—' }}
        </span>
    </td>

    <td>
        <span style="color:var(--muted);">
            {{ $tratamiento->descripcion ?: '—' }}
        </span>
    </td>

    <td>
        <strong>{{ number_format($tratamiento->costo, 2) }}</strong> Bs
    </td>

    <td>
        {{ $tratamiento->porcentaje_pg ?: '—' }}
    </td>

    <td>
        <span class="badge badge-muted">
            {{ $tratamiento->tratamientos_realizados_count ?? 0 }}
        </span>
    </td>

    <td>
        <div class="actions">
            @can('tratamientos.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $tratamiento->id }}">
                Editar
            </button>
            @endcan

            @can('tratamientos.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $tratamiento->id }}"
                data-nombre="{{ $tratamiento->nombre }}">
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
                <circle cx="12" cy="12" r="10" />
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
            <p>No hay tratamientos registrados</p>
        </div>
    </td>
</tr>
@endforelse