@forelse ($insumos as $i)
@php
$bajoStock = $i->cantidad_numerica <= $i->stock_minimo;
    @endphp
    <tr @class(['fila-alerta'=> $bajoStock])>
        <td>{{ $i->id }}</td>
        <td><strong>{{ $i->nombre }}</strong></td>
        <td>
            <span class="badge badge-muted">{{ $i->tipo_insumo ?? '—' }}</span>
        </td>
        <td>
            <span class="badge badge-info">{{ $i->clinica ?? '—' }}</span>
        </td>
        <td>
            @if ($bajoStock)
            <span class="badge badge-danger" title="Bajo stock">
                {{ number_format($i->cantidad_numerica, 2) }}
            </span>
            @else
            <span class="badge badge-success">
                {{ number_format($i->cantidad_numerica, 2) }}
            </span>
            @endif
        </td>
        <td>
            <span class="badge badge-muted">{{ number_format($i->stock_minimo, 2) }}</span>
        </td>
        <td>
            <strong>Bs {{ number_format($i->costo_unitario, 2) }}</strong>
        </td>
        <td>
            <div class="actions" style="justify-content:flex-end;">
                <button type="button"
                    class="btn btn-secondary btn-sm"
                    onclick="abrirModalEditarInsumo({{ $i->id }})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Editar
                </button>
            </div>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="8">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21" />
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                    <line x1="12" y1="22.08" x2="12" y2="12" />
                </svg>
                No se encontraron insumos con los filtros aplicados.
            </div>
        </td>
    </tr>
    @endforelse