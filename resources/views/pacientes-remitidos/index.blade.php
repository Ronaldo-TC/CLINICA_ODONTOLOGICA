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

        if (!form || !resultsBox) return;

        const endpoint = form.dataset.endpoint;
        let controller = null;

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
    })();
</script>
@endpush