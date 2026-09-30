@forelse ($cajas as $c)
@php
$saldo = ($c->costo_total ?? 0) - ($c->cancelado ?? 0);
@endphp
<tr>
    <td>{{ $c->id }}</td>
    <td><code>#{{ $c->recibo }}</code></td>
    <td>
        <strong>{{ $c->paciente_nombre_completo ?? '—' }}</strong>
        @if ($c->ci)
        <br><small class="text-muted">CI: {{ $c->ci }}</small>
        @endif
    </td>
    <td>
        @if ($c->tipo_pago === 'Efectivo')
        <span class="badge badge-success">Efectivo</span>
        @elseif ($c->tipo_pago === 'QR')
        <span class="badge badge-info">QR</span>
        @elseif ($c->tipo_pago === 'Transferencia')
        <span class="badge badge-muted" style="background: rgba(217,70,239,0.12); color: #a21caf;">Transferencia</span>
        @elseif ($c->tipo_pago === 'Tarjeta')
        <span class="badge badge-warning">Tarjeta</span>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td><strong>Bs {{ number_format($c->costo_total ?? 0, 2) }}</strong></td>
    <td><span class="badge badge-success">Bs {{ number_format($c->cancelado ?? 0, 2) }}</span></td>
    <td>
        @if ($saldo > 0)
        <span class="badge badge-danger">Bs {{ number_format($saldo, 2) }}</span>
        @else
        <span class="badge badge-muted">Bs 0.00</span>
        @endif
    </td>
    <td>
        @if ($c->created_at)
        {{ $c->created_at->format('d/m/Y H:i') }}
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <div class="actions" style="justify-content:flex-end;">
            <button type="button"
                class="btn btn-secondary btn-sm"
                onclick="verDetalleCaja({{ $c->id }})"
                title="Ver detalle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Ver
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
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                <line x1="1" y1="10" x2="23" y2="10" />
            </svg>
            No se encontraron movimientos con los filtros aplicados.
        </div>
    </td>
</tr>
@endforelse