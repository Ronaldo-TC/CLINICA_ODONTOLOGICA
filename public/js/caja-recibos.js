(() => {
    'use strict';
    const form = document.getElementById('formCaja');
    if (!form) return;
    const modal = document.getElementById('modalCaja');
    const id = document.getElementById('formCajaId');
    const clinica = document.getElementById('caja-clinica');
    const ficha = document.getElementById('caja-ficha');
    const buscarPaciente = document.getElementById('caja-paciente-buscar');
    const busquedaPaciente = document.getElementById('caja-paciente-busqueda');
    const resultadosPaciente = document.getElementById('caja-paciente-resultados');
    const estadoPaciente = document.getElementById('caja-paciente-buscar-estado');
    const atencion = document.getElementById('caja-atencion');
    const paciente = document.getElementById('caja-paciente');
    const estudiante = document.getElementById('caja-estudiante');
    const costo = document.getElementById('caja-costo-total');
    const anterior = document.getElementById('caja-anterior');
    const total = document.getElementById('caja-total-cancelado');
    const saldo = document.getElementById('caja-saldo');
    const error = document.getElementById('formCajaErrorText');
    const save = document.getElementById('btnGuardarCaja');
    let atenciones = [];
    let designacion = null;
    let requestSequence = 0;
    let debounce = null;
    let patientSequence = 0;
    let patientDebounce = null;
    let coincidencias = [];
    let opcionActiva = -1;

    function resumen() {
        const acumulado = Math.round(Number(anterior.value || 0) * 100) +
            (form.habilitada.checked ? Math.round(Number(form.cancelado.value || 0) * 100) : 0);
        total.value = (acumulado / 100).toFixed(2);
        saldo.value = ((Math.round(Number(costo.value || 0) * 100) - acumulado) / 100).toFixed(2);
    }

    function limpiarAtencion() {
        atenciones = [];
        designacion = null;
        atencion.replaceChildren(new Option('Busque primero la ficha', ''));
        paciente.value = estudiante.value = costo.value = form.tratamiento_id.value = '';
        anterior.value = '0.00';
        resumen();
    }

    function elegirAtencion() {
        const item = atenciones.find(item => String(item.id) === atencion.value);
        if (!item) {
            paciente.value = designacion?.paciente || '';
            estudiante.value = designacion?.estudiante || '';
            costo.value = form.tratamiento_id.value = '';
            anterior.value = '0.00';
            save.disabled = true;
            resumen();
            return;
        }
        clinica.value = String(item.clinica_id || clinica.value);
        paciente.value = item.paciente;
        estudiante.value = item.estudiante;
        costo.value = Number(item.costo).toFixed(2);
        anterior.value = Number(item.cancelado_anterior).toFixed(2);
        form.tratamiento_id.value = item.tratamiento_id;
        save.disabled = false;
        resumen();
    }

    async function buscarFicha() {
        clearTimeout(debounce);
        if (id.value || !ficha.value) return;
        const sequence = ++requestSequence;
        limpiarAtencion();
        error.textContent = 'Buscando ficha…';
        save.disabled = true;
        try {
            const params = new URLSearchParams({ficha: ficha.value});
            if (clinica.value) params.set('clinica_id', clinica.value);
            const response = await fetch(form.dataset.ficha + '?' + params, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok) throw new Error(result.message || 'No se encontró la ficha.');
            atenciones = result.atenciones;
            designacion = {paciente: result.paciente, estudiante: result.estudiante};
            paciente.value = designacion.paciente;
            estudiante.value = designacion.estudiante || '';
            if (result.clinica_id) clinica.value = String(result.clinica_id);
            atencion.replaceChildren(new Option('Seleccione un tratamiento autorizado', ''));
            atenciones.forEach(item => atencion.add(new Option(item.tratamiento + ' · ' + item.estudiante + ' · Saldo: ' + Number(item.saldo).toFixed(2) + ' Bs', item.id)));
            error.textContent = atenciones.length ? '' : 'La ficha no tiene tratamientos autorizados en esta clínica. El docente debe autorizar el tratamiento.';
            if (atenciones.length === 1) { atencion.value = String(atenciones[0].id); elegirAtencion(); }
            save.disabled = !atencion.value;
        } catch (failure) {
            if (sequence === requestSequence) error.textContent = failure.message;
        }
    }

    function ocultarOpciones() {
        resultadosPaciente.hidden = true;
        resultadosPaciente.classList.remove('open');
        buscarPaciente.setAttribute('aria-expanded', 'false');
        buscarPaciente.removeAttribute('aria-activedescendant');
        opcionActiva = -1;
    }

    function mostrarOpciones() {
        if (!coincidencias.length) return;
        resultadosPaciente.hidden = false;
        resultadosPaciente.classList.add('open');
        buscarPaciente.setAttribute('aria-expanded', 'true');
    }

    function limpiarBusquedaPaciente(borrarTexto = true) {
        ++patientSequence;
        clearTimeout(patientDebounce);
        if (borrarTexto) buscarPaciente.value = '';
        coincidencias = [];
        resultadosPaciente.replaceChildren();
        ocultarOpciones();
        estadoPaciente.textContent = '';
    }

    async function buscarPorPaciente() {
        clearTimeout(patientDebounce);
        if (id.value) return;
        limpiarBusquedaPaciente(false);
        ++requestSequence;
        clearTimeout(debounce);
        ficha.value = '';
        limpiarAtencion();
        error.textContent = '';
        save.disabled = true;
        const buscar = buscarPaciente.value.trim();
        if (buscar.length < 2) {
            estadoPaciente.textContent = 'Ingrese al menos dos caracteres del CI o nombre del paciente.';
            return;
        }
        const sequence = patientSequence;
        estadoPaciente.textContent = 'Buscando pacientes…';
        try {
            const params = new URLSearchParams({buscar});
            if (clinica.value) params.set('clinica_id', clinica.value);
            const response = await fetch(form.dataset.pacientes + '?' + params, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (sequence !== patientSequence) return;
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo buscar al paciente.');
            coincidencias = result.fichas;
            coincidencias.forEach((item, index) => {
                const opcion = document.createElement('button');
                opcion.type = 'button';
                opcion.tabIndex = -1;
                opcion.id = 'caja-paciente-opcion-' + index;
                opcion.className = 'autocomplete-item';
                opcion.setAttribute('role', 'option');
                opcion.setAttribute('aria-selected', 'false');
                const info = document.createElement('span');
                info.className = 'autocomplete-info';
                const nombre = document.createElement('span');
                nombre.className = 'autocomplete-name';
                nombre.textContent = item.paciente;
                const detalle = document.createElement('span');
                detalle.className = 'autocomplete-meta';
                detalle.textContent = 'CI: ' + item.ci + ' · Ficha #' + item.ficha + (item.fecha ? ' · ' + item.fecha : '');
                info.append(nombre, detalle);
                opcion.append(info);
                opcion.addEventListener('click', () => elegirPaciente(item));
                resultadosPaciente.append(opcion);
            });
            mostrarOpciones();
            estadoPaciente.textContent = result.fichas.length
                ? (result.hay_mas ? 'Se muestran las primeras 20 fichas. Escriba más caracteres para precisar la búsqueda.' : 'Seleccione la ficha de admisión que corresponde al recibo.')
                : 'No se encontraron fichas con pacientes designados para esta búsqueda.';
        } catch (failure) {
            if (sequence === patientSequence) estadoPaciente.textContent = failure.message;
        }
    }

    function changedPaciente() {
        if (id.value) return;
        limpiarBusquedaPaciente(false);
        ++requestSequence;
        clearTimeout(debounce);
        ficha.value = '';
        limpiarAtencion();
        error.textContent = '';
        save.disabled = true;
        if (buscarPaciente.value.trim().length >= 2) patientDebounce = setTimeout(buscarPorPaciente, 300);
    }

    function elegirPaciente(item) {
        if (id.value) return;
        limpiarBusquedaPaciente(false);
        buscarPaciente.value = item.paciente;
        ficha.value = String(item.ficha);
        buscarPaciente.focus();
        return buscarFicha();
    }

    function open() {
        modal.classList.add('open');
        document.body.classList.add('modal-open');
    }

    function close() {
        ++requestSequence;
        clearTimeout(debounce);
        limpiarBusquedaPaciente();
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
    }

    function reset() {
        ++requestSequence;
        clearTimeout(debounce);
        form.reset();
        limpiarBusquedaPaciente();
        id.value = '';
        clinica.disabled = ficha.readOnly = atencion.disabled = false;
        buscarPaciente.disabled = false;
        error.textContent = '';
        save.disabled = true;
        limpiarAtencion();
    }

    window.CajaModal = {
        abrirCrear() {
            reset();
            document.getElementById('modalCajaTitulo').textContent = 'Nuevo recibo';
            open();
        },
        abrirEdicion(data) {
            reset();
            const caja = data.registro;
            const caso = data.atencion;
            id.value = caja.id;
            clinica.value = String(caja.clinica_id);
            ficha.value = caso?.historia_clinica?.admision_id || '';
            atenciones = [{id: caja.tratamiento_realizado_id, costo: caja.costo_total,
                cancelado_anterior: data.cancelado_anterior || 0, paciente: caja.paciente_nombre_completo,
                estudiante: caso?.historia_clinica?.estudiante?.nombre_completo || '', tratamiento_id: caja.tratamiento_id}];
            atencion.replaceChildren(new Option('Atención #' + caja.tratamiento_realizado_id, caja.tratamiento_realizado_id));
            ['recibo', 'cancelado', 'descripcion', 'tipo_pago'].forEach(field => { form[field].value = caja[field] ?? ''; });
            form.habilitada.checked = !!caja.habilitada;
            clinica.disabled = ficha.readOnly = atencion.disabled = true;
            buscarPaciente.disabled = true;
            elegirAtencion();
            save.disabled = false;
            document.getElementById('modalCajaTitulo').textContent = 'Editar recibo #' + caja.recibo;
            open();
        },
        cerrar: close,
    };

    function changed() {
        if (id.value) return;
        limpiarBusquedaPaciente();
        ++requestSequence;
        clearTimeout(debounce);
        limpiarAtencion();
        error.textContent = '';
        save.disabled = true;
        if (ficha.value) debounce = setTimeout(buscarFicha, 300);
    }
    ficha.addEventListener('input', changed);
    clinica.addEventListener('change', () => {
        if (!ficha.value && buscarPaciente.value) changedPaciente();
        else changed();
    });
    buscarPaciente.addEventListener('input', changedPaciente);
    buscarPaciente.addEventListener('focus', mostrarOpciones);
    buscarPaciente.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (!resultadosPaciente.hidden && opcionActiva >= 0) return elegirPaciente(coincidencias[opcionActiva]);
        } else if (['ArrowDown', 'ArrowUp'].includes(event.key) && coincidencias.length) {
            event.preventDefault();
            mostrarOpciones();
            opcionActiva = opcionActiva < 0
                ? (event.key === 'ArrowDown' ? 0 : coincidencias.length - 1)
                : (opcionActiva + (event.key === 'ArrowDown' ? 1 : -1) + coincidencias.length) % coincidencias.length;
            Array.from(resultadosPaciente.children).forEach((opcion, index) => {
                opcion.classList.toggle('active', index === opcionActiva);
                opcion.setAttribute('aria-selected', String(index === opcionActiva));
            });
            const opcion = resultadosPaciente.children[opcionActiva];
            buscarPaciente.setAttribute('aria-activedescendant', opcion.id);
            opcion.scrollIntoView({block: 'nearest'});
        } else if (event.key === 'Escape' || event.key === 'Tab') {
            if (event.key === 'Escape' && !resultadosPaciente.hidden) { event.preventDefault(); event.stopPropagation(); }
            ++patientSequence;
            clearTimeout(patientDebounce);
            ocultarOpciones();
        }
    });
    document.addEventListener('click', event => {
        if (!busquedaPaciente.contains(event.target)) {
            ++patientSequence;
            clearTimeout(patientDebounce);
            ocultarOpciones();
        }
    });
    atencion.addEventListener('change', elegirAtencion);
    form.cancelado.addEventListener('input', resumen);
    form.habilitada.addEventListener('change', resumen);
    modal.addEventListener('click', event => { if (event.target === modal || event.target.closest('[data-caja-close]')) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal.classList.contains('open')) close(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled) return;
        error.textContent = '';
        save.disabled = true;
        try {
            const body = new FormData(form);
            body.set('tratamiento_realizado_id', atencion.value);
            body.set('clinica_id', clinica.value);
            body.set('habilitada', form.habilitada.checked ? '1' : '0');
            if (id.value) body.set('_method', 'PUT');
            const response = await fetch(id.value ? form.dataset.update.replace('__ID__', id.value) : form.dataset.store, {
                method: 'POST', headers: {Accept: 'application/json'}, body,
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo guardar el recibo.');
            close();
            window.CajasLista?.toast('success', result.message);
            window.dispatchEvent(new Event('caja:guardada'));
        } catch (failure) { error.textContent = failure.message; }
        finally { save.disabled = false; }
    });
})();
