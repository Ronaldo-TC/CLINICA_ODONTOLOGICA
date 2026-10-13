@extends('layouts.app')

@section('title', 'Insumos')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $stats['total'] }} insumos registrados</span>
    </div>
    <div class="toolbar-actions">
        <x-boton-reporte ruta="insumos.reporte" />
        @can('insumos.ver')
        <a href="{{ route('solicitudes-insumos.index') }}" class="btn btn-secondary">
            📋 Ver Solicitudes
        </a>
        @endcan
        @canany(['insumos.crear', 'insumos.gestionar'])
        <button type="button" class="btn btn-primary" id="btnNuevoInsumo">
            + Nuevo Insumo
        </button>
        @endcanany
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon cyan"><svg viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><svg viewBox="0 0 24 24">
                <path d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.5 0L3.16 16.25A2 2 0 005 19z" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['stock_bajo'] }}</div>
            <div class="stat-label">Stock Bajo</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon fuchsia"><svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v8m-4-4h8" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ number_format($stats['valor'], 2) }}</div>
            <div class="stat-label">Valor Total (Bs)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
            </svg></div>
        <div class="stat-info">
            <div class="stat-value">{{ $stats['agotados'] }}</div>
            <div class="stat-label">Agotados</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Catálogo de Insumos</h2>
        <span class="tabla-status" id="crud-status"><span class="dot"></span><span id="crud-status-text">Cargando...</span></span>
    </div>

    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control" placeholder="Nombre..." autocomplete="off">
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
                <label>Filtros</label>
                <label class="switch-item" style="height:42px;">
                    <input type="checkbox" id="filtro-stock-bajo">
                    <span class="switch-track"><span class="switch-thumb"></span></span>
                    <span class="switch-label">Solo stock bajo</span>
                </label>
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
                    <th>Nombre</th>
                    <th>Tratamiento</th>
                    <th>Clínica</th>
                    <th>Stock</th>
                    <th>Costo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyInsumos">
                @include('insumos.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

@include('insumos.partials.modal')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('insumos.tabla')),
            store: @json(route('insumos.store')),
            datos: (id) => `/insumos/${id}/datos`,
            update: (id) => `/insumos/${id}`,
            destroy: (id) => `/insumos/${id}`,
        };

        const $tbody = document.getElementById('tbodyInsumos');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $filtroStockBajo = document.getElementById('filtro-stock-bajo');
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
            if ($filtroStockBajo?.checked) params.set('stock_bajo', '1');
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
                $totalLabel.textContent = `${data.total} insumos registrados`;

                setStatus('idle');
            } catch (err) {
                console.error('[Insumos] Error:', err);
                $tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><p>Error al cargar insumos.</p></div></td></tr>`;
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
        $filtroStockBajo?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroClinica.value = '';
            $filtroStockBajo.checked = false;
            cargarTabla(1);
        });

        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                cargarTabla(parseInt(url.searchParams.get('page') || '1', 10));
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'este insumo';

            switch (action) {
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
                case 'ajustar':
                    ajustarStock(id, nombre);
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
                if (!res.ok) throw new Error();
                const data = await res.json();
                InsumosModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Insumos] Error:', err);
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
                if (!res.ok || !data.success) throw new Error(data.message || 'Error al eliminar.');
                toast('success', data.message || 'Eliminado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Insumos] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        async function ajustarStock(id, nombre) {
            const cantidad = prompt(`Ajustar stock de "${nombre}"\n\nUsa positivo para sumar (ej: 10) o negativo para restar (ej: -5):`);
            if (cantidad === null || cantidad === '') return;

            const num = parseFloat(cantidad);
            if (isNaN(num)) {
                toast('error', 'Cantidad inválida');
                return;
            }

            try {
                const res = await fetch(`/insumos/${id}/ajustar-stock`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cantidad: num
                    }),
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Error');
                toast('success', data.message);
                cargarTabla(1);
            } catch (err) {
                toast('error', err.message || 'Error al ajustar.');
            }
        }

        window.addEventListener('insumo:guardado', () => cargarTabla(1));

        window.InsumosLista = {
            cargar: cargarTabla,
            toast: toast
        };
    })();
</script>
@endpush
