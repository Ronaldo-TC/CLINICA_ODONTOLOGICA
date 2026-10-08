@forelse ($items as $caja)
@php
$canceladoAcumulado = (float) ($caja->cancelado_acumulado ?? ($caja->habilitada ? $caja->cancelado : 0));
$saldo = (float) $caja->costo_total - $canceladoAcumulado;
$estadoPago = $canceladoAcumulado <= 0
    ? 'pendiente'
    : ($canceladoAcumulado >= $caja->costo_total ? 'pagado' : 'parcial');
    @endphp

    <tr data-id="{{ $caja->id }}">
        <td>
            @if ($caja->recibo)
            <code>#{{ $caja->recibo }}</code>
            @else
            <span style="color:var(--muted);">—</span>
            @endif
        </td>

        <td>
            <strong>{{ $caja->paciente_nombre_completo ?: '—' }}</strong>
            <br>
            <small style="color:var(--muted);">
                CI: {{ $caja->ci ?: '—' }}
            </small>
            <br><small>Ficha #{{ $caja->tratamientoRealizado?->historiaClinica?->admision_id }} · {{ $caja->tratamientoRealizado?->historiaClinica?->estudiante?->nombre_completo }}</small>
        </td>

        <td>
            <small>{{ $caja->tratamiento?->nombre ?? '—' }}</small>
        </td>

        <td>
            <span class="badge badge-info">
                {{ $caja->clinica?->especialidad ?? '—' }}
            </span>
        </td>

        <td>
            <strong>{{ number_format($caja->costo_total, 2) }}</strong> Bs
        </td>

        <td>
            {{ number_format($caja->cancelado, 2) }} Bs
        </td>

        <td>{{ number_format($canceladoAcumulado, 2) }} Bs</td>

        <td>
            @if ($saldo > 0)
            <span style="color:var(--danger); font-weight:700;">
                {{ number_format($saldo, 2) }} Bs
            </span>
            @else
            <span style="color:var(--success); font-weight:700;">
                0.00 Bs
            </span>
            @endif
        </td>

        <td>
            @if ($estadoPago === 'pagado')
            <span class="badge badge-success">Pagado</span>
            @elseif ($estadoPago === 'parcial')
            <span class="badge badge-warning">Parcial</span>
            @else
            <span class="badge badge-danger">Pendiente</span>
            @endif
        </td>

        <td>
            <div class="actions">
                @can('cajas.editar')
                <button type="button" class="btn btn-sm btn-secondary"
                    data-action="editar" data-id="{{ $caja->id }}">
                    Editar
                </button>
                @endcan

                @can('cajas.eliminar')
                <button type="button" class="btn btn-sm btn-danger"
                    data-action="eliminar"
                    data-id="{{ $caja->id }}"
                    data-nombre="Caja #{{ $caja->id }}">
                    Eliminar
                </button>
                @endcan
            </div>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="10">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="6" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                    <circle cx="12" cy="15" r="2" />
                </svg>
                <p>No hay cajas registradas</p>
            </div>
        </td>
    </tr>
    @endforelse
