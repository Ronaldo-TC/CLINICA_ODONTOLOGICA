<div class="modal-backdrop" id="modalEsterilizacion">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formEsterilizacion" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formEsterilizacionMethod" value="POST">
            <input type="hidden" name="esterilizacion_id" id="formEsterilizacionId" value="">

            <div class="modal-header">
                <h3 id="modalEsterilizacionTitulo">Nuevo Registro de Esterilización</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group"><label>Docente supervisor</label><select name="docente_id" required class="form-control"><option value="">Seleccione...</option>@foreach($docentes as $docente)<option value="{{ $docente->id }}">{{ $docente->nombre_completo }}</option>@endforeach</select><span class="form-error" data-error-for="docente_id"></span></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="est-clinica">Clínica <span class="req">*</span></label>
                        <select id="est-clinica" name="clinica_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="est-periodo">Periodo <span class="req">*</span></label>
                        <select id="est-periodo" name="periodo_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="periodo_id"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="est-estudiante-buscar">Estudiante <span class="req">*</span></label>
                        <div class="autocomplete-wrap" id="wrapEstudiante">
                            <input type="hidden" name="estudiante_id" id="est-estudiante-id">
                            <input type="text" id="est-estudiante-buscar"
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
                        <label for="est-cantidad-sobres">Cantidad de sobres <span class="req">*</span></label>
                        <input type="number" id="est-cantidad-sobres" name="cantidad_sobres"
                            class="form-control" required min="0" value="0">
                        <span class="form-error" data-error-for="cantidad_sobres"></span>
                    </div>

                    <div class="form-group">
                        <label for="est-cantidad-paquetes">Cantidad de paquetes <span class="req">*</span></label>
                        <input type="number" id="est-cantidad-paquetes" name="cantidad_paquetes"
                            class="form-control" required min="0" value="0">
                        <span class="form-error" data-error-for="cantidad_paquetes"></span>
                    </div>

                    <p style="grid-column: 1 / -1;">La firma docente, recepción, ciclo, liberación y recojo se registran desde la ficha de control. Las fechas y responsables se guardan automáticamente.</p>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="est-info">Información</label>
                        <input type="text" id="est-info" name="info"
                            class="form-control" maxlength="200"
                            placeholder="Info adicional...">
                        <span class="form-error" data-error-for="info"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="est-descripcion">Descripción</label>
                        <textarea id="est-descripcion" name="descripcion"
                            class="form-control" maxlength="200" rows="2"
                            placeholder="Descripción..."></textarea>
                        <span class="form-error" data-error-for="descripcion"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Estado</label>
                        <label class="switch-item">
                            <input type="checkbox" name="habilitada" id="est-habilitada" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Habilitada</span>
                        </label>
                    </div>
                </div>

                <div id="formEsterilizacionError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formEsterilizacionErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarEsterilizacion">
                    <span id="btnGuardarEsterilizacionText">Guardar Registro</span>
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
        const ESTUDIANTE_ACTUAL = @json($estudianteActual);

        const URLS = {
            store: @json(route('esterilizacion.store')),
            update: (id) => @json(route('esterilizacion.update', ['esterilizacion' => '__ID__'])).replace('__ID__', id),
            datosIniciales: @json(route('esterilizacion.datos-iniciales')),
            buscarUsuarios: @json(route('esterilizacion.buscar-usuarios')),
        };

        let datosInicialesCargados = false;

        const $modal = document.getElementById('modalEsterilizacion');
        const $form = document.getElementById('formEsterilizacion');
        const $titulo = document.getElementById('modalEsterilizacionTitulo');
        const $id = document.getElementById('formEsterilizacionId');
        const $btnGuard = document.getElementById('btnGuardarEsterilizacion');
        const $btnText = document.getElementById('btnGuardarEsterilizacionText');
        const $errorBox = document.getElementById('formEsterilizacionError');
        const $errorTxt = document.getElementById('formEsterilizacionErrorText');

        window.EsterilizacionModal = {
            async abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Registro de Esterilización';
                $id.value = '';
                $btnText.textContent = 'Registrar';
                await cargarDatosIniciales();
                openModal();
            },

            async abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Registro #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar';

                await cargarDatosIniciales();

                const e = data.registro || data;

                $form.clinica_id.value = e.clinica_id ?? '';
                $form.periodo_id.value = e.periodo_id ?? '';
                $form.docente_id.value = e.docente_id ?? '';
                $form.cantidad_sobres.value = e.cantidad_sobres ?? 0;
                $form.cantidad_paquetes.value = e.cantidad_paquetes ?? 0;
                $form.info.value = e.info ?? '';
                $form.descripcion.value = e.descripcion ?? '';
                $form.habilitada.checked = !!e.habilitada;

                if (e.estudiante) {
                    document.getElementById('est-estudiante-id').value = e.estudiante_id;
                    document.getElementById('est-estudiante-buscar').value = e.estudiante.nombre_completo;
                    document.getElementById('wrapEstudiante').classList.add('has-value');
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

                $form.clinica_id.replaceChildren(new Option('Seleccione...', ''),
                    ...data.clinicas.map((clinica) => new Option(clinica.texto, clinica.id)));
                $form.periodo_id.replaceChildren(new Option('Seleccione...', ''),
                    ...data.periodos.map((periodo) => new Option(periodo.texto, periodo.id)));

                datosInicialesCargados = true;
            } catch (err) {
                console.error('[Esterilizacion] Error:', err);
            }
        }

        /* Autocomplete estudiante */
        (() => {
            const $wrap = document.getElementById('wrapEstudiante');
            const $input = document.getElementById('est-estudiante-buscar');
            const $hidden = document.getElementById('est-estudiante-id');
            const $dropdown = document.getElementById('dropdownEstudiante');

            if (!$input) return;

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
                    const res = await fetch(`${URLS.buscarUsuarios}?q=${encodeURIComponent(q)}&rol=estudiante`, {
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

                $dropdown.replaceChildren();
                items.forEach((item) => {
                    const option = document.createElement('div');
                    option.className = 'autocomplete-item';
                    option.dataset.id = item.id;
                    option.dataset.texto = item.texto;
                    const avatar = document.createElement('div');
                    avatar.className = 'autocomplete-avatar';
                    avatar.textContent = item.texto.substring(0, 2).toUpperCase();
                    const info = document.createElement('div');
                    info.className = 'autocomplete-info';
                    const name = document.createElement('span');
                    name.className = 'autocomplete-name';
                    name.textContent = item.texto;
                    info.append(name);
                    if (item.extra) {
                        const meta = document.createElement('span');
                        meta.className = 'autocomplete-meta';
                        meta.textContent = item.extra;
                        info.append(meta);
                    }
                    option.append(avatar, info);
                    $dropdown.append(option);
                });

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
        })();

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
                window.EsterilizacionesLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('esterilizacion:guardada'));
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
            document.getElementById('est-estudiante-buscar').value = '';
            document.getElementById('est-estudiante-id').value = '';
            document.getElementById('wrapEstudiante').classList.remove('has-value');
            if (ESTUDIANTE_ACTUAL) {
                document.getElementById('est-estudiante-id').value = ESTUDIANTE_ACTUAL.id;
                document.getElementById('est-estudiante-buscar').value = ESTUDIANTE_ACTUAL.nombre_completo;
                document.getElementById('est-estudiante-buscar').readOnly = true;
                document.getElementById('wrapEstudiante').classList.add('has-value');
                document.querySelector('#wrapEstudiante .autocomplete-clear').hidden = true;
            }
            $form.habilitada.checked = true;
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
                ($id.value ? 'Actualizar' : 'Registrar');
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
