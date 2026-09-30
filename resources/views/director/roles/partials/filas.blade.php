@forelse ($roles as $r)
<tr>
    <td>{{ $r->id }}</td>
    <td><strong>{{ $r->nombre }}</strong></td>
    <td>{{ $r->descripcion ?? '—' }}</td>
    <td>
        <span class="badge badge-info">{{ $r->usuarios_count ?? 0 }} usuarios</span>
    </td>
    <td>
        <span class="badge badge-muted">{{ $r->permisos_count ?? 0 }} permisos</span>
    </td>
    <td>
        @if ($r->habilitado)
        <span class="badge badge-success">Habilitado</span>
        @else
        <span class="badge badge-danger">Deshabilitado</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <a href="{{ route('admin.roles.permisos', $r->id) }}"
                class="btn btn-secondary btn-sm" title="Gestionar permisos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
                Permisos
            </a>

            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="abrirModalEditarRol('{{ $r->id }}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button"
                class="btn btn-danger btn-sm btn-deshabilitar"
                data-url="{{ route('admin.roles.eliminar', $r->id) }}"
                data-nombre="{{ $r->nombre }}"
                data-tabla="{{ route('admin.roles.tabla') }}"
                data-tbody="tbodyRoles"
                data-callback="recargarTablaRoles"
                @disabled(!$r->habilitado)>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
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
    <td colspan="7">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            </svg>
            No hay roles registrados.
        </div>
    </td>
</tr>
@endforelse