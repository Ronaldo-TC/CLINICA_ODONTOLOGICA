@extends('layouts.app')

@section('title', 'Pacientes remitidos')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="remitidos-total">{{ $registros->total() }} remisiones habilitadas</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Pacientes remitidos</h2>
    </div>

    <div class="card-body" style="padding-bottom:14px;">
        <p style="color:var(--muted);margin-bottom:18px;">
            Revise los pacientes remitidos a sus especialidades y asigne la atención a un estudiante de la especialidad destino.
        </p>

        @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="GET"
            action="{{ route('pacientes-remitidos.index') }}"
            class="form-grid"
            id="remitidos-filtros"
            data-endpoint="{{ route('pacientes-remitidos.buscar') }}">
            <div class="form-group">
                <label for="remitidos-buscar">Buscar paciente</label>
                <input type="text"
                    id="remitidos-buscar"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    class="form-control"
                    placeholder="Nombre o CI..."
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label for="remitidos-clinica">Especialidad destino</label>
                <select id="remitidos-clinica" name="clinica_id" class="form-control">
                    <option value="">Todas las especialidades disponibles</option>
                    @foreach ($clinicas as $clinica)
                    <option value="{{ $clinica->id }}"
                        @selected((string) request('clinica_id')===(string) $clinica->id)>
                        {{ $clinica->especialidad }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="remitidos-periodo">Periodo</label>
                <select id="remitidos-periodo" name="periodo_id" class="form-control">
                    <option value="">Todos los periodos</option>
                    @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}"
                        @selected((string) request('periodo_id')===(string) $periodo->id)>
                        {{ $periodo->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="remitidos-estado">Estado de atención</label>
                <select id="remitidos-estado" name="flujo_estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" @selected(request('flujo_estado')==='pendiente' )>Pendiente de asignación</option>
                    <option value="asignada" @selected(request('flujo_estado')==='asignada' )>Asignada</option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div class="actions">
                    <button type="submit" class="btn btn-primary" id="remitidos-btn-buscar">Buscar</button>
                    <button type="button" class="btn btn-secondary" id="remitidos-btn-limpiar">Limpiar</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Contenedor que se reemplaza vía AJAX --}}
    <div id="remitidos-resultados">
        @include('pacientes-remitidos.partials.tabla', ['items' => $items])
    </div>
</div>

