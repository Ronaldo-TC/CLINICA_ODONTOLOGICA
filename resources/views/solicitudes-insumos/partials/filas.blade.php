@forelse ($items as $sol)
<tr data-id="{{ $sol->id }}">
    <td><code>{{ $sol->id }}</code></td>
    <td>
        <strong>{{ $sol->estudiante?->nombre_completo ?? '—' }}</strong>
    </td>
    <td>
        <span class="badge badge-info">{{ $sol->clinica?->especialidad ?? '—' }}</span>
    </td>
    <td>
        @if ($sol->pieza)
        <span class="badge badge-muted">Pieza {{ $sol->pieza }}</span>
        @else
        —
        @endif
    </td>
    <td>
        <span class="badge badge-info">{{ $sol->detalles_count ?? 0 }}</span>
    </td>
    <td>
        @php
        $colores = [
        'pendiente' => 'warning',
        'autorizado' => 'info',
        'entregado' => 'success',
        'rechazado' => 'danger',
        'cancelado' => 'muted',
        ];
        $color = $colores[$sol->estado] ?? 'muted';
        @endphp
        <span class="badge badge-{{ $color }}">{{ ucfirst($sol->estado) }}</span>
    </td>
    <td>
        <small>{{ $sol->docente?->nombre_completo ?? '—' }}</small>
    </td>
    <td>
        <small>{{ $sol->created_at?->format('d/m/Y') }}</small>
    </td>
    <td>
        <div class="actions">
            @if (in_array($sol->estado, ['pendiente', 'rechazado'], true))
            @can('insumos.editar')
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $sol->id }}">Editar</button>
            @endcan
            @endif

            @if ($sol->estado === 'pendiente')
            @can('insumos.editar')
            <button type="button" class="btn btn-sm btn-primary"
                data-action="autorizar" data-id="{{ $sol->id }}">Autorizar</button>
            <button type="button" class="btn btn-sm btn-danger"
                data-action="rechazar" data-id="{{ $sol->id }}">Rechazar</button>
            @endcan
            @endif

            @if ($sol->estado === 'autorizado')
            @can('insumos.gestionar')
            <button type="button" class="btn btn-sm btn-primary"
                data-action="entregar" data-id="{{ $sol->id }}">Entregar</button>
            @endcan
            @endif

            @if (in_array($sol->estado, ['pendiente', 'rechazado'], true))
            @can('insumos.eliminar')
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar" data-id="{{ $sol->id }}">Eliminar</button>
            @endcan
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="9">
        <div class="empty-state">
            <p>No hay solicitudes</p>
        </div>
    </td>
</tr>
@endforelse