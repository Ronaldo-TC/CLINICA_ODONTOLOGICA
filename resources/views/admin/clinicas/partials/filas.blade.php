@forelse ($clinicas as $c)
<tr>
    <td>{{ $c->id }}</td>
    <td>
        @if ($c->modulo)
        <span class="badge badge-info">{{ $c->modulo }}</span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td><strong>{{ $c->especialidad }}</strong></td>
    <td>
        @if ($c->curso && $c->curso > 0)
        <span class="badge badge-muted">Curso {{ $c->curso }}</span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if ($c->created_at)
        {{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y') }}
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="abrirModalEditarClinica({{ $c->id }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button"
                class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.clinicas.eliminar', $c->id) }}"
                data-nombre="{{ $c->especialidad }}"
                data-tabla="{{ route('admin.clinicas.tabla') }}"
                data-tbody="tbodyClinicas"
                data-paginacion="paginacionClinicas"
                data-total="total-clinicas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                Eliminar
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
                <path d="M3 21h18" />
            </svg>
            No se encontraron clínicas con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse