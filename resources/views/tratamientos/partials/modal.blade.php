<div class="modal-backdrop" id="modalTratamiento">
    <div class="modal" role="dialog" aria-modal="true">
        <form id="formTratamiento" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formTratamientoMethod" value="POST">
            <input type="hidden" name="tratamiento_id" id="formTratamientoId" value="">

            <div class="modal-header">
                <h3 id="modalTratamientoTitulo">Nuevo Tratamiento</h3>
                <button type="button" class="modal-close" data-modal-close>
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
                        <label for="trat-nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="trat-nombre" name="nombre"
                            class="form-control" required maxlength="100"
                            placeholder="Ej: Endodoncia unirradicular">
                        <span class="form-error" data-error-for="nombre"></span>
                    </div>

                    <p style="grid-column:1 / -1; color:var(--muted);">El costo puede ingresarse aquí y actualizarse al editar el tratamiento. Los materiales se asignan desde Insumos.</p>

                    <div class="form-group">
                        <label for="trat-costo">Costo (Bs)</label>
                        <input type="number" id="trat-costo" name="costo" class="form-control"
                            min="0" max="99999999.99" step="0.01" placeholder="Ej: 150.00">
                        <span class="form-error" data-error-for="costo"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="trat-clinica">Clínica <span class="req">*</span></label>
                        <select id="trat-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Seleccione una clínica...</option>
                            @foreach ($clinicas as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }} ({{ $c->modulo }})</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="trat-descripcion">Descripción</label>
                        <input type="text" id="trat-descripcion" name="descripcion"
                            class="form-control" maxlength="100"
                            placeholder="Descripción breve...">
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>
                </div>

                <div id="formTratamientoError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formTratamientoErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarTratamiento">
                    <span id="btnGuardarTratamientoText">Guardar Tratamiento</span>
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
            store: @json(route('tratamientos.store')),
            update: (id) => `/tratamientos/${id}`,
        };

        const $modal = document.getElementById('modalTratamiento');
        const $form = document.getElementById('formTratamiento');
        const $titulo = document.getElementById('modalTratamientoTitulo');
        const $id = document.getElementById('formTratamientoId');
        const $btnGuard = document.getElementById('btnGuardarTratamiento');
        const $btnText = document.getElementById('btnGuardarTratamientoText');
        const $errorBox = document.getElementById('formTratamientoError');
        const $errorTxt = document.getElementById('formTratamientoErrorText');

        window.TratamientoModal = {
            abrirCrear(clinicaId = '') {
                resetForm();
                $titulo.textContent = 'Nuevo Tratamiento';
                $id.value = '';
                $btnText.textContent = 'Registrar Tratamiento';
                $form.clinica_id.value = clinicaId;
                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Tratamiento';
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Tratamiento';

                const t = data.registro || data;

                $form.nombre.value = t.nombre ?? '';
                $form.descripcion.value = t.descripcion ?? '';
                $form.costo.value = t.precio_asignado ? t.costo : '';
                $form.clinica_id.value = t.clinica_id ?? '';

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
                window.TratamientosLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('tratamiento:guardado'));
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
                ($id.value ? 'Actualizar Tratamiento' : 'Registrar Tratamiento');
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
