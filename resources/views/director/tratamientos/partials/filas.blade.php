@forelse ($tratamientos as $t)
<tr>
    <td>{{ $t->id }}</td>
    <td><strong>{{ $t->nombre }}</strong></td>
    <td>
        <span class="badge badge-info">{{ $t->clinica_nombre ?? '—' }}</span>
    </td>
    <td>
        <strong>Bs {{ number_format($t->costo ?? 0, 2) }}</strong>
    </td>
    <td>
        @if ($t->porcentaje_pg)
        <span class="badge badge-muted">{{ $t->porcentaje_pg }}%</span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if ($t->descripcion)
        <span title="{{ $t->descripcion }}">
            {{ \Illuminate\Support\Str::limit($t->descripcion, 40) }}
        </span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @unless (request()->routeIs('director.*'))
        <div class="actions" style="justify-content:flex-end;">
            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="abrirModalEditarTratamiento({{ $t->id }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button"
                class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.tratamientos.eliminar', $t->id) }}"
                data-nombre="{{ $t->nombre }}"
                data-tabla="{{ route('admin.tratamientos.tabla') }}"
                data-tbody="tbodyTratamientos"
                data-paginacion="paginacionTratamientos"
                data-total="total-tratamientos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                Eliminar
            </button>
        </div>
        @else
        <span class="text-muted">Solo lectura</span>
        @endunless
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
            No se encontraron tratamientos con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse