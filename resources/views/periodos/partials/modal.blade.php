<div class="modal-backdrop" id="modalPeriodo">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalPeriodoTitulo">
        <form id="formPeriodo" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formPeriodoMethod" value="POST">
            <input type="hidden" name="periodo_id" id="formPeriodoId" value="">

            <div class="modal-header">
                <h3 id="modalPeriodoTitulo">Nuevo Periodo</h3>
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
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="periodo-nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="periodo-nombre" name="nombre"
                            class="form-control" required maxlength="100"
                            placeholder="Ej: Gestión 2026 - Semestre 1">
                        <span class="form-error" data-error-for="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="periodo-gestion">Gestión <span class="req">*</span></label>
                        <input type="number" id="periodo-gestion" name="gestion"
                            class="form-control" required min="2000" max="2100"
                            placeholder="Ej: 2026">
                        <span class="form-error" data-error-for="gestion"></span>
                    </div>

                    <div class="form-group">
                        <label for="periodo-semestre">Semestre <span class="req">*</span></label>
                        <select id="periodo-semestre" name="semestre" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="1">1° Semestre</option>
                            <option value="2">2° Semestre</option>
                        </select>
                        <span class="form-error" data-error-for="semestre"></span>
                    </div>

                    <div class="form-group">
                        <label for="periodo-fecha-inicio">Fecha de inicio <span class="req">*</span></label>
                        <input type="date" id="periodo-fecha-inicio" name="fecha_inicio"
                            class="form-control" required>
                        <span class="form-error" data-error-for="fecha_inicio"></span>
                    </div>

                    <div class="form-group">
                        <label for="periodo-fecha-fin">Fecha de fin <span class="req">*</span></label>
                        <input type="date" id="periodo-fecha-fin" name="fecha_fin"
                            class="form-control" required>
                        <span class="form-error" data-error-for="fecha_fin"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Estado</label>
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="periodo-habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitado</span>
                        </label>
                    </div>
                </div>

                <div id="formPeriodoError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formPeriodoErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnGuardarPeriodo">
                    <span id="btnGuardarPeriodoText">Guardar Periodo</span>
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
            store: @json(route('periodos.store')),
            update: (id) => `/periodos/${id}`,
        };

        const $modal = document.getElementById('modalPeriodo');
        const $form = document.getElementById('formPeriodo');
        const $titulo = document.getElementById('modalPeriodoTitulo');
        const $id = document.getElementById('formPeriodoId');
        const $btnGuard = document.getElementById('btnGuardarPeriodo');
        const $btnText = document.getElementById('btnGuardarPeriodoText');
        const $errorBox = document.getElementById('formPeriodoError');
        const $errorTxt = document.getElementById('formPeriodoErrorText');

        window.PeriodoModal = {
            abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Periodo';
                $id.value = '';
                $btnText.textContent = 'Registrar Periodo';
                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Periodo';
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Periodo';

                const p = data.registro || data;

                $form.nombre.value = p.nombre ?? '';
                $form.gestion.value = p.gestion ?? new Date().getFullYear();
                $form.semestre.value = p.semestre ?? 1;
                $form.fecha_inicio.value = p.fecha_inicio ?
                    p.fecha_inicio.substring(0, 10) :
                    '';
                $form.fecha_fin.value = p.fecha_fin ?
                    p.fecha_fin.substring(0, 10) :
                    '';
                $form.habilitado.checked = !!p.habilitado;

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? URLS.update(id) : URLS.store;

            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            formData.set('habilitado', $form.habilitado.checked ? '1' : '0');

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
                window.PeriodosLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('periodo:guardado'));
            } catch (err) {
                if (Object.keys(collectErrors()).length === 0) {
                    $errorTxt.textContent = err.message || 'Error inesperado.';
                    $errorBox.style.display = 'flex';
                }
            } finally {
                setLoading(false);
            }
        });

        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            setTimeout(() => $form.nombre?.focus(), 100);
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
            $form.gestion.value = new Date().getFullYear();
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
                ($id.value ? 'Actualizar Periodo' : 'Registrar Periodo');
        }

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