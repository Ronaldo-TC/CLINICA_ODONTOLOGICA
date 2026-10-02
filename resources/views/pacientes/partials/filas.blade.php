@forelse ($items as $paciente)
<tr data-id="{{ $paciente->id }}">
    <td>
        @if ($paciente->ci)
        <code>{{ $paciente->ci }}</code>
        @else
        <span style="color:var(--muted);">—</span>
        @endif
    </td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">
                {{ $paciente->iniciales }}
            </div>
            <strong>{{ $paciente->nombre_completo }}</strong>
        </div>
    </td>

    <td>
        @if ($paciente->genero)
        <span class="badge badge-muted">{{ $paciente->genero }}</span>
        @else
        <span style="color:var(--muted);">—</span>
        @endif
    </td>

    <td>
        @if ($paciente->edad !== null)
        {{ $paciente->edad }} años
        @else
        <span style="color:var(--muted);">—</span>
        @endif
    </td>

    <td>
        <span class="badge badge-info">{{ $paciente->admisiones_count ?? 0 }}</span>
    </td>

    <td>
        <span class="badge badge-info">{{ $paciente->historias_clinicas_count ?? 0 }}</span>
    </td>

    <td>
        <div class="actions">
            @can('pacientes.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $paciente->id }}">
                Editar
            </button>
            @endcan

            @can('pacientes.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $paciente->id }}"
                data-nombre="{{ $paciente->nombre_completo }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            <p>No hay pacientes registrados</p>
        </div>
    </td>
</tr>
@endforelse