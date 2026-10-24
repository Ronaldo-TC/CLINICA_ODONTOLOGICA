@forelse ($asignaciones as $a)
<tr>
    <td>
        <input type="checkbox"
            class="row-check"
            data-usuario="{{ $a->usuario_id }}"
            data-clinica="{{ $a->clinica_id }}"
            aria-label="Seleccionar fila">
    </td>
    <td>
        <code>{{ $a->ci ?? '—' }}</code>
    </td>
    <td>
        <strong>{{ $a->nombre_completo }}</strong>
    </td>

    <td>
        @forelse ($a->roles as $rol)
        <span class="badge badge-info">{{ $rol }}</span>
        @empty
        <span class="badge badge-muted">Sin rol</span>
        @endforelse
    </td>



    <td>{{ $a->especialidad }}</td>

    <td>
        <span class="badge badge-info">{{ $a->modulo }}</span>
    </td>

    <td>
        @if ($a->rotacion >= 0)
        <span class="badge badge-muted">Rot. {{ $a->rotacion }}</span>
        @else
        <span class="badge badge-muted">—</span>
        @endif
    </td>

    <td>
        @if ($a->responsable_admision)
        <span class="badge badge-success">Sí</span>
        @else
        <span class="badge badge-muted">No</span>
        @endif
    </td>

    <td>
        @if ($a->habilitado)
        <span class="badge badge-success">Habilitada</span>
        @else
        <span class="badge badge-danger">Deshabilitada</span>
        @endif
    </td>

    <td>
        <div class="actions" style="justify-content:flex-end;">

            @can('clinicas.gestionar')
            @if ($a->habilitado)
            <button type="button"
                class="btn btn-warning btn-sm btn-toggle-especialidad"
                data-usuario="{{ $a->usuario_id }}"
                data-clinica="{{ $a->clinica_id }}"
                data-habilitar="0">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                </svg>
                Deshabilitar
            </button>
            @else
            <button type="button"
                class="btn btn-primary btn-sm btn-toggle-especialidad"
                data-usuario="{{ $a->usuario_id }}"
                data-clinica="{{ $a->clinica_id }}"
                data-habilitar="1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                Habilitar
            </button>
            @endif

            <button type="button"
                class="btn btn-danger btn-sm btn-eliminar-especialidad"
                data-usuario="{{ $a->usuario_id }}"
                data-clinica="{{ $a->clinica_id }}"
                data-nombre="la asignación de {{ $a->nombre_completo }} en {{ $a->especialidad }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    <path d="M10 11v6" />
                    <path d="M14 11v6" />
                </svg>
                Eliminar
            </button>
            @endcan

            @cannot('clinicas.gestionar')
            <span class="badge badge-muted">Solo lectura</span>
            @endcannot

        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="10">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            No se encontraron asignaciones con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse