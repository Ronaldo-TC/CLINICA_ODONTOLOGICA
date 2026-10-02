<div class="modal-backdrop" id="modalUsuario">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalUsuarioTitulo">
        <form id="formUsuario" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formUsuarioMethod" value="POST">
            <input type="hidden" name="usuario_id" id="formUsuarioId" value="">

            <div class="modal-header">
                <h3 id="modalUsuarioTitulo">Nuevo Usuario</h3>
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
                        <label for="usu-ci">CI <span class="req">*</span></label>
                        <input type="text" id="usu-ci" name="ci"
                            class="form-control" required maxlength="50"
                            placeholder="Ej: 10000001">
                        <span class="form-error" data-error-for="ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="usu-nombre-usuario">Nombre de usuario <span class="req">*</span></label>
                        <input type="text" id="usu-nombre-usuario" name="nombre_usuario"
                            class="form-control" required maxlength="20"
                            placeholder="Ej: jperez">
                        <span class="form-error" data-error-for="nombre_usuario"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="usu-nombre-completo">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="usu-nombre-completo" name="nombre_completo"
                            class="form-control" required maxlength="200"
                            placeholder="Ej: Juan Pérez Mamani">
                        <span class="form-error" data-error-for="nombre_completo"></span>
                    </div>

                    <div class="form-group">
                        <label for="usu-contrasena">
                            Contraseña <span class="req" id="reqContrasena">*</span>
                        </label>
                        <div class="password-wrap">
                            <input type="password" id="usu-contrasena" name="contrasena"
                                class="form-control" minlength="6" maxlength="100"
                                placeholder="Mínimo 6 caracteres"
                                autocomplete="new-password">
                            <button type="button" class="password-toggle" tabindex="-1"
                                aria-label="Mostrar contraseña">
                                <svg class="eye-on" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg class="eye-off" viewBox="0 0 24 24">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        <small style="color:var(--muted); font-size:11px;" id="hintContrasena">
                            Obligatoria al crear.
                        </small>
                        <span class="form-error" data-error-for="contrasena"></span>
                    </div>

                    <div class="form-group">
                        <label for="usu-tipo">Tipo</label>
                        <input type="text" id="usu-tipo" name="tipo"
                            class="form-control" maxlength="20"
                            placeholder="Ej: docente, estudiante...">
                        <span class="form-error" data-error-for="tipo"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="usu-descripcion">Descripción</label>
                        <textarea id="usu-descripcion" name="descripcion"
                            class="form-control" maxlength="300" rows="2"
                            placeholder="Descripción breve del usuario..."></textarea>
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="usu-info">Información adicional</label>
                        <input type="text" id="usu-info" name="info"
                            class="form-control" maxlength="300"
                            placeholder="Info extra (opcional)">
                        <span class="form-error" data-error-for="info"></span>
                    </div>
                </div>

                {{-- Roles --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label>Roles asignados</label>
                    <div class="checkbox-grid" id="rolesGrid">
                        @foreach ($roles as $rol)
                        <label class="checkbox-item">
                            <input type="checkbox"
                                name="roles[]"
                                value="{{ $rol->id }}">
                            <span>{{ $rol->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    <span class="form-error" data-error-for="roles"></span>
                </div>

                {{-- Switches --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label>Configuración</label>
                    <div class="checkbox-grid">
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="usu-habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitado</span>
                        </label>

                        <label class="switch-item">
                            <input type="checkbox" name="permitir_login" id="usu-permitir-login" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Permitir login</span>
                        </label>

                        <label class="switch-item">
                            <input type="checkbox" name="multilogin" id="usu-multilogin" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Permitir multilogin</span>
                        </label>
                    </div>
                </div>

                {{-- Error general --}}
                <div id="formUsuarioError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formUsuarioErrorText">Corrige los errores del formulario.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">
                    <span id="btnGuardarUsuarioText">Guardar Usuario</span>
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
            store: @json(route('usuarios.store')),
            update: (id) => `/usuarios/${id}`,
        };

        const $modal = document.getElementById('modalUsuario');
        const $form = document.getElementById('formUsuario');
        const $titulo = document.getElementById('modalUsuarioTitulo');
        const $id = document.getElementById('formUsuarioId');
        const $btnGuard = document.getElementById('btnGuardarUsuario');
        const $btnText = document.getElementById('btnGuardarUsuarioText');
        const $errorBox = document.getElementById('formUsuarioError');
        const $errorTxt = document.getElementById('formUsuarioErrorText');
        const $reqPass = document.getElementById('reqContrasena');
        const $hintPass = document.getElementById('hintContrasena');

        /* ============================================================
           PASSWORD TOGGLE
           ============================================================ */
        document.querySelectorAll('.password-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const input = btn.closest('.password-wrap').querySelector('input');
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                btn.classList.toggle('visible', !visible);
            });
        });

        /* ============================================================
           API PÚBLICA DEL MODAL
           ============================================================ */
        window.UsuarioModal = {
            abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Usuario';
                $id.value = '';
                $btnText.textContent = 'Registrar Usuario';

                $reqPass.style.display = '';
                $hintPass.textContent = 'Obligatoria al crear.';
                $form.contrasena.setAttribute('required', 'required');

                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Usuario';
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Usuario';

                const u = data.registro || data.usuario || data;

                // Llenar campos
                $form.ci.value = u.ci ?? '';
                $form.nombre_usuario.value = u.nombre_usuario ?? '';
                $form.nombre_completo.value = u.nombre_completo ?? '';
                $form.descripcion.value = u.descripcion ?? '';
                $form.tipo.value = u.tipo ?? '';
                $form.info.value = u.info ?? '';
                $form.contrasena.value = '';

                // Switches
                $form.habilitado.checked = !!u.habilitado;
                $form.permitir_login.checked = !!u.permitir_login;
                $form.multilogin.checked = !!u.multilogin;

                // Contraseña opcional
                $reqPass.style.display = 'none';
                $hintPass.textContent = 'Dejar en blanco para no cambiar.';
                $form.contrasena.removeAttribute('required');

                // Roles seleccionados
                const rolesIds = data.rolesSeleccionados ||
                    (u.roles || []).map((r) => r.id);

                document.querySelectorAll('input[name="roles[]"]').forEach((cb) => {
                    cb.checked = rolesIds.includes(Number(cb.value));
                });

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        /* ============================================================
           SUBMIT DEL FORMULARIO
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
            formData.set('permitir_login', $form.permitir_login.checked ? '1' : '0');
            formData.set('multilogin', $form.multilogin.checked ? '1' : '0');

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

                // ✅ Éxito: cerrar modal + notificar a la lista
                closeModal();
                window.UsuariosLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('usuario:guardado'));
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
           HELPERS DEL MODAL
           ============================================================ */
        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            setTimeout(() => $form.ci?.focus(), 100);
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';
            clearErrors();
            $errorBox.style.display = 'none';

            $form.habilitado.checked = true;
            $form.permitir_login.checked = true;
            $form.multilogin.checked = false;
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
                ($id.value ? 'Actualizar Usuario' : 'Registrar Usuario');
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