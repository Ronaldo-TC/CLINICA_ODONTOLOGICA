@forelse ($pacientes as $p)
@php
$edad = $p->fecha_nacimiento
? \Carbon\Carbon::parse($p->fecha_nacimiento)->age
: null;
@endphp
<tr>
    <td>{{ $p->id }}</td>
    <td><code>{{ $p->ci }}</code></td>
    <td>
        <strong>{{ $p->nombre }} {{ $p->apellido_paterno }} {{ $p->apellido_materno }}</strong>
    </td>
    <td>
        @if ($p->genero === 'Masculino')
        <span class="badge badge-info">Masculino</span>
        @elseif ($p->genero === 'Femenino')
        <span class="badge badge-muted" style="background: rgba(217,70,239,0.12); color: #a21caf;">Femenino</span>
        @else
        <span class="badge badge-muted">{{ $p->genero ?? '—' }}</span>
        @endif
    </td>
    <td>
        @if ($edad !== null)
        <span class="badge badge-muted">{{ $edad }} años</span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if ($p->fecha_nacimiento)
        {{ \Carbon\Carbon::parse($p->fecha_nacimiento)->format('d/m/Y') }}
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="abrirModalEditarPaciente({{ $p->id }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button"
                class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.pacientes.eliminar', $p->id) }}"
                data-nombre="{{ $p->nombre }} {{ $p->apellido_paterno }}"
                data-tabla="{{ route('admin.pacientes.tabla') }}"
                data-tbody="tbodyPacientes"
                data-paginacion="paginacionPacientes"
                data-total="total-pacientes">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                Eliminar
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            No se encontraron pacientes con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse