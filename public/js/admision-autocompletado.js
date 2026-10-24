export function normalizarValor(valor) {
    if (valor === null || valor === undefined) {
        return '';
    }
    if (typeof valor === 'boolean') {
        return valor ? '1' : '0';
    }
    return typeof valor === 'object' ? valor : String(valor);
}

export function crearAutocompletadoAdmision({
    modo = 'crear',
    pacienteInicialId = '',
    preservarInicial = false,
    campos = [],
    obtenerPaciente,
    escribirCampo,
    limpiarFicha,
    alEstado = () => {},
    alPaciente = () => {},
}) {
    let pacienteId = String(pacienteInicialId || '');
    const idInicial = pacienteId;
    const identidadFija = modo === 'editar' && idInicial !== '';
    let conservarInicial = preservarInicial;
    let estadoActual = 'sin-paciente';
    let errorActual = null;
    let controlador = null;
    let version = 0;
    let cargaActual = null;
    const editados = new Set();

    const estado = () => ({ estado: estadoActual, pacienteId, error: errorActual });
    const notificar = (nuevoEstado, error = null) => {
        estadoActual = nuevoEstado;
        errorActual = error;
        alEstado(estado());
    };
    const cancelar = () => {
        version += 1;
        controlador?.abort();
        controlador = null;
        cargaActual = null;
    };
    const limpiar = () => {
        if (identidadFija) {
            return;
        }
        const teniaPaciente = pacienteId !== '';
        cancelar();
        pacienteId = '';
        conservarInicial = false;
        if (teniaPaciente) {
            limpiarFicha();
            editados.clear();
        }
        alPaciente(null);
        notificar('sin-paciente');
    };

    const seleccionar = (id) => {
        const nuevoId = String(id || '');
        if (identidadFija && nuevoId !== idInicial) {
            return Promise.resolve(null);
        }
        if (!nuevoId) {
            limpiar();
            return Promise.resolve(null);
        }
        if (nuevoId === pacienteId && estadoActual === 'listo') {
            return Promise.resolve(null);
        }
        if (nuevoId === pacienteId && estadoActual === 'cargando') {
            return cargaActual;
        }
        if (pacienteId && nuevoId !== pacienteId) {
            limpiar();
        }
        cancelar();
        pacienteId = nuevoId;
        const versionCarga = version;
        const peticion = new AbortController();
        controlador = peticion;
        notificar('cargando');

        cargaActual = (async () => {
            try {
                const paciente = await obtenerPaciente(nuevoId, { signal: peticion.signal });
                if (versionCarga !== version || peticion.signal.aborted || pacienteId !== nuevoId) {
                    return null;
                }
                if (!paciente || String(paciente.id) !== nuevoId) {
                    throw new Error('Los datos recibidos no corresponden al paciente seleccionado.');
                }
                if (modo === 'crear' && ! (conservarInicial && nuevoId === idInicial)) {
                    const anteriores = paciente.admision_anterior?.datos || {};
                    campos.forEach((campo) => {
                        if (editados.has(campo)) {
                            return;
                        }
                        const origen = Object.hasOwn(anteriores, campo) ? anteriores : paciente;
                        if (Object.hasOwn(origen, campo)) {
                            escribirCampo(campo, normalizarValor(origen[campo]), paciente);
                        }
                    });
                }
                alPaciente(paciente);
                notificar('listo');
                return paciente;
            } catch (error) {
                if (versionCarga !== version || peticion.signal.aborted || pacienteId !== nuevoId) {
                    return null;
                }
                notificar('error', error);
                return null;
            } finally {
                if (controlador === peticion) {
                    controlador = null;
                    cargaActual = null;
                }
            }
        })();
        return cargaActual;
    };

    notificar('sin-paciente');

    return {
        seleccionar,
        limpiar,
        reintentar: () => seleccionar(pacienteId),
        marcarEditado: (campo) => editados.add(campo),
        puedeGuardar: () => pacienteId !== '' && estadoActual === 'listo',
        estado,
    };
}

