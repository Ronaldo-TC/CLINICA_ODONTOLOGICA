@extends('layouts.app')

@section('title', 'Gestión de Pacientes')
@section('page-title', 'Pacientes')
@section('page-subtitle', 'Directorio de atención clínica')

@section('content')

<div class="patients-page">
    <section class="patients-welcome" aria-labelledby="patients-welcome-title">
        <div class="patients-welcome__copy">
            <span class="patients-eyebrow">DIRECTORIO CLÍNICO</span>
            <h2 id="patients-welcome-title">Una atención más cercana empieza aquí</h2>
            <p>Encuentra a cada paciente y continúa su cuidado desde un solo lugar.</p>
            <div class="patients-welcome__count">
                <span class="patients-welcome__count-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
                <span><strong id="crud-total-label">{{ $registros->total() }} pacientes registrados</strong><small>Directorio actualizado</small></span>
            </div>
        </div>
        <div class="patients-welcome__actions">
            <x-boton-reporte ruta="pacientes.reporte" />
            @can('pacientes.crear')
            <button type="button" class="btn btn-primary patients-new-button" id="btnNuevoPaciente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Registrar paciente
            </button>
            @endcan
        </div>
        <svg class="patients-welcome__decoration" viewBox="0 0 220 220" fill="none" aria-hidden="true">
            <circle cx="110" cy="110" r="88" stroke="currentColor" stroke-width="1" />
            <circle cx="110" cy="110" r="64" stroke="currentColor" stroke-width="1" />
            <path d="M110 52c-22 0-38 16-38 39 0 22 13 40 20 57 4 9 6 22 13 22s7-20 12-20 8 20 14 20 10-13 14-23c7-16 19-34 19-56 0-23-16-39-38-39-7 0-13 5-16 5s-8-5-20-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
            <path d="M93 109c3 4 8 6 17 6s14-2 17-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
    </section>

    <section class="patients-directory card" aria-labelledby="patients-directory-title">
        <div class="patients-directory__heading">
            <div>
                <span class="patients-section-label">CUIDADO Y SEGUIMIENTO</span>
                <h2 id="patients-directory-title">Directorio de pacientes</h2>
                <p>Consulta datos básicos y accede rápidamente a la atención.</p>
            </div>
            <span class="patients-live-status" id="crud-status" role="status" aria-live="polite">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>

        <div class="patients-filters">
            <div class="patients-search">
                <label class="sr-only" for="filtro-buscar">Buscar paciente por nombre o CI</label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-4-4" />
                </svg>
                <input type="search" id="filtro-buscar" class="form-control"
                    placeholder="Buscar por nombre o CI..."
                    autocomplete="off">
            </div>
            <label class="sr-only" for="filtro-genero">Filtrar por género</label>
            <select id="filtro-genero" class="form-control patients-gender-filter">
                <option value="">Todos los géneros</option>
                @foreach ($generos as $g)
                <option value="{{ $g }}">{{ $g }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-secondary patients-clear-button" id="btnLimpiarFiltros">
                Limpiar filtros
            </button>
        </div>

        <div class="patients-results">
            <div class="patients-results__heading">
                <span>Personas en seguimiento</span>
                <span class="patients-results__hint">Selecciona una acción para continuar</span>
            </div>
            <div class="patients-table-wrap">
                <table class="patients-table">
                    <thead class="sr-only">
                        <tr>
                            <th>Paciente</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPacientes">
                        @include('pacientes.partials.filas')
                    </tbody>
                </table>
            </div>
            <div class="patients-pagination" id="paginacionWrap">{{ $registros->links() }}</div>
        </div>
    </section>
</div>

@include('pacientes.partials.modal')

@endsection

@push('styles')
<style>
    .patients-page {
        display: grid;
        gap: 22px;
    }

    .patients-welcome {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        min-height: 228px;
        padding: 34px clamp(22px, 4vw, 48px);
        overflow: hidden;
        isolation: isolate;
        border: 1px solid color-mix(in srgb, var(--teal) 18%, var(--line));
        border-radius: 24px;
        background:
            radial-gradient(ellipse at 86% 18%, color-mix(in srgb, var(--cyan) 16%, transparent), transparent 38%),
            linear-gradient(120deg, var(--surface) 5%, color-mix(in srgb, var(--teal) 6%, var(--surface)) 100%);
        box-shadow: var(--shadow-card);
    }

    .patients-welcome__copy,
    .patients-welcome__actions {
        position: relative;
        z-index: 1;
    }

    .patients-welcome__copy {
        max-width: 610px;
    }

    .patients-eyebrow,
    .patients-section-label {
        display: inline-block;
        color: var(--teal);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .14em;
    }

    .patients-welcome h2 {
        max-width: 580px;
        margin-top: 11px;
        color: var(--ink);
        font-size: clamp(25px, 3vw, 36px);
        font-weight: 750;
        letter-spacing: -.045em;
        line-height: 1.15;
    }

    .patients-welcome__copy > p {
        margin-top: 10px;
        color: var(--muted);
        font-size: 14px;
    }

    .patients-welcome__count {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        margin-top: 22px;
    }

    .patients-welcome__count-icon {
        display: grid;
        width: 40px;
        height: 40px;
        place-items: center;
        border: 1px solid color-mix(in srgb, var(--teal) 18%, var(--line));
        border-radius: 13px;
        background: var(--surface);
        color: var(--teal);
    }

    .patients-welcome__count-icon svg {
        width: 20px;
        height: 20px;
    }

    .patients-welcome__count strong,
    .patients-welcome__count small {
        display: block;
    }

    .patients-welcome__count strong {
        color: var(--ink);
        font-size: 13px;
    }

    .patients-welcome__count small {
        margin-top: 1px;
        color: var(--muted);
        font-size: 11px;
    }

    .patients-welcome__actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .patients-new-button {
        min-height: 46px;
        padding-inline: 18px;
        border-radius: 12px;
        box-shadow: 0 8px 20px color-mix(in srgb, var(--teal) 22%, transparent);
    }

    .patients-new-button svg {
        width: 17px;
        height: 17px;
    }

    .patients-welcome__decoration {
        position: absolute;
        z-index: -1;
        right: 16%;
        bottom: -74px;
        width: 220px;
        color: color-mix(in srgb, var(--teal) 12%, transparent);
        transform: rotate(-12deg);
        pointer-events: none;
    }

    .patients-page .patients-directory {
        margin-bottom: 0;
        border-radius: 20px;
        box-shadow: var(--shadow-card);
    }

    .patients-directory__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 23px 26px 20px;
        border-bottom: 1px solid var(--line);
    }

    .patients-directory__heading h2 {
        margin-top: 5px;
        color: var(--ink);
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .patients-directory__heading p {
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
    }

    .patients-live-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 32px;
        padding: 0 11px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--surface-soft);
        color: var(--muted);
        font-size: 11px;
        white-space: nowrap;
    }

    .patients-live-status .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 3px color-mix(in srgb, #22c55e 14%, transparent);
    }

    .patients-filters {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(170px, 220px) auto;
        align-items: center;
        gap: 12px;
        padding: 18px 26px;
        border-bottom: 1px solid var(--line);
        background: color-mix(in srgb, var(--surface-soft) 38%, var(--surface));
    }

    .patients-search {
        position: relative;
        min-width: 0;
    }

    .patients-search > svg {
        position: absolute;
        top: 50%;
        left: 14px;
        width: 17px;
        height: 17px;
        color: var(--muted);
        transform: translateY(-50%);
        pointer-events: none;
    }

    .patients-search .form-control {
        min-height: 44px;
        padding-left: 42px;
        border-radius: 12px;
        background: var(--surface);
    }

    .patients-gender-filter {
        min-height: 44px;
        border-radius: 12px;
    }

    .patients-clear-button {
        min-height: 44px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .patients-results {
        padding: 0 26px 10px;
    }

    .patients-results__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 17px 0 5px;
        color: var(--ink);
        font-size: 12px;
        font-weight: 650;
    }

    .patients-results__hint {
        color: var(--muted);
        font-size: 11px;
        font-weight: 400;
    }

    .patients-table-wrap {
        overflow: visible;
    }

    .patients-table {
        width: 100%;
        min-width: 0;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .patients-table tbody tr.patient-row > td {
        padding: 0;
        border: 0;
    }

    .patient-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 22px;
        padding: 16px 18px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: var(--surface);
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast);
    }

    .patient-card:hover {
        border-color: color-mix(in srgb, var(--teal) 38%, var(--line));
        box-shadow: 0 8px 24px color-mix(in srgb, var(--ink) 7%, transparent);
        transform: translateY(-1px);
    }

    .patient-card__identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .patient-card__avatar {
        width: 48px !important;
        height: 48px !important;
        border: 3px solid color-mix(in srgb, var(--surface) 82%, transparent);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--teal) 18%, var(--line));
        font-size: 14px !important;
    }

    .patient-card__identity-copy {
        min-width: 0;
    }

    .patient-card__name {
        overflow: hidden;
        color: var(--ink);
        font-size: 14px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .patient-card__details {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 5px;
        color: var(--muted);
        font-size: 11px;
    }

    .patient-card__ci {
        color: var(--teal);
        font-weight: 650;
    }

    .patient-card__separator {
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: color-mix(in srgb, var(--muted) 55%, transparent);
    }

    .patient-card__metrics {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .patient-card__metric {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 112px;
        padding: 9px 11px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: color-mix(in srgb, var(--surface-soft) 48%, var(--surface));
    }

    .patient-card__metric-icon {
        display: grid;
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        place-items: center;
        border-radius: 9px;
        background: color-mix(in srgb, var(--cyan) 12%, var(--surface));
        color: var(--teal);
        font-size: 13px;
        font-weight: 750;
    }

    .patient-card__metric strong,
    .patient-card__metric small {
        display: block;
    }

    .patient-card__metric strong {
        color: var(--ink);
        font-size: 12px;
        line-height: 1.2;
    }

    .patient-card__metric small {
        margin-top: 2px;
        color: var(--muted);
        font-size: 9px;
        line-height: 1.2;
    }

    .patient-card__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
    }

    .patient-card__actions .btn {
        min-height: 36px;
        border-radius: 10px;
    }

    .patient-card__actions .patient-card__edit,
    .patient-card__actions .patient-card__delete {
        width: 36px;
        min-width: 36px;
        padding: 0;
    }

    .patient-card__actions .patient-card__delete {
        border: 1px solid color-mix(in srgb, var(--danger) 20%, var(--line));
        background: color-mix(in srgb, var(--danger) 6%, var(--surface));
        color: var(--danger);
    }

    .patients-empty {
        display: grid;
        justify-items: center;
        padding: 44px 20px;
        border: 1px dashed var(--line);
        border-radius: 16px;
        background: color-mix(in srgb, var(--surface-soft) 35%, var(--surface));
        color: var(--muted);
        text-align: center;
    }

    .patients-empty svg {
        width: 40px;
        height: 40px;
        margin-bottom: 12px;
        color: var(--teal);
    }

    .patients-empty strong {
        color: var(--ink);
        font-size: 14px;
    }

    .patients-empty p {
        margin-top: 4px;
        font-size: 12px;
    }

    .patients-pagination {
        padding: 10px 0 4px;
    }

    @media (max-width: 900px) {
        .patients-welcome {
            align-items: flex-start;
        }

        .patients-welcome__decoration {
            right: 8%;
        }

        .patient-card {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
        }

        .patient-card__metrics {
            grid-column: 1;
            grid-row: 2;
        }

        .patient-card__actions {
            grid-column: 2;
            grid-row: 1 / span 2;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 600px) {
        .patients-page {
            gap: 15px;
        }

        .patients-welcome {
            min-height: auto;
            flex-direction: column;
            gap: 20px;
            padding: 24px 20px;
            border-radius: 18px;
        }

        .patients-welcome h2 {
            max-width: 420px;
            font-size: 27px;
        }

        .patients-welcome__copy > p {
            max-width: 300px;
            font-size: 13px;
        }

        .patients-welcome__count {
            margin-top: 16px;
        }

        .patients-welcome__actions {
            width: 100%;
        }

        .patients-welcome__actions > * {
            flex: 1;
            justify-content: center;
        }

        .patients-welcome__decoration {
            right: -62px;
            bottom: 36px;
            width: 170px;
        }

        .patients-directory__heading {
            align-items: flex-start;
            padding: 19px 18px 16px;
        }

        .patients-directory__heading h2 {
            font-size: 17px;
        }

        .patients-live-status {
            padding-inline: 9px;
            font-size: 0;
        }

        .patients-live-status .dot {
            width: 8px;
            height: 8px;
        }

        .patients-filters {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 9px;
            padding: 14px 18px;
        }

        .patients-search {
            grid-column: 1 / -1;
        }

        .patients-gender-filter,
        .patients-clear-button {
            min-width: 0;
            font-size: 12px;
        }

        .patients-results {
            padding: 0 14px 8px;
        }

        .patients-results__hint {
            display: none;
        }

        .patients-table {
            border-spacing: 0 8px;
        }

        .patient-card {
            grid-template-columns: minmax(0, 1fr);
            gap: 13px;
            padding: 14px;
        }

        .patient-card__identity {
            gap: 11px;
        }

        .patient-card__avatar {
            width: 42px !important;
            height: 42px !important;
        }

        .patient-card__metrics {
            grid-column: auto;
            grid-row: auto;
        }

        .patient-card__metric {
            min-width: 0;
            flex: 1;
            padding: 7px;
        }

        .patient-card__actions {
            grid-column: auto;
            grid-row: auto;
            justify-content: flex-start;
            padding-top: 11px;
            border-top: 1px solid var(--line);
        }

        .patient-card__actions .btn-primary {
            flex: 1;
        }

        .patients-pagination {
            overflow-x: auto;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .patient-card {
            transition: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('pacientes.tabla')),
            store: @json(route('pacientes.store')),
            datos: (id) => `/pacientes/${id}/datos`,
            update: (id) => `/pacientes/${id}`,
            destroy: (id) => `/pacientes/${id}`,
        };

        const $tbody = document.getElementById('tbodyPacientes');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroGenero = document.getElementById('filtro-genero');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        let debounceTimer = null;
        let cargando = false;

        function toast(type, message) {
            const colores = {
                success: 'linear-gradient(135deg, #16a34a, #15803d)',
                error: 'linear-gradient(135deg, #dc2626, #b91c1c)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                info: 'linear-gradient(135deg, #06b6d4, #0891b2)',
            };
            const el = document.createElement('div');
            el.textContent = message;
            Object.assign(el.style, {
                position: 'fixed',
                bottom: '24px',
                right: '24px',
                padding: '13px 20px',
                borderRadius: '12px',
                background: colores[type] || colores.info,
                color: '#fff',
                font: '600 13.5px -apple-system, sans-serif',
                boxShadow: '0 10px 32px rgba(0,0,0,0.18)',
                zIndex: '3000',
                opacity: '0',
                transform: 'translateY(20px) scale(0.95)',
                transition: 'all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1)',
                maxWidth: 'min(360px, calc(100vw - 32px))',
                wordBreak: 'break-word',
            });
            document.body.appendChild(el);
            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
            });
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px) scale(0.95)';
                setTimeout(() => el.remove(), 300);
            }, 3200);
        }

        function setStatus(state, text) {
            if (!$status) return;
            if (state === 'idle') {
                $status.classList.remove('visible');
                return;
            }
            $status.classList.add('visible');
            if ($statusText) $statusText.textContent = text || 'Cargando...';
        }

        async function cargarTabla(page = 1) {
            if (cargando) return;
            cargando = true;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroGenero?.value) params.set('genero', $filtroGenero.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} pacientes registrados`;

                setStatus('idle');
            } catch (err) {
                console.error('[Pacientes] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state"><p>Error al cargar pacientes.</p></div>
                </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                $tbody?.classList.remove('loading');
                cargando = false;
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroGenero?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroGenero.value = '';
            cargarTabla(1);
        });

        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                cargarTabla(page);
                return;
            }

            if (e.target.closest('#btnNuevoPaciente')) {
                PacienteModal.abrirCrear();
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'este paciente';

            switch (action) {
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
            }
        });

        async function abrirEdicion(id) {
            setStatus('loading', 'Cargando datos...');

            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                PacienteModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Pacientes] Error:', err);
                toast('error', 'No se pudieron cargar los datos.');
                setStatus('idle');
            }
        }

        async function eliminar(id, nombre) {
            if (!await window.confirmarEliminacion(nombre)) return;

            setStatus('loading', 'Eliminando...');

            try {
                const res = await fetch(URLS.destroy(id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al eliminar.');
                }

                toast('success', data.message || 'Paciente eliminado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Pacientes] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('paciente:guardado', () => {
            cargarTabla(1);
        });

        window.PacientesLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush
