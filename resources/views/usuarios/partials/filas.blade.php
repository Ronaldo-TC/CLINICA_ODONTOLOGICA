@forelse ($items as $usuario)
<tr data-id="{{ $usuario->id }}">
    <td>
        <code>{{ $usuario->ci }}</code>
    </td>

    <td>
        <strong>{{ $usuario->nombre_usuario }}</strong>
        @if ($usuario->multilogin)
        <span class="badge badge-info" title="Permite multilogin">ML</span>
        @endif
    </td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="user-avatar-wrap">
                <span class="avatar" aria-hidden="true" style="width:32px;height:32px;font-size:12px;">
                    {{ $usuario->initials }}
                </span>
                @if ($usuario->foto)
                <img class="avatar"
                    src="{{ asset('storage/' . $usuario->foto) }}"
                    alt=""
                    loading="lazy"
                    style="width:32px;height:32px;"
                    onerror="this.remove();">
                @endif
            </div>
            <span>{{ $usuario->nombre_completo }}</span>
        </div>
    </td>

    <td>
        @forelse ($usuario->roles as $rol)
        <span class="badge badge-info">{{ $rol->name }}</span>
        @empty
        <span class="badge badge-muted">Sin rol</span>
        @endforelse
    </td>

    <td>
        @if ($usuario->habilitado)
        <span class="badge badge-success">Activo</span>
        @else
        <span class="badge badge-danger">Inactivo</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @can('usuarios.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $usuario->id }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m16 3 5 5-12 12H4v-5L16 3Z" />
                </svg>
                Editar
            </button>
            @endcan

            @can('usuarios.editar')
            @if (!$usuario->habilitado)
            <button type="button"
                class="btn btn-sm btn-primary"
                data-action="habilitar"
                data-id="{{ $usuario->id }}"
                data-nombre="{{ $usuario->nombre_completo }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m5 12 4 4L19 6" />
                </svg>
                Habilitar
            </button>
            @endif
            @endcan

            @can('usuarios.eliminar')
            @if ($usuario->habilitado && $usuario->id !== auth()->id())
            <button type="button"
                class="btn btn-sm btn-warning"
                data-action="deshabilitar"
                data-id="{{ $usuario->id }}"
                data-nombre="{{ $usuario->nombre_completo }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="m6 6 12 12" />
                </svg>
                Deshabilitar
            </button>
            @endif

            @if ($usuario->id !== auth()->id())
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $usuario->id }}"
                data-nombre="{{ $usuario->nombre_completo }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v7M14 10v7" />
                </svg>
                Eliminar
            </button>
            @endif
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
            </svg>
            <p>No hay usuarios registrados</p>
        </div>
    </td>
</tr>
@endforelse
