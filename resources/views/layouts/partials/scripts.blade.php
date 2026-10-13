<script>
    /* ============================================================
       UTILIDADES GLOBALES
       ============================================================ */
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    window.confirmarEliminacion = function(nombre, { deshabilitar = false } = {}) {
        return window.Modal.confirm({
            title: deshabilitar ? '¿Deshabilitar este registro?' : '¿Eliminar este registro?',
            record: nombre || 'Registro seleccionado',
            text: deshabilitar
                ? 'El registro quedará deshabilitado. Podrás reactivarlo después.'
                : 'Al confirmar, se eliminará el registro seleccionado.\nEsta acción no se puede deshacer.',
            confirmText: deshabilitar ? 'Sí, deshabilitar' : 'Sí, eliminar',
            cancelText: 'Cancelar',
            variant: 'danger',
        });
    };

    /* ============================================================
       NOTA · El tema, los menús desplegables del topbar, el buscador
       global y el sidebar (drawer + filtro) se inicializan en sus
       propios partials: layouts.partials.topbar y layouts.partials.sidebar
       ============================================================ */

    /* ---------------- Fetch con CSRF ---------------- */
    window.fetchAjax = async function(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });
        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ?
            await res.json().catch(() => ({})) :
            await res.text();
        return {
            ok: res.ok,
            status: res.status,
            data
        };
    };

    /* ---------------- Toasts ---------------- */
    window.mostrarToast = function(tipo, mensaje) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        toast.textContent = mensaje;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    /* ============================================================
       RECARGA GENÉRICA DE TABLA VÍA AJAX
       Uso desde una vista:
         window.recargarTablaGenerica({
             urlTabla: '...',
             tbodyId: '...',
             paginacionId: '...',
             totalId: '...',
             filtros: { buscar: 'filtroBuscar' },
             extra: (data) => { ... }
         });
       ============================================================ */
    window.recargarTablaGenerica = async function(opts) {
        const {
            urlTabla,
            tbodyId,
            paginacionId = null,
            totalId = null,
            filtros = {},
            extra = null,
        } = opts;

        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        tbody.style.opacity = '0.5';
        tbody.style.pointerEvents = 'none';

        const params = new URLSearchParams();
        Object.entries(filtros).forEach(([key, elId]) => {
            const el = document.getElementById(elId);
            if (el && el.value !== '' && el.value !== null) {
                params.append(key, el.value);
            }
        });

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(`${urlTabla}?${params.toString()}`);
            if (!ok) throw new Error(data?.message || 'Error al cargar');

            tbody.innerHTML = data.filas;

            if (paginacionId) {
                const p = document.getElementById(paginacionId);
                if (p) p.innerHTML = data.paginacion || '';
            }
            if (totalId && typeof data.total !== 'undefined') {
                const t = document.getElementById(totalId);
                if (t) t.textContent = data.total;
            }
            if (typeof extra === 'function') extra(data);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="99" class="empty-state">Error al cargar: ${e.message}</td></tr>`;
            window.mostrarToast('error', e.message);
        } finally {
            tbody.style.opacity = '1';
            tbody.style.pointerEvents = '';
        }
    };

    /* ---------------- Debounce ---------------- */
    window.debounce = function(fn, delay = 300) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    };

    /* ---------------- Toggle password ---------------- */
    window.togglePassword = function(btn) {
        const wrap = btn.closest('.password-wrap');
        const input = wrap?.querySelector('input');
        if (!input) return;
        const esPassword = input.type === 'password';
        input.type = esPassword ? 'text' : 'password';
        btn.classList.toggle('visible', esPassword);
        btn.setAttribute('aria-label', esPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    };

    /* ---------------- ESC cierra modales ---------------- */
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
        document.body.classList.remove('modal-open');
    });

    /* ---------------- Animación de entrada ---------------- */
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.metric-card, .welcome-card, .card');
        cards.forEach((card, i) => card.style.animationDelay = `${i * 0.06}s`);
    });

    /* ---------------- Auto-cerrar alertas ---------------- */
    document.querySelectorAll('.alert-auto-close').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s ease, transform .4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    /* ============================================================
       DELEGACIÓN GLOBAL: .btn-deshabilitar
       ============================================================ */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-deshabilitar');
        if (!btn) return;

        e.preventDefault();
        const url = btn.dataset.url;
        const nombre = btn.dataset.nombre || 'el registro';
        const urlTabla = btn.dataset.tabla;
        const tbodyId = btn.dataset.tbody;
        const paginacionId = btn.dataset.paginacion;
        const totalId = btn.dataset.total;

        if (!url) return;
        if (!await window.confirmarEliminacion(nombre, { deshabilitar: true })) return;

        btn.disabled = true;
        btn.style.opacity = '0.5';

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(url, {
                method: 'DELETE'
            });
            if (ok && data.success) {
                window.mostrarToast('success', data.message || 'Operación exitosa');
                if (urlTabla && tbodyId) {
                    await window.recargarTablaGenerica({
                        urlTabla,
                        tbodyId,
                        paginacionId,
                        totalId
                    });
                }
            } else {
                window.mostrarToast('error', data.message || 'Error al procesar');
            }
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red');
        } finally {
            btn.disabled = false;
            btn.style.opacity = '';
        }
    });

    /* ============================================================
       HELPERS DE PERMISOS EN JS (vienen del layout app)
       window.can('usuarios.crear')     → true/false
       window.hasRole('admin')          → true/false
       window.hasAnyRole(['admin','subadmin'])
       ============================================================ */
    // Ya definidos en app.blade.php vía meta tags
</script>
