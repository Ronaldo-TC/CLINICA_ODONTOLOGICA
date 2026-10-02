<div class="modal-backdrop" id="modalHistoria">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formHistoria" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formHistoriaMethod" value="POST">
            <input type="hidden" name="historia_id" id="formHistoriaId" value="">

            <div class="modal-header">
                <h3 id="modalHistoriaTitulo">Nueva Historia Clínica</h3>
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
                        <label for="hist-admision-buscar">
                            Admisión / Paciente <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapAdmision">
                            <input type="hidden" name="admision_id" id="hist-admision-id">
                            <input type="hidden" name="paciente_id" id="hist-paciente-id">
                            <input type="text" id="hist-admision-buscar"
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

                    {{-- Clínica --}}
                    <div class="form-group">
                        <label for="hist-clinica">Clínica <span class="req">*</span></label>
                        <select id="hist-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    {{-- Periodo --}}
                    <div class="form-group">
                        <label for="hist-periodo">Periodo <span class="req">*</span></label>
                        <select id="hist-periodo" name="periodo_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="periodo_id"></span>
                    </div>

                    {{-- Estado --}}
                    <div class="form-group">
                        <label for="hist-estado">Estado</label>
                        <input type="text" id="hist-estado" name="estado"
                            class="form-control" maxlength="50"
                            placeholder="Ej: en_tratamiento" list="listaEstados">
                        <datalist id="listaEstados">
                            <option>pendiente</option>
                            <option>en_tratamiento</option>
                            <option>finalizado</option>
                            <option>archivado</option>
                        </datalist>
                        <span class="form-error" data-error-for="estado"></span>
                    </div>

                    {{-- Archivo nombre --}}
                    <div class="form-group">
                        <label for="hist-archivo-nombre">Nombre de archivo</label>
                        <input type="text" id="hist-archivo-nombre" name="archivo_nombre"
                            class="form-control" maxlength="100"
                            placeholder="Ej: HC-2026-001">
                        <span class="form-error" data-error-for="archivo_nombre"></span>
                    </div>

                    {{-- Fecha inicio --}}
                    <div class="form-group">
                        <label for="hist-fecha-inicio">Fecha de inicio</label>
                        <input type="date" id="hist-fecha-inicio" name="fecha_inicio"
                            class="form-control">
                        <span class="form-error" data-error-for="fecha_inicio"></span>
                    </div>

                    {{-- Fecha fin --}}
                    <div class="form-group">
                        <label for="hist-fecha-fin">Fecha de fin</label>
                        <input type="date" id="hist-fecha-fin" name="fecha_fin"
                            class="form-control">
                        <span class="form-error" data-error-for="fecha_fin"></span>
                    </div>
                </div>

                {{-- Estudiante --}}
                <div class="form-grid" style="margin-top: 18px;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="hist-estudiante-buscar">
                            Estudiante <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapEstudiante">
                            <input type="hidden" name="estudiante_id" id="hist-estudiante-id">
                            <input type="text" id="hist-estudiante-buscar"
                                class="form-control autocomplete-input"
                                placeholder="Escribe nombre o CI del estudiante..."
                                autocomplete="off">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" id="dropdownEstudiante"></div>
                        </div>
                        <span class="form-error" data-error-for="estudiante_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="hist-especialidad-est">Clínica del estudiante <span class="req">*</span></label>
                        <select id="hist-especialidad-est" name="especialidad_estudiante"
                            class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="especialidad_estudiante"></span>
                    </div>

                    {{-- Docente --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="hist-docente-buscar">
                            Docente <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapDocente">
                            <input type="hidden" name="docente_id" id="hist-docente-id">
                            <input type="text" id="hist-docente-buscar"
                                class="form-control autocomplete-input"
                                placeholder="Escribe nombre o CI del docente..."
                                autocomplete="off">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" id="dropdownDocente"></div>
                        </div>
                        <span class="form-error" data-error-for="docente_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="hist-especialidad-doc">Clínica del docente <span class="req">*</span></label>
                        <select id="hist-especialidad-doc" name="especialidad_docente"
                            class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="especialidad_docente"></span>
                    </div>
                </div>

                {{-- Autorizado --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label>Estado de autorización</label>
                    <div class="checkbox-grid">
                        <label class="switch-item">
                            <input type="checkbox" name="autorizado" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Autorizado</span>
                        </label>
                    </div>
                </div>

                <div id="formHistoriaError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formHistoriaErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarHistoria">
                    <span id="btnGuardarHistoriaText">Guardar Historia</span>
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
            store: @json(route('historias.store')),
            update: (id) => `/historias/${id}`,
            datosIniciales: @json(route('historias.datos-iniciales')),
            buscarAdmisiones: @json(route('historias.buscar-admisiones')),
            buscarUsuarios: @json(route('historias.buscar-usuarios')),
        };

        let datosInicialesCargados = false;

        const $modal = document.getElementById('modalHistoria');
        const $form = document.getElementById('formHistoria');
        const $titulo = document.getElementById('modalHistoriaTitulo');
        const $id = document.getElementById('formHistoriaId');
        const $btnGuard = document.getElementById('btnGuardarHistoria');
        const $btnText = document.getElementById('btnGuardarHistoriaText');
        const $errorBox = document.getElementById('formHistoriaError');
        const $errorTxt = document.getElementById('formHistoriaErrorText');

        window.HistoriaModal = {
            async abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nueva Historia Clínica';
                $id.value = '';
                $btnText.textContent = 'Registrar Historia';
                await cargarDatosIniciales();
                openModal();
            },

            async abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Historia #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Historia';

                await cargarDatosIniciales();

                const h = data.registro || data;

                $form.admision_id.value = h.admision_id ?? '';
                $form.paciente_id.value = h.paciente_id ?? '';
                $form.estudiante_id.value = h.estudiante_id ?? '';
                $form.docente_id.value = h.docente_id ?? '';

                // Preseleccionar autocompletes
                if (h.paciente) {
                    setAuto('Admision',
                        `Admisión #${h.admision_id}`,
                        h.paciente.nombre_completo,
                        '');
                    $form.paciente_id.value = h.paciente.id;
                }
                if (h.estudiante) {
                    setAuto('Estudiante', h.estudiante.nombre_completo, '', '');
                }
                if (h.docente) {
                    setAuto('Docente', h.docente.nombre_completo, '', '');
                }

                // Selects
                $form.clinica_id.value = h.clinica_id ?? '';
                $form.periodo_id.value = h.periodo_id ?? '';
                $form.especialidad_estudiante.value = h.especialidad_estudiante ?? '';
                $form.especialidad_docente.value = h.especialidad_docente ?? '';

                // Otros
                ['estado', 'archivo_nombre', 'fecha_inicio', 'fecha_fin'].forEach((f) => {
                    if ($form[f] && h[f] !== undefined && h[f] !== null) {
                        $form[f].value = typeof h[f] === 'string' && h[f].includes('T') ?
                            h[f].substring(0, 10) :
                            h[f];
                    }
                });

                $form.autorizado.checked = !!h.autorizado;

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        function setAuto(campo, hiddenVal, textoVal, extraVal) {
            const $hidden = document.getElementById(`hist-${campo.toLowerCase()}-id`);
            const $input = document.getElementById(`hist-${campo.toLowerCase()}-buscar`);
            const $wrap = document.getElementById(`wrap${campo}`);

            if ($hidden) $hidden.value = hiddenVal;
            if ($input) $input.value = textoVal;
            if ($wrap) $wrap.classList.add('has-value');
        }

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

                if (!res.ok) throw new Error('Error al cargar datos iniciales');
                const data = await res.json();

                const clinicasOpts = data.clinicas.map((c) =>
                    `<option value="${c.id}">${c.texto}</option>`
                ).join('');

                $form.clinica_id.innerHTML = '<option value="">Seleccione...</option>' + clinicasOpts;
                $form.especialidad_estudiante.innerHTML = '<option value="">Seleccione...</option>' + clinicasOpts;
                $form.especialidad_docente.innerHTML = '<option value="">Seleccione...</option>' + clinicasOpts;

                $form.periodo_id.innerHTML = '<option value="">Seleccione...</option>' +
                    data.periodos.map((p) =>
                        `<option value="${p.id}">${p.texto}</option>`
                    ).join('');

                datosInicialesCargados = true;
            } catch (err) {
                console.error('[Historias] Error:', err);
            }
        }

        function setupAutocomplete(campo, urlBuilder) {
            const $wrap = document.getElementById(`wrap${campo}`);
            const $input = document.getElementById(`hist-${campo.toLowerCase()}-buscar`);
            const $hidden = document.getElementById(`hist-${campo.toLowerCase()}-id`);
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
                    $dropdown.innerHTML = `
                    <div class="autocomplete-empty">
                        <p>No se encontraron resultados</p>
                    </div>`;
                    $dropdown.classList.add('open');
                    return;
                }

                $dropdown.innerHTML = items.map((item) => `
                <div class="autocomplete-item"
                     data-id="${item.id}"
                     data-paciente="${item.paciente_id || ''}"
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

                        // Si es admisión, también setear paciente_id
                        if (campo === 'Admision' && el.dataset.paciente) {
                            $form.paciente_id.value = el.dataset.paciente;
                        }
                    });
                });
            }
        }

        setupAutocomplete('Admision', (q) => `${URLS.buscarAdmisiones}?q=${encodeURIComponent(q)}`);
        setupAutocomplete('Estudiante', (q) => `${URLS.buscarUsuarios}?q=${encodeURIComponent(q)}&rol=estudiante`);
        setupAutocomplete('Docente', (q) => `${URLS.buscarUsuarios}?q=${encodeURIComponent(q)}&rol=docente`);

        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? URLS.update(id) : URLS.store;

            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            formData.set('autorizado', $form.autorizado.checked ? '1' : '0');

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
                window.HistoriasLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('historia:guardada'));
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

            ['Admision', 'Estudiante', 'Docente'].forEach((campo) => {
                const $input = document.getElementById(`hist-${campo.toLowerCase()}-buscar`);
                const $hidden = document.getElementById(`hist-${campo.toLowerCase()}-id`);
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
                ($id.value ? 'Actualizar Historia' : 'Registrar Historia');
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