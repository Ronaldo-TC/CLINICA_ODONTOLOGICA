(() => {
    'use strict';

    const filters = document.querySelector('[data-estudiante-filtros]');
    const table = document.querySelector('[data-estudiante-tabla]');
    const detail = document.querySelector('[data-estudiante-detalle]');
    const status = document.querySelector('[data-estudiante-status]');
    const feedback = document.querySelector('[data-estudiante-feedback]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let listRequest;
    let detailRequest;
    let searchTimer;

    async function readJson(response) {
        if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('La sesión puede haber expirado. Vuelve a iniciar sesión.');
        }
        const data = await response.json();
        if (!response.ok || data.success === false) {
            const errors = Object.values(data.errors || {}).flat();
            throw new Error(errors.join(' ') || data.message || 'No se pudo completar la operación.');
        }
        return data;
    }

    function showFeedback(message, error = false) {
        if (!feedback) return;
        feedback.className = error ? 'alert alert-danger' : 'alert alert-success';
        feedback.textContent = message;
    }

    async function loadTable(url, updateHistory = true) {
        listRequest?.abort();
        const currentRequest = new AbortController();
        listRequest = currentRequest;
        table.setAttribute('aria-busy', 'true');
        status.textContent = 'Cargando pacientes…';
        try {
            const response = await fetch(url, {
                headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin', signal: currentRequest.signal,
            });
            const data = await readJson(response);
            if (currentRequest.signal.aborted) return;
            table.innerHTML = data.tabla;
            status.textContent = `${data.total} registros encontrados.`;
            document.querySelector('[data-estudiante-total]')?.replaceChildren(document.createTextNode(String(data.total)));
            if (updateHistory) history.replaceState(null, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = error.message;
        } finally {
            if (listRequest === currentRequest) table.removeAttribute('aria-busy');
        }
    }

    function applyFilters() {
        clearTimeout(searchTimer);
        const url = new URL(filters.action, location.origin);
        for (const [key, value] of new FormData(filters)) {
            if (String(value).trim()) url.searchParams.set(key, String(value).trim());
        }
        loadTable(url);
    }

    if (filters && table) {
        filters.addEventListener('submit', event => { event.preventDefault(); applyFilters(); });
        filters.addEventListener('change', event => { if (event.target.matches('select')) applyFilters(); });
        filters.querySelector('[name="buscar"]')?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            listRequest?.abort();
            searchTimer = setTimeout(applyFilters, 300);
        });
        filters.addEventListener('reset', event => {
            event.preventDefault();
            for (const field of filters.querySelectorAll('input, select')) field.value = '';
            applyFilters();
        });
        table.addEventListener('click', event => {
            const link = event.target.closest('.pagination a, nav a');
            if (!link || !table.contains(link)) return;
            event.preventDefault();
            clearTimeout(searchTimer);
            loadTable(new URL(link.href, location.origin));
        });
    }

    async function refreshDetail() {
        if (!detail) return;
        detailRequest?.abort();
        const currentRequest = new AbortController();
        detailRequest = currentRequest;
        detail.setAttribute('aria-busy', 'true');
        try {
            const data = await readJson(await fetch(detail.dataset.url, {
                headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin', signal: currentRequest.signal,
            }));
            if (!currentRequest.signal.aborted) detail.innerHTML = data.detalle;
        } catch (error) {
            if (error.name !== 'AbortError') showFeedback('Se guardó la operación, pero no se pudo actualizar la vista. ' + error.message, true);
        } finally {
            if (detailRequest === currentRequest) detail.removeAttribute('aria-busy');
        }
    }

    document.addEventListener('submit', async event => {
        const form = event.target.closest('[data-estudiante-form]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.saving === 'true') return;
        if (!form.reportValidity()) return;
        form.dataset.saving = 'true';
        const button = form.querySelector('button[type="submit"], button:not([type])');
        const errorMessage = form.querySelector('[data-form-error]');
        const originalLabel = button?.textContent;
        if (errorMessage) errorMessage.textContent = '';
        if (button) { button.disabled = true; button.textContent = 'Guardando…'; }
        try {
            let body = new FormData(form);
            const headers = {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf};
            if (form.hasAttribute('data-consumo')) {
                const detalles = Array.from(form.querySelectorAll('[data-detalle]')).map(input => ({
                    detalle_id: Number(input.dataset.detalle), cantidad_usada: Number(input.value),
                })).filter(item => item.cantidad_usada > 0);
                if (!detalles.length) throw new Error('Indica la cantidad utilizada de al menos un insumo.');
                body = JSON.stringify({detalles});
                headers['Content-Type'] = 'application/json';
            }
            const data = await readJson(await fetch(form.action, {method: 'POST', headers, body, credentials: 'same-origin'}));
            showFeedback(data.message || 'Registro guardado correctamente.');
            await refreshDetail();
        } catch (error) {
            if (errorMessage) errorMessage.textContent = error.message;
            showFeedback(error.message, true);
        } finally {
            delete form.dataset.saving;
            if (button) { button.disabled = false; button.textContent = originalLabel; }
        }
    });

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-solicitar-insumos]');
        if (!button) return;
        window.SolicitudModal?.abrirCrear({
            clinica_id: button.dataset.clinica, tratamiento_realizado_id: button.dataset.tratamiento,
            sesion_atencion_id: button.dataset.sesion, tratamiento_nombre: button.dataset.nombre, pieza: button.dataset.pieza,
        });
    });

    if (detail) {
        window.SolicitudesLista = {toast: (type, message) => showFeedback(message, type === 'error')};
        window.addEventListener('solicitud:guardada', refreshDetail);
    }
})();
