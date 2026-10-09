(() => {
    'use strict';
    const form = document.getElementById('formSolicitudDesignado');
    if (!form) return;
    const modal = document.getElementById('modalSolicitudDesignado');
    const select = document.getElementById('designado-tratamiento');
    const tbody = document.getElementById('designado-materiales');
    const error = document.getElementById('designado-error');
    const save = document.getElementById('designado-guardar');
    const clinica = document.getElementById('designado-clinica');
    const pacienteSelect = document.getElementById('designado-paciente-select');
    const elegirPaciente = document.getElementById('designado-elegir-paciente');
    let historiaOrigenId = null;
    let historia = null;
    let catalogo = [];
    let edicion = null;
    let tratamientoInicialId = null;
    let requestSequence = 0;

    function close() {
        ++requestSequence;
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
    }

    function materiales() {
        tbody.replaceChildren();
        const tratamiento = catalogo.find(item => String(item.id) === select.value);
        if (!tratamiento?.insumos.length) {
            const row = tbody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 5;
            cell.textContent = tratamiento ? 'Este tratamiento todavía no tiene insumos configurados.' : 'Seleccione un tratamiento.';
            return;
        }
        tratamiento.insumos.forEach(insumo => {
            const row = tbody.insertRow();
            [insumo.nombre, insumo.unidad || 'unidades', insumo.stock, insumo.cantidad].forEach(value => {
                row.insertCell().textContent = String(value);
            });
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control';
            input.min = '0';
            input.max = String(insumo.stock);
            input.step = '0.01';
            input.dataset.insumo = String(insumo.id);
            input.setAttribute('aria-label', 'Solicitar ' + insumo.nombre);
            const mismaAtencion = Number(edicion?.registro?.historia_clinica_id) === Number(historia?.id)
                && Number(edicion?.registro?.tratamiento_id) === Number(tratamiento.id);
            const previo = mismaAtencion ? edicion?.detalles?.find(item => Number(item.insumo_id) === Number(insumo.id)) : null;
            input.value = previo ? String(previo.cantidad_solicitada) : String(Math.min(Number(insumo.cantidad), Number(insumo.stock)));
            row.insertCell().appendChild(input);
        });
    }

    async function cargarCatalogo(clinicaId = null) {
        const sequence = ++requestSequence;
        historia = null;
        catalogo = [];
        error.textContent = '';
        select.replaceChildren(new Option('Cargando tratamientos…', ''));
        select.disabled = true;
        tbody.replaceChildren();
        document.getElementById('designado-paciente').textContent = 'Cargando paciente…';
        save.disabled = true;
        try {
            const params = new URLSearchParams({historia_clinica_id: historiaOrigenId});
            if (clinicaId) params.set('clinica_id', clinicaId);
            const response = await fetch(form.dataset.catalogo + '?' + params, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo cargar el paciente designado.');
            historia = result.historia;
            catalogo = result.tratamientos;
            document.getElementById('designado-paciente').textContent = 'Ficha #' + historia.ficha + ' · ' + historia.paciente + ' · Docente: ' + (historia.docente || 'Sin asignar');
            clinica.replaceChildren(new Option('Seleccione una clínica', ''));
            result.clinicas.forEach(item => clinica.add(new Option(item.nombre, item.id)));
            clinica.value = String(historia.clinica_id);
            clinica.disabled = false;
            select.replaceChildren(new Option('Seleccione un tratamiento', ''));
            catalogo.forEach(item => select.add(new Option(item.nombre, item.id)));
            if (Number(edicion?.registro?.historia_clinica_id) === Number(historia.id)) {
                select.value = String(edicion.registro.tratamiento_id);
                document.getElementById('designado-pieza').value = edicion.registro.pieza || '';
            } else if (Number(historiaOrigenId) === Number(historia.id) && catalogo.some(item => String(item.id) === String(tratamientoInicialId))) {
                select.value = String(tratamientoInicialId);
            } else if (catalogo.length === 1) {
                select.value = String(catalogo[0].id);
            }
            select.disabled = false;
            materiales();
            save.disabled = false;
        } catch (failure) {
            if (sequence === requestSequence) error.textContent = failure.message;
        }
    }

    async function abrir(historiaId = null, data = null, tratamientoId = null) {
        ++requestSequence;
        form.reset();
        historiaOrigenId = historiaId;
        historia = null;
        catalogo = [];
        edicion = data;
        tratamientoInicialId = tratamientoId;
        error.textContent = '';
        select.replaceChildren(new Option('Seleccione un tratamiento', ''));
        select.disabled = true;
        clinica.replaceChildren(new Option('Seleccione una clínica', ''));
        clinica.disabled = true;
        tbody.replaceChildren();
        save.disabled = true;
        if (elegirPaciente) elegirPaciente.hidden = Boolean(historiaId);
        if (pacienteSelect) pacienteSelect.required = !historiaId;
        document.getElementById('designado-paciente').textContent = 'Seleccione un paciente para ver sus clínicas asignadas.';
        modal.classList.add('open');
        document.body.classList.add('modal-open');
        if (historiaId) await cargarCatalogo();
    }

    clinica.addEventListener('change', () => {
        document.getElementById('designado-pieza').value = '';
        if (clinica.value) return cargarCatalogo(clinica.value);
        ++requestSequence;
        historia = null;
        catalogo = [];
        select.replaceChildren(new Option('Seleccione un tratamiento', ''));
        select.disabled = true;
        tbody.replaceChildren();
        save.disabled = true;
    });
    pacienteSelect?.addEventListener('change', () => {
        ++requestSequence;
        historiaOrigenId = pacienteSelect.value || null;
        historia = null;
        catalogo = [];
        clinica.replaceChildren(new Option('Seleccione una clínica', ''));
        clinica.disabled = true;
        select.replaceChildren(new Option('Seleccione un tratamiento', ''));
        select.disabled = true;
        tbody.replaceChildren();
        document.getElementById('designado-pieza').value = '';
        error.textContent = '';
        save.disabled = true;
        if (historiaOrigenId) return cargarCatalogo();
        document.getElementById('designado-paciente').textContent = 'Seleccione un paciente para ver sus clínicas asignadas.';
    });
    select.addEventListener('change', materiales);
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-solicitar-designado]');
        if (button) abrir(button.dataset.historia, null, button.dataset.tratamiento);
    });
    modal.addEventListener('click', event => {
        if (event.target === modal || event.target.closest('[data-designado-close]')) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('open')) close();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        error.textContent = '';
        if (!historia || save.disabled) return;
        const detalles = Array.from(tbody.querySelectorAll('[data-insumo]')).map(input => ({
            insumo_id: Number(input.dataset.insumo), cantidad_solicitada: Number(input.value),
        })).filter(item => item.cantidad_solicitada > 0);
        if (!detalles.length) { error.textContent = 'Indique al menos una cantidad mayor que cero.'; return; }
        save.disabled = true;
        try {
            const id = edicion?.registro?.id;
            const response = await fetch(id ? form.dataset.store + '/' + id : form.dataset.store, {
                method: id ? 'PUT' : 'POST',
                headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({historia_clinica_id: historia.id, clinica_id: historia.clinica_id,
                    tratamiento_id: Number(select.value), pieza: Number(document.getElementById('designado-pieza').value) || 0, detalles}),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo guardar la solicitud.');
            close();
            window.SolicitudesLista?.toast('success', 'Solicitud enviada al docente.');
            window.dispatchEvent(new Event('solicitud:guardada'));
            if (!document.querySelector('[data-estudiante-detalle]') && !window.SolicitudesLista) location.reload();
        } catch (failure) {
            error.textContent = failure.message;
        } finally {
            save.disabled = false;
        }
    });
    window.SolicitudDesignado = {abrir};
})();
