@extends('layouts.app')

@section('title', 'Roles y Permisos')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Roles y Permisos</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra los roles del sistema y los permisos asociados a cada uno
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="abrirModalRol()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nuevo rol
    </button>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de roles</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusRol">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaRoles(true)" title="Actualizar">
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
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Usuarios</th>
                    <th>Permisos</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyRoles">
                @include('admin.roles.partials.filas', ['roles' => $roles])
            </tbody>
        </table>
    </div>
</div>

{{-- ==================== MODAL: CREAR/EDITAR ROL ==================== --}}
@include('admin.roles.partials.modal')

@endsection

@push('scripts')
<script>
    /* ============================================================
       CONFIG
       ============================================================ */
    const URL_TABLA_ROLES = "{{ route('admin.roles.tabla') }}";
    const URL_BASE_ROLES = "{{ url('admin/roles') }}";

    /* ============================================================
       BARRA DE PROGRESO
       ============================================================ */
    function mostrarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        bar.classList.remove('complete');
        bar.classList.add('active');
    }

    function completarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        bar.classList.remove('active');
        bar.classList.add('complete');
        setTimeout(() => {
            bar.classList.remove('complete');
            bar.style.width = '0';
        }, 400);
    }

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaRoles = async function(manual = false) {
        const tbody = document.getElementById('tbodyRoles');
        const status = document.getElementById('tablaStatusRol');

        mostrarBarraCarga();
        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            // fetch directo y sencillo (sin depender de recargarTablaGenerica)
            const res = await fetch(URL_TABLA_ROLES, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (tbody && data.filas !== undefined) {
                tbody.innerHTML = data.filas;

                requestAnimationFrame(() => {
                    tbody.querySelectorAll('tr').forEach((tr, i) => {
                        tr.style.animation = 'none';
                        tr.offsetHeight;
                        tr.style.animation = 'filaEntra 0.35s ease-out backwards';
                        tr.style.animationDelay = `${Math.min(i * 0.03, 0.3)}s`;
                    });
                });
            }

            if (manual && window.mostrarToast) window.mostrarToast('success', 'Roles actualizados');
        } catch (e) {
            if (window.mostrarToast) window.mostrarToast('error', 'Error al cargar roles');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
            completarBarraCarga();
        }
    };

    /* ============================================================
       MODAL: CREAR/EDITAR ROL
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalRol');
        const form = document.getElementById('formRol');
        const titleEl = document.getElementById('modalRolTitle');
        const btnText = document.getElementById('btnGuardarRolText');
        const methodEl = document.getElementById('formRolMethod');
        const idEl = document.getElementById('formRolId');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.roles.guardar') }}";
            const chk = document.getElementById('m_rol_habilitado');
            if (chk) chk.checked = true;
            limpiarErrores();
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                form.querySelector('input:not([type="hidden"])')?.focus();
            }, 100);
        }

        window.abrirModalRol = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo rol';
            btnText.textContent = 'Crear rol';
            abrir();
        };

        window.abrirModalEditarRol = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar rol';
            btnText.textContent = 'Guardar cambios';

            try {
                const res = await fetch(`${URL_BASE_ROLES}/${id}/json`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.rol) throw new Error('No se pudo cargar el rol');

                const r = data.rol;
                idEl.value = r.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_ROLES}/${r.id}`;

                document.getElementById('m_rol_nombre').value = r.nombre || '';
                document.getElementById('m_rol_descripcion').value = r.descripcion || '';
                document.getElementById('m_rol_habilitado').checked = !!r.habilitado;

                abrir();
            } catch (e) {
                if (window.mostrarToast) window.mostrarToast('error', e.message || 'Error al cargar el rol');
            }
        };

        window.cerrarModalRol = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalRol();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarRol');
            const formData = new FormData(form);
            const isEdit = methodEl.value === 'PUT';

            btn.disabled = true;
            btnText.textContent = 'Guardando…';
            limpiarErrores();

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                            formData.get('_token'),
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    window.cerrarModalRol();
                    await window.recargarTablaRoles();
                    if (window.mostrarToast) {
                        window.mostrarToast('success', data.message || (isEdit ? 'Rol actualizado' : 'Rol creado'));
                    }
                    return;
                }

                if (res.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([campo, mensajes]) => {
                        const span = form.querySelector(`[data-error="${campo}"]`);
                        if (span) span.textContent = mensajes[0];
                        form.querySelector(`[name="${campo}"]`)?.classList.add('is-invalid');
                    });
                    if (window.mostrarToast) window.mostrarToast('warning', 'Revisa los campos marcados');
                    return;
                }

                if (window.mostrarToast) window.mostrarToast('error', data.message || 'Error al guardar');
            } catch (err) {
                if (window.mostrarToast) window.mostrarToast('error', err.message || 'Error de red');
            } finally {
                btn.disabled = false;
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear rol';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaRoles();
    });
</script>
@endpush