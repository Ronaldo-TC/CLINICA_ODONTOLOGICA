@forelse ($usuarios as $u)
<tr>
    <td>{{ $u->id }}</td>
    <td><code>{{ $u->ci }}</code></td>
    <td><strong>{{ $u->nombre_usuario }}</strong></td>
    <td>{{ $u->nombre_completo }}</td>
    <td>
        @php
        $tipo = trim((string) ($u->tipo ?? ''));
        if ($tipo === '') {
        $tipo = is_string($u->roles ?? null) ? trim($u->roles) : '';
        }
        @endphp
        <span class="badge badge-secondary">{{ $tipo !== '' ? ucfirst($tipo) : '—' }}</span>
    </td>
    <td>
        @php
        // Esto evita el error: Argumento foreach debe ser array|object, string dado
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
    <td>
        @if ($u->habilitado)
        <span class="badge badge-success">Habilitado</span>
        @else
        <span class="badge badge-danger">Deshabilitado</span>
        @endif
    </td>
    <td>
        @if ($u->ultimo_login)
        {{ \Carbon\Carbon::parse($u->ultimo_login)->format('d/m/Y H:i') }}
        @else
        <span class="text-muted">Nunca</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="abrirModalEditar('{{ $u->id }}')">
                <x-icono nombre="editar" /> Editar
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.usuarios.eliminar', $u->id) }}"
                data-nombre="{{ $u->nombre_usuario }}"
                data-tabla="{{ route('admin.usuarios.tabla') }}"
                data-tbody="tbodyUsuarios"
                data-paginacion="paginacionUsuarios"
                data-total="total-usuarios">
                <x-icono nombre="deshabilitar" /> Deshabilitar
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state" [STRIPPED 27 bytes]"0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
            No se encontraron usuarios con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse
