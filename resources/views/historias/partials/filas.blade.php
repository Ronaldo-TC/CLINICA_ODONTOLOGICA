@forelse ($items as $historia)
<tr data-id="{{ $historia->id }}">
    <td>
        <code>{{ $historia->id }}</code>
    </td>

    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar" style="width:32px;height:32px;font-size:12px;">
                {{ $historia->paciente?->iniciales ?? '??' }}
            </div>
            <div>
                <strong>{{ $historia->paciente?->nombre_completo ?? '—' }}</strong>
                <br>
                <small style="color:var(--muted);">
                    CI: {{ $historia->paciente?->ci ?? '—' }}
                </small>
            </div>
        </div>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $historia->clinica?->especialidad ?? '—' }}
        </span>
    </td>

    <td>
        @if ($historia->periodo)
        <span class="badge badge-muted">
            {{ $historia->periodo->gestion }}-{{ $historia->periodo->semestre }}
        </span>
        @else
        —
        @endif
    </td>

    <td>
        <small>{{ $historia->estudiante?->nombre_completo ?? '—' }}</small>
    </td>

    <td>
        <small>{{ $historia->docente?->nombre_completo ?? '—' }}</small>
    </td>

    <td>
        <span class="badge badge-info">
            {{ $historia->tratamientos_realizados_count ?? 0 }}
        </span>
        <a class="btn btn-sm btn-secondary" href="{{ route('historias.show', $historia->id) }}#tratamientos">Ver tratamientos</a>
    </td>

    <td>
        @canany(['cajas.ver', 'cajas.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('historias.show', $historia->id) }}#cajas">Ver recibos</a>
        @else
            <small>Sin acceso</small>
        @endcanany
    </td>
    <td>
        @canany(['insumos.ver', 'insumos.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('historias.show', $historia->id) }}#insumos">Ver solicitudes</a>
        @else
            <small>Sin acceso</small>
        @endcanany
    </td>
    <td>
        @canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
            <a class="btn btn-sm btn-secondary" href="{{ route('historias.show', $historia->id) }}#esterilizaciones">Ver controles</a>
        @else
            <small>Sin acceso</small>
        @endcanany
    </td>

    <td>
        @if ($historia->autorizado)
        <span class="badge badge-success">Autorizado</span>
        @elseif ($historia->estado_revision)
        <span class="badge badge-warning">En revisión</span>
        @else
        <span class="badge badge-muted">Pendiente</span>
        @endif
    </td>

    <td>
        <div class="actions">
            @if(!$historia->autorizado) @canany(['historias.autorizar','historias.gestionar'])<form method="POST" action="{{ route('historias.autorizar', $historia->id) }}">@csrf<button class="btn btn-sm btn-primary">Autorizar historia</button></form>@endcanany @endif
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="archivos"
                data-id="{{ $historia->id }}"
                title="Ver archivos adjuntos">
                Archivos
            </button>

            @can('historias.editar')
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="editar"
                data-id="{{ $historia->id }}">
                Editar
            </button>
            @endcan

            @can('historias.eliminar')
            <button type="button"
                class="btn btn-sm btn-danger"
                data-action="eliminar"
                data-id="{{ $historia->id }}"
                data-nombre="Historia #{{ $historia->id }}">
                Eliminar
            </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="12">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
            </svg>
            <p>No hay historias clínicas registradas</p>
        </div>
    </td>
</tr>
@endforelse
