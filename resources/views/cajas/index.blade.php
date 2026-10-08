@extends('layouts.app')

@section('title', 'Caja y Pagos')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} registros de caja</span>
    </div>
    <div class="toolbar-actions">
        <x-boton-reporte ruta="cajas.reporte" />
        @can('cajas.crear')
        <button type="button" class="btn btn-primary" id="btnNuevaCaja">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nuevo recibo
        </button>
        @endcan
    </div>
</div>

@can('tratamientos.precios')
@include('cajas.partials.precios')
@endcan

<div class="card">
    <div class="card-header">
        <h2>Recibos y pagos</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    placeholder="Paciente, CI o recibo..." autocomplete="off">
            </div>

            <div class="form-group">
                <label>Clínica</label>
                <select id="filtro-clinica" class="form-control">
                    <option value="">Todas las clínicas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de pago</label>
                <select id="filtro-tipo-pago" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($tiposPago as $tp)
                    <option value="{{ $tp }}">{{ $tp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select id="filtro-estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="1">Habilitadas</option>
                    <option value="0">Deshabilitadas</option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Recibo</th>
                    <th>Paciente</th>
                    <th>Tratamiento</th>
                    <th>Clínica</th>
                    <th>Costo</th>
                    <th>Pago del recibo</th>
                    <th>Total cancelado</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyCajas">
                @include('cajas.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

@include('cajas.partials.modal')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('cajas.tabla')),
            store: @json(route('cajas.store')),
            datos: (id) => `/cajas/${id}/datos`,
            update: (id) => `/cajas/${id}`,
            destroy: (id) => `/cajas/${id}`,
        };

        const $tbody = document.getElementById('tbodyCajas');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $filtroTipoPago = document.getElementById('filtro-tipo-pago');
        const $filtroEstado = document.getElementById('filtro-estado');
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
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if ($filtroTipoPago?.value) params.set('tipo_pago', $filtroTipoPago.value);
            if ($filtroEstado?.value !== '') params.set('estado', $filtroEstado.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} registros de caja`;

                setStatus('idle');
            } catch (err) {
                console.error('[Cajas] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="10">
                    <div class="empty-state"><p>Error al cargar cajas.</p></div>
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
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));
        $filtroTipoPago?.addEventListener('change', () => cargarTabla(1));
        $filtroEstado?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroClinica.value = '';
            $filtroTipoPago.value = '';
            $filtroEstado.value = '';
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

            if (e.target.closest('#btnNuevaCaja')) {
                CajaModal.abrirCrear();
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'esta caja';

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
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                CajaModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Cajas] Error:', err);
                toast('error', 'No se pudieron cargar los datos.');
                setStatus('idle');
            }
        }

        async function eliminar(id, nombre) {
            if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) return;

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

                toast('success', data.message || 'Caja eliminada.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Cajas] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('caja:guardada', () => {
            cargarTabla(1);
        });

        window.CajasLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush
