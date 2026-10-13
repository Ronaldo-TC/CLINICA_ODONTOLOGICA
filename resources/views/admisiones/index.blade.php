@extends('layouts.app')

@section('title', 'Gestión de Admisiones')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} admisiones registradas</span>
    </div>
    <div class="toolbar-actions">
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
        @can('admisiones.crear')
        <a href="{{ route('admisiones.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva Admisión
        </a>
        @endcan
        @canany(['admisiones.crear', 'admisiones.gestionar'])
        <a href="{{ route('admisiones.create-3d') }}" class="btn btn-primary">
            Nueva admisión 3D
        </a>
        @endcanany
    </div>
</div>

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

<div class="card">
    <div class="card-header">
        <h2>Admisiones de Pacientes</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    placeholder="Paciente, CI o motivo..."
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label>Periodo</label>
                <select id="filtro-periodo" class="form-control">
                    <option value="">Todos los periodos</option>
                    @foreach ($periodos as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->nombre }} ({{ $p->gestion }}-{{ $p->semestre }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Desde</label>
                <input type="date" id="filtro-desde" class="form-control">
            </div>

            <div class="form-group">
                <label>Hasta</label>
                <input type="date" id="filtro-hasta" class="form-control">
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-wrap">
        <table>
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

    <div id="paginacionWrap"></div>
</div>


@endsection

@push('styles')
<style>
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
