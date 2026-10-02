@forelse ($items as $est)
<tr data-id="{{ $est->id }}">
    <td><code>{{ $est->id }}</code></td>

    <td>
        <span class="badge badge-info">
            {{ $est->clinica?->especialidad ?? '—' }}
        </span>
    </td>

    <td>
        <small>{{ $est->estudiante?->nombre_completo ?? '—' }}</small>
    </td>

    <td>
        <small>{{ $est->usuario?->nombre_completo ?? '—' }}</small>
    </td>

    <td>
        <span class="badge badge-muted">{{ $est->cantidad_sobres }}</span>
    </td>

    <td>
        <span class="badge badge-muted">{{ $est->cantidad_paquetes }}</span>
    </td>

    <td>
        <small>
            @if ($est->fecha_entrega)
            {{ $est->fecha_entrega->format('d/m/Y') }}
            @else
            —
            @endif
            @if ($est->fecha_recojo)
            → {{ $est->fecha_recojo->format('d/m/Y') }}
            @endif
        </small>
    </td>

    <td>
        @if ($est->habilitada)
        <span class="badge badge-success">Habilitada</span>
        @else
        <span class="badge badge-danger">Deshabilitada</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @can('esterilizaciones.editar')
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $est->id }}">
                Editar
            </button>
            @endcan

            @can('esterilizaciones.eliminar')
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $est->id }}"
                data-nombre="Esterilización #{{ $est->id }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="9">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 2v6M15 2v6M6 8h12v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8z" />
                <path d="M9 14h6" />
            </svg>
            <p>No hay registros de esterilización</p>
        </div>
    </td>
</tr>
@endforelse