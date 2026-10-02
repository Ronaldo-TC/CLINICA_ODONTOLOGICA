<div class="modal-backdrop" id="modalCaja">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formCaja" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formCajaMethod" value="POST">
            <input type="hidden" name="caja_id" id="formCajaId" value="">

            <div class="modal-header">
                <h3 id="modalCajaTitulo">Nueva Caja</h3>
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
                    <div class="form-group">
                        <label for="caja-recibo">Número de recibo</label>
                        <input type="number" id="caja-recibo" name="recibo"
                            class="form-control" min="0" placeholder="Ej: 1001">
                        <span class="form-error" data-error-for="recibo"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-paciente">Paciente</label>
                        <input type="text" id="caja-paciente" name="paciente_nombre_completo"
                            class="form-control" maxlength="100"
                            placeholder="Nombre completo del paciente">
                        <span class="form-error" data-error-for="paciente_nombre_completo"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-ci">CI</label>
                        <input type="number" id="caja-ci" name="ci"
                            class="form-control" min="0" placeholder="Ej: 12345678">
                        <span class="form-error" data-error-for="ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-grado">Grado / Curso</label>
                        <input type="text" id="caja-grado" name="grado"
                            class="form-control" maxlength="100"
                            placeholder="Ej: 5to año">
                        <span class="form-error" data-error-for="grado"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="caja-tratamiento">Tratamiento <span class="req">*</span></label>
                        <select id="caja-tratamiento" name="tratamiento_id"
                            class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="tratamiento_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-clinica">Clínica <span class="req">*</span></label>
                        <select id="caja-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-tipo-pago">Tipo de pago</label>
                        <input type="text" id="caja-tipo-pago" name="tipo_pago"
                            class="form-control" maxlength="100"
                            placeholder="Ej: Efectivo" list="listaTipoPago">
                        <datalist id="listaTipoPago">
                            <option>Efectivo</option>
                            <option>Transferencia</option>
                            <option>Tarjeta</option>
                            <option>QR</option>
                        </datalist>
                        <span class="form-error" data-error-for="tipo_pago"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-costo-total">Costo total (Bs) <span class="req">*</span></label>
                        <input type="number" id="caja-costo-total" name="costo_total"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="costo_total"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-cancelado">Cancelado (Bs)</label>
                        <input type="number" id="caja-cancelado" name="cancelado"
                            class="form-control" min="0" step="0.01"
                            placeholder="0.00" value="0">
                        <span class="form-error" data-error-for="cancelado"></span>
                    </div>

                    <div class="form-group">
                        <label for="caja-tipo">Tipo</label>
                        <input type="text" id="caja-tipo" name="tipo"
                            class="form-control" maxlength="100"
                            placeholder="Ej: Consulta">
                        <span class="form-error" data-error-for="tipo"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="caja-descripcion">Descripción</label>
                        <input type="text" id="caja-descripcion" name="descripcion"
                            class="form-control" maxlength="100"
                            placeholder="Descripción breve...">
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Estado</label>
                        <label class="switch-item">
                            <input type="checkbox" name="habilitada" id="caja-habilitada" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitada</span>
                        </label>
                    </div>
                </div>

                <div id="formCajaError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formCajaErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarCaja">
                    <span id="btnGuardarCajaText">Guardar Caja</span>
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
            store: @json(route('cajas.store')),
            update: (id) => `/cajas/${id}`,
        };

        const $modal = document.getElementById('modalCaja');
        const $form = document.getElementById('formCaja');
        const $titulo = document.getElementById('modalCajaTitulo');
        const $id = document.getElementById('formCajaId');
        const $btnGuard = document.getElementById('btnGuardarCaja');
        const $btnText = document.getElementById('btnGuardarCajaText');
        const $errorBox = document.getElementById('formCajaError');
        const $errorTxt = document.getElementById('formCajaErrorText');

        window.CajaModal = {
            abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nueva Caja';
                $id.value = '';
                $btnText.textContent = 'Registrar Caja';
                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Caja #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Caja';

                const c = data.registro || data;

                // Cargar selects primero
                cargarSelects(data);

                // Asignar valores después de un tick
                setTimeout(() => {
                    ['recibo', 'monto', 'costo_total', 'cancelado', 'descripcion',
                        'ci', 'tipo', 'tipo_pago', 'paciente_nombre_completo', 'grado',
                        'tratamiento_id', 'clinica_id', 'remision_id'
                    ].forEach((f) => {
                        if ($form[f] && c[f] !== undefined && c[f] !== null) {
                            $form[f].value = c[f];
                        }
                    });

                    $form.habilitada.checked = !!c.habilitada;
                }, 50);

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        function cargarSelects(data) {
            const opts = (arr, valueKey, labelFn) =>
                arr.map((item) => `<option value="${item[valueKey]}">${labelFn(item)}</option>`).join('');

            $form.tratamiento_id.innerHTML = '<option value="">Seleccione un tratamiento...</option>' +
                opts(data.tratamientos || [], 'id', (t) =>
                    `${t.nombre} (${Number(t.costo).toFixed(2)} Bs)`);

            $form.clinica_id.innerHTML = '<option value="">Seleccione una clínica...</option>' +
                opts(data.clinicas || [], 'id', (c) =>
                    `${c.especialidad} (${c.modulo || ''})`);
        }

        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? URLS.update(id) : URLS.store;

            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            formData.set('habilitada', $form.habilitada.checked ? '1' : '0');

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
                window.CajasLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('caja:guardada'));
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
            setTimeout(() => $form.recibo?.focus(), 100);
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
            $form.habilitada.checked = true;
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
                ($id.value ? 'Actualizar Caja' : 'Registrar Caja');
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