<div class="modal-backdrop" id="modalRol">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalRolTitulo">
        <form id="formRol" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formRolMethod" value="POST">
            <input type="hidden" name="rol_id" id="formRolId" value="">

            <div class="modal-header">
                <h3 id="modalRolTitulo">Nuevo Rol</h3>
                <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="rol-name">Nombre del rol <span class="req">*</span></label>
                        <input type="text" id="rol-name" name="name"
                            class="form-control" required maxlength="100"
                            placeholder="Ej: odontologo">
                        <small style="color:var(--muted); font-size:11px;">
                            Solo letras, números, guión y guión bajo.
                        </small>
                        <span class="form-error" data-error-for="name"></span>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="rol-habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitado</span>
                        </label>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="rol-descripcion">Descripción</label>
                        <textarea id="rol-descripcion" name="descripcion"
                            class="form-control" maxlength="255" rows="2"
                            placeholder="Describe el rol brevemente..."></textarea>
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>
                </div>

                {{-- Matriz de permisos --}}
                <div class="form-group" style="margin-top: 20px;">
                    <label>Permisos del rol</label>

                    <div class="form-actions" style="margin-bottom: 10px;">
                        <button type="button" class="btn btn-sm btn-secondary" id="btnMarcarTodos">
                            Marcar todos
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btnDesmarcarTodos">
                            Desmarcar todos
                        </button>
                        <span class="card-meta" style="margin-left:auto;">
                            Total: <strong>{{ $totalPermisos }}</strong> permisos
                        </span>
                    </div>

                    <div class="checkbox-grid" id="permisosGrid" style="max-height: 420px;">
                        @foreach ($permisosAgrupados as $modulo => $permisos)
                        <div style="grid-column: 1 / -1; display:flex; align-items:center; justify-content:space-between; padding:6px 8px; border-bottom:1px solid var(--line); margin-top:6px;">
                            <strong style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:var(--teal);">
                                {{ $modulo }}
                            </strong>
                            <label style="cursor:pointer;">
                                <input type="checkbox"
                                    class="permiso-modulo-toggle"
                                    data-modulo="{{ $modulo }}"
                                    style="accent-color:var(--cyan); cursor:pointer;">
                            </label>
                        </div>

                        @foreach ($permisos as $permiso)
                        <label class="checkbox-item">
                            <input type="checkbox"
                                name="permisos[]"
                                value="{{ $permiso['name'] }}"
                                data-modulo="{{ $modulo }}">
                            <span>{{ $permiso['accion'] }}</span>
                        </label>
                        @endforeach
                        @endforeach
                    </div>
                </div>

                {{-- Error general --}}
                <div id="formRolError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formRolErrorText">Corrige los errores del formulario.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnGuardarRol">
                    <span id="btnGuardarRolText">Guardar Rol</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            store: @json(route('roles.store')),
            update: (id) => `/roles/${id}`,
        };

        const $modal = document.getElementById('modalRol');
        const $form = document.getElementById('formRol');
        const $titulo = document.getElementById('modalRolTitulo');
        const $id = document.getElementById('formRolId');
        const $btnGuard = document.getElementById('btnGuardarRol');
        const $btnText = document.getElementById('btnGuardarRolText');
        const $errorBox = document.getElementById('formRolError');
        const $errorTxt = document.getElementById('formRolErrorText');

        /* ============================================================
           API PÚBLICA
           ============================================================ */
        window.RolModal = {
            abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Rol';
                $id.value = '';
                $btnText.textContent = 'Registrar Rol';
                habilitarCampos(true);
                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Rol';
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Rol';
                habilitarCampos(true);

                const r = data.registro || data;

                $form.name.value = r.name ?? '';
                $form.descripcion.value = r.descripcion ?? '';
                $form.habilitado.checked = !!r.habilitado;

                // Marcar permisos
                const seleccionados = data.permisosSeleccionados || [];
                document.querySelectorAll('input[name="permisos[]"]').forEach((cb) => {
                    cb.checked = seleccionados.includes(cb.value);
                });
                actualizarModulos();

                openModal();
            },

            abrirPermisos(data) {
                this.abrirEdicion(data);
                $titulo.textContent = 'Permisos del Rol: ' + (data.name || '');
                $btnText.textContent = 'Guardar Permisos';

                // Bloquear campos de identidad
                habilitarCampos(false);
            },

            cerrar() {
                closeModal();
            },
        };

        /* ============================================================
           HABILITAR / DESHABILITAR CAMPOS
           ============================================================ */
        function habilitarCampos(habilitar) {
            $form.name.toggleAttribute('readonly', !habilitar);
            $form.descripcion.toggleAttribute('readonly', !habilitar);
            $form.habilitado.disabled = !habilitar;
        }

        /* ============================================================
           TOGGLE MÓDULOS
           ============================================================ */
        document.querySelectorAll('.permiso-modulo-toggle').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const modulo = toggle.dataset.modulo;
                document.querySelectorAll(`input[name="permisos[]"][data-modulo="${modulo}"]`)
                    .forEach((cb) => cb.checked = toggle.checked);
            });
        });

        function actualizarModulos() {
            document.querySelectorAll('.permiso-modulo-toggle').forEach((toggle) => {
                const modulo = toggle.dataset.modulo;
                const items = [...document.querySelectorAll(`input[name="permisos[]"][data-modulo="${modulo}"]`)];
                const marcados = items.filter((i) => i.checked).length;
                toggle.checked = items.length > 0 && marcados === items.length;
                toggle.indeterminate = marcados > 0 && marcados < items.length;
            });
        }

        /* ============================================================
           MARCAR / DESMARCAR TODOS
           ============================================================ */
        document.getElementById('btnMarcarTodos')?.addEventListener('click', () => {
            document.querySelectorAll('input[name="permisos[]"]').forEach((cb) => cb.checked = true);
            actualizarModulos();
        });

        document.getElementById('btnDesmarcarTodos')?.addEventListener('click', () => {
            document.querySelectorAll('input[name="permisos[]"]').forEach((cb) => cb.checked = false);
            actualizarModulos();
        });

        /* ============================================================
           SUBMIT
           ============================================================ */
        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? URLS.update(id) : URLS.store;

            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            // Normalizar checkboxes
            formData.set('habilitado', $form.habilitado.checked ? '1' : '0');

            // Normalizar strings
            ['descripcion'].forEach((field) => {
                const val = formData.get(field);
                if (val === null || val === undefined) {
                    formData.set(field, '');
                }
            });

            setLoading(true);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (res.status === 422) {
                    mostrarErrores(data.errors || {});
                    throw new Error(data.message || 'Revisa los campos.');
                }

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al guardar.');
                }

                closeModal();
                window.RolesLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('rol:guardado'));
            } catch (err) {
                if (Object.keys(collectErrors()).length === 0) {
                    $errorTxt.textContent = err.message || 'Error inesperado.';
                    $errorBox.style.display = 'flex';
                }
            } finally {
                setLoading(false);
            }
        });

        /* ============================================================
           HELPERS
           ============================================================ */
        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            setTimeout(() => $form.name?.focus(), 100);
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';
            clearErrors();
            actualizarModulos();
            $errorBox.style.display = 'none';
            $form.habilitado.checked = true;
        }

        function clearErrors() {
            document.querySelectorAll('[data-error-for]').forEach((el) => el.textContent = '');
            $errorBox.style.display = 'none';
        }

        function mostrarErrores(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const $err = document.querySelector(`[data-error-for="${field}"]`);
                if ($err) $err.textContent = Array.isArray(messages) ? messages[0] : messages;
            });

            $errorTxt.textContent = 'Corrige los errores marcados en rojo.';
            $errorBox.style.display = 'flex';
        }

        function collectErrors() {
            const errors = {};
            document.querySelectorAll('[data-error-for]').forEach((el) => {
                if (el.textContent.trim()) errors[el.dataset.errorFor] = el.textContent;
            });
            return errors;
        }

        function setLoading(loading) {
            $btnGuard.disabled = loading;
            $btnText.textContent = loading ?
                'Guardando...' :
                ($id.value ? 'Actualizar Rol' : 'Registrar Rol');
        }

        /* ============================================================
           CERRAR MODAL
           ============================================================ */
        $modal?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]')) closeModal();
            if (e.target === $modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) closeModal();
        });
    })();
</script>
@endpush