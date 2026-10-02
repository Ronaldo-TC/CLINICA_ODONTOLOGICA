@extends('layouts.app')

@section('title', 'Solicitudes de Insumos')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $stats['pendientes'] + $stats['autorizadas'] + $stats['entregadas'] }} solicitudes</span>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('insumos.index') }}" class="btn btn-secondary">📦 Ver Catálogo</a>
        @can('insumos.crear')
        <button type="button" class="btn btn-primary" id="btnNuevaSolicitud">
            + Nueva Solicitud
        </button>
        @endcan
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon amber"><svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 6v6l4 2" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['pendientes'] }}</div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><svg viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['autorizadas'] }}</div>
            <div class="stat-label">Autorizadas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon fuchsia"><svg viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['entregadas'] }}</div>
            <div class="stat-label">Entregadas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['rechazadas'] }}</div>
            <div class="stat-label">Rechazadas</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Solicitudes de Insumos</h2>
        <span class="tabla-status" id="crud-status"><span class="dot"></span><span id="crud-status-text">Cargando...</span></span>
    </div>

    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control" placeholder="Estudiante o pieza..." autocomplete="off">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select id="filtro-estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="autorizado">Autorizadas</option>
                    <option value="entregado">Entregadas</option>
                    <option value="rechazado">Rechazadas</option>
                </select>
            </div>
            <div class="form-group">
                <label>Clínica</label>
                <select id="filtro-clinica" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">Limpiar</button>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th>Clínica</th>
                    <th>Pieza</th>
                    <th>Items</th>
                    <th>Estado</th>
                    <th>Docente</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodySolicitudes">
                @include('solicitudes-insumos.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

@include('solicitudes-insumos.partials.modal')
@include('solicitudes-insumos.partials.modal-entregar')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const BASE = @json(url('solicitudes-insumos'));

        const URLS = {
            tabla: @json(route('solicitudes-insumos.tabla')),
            store: @json(route('solicitudes-insumos.store')),
            datos: (id) => `${BASE}/${id}/datos`,
            update: (id) => `${BASE}/${id}`,
            destroy: (id) => `${BASE}/${id}`,
            autorizar: (id) => `${BASE}/${id}/autorizar`,
            rechazar: (id) => `${BASE}/${id}/rechazar`,
            entregar: (id) => `${BASE}/${id}/entregar`,
        };

        const $tbody = document.getElementById('tbodySolicitudes');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroEstado = document.getElementById('filtro-estado');
        const $filtroClinica = document.getElementById('filtro-clinica');
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
            if ($filtroEstado?.value) params.set('estado', $filtroEstado.value);
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} solicitudes`;
                setStatus('idle');
            } catch (err) {
                console.error('[Solicitudes] Error:', err);
                $tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><p>Error al cargar.</p></div></td></tr>`;
                setStatus('error', 'Error');
            } finally {
                $tbody?.classList.remove('loading');
                cargando = false;
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroEstado?.addEventListener('change', () => cargarTabla(1));
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));
        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroEstado.value = '';
            $filtroClinica.value = '';
            cargarTabla(1);
        });

        document.addEventListener('click', async (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                cargarTabla(parseInt(url.searchParams.get('page') || '1', 10));
                return;
            }

            if (e.target.closest('#btnNuevaSolicitud')) {
                SolicitudModal.abrirCrear();
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;

            switch (action) {
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'eliminar':
                    eliminar(id);
                    break;
                case 'autorizar':
                    await accion(id, 'autorizar', '¿Autorizar esta solicitud?');
                    break;
                case 'rechazar':
                    await rechazar(id);
                    break;
                case 'entregar':
                    await abrirEntregar(id);
                    break;
            }
        });

        async function abrirEdicion(id) {
            setStatus('loading', 'Cargando...');
            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                SolicitudModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                toast('error', 'No se pudo cargar.');
                setStatus('idle');
            }
        }

        async function accion(id, endpoint, msg) {
            if (!confirm(msg)) return;
            try {
                const res = await fetch(URLS[endpoint](id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message);
                toast('success', data.message);
                cargarTabla(1);
            } catch (err) {
                toast('error', err.message || 'Error');
            }
        }

        async function rechazar(id) {
            const motivo = prompt('Motivo del rechazo:');
            if (motivo === null) return;
            try {
                const res = await fetch(URLS.rechazar(id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        motivo
                    }),
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message);
                toast('success', data.message);
                cargarTabla(1);
            } catch (err) {
                toast('error', err.message || 'Error');
            }
        }

        async function abrirEntregar(id) {
            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                SolicitudEntregarModal.abrir(data);
            } catch (err) {
                toast('error', 'No se pudo cargar.');
            }
        }

        async function eliminar(id) {
            if (!confirm('¿Eliminar esta solicitud?')) return;
            try {
                const res = await fetch(URLS.destroy(id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message);
                toast('success', data.message);
                cargarTabla(1);
            } catch (err) {
                toast('error', err.message || 'Error');
            }
        }

        window.addEventListener('solicitud:guardada', () => cargarTabla(1));
        window.SolicitudesLista = {
            cargar: cargarTabla,
            toast: toast
        };
    })();
</script>
@endpush