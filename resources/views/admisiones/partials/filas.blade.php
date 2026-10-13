@forelse ($items as $admision)
<tr data-id="{{ $admision->id }}">
    <td>
        <code>{{ $admision->id }}</code>
    </td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">
                {{ strtoupper(mb_substr($admision->paciente?->nombre ?? '?', 0, 2)) }}
            </div>
            <div>
                <strong>{{ $admision->paciente?->nombre_completo ?? '—' }}</strong>
                <br>
                <small class="table-value" style="color:var(--muted);">
                    CI: {{ $admision->paciente?->ci ?? '—' }}
                </small>
            </div>
        </div>
    </td>

    <td>
        @if ($admision->periodo)
        <span class="badge badge-info">
            {{ $admision->periodo->gestion }}-{{ $admision->periodo->semestre }}
        </span>
        @else
        <span style="color:var(--muted);">—</span>
        @endif
    </td>

    <td>
        {{ $admision->motivoConsulta?->nombre ?? '—' }}
    </td>

    <td>
        {{ $admision->area_derivada ?: 'Sin áreas derivadas' }}
    </td>

    <td>
        {{ $admision->registradoPor?->nombre_completo ?? $admision->estudiante?->nombre_completo ?? '—' }}
    </td>

    <td>
        <small>{{ $admision->created_at?->format('d/m/Y') }}</small>
    </td>

    <td>
        <div class="actions">
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.ficha-pdf', ['admision' => $admision->id, 'formato' => 'pdf']) }}"><x-icono nombre="pdf" /> PDF de la ficha</a>
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.reporte-paciente', ['admision' => $admision->id, 'formato' => 'pdf']) }}"><x-icono nombre="pdf" /> PDF del paciente</a>
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.reporte-paciente', $admision->id) }}" target="_blank" rel="noopener"><x-icono nombre="reporte" /> Reporte del paciente</a>
            <a class="btn btn-sm btn-secondary" href="{{ route('admisiones.show', $admision->id) }}"><x-icono nombre="ficha" /> Ver ficha</a>
            @can('admisiones.editar')
            <a href="{{ route('admisiones.edit', $admision->id) }}" class="btn btn-sm btn-secondary">
                <x-icono nombre="editar" /> Editar
            </a>
            @endcan

            @can('admisiones.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $admision->id }}"
                data-nombre="Admisión #{{ $admision->id }}">
                <x-icono nombre="eliminar" /> Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
            </svg>
            <p>No hay admisiones registradas</p>
        </div>
    </td>
</tr>
@endforelse
