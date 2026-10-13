@forelse ($items as $insumo)
<tr data-id="{{ $insumo->id }}">
    <td><strong>{{ $insumo->nombre }}</strong></td>
    <td>
        @forelse ($insumo->tratamientos as $tratamiento)
            <span class="badge badge-info">{{ $tratamiento->nombre }}</span>
        @empty
            <span class="badge badge-warning">Sin tratamiento asignado</span>
        @endforelse
    </td>
    <td>{{ $insumo->clinica?->especialidad ?? '—' }}</td>
    <td>
        <strong>{{ number_format($insumo->cantidad_numerica, 2) }}</strong>
        <small style="color:var(--muted);">{{ $insumo->cantidad }}</small>
        <div style="font-size:11px;color:var(--muted);">Mín: {{ number_format($insumo->stock_minimo, 2) }}</div>
    </td>
    <td class="table-value">{{ number_format($insumo->costo_unitario, 2) }} Bs</td>
    <td class="table-value"><strong>{{ number_format($insumo->cantidad_numerica * $insumo->costo_unitario, 2) }}</strong> Bs</td>
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
            @canany(['insumos.editar', 'insumos.gestionar'])
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $insumo->id }}"><x-icono nombre="editar" /> Editar</button>
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="ajustar" data-id="{{ $insumo->id }}"
                data-nombre="{{ $insumo->nombre }}"><x-icono nombre="stock" /> ± Stock</button>
            @endcanany
            @canany(['insumos.eliminar', 'insumos.gestionar'])
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar" data-id="{{ $insumo->id }}"
                data-nombre="{{ $insumo->nombre }}"><x-icono nombre="eliminar" /> Eliminar</button>
            @endcanany
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
