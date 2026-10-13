@extends('layouts.app')

@section('title', 'Gestión de Remisiones')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} remisiones registradas</span>
    </div>
    <div class="toolbar-actions">
        <x-boton-reporte ruta="remisiones.reporte" />
        @canany(['remisiones.crear', 'remisiones.gestionar'])
        <button type="button" class="btn btn-primary" id="btnNuevaRemision">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva Remisión
        </button>
        @endcanany
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Remisiones por especialidad</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    <div class="card-body" style="padding-bottom: 14px;">
        <form method="GET" action="{{ route('remisiones.index') }}" class="form-grid" id="formFiltrosRemisiones">
            <div class="form-group">
                <label for="filtro-buscar">Buscar</label>
                <input type="text" id="filtro-buscar" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Paciente o CI..." autocomplete="off">
            </div>

            <div class="form-group">
                <label for="filtro-clinica">Especialidad destino</label>
                <select id="filtro-clinica" name="clinica_id" class="form-control">
                    <option value="">Todas las especialidades</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}" @selected((string) request('clinica_id') === (string) $c->id)>{{ $c->especialidad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-periodo">Periodo</label>
                <select id="filtro-periodo" name="periodo_id" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($periodos as $p)
                    <option value="{{ $p->id }}" @selected((string) request('periodo_id') === (string) $p->id)>{{ $p->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-flujo">Estado de atención</label>
                <select id="filtro-flujo" name="flujo_estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" @selected(request('flujo_estado') === 'pendiente')>Pendiente de asignación</option>
                    <option value="asignada" @selected(request('flujo_estado') === 'asignada')>Asignada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-estado">Activación</label>
                <select id="filtro-estado" name="estado" class="form-control">
                    <option value="">Todas</option>
                    <option value="1" @selected((string) request('estado') === '1')>Habilitadas</option>
                    <option value="0" @selected((string) request('estado') === '0')>Inhabilitadas</option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Buscar</button>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Especialidad destino</th>
                    <th>Periodo</th>
                    <th>Docente</th>
                    <th>Estudiante</th>
                    <th>Historias</th>
                    <th>Insumos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyRemisiones">
                @include('remisiones.partials.filas', ['items' => $items])
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap">{{ $registros->withQueryString()->links() }}</div>
</div>

@include('remisiones.partials.modal')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('remisiones.tabla')),
            datos: (id) => @json(route('remisiones.datos', ['remision' => '__ID__'])).replace('__ID__', encodeURIComponent(id)),
            destroy: (id) => @json(route('remisiones.destroy', ['remision' => '__ID__'])).replace('__ID__', encodeURIComponent(id)),
        };

        const $tbody = document.getElementById('tbodyRemisiones');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $filtroPeriodo = document.getElementById('filtro-periodo');
        const $filtroFlujo = document.getElementById('filtro-flujo');
        const $filtroEstado = document.getElementById('filtro-estado');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        let debounceTimer = null;
        let tablaAbortController = null;

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
            tablaAbortController?.abort();
            const abortController = new AbortController();
            tablaAbortController = abortController;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if ($filtroPeriodo?.value) params.set('periodo_id', $filtroPeriodo.value);
            if ($filtroFlujo?.value) params.set('flujo_estado', $filtroFlujo.value);
            if ($filtroEstado?.value !== '') params.set('estado', $filtroEstado.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    signal: abortController.signal,
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if (tablaAbortController !== abortController) return;

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} remisiones registradas`;

                setStatus('idle');
            } catch (err) {
                if (err.name === 'AbortError') return;
                console.error('[Remisiones] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="10">
                    <div class="empty-state"><p>Error al cargar remisiones.</p></div>
                </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                if (tablaAbortController === abortController) {
                    $tbody?.classList.remove('loading');
                }
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));
        $filtroPeriodo?.addEventListener('change', () => cargarTabla(1));
        $filtroFlujo?.addEventListener('change', () => cargarTabla(1));
        $filtroEstado?.addEventListener('change', () => cargarTabla(1));
        document.getElementById('formFiltrosRemisiones')?.addEventListener('submit', (event) => {
            event.preventDefault();
            cargarTabla(1);
        });

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroClinica.value = '';
            $filtroPeriodo.value = '';
            $filtroFlujo.value = '';
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

            if (e.target.closest('#btnNuevaRemision')) {
                RemisionModal.abrirCrear();
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'esta remisión';

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
                RemisionModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Remisiones] Error:', err);
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

                toast('success', data.message || 'Remisión eliminada.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Remisiones] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('remision:guardada', () => {
            cargarTabla(1);
        });

        window.RemisionesLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush
