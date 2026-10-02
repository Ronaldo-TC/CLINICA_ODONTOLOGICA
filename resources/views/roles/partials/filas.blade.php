@forelse ($items as $rol)
@php
$esProtegido = in_array($rol->name, ['admin', 'subadmin'], true);
@endphp

<tr data-id="{{ $rol->id }}">
    <td>
        <strong>{{ $rol->name }}</strong>
        @if ($esProtegido)
        <span class="badge badge-warning">Sistema</span>
        @endif
    </td>

    <td>
        <span style="color:var(--muted);">
            {{ $rol->descripcion ?: '—' }}
        </span>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $rol->permissions_count ?? $rol->permissions()->count() }} permisos
        </span>
    </td>

    <td>
        <span class="badge badge-muted">
            {{ $rol->users_count ?? $rol->users()->count() }} usuarios
        </span>
    </td>

    <td>
        @if ($rol->habilitado)
        <span class="badge badge-success">Activo</span>
        @else
        <span class="badge badge-danger">Inactivo</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @can('roles.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $rol->id }}">
                Editar
            </button>
            @endcan

            @can('roles.editar')
            @if (!$esProtegido)
            <button type="button"
                class="btn btn-sm btn-primary"
                data-action="permisos"
                data-id="{{ $rol->id }}"
                data-nombre="{{ $rol->name }}">
                Permisos
            </button>
            @endif
            @endcan

            @can('roles.eliminar')
            @if (!$esProtegido)
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $rol->id }}"
                data-nombre="{{ $rol->name }}">
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
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
            <p>No hay roles registrados</p>
        </div>
    </td>
</tr>
@endforelse