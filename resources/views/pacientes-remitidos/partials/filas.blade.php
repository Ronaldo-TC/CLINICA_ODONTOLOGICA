@forelse ($items as $remision)
<tr>
    <td><code>{{ $remision->id }}</code></td>
    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">{{ $remision->paciente?->iniciales ?? '??' }}</div>
            <div>
                <strong>{{ $remision->paciente?->nombre_completo ?? '—' }}</strong>
                <br>
                <small class="table-value" style="color:var(--muted);">CI: {{ $remision->paciente?->ci ?? '—' }}</small>
            </div>
        </div>
    </td>
    <td><span class="badge badge-info">{{ $remision->clinica?->especialidad ?? '—' }}</span></td>
    <td>{{ $remision->periodo?->nombre_completo ?? 'Sin periodo' }}</td>
    <td>{{ $remision->docente?->nombre_completo ?? 'Sin asignar' }}</td>
    <td>{{ $remision->estudiante?->nombre_completo ?? 'Sin asignar' }}</td>
    <td><span class="badge badge-info">{{ $remision->historias_clinicas_count ?? 0 }}</span></td>
    <td><span class="badge badge-info">{{ $remision->insumos_solicitados_count ?? 0 }}</span></td>
    <td>
        @if ($remision->estado === 'asignada')
        <span class="badge badge-info">Asignada</span>
        @else
        <span class="badge badge-warning">Pendiente de asignación</span>
        @endif
    </td>
    <td>
        <div class="actions">
            @if ($remision->habilitado && $remision->estado === 'pendiente' && ($remision->historias_clinicas_count ?? 0) === 0)
            @canany(['remisiones.asignar', 'remisiones.gestionar'])
            <button type="button"
                class="btn btn-sm btn-primary"
                data-asignar-estudiante
                data-url="{{ route('pacientes-remitidos.asignacion', $remision->id) }}"
                data-remision="{{ $remision->id }}">
                <x-icono nombre="asignar" /> Asignar estudiante
            </button>
            @endcanany
            @endif
            @if ($remision->admision_id)
            @canany(['admisiones.ver', 'admisiones.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.show', $remision->admision_id) }}"><x-icono nombre="ficha" /> Ver ficha</a>
            @endcanany
            @endif
            @if (($remision->historias_clinicas_count ?? 0) > 0)
            @canany(['historias.ver', 'historias.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('historias.index', ['buscar' => $remision->paciente?->ci, 'clinica_id' => $remision->clinica_id, 'periodo_id' => $remision->periodo_id]) }}"><x-icono nombre="historia" /> Historias</a>
            @endcanany
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="10">
        <div class="empty-state"><p>No hay pacientes remitidos para las especialidades y filtros seleccionados.</p></div>
    </td>
</tr>
@endforelse
