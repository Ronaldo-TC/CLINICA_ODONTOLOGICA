@forelse ($items as $paciente)
@php
$pagado = (float) ($paciente->total_pagado ?? 0);
$costo  = (float) ($paciente->total_costo ?? 0);
$saldo  = $costo - $pagado;
@endphp
<tr data-id="{{ $paciente->id }}">
    <td>
        @if ($paciente->ci)
        <code>{{ $paciente->ci }}</code>
        @else
        <span style="color:var(--muted);">—</span>
        @endif
    </td>

    <td>
        <strong>{{ $paciente->nombre_completo }}</strong>
    </td>

    <td>
        <span class="badge badge-info">{{ $paciente->total_admisiones ?? 0 }}</span>
    </td>

    <td>
        <span class="badge badge-info">{{ $paciente->total_historias ?? 0 }}</span>
    </td>

    <td>
        <span class="badge badge-muted">{{ $paciente->total_tratamientos ?? 0 }}</span>
    </td>

    <td class="table-value">
        <strong>{{ number_format($pagado, 2) }} Bs</strong>
    </td>

    <td>
        @if ($saldo > 0)
        <span class="badge badge-warning">{{ number_format($saldo, 2) }} Bs</span>
        @else
        <span class="badge badge-success">{{ number_format($saldo, 2) }} Bs</span>
        @endif
    </td>

    <td>
        <div class="actions">
            <button type="button"
                class="btn btn-sm btn-secondary"
                data-action="detalle"
                data-id="{{ $paciente->id }}"
                data-nombre="{{ $paciente->nombre_completo }}">
                <x-icono nombre="ver" /> Ver detalle
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
            </svg>
            <p>No hay pacientes con movimientos registrados</p>
        </div>
    </td>
</tr>
@endforelse
