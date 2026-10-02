@forelse ($items as $insumo)
<tr data-id="{{ $insumo->id }}">
    <td><strong>{{ $insumo->nombre }}</strong></td>
    <td><span class="badge badge-info">{{ $insumo->insumoTratamiento?->nombre ?? '—' }}</span></td>
    <td>{{ $insumo->clinica?->especialidad ?? '—' }}</td>
    <td>
        <strong>{{ number_format($insumo->cantidad_numerica, 2) }}</strong>
        <small style="color:var(--muted);">{{ $insumo->cantidad }}</small>
        <div style="font-size:11px;color:var(--muted);">Mín: {{ number_format($insumo->stock_minimo, 2) }}</div>
    </td>
    <td>{{ number_format($insumo->costo_unitario, 2) }} Bs</td>
    <td><strong>{{ number_format($insumo->cantidad_numerica * $insumo->costo_unitario, 2) }}</strong> Bs</td>
    <td>
        @if ($insumo->agotado)
        <span class="badge badge-danger">Agotado</span>
        @elseif ($insumo->stock_bajo)
        <span class="badge badge-warning">Stock Bajo</span>
        @else
        <span class="badge badge-success">OK</span>
        @endif
    </td>
    <td>
        <div class="actions">
            @can('insumos.editar')
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $insumo->id }}">Editar</button>
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="ajustar" data-id="{{ $insumo->id }}"
                data-nombre="{{ $insumo->nombre }}">± Stock</button>
            @endcan
            @can('insumos.eliminar')
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar" data-id="{{ $insumo->id }}"
                data-nombre="{{ $insumo->nombre }}">Eliminar</button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <p>No hay insumos registrados</p>
        </div>
    </td>
</tr>
@endforelse