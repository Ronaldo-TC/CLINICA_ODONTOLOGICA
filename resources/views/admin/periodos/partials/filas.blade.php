@forelse ($periodos as $p)
<tr>
    <td>{{ $p->id }}</td>
    <td><strong>{{ $p->nombre }}</strong></td>
    <td>
        <span class="badge badge-info">{{ $p->gestion }}</span>
    </td>
    <td>
        <span class="badge badge-muted">Semestre {{ $p->semestre }}</span>
    </td>
    <td>
        @if ($p->fecha_inicio)
        {{ \Carbon\Carbon::parse($p->fecha_inicio)->format('d/m/Y') }}
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if ($p->fecha_fin)
        {{ \Carbon\Carbon::parse($p->fecha_fin)->format('d/m/Y') }}
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if ($p->habilitado)
        <span class="badge badge-success">Habilitado</span>
        @else
        <span class="badge badge-danger">Deshabilitado</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="abrirModalEditarPeriodo({{ $p->id }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button"
                class="btn btn-warning btn-sm"
                onclick="toggleHabilitadoPeriodo({{ $p->id }}, {{ $p->habilitado ? 'true' : 'false' }})"
                title="{{ $p->habilitado ? 'Deshabilitar' : 'Habilitar' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    @if ($p->habilitado)
                    <circle cx="12" cy="12" r="10" />
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                    @else
                    <path d="M20 6L9 17l-5-5" />
                    @endif
                </svg>
                {{ $p->habilitado ? 'Deshabilitar' : 'Habilitar' }}
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            No se encontraron periodos con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse