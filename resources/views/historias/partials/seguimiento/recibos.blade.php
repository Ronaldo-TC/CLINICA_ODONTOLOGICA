<div class="seguimiento-summary">
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Historia</span><span class="seguimiento-summary__value">#{{ $registro->id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Ficha</span><span class="seguimiento-summary__value">#{{ $registro->admision_id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Recibos activos</span><span class="seguimiento-summary__value">{{ $pagos->count() }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Paciente</span><span class="seguimiento-summary__value">{{ $registro->paciente?->nombre_completo }}</span></div>
</div>

<div class="table-wrap seguimiento-table-wrap">
    <table class="seguimiento-table">
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Tratamiento</th>
                <th>Pagado por esta historia</th>
                <th>Tipo de pago</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagos as $pago)
                <tr>
                    <td><span class="seguimiento-table__primary">#{{ $pago->recibo }}</span></td>
                    <td>
                        @if($pago->detalles->isNotEmpty())
                            @foreach($pago->detalles as $detalle)
                                <span class="seguimiento-table__primary">{{ $detalle->tratamientoRealizado?->tratamiento?->nombre ?? $detalle->concepto }}</span>
                            @endforeach
                        @else
                            <span class="seguimiento-table__primary">{{ $pago->tratamiento?->nombre ?? $pago->descripcion }}</span>
                        @endif
                    </td>
                    <td class="table-value"><strong>Bs {{ number_format($pago->detalles->isNotEmpty() ? $pago->detalles->sum('monto') : $pago->cancelado, 2) }}</strong></td>
                    <td><span class="badge badge-info">{{ ucfirst($pago->tipo_pago) }}</span></td>
                    <td>{{ $pago->created_at?->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="seguimiento-empty-cell">No hay recibos activos registrados para esta historia.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
