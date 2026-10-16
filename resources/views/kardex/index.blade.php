@extends('layouts.app')

@section('title', 'Kardex de pacientes')

@section('page-title', 'Kardex')

@section('page-subtitle', 'Historial consolidado de pacientes: admisiones, historias, tratamientos y pagos')

@section('content')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value">{{ $stats['pacientes'] }}</span>
            <span class="stat-label">Pacientes con movimientos</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                <line x1="1" y1="10" x2="23" y2="10" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value">{{ number_format($stats['total_pagado'], 2) }} Bs</span>
            <span class="stat-label">Total pagado</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value">{{ number_format($stats['saldo_pendiente'], 2) }} Bs</span>
            <span class="stat-label">Saldo por cobrar</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon fuchsia">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value">{{ $stats['total_tratamientos'] }}</span>
            <span class="stat-label">Tratamientos realizados</span>
        </div>
    </div>
</div>

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} pacientes con movimientos</span>
    </div>
    <div class="toolbar-actions">
        <span class="badge badge-muted">Solo lectura</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Historial consolidado</h2>
        <span class="tabla-status" id="crud-status">
            <span class="dot"></span>
            <span id="crud-status-text">Listo</span>
        </span>
    </div>

    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtro-buscar">Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    placeholder="Nombre o CI..." autocomplete="off">
            </div>

            <div class="form-group">
                <label for="filtro-periodo">Periodo</label>
                <select id="filtro-periodo" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}">
                        {{ $periodo->nombre }} ({{ $periodo->gestion }}-{{ $periodo->semestre }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-clinica">Clínica</label>
                <select id="filtro-clinica" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($clinicas as $clinica)
                    <option value="{{ $clinica->id }}">{{ $clinica->especialidad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="btnLimpiarKardex">&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarKardex">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Paciente</th>
                    <th>Admisiones</th>
                    <th>Historias</th>
                    <th>Tratamientos</th>
                    <th>Total pagado</th>
                    <th>Saldo pendiente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyKardex">
                @include('kardex.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap">{{ $registros->links() }}</div>
</div>

@include('kardex.partials.modal')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('kardex.tabla')),
            datos: (id) => `/kardex/${id}/datos`,
        };

        const $tbody = document.getElementById('tbodyKardex');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroPeriodo = document.getElementById('filtro-periodo');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $btnLimpiar = document.getElementById('btnLimpiarKardex');

        let debounceTimer = null;
        let cargando = false;

        /* ---------------- Estado de la interfaz ---------------- */
        function setStatus(estado, texto) {
            if (!$status) return;
            $status.classList.toggle('visible', estado !== 'idle');
            if ($statusText && texto) $statusText.textContent = texto;
        }

        /* ---------------- Carga de la tabla ---------------- */
        async function cargarTabla(page = 1) {
            if (cargando) return;
            cargando = true;
            setStatus('loading', 'Cargando...');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroPeriodo?.value) params.set('periodo_id', $filtroPeriodo.value);
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params.toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                if ($totalLabel) {
                    $totalLabel.textContent = `${data.total} pacientes con movimientos`;
                }
                setStatus('idle');
            } catch (err) {
                console.error('[Kardex] Error al cargar la tabla:', err);
                $tbody.innerHTML = `
                    <tr><td colspan="8">
                        <div class="empty-state"><p>Error al cargar el kardex.</p></div>
                    </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                cargando = false;
            }
        }

        /* ---------------- Filtros ---------------- */
        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroPeriodo?.addEventListener('change', () => cargarTabla(1));
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            if ($filtroBuscar) $filtroBuscar.value = '';
            if ($filtroPeriodo) $filtroPeriodo.value = '';
            if ($filtroClinica) $filtroClinica.value = '';
            cargarTabla(1);
        });

        /* ---------------- Detalle en modal ---------------- */
        document.addEventListener('click', async (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                cargarTabla(parseInt(url.searchParams.get('page') || '1', 10));
                return;
            }

            const boton = e.target.closest('[data-action="detalle"]');
            if (!boton) return;

            const id = boton.dataset.id;
            setStatus('loading', 'Cargando detalle...');

            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                KardexDetalle.abrir(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Kardex] Error al cargar el detalle:', err);
                setStatus('error', 'Error al cargar el detalle');
            }
        });

        window.KardexLista = { cargar: cargarTabla };
    })();
</script>
@endpush
