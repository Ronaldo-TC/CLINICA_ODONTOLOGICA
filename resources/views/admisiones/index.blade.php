@extends('layouts.app')

@section('title', 'Gestión de Admisiones')
@section('page-title', 'Admisiones')
@section('page-subtitle', 'Registro, evaluación inicial y derivación de pacientes')

@section('content')

<div class="admisiones-shell">
<section class="admisiones-summary" aria-labelledby="admisiones-summary-title">
    <div class="admisiones-summary-copy">
        <span class="admisiones-summary-icon" aria-hidden="true">
            <x-icono nombre="ficha" />
        </span>
        <div>
            <span class="admisiones-eyebrow">Atención inicial</span>
            <h2 id="admisiones-summary-title">Admisiones de pacientes</h2>
            <p id="crud-total-label">{{ $registros->total() }} admisiones registradas</p>
        </div>
    </div>
    <div class="admisiones-toolbar" aria-label="Acciones de admisiones">
        <x-boton-reporte ruta="admisiones.reporte" />
        <!-- <div class="admision-odontograma-preview">
            <canvas
                id="admision-odontograma-canvas"
                data-model-url="{{ asset('models/odontograma.glb') }}"
                aria-label="Vista previa interactiva del odontograma 3D">
            </canvas>
            <span id="admision-odontograma-status" role="status">Preparando odontograma 3D...</span>
            <button type="button" class="admision-odontograma-expand" id="abrir-odontograma-3d"
                aria-label="Ampliar odontograma 3D">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                    <path d="M16 3h3a2 2 0 0 1 2 2v3"></path>
                    <path d="M21 16v3a2 2 0 0 1-2 2h-3"></path>
                    <path d="M3 16v3a2 2 0 0 0 2 2h3"></path>
                </svg>
                Ampliar
            </button>
        </div> -->
        @canany(['admisiones.crear', 'admisiones.gestionar'])
        <a href="{{ route('admisiones.create') }}" class="btn btn-primary admisiones-primary-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva admisión
        </a>
        @endcanany
    </div>
</section>

<div class="modal-backdrop" id="modalOdontograma3D" aria-hidden="true">
    <section class="modal modal-lg admision-odontograma-modal" role="dialog" aria-modal="true"
        aria-labelledby="modalOdontograma3DTitulo">
        <div class="modal-header">
            <div>
                <h3 id="modalOdontograma3DTitulo">Odontograma 3D</h3>
                <p class="admision-odontograma-help">Arrastra el modelo para girarlo y usa la rueda para acercar.</p>
            </div>
            <button type="button" class="modal-close" id="cerrar-odontograma-3d" aria-label="Cerrar odontograma 3D">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body admision-odontograma-modal-body">
            <div class="admision-odontograma-modal-stage" id="odontograma-3d-modal-stage"></div>
        </div>
    </section>
</div>

