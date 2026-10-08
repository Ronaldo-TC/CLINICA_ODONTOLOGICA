<div class="modal-backdrop" id="modalRadiologia">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formRadiologia" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formRadiologiaMethod" value="POST">
            <input type="hidden" name="radiologia_id" id="formRadiologiaId" value="">

            <div class="modal-header">
                <h3 id="modalRadiologiaTitulo">Nuevo Estudio Radiológico</h3>
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

                    {{-- Paciente (autocomplete) --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="rad-paciente-buscar">Paciente</label>
                        <div class="autocomplete-wrap" id="wrapPaciente">
                            <input type="hidden" name="paciente_id" id="rad-paciente-id">
                            <input type="text" id="rad-paciente-buscar"
                                class="form-control autocomplete-input"
                                placeholder="Escribe nombre o CI del paciente..."
                                autocomplete="off">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" id="dropdownPaciente"></div>
                        </div>
                        <span class="form-error" data-error-for="paciente_id"></span>
                    </div>

                    {{-- Caja (autocomplete) --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="rad-caja-buscar">Caja (Recibo) <span class="req">*</span></label>
                        <div class="autocomplete-wrap" id="wrapCaja">
                            <input type="hidden" name="caja_id" id="rad-caja-id">
                            <input type="text" id="rad-caja-buscar"
                                class="form-control autocomplete-input"
                                placeholder="Escribe número de recibo o nombre del paciente..."
                                autocomplete="off">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" id="dropdownCaja"></div>
                        </div>
                        <span class="form-error" data-error-for="caja_id"></span>
                    </div>

                    {{-- Descripción --}}
                    <div class="form-group">
                        <label for="rad-descripcion">Descripción <span class="req">*</span></label>
                        <input type="text" id="rad-descripcion" name="descripcion"
                            class="form-control" required maxlength="100" list="tipos-estudio-radiologico"
                            placeholder="Ej: Radiografía periapical">
                        <datalist id="tipos-estudio-radiologico"><option value="Radiografía periapical"><option value="Radiografía panorámica"><option value="Tomografía CBCT"><option value="Tomografía computarizada"></datalist>
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>

                    {{-- Estado --}}
                    <div class="form-group">
                        <label for="rad-estado">Estado <span class="req">*</span></label>
                        <select id="rad-estado" name="estado" class="form-control" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="realizado">Realizado</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                        <span class="form-error" data-error-for="estado"></span>
                    </div>

                    {{-- Observación --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="rad-observacion">Observación</label>
                        <input type="text" id="rad-observacion" name="observacion"
                            class="form-control" maxlength="100"
                            placeholder="Observaciones...">
                        <span class="form-error" data-error-for="observacion"></span>
                    </div>

                    {{-- Clínica --}}
                    <div class="form-group">
                        <label for="rad-clinica">Clínica <span class="req">*</span></label>
                        <select id="rad-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    {{-- Fecha de toma --}}
                    <div class="form-group">
                        <label for="rad-fecha-toma">Fecha de toma</label>
                        <input type="datetime-local" id="rad-fecha-toma" name="fecha_toma"
                            class="form-control">
                        <span class="form-error" data-error-for="fecha_toma"></span>
                    </div>
                </div>

                <div id="formRadiologiaError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formRadiologiaErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarRadiologia">
                    <span id="btnGuardarRadiologiaText">Guardar Estudio</span>
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
            store: @json(route('radiologia.store')),
            update: (id) => `/radiologia/${id}`,
            datosIniciales: @json(route('radiologia.datos-iniciales')),
            buscarPacientes: @json(route('radiologia.buscar-pacientes')),
            buscarCajas: @json(route('radiologia.buscar-cajas')),
        };

        let datosInicialesCargados = false;

        const $modal = document.getElementById('modalRadiologia');
        const $form = document.getElementById('formRadiologia');
        const $titulo = document.getElementById('modalRadiologiaTitulo');
        const $id = document.getElementById('formRadiologiaId');
        const $btnGuard = document.getElementById('btnGuardarRadiologia');
        const $btnText = document.getElementById('btnGuardarRadiologiaText');
        const $errorBox = document.getElementById('formRadiologiaError');
        const $errorTxt = document.getElementById('formRadiologiaErrorText');

        window.RadiologiaModal = {
            async abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Estudio Radiológico';
                $id.value = '';
                $btnText.textContent = 'Registrar Estudio';
                await cargarDatosIniciales();
                openModal();
            },

            async abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Estudio #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Estudio';

                await cargarDatosIniciales();

                const r = data.registro || data;

                $form.paciente_id.value = r.paciente_id ?? '';
                $form.caja_id.value = r.caja_id ?? '';
                $form.descripcion.value = r.descripcion ?? '';
                $form.observacion.value = r.observacion ?? '';
                $form.estado.value = r.estado ?? 'pendiente';
                $form.clinica_id.value = r.clinica_id ?? '';
                $form.fecha_toma.value = r.fecha_toma ?
                    r.fecha_toma.substring(0, 16) :
                    '';

                if (r.paciente) {
                    document.getElementById('rad-paciente-id').value = r.paciente.id;
                    document.getElementById('rad-paciente-buscar').value = r.paciente.nombre_completo;
                    document.getElementById('wrapPaciente').classList.add('has-value');
                }
                if (r.caja) {
                    document.getElementById('rad-caja-id').value = r.caja.id;
                    document.getElementById('rad-caja-buscar').value = 'Recibo #' + (r.caja.recibo || r.caja.id);
                    document.getElementById('wrapCaja').classList.add('has-value');
                }

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        async function cargarDatosIniciales() {
            if (datosInicialesCargados) return;

            try {
                const res = await fetch(URLS.datosIniciales, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error();
                const data = await res.json();

                $form.clinica_id.innerHTML = '<option value="">Seleccione...</option>' +
                    data.clinicas.map((c) => `<option value="${c.id}">${c.texto}</option>`).join('');

                datosInicialesCargados = true;
            } catch (err) {
                console.error('[Radiologia] Error:', err);
            }
        }

        /* Autocomplete genérico */
        function setupAutocomplete(campo, urlBuilder) {
            const $wrap = document.getElementById(`wrap${campo}`);
            const $input = document.getElementById(`rad-${campo.toLowerCase()}-buscar`);
            const $hidden = document.getElementById(`rad-${campo.toLowerCase()}-id`);
            const $dropdown = document.getElementById(`dropdown${campo}`);

            if (!$input || !$dropdown) return;

            let debounceTimer = null;
            let abortController = null;

            $input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const q = $input.value.trim();

                if (q === '') {
                    $hidden.value = '';
                    $wrap.classList.remove('has-value');
                    $dropdown.classList.remove('open');
                    return;
                }

                debounceTimer = setTimeout(() => buscar(q), 300);
            });

            document.addEventListener('click', (e) => {
                if (!$wrap.contains(e.target)) $dropdown.classList.remove('open');
            });

            $wrap.querySelector('.autocomplete-clear')?.addEventListener('click', () => {
                $input.value = '';
                $hidden.value = '';
                $wrap.classList.remove('has-value');
                $dropdown.classList.remove('open');
                $input.focus();
            });

            async function buscar(q) {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                $wrap.classList.add('loading');

                try {
                    const res = await fetch(urlBuilder(q), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        signal: abortController.signal,
                    });

                    if (!res.ok) throw new Error();
                    const items = await res.json();
                    render(items);
                } catch (err) {
                    if (err.name !== 'AbortError') console.error(err);
                } finally {
                    $wrap.classList.remove('loading');
                }
            }

            function render(items) {
                if (!items || items.length === 0) {
                    $dropdown.innerHTML = `<div class="autocomplete-empty"><p>Sin resultados</p></div>`;
                    $dropdown.classList.add('open');
                    return;
                }

                $dropdown.innerHTML = items.map((item) => `
                <div class="autocomplete-item"
                     data-id="${item.id}"
                     data-texto="${item.texto}"
                     data-extra="${item.extra || ''}">
                    <div class="autocomplete-avatar">
                        ${item.iniciales || item.texto.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="autocomplete-info">
                        <span class="autocomplete-name">${item.texto}</span>
                        ${item.extra ? `<span class="autocomplete-meta">${item.extra}</span>` : ''}
                    </div>
                </div>
            `).join('');

                $dropdown.classList.add('open');

                $dropdown.querySelectorAll('.autocomplete-item').forEach((el) => {
                    el.addEventListener('click', () => {
                        $hidden.value = el.dataset.id;
                        $input.value = el.dataset.texto;
                        $wrap.classList.add('has-value');
                        $dropdown.classList.remove('open');
                    });
                });
            }
        }

        setupAutocomplete('Paciente', (q) => `${URLS.buscarPacientes}?q=${encodeURIComponent(q)}`);
        setupAutocomplete('Caja', (q) => `${URLS.buscarCajas}?q=${encodeURIComponent(q)}`);

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
                window.RadiologiasLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('radiologia:guardada'));
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
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';

            ['Paciente', 'Caja'].forEach((campo) => {
                const $input = document.getElementById(`rad-${campo.toLowerCase()}-buscar`);
                const $hidden = document.getElementById(`rad-${campo.toLowerCase()}-id`);
                const $wrap = document.getElementById(`wrap${campo}`);
                if ($input) $input.value = '';
                if ($hidden) $hidden.value = '';
                if ($wrap) $wrap.classList.remove('has-value');
            });

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
                ($id.value ? 'Actualizar Estudio' : 'Registrar Estudio');
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