@include('pacientes-remitidos.partials.modal-asignacion')
@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const form = document.getElementById('remitidos-filtros');
        const resultsBox = document.getElementById('remitidos-resultados');
        const totalLabel = document.getElementById('remitidos-total');
        const btnBuscar = document.getElementById('remitidos-btn-buscar');
        const btnLimpiar = document.getElementById('remitidos-btn-limpiar');
        const modalAsignacion = document.getElementById('modalAsignacionRemision');
        const modalAsignacionTitulo = document.getElementById('modalAsignacionRemisionTitulo');
        const modalAsignacionContenido = document.getElementById('modalAsignacionRemisionContenido');

        if (!form || !resultsBox) return;

        const endpoint = form.dataset.endpoint;
        let controller = null;

        const cerrarModalAsignacion = () => {
            modalAsignacion?.classList.remove('open');
            modalAsignacion?.setAttribute('aria-hidden', 'true');
        };

        const mostrarErrorAsignacion = (formulario, errores, mensaje) => {
            formulario.querySelectorAll('[data-error-for]').forEach((elemento) => {
                elemento.textContent = '';
            });

            Object.entries(errores || {}).forEach(([campo, mensajes]) => {
                const elemento = formulario.querySelector(`[data-error-for="${campo}"]`);
                if (elemento) elemento.textContent = mensajes[0] || '';
            });

            const alerta = formulario.querySelector('[data-asignacion-error]');
            if (alerta) {
                alerta.textContent = mensaje || Object.values(errores || {})[0]?.[0] || 'Revise los datos ingresados.';
                alerta.hidden = false;
            }
        };

        const inicializarBuscadorEstudiante = () => {
            const buscador = modalAsignacionContenido.querySelector('[data-estudiante-buscador]');
            const campoBusqueda = buscador?.querySelector('[data-estudiante-busqueda]');
            const campoId = buscador?.querySelector('[data-estudiante-id]');
            const resultados = buscador?.querySelector('[data-estudiante-resultados]');
            const mensajeVacio = buscador?.querySelector('[data-estudiante-vacio]');
            const opciones = Array.from(buscador?.querySelectorAll('[data-estudiante-opcion]') || []);
            let indiceActivo = -1;

            if (!buscador || !campoBusqueda || !campoId || !resultados || !mensajeVacio) return;

            const normalizar = (valor) => valor.normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase();
            const opcionesVisibles = () => opciones.filter((opcion) => !opcion.hidden);
            const cerrarResultados = () => {
                resultados.classList.remove('open');
                resultados.hidden = true;
                campoBusqueda.setAttribute('aria-expanded', 'false');
                indiceActivo = -1;
                opciones.forEach((opcion) => opcion.classList.remove('active'));
            };
            const abrirResultados = () => {
                resultados.hidden = false;
                resultados.classList.add('open');
                campoBusqueda.setAttribute('aria-expanded', 'true');
            };
            const filtrar = () => {
                campoId.value = '';
                campoBusqueda.setCustomValidity('');
                const terminos = normalizar(campoBusqueda.value).trim().split(/\s+/).filter(Boolean);
                let coincidencias = 0;

                opciones.forEach((opcion) => {
                    const coincide = terminos.length > 0 && terminos.every((termino) =>
                        normalizar(opcion.dataset.busqueda || '').includes(termino));
                    opcion.hidden = !coincide;
                    opcion.classList.remove('active');
                    if (coincide) coincidencias++;
                });

                mensajeVacio.textContent = terminos.length === 0
                    ? 'Escriba el CI o nombre del estudiante.'
                    : 'No se encontraron estudiantes con ese CI o nombre.';
                mensajeVacio.hidden = coincidencias > 0;
                indiceActivo = -1;
                abrirResultados();
            };
            const seleccionar = (opcion) => {
                campoId.value = opcion.dataset.id;
                campoBusqueda.value = opcion.dataset.etiqueta;
                campoBusqueda.setCustomValidity('');
                cerrarResultados();
            };
            const moverActivo = (direccion) => {
                const visibles = opcionesVisibles();
                if (visibles.length === 0) return;
                indiceActivo = (indiceActivo + direccion + visibles.length) % visibles.length;
                visibles.forEach((opcion) => opcion.classList.remove('active'));
                visibles[indiceActivo].classList.add('active');
                visibles[indiceActivo].scrollIntoView({ block: 'nearest' });
            };

            campoBusqueda.addEventListener('input', filtrar);
            campoBusqueda.addEventListener('focus', filtrar);
            campoBusqueda.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    moverActivo(event.key === 'ArrowDown' ? 1 : -1);
                } else if (event.key === 'Enter') {
                    const visibles = opcionesVisibles();
                    if (visibles.length > 0) {
                        event.preventDefault();
                        seleccionar(visibles[indiceActivo >= 0 ? indiceActivo : 0]);
                    }
                } else if (event.key === 'Escape') {
                    cerrarResultados();
                }
            });
            opciones.forEach((opcion) => opcion.addEventListener('click', () => seleccionar(opcion)));
            document.addEventListener('click', (event) => {
                if (!buscador.contains(event.target)) cerrarResultados();
            }, { signal: modalAsignacionAbortController.signal });
        };

        let modalAsignacionAbortController = new AbortController();

        const abrirModalAsignacion = async (boton) => {
            if (!modalAsignacion || !modalAsignacionContenido) return;

            modalAsignacionTitulo.textContent = `Asignar estudiante · Remisión #${boton.dataset.remision}`;
            modalAsignacionContenido.innerHTML = '<div class="modal-body"><p style="color:var(--muted);">Cargando datos de la remisión...</p></div>';
            modalAsignacion.classList.add('open');
            modalAsignacion.setAttribute('aria-hidden', 'false');

            try {
                const response = await fetch(boton.dataset.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo cargar la asignación.');
                }

                modalAsignacionTitulo.textContent = data.titulo || 'Asignar estudiante';
                modalAsignacionContenido.innerHTML = data.html;
                modalAsignacionAbortController.abort();
                modalAsignacionAbortController = new AbortController();
                inicializarBuscadorEstudiante();
                modalAsignacionContenido.querySelector('[autofocus]')?.focus();
            } catch (error) {
                modalAsignacionContenido.innerHTML = '<div class="modal-body"><div class="alert alert-danger" data-carga-error></div></div>';
                modalAsignacionContenido.querySelector('[data-carga-error]').textContent = error.message;
            }
        };

        // ----------------------------------------------------------
        // Petición AJAX
        // ----------------------------------------------------------
        const fetchRemitidos = async (params) => {
            // Cancela petición previa si aún está en curso
            if (controller) controller.abort();
            controller = new AbortController();

            resultsBox.style.opacity = '0.5';
            resultsBox.style.pointerEvents = 'none';

            try {
                const url = new URL(endpoint, window.location.origin);
                Object.entries(params).forEach(([k, v]) => {
                    if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
                });

                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    signal: controller.signal,
                });

                if (!res.ok) throw new Error('Error en la respuesta');

                const data = await res.json();
                if (!data.success) throw new Error('Respuesta inválida');

                // Reemplaza contenido
                resultsBox.innerHTML = data.tabla;

                // Actualiza contador
                if (totalLabel) {
                    totalLabel.textContent = `${data.total} remisiones habilitadas`;
                }

                // Actualiza URL sin recargar (para compartir enlace)
                const nuevaUrl = new URL(window.location.href);
                nuevaUrl.search = '';
                Object.entries(data.query).forEach(([k, v]) => {
                    if (v) nuevaUrl.searchParams.set(k, v);
                });
                history.replaceState({}, '', nuevaUrl);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error(err);
                    resultsBox.innerHTML = '<div class="alert alert-danger" style="margin:20px;">No se pudieron cargar los resultados. Intenta de nuevo.</div>';
                }
            } finally {
                resultsBox.style.opacity = '';
                resultsBox.style.pointerEvents = '';
            }
        };

        // ----------------------------------------------------------
        // Eventos
        // ----------------------------------------------------------
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            const params = Object.fromEntries(fd.entries());
            fetchRemitidos(params);
        });

        // Búsqueda automática con debounce en el input de texto
        const inputBuscar = form.querySelector('#remitidos-buscar');
        let debounceTimer = null;
        inputBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const fd = new FormData(form);
                fetchRemitidos(Object.fromEntries(fd.entries()));
            }, 350);
        });

        // Filtros select → cambio inmediato
        form.querySelectorAll('select').forEach((sel) => {
            sel.addEventListener('change', () => {
                const fd = new FormData(form);
                fetchRemitidos(Object.fromEntries(fd.entries()));
            });
        });

        // Botón limpiar
        btnLimpiar?.addEventListener('click', () => {
            form.reset();
            fetchRemitidos({});
        });

        // Paginación AJAX (delegación)
        resultsBox.addEventListener('click', (e) => {
            const botonAsignar = e.target.closest('[data-asignar-estudiante]');
            if (botonAsignar) {
                e.preventDefault();
                abrirModalAsignacion(botonAsignar);
                return;
            }

            const link = e.target.closest('#remitidos-paginacion a');
            if (!link) return;
            e.preventDefault();

            const url = new URL(link.href);
            const params = Object.fromEntries(url.searchParams.entries());
            fetchRemitidos(params);

            // Scroll suave al top de la tabla
            resultsBox.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });

        modalAsignacion?.addEventListener('click', (e) => {
            if (e.target === modalAsignacion || e.target.closest('[data-asignacion-close]')) {
                cerrarModalAsignacion();
            }
        });

        modalAsignacion?.addEventListener('submit', async (e) => {
            const formulario = e.target.closest('#formAsignacionRemision');
            if (!formulario) return;
            e.preventDefault();

            const estudianteId = formulario.querySelector('[data-estudiante-id]');
            const estudianteBusqueda = formulario.querySelector('[data-estudiante-busqueda]');
            if (!estudianteId?.value) {
                estudianteBusqueda?.setCustomValidity('Busque y seleccione un estudiante antes de continuar.');
                estudianteBusqueda?.reportValidity();
                return;
            }

            const boton = formulario.querySelector('[data-asignacion-submit]');
            if (boton) boton.disabled = true;

            try {
                const response = await fetch(formulario.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(formulario),
                });
                const data = await response.json();

                if (response.status === 422) {
                    mostrarErrorAsignacion(formulario, data.errors, data.message);
                    return;
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo registrar la asignación.');
                }

                cerrarModalAsignacion();
                window.mostrarToast?.('success', data.message);
                const datosFiltros = new FormData(form);
                await fetchRemitidos(Object.fromEntries(datosFiltros.entries()));
            } catch (error) {
                mostrarErrorAsignacion(formulario, {}, error.message);
            } finally {
                if (boton) boton.disabled = false;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalAsignacion?.classList.contains('open')) {
                cerrarModalAsignacion();
            }
        });
    })();
</script>
@endpush
