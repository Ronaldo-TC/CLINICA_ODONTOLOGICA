@forelse ($items as $admision)
<tr data-id="{{ $admision->id }}">
    <td class="admisiones-id">
        <code>{{ $admision->id }}</code>
    </td>

    <td>
        <div class="admisiones-patient">
            <div class="avatar admisiones-patient-avatar" aria-hidden="true">
                {{ strtoupper(mb_substr($admision->paciente?->nombre ?? '?', 0, 2)) }}
            </div>
            <div class="admisiones-patient-copy">
                <strong>{{ $admision->paciente?->nombre_completo ?? '—' }}</strong>
                <small class="table-value">
                    CI: {{ $admision->paciente?->ci ?? '—' }}
                </small>
            </div>
        </div>
    </td>

    <td>
        @if ($admision->periodo)
        <span class="badge badge-info">
            {{ $admision->periodo->gestion }}
        </span>
        @else
        <span class="admisiones-muted">—</span>
        @endif
    </td>

    <td>
        {{ $admision->motivoConsulta?->nombre ?? '—' }}
    </td>

    <td class="admisiones-area">
        {{ $admision->area_derivada ?: 'Sin áreas derivadas' }}
    </td>

    <td>
        {{ $admision->registradoPor?->nombre_completo ?? $admision->estudiante?->nombre_completo ?? '—' }}
    </td>

    <td class="admisiones-date">
        <small>{{ $admision->created_at?->format('d/m/Y') }}</small>
    </td>

    <td class="admisiones-actions-cell">
        <div class="admisiones-row-actions" aria-label="Acciones de la admisión {{ $admision->id }}">
            <a class="admisiones-action admisiones-action--primary" href="{{ route('admisiones.show', $admision->id) }}" title="Ver ficha" aria-label="Ver ficha">
                <x-icono nombre="ficha" /><span class="admisiones-sr-only">Ver ficha</span>
            </a>
            <a class="admisiones-action" href="{{ route('admisiones.ficha-pdf', ['admision' => $admision->id, 'formato' => 'pdf']) }}" title="PDF de la ficha" aria-label="PDF de la ficha">
                <x-icono nombre="pdf" /><span class="admisiones-sr-only">PDF de la ficha</span>
            </a>
            <a class="admisiones-action" href="{{ route('admisiones.reporte-paciente', ['admision' => $admision->id, 'formato' => 'pdf']) }}" title="PDF del paciente" aria-label="PDF del paciente">
                <x-icono nombre="pdf" /><span class="admisiones-sr-only">PDF del paciente</span>
            </a>
            <a class="admisiones-action" href="{{ route('admisiones.reporte-paciente', $admision->id) }}" target="_blank" rel="noopener" title="Reporte del paciente" aria-label="Reporte del paciente">
                <x-icono nombre="reporte" /><span class="admisiones-sr-only">Reporte del paciente</span>
            </a>
            @can('admisiones.editar')
            <a href="{{ route('admisiones.edit', $admision->id) }}" class="admisiones-action" title="Editar admisión" aria-label="Editar admisión">
                <x-icono nombre="editar" /><span class="admisiones-sr-only">Editar</span>
            </a>
            @endcan

            @can('admisiones.eliminar')
            <button type="button"
                class="admisiones-action admisiones-action--danger"
                data-action="eliminar"
                data-id="{{ $admision->id }}"
                data-nombre="Admisión #{{ $admision->id }}"
                title="Eliminar admisión"
                aria-label="Eliminar admisión">
                <x-icono nombre="eliminar" /><span class="admisiones-sr-only">Eliminar</span>
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
