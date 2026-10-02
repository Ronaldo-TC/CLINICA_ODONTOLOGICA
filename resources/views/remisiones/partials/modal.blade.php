<div class="modal-backdrop" id="modalRemision">
    <div class="modal" role="dialog" aria-modal="true">
        <form id="formRemision" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formRemisionMethod" value="POST">
            <input type="hidden" name="remision_id" id="formRemisionId" value="">

            <div class="modal-header">
                <h3 id="modalRemisionTitulo">Nueva Remisión</h3>
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
                    {{-- Admisión (autocomplete) --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="rem-admision-buscar">Admisión / Paciente <span class="req">*</span></label>
                        <div class="autocomplete-wrap" id="wrapAdmision">
                            <input type="hidden" name="admision_id" id="rem-admision-id">
                            <input type="hidden" name="paciente_id" id="rem-paciente-id">
                            <input type="text" id="rem-admision-buscar"
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
                            <div class="autocomplete-dropdown" id="dropdownAdmision"></div>
                        </div>
                        <span class="form-error" data-error-for="admision_id"></span>
                        <span class="form-error" data-error-for="paciente_id"></span>
                    </div>

                    {{-- Clínica destino --}}
                    <div class="form-group">
                        <label for="rem-clinica">Clínica destino <span class="req">*</span></label>
                        <select id="rem-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    {{-- Periodo --}}
                    <div class="form-group">
                        <label for="rem-periodo">Periodo <span class="req">*</span></label>
                        <select id="rem-periodo" name="periodo_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="periodo_id"></span>
                    </div>

                    {{-- Habilitado --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Estado</label>
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="rem-habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitada</span>
                        </label>
                    </div>
                </div>

                <div id="formRemisionError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formRemisionErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarRemision">
                    <span id="btnGuardarRemisionText">Guardar Remisión</span>
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
            store: @json(route('remisiones.store')),
            update: (id) => `/remisiones/${id}`,
            datosIniciales: @json(route('remisiones.datos-iniciales')),
            buscarAdmisiones: @json(route('remisiones.buscar-admisiones')),
        };

        let datosInicialesCargados = false;

        const $modal = document.getElementById('modalRemision');
        const $form = document.getElementById('formRemision');
        const $titulo = document.getElementById('modalRemisionTitulo');
        const $id = document.getElementById('formRemisionId');
        const $btnGuard = document.getElementById('btnGuardarRemision');
        const $btnText = document.getElementById('btnGuardarRemisionText');
        const $errorBox = document.getElementById('formRemisionError');
        const $errorTxt = document.getElementById('formRemisionErrorText');

        window.RemisionModal = {
            async abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nueva Remisión';
                $id.value = '';
                $btnText.textContent = 'Registrar Remisión';
                await cargarDatosIniciales();
                openModal();
            },

            async abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Remisión #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Remisión';

                await cargarDatosIniciales();

                const r = data.registro || data;

                $form.admision_id.value = r.admision_id ?? '';
                $form.paciente_id.value = r.paciente_id ?? '';
                $form.clinica_id.value = r.clinica_id ?? '';
                $form.periodo_id.value = r.periodo_id ?? '';
                $form.habilitado.checked = !!r.habilitado;

                if (r.paciente) {
                    document.getElementById('rem-admision-id').value = r.admision_id;
                    document.getElementById('rem-admision-buscar').value = r.paciente.nombre_completo;
                    document.getElementById('wrapAdmision').classList.add('has-value');
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

                $form.periodo_id.innerHTML = '<option value="">Seleccione...</option>' +
                    data.periodos.map((p) => `<option value="${p.id}">${p.texto}</option>`).join('');

                datosInicialesCargados = true;
            } catch (err) {
                console.error('[Remisiones] Error:', err);
            }
        }

        /* Autocomplete de admisión */
        (() => {
            const $wrap = document.getElementById('wrapAdmision');
            const $input = document.getElementById('rem-admision-buscar');
            const $hidden = document.getElementById('rem-admision-id');
            const $paciente = document.getElementById('rem-paciente-id');
            const $dropdown = document.getElementById('dropdownAdmision');

            if (!$input) return;

            let debounceTimer = null;
            let abortController = null;

            $input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const q = $input.value.trim();

                if (q === '') {
                    $hidden.value = '';
                    $paciente.value = '';
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
                $paciente.value = '';
                $wrap.classList.remove('has-value');
                $dropdown.classList.remove('open');
                $input.focus();
            });

            async function buscar(q) {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                $wrap.classList.add('loading');

                try {
                    const res = await fetch(`${URLS.buscarAdmisiones}?q=${encodeURIComponent(q)}`, {
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
                     data-paciente="${item.paciente_id || ''}"
                     data-texto="${item.texto}"
                     data-extra="${item.extra || ''}">
                    <div class="autocomplete-avatar">${item.iniciales || item.texto.substring(0, 2).toUpperCase()}</div>
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
                        $paciente.value = el.dataset.paciente;
                        $input.value = el.dataset.texto;
                        $wrap.classList.add('has-value');
                        $dropdown.classList.remove('open');
                    });
                });
            }
        })();

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
                window.RemisionesLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('remision:guardada'));
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
            document.getElementById('rem-admision-buscar').value = '';
            document.getElementById('rem-admision-id').value = '';
            document.getElementById('rem-paciente-id').value = '';
            document.getElementById('wrapAdmision').classList.remove('has-value');
            $form.habilitado.checked = true;
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
                ($id.value ? 'Actualizar Remisión' : 'Registrar Remisión');
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