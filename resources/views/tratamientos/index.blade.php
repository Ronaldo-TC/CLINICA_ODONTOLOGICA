@extends('layouts.app')

@section('title', 'Gestión de Tratamientos')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} tratamientos registrados</span>
    </div>
    <div class="toolbar-actions">
        <x-boton-reporte ruta="tratamientos.reporte" />
        @canany(['tratamientos.crear', 'tratamientos.gestionar'])
        <button type="button" class="btn btn-primary" id="btnNuevoTratamiento">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nuevo Tratamiento
        </button>
        @endcanany
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Catálogo de Tratamientos</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    <div class="card-body" style="padding-bottom: 14px;">
        <p style="color:var(--muted); margin-bottom:14px;">Administración registra los tratamientos por clínica. Insumos asigna los materiales y Caja establece los precios.</p>
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    placeholder="Nombre o descripción..." autocomplete="off">
            </div>

            <div class="form-group">
                <label>Clínica</label>
                <select id="filtro-clinica" class="form-control">
                    <option value="">Todas las clínicas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}" @selected((string) request('clinica_id') === (string) $c->id)>{{ $c->especialidad }} ({{ $c->modulo }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Clínica</th>
                    <th>Descripción</th>
                    <th>Costo</th>
                    <th>Insumos</th>
                    <th>Realizados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyTratamientos">
                @include('tratamientos.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap">{{ $registros->links() }}</div>
</div>

@include('tratamientos.partials.modal')
@include('tratamientos.partials.modal-configuracion')

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('tratamientos.tabla')),
            store: @json(route('tratamientos.store')),
            datos: (id) => `/tratamientos/${id}/datos`,
            show: (id, modo) => `/tratamientos/${id}?modal=${encodeURIComponent(modo)}`,
            update: (id) => `/tratamientos/${id}`,
            destroy: (id) => `/tratamientos/${id}`,
        };

        const $tbody = document.getElementById('tbodyTratamientos');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');
        const $modalConfiguracion = document.getElementById('modalConfiguracionTratamiento');
        const $modalConfiguracionTitulo = document.getElementById('modalConfiguracionTratamientoTitulo');
        const $modalConfiguracionContenido = document.getElementById('modalConfiguracionTratamientoContenido');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        let debounceTimer = null;
        let cargando = false;

        function toast(type, message) {
            const colores = {
                success: 'linear-gradient(135deg, #16a34a, #15803d)',
                error: 'linear-gradient(135deg, #dc2626, #b91c1c)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                info: 'linear-gradient(135deg, #06b6d4, #0891b2)',
            };
            const el = document.createElement('div');
            el.textContent = message;
            Object.assign(el.style, {
                position: 'fixed',
                bottom: '24px',
                right: '24px',
                padding: '13px 20px',
                borderRadius: '12px',
                background: colores[type] || colores.info,
                color: '#fff',
                font: '600 13.5px -apple-system, sans-serif',
                boxShadow: '0 10px 32px rgba(0,0,0,0.18)',
                zIndex: '3000',
                opacity: '0',
                transform: 'translateY(20px) scale(0.95)',
                transition: 'all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1)',
                maxWidth: 'min(360px, calc(100vw - 32px))',
                wordBreak: 'break-word',
            });
            document.body.appendChild(el);
            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
            });
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px) scale(0.95)';
                setTimeout(() => el.remove(), 300);
            }, 3200);
        }

        function setStatus(state, text) {
            if (!$status) return;
            if (state === 'idle') {
                $status.classList.remove('visible');
                return;
            }
            $status.classList.add('visible');
            if ($statusText) $statusText.textContent = text || 'Cargando...';
        }

        async function cargarTabla(page = 1) {
            if (cargando) return;
            cargando = true;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} tratamientos registrados`;

                setStatus('idle');
            } catch (err) {
                console.error('[Tratamientos] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state"><p>Error al cargar tratamientos.</p></div>
                </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                $tbody?.classList.remove('loading');
                cargando = false;
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroClinica.value = '';
            cargarTabla(1);
        });

        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                cargarTabla(page);
                return;
            }

            if (e.target.closest('#btnNuevoTratamiento')) {
                TratamientoModal.abrirCrear($filtroClinica.value);
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'este tratamiento';

            switch (action) {
                case 'ver':
                case 'precio':
                case 'insumos':
                    abrirConfiguracion(id, action, nombre);
                    break;
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
            }
        });

        async function abrirEdicion(id) {
            setStatus('loading', 'Cargando datos...');

            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                TratamientoModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Tratamientos] Error:', err);
                toast('error', 'No se pudieron cargar los datos.');
                setStatus('idle');
            }
        }

        async function abrirConfiguracion(id, modo, nombre) {
            $modalConfiguracionTitulo.textContent = nombre || 'Tratamiento';
            $modalConfiguracionContenido.innerHTML = `
                <div class="modal-body">
                    <p style="color:var(--muted);">Cargando tratamiento...</p>
                </div>`;
            $modalConfiguracion.classList.add('open');
            $modalConfiguracion.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            try {
                const res = await fetch(URLS.show(id, modo), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo cargar el tratamiento.');
                }

                $modalConfiguracionTitulo.textContent = data.titulo || nombre || 'Tratamiento';
                $modalConfiguracionContenido.innerHTML = data.html || '';
                setTimeout(() => $modalConfiguracionContenido.querySelector('input:not([type="hidden"])')?.focus(), 100);
            } catch (err) {
                console.error('[Tratamientos] Error de configuración:', err);
                $modalConfiguracionContenido.innerHTML = `
                    <div class="modal-body">
                        <div class="alert alert-danger">${escaparHtml(err.message || 'No se pudo cargar el tratamiento.')}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-tratamiento-config-close>Cerrar</button>
                    </div>`;
            }
        }

        function cerrarConfiguracion() {
            $modalConfiguracion.classList.remove('open');
            $modalConfiguracion.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        function escaparHtml(value) {
            const element = document.createElement('div');
            element.textContent = value;
            return element.innerHTML;
        }

        function payloadConfiguracion($form) {
            if ($form.dataset.modo === 'precio') {
                return { costo: $form.elements.costo.value };
            }

            const insumos = Array.from($form.querySelectorAll('[data-insumo-row]'))
                .filter(($row) => $row.querySelector('[data-insumo-check]').checked)
                .map(($row) => ({
                    insumo_id: Number($row.querySelector('[data-insumo-check]').value),
                    cantidad: $row.querySelector('[data-insumo-cantidad]').value,
                }));

            return { insumos };
        }

        function mostrarErroresConfiguracion($form, errors) {
            $form.querySelectorAll('[data-error-for]').forEach(($error) => $error.textContent = '');
            Object.entries(errors).forEach(([field, messages]) => {
                const $error = $form.querySelector(`[data-error-for="${field}"]`);
                if ($error) $error.textContent = Array.isArray(messages) ? messages[0] : messages;
            });

            const firstMessage = Object.values(errors).flat()[0] || 'Revise los datos ingresados.';
            const $alert = $form.querySelector('[data-configuracion-error]');
            if ($alert) {
                $alert.textContent = firstMessage;
                $alert.style.display = 'block';
            }
        }

        $modalConfiguracion?.addEventListener('change', (event) => {
            const $check = event.target.closest('[data-insumo-check]');
            if (!$check) return;
            $check.closest('[data-insumo-row]').querySelector('[data-insumo-cantidad]').disabled = !$check.checked;
        });

        $modalConfiguracion?.addEventListener('submit', async (event) => {
            const $form = event.target.closest('[data-tratamiento-config-form]');
            if (!$form) return;
            event.preventDefault();

            const $submit = $form.querySelector('[data-configuracion-submit]');
            const $alert = $form.querySelector('[data-configuracion-error]');
            if ($alert) $alert.style.display = 'none';
            $submit.disabled = true;

            try {
                const res = await fetch($form.action, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payloadConfiguracion($form)),
                });
                const data = await res.json();

                if (res.status === 422) {
                    mostrarErroresConfiguracion($form, data.errors || {});
                    return;
                }
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo guardar la configuración.');
                }

                cerrarConfiguracion();
                toast('success', data.message || 'Configuración actualizada.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Tratamientos] Error al guardar configuración:', err);
                mostrarErroresConfiguracion($form, { general: [err.message || 'No se pudo guardar la configuración.'] });
            } finally {
                $submit.disabled = false;
            }
        });

        $modalConfiguracion?.addEventListener('click', (event) => {
            if (event.target.closest('[data-tratamiento-config-close]') || event.target === $modalConfiguracion) {
                cerrarConfiguracion();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && $modalConfiguracion?.classList.contains('open')) {
                cerrarConfiguracion();
            }
        });

        async function eliminar(id, nombre) {
            if (!await window.confirmarEliminacion(nombre)) return;

            setStatus('loading', 'Eliminando...');

            try {
                const res = await fetch(URLS.destroy(id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al eliminar.');
                }

                toast('success', data.message || 'Tratamiento eliminado.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Tratamientos] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('tratamiento:guardado', () => {
            cargarTabla(1);
        });

        window.TratamientosLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush
