@forelse ($historias as $h)
<tr>
    <td>{{ $h->id }}</td>

    <td><strong>{{ trim(($h->paciente_nombre ?? '') . ' ' . ($h->apellido_paterno ?? '') . ' ' . ($h->apellido_materno ?? '')) ?: 'Sin paciente' }}</strong></td>

    <td><span class="badge badge-info">{{ $h->clinica ?? '—' }}</span></td>

    <td>{{ $h->periodo ?? '—' }}</td>

    <td>{{ $h->estudiante ?? '—' }}</td>

    <td>{{ $h->docente ?? '—' }}</td>

    <td>
        @php
        $estado = $h->estado ?? 'abierta';
        $mapaEstado = [
        'abierta' => 'badge-info',
        'en_revision' => 'badge-warning',
        'autorizada' => 'badge-success',
        'cerrada' => 'badge-secondary',
        ];
        $badge = $mapaEstado[$estado] ?? 'badge-secondary';
        @endphp
        <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
    </td>

    <td>
        @if ($h->autorizado)
        <span class="badge badge-success">Si</span>
        @else
        <span class="badge badge-danger">No</span>
        @endif
    </td>

    <td>
        @if ($h->fecha_inicio)
        {{ \Carbon\Carbon::parse($h->fecha_inicio)->format('d/m/Y') }}
        @else
        <span class="text-muted">-</span>
        @endif
    </td>

    <td>
        @unless (request()->routeIs('director.*'))
        <div class="actions" style="justify-content:flex-end;">
            @if (!$h->autorizado)
            <button type="button" class="btn btn-secondary btn-sm"
                onclick="abrirModalEditar('{{ $h->id }}')" title="Editar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Editar
            </button>

            <button type="button" class="btn btn-primary btn-sm"
                onclick="autorizarHistoria('{{ $h->id }}')" title="Autorizar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                Autorizar
            </button>
            @else
            @if ($h->estado !== 'cerrada')
            <button type="button" class="btn btn-secondary btn-sm"
                onclick="cerrarHistoria('{{ $h->id }}')" title="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                Cerrar
            </button>
            @else
            <span class="badge badge-secondary">Cerrada</span>
            @endif
            @endif

            <button type="button" class="btn btn-secondary btn-sm"
                onclick="verHistorialPaciente('{{ $h->paciente_id }}')" title="Ver historial del paciente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Historial
            </button>
        </div>
        @else
        <span class="text-muted">Consulta</span>
        @endunless
    </td>
</tr>
@empty
<tr>
    <td colspan="10">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
            </svg>
            No se encontraron historias clinicas con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse