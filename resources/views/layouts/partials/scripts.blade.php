<script>
    /* ============================================================
   UTILIDADES GLOBALES
   ============================================================ */
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const updateThemeControl = () => {
            const isDark = document.documentElement.dataset.theme === 'dark';
            const nextThemeLabel = isDark ? 'Activar modo claro' : 'Activar modo oscuro';
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', nextThemeLabel);
            themeToggle.title = nextThemeLabel;
        };

        updateThemeControl();
        themeToggle.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            document.documentElement.style.colorScheme = nextTheme;

            try {
                localStorage.setItem('upea-theme', nextTheme);
            } catch {}

            updateThemeControl();
        });
    }

    /**
     * Fetch con headers CSRF y detección automática de JSON vs HTML.
     * Devuelve { ok, status, data }
     */
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

    /* ============================================================
       TOASTS (notificaciones flotantes)
       ============================================================ */
    window.mostrarToast = function(tipo, mensaje) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        toast.textContent = mensaje;
        document.body.appendChild(toast);

        // Entrada
        requestAnimationFrame(() => toast.classList.add('show'));

        // Salida
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // /* ============================================================
    //    RECARGA GENÉRICA DE TABLA VÍA AJAX
    //    ============================================================
    //    Uso desde una vista:
    //      window.recargarTablaGenerica({
    //          urlTabla:     "{{ route('admin.usuarios.tabla') }}",
    //          tbodyId:      'tbodyUsuarios',
    //          paginacionId: 'paginacionUsuarios',
    //          totalId:      'total-usuarios',
    //          filtros: {
    //              buscar: 'filtroBuscar',
    //              tipo:   'filtroTipo',
    //          },
    //          extra: (data) => { /* callback opcional */
    // }
    // });
    // */
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

            // Reemplaza filas
            tbody.innerHTML = data.filas;

            // Reemplaza paginación
            if (paginacionId) {
                const p = document.getElementById(paginacionId);
                if (p) p.innerHTML = data.paginacion || '';
            }

            // Actualiza contador
            if (totalId && typeof data.total !== 'undefined') {
                const t = document.getElementById(totalId);
                if (t) t.textContent = data.total;
            }

            // Callback extra (ej: totales de caja)
            if (typeof extra === 'function') extra(data);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="99" class="empty-state">
            Error al cargar: ${e.message}
        </td></tr>`;
            window.mostrarToast('error', e.message);
        } finally {
            tbody.style.opacity = '1';
            tbody.style.pointerEvents = '';
        }
    };

    /* ============================================================
       DEBOUNCE (para filtros de búsqueda)
       ============================================================ */
    window.debounce = function(fn, delay = 300) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    };
    window.togglePassword = function(btn) {
        const wrap = btn.closest('.password-wrap');
        const input = wrap?.querySelector('input');
        if (!input) return;

        const esPassword = input.type === 'password';
        input.type = esPassword ? 'text' : 'password';
        btn.classList.toggle('visible', esPassword);
        btn.setAttribute('aria-label', esPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    };
    /* ============================================================
       MENÚ DE USUARIO (TOPBAR)
       ============================================================ */
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        if (menu) menu.classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('userMenu');
        if (!menu) return;
        if (!menu.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    /* ============================================================
       SIDEBAR EN MÓVIL
       ============================================================ */
    window.toggleSidebar = function() {
        document.querySelector('.sidebar')?.classList.toggle('open');
    };

    // Cerrar sidebar al hacer clic en un enlace (solo móvil)
    document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 900) {
                document.querySelector('.sidebar')?.classList.remove('open');
            }
        });
    });

    // Cerrar sidebar al hacer clic fuera (solo móvil)
    document.addEventListener('click', function(e) {
        if (window.innerWidth > 900) return;
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-toggle, .topbar-mobile-toggle');
        if (!sidebar || !sidebar.classList.contains('open')) return;
        if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    /* ============================================================
       CERRAR MODALES CON ESC
       ============================================================ */
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;

        // Cerrar menú de usuario
        document.getElementById('userMenu')?.classList.remove('open');

        // Cerrar sidebar
        document.querySelector('.sidebar')?.classList.remove('open');

        // Cerrar cualquier modal abierto
        document.querySelectorAll('.modal-backdrop.open').forEach(m => {
            m.classList.remove('open');
        });
        document.body.classList.remove('modal-open');
    });

    /* ============================================================
       ANIMACIÓN DE ENTRADA PARA TARJETAS
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.metric-card, .welcome-card, .card');
        cards.forEach((card, i) => {
            card.style.animationDelay = `${i * 0.06}s`;
        });
    });

    /* ============================================================
       AUTO-CERRAR ALERTAS DESPUÉS DE 5s
       ============================================================ */
    document.querySelectorAll('.alert-auto-close').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s ease, transform .4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    /* ============================================================
       DELEGACIÓN GLOBAL: BOTONES .btn-deshabilitar
       ============================================================
       Cualquier botón con clase .btn-deshabilitar y data-url
       enviará un DELETE vía AJAX y recargará la tabla.
    */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-deshabilitar');
        if (!btn) return;

        e.preventDefault();
        const url = btn.dataset.url;
        const nombre = btn.dataset.nombre || 'el registro';
        const urlTabla = btn.dataset.tabla; // opcional: recargar tabla
        const tbodyId = btn.dataset.tbody; // opcional
        const paginacionId = btn.dataset.paginacion;
        const totalId = btn.dataset.total;

        if (!url) return;
        if (!confirm(`¿Deshabilitar ${nombre}?`)) return;

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

                // Recargar tabla si se especificó
                if (urlTabla && tbodyId) {
                    await window.recargarTablaGenerica({
                        urlTabla,
                        tbodyId,
                        paginacionId,
                        totalId,
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
</script>