export function inicializarFormularioAdmision({
    form,
    config,
    odontograma,
    firma,
    solicitar = (...args) => fetch(...args),
}) {
    const documento = form.ownerDocument;
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const input = documento.getElementById('adm-paciente-buscar');
    const hidden = documento.getElementById('adm-paciente-id');
    const wrap = documento.getElementById('wrapPaciente');
    const dropdown = documento.getElementById('dropdownPaciente');
    const resumen = documento.getElementById('datosPacienteResumen');
    const resumenNombre = documento.getElementById('datosPacienteNombre');
    const resumenMeta = documento.getElementById('datosPacienteMeta');
    const resumenOrigen = documento.getElementById('datosPacienteOrigen');
    const fichaAnterior = documento.getElementById('datosPacienteFicha');
    const reporteAnterior = documento.getElementById('datosPacienteReporte');
    const historiaClinica = documento.getElementById('datosPacienteHistoria');
    const cargaEstado = documento.getElementById('cargaPacienteEstado');
    const cargaMensaje = documento.getElementById('cargaPacienteMensaje');
    const reintentar = documento.getElementById('btnReintentarPaciente');
    const botonGuardar = documento.getElementById('btnGuardarAdmision');
    const errorFormulario = documento.getElementById('formAdmisionError');
    const errorTexto = documento.getElementById('formAdmisionErrorText');
    let timer = null;
    let peticionBusqueda = null;
    let guardando = false;
    let conservarFormularioInicial = config.preservarInicial;
    const botonLimpiar = wrap.querySelector('.autocomplete-clear');
    if (config.modo === 'editar') {
        input.readOnly = true;
        botonLimpiar.disabled = true;
        botonLimpiar.hidden = true;
        botonLimpiar.style.display = 'none';
    }

    const urlConId = (plantilla, id) => plantilla.replace('__ID__', encodeURIComponent(id));
    const error = (mensaje) => {
        errorTexto.textContent = mensaje;
        errorFormulario.style.display = 'flex';
    };
    const renderResumen = (paciente) => {
        if (!paciente) {
            resumen.style.display = 'none';
            resumenNombre.textContent = '';
            resumenMeta.textContent = '';
            resumenOrigen.textContent = '';
            [fichaAnterior, reporteAnterior, historiaClinica].forEach((enlace) => {
                if (enlace) enlace.style.display = 'none';
            });
            return;
        }
        input.value = paciente.nombre_completo || 'Paciente registrado';
        const meta = [];
        if (paciente.ci) meta.push('CI: ' + paciente.ci);
        if (paciente.genero) meta.push('Género: ' + paciente.genero);
        if (paciente.fecha_nacimiento) meta.push('Nacimiento: ' + paciente.fecha_nacimiento);
        if (paciente.edad !== null && paciente.edad !== undefined) meta.push('Edad: ' + paciente.edad + ' años');
        resumenNombre.textContent = input.value;
        resumenMeta.textContent = meta.join(' · ');
        const anterior = paciente.admision_anterior;
        if (anterior) {
            const fecha = String(anterior.fecha_admision || '').slice(0, 10).replace(/^(\d{4})-(\d{2})-(\d{2})$/, '$3/$2/$1');
            const origen = 'ficha #' + anterior.id + (fecha ? ' del ' + fecha : '');
            resumenOrigen.textContent = config.modo === 'crear' && !conservarFormularioInicial
                ? 'Datos precargados de la última ficha disponible: ' + origen + '. Revise y actualice los antecedentes y la evaluación antes de registrar esta nueva visita.'
                : 'Última ficha disponible: ' + origen + '. Se conservan los datos del formulario actual.';
            [[fichaAnterior, config.urls.ficha], [reporteAnterior, config.urls.reporte]].forEach(([enlace, plantilla]) => {
                if (enlace && plantilla) {
                    enlace.href = urlConId(plantilla, anterior.id);
                    enlace.style.display = 'inline-flex';
                }
            });
        } else {
            resumenOrigen.textContent = 'El paciente no tiene una ficha anterior disponible. Complete los datos y la evaluación para esta visita.';
        }
        if (historiaClinica && paciente.historia_clinica && config.urls.historia) {
            historiaClinica.href = urlConId(config.urls.historia, paciente.historia_clinica.id);
            historiaClinica.style.display = 'inline-flex';
        } else if (historiaClinica) {
            historiaClinica.style.display = 'none';
        }
        resumen.style.display = 'block';
    };
    const escribirCampo = (campo, valor, paciente) => {
        if (campo === 'odontograma_pintado') {
            odontograma?.set('#adm-odontograma', valor || {}, paciente.admision_anterior?.datos?.odontograma_denticion || 'adulto');
            return;
        }
        if (campo === 'odontograma_denticion') {
            odontograma?.set('#adm-odontograma', odontograma.get('#adm-odontograma'), valor || 'adulto');
            return;
        }
        const control = form.elements.namedItem(campo);
        if (!control) return;
        if (control.tagName === 'SELECT' && valor !== '' && !Array.from(control.options).some((opcion) => opcion.value === valor)) {
            const opcion = documento.createElement('option');
            opcion.value = valor;
            opcion.textContent = campo === 'motivo_consulta_id'
                ? paciente.admision_anterior?.motivo_consulta_nombre || 'Motivo de la ficha anterior'
                : valor;
            opcion.dataset.autocompletado = '1';
            control.add(opcion);
        }
        control.value = typeof valor === 'object' ? JSON.stringify(valor) : valor;
    };
    const limpiarFicha = () => {
        conservarFormularioInicial = false;
        config.campos.forEach((campo) => {
            const control = form.elements.namedItem(campo);
            if (!control) return;
            if (control.tagName === 'SELECT') {
                Array.from(control.options).filter((opcion) => opcion.dataset.autocompletado === '1').forEach((opcion) => opcion.remove());
            }
            control.value = '';
        });
        odontograma?.limpiar('#adm-odontograma');
        firma?.limpiar('#adm-firma');
        form.querySelectorAll('[name="clinica_ids[]"], [name^="consentimiento_"]').forEach((control) => control.checked = false);
        if (config.modo === 'crear') {
            form.elements.namedItem('periodo_id').value = '';
        }
        form.elements.namedItem('odontograma_pintado_anterior').value = '';
        form.querySelectorAll('[data-error-for]').forEach((control) => control.textContent = '');
        errorFormulario.style.display = 'none';
    };

    const autocompletado = crearAutocompletadoAdmision({
        modo: config.modo,
        pacienteInicialId: hidden.value,
        preservarInicial: config.preservarInicial,
        campos: config.campos,
        escribirCampo,
        limpiarFicha,
        alPaciente: renderResumen,
        obtenerPaciente: async (id, { signal }) => {
            const respuesta = await solicitar(urlConId(config.urls.datosPaciente, id), { headers, signal, credentials: 'same-origin' });
            if (!respuesta.ok) {
                throw new Error('No se pudieron cargar los datos y la ficha anterior del paciente. Reintente antes de guardar.');
            }
            return respuesta.json();
        },
        alEstado: ({ estado, pacienteId, error: fallo }) => {
            hidden.value = pacienteId;
            wrap.classList.toggle('has-value', pacienteId !== '');
            wrap.classList.toggle('loading', estado === 'cargando');
            botonGuardar.disabled = guardando || estado !== 'listo';
            reintentar.hidden = estado !== 'error';
            cargaEstado.style.display = ['cargando', 'error'].includes(estado) ? 'block' : 'none';
            cargaEstado.className = estado === 'error' ? 'alert alert-danger' : 'alert alert-info';
            cargaMensaje.textContent = estado === 'error' ? fallo.message : 'Cargando los datos y la ficha anterior del paciente…';
        },
    });

    form.addEventListener('input', (evento) => {
        if (config.campos.includes(evento.target.name)) {
            autocompletado.marcarEditado(evento.target.name);
        }
    });
    form.addEventListener('change', (evento) => {
        if (config.campos.includes(evento.target.name)) {
            autocompletado.marcarEditado(evento.target.name);
        }
    });
    form.addEventListener('odontograma:cambio', () => {
        autocompletado.marcarEditado('odontograma_pintado');
        autocompletado.marcarEditado('odontograma_denticion');
    });
    const cancelarBusqueda = () => {
        clearTimeout(timer);
        peticionBusqueda?.abort();
        peticionBusqueda = null;
        dropdown.replaceChildren();
        dropdown.classList.remove('open');
    };
    input.addEventListener('input', () => {
        if (config.modo === 'editar') {
            return;
        }
        cancelarBusqueda();
        autocompletado.limpiar();
        const buscar = input.value.trim();
        if (buscar.length < 2) return;
        timer = setTimeout(async () => {
            const peticion = new AbortController();
            peticionBusqueda = peticion;
            try {
                const respuesta = await solicitar(config.urls.buscarPacientes + '?q=' + encodeURIComponent(buscar), { headers, signal: peticion.signal, credentials: 'same-origin' });
                if (!respuesta.ok) throw new Error('No se pudo buscar al paciente. Intente nuevamente.');
                const items = await respuesta.json();
                if (peticion.signal.aborted || peticionBusqueda !== peticion || input.value.trim() !== buscar) return;
                dropdown.replaceChildren();
                items.forEach((item) => {
                    const boton = documento.createElement('button');
                    boton.type = 'button';
                    boton.className = 'autocomplete-item';
                    boton.textContent = item.texto + (item.extra ? ' · ' + item.extra : '');
                    boton.addEventListener('click', () => {
                        cancelarBusqueda();
                        input.value = item.texto;
                        autocompletado.seleccionar(item.id);
                    });
                    dropdown.append(boton);
                });
                if (!items.length) dropdown.textContent = 'No se encontraron pacientes.';
                dropdown.classList.add('open');
            } catch (fallo) {
                if (!peticion.signal.aborted && peticionBusqueda === peticion) error(fallo.message);
            }
        }, 250);
    });
    botonLimpiar.addEventListener('click', () => {
        if (config.modo === 'editar') {
            return;
        }
        cancelarBusqueda();
        input.value = '';
        autocompletado.limpiar();
        input.focus();
    });
    reintentar.addEventListener('click', () => autocompletado.reintentar());
    documento.addEventListener('click', (evento) => {
        if (!wrap.contains(evento.target)) dropdown.classList.remove('open');
    });
    documento.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') dropdown.classList.remove('open');
    });

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (guardando) return;
        if (!autocompletado.puedeGuardar()) {
            error('Seleccione un paciente y espere a que se carguen sus datos antes de guardar.');
            input.focus();
            return;
        }
        if (!form.reportValidity()) return;
        guardando = true;
        botonGuardar.disabled = true;
        errorFormulario.style.display = 'none';
        form.querySelectorAll('[data-error-for]').forEach((control) => control.textContent = '');
        const body = new FormData(form);
        ['consentimiento_datos', 'consentimiento_fotografias', 'consentimiento_investigacion'].forEach((campo) => {
            body.set(campo, form.elements.namedItem(campo).checked ? '1' : '0');
        });
        try {
            const respuesta = await solicitar(form.action, {
                method: 'POST',
                headers: { ...headers, 'X-CSRF-TOKEN': form.elements.namedItem('_token').value },
                body,
                credentials: 'same-origin',
            });
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.success) {
                const controlesError = Array.from(form.querySelectorAll('[data-error-for]'));
                Object.entries(datos.errors || {}).forEach(([campo, mensajes]) => {
                    const nombreCampo = campo.startsWith('clinica_ids.') ? 'clinica_ids' : campo;
                    const control = controlesError.find((elemento) => elemento.dataset.errorFor === nombreCampo);
                    if (control) control.textContent = Array.isArray(mensajes) ? mensajes[0] : mensajes;
                });
                throw new Error(datos.message || 'Revise los campos de la ficha.');
            }
            documento.defaultView.location.href = urlConId(config.urls.ficha, datos.id);
        } catch (fallo) {
            error(fallo.message);
            guardando = false;
            botonGuardar.disabled = !autocompletado.puedeGuardar();
        }
    });

    if (hidden.value) autocompletado.seleccionar(hidden.value);
    return autocompletado;
}
