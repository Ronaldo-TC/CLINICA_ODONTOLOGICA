<div class="modal-backdrop" id="modalAdmision">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formAdmision" novalidate>
            @csrf
            <input type="hidden" name="_method" id="formAdmisionMethod" value="POST">
            <input type="hidden" name="admision_id" id="formAdmisionId" value="">

            <div class="modal-header">
                <h3 id="modalAdmisionTitulo">Nueva Admisión</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">

                {{-- ============ IDENTIFICACIÓN ============ --}}
                <div class="form-grid">

                    {{-- Paciente con autocomplete --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="adm-paciente-buscar">
                            Paciente <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapPaciente">
                            <input type="hidden" name="paciente_id" id="adm-paciente-id">
                            <input type="text"
                                id="adm-paciente-buscar"
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

                    {{-- Motivo --}}
                    <div class="form-group">
                        <label for="adm-motivo">Motivo de consulta</label>
                        <select id="adm-motivo" name="motivo_consulta_id" class="form-control">
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="motivo_consulta_id"></span>
                    </div>

                    {{-- Teléfono --}}
                    <div class="form-group">
                        <label for="adm-telefono">Teléfono</label>
                        <input type="number" id="adm-telefono" name="telefono"
                            class="form-control" placeholder="Ej: 71234567">
                        <span class="form-error" data-error-for="telefono"></span>
                    </div>

                    {{-- Estado civil --}}
                    <div class="form-group">
                        <label for="adm-estado-civil">Estado civil</label>
                        <input type="text" id="adm-estado-civil" name="estado_civil"
                            class="form-control" maxlength="200"
                            placeholder="Ej: Soltero" list="listaEstadoCivil">
                        <datalist id="listaEstadoCivil">
                            <option>Soltero</option>
                            <option>Casado</option>
                            <option>Divorciado</option>
                            <option>Viudo</option>
                            <option>Concubino</option>
                        </datalist>
                        <span class="form-error" data-error-for="estado_civil"></span>
                    </div>

                    {{-- Ocupación --}}
                    <div class="form-group">
                        <label for="adm-ocupacion">Ocupación</label>
                        <input type="text" id="adm-ocupacion" name="ocupacion"
                            class="form-control" maxlength="200"
                            placeholder="Ej: Estudiante" list="listaOcupacion">
                        <datalist id="listaOcupacion">
                            <option>Estudiante</option>
                            <option>Docente</option>
                            <option>Comerciante</option>
                            <option>Empleado</option>
                            <option>Independiente</option>
                            <option>Ama de casa</option>
                        </datalist>
                        <span class="form-error" data-error-for="ocupacion"></span>
                    </div>

                    {{-- Dirección --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="adm-direccion">Dirección</label>
                        <input type="text" id="adm-direccion" name="direccion"
                            class="form-control" maxlength="200"
                            placeholder="Dirección completa">
                        <span class="form-error" data-error-for="direccion"></span>
                    </div>

                    {{-- Emergencia --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="adm-emergencia">Contacto de emergencia</label>
                        <input type="text" id="adm-emergencia" name="emergencia"
                            class="form-control" maxlength="200"
                            placeholder="Nombre y teléfono del contacto">
                        <span class="form-error" data-error-for="emergencia"></span>
                    </div>
                </div>

                {{-- ============ ASIGNACIÓN ACADÉMICA ============ --}}
                <div class="form-grid" style="margin-top: 18px;">

                    {{-- Estudiante con autocomplete --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="adm-estudiante-buscar">
                            Estudiante <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapEstudiante">
                            <input type="hidden" name="estudiante_id" id="adm-estudiante-id">
                            <input type="text"
                                id="adm-estudiante-buscar"
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

                    {{-- Clínica del estudiante --}}
                    <div class="form-group">
                        <label for="adm-especialidad-est">Clínica del estudiante <span class="req">*</span></label>
                        <select id="adm-especialidad-est" name="especialidad_estudiante"
                            class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="especialidad_estudiante"></span>
                    </div>

                    {{-- Responsable con autocomplete --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="adm-responsable-buscar">
                            Responsable <span class="req">*</span>
                        </label>
                        <div class="autocomplete-wrap" id="wrapResponsable">
                            <input type="hidden" name="responsable_id" id="adm-responsable-id">
                            <input type="text"
                                id="adm-responsable-buscar"
                                class="form-control autocomplete-input"
                                placeholder="Escribe nombre o CI del responsable..."
                                autocomplete="off">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" id="dropdownResponsable"></div>
                        </div>
                        <span class="form-error" data-error-for="responsable_id"></span>
                    </div>

                    {{-- Clínica del responsable --}}
                    <div class="form-group">
                        <label for="adm-especialidad-resp">Clínica del responsable <span class="req">*</span></label>
                        <select id="adm-especialidad-resp" name="especialidad_responsable"
                            class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <span class="form-error" data-error-for="especialidad_responsable"></span>
                    </div>
                </div>

                {{-- ============ ANTECEDENTES ============ --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label for="adm-antecedentes">Antecedentes</label>
                    <textarea id="adm-antecedentes" name="antecedentes"
                        class="form-control" rows="3"
                        placeholder="Antecedentes médicos, familiares, alergias..."></textarea>
                </div>

                <div class="form-group" style="margin-top: 12px;">
                    <label for="adm-motivo-consulta">Motivo de consulta (texto libre)</label>
                    <textarea id="adm-motivo-consulta" name="motivo_consulta"
                        class="form-control" rows="3"
                        placeholder="Descripción detallada..."></textarea>
                </div>

                {{-- ============ ODONTOGRAMA ============ --}}
                <div class="form-grid" style="margin-top:18px;">
                    <div class="form-group"><label>Fecha de admisión</label><input type="date" name="fecha_admision" class="form-control" value="{{ today()->toDateString() }}"></div>
                    <div class="form-group"><label>Teléfono de emergencia</label><input type="tel" name="telefono_emergencia" class="form-control" maxlength="30"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Historia de la enfermedad actual</label><textarea name="historia_enfermedad_actual" class="form-control" rows="3"></textarea></div>
                    <div class="form-group"><label>Estado físico general</label><select name="gpe" class="form-control"><option value="">Sin registrar</option><option value="bueno">Bueno</option><option value="regular">Regular</option><option value="malo">Malo</option></select></div>
                    @foreach (['peso_kg' => ['Peso (kg)', '0.01'], 'temperatura_c' => ['Temperatura (°C)', '0.1'], 'pulso' => ['Pulso por minuto', '1'], 'frecuencia_respiratoria' => ['Frecuencia respiratoria por minuto', '1']] as $campo => $configuracion)
                        <div class="form-group"><label>{{ $configuracion[0] }}</label><input type="number" name="{{ $campo }}" step="{{ $configuracion[1] }}" min="0" class="form-control"><span class="form-error" data-error-for="{{ $campo }}"></span></div>
                    @endforeach
                    <div class="form-group"><label>Presión arterial (mmHg)</label><input name="presion_arterial" class="form-control" maxlength="20" placeholder="Ej. 120/80"></div>
                    @foreach (['gmh' => 'Historia médica general', 'ee' => 'Examen extraoral: asimetría, color y abultamiento', 'cn' => 'Ganglios cervicales: palpación y observaciones', 'atm' => 'ATM: ruidos articulares y observaciones', 'bm' => 'Mucosa bucal: alteraciones y observaciones', 'gm' => 'Mucosa gingival: alteraciones y observaciones', 'calculo' => 'Cálculos', 'oclusion' => 'Oclusión', 'pieza_dental' => 'Piezas dentales', 'diagnostico' => 'Diagnóstico presuntivo', 'examen' => 'Examen complementario requerido'] as $campo => $etiqueta)
                        <div class="form-group"><label>{{ $etiqueta }}</label><textarea name="{{ $campo }}" class="form-control" rows="2"></textarea><span class="form-error" data-error-for="{{ $campo }}"></span></div>
                    @endforeach
                </div>
                <div class="form-group" style="margin-top: 18px;">
                    <label>Odontograma</label>
                    <x-odontograma nombre="odontograma_pintado" id="adm-odontograma" />
                </div>

                {{-- ============ FIRMA DEL PACIENTE ============ --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label>Firma del paciente</label>
                    <x-firma-digital nombre="firma" id="adm-firma" />
                </div>

                {{-- ============ CONSENTIMIENTOS ============ --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label>Consentimientos</label>
                    <div class="checkbox-grid">
                        <label class="switch-item">
                            <input type="checkbox" name="consentimiento_datos" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Consiente tratamiento de datos</span>
                        </label>
                        <label class="switch-item">
                            <input type="checkbox" name="consentimiento_fotografias" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Autoriza uso de fotografías con fines didácticos</span>
                        </label>
                        <label class="switch-item">
                            <input type="checkbox" name="consentimiento_investigacion" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Autoriza investigación</span>
                        </label>
                    </div>
                </div>

                {{-- Error --}}
                <div id="formAdmisionError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formAdmisionErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarAdmision">
                    <span id="btnGuardarAdmisionText">Guardar Admisión</span>
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
            store: @json(route('admisiones.store')),
            update: (id) => `/admisiones/${id}`,
            datosIniciales: @json(route('admisiones.datos-iniciales')),
            buscarPacientes: @json(route('admisiones.buscar-pacientes')),
            buscarUsuarios: @json(route('admisiones.buscar-usuarios')),
        };

        /* ============================================================
           ESTADO
           ============================================================ */
        let datosInicialesCargados = false;

        const $modal = document.getElementById('modalAdmision');
        const $form = document.getElementById('formAdmision');
        const $titulo = document.getElementById('modalAdmisionTitulo');
        const $id = document.getElementById('formAdmisionId');
        const $btnGuard = document.getElementById('btnGuardarAdmision');
        const $btnText = document.getElementById('btnGuardarAdmisionText');
        const $errorBox = document.getElementById('formAdmisionError');
        const $errorTxt = document.getElementById('formAdmisionErrorText');

        function mostrarDatosContacto(visible) {
            @json(\App\Models\Paciente::CAMPOS_CONTACTO).forEach((campo) => {
                const input = $form.elements.namedItem(campo);
                if (input) {
                    input.closest('.form-group').style.display = visible ? '' : 'none';
                    input.disabled = !visible;
                }
            });
        }

        mostrarDatosContacto(false);

        /* ============================================================
           API PÚBLICA
           ============================================================ */
        window.AdmisionModal = {
            async abrirCrear() {
                resetForm();
                mostrarDatosContacto(false);
                $titulo.textContent = 'Nueva Admisión';
                $id.value = '';
                $btnText.textContent = 'Registrar Admisión';

                // ⚡ Cargar datos iniciales SIEMPRE (la primera vez)
                await cargarDatosIniciales();

                openModal();
            },

            async abrirEdicion(data) {
                resetForm();
                mostrarDatosContacto(true);
                $titulo.textContent = 'Editar Admisión #' + data.id;
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Admisión';

                await cargarDatosIniciales();

                const a = data.registro || data;

                // Asignar valores
                $form.paciente_id.value = a.paciente_id ?? '';
                $form.estudiante_id.value = a.estudiante_id ?? '';
                $form.responsable_id.value = a.responsable_id ?? '';
                $form.motivo_consulta_id.value = a.motivo_consulta_id ?? '';
                $form.especialidad_estudiante.value = a.especialidad_estudiante ?? '';
                $form.especialidad_responsable.value = a.especialidad_responsable ?? '';

                // Preseleccionar textos del autocomplete
                if (a.paciente) {
                    setAutocompleteSeleccion(
                        'Paciente',
                        a.paciente.id,
                        `${a.paciente.nombre} ${a.paciente.apellido_paterno || ''} ${a.paciente.apellido_materno || ''}`.trim(),
                        a.paciente.ci ? `CI: ${a.paciente.ci}` : ''
                    );
                }
                if (a.estudiante) {
                    setAutocompleteSeleccion('Estudiante', a.estudiante.id, a.estudiante.nombre_completo, '');
                }
                if (a.responsable) {
                    setAutocompleteSeleccion('Responsable', a.responsable.id, a.responsable.nombre_completo, '');
                }

                // Resto de campos
                ['telefono', 'estado_civil', 'ocupacion', 'direccion', 'emergencia',
                    'antecedentes', 'motivo_consulta', 'fecha_admision', 'telefono_emergencia', 'historia_enfermedad_actual',
                    'peso_kg', 'temperatura_c', 'pulso', 'presion_arterial', 'frecuencia_respiratoria', 'gmh', 'gpe', 'ee', 'cn', 'atm', 'bm', 'gm', 'calculo', 'oclusion', 'pieza_dental', 'diagnostico', 'examen'
                ].forEach((f) => {
                    if ($form[f] && a[f] !== undefined && a[f] !== null) {
                        $form[f].value = a[f];
                    }
                });

                $form.consentimiento_datos.checked = !!a.consentimiento_datos;
                $form.consentimiento_fotografias.checked = !!a.consentimiento_fotografias;
                $form.consentimiento_investigacion.checked = !!a.consentimiento_investigacion;

                // Odontograma: se restaura lo pintado y se recuerda el valor previo
                // (el controlador lo usa para registrar el cambio en bitácoras).
                const odontogramaPrev = a.odontograma_pintado || '{}';
                window.Odontograma?.set('#adm-odontograma', odontogramaPrev, a.odontograma_denticion || null);
                let campoAnterior = $form.querySelector('[name="odontograma_pintado_anterior"]');
                if (!campoAnterior) {
                    campoAnterior = document.createElement('input');
                    campoAnterior.type = 'hidden';
                    campoAnterior.name = 'odontograma_pintado_anterior';
                    $form.appendChild(campoAnterior);
                }
                campoAnterior.value = odontogramaPrev;

                // Firma del paciente
                window.FirmaDigital?.set('#adm-firma', a.firma || '');

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        /* ============================================================
           CARGA DE DATOS INICIALES (motivos y clinicas)
           ============================================================ */
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

                // Motivos
                $form.motivo_consulta_id.innerHTML = '<option value="">Sin motivo</option>' +
                    data.motivos.map((m) =>
                        `<option value="${m.id}">${m.texto}</option>`
                    ).join('');

                // Clínicas
                const clinicasOpts = data.clinicas.map((c) =>
                    `<option value="${c.id}">${c.texto}</option>`
                ).join('');

                $form.especialidad_estudiante.innerHTML = '<option value="">Seleccione...</option>' + clinicasOpts;
                $form.especialidad_responsable.innerHTML = '<option value="">Seleccione...</option>' + clinicasOpts;

                datosInicialesCargados = true;
            } catch (err) {
                console.error('[Admisiones] Error cargando datos iniciales:', err);
            }
        }

        /* ============================================================
           AUTOCOMPLETE GENÉRICO
           ============================================================ */
        function setupAutocomplete(campo, urlBuilder) {
            const $wrap = document.getElementById(`wrap${campo}`);
            const $input = document.getElementById(`adm-${campo.toLowerCase()}-buscar`);
            const $hidden = document.getElementById(`adm-${campo.toLowerCase()}-id`);
            const $dropdown = document.getElementById(`dropdown${campo}`);
            const $clear = $wrap.querySelector('.autocomplete-clear');

            if (!$input || !$dropdown) return;

            let debounceTimer = null;
            let abortController = null;
            let lastQuery = '';

            // Búsqueda al escribir
            $input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const q = $input.value.trim();

                if (q === '') {
                    $hidden.value = '';
                    $wrap.classList.remove('has-value');
                    $dropdown.classList.remove('open');
                    return;
                }

                if (q === lastQuery) return;
                lastQuery = q;

                debounceTimer = setTimeout(() => buscar(q), 300);
            });

            // Focus: si hay valor, mostrar dropdown
            $input.addEventListener('focus', () => {
                if ($input.value.trim().length >= 2) {
                    buscar($input.value.trim());
                }
            });

            // Cerrar al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!$wrap.contains(e.target)) {
                    $dropdown.classList.remove('open');
                }
            });

            // Botón limpiar
            $clear?.addEventListener('click', () => {
                $input.value = '';
                $hidden.value = '';
                $wrap.classList.remove('has-value');
                $dropdown.classList.remove('open');
                $input.focus();
            });

            async function buscar(q) {
                // Cancelar búsqueda anterior
                if (abortController) abortController.abort();
                abortController = new AbortController();

                $wrap.classList.add('loading');

                try {
                    const url = urlBuilder(q);
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        signal: abortController.signal,
                    });

                    if (!res.ok) throw new Error('Error al buscar');

                    const items = await res.json();
                    renderDropdown(items);
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error(`[Autocomplete ${campo}] Error:`, err);
                    }
                } finally {
                    $wrap.classList.remove('loading');
                }
            }

            function renderDropdown(items) {
                if (!items || items.length === 0) {
                    $dropdown.innerHTML = `
                    <div class="autocomplete-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <p>No se encontraron resultados</p>
                    </div>`;
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

                // Click en item
                $dropdown.querySelectorAll('.autocomplete-item').forEach((el) => {
                    el.addEventListener('click', () => {
                        const id = el.dataset.id;
                        const texto = el.dataset.texto;
                        const extra = el.dataset.extra;

                        $hidden.value = id;
                        $input.value = texto;
                        $wrap.classList.add('has-value');
                        $dropdown.classList.remove('open');
                        lastQuery = texto;
                    });
                });
            }
        }

        function setAutocompleteSeleccion(campo, id, texto, extra) {
            const $input = document.getElementById(`adm-${campo.toLowerCase()}-buscar`);
            const $hidden = document.getElementById(`adm-${campo.toLowerCase()}-id`);
            const $wrap = document.getElementById(`wrap${campo}`);

            if ($input) $input.value = texto;
            if ($hidden) $hidden.value = id;
            if ($wrap) $wrap.classList.add('has-value');
        }

        /* ============================================================
           INICIALIZAR AUTOCOMPLETES
           ============================================================ */
        setupAutocomplete('Paciente', (q) => `${URLS.buscarPacientes}?q=${encodeURIComponent(q)}`);
        setupAutocomplete('Estudiante', (q) => `${URLS.buscarUsuarios}?q=${encodeURIComponent(q)}&rol=estudiante`);
        setupAutocomplete('Responsable', (q) => `${URLS.buscarUsuarios}?q=${encodeURIComponent(q)}&rol=responsable`);

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

            formData.set('consentimiento_datos', $form.consentimiento_datos.checked ? '1' : '0');
            formData.set('consentimiento_investigacion', $form.consentimiento_investigacion.checked ? '1' : '0');

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
                window.AdmisionesLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('admision:guardada'));
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
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';

            // Limpiar autocompletes
            ['Paciente', 'Estudiante', 'Responsable'].forEach((campo) => {
                const $input = document.getElementById(`adm-${campo.toLowerCase()}-buscar`);
                const $hidden = document.getElementById(`adm-${campo.toLowerCase()}-id`);
                const $wrap = document.getElementById(`wrap${campo}`);
                if ($input) $input.value = '';
                if ($hidden) $hidden.value = '';
                if ($wrap) $wrap.classList.remove('has-value');
            });

            // Los catálogos se recargan al abrir el formulario.
            datosInicialesCargados = false;

            // Odontograma en blanco
            window.Odontograma?.limpiar('#adm-odontograma');
            const campoAnterior = $form.querySelector('[name="odontograma_pintado_anterior"]');
            if (campoAnterior) campoAnterior.value = '';

            // Firma en blanco
            window.FirmaDigital?.limpiar('#adm-firma');

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
                ($id.value ? 'Actualizar Admisión' : 'Registrar Admisión');
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
