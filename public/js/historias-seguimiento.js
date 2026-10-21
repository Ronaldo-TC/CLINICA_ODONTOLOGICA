(() => {
    'use strict';

    const modal = document.getElementById('modalSeguimientoHistoria');
    if (!modal) {
        console.error('[Historias] No se encontró el modal de seguimiento.');
        return;
    }

    const dialog = modal.querySelector('[role="dialog"]');
    const title = document.getElementById('modalSeguimientoTitulo');
    const body = document.getElementById('modalSeguimientoBody');
    const fullLink = document.getElementById('modalSeguimientoCompleto');
    const baseUrl = (modal.dataset.seguimientoBase || '/historias').replace(/\/$/, '');
    let current = null;
    let requestController = null;
    let opener = null;

    function toast(type, message) {
        if (window.mostrarToast) {
            window.mostrarToast(type, message);
        }
    }

    if (!window.SolicitudesLista) {
        window.SolicitudesLista = {toast};
    }

    function showModal() {
        modal.classList.add('open');
        document.body.classList.add('modal-open');
        window.setTimeout(() => dialog?.focus(), 0);
    }

    function closeModal() {
        requestController?.abort();
        requestController = null;
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
        opener?.focus();
    }

    function loading() {
        body.innerHTML = '<p class="notif-cargando">Cargando informaci\u00f3n\u2026</p>';
        fullLink.hidden = true;
    }

    function requestUrl(historyId, section) {
        return `${baseUrl}/${encodeURIComponent(historyId)}/seguimiento/${encodeURIComponent(section)}`;
    }

    function responseMessage(data, fallback) {
        if (data?.message) return data.message;
        const errors = data?.errors ? Object.values(data.errors).flat() : [];

        return errors[0] || fallback;
    }

    async function readJson(response, fallback) {
        if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('La sesi\u00f3n puede haber expirado. Vuelve a iniciar sesi\u00f3n.');
        }

        const data = await response.json();
        if (!response.ok || data.success === false) {
            throw new Error(responseMessage(data, fallback));
        }

        return data;
    }

    function showActionError(message) {
        let error = body.querySelector('[data-seguimiento-error]');
        if (!error) {
            error = document.createElement('p');
            error.className = 'form-error';
            error.dataset.seguimientoError = '';
            error.setAttribute('role', 'alert');
            body.prepend(error);
        }
        error.textContent = message;
    }

    async function loadCurrent() {
        if (!current) return;

        requestController?.abort();
        requestController = new AbortController();
        loading();

        try {
            const response = await fetch(requestUrl(current.historyId, current.section), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: requestController.signal,
            });
            const data = await readJson(response, 'No se pudo cargar el seguimiento.');

            title.textContent = data.title || 'Seguimiento de la historia cl\u00ednica';
            body.innerHTML = data.html || '<div class="empty-state"><p>Sin informaci\u00f3n disponible.</p></div>';
            fullLink.href = data.url || current.fallbackUrl || '#';
            fullLink.hidden = !data.url && !current.fallbackUrl;
        } catch (error) {
            if (error.name === 'AbortError') return;
            body.innerHTML = '<div class="empty-state"><p></p></div>';
            body.querySelector('p').textContent = error.message || 'No se pudo cargar el seguimiento.';
        } finally {
            requestController = null;
        }
    }

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const trigger = target?.closest('[data-historia-seguimiento]');
        if (!trigger) return;

        event.preventDefault();
        const historyId = trigger.dataset.historiaSeguimiento;
        const section = trigger.dataset.seguimientoSeccion;
        if (!historyId || !section) return;

        opener = trigger;
        current = {historyId, section, fallbackUrl: trigger.dataset.seguimientoUrl || trigger.href || ''};
        title.textContent = trigger.dataset.seguimientoTitulo || 'Seguimiento de la historia cl\u00ednica';
        showModal();
        loadCurrent();
    }, true);

    modal.addEventListener('click', (event) => {
        if (event.target === modal || event.target.closest('[data-seguimiento-close]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        const secondaryModalOpen = document.querySelector('#modalSolicitudDesignado.open, #modalSolicitud.open');
        if (event.key === 'Escape' && modal.classList.contains('open') && !secondaryModalOpen) {
            closeModal();
        }
    });

    modal.addEventListener('submit', async (event) => {
        const form = event.target.closest('form');
        if (!form) return;

        event.preventDefault();
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        if (!form.reportValidity() || form.dataset.saving === 'true') return;

        form.dataset.saving = 'true';
        const buttons = [...form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])')];
        buttons.forEach((button) => { button.disabled = true; });

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const data = await readJson(response, 'No se pudo completar la acci\u00f3n.');

            toast('success', data.message || 'Acci\u00f3n completada.');
            await loadCurrent();
        } catch (error) {
            showActionError(error.message || 'No se pudo completar la acci\u00f3n.');
        } finally {
            delete form.dataset.saving;
            buttons.forEach((button) => { button.disabled = false; });
        }
    });

    window.addEventListener('solicitud:guardada', () => {
        if (!current || current.section !== 'solicitudes' || !modal.classList.contains('open')) return;
        document.body.classList.add('modal-open');
        loadCurrent();
    });
})();