<section class="card admisiones-table-card">
    <div class="card-header admisiones-card-header">
        <div>
            <span class="admisiones-eyebrow">Directorio clínico</span>
            <h2>Registros de admisión</h2>
        </div>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    <div class="admisiones-filters" aria-label="Filtros de admisiones">
        <div class="admisiones-filter-heading">
            <div>
                <strong>Filtrar registros</strong>
                <span>Encuentra una admisión por paciente, gestión o fecha.</span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiarFiltros">
                Limpiar filtros
            </button>
        </div>

        <div class="admisiones-filter-grid">
            <div class="form-group admisiones-search-field">
                <label for="filtro-buscar">Buscar paciente</label>
                <span class="admisiones-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>
                </span>
                <input type="search" id="filtro-buscar" class="form-control"
                    placeholder="Nombre, CI o motivo de consulta"
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label for="filtro-periodo">Periodo académico</label>
                <select id="filtro-periodo" class="form-control">
                    <option value="">Todos los periodos</option>
                    @foreach ($periodos as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->nombre }} ({{ $p->gestion }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-desde">Desde</label>
                <input type="date" id="filtro-desde" class="form-control">
            </div>

            <div class="form-group">
                <label for="filtro-hasta">Hasta</label>
                <input type="date" id="filtro-hasta" class="form-control">
            </div>
        </div>
    </div>

    <div class="table-wrap admisiones-table-wrap">
        <table class="admisiones-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Periodo</th>
                    <th>Motivo</th>
                    <th>Áreas derivadas</th>
                    <th>Registrado por</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyAdmisiones">
                @include('admisiones.partials.filas')
            </tbody>
        </table>
    </div>

    <div class="admisiones-pagination" id="paginacionWrap">{{ $registros->links() }}</div>
</section>
</div>


@endsection

@push('styles')
<style>
    .admisiones-shell {
        display: grid;
        gap: 18px;
    }

    .admisiones-summary {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        overflow: hidden;
        padding: 22px 24px;
        border: 1px solid color-mix(in srgb, var(--teal) 20%, var(--line));
        border-radius: var(--radius-lg);
        background:
            radial-gradient(circle at 8% 12%, color-mix(in srgb, var(--teal) 12%, transparent), transparent 36%),
            linear-gradient(135deg, var(--surface), color-mix(in srgb, var(--surface) 91%, var(--teal)));
        box-shadow: var(--shadow-card);
    }

    .admisiones-summary::after {
        position: absolute;
        top: -64px;
        right: -32px;
        width: 190px;
        height: 190px;
        border: 30px solid color-mix(in srgb, var(--teal) 7%, transparent);
        border-radius: 50%;
        content: '';
        pointer-events: none;
    }

    .admisiones-summary-copy,
    .admisiones-toolbar {
        position: relative;
        z-index: 1;
    }

    .admisiones-summary-copy {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
    }

    .admisiones-summary-icon {
        display: grid;
        flex: 0 0 52px;
        width: 52px;
        height: 52px;
        place-items: center;
        border-radius: 15px;
        background: linear-gradient(145deg, var(--teal), var(--teal-dark));
        color: #fff;
        box-shadow: 0 10px 22px color-mix(in srgb, var(--teal) 25%, transparent);
    }

    .admisiones-summary-icon svg {
        width: 25px;
        height: 25px;
    }

    .admisiones-eyebrow {
        display: block;
        margin-bottom: 4px;
        color: var(--teal);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .admisiones-summary h2,
    .admisiones-card-header h2 {
        margin: 0;
        color: var(--ink);
    }

    .admisiones-summary h2 {
        font-size: clamp(19px, 2vw, 24px);
    }

    .admisiones-summary p {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 13px;
    }

    .admisiones-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .admisiones-table-card {
        overflow: hidden;
    }

    .admisiones-card-header {
        min-height: 70px;
    }

    .admisiones-filters {
        padding: 18px 20px 20px;
        border-bottom: 1px solid var(--line);
        background: color-mix(in srgb, var(--surface-soft) 58%, var(--surface));
    }

    .admisiones-filter-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 15px;
    }

    .admisiones-filter-heading strong,
    .admisiones-filter-heading span {
        display: block;
    }

    .admisiones-filter-heading strong {
        color: var(--ink);
        font-size: 14px;
    }

    .admisiones-filter-heading span {
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
    }

    .admisiones-filter-grid {
        display: grid;
        grid-template-columns: minmax(260px, 1.6fr) minmax(180px, 1fr) minmax(145px, .7fr) minmax(145px, .7fr);
        gap: 12px;
        align-items: end;
    }

    .admisiones-filter-grid .form-group {
        margin: 0;
    }

    .admisiones-search-field {
        position: relative;
    }

    .admisiones-search-field .form-control {
        padding-left: 39px;
    }

    .admisiones-input-icon {
        position: absolute;
        bottom: 12px;
        left: 13px;
        display: grid;
        width: 17px;
        height: 17px;
        place-items: center;
        color: var(--muted);
        pointer-events: none;
    }

    .admisiones-input-icon svg {
        width: 100%;
        height: 100%;
    }

    .admisiones-table {
        min-width: 1040px;
    }

    .admisiones-table th:last-child,
    .admisiones-table td:last-child {
        width: 184px;
    }

    .admisiones-id code {
        display: inline-flex;
        min-width: 30px;
        justify-content: center;
        padding: 4px 7px;
        border: 1px solid var(--line);
        border-radius: 7px;
        background: var(--surface-soft);
        color: var(--teal-dark);
        font-weight: 700;
    }

    .admisiones-patient {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 190px;
    }

    .admisiones-patient-avatar {
        width: 34px;
        height: 34px;
        font-size: 11px;
    }

    .admisiones-patient-copy {
        min-width: 0;
    }

    .admisiones-patient-copy strong,
    .admisiones-patient-copy small {
        display: block;
    }

    .admisiones-patient-copy strong {
        overflow: hidden;
        color: var(--ink);
        font-size: 13px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admisiones-patient-copy small,
    .admisiones-muted,
    .admisiones-date,
    .admisiones-area {
        color: var(--muted);
    }

    .admisiones-patient-copy small {
        margin-top: 3px;
        font-size: 11px;
    }

    .admisiones-area {
        max-width: 190px;
        line-height: 1.45;
    }

    .admisiones-row-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: wrap;
    }

    .admisiones-action {
        display: inline-grid;
        width: 34px;
        height: 34px;
        padding: 0;
        place-items: center;
        border: 1px solid var(--line);
        border-radius: 9px;
        background: var(--surface);
        color: var(--muted);
        cursor: pointer;
        transition: border-color var(--transition-fast), background var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
    }

    .admisiones-action:hover,
    .admisiones-action:focus-visible {
        border-color: color-mix(in srgb, var(--teal) 45%, var(--line));
        background: color-mix(in srgb, var(--teal) 9%, var(--surface));
        color: var(--teal-dark);
        transform: translateY(-1px);
    }

    .admisiones-action:focus-visible {
        outline: none;
        box-shadow: var(--focus-ring);
    }

    .admisiones-action svg {
        width: 16px;
        height: 16px;
    }

    .admisiones-action--primary {
        border-color: color-mix(in srgb, var(--teal) 28%, var(--line));
        background: color-mix(in srgb, var(--teal) 12%, var(--surface));
        color: var(--teal-dark);
    }

    .admisiones-action--danger {
        border-color: var(--danger-border);
        color: var(--danger);
    }

    .admisiones-action--danger:hover,
    .admisiones-action--danger:focus-visible {
        border-color: var(--danger-border);
        background: var(--danger-bg);
        color: var(--danger);
    }

    .admisiones-sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }

    .admisiones-pagination {
        padding: 14px 20px;
        border-top: 1px solid var(--line);
        background: var(--surface);
    }

    .admision-odontograma-preview {
        position: relative;
        width: 250px;
        height: 112px;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: var(--radius-md);
        background: radial-gradient(ellipse at center, #293b48, #101b25);
    }

    .admision-odontograma-expand {
        position: absolute;
        top: 7px;
        right: 7px;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border: 1px solid rgba(255, 255, 255, 0.55);
        border-radius: 7px;
        background: rgba(16, 27, 37, 0.82);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .admision-odontograma-expand:hover {
        background: #087f79;
    }

    .admision-odontograma-expand svg {
        width: 13px;
        height: 13px;
    }

    .admision-odontograma-modal {
        width: min(1100px, 100%);
        max-width: 1100px !important;
        max-height: calc(100dvh - 40px);
    }

    .admision-odontograma-help {
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
    }

    .admision-odontograma-modal-body {
        display: flex;
        padding: 12px;
        overflow: hidden;
        background: #101b25;
    }

    .admision-odontograma-modal-stage {
        position: relative;
        width: 100%;
        height: min(68dvh, 680px);
        overflow: hidden;
        border-radius: var(--radius-md);
        background: radial-gradient(ellipse at center, #293b48, #101b25);
    }

    .admision-odontograma-modal-stage .admision-odontograma-preview {
        width: 100%;
        height: 100%;
        border: 0;
        border-radius: 0;
    }

    .admision-odontograma-modal-stage .admision-odontograma-expand {
        display: none;
    }

    #admision-odontograma-canvas {
        display: block;
        width: 100%;
        height: 100%;
        cursor: grab;
        touch-action: none;
    }

    #admision-odontograma-canvas:active {
        cursor: grabbing;
    }

    #admision-odontograma-status {
        position: absolute;
        right: 8px;
        bottom: 7px;
        left: 8px;
        color: #fff;
        font-size: 11px;
        line-height: 1.3;
        text-align: center;
        text-shadow: 0 1px 3px #000;
        pointer-events: none;
    }

    @media (max-width: 640px) {
        .admisiones-summary {
            align-items: stretch;
            padding: 18px;
        }

        .admisiones-summary,
        .admisiones-filter-heading {
            flex-direction: column;
        }

        .admisiones-toolbar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            justify-content: stretch;
        }

        .admisiones-toolbar .btn {
            justify-content: center;
            width: 100%;
        }

        .admisiones-primary-action {
            grid-column: 1 / -1;
            grid-row: 1;
        }

        .admisiones-filters {
            padding: 16px;
        }

        .admisiones-filter-heading .btn {
            align-self: flex-start;
        }

        .admisiones-filter-grid {
            grid-template-columns: 1fr;
        }

        .admision-odontograma-preview {
            width: min(100%, 250px);
        }

        .admision-odontograma-modal {
            max-height: calc(100dvh - 20px);
        }

        .admision-odontograma-modal-stage {
            height: 58dvh;
        }
    }

    @media (min-width: 641px) and (max-width: 1050px) {
        .admisiones-summary {
            align-items: flex-start;
            flex-direction: column;
        }

        .admisiones-toolbar {
            justify-content: flex-start;
        }

        .admisiones-filter-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@push('scripts')
@vite('public/js/odontograma-3d.js')
@endpush

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('admisiones.tabla')),
            store: @json(route('admisiones.store')),
            datos: (id) => `/admisiones/${id}/datos`,
            update: (id) => `/admisiones/${id}`,
            destroy: (id) => `/admisiones/${id}`,
        };

        const $tbody = document.getElementById('tbodyAdmisiones');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroPeriodo = document.getElementById('filtro-periodo');
        const $filtroDesde = document.getElementById('filtro-desde');
        const $filtroHasta = document.getElementById('filtro-hasta');
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
            if ($filtroPeriodo?.value) params.set('periodo_id', $filtroPeriodo.value);
            if ($filtroDesde?.value) params.set('desde', $filtroDesde.value);
            if ($filtroHasta?.value) params.set('hasta', $filtroHasta.value);
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
                $totalLabel.textContent = `${data.total} admisiones registradas`;

                setStatus('idle');
            } catch (err) {
                console.error('[Admisiones] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state"><p>Error al cargar admisiones.</p></div>
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
        $filtroPeriodo?.addEventListener('change', () => cargarTabla(1));
        $filtroDesde?.addEventListener('change', () => cargarTabla(1));
        $filtroHasta?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroPeriodo.value = '';
            $filtroDesde.value = '';
            $filtroHasta.value = '';
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


            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'esta admisión';

            switch (action) {
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
            }
        });

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

                toast('success', data.message || 'Admisión eliminada.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Admisiones] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('admision:guardada', () => {
            cargarTabla(1);
        });

        window.AdmisionesLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush
