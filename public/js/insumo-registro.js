(() => {
    'use strict';
    const form = document.getElementById('formInsumo');
    if (!form) return;
    const modal = document.getElementById('modalInsumo');
    const titulo = document.getElementById('modalInsumoTitulo');
    const id = document.getElementById('formInsumoId');
    const method = document.getElementById('formMethod');
    const clinica = document.getElementById('clinica_id');
    const tratamiento = document.getElementById('insumo-tratamiento');
    const actuales = document.getElementById('insumo-tratamientos-actuales');
    const guardar = document.getElementById('btnGuardarInsumo');
    const guardarTexto = document.getElementById('btnGuardarInsumoText');
    const errorBox = document.getElementById('formInsumoError');
    const errorTexto = document.getElementById('formInsumoErrorText');
    let asignados = [];
    let clinicaOriginal = null;
    let requestSequence = 0;
    let cargando = false;
    let guardando = false;

    function actualizarGuardar() {
        guardar.disabled = guardando || cargando || (tratamiento.required && (!clinica.value || tratamiento.disabled));
        guardarTexto.textContent = guardando ? 'Guardando...' : (id.value ? 'Actualizar Insumo' : 'Registrar Insumo');
    }

    function clearErrors() {
        form.querySelectorAll('[data-error-for]').forEach(element => {element.textContent = '';});
        form.querySelectorAll('.form-control').forEach(element => element.classList.remove('is-invalid'));
        errorBox.style.display = 'none';
    }

    function mostrarErrores(errors, message = 'Corrige los errores marcados en rojo.') {
        Object.entries(errors).forEach(([field, messages]) => {
            const element = form.querySelector(`[data-error-for="${field}"]`);
            if (element) element.textContent = Array.isArray(messages) ? messages[0] : messages;
            form.querySelector(`[name="${field}"]`)?.classList.add('is-invalid');
        });
        errorTexto.textContent = message;
        errorBox.style.display = 'flex';
    }

    async function cargarTratamientos(selected = '') {
        const sequence = ++requestSequence;
        const clinicaId = clinica.value;
        const compartido = String(clinicaOriginal) === clinicaId && asignados.length > 1;
        tratamiento.required = !compartido;
        actuales.textContent = compartido
            ? 'Asignado a: ' + asignados.map(item => item.nombre).join(', ') + '. Seleccionar otro tratamiento agrega la relación y conserva las actuales.'
            : '';
        tratamiento.replaceChildren(new Option(clinicaId ? 'Cargando tratamientos...' : 'Primero seleccione una clínica', ''));
        tratamiento.disabled = true;
        cargando = Boolean(clinicaId);
        actualizarGuardar();
        if (!clinicaId) return;
        try {
            const response = await fetch(form.dataset.tratamientos.replace('__CLINICA__', encodeURIComponent(clinicaId)), {
                headers: {Accept: 'application/json'}, credentials: 'same-origin',
            });
            const result = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok) throw new Error(result.message || 'No se pudieron cargar los tratamientos.');
            tratamiento.replaceChildren(new Option(compartido ? 'Mantener tratamientos actuales' : 'Seleccione un tratamiento', ''));
            result.forEach(item => tratamiento.add(new Option(item.nombre, item.id)));
            if (!result.length && !compartido) {
                tratamiento.replaceChildren(new Option('Esta clínica no tiene tratamientos registrados', ''));
            } else {
                tratamiento.disabled = false;
                if (selected) tratamiento.value = String(selected);
            }
        } catch (failure) {
            if (sequence !== requestSequence) return;
            tratamiento.replaceChildren(new Option('No se pudieron cargar los tratamientos', ''));
            mostrarErrores({}, failure.message);
        } finally {
            if (sequence === requestSequence) {
                cargando = false;
                actualizarGuardar();
            }
        }
    }

    function openModal() {
        modal.classList.add('open');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        ++requestSequence;
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    function resetForm() {
        ++requestSequence;
        form.reset();
        id.value = '';
        method.value = 'POST';
        asignados = [];
        clinicaOriginal = null;
        cargando = false;
        guardando = false;
        clinica.disabled = false;
        tratamiento.required = true;
        tratamiento.disabled = true;
        tratamiento.replaceChildren(new Option('Primero seleccione una clínica', ''));
        actuales.textContent = '';
        clearErrors();
        actualizarGuardar();
    }

    document.getElementById('btnNuevoInsumo')?.addEventListener('click', () => {
        resetForm();
        titulo.textContent = 'Nuevo Insumo';
        openModal();
    });
    clinica.addEventListener('change', () => {
        clearErrors();
        return cargarTratamientos();
    });
    modal.addEventListener('click', event => {
        if (event.target === modal || event.target.closest('[data-modal-close]')) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (guardar.disabled || !form.reportValidity()) return;
        clearErrors();
        const sequence = requestSequence;
        const isEdit = Boolean(id.value);
        const formData = new FormData(form);
        if (isEdit) formData.set('_method', 'PUT');
        const url = isEdit ? form.dataset.update.replace('__INSUMO__', encodeURIComponent(id.value)) : form.dataset.store;
        guardando = true;
        clinica.disabled = true;
        tratamiento.disabled = true;
        actualizarGuardar();
        try {
            const response = await fetch(url, {
                method: 'POST', headers: {
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                    Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                }, body: formData, credentials: 'same-origin',
            });
            const result = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok || !result.success) {
                mostrarErrores(result.errors || {}, result.message || 'No se pudo guardar el insumo.');
                return;
            }
            closeModal();
            window.InsumosLista?.toast('success', result.message || 'Insumo guardado.');
            window.dispatchEvent(new Event('insumo:guardado'));
        } catch (failure) {
            if (sequence === requestSequence) mostrarErrores({}, failure.message || 'No se pudo guardar el insumo.');
        } finally {
            if (sequence === requestSequence) {
                guardando = false;
                clinica.disabled = false;
                tratamiento.disabled = false;
                actualizarGuardar();
            }
        }
    });

    window.InsumosModal = {
        async abrirEdicion(data) {
            resetForm();
            const registro = data.registro ?? data;
            titulo.textContent = 'Editar Insumo';
            method.value = 'PUT';
            id.value = registro.id;
            for (const field of ['nombre', 'cantidad_numerica', 'cantidad', 'stock_minimo', 'costo_unitario']) {
                form.elements.namedItem(field).value = registro[field] ?? '';
            }
            asignados = data.tratamientosAsignados ?? registro.tratamientos ?? [];
            clinicaOriginal = registro.clinica_id;
            clinica.value = String(registro.clinica_id ?? '');
            openModal();
            await cargarTratamientos(asignados.length === 1 ? asignados[0].id : '');
        },
    };
})();
