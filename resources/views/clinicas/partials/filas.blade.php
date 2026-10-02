@forelse ($items as $clinica)
<tr data-id="{{ $clinica->id }}">
    <td>
        <span class="badge badge-muted">{{ $clinica->modulo }}</span>
    </td>

    <td>
        <strong>{{ $clinica->especialidad }}</strong>
    </td>

    <td>
        @if ($clinica->curso > 0)
        {{ $clinica->curso }}° año
        @else
        —
        @endif
    </td>

    <td>
        <span class="badge badge-info">
            {{ $clinica->tratamientos_count ?? 0 }}
        </span>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $clinica->insumos_tratamiento_count ?? 0 }}
        </span>
    </td>

    <td>
        <div class="actions">
            @can('clinicas.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $clinica->id }}">
                Editar
            </button>
            @endcan

            @can('clinicas.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $clinica->id }}"
                data-nombre="{{ $clinica->especialidad }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <p>No hay clínicas registradas</p>
        </div>
    </td>
</tr>
@endforelse