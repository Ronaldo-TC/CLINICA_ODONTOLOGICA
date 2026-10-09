(() => {
    'use strict';
    const form = document.getElementById('formCaja');
    if (!form) return;
    const modal = document.getElementById('modalCaja');
    const id = document.getElementById('formCajaId');
    const clinica = document.getElementById('caja-clinica');
    const ficha = document.getElementById('caja-ficha');
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

    function open() {
        modal.classList.add('open');
        document.body.classList.add('modal-open');
    }

    function close() {
        ++requestSequence;
        clearTimeout(debounce);
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
    }

    function reset() {
        ++requestSequence;
        clearTimeout(debounce);
        form.reset();
        id.value = '';
        clinica.disabled = ficha.readOnly = atencion.disabled = false;
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
            elegirAtencion();
            save.disabled = false;
            document.getElementById('modalCajaTitulo').textContent = 'Editar recibo #' + caja.recibo;
            open();
        },
        cerrar: close,
    };

    document.getElementById('caja-buscar-ficha').addEventListener('click', buscarFicha);
    function changed() {
        if (id.value) return;
        ++requestSequence;
        clearTimeout(debounce);
        limpiarAtencion();
        error.textContent = '';
        save.disabled = true;
        if (ficha.value) debounce = setTimeout(buscarFicha, 300);
    }
    ficha.addEventListener('input', changed);
    clinica.addEventListener('change', changed);
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
