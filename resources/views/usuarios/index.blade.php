@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} usuarios registrados</span>
    </div>
    <div class="toolbar-actions">
        @can('usuarios.crear')
        <button type="button" class="btn btn-primary" id="btnNuevoUsuario">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nuevo Usuario
        </button>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Lista de Usuarios</h2>
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
                    placeholder="CI, usuario o nombre..."
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label>Rol</label>
                <select id="filtro-rol" class="form-control">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $rol)
                    <option value="{{ $rol->name }}">{{ $rol->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select id="filtro-estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="1">Habilitados</option>
                    <option value="0">Deshabilitados</option>
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

    {{-- Tabla --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyUsuarios">
                @include('usuarios.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

@include('usuarios.partials.modal')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        /* ============================================================
           CONFIGURACIÓN
           ============================================================ */
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('usuarios.tabla')),
            store: @json(route('usuarios.store')),
            datos: (id) => `/usuarios/${id}/datos`,
            update: (id) => `/usuarios/${id}`,
            destroy: (id) => `/usuarios/${id}`,
            eliminar: (id) => @json(route('usuarios.eliminar-definitivamente', ['usuario' => '__ID__'])).replace('__ID__', id),
            habilitar: (id) => `/usuarios/${id}/habilitar`,
        };

        /* ============================================================
           REFERENCIAS DOM
           ============================================================ */
        const $tbody = document.getElementById('tbodyUsuarios');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroRol = document.getElementById('filtro-rol');
        const $filtroEstado = document.getElementById('filtro-estado');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');
        const $btnNuevo = document.getElementById('btnNuevoUsuario');

        let debounceTimer = null;
        let cargando = false;

        /* ============================================================
           TOASTS
           ============================================================ */
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

        /* ============================================================
           ESTADO VISUAL
           ============================================================ */
        function setStatus(state, text) {
            if (!$status) return;

            if (state === 'idle') {
                $status.classList.remove('visible');
                return;
            }

            $status.classList.add('visible');
            if ($statusText) $statusText.textContent = text || 'Cargando...';
        }

        /* ============================================================
           CARGAR TABLA
           ============================================================ */
        async function cargarTabla(page = 1) {
            if (cargando) return;
            cargando = true;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroRol?.value) params.set('rol', $filtroRol.value);
            if ($filtroEstado?.value !== '') params.set('estado', $filtroEstado.value);
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

                // Inyectar filas y paginación
                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} usuarios registrados`;

                setStatus('idle');
            } catch (err) {
                console.error('[Usuarios] Error al cargar:', err);
                $tbody.innerHTML = `
                <tr><td colspan="6">
                    <div class="empty-state">
                        <p>Error al cargar usuarios. Intenta de nuevo.</p>
                    </div>
                </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                $tbody?.classList.remove('loading');
                cargando = false;
            }
        }

        /* ============================================================
           FILTROS
           ============================================================ */
        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });

        $filtroRol?.addEventListener('change', () => cargarTabla(1));
        $filtroEstado?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroRol.value = '';
            $filtroEstado.value = '';
            cargarTabla(1);
        });

        /* ============================================================
           DELEGACIÓN DE CLICKS
           ============================================================ */
        document.addEventListener('click', (e) => {
            // Paginación AJAX
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                cargarTabla(page);
                return;
            }

            // Botón nuevo
            if (e.target.closest('#btnNuevoUsuario')) {
                UsuarioModal.abrirCrear();
                return;
            }

            // Botones de acción
            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'este usuario';

            switch (action) {
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'habilitar':
                    habilitar(id, nombre);
                    break;
                case 'deshabilitar':
                    deshabilitar(id, nombre);
                    break;
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
            }
        });

        /* ============================================================
           ABRIR EDICIÓN
           ============================================================ */
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
                UsuarioModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Usuarios] Error al cargar datos:', err);
                toast('error', 'No se pudieron cargar los datos del usuario.');
                setStatus('idle');
            }
        }

        /* ============================================================
           HABILITAR
           ============================================================ */
        async function habilitar(id, nombre) {
            if (!await window.Modal.confirm({
                title: '¿Habilitar este usuario?',
                record: nombre,
                text: 'El usuario podrá iniciar sesión nuevamente.',
                confirmText: 'Sí, habilitar',
                variant: 'success',
            })) return;

            setStatus('loading', 'Habilitando...');

            try {
                const res = await fetch(URLS.habilitar(id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al habilitar.');
                }

                toast('success', data.message || 'Usuario habilitado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Usuarios] Error al habilitar:', err);
                toast('error', err.message || 'No se pudo habilitar.');
                setStatus('idle');
            }
        }

        /* ============================================================
           DESHABILITAR
           ============================================================ */
        async function deshabilitar(id, nombre) {
            if (!await window.Modal.confirm({
                title: '¿Deshabilitar este usuario?',
                record: nombre,
                text: 'El usuario dejará de tener acceso al sistema.\nPodrás habilitarlo nuevamente cuando lo necesites.',
                confirmText: 'Sí, deshabilitar',
                variant: 'warning',
            })) return;

            setStatus('loading', 'Deshabilitando...');

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
                    throw new Error(data.message || 'Error al deshabilitar.');
                }

                toast('success', data.message || 'Usuario deshabilitado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Usuarios] Error al deshabilitar:', err);
                toast('error', err.message || 'No se pudo deshabilitar.');
                setStatus('idle');
            }
        }

        /* ============================================================
           ELIMINAR DEFINITIVAMENTE
           ============================================================ */
        async function eliminar(id, nombre) {
            if (!await window.Modal.confirm({
                title: '¿Eliminar este usuario?',
                record: nombre,
                text: 'Se eliminará definitivamente la cuenta del usuario.\nEsta acción no se puede deshacer.',
                confirmText: 'Sí, eliminar',
                variant: 'danger',
            })) return;

            setStatus('loading', 'Eliminando...');

            try {
                const res = await fetch(URLS.eliminar(id), {
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

                toast('success', data.message || 'Usuario eliminado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Usuarios] Error al eliminar:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        /* ============================================================
           CALLBACK DEL MODAL
           ============================================================ */
        // El modal emite este evento cuando guarda exitosamente
        window.addEventListener('usuario:guardado', () => {
            cargarTabla(1);
        });

        /* ============================================================
           EXPONER API (por si el modal la necesita)
           ============================================================ */
        window.UsuariosLista = {
            cargar: cargarTabla,
            toast: toast,
        };

    })();
</script>
@endpush
