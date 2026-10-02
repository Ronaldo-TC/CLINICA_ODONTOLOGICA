{{-- ============================================================
     MODAL DE INSUMO (crear / editar)
     ============================================================ --}}
<div class="modal-backdrop" id="modalInsumo">
    <div class="modal modal-lg" role="dialog" aria-modal="true"
        aria-labelledby="modalInsumoTitulo">
        <form id="formInsumo" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="insumo_id" id="formInsumoId" value="">

            <div class="modal-header">
                <h3 id="modalInsumoTitulo">Nuevo Insumo</h3>
                <button type="button" class="modal-close"
                    data-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
                    {{-- Nombre --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="nombre">
                            Nombre del insumo <span class="req">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre"
                            class="form-control" required maxlength="100"
                            placeholder="Ej: Resina compuesta A2">
                        <span class="form-error" data-error-for="nombre"></span>
                    </div>

                    {{-- Clínica --}}
                    <div class="form-group">
                        <label for="clinica_id">
                            Clínica <span class="req">*</span>
                        </label>
                        <select id="clinica_id" name="clinica_id" class="form-control" required>
                            <option value="">Seleccione una clínica...</option>
                            @foreach ($clinicas as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    {{-- Tipo de insumo --}}
                    <div class="form-group">
                        <label for="insumo_tratamiento_id">
                            Tipo de insumo <span class="req">*</span>
                        </label>
                        <select id="insumo_tratamiento_id" name="insumo_tratamiento_id"
                            class="form-control" required>
                            <option value="">Primero seleccione una clínica</option>
                        </select>
                        <span class="form-error" data-error-for="insumo_tratamiento_id"></span>
                    </div>

                    {{-- Cantidad numérica --}}
                    <div class="form-group">
                        <label for="cantidad_numerica">
                            Cantidad <span class="req">*</span>
                        </label>
                        <input type="number" id="cantidad_numerica" name="cantidad_numerica"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="cantidad_numerica"></span>
                    </div>

                    {{-- Unidad --}}
                    <div class="form-group">
                        <label for="cantidad">Unidad de medida</label>
                        <input type="text" id="cantidad" name="cantidad"
                            class="form-control" maxlength="100"
                            placeholder="Ej: cajas, frascos, unidades">
                        <span class="form-error" data-error-for="cantidad"></span>
                    </div>

                    {{-- Stock mínimo --}}
                    <div class="form-group">
                        <label for="stock_minimo">
                            Stock mínimo <span class="req">*</span>
                        </label>
                        <input type="number" id="stock_minimo" name="stock_minimo"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="stock_minimo"></span>
                    </div>

                    {{-- Costo unitario --}}
                    <div class="form-group">
                        <label for="costo_unitario">
                            Costo unitario (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" id="costo_unitario" name="costo_unitario"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="costo_unitario"></span>
                    </div>
                </div>

                {{-- Alerta general de errores --}}
                <div id="formInsumoError" class="alert alert-danger"
                    style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formInsumoErrorText">Corrige los errores del formulario.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarInsumo">
                    <span id="btnGuardarInsumoText">Guardar Insumo</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
        const $modal = document.getElementById('modalInsumo');
        const $form = document.getElementById('formInsumo');
        const $titulo = document.getElementById('modalInsumoTitulo');
        const $method = document.getElementById('formMethod');
        const $id = document.getElementById('formInsumoId');
        const $btnGuardar = document.getElementById('btnGuardarInsumo');
        const $btnText = document.getElementById('btnGuardarInsumoText');
        const $errorBox = document.getElementById('formInsumoError');
        const $errorTxt = document.getElementById('formInsumoErrorText');
        const $clinica = document.getElementById('clinica_id');
        const $tipo = document.getElementById('insumo_tratamiento_id');

        // ─── Abrir modal (crear) ──────────────────────────────────
        document.getElementById('btnNuevoInsumo')?.addEventListener('click', () => {
            resetForm();
            $titulo.textContent = 'Nuevo Insumo';
            $method.value = 'POST';
            $btnText.textContent = 'Registrar Insumo';
            openModal();
        });

        // ─── Cerrar modal ─────────────────────────────────────────
        $modal?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]')) closeModal();
            if (e.target === $modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) closeModal();
        });

        // ─── Cargar tipos de insumo según clínica ─────────────────
        $clinica?.addEventListener('change', async () => {
            const clinicaId = $clinica.value;
            const currentTipo = $tipo.dataset.selected || '';

            $tipo.innerHTML = '<option value="">Cargando...</option>';
            $tipo.disabled = true;

            if (!clinicaId) {
                $tipo.innerHTML = '<option value="">Primero seleccione una clínica</option>';
                $tipo.disabled = false;
                return;
            }

            try {
                const res = await fetch(`/insumos/tipos-por-clinica/${clinicaId}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error();

                const tipos = await res.json();

                $tipo.innerHTML = '<option value="">Seleccione un tipo...</option>';
                tipos.forEach((t) => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.nombre;
                    if (String(t.id) === String(currentTipo)) opt.selected = true;
                    $tipo.appendChild(opt);
                });
            } catch (err) {
                console.error(err);
                $tipo.innerHTML = '<option value="">Error al cargar tipos</option>';
            } finally {
                $tipo.disabled = false;
            }
        });

        // ─── Submit del formulario ────────────────────────────────
        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? `/insumos/${id}` : @json(route('insumos.store'));
            const method = isEdit ? 'PUT' : 'POST';

            // Construir FormData con _method si es edición
            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            setLoading(true);

            try {
                const res = await fetch(url, {
                    method: 'POST', // Laravel interpreta _method
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

                // Éxito
                closeModal();
                window.InsumosLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('insumo:guardado'));
            } catch (err) {
                if (Object.keys(collectErrors()).length === 0) {
                    $errorTxt.textContent = err.message || 'Error inesperado.';
                    $errorBox.style.display = 'flex';
                }
            } finally {
                setLoading(false);
            }
        });

        // ─── API pública del modal ────────────────────────────────
        window.InsumosModal = {
            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Insumo';
                $method.value = 'PUT';
                $btnText.textContent = 'Actualizar Insumo';
                $id.value = data.id;

                // Llenar campos
                $form.nombre.value = data.nombre ?? '';
                $form.cantidad_numerica.value = data.cantidad_numerica ?? '';
                $form.cantidad.value = data.cantidad ?? '';
                $form.stock_minimo.value = data.stock_minimo ?? '';
                $form.costo_unitario.value = data.costo_unitario ?? '';

                // Clínica primero
                $clinica.value = data.clinica_id ?? '';
                $tipo.dataset.selected = data.insumo_tratamiento_id ?? '';

                // Disparar carga de tipos
                $clinica.dispatchEvent(new Event('change'));

                openModal();
            },
        };

        // ─── Helpers ──────────────────────────────────────────────
        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => $form.nombre?.focus(), 100);
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }

        function resetForm() {
            $form.reset();
            $id.value = '';
            $method.value = 'POST';
            clearErrors();
            $errorBox.style.display = 'none';
        }

        function clearErrors() {
            document.querySelectorAll('[data-error-for]').forEach((el) => el.textContent = '');
            document.querySelectorAll('.form-control').forEach((el) => el.classList.remove('is-invalid'));
            $errorBox.style.display = 'none';
        }

        function mostrarErrores(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const $err = document.querySelector(`[data-error-for="${field}"]`);
                if ($err) $err.textContent = Array.isArray(messages) ? messages[0] : messages;

                const $input = $form.querySelector(`[name="${field}"]`);
                if ($input) $input.classList.add('is-invalid');
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
            $btnGuardar.disabled = loading;
            $btnText.textContent = loading ?
                'Guardando...' :
                ($id.value ? 'Actualizar Insumo' : 'Registrar Insumo');
        }
    })();
</script>
@endpush