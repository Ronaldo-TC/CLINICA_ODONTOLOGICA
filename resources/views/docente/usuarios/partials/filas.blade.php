@forelse ($usuarios as $u)
<tr>
    <td>{{ $u->id }}</td>
    <td><code>{{ $u->ci }}</code></td>
    <td><strong>{{ $u->nombre_usuario }}</strong></td>
    <td>{{ $u->nombre_completo }}</td>

    {{-- Tipo --}}
    <td>
        @php
        $tipo = trim((string) ($u->tipo ?? ''));
        if ($tipo === '') {
        $tipo = is_string($u->roles ?? null) ? trim($u->roles) : '';
        }
        @endphp
        <span class="badge badge-secondary">{{ $tipo !== '' ? ucfirst($tipo) : '—' }}</span>
    </td>

    {{-- Roles --}}
    <td>
        @php
        $listaRoles = $u->roles ?? [];
        if (is_string($listaRoles)) {
        $listaRoles = array_filter(array_map('trim', explode(',', $listaRoles)));
        }
        @endphp

        @forelse ($listaRoles as $rol)
        <span class="badge badge-info">
            {{ is_object($rol) ? $rol->nombre : $rol }}
        </span>
        @empty
        <span class="text-muted" style="font-size:12px;">Sin rol</span>
        @endforelse
    </td>

    {{-- Estado --}}
    <td>
        @if ($u->habilitado)
        <span class="badge badge-success">Habilitado</span>
        @else
        <span class="badge badge-danger">Deshabilitado</span>
        @endif
    </td>

    {{-- Último login --}}
    <td>
        @if ($u->ultimo_login)
        {{ \Carbon\Carbon::parse($u->ultimo_login)->format('d/m/Y H:i') }}
        @else
        <span class="text-muted">Nunca</span>
        @endif
    </td>

    {{-- Acciones --}}
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button" class="btn btn-secondary btn-sm"
                onclick="abrirModalEditar('{{ $u->id }}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.usuarios.eliminar', $u->id) }}"
                data-nombre="{{ $u->nombre_usuario }}"
                data-tabla="{{ route('admin.usuarios.tabla') }}"
                data-tbody="tbodyUsuarios"
                data-paginacion="paginacionUsuarios"
                data-total="total-usuarios">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                </svg>
                Deshabilitar
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="9">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            No se encontraron usuarios con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse