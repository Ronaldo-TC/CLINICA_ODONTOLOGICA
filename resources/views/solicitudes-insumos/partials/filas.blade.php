@forelse ($items as $sol)
<tr data-id="{{ $sol->id }}">
    <td><code>{{ $sol->id }}</code></td>
    <td>
        <strong>{{ $sol->estudiante?->nombre_completo ?? '—' }}</strong>
        <br><small>{{ $sol->historiaClinica?->paciente?->nombre_completo }} · Ficha #{{ $sol->historiaClinica?->admision_id }}</small>
    </td>
    <td>
        <span class="badge badge-info">{{ $sol->clinica?->especialidad ?? '—' }}</span>
        <br><small>{{ $sol->tratamiento?->nombre ?? $sol->tratamientoRealizado?->tratamiento?->nombre }}</small>
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
        'aprobado' => 'success',
        'parcial' => 'info',
        'entregado' => 'success',
        'rechazado' => 'danger',
        'cancelado' => 'muted',
        ];
        $color = $colores[$sol->estado] ?? 'muted';
        @endphp
        <span class="badge badge-{{ $color }}">{{ ['pendiente' => 'Pendiente de docente', 'autorizado' => 'Pendiente de Insumos', 'aprobado' => 'Aprobada por Insumos'][$sol->estado] ?? ucfirst($sol->estado) }}</span>
    </td>
    <td>
        <small>{{ $sol->docente?->nombre_completo ?? '—' }}</small>
    </td>
    <td>
        <small>{{ $sol->created_at?->format('d/m/Y') }}</small>
    </td>
    <td>
        <div class="actions">
            <a class="btn btn-sm btn-secondary" href="{{ route('solicitudes-insumos.show', $sol->id) }}">Ver solicitud</a>
            @if (in_array($sol->estado, ['pendiente', 'rechazado'], true))
            @if((int) $sol->usuario_id === auth()->id() || auth()->user()->hasAnyRole(['admin', 'subadmin']))
            @canany(['insumos.editar', 'insumos.gestionar'])
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $sol->id }}">Editar</button>
            @endcanany
            @endif
            @endif

            @if ($sol->estado === 'pendiente')
            @can('insumos.autorizar')
            <button type="button" class="btn btn-sm btn-primary"
                data-action="autorizar" data-id="{{ $sol->id }}">Autorizar</button>
            <button type="button" class="btn btn-sm btn-danger"
                data-action="rechazar" data-id="{{ $sol->id }}">Rechazar</button>
            @endcan
            @endif

            @if ($sol->estado === 'autorizado' && auth()->user()->hasAnyRole(['insumos', 'admin', 'subadmin']))
            @canany(['insumos.aprobar', 'insumos.gestionar'])
            <button type="button" class="btn btn-sm btn-primary" data-action="aprobar" data-id="{{ $sol->id }}">Aprobar en Insumos</button>
            @endcanany
            @endif
            @if(in_array($sol->estado, ['autorizado', 'aprobado'], true) && auth()->user()->hasAnyRole(['insumos', 'admin', 'subadmin']))
            @canany(['insumos.rechazar', 'insumos.gestionar'])
            <button type="button" class="btn btn-sm btn-danger" data-action="rechazar" data-id="{{ $sol->id }}">Rechazar en Insumos</button>
            @endcanany
            @endif
            @if (in_array($sol->estado, ['aprobado','parcial'], true) || ($sol->estado === 'autorizado' && $sol->sesion_atencion_id))
            @can('insumos.entregar')
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
