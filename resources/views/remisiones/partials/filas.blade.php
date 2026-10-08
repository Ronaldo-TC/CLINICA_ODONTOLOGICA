@forelse ($items as $remision)
<tr data-id="{{ $remision->id }}">
    <td><code>{{ $remision->id }}</code></td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">
                {{ $remision->paciente?->iniciales ?? '??' }}
            </div>
            <div>
                <strong>{{ $remision->paciente?->nombre_completo ?? '—' }}</strong>
                <br>
                <small style="color:var(--muted);">CI: {{ $remision->paciente?->ci ?? '—' }}</small>
            </div>
        </div>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $remision->clinica?->especialidad ?? '—' }}
        </span>
    </td>

    <td>
        @if ($remision->periodo)
        <span class="badge badge-muted">{{ $remision->periodo->nombre_completo }}</span>
        @else
        —
        @endif
    </td>

    <td>{{ $remision->docente?->nombre_completo ?? 'Sin asignar' }}</td>

    <td>{{ $remision->estudiante?->nombre_completo ?? 'Sin asignar' }}</td>

    <td>
        <span class="badge badge-info">{{ $remision->historias_clinicas_count ?? 0 }}</span>
    </td>

    <td>
        <span class="badge badge-info">{{ $remision->insumos_solicitados_count ?? 0 }}</span>
    </td>

    <td>
        @if ($remision->estado === 'asignada')
        <span class="badge badge-info">Asignada</span>
        @else
        <span class="badge badge-warning">Pendiente de asignación</span>
        @endif
        <br>
        @if ($remision->habilitado)
        <small style="color:var(--muted);">Habilitada</small>
        @else
        <span class="badge badge-danger">Inhabilitada</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @if ($remision->admision_id)
            @canany(['admisiones.ver', 'admisiones.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.show', $remision->admision_id) }}">Ver ficha</a>
            @endcanany
            @endif
            @if (($remision->historias_clinicas_count ?? 0) > 0)
            @canany(['historias.ver', 'historias.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('historias.index', ['buscar' => $remision->paciente?->ci, 'clinica_id' => $remision->clinica_id, 'periodo_id' => $remision->periodo_id]) }}">Historias</a>
            @endcanany
            @endif
            @canany(['remisiones.editar', 'remisiones.gestionar'])
            <button type="button" class="btn btn-sm btn-secondary"
                data-action="editar" data-id="{{ $remision->id }}">
                Editar
            </button>
            @endcanany

            @canany(['remisiones.eliminar', 'remisiones.gestionar'])
            <button type="button" class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $remision->id }}"
                data-nombre="Remisión #{{ $remision->id }}">
                Eliminar
            </button>
            @endcanany
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="10">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="17 1 21 5 17 9" />
                <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                <polyline points="7 23 3 19 7 15" />
                <path d="M21 13v2a4 4 0 0 1-4 4H3" />
            </svg>
            <p>No hay remisiones registradas</p>
        </div>
    </td>
</tr>
@endforelse
