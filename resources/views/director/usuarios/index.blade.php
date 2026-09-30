@extends('layouts.app')

@section('title', 'Usuarios')



@section('content')

{{-- Barra de progreso superior --}}
<div class="loading-bar" id="loadingBar" aria-hidden="true"></div>

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Gestión de Usuarios</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra las cuentas del sistema y sus roles
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="abrirModalCrear()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nuevo usuario
    </button>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-usuarios">{{ $usuarios->total() }}</strong> usuarios
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscar">Buscar</label>
                <input type="text" id="filtroBuscar" class="form-control"
                    placeholder="CI, usuario o nombre…"
                    value="{{ request('buscar') }}"
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label for="filtroTipo">Tipo</label>
                <select id="filtroTipo" class="form-control">
                    <option value="">— Todos —</option>
                    @foreach (['admin','subadmin','director','jefatura','docente','estudiante','interno','cajero','personal'] as $t)
                    <option value="{{ $t }}" @selected(request('tipo')===$t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroHabilitado">Estado</label>
                <select id="filtroHabilitado" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="1" @selected(request('habilitado')==='1' )>Habilitados</option>
                    <option value="0" @selected(request('habilitado')==='0' )>Deshabilitados</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTabla(true)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de usuarios</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="tablaStatus" aria-live="polite">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm btn-actualizar"
                id="btnActualizarTabla" onclick="recargarTabla(true)" title="Actualizar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <polyline points="23 4 23 10 17 10" />
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                </svg>
                Actualizar
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>CI</th>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Tipo</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Último login</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyUsuarios">
                @include('admin.usuarios.partials.filas', ['usuarios' => $usuarios])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionUsuarios">
        @if ($usuarios->hasPages())
        <span class="info">
            Mostrando <strong>{{ $usuarios->firstItem() }}</strong>–<strong>{{ $usuarios->lastItem() }}</strong>
            de <strong>{{ $usuarios->total() }}</strong> registros
        </span>
        <div>{{ $usuarios->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

{{-- ==================== MODAL ==================== --}}
@include('admin.usuarios.partials.modal', ['roles' => $roles])

@endsection

@push('scripts')
<script>
    /* ============================================================
       CONFIG
       ============================================================ */
    const URL_TABLA_USUARIOS = "{{ route('admin.usuarios.tabla') }}";
    const URL_BASE_USUARIOS = "{{ url('admin/usuarios') }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";

    /* ============================================================
       BARRA DE PROGRESO SUPERIOR
       ============================================================ */
    let loadingBarTimeout = null;

    function mostrarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        clearTimeout(loadingBarTimeout);
        bar.classList.remove('complete');
        bar.classList.add('active');
    }

    function completarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        bar.classList.remove('active');
        bar.classList.add('complete');
        loadingBarTimeout = setTimeout(() => {
            bar.classList.remove('complete');
            bar.style.width = '0';
        }, 400);
    }

    /* ============================================================
       RECARGAR TABLA (con animaciones y control de concurrencia)
       ============================================================ */
    let recargandoTabla = false;

    window.recargarTabla = async function(manual = false) {
        if (recargandoTabla) return;
        recargandoTabla = true;

        const tbody = document.getElementById('tbodyUsuarios');
        const status = document.getElementById('tablaStatus');
        const btnActualizar = document.getElementById('btnActualizarTabla');

        mostrarBarraCarga();
        tbody?.classList.add('loading');
        status?.classList.add('visible');
        btnActualizar?.classList.add('spinning');
        if (btnActualizar) btnActualizar.disabled = true;

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_USUARIOS,
                tbodyId: 'tbodyUsuarios',
                paginacionId: 'paginacionUsuarios',
                totalId: 'total-usuarios',
                filtros: {
                    buscar: 'filtroBuscar',
                    tipo: 'filtroTipo',
                    habilitado: 'filtroHabilitado',
                },
            });

            // Reaplicar animación escalonada a TODAS las filas
            tbody?.querySelectorAll('tr').forEach((tr, i) => {
                tr.style.animation = 'none';
                void tr.offsetHeight; // reflow
                tr.style.animation = '';
                tr.style.animationDelay = `${Math.min(i * 0.03, 0.4)}s`;
            });

            if (manual) window.mostrarToast('success', 'Tabla actualizada');
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error al recargar la tabla');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
            btnActualizar?.classList.remove('spinning');
            if (btnActualizar) btnActualizar.disabled = false;
            completarBarraCarga();
            recargandoTabla = false;
        }
    };

    /* ============================================================
       FILTROS + LIMPIAR
       ============================================================ */
    window.limpiarFiltros = function() {
        document.getElementById('filtroBuscar').value = '';
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroHabilitado').value = '';
        window.recargarTabla();
    };

    /* ============================================================
       MODAL DE USUARIO
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalUsuario');
        const form = document.getElementById('formUsuario');
        const titleEl = document.getElementById('modalUsuarioTitle');
        const btnTextEl = document.getElementById('btnGuardarUsuarioText');
        const methodEl = document.getElementById('formUsuarioMethod');
        const idEl = document.getElementById('formUsuarioId');
        const reqContrasena = document.getElementById('reqContrasena');
        const hintContrasena = document.getElementById('hintContrasena');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.usuarios.guardar') }}";
            form.querySelectorAll('input[name="roles[]"]').forEach(cb => cb.checked = false);

            const mHabilitado = document.getElementById('m_habilitado');
            const mPermitirLogin = document.getElementById('m_permitir_login');
            const mMultilogin = document.getElementById('m_multilogin');
            if (mHabilitado) mHabilitado.checked = true;
            if (mPermitirLogin) mPermitirLogin.checked = true;
            if (mMultilogin) mMultilogin.checked = false;

            limpiarErrores();
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                form.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
            }, 100);
        }

        window.abrirModalCrear = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo usuario';
            btnTextEl.textContent = 'Crear usuario';
            reqContrasena.style.display = 'inline';
            hintContrasena.style.display = 'none';
            abrir();
        };

        window.abrirModalEditar = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar usuario';
            btnTextEl.textContent = 'Guardar cambios';
            reqContrasena.style.display = 'none';
            hintContrasena.style.display = 'inline';

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_USUARIOS}/${id}/json`);
                if (!ok || !data.usuario) throw new Error('No se pudo cargar el usuario');

                const u = data.usuario;
                idEl.value = u.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_USUARIOS}/${u.id}`;

                document.getElementById('m_ci').value = u.ci || '';
                document.getElementById('m_nombre_usuario').value = u.nombre_usuario || '';
                document.getElementById('m_nombre_completo').value = u.nombre_completo || '';
                document.getElementById('m_descripcion').value = u.descripcion || '';
                document.getElementById('m_tipo').value = u.tipo || '';
                document.getElementById('m_contrasena').value = '';
                document.getElementById('m_info').value = u.info || '';

                document.getElementById('m_habilitado').checked = !!u.habilitado;
                document.getElementById('m_permitir_login').checked = !!u.permitir_login;
                document.getElementById('m_multilogin').checked = !!u.multilogin;

                form.querySelectorAll('input[name="roles[]"]').forEach(cb => {
                    cb.checked = (u.roles_ids || []).includes(parseInt(cb.dataset.rolId, 10));
                });

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar usuario');
            }
        };

        window.cerrarModal = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModal();
        });

        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && backdrop.classList.contains('open')) {
                window.cerrarModal();
            }
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarUsuario');
            const formData = new FormData(form);
            const isEdit = methodEl.value === 'PUT';

            // Asegurar CSRF
            formData.append('_token', CSRF_TOKEN);

            btn.disabled = true;
            btnTextEl.textContent = 'Guardando…';
            limpiarErrores();

            try {
                const {
                    ok,
                    status,
                    data
                } = await window.fetchAjax(form.action, {
                    method: 'POST',
                    body: formData,
                });

                if (ok && data.success) {
                    window.cerrarModal();
                    await window.recargarTabla();
                    window.mostrarToast('success', data.message || (isEdit ? 'Usuario actualizado' : 'Usuario creado'));
                    return;
                }

                if (status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([campo, mensajes]) => {
                        const span = form.querySelector(`[data-error="${campo}"]`);
                        if (span) span.textContent = mensajes[0];

                        // Selector robusto para inputs y checkboxes
                        const input = form.querySelector(`[name="${campo}"], [name="${campo}[]"]`);
                        input?.classList.add('is-invalid');
                    });
                    window.mostrarToast('warning', 'Revisa los campos marcados');
                    return;
                }

                window.mostrarToast('error', data.message || 'Error al guardar');
            } catch (err) {
                window.mostrarToast('error', err.message || 'Error de red');
            } finally {
                btn.disabled = false;
                btnTextEl.textContent = isEdit ? 'Guardar cambios' : 'Crear usuario';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        // NO recargamos al inicio: el Blade ya renderizó las filas.
        // Solo aplicamos la animación escalonada.

        const tbody = document.getElementById('tbodyUsuarios');
        tbody?.querySelectorAll('tr').forEach((tr, i) => {
            tr.style.animationDelay = `${Math.min(i * 0.03, 0.4)}s`;
        });

        // Filtros en vivo
        document.getElementById('filtroBuscar')
            ?.addEventListener('input', window.debounce(() => window.recargarTabla(), 350));

        document.getElementById('filtroTipo')
            ?.addEventListener('change', () => window.recargarTabla());

        document.getElementById('filtroHabilitado')
            ?.addEventListener('change', () => window.recargarTabla());

        // Barra de progreso global para fetchAjax (sin sobrescribir si ya existe)
        if (!window.__fetchAjaxWrapped) {
            const originalFetch = window.fetchAjax;
            window.fetchAjax = async function(url, opts) {
                mostrarBarraCarga();
                try {
                    return await originalFetch(url, opts);
                } finally {
                    completarBarraCarga();
                }
            };
            window.__fetchAjaxWrapped = true;
        }
    });
</script>
@endpush