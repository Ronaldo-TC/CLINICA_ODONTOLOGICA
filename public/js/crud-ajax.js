/**
 * crud-ajax.js
 *
 * Sistema CRUD AJAX genérico para todos los módulos.
 *
 * Lee `window.CRUD_CONFIG` que define cada vista y se engancha
 * automáticamente a:
 *   - Botones con [data-crud-action="crear|editar|eliminar|habilitar|permisos|..."]
 *   - Inputs/selects con [data-crud-filter="..."]
 *   - Links de paginación dentro de #crud-pagination
 *   - Cualquier [data-crud-refresh] para recargar la tabla
 *
 * Expone la API pública:
 *   window.CRUD.toast(type, message)
 *   window.CRUD.recargar()
 *   window.CRUD.confirmar(mensaje)  → Promise<boolean>
 */
(function () {
    'use strict';

    /* ============================================================
       CONFIGURACIÓN GLOBAL
       ============================================================ */
    const CONFIG = window.CRUD_CONFIG || {};

    const URLS = {
        tabla:  CONFIG.endpointTabla  || null,
        datos:  CONFIG.endpointDatos  || null,
        store:  CONFIG.endpointStore  || null,
        update: CONFIG.endpointUpdate || null,
        delete: CONFIG.endpointDelete || null,
        hab:    CONFIG.endpointHab    || null,
        sync:   CONFIG.endpointSync   || null,
    };

    const RESOURCE   = CONFIG.resourceName  || 'registro';
    const MODAL_ID   = CONFIG.modalId       || null;
    const MODAL_CB   = CONFIG.modalCallback || null;   // ej: 'UsuarioModal.abrirEdicion'
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ============================================================
       ESTADO
       ============================================================ */
    let debounceTimer = null;
    let isCargando    = false;

    /* ============================================================
       ELEMENTOS DEL DOM
       ============================================================ */
    const $tbody      = document.getElementById('crud-tbody');
    const $pagination = document.getElementById('crud-pagination');
    const $status     = document.getElementById('crud-status');
    const $statusText = document.getElementById('crud-status-text');
    const $totalLabel = document.getElementById('crud-total-label');

    /* ============================================================
       API PÚBLICA
       ============================================================ */
    window.CRUD = {
        toast,
        recargar: () => cargarTabla(1),
        confirmar: (mensaje) => Promise.resolve(window.confirm(mensaje)),
    };

    /* ============================================================
       CARGA DE TABLA POR AJAX
       ============================================================ */
    async function cargarTabla(page = 1) {
        if (!URLS.tabla || isCargando) return;

        isCargando = true;
        setStatus('loading', 'Cargando...');
        $tbody?.classList.add('loading');

        const params = buildFiltrosParams();
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

            // El controlador devuelve: { filas, paginacion, total }
            if ($tbody)      $tbody.innerHTML      = data.filas      || '';
            if ($pagination) $pagination.innerHTML = data.paginacion || '';
            if ($totalLabel) $totalLabel.textContent = `${data.total} ${pluralizar(data.total)} registrados`;

            setStatus('idle');
        } catch (err) {
            console.error('[CRUD] Error al cargar tabla:', err);
            if ($tbody) {
                $tbody.innerHTML = `
                    <tr><td colspan="99">
                        <div class="empty-state">
                            <p>Error al cargar. Intenta de nuevo.</p>
                        </div>
                    </td></tr>`;
            }
            setStatus('error', 'Error al cargar');
        } finally {
            $tbody?.classList.remove('loading');
            isCargando = false;
        }
    }

    /* ============================================================
       FILTROS
       ============================================================ */
    function buildFiltrosParams() {
        const params = new URLSearchParams();

        document.querySelectorAll('[data-crud-filter]').forEach((el) => {
            const key   = el.dataset.crudFilter;
            const value = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value;

            if (value !== '' && value !== null && value !== undefined) {
                params.set(key, value);
            }
        });

        return params;
    }

    function initFiltros() {
        document.querySelectorAll('[data-crud-filter]').forEach((el) => {
            const tipo = el.tagName === 'SELECT' || el.type === 'checkbox' ? 'change' : 'input';

            el.addEventListener(tipo, () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => cargarTabla(1), 300);
            });
        });
    }

    function limpiarFiltros() {
        document.querySelectorAll('[data-crud-filter]').forEach((el) => {
            if (el.type === 'checkbox') el.checked = false;
            else el.value = '';
        });
        cargarTabla(1);
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
       DELEGACIÓN DE CLICKS
       ============================================================ */
    document.addEventListener('click', (e) => {
        /* --- Paginación AJAX --- */
        const pagLink = e.target.closest('#crud-pagination a');
        if (pagLink) {
            e.preventDefault();
            const url = new URL(pagLink.href, window.location.origin);
            const page = parseInt(url.searchParams.get('page') || '1', 10);
            cargarTabla(page);
            return;
        }

        /* --- Acciones CRUD --- */
        const actionBtn = e.target.closest('[data-crud-action]');
        if (!actionBtn) return;

        const action = actionBtn.dataset.crudAction;
        const id     = actionBtn.dataset.id;
        const nombre = actionBtn.dataset.nombre || 'este registro';

        switch (action) {
            case 'crear':
                abrirModalCrear();
                break;

            case 'editar':
                abrirModalEditar(id);
                break;

            case 'permisos':
                abrirModalPermisos(id, nombre);
                break;

            case 'eliminar':
                eliminar(id, nombre);
                break;

            case 'habilitar':
                habilitar(id, nombre);
                break;

            case 'limpiar-filtros':
                limpiarFiltros();
                break;

            default:
                console.warn('[CRUD] Acción no reconocida:', action);
        }
    });

    /* ============================================================
       ABRIR MODAL CREAR
       ============================================================ */
    function abrirModalCrear() {
        if (MODAL_CB && getCallback('abrirCrear')) {
            getCallback('abrirCrear')();
            return;
        }

        // Fallback: abrir el modal sin callbacks
        abrirModalBase('Nuevo ' + capitalizar(RESOURCE));
    }

    /* ============================================================
       ABRIR MODAL EDITAR
       ============================================================ */
    async function abrirModalEditar(id) {
        if (!id || !URLS.datos) return;

        setStatus('loading', 'Cargando datos...');

        try {
            const url = typeof URLS.datos === 'function' ? URLS.datos(id) : `${URLS.datos}/${id}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();

            // Llamar al callback específico del módulo
            const cb = getCallback('abrirEdicion');
            if (cb) {
                cb(data);
            } else {
                console.warn('[CRUD] No se encontró callback para abrir edición. Configura modalCallback.');
            }

            setStatus('idle');
        } catch (err) {
            console.error('[CRUD] Error al cargar datos:', err);
            toast('error', 'No se pudieron cargar los datos.');
            setStatus('error', 'Error');
        }
    }

    /* ============================================================
       ABRIR MODAL PERMISOS (módulo roles)
       ============================================================ */
    async function abrirModalPermisos(id, nombre) {
        if (!id || !URLS.datos) return;

        setStatus('loading', 'Cargando permisos...');

        try {
            const url = typeof URLS.datos === 'function' ? URLS.datos(id) : `${URLS.datos}/${id}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();

            const cb = getCallback('abrirPermisos') || getCallback('abrirEdicion');
            if (cb) {
                cb(data);
            } else {
                console.warn('[CRUD] No se encontró callback para abrir permisos.');
            }

            setStatus('idle');
        } catch (err) {
            console.error('[CRUD] Error al cargar permisos:', err);
            toast('error', 'No se pudieron cargar los permisos.');
            setStatus('error', 'Error');
        }
    }

    /* ============================================================
       ELIMINAR
       ============================================================ */
    async function eliminar(id, nombre) {
        if (!id || !URLS.delete) return;

        const confirmado = await window.CRUD.confirmar(
            `¿Eliminar ${nombre}? Esta acción no se puede deshacer.`
        );
        if (!confirmado) return;

        setStatus('loading', 'Eliminando...');

        try {
            const url = typeof URLS.delete === 'function' ? URLS.delete(id) : `${URLS.delete}/${id}`;
            const res = await fetch(url, {
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

            toast('success', data.message || 'Eliminado correctamente.');
            cargarTabla(1);
        } catch (err) {
            console.error('[CRUD] Error al eliminar:', err);
            toast('error', err.message || 'No se pudo eliminar.');
            setStatus('idle');
        }
    }

    /* ============================================================
       HABILITAR
       ============================================================ */
    async function habilitar(id, nombre) {
        if (!id || !URLS.hab) return;

        const confirmado = await window.CRUD.confirmar(
            `¿Habilitar ${nombre}?`
        );
        if (!confirmado) return;

        setStatus('loading', 'Habilitando...');

        try {
            const url = typeof URLS.hab === 'function' ? URLS.hab(id) : `${URLS.hab}/${id}/habilitar`;
            const res = await fetch(url, {
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

            toast('success', data.message || 'Habilitado correctamente.');
            cargarTabla(1);
        } catch (err) {
            console.error('[CRUD] Error al habilitar:', err);
            toast('error', err.message || 'No se pudo habilitar.');
            setStatus('idle');
        }
    }

    /* ============================================================
       CALLBACKS DINÁMICOS
       ============================================================ */
    /**
     * Retorna una función desde un string tipo "RolModal.abrirEdicion".
     */
    function getCallback(nombre) {
        if (!MODAL_CB) return null;

        const [namespace, method] = MODAL_CB.split('.');

        // Si solo hay un método (ej: "abrirEdicion")
        if (!method) {
            return window[namespace]?.[nombre] || null;
        }

        // Si es "RolModal.abrirEdicion" → window.RolModal.abrirEdicion
        // Pero para crear/permisos, usamos el mismo namespace con otro método
        const ns = window[namespace];
        if (!ns) return null;

        // Si el nombre solicitado coincide con el método configurado
        if (method === nombre || nombre === 'abrirEdicion') {
            return ns[method] || null;
        }

        // Para otros métodos dentro del mismo namespace
        return ns[nombre] || null;
    }

    /* ============================================================
       MODAL BASE (fallback)
       ============================================================ */
    function abrirModalBase(titulo) {
        if (!MODAL_ID) {
            console.warn('[CRUD] No hay modalId configurado.');
            return;
        }

        const $modal = document.getElementById(MODAL_ID);
        if (!$modal) {
            console.warn('[CRUD] No se encontró el modal con id:', MODAL_ID);
            return;
        }

        const $titulo = $modal.querySelector('h3');
        if ($titulo) $titulo.textContent = titulo;

        $modal.classList.add('open');
        document.body.classList.add('modal-open');
    }

    /* ============================================================
       TOAST NOTIFICATIONS
       ============================================================ */
    function toast(type, message) {
        const colores = {
            success: 'linear-gradient(135deg, #16a34a, #15803d)',
            error:   'linear-gradient(135deg, #dc2626, #b91c1c)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            info:    'linear-gradient(135deg, #06b6d4, #0891b2)',
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
       ESCUCHAR EVENTO crud:refresh (emitido por los modales)
       ============================================================ */
    window.addEventListener('crud:refresh', () => {
        cargarTabla(1);
    });

    /* ============================================================
       HELPERS
       ============================================================ */
    function capitalizar(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function pluralizar(n) {
        return n === 1 ? 'registro' : 'registros';
    }

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    function init() {
        if (!URLS.tabla) {
            // No hay config CRUD en esta página
            return;
        }

        initFiltros();
        // La tabla viene renderizada por el servidor, no la recargamos al inicio
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();