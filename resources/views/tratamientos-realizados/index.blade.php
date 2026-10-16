@extends('layouts.app')
@section('title', 'Atención y avances')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} tratamientos registrados</span>
    </div>
    <div class="toolbar-actions"><x-boton-reporte ruta="tratamientos-realizados.reporte" /></div>
</div>

@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div id="mensajeAtencion" class="alert" role="alert" hidden></div>

@if ($resumenRevision)
<section class="revision-catedra" aria-labelledby="revision-catedra-titulo">
    <div class="revision-catedra-intro">
        <div>
            <span class="revision-eyebrow">Coordinación clínica</span>
            <h2 id="revision-catedra-titulo">Bandeja de revisión de cátedra</h2>
            <p>Supervise las propuestas, los avances y los cierres de los estudiantes y docentes de sus clínicas asignadas.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('tratamientos-realizados.index', ['revision' => 'todos']) }}">Ver todos los tratamientos</a>
    </div>
    <div class="revision-cards">
        <a class="revision-card revision-card-propuesta" href="{{ route('tratamientos-realizados.index', ['revision' => 'propuestas']) }}">
            <span class="revision-card-icon" aria-hidden="true">01</span>
            <span class="revision-card-copy"><strong>Propuestas por autorizar</strong><small>Revisar tratamientos antes de habilitar Caja</small></span>
            <span class="revision-card-count" data-review-count="propuestas">{{ $resumenRevision['propuestas'] }}</span>
        </a>
        <a class="revision-card revision-card-avance" href="{{ route('tratamientos-realizados.index', ['revision' => 'avances']) }}">
            <span class="revision-card-icon" aria-hidden="true">02</span>
            <span class="revision-card-copy"><strong>Avances por revisar</strong><small>Validar el trabajo clínico registrado</small></span>
            <span class="revision-card-count" data-review-count="avances">{{ $resumenRevision['avances'] }}</span>
        </a>
        <a class="revision-card revision-card-cierre" href="{{ route('tratamientos-realizados.index', ['revision' => 'culminaciones']) }}">
            <span class="revision-card-icon" aria-hidden="true">03</span>
            <span class="revision-card-copy"><strong>Culminaciones pendientes</strong><small>Confirmar tratamientos finalizados</small></span>
            <span class="revision-card-count" data-review-count="culminaciones">{{ $resumenRevision['culminaciones'] }}</span>
        </a>
    </div>
</section>
@endif

@canany(['atenciones.crear', 'atenciones.gestionar'])
<details class="card propuesta-tratamiento">
    <summary class="card-header"><h2>Proponer tratamiento</h2><span class="badge badge-info">Nueva propuesta</span></summary>
    <div class="card-body">
        <p class="text-muted">Seleccione una historia autorizada. El docente revisará la propuesta antes del pago y del registro de avances.</p>
        <form action="{{ route('tratamientos-realizados.store') }}" method="POST" class="form-grid" data-form-atencion data-propuesta>
            @csrf
            <div class="form-group">
                <label for="propuesta-historia">Historia clínica autorizada</label>
                <select id="propuesta-historia" name="historia_clinica_id" class="form-control" required>
                    <option value="">Seleccione...</option>
                    @foreach ($historias as $historia)
                    <option value="{{ $historia->id }}" @selected((string) old('historia_clinica_id') === (string) $historia->id)>#{{ $historia->id }} · {{ $historia->paciente?->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="propuesta-tratamiento">Tratamiento</label>
                <select id="propuesta-tratamiento" name="tratamiento_id" class="form-control" required>
                    <option value="">Seleccione...</option>
                    @foreach ($catalogo as $tratamiento)
                    <option value="{{ $tratamiento->id }}" @selected((string) old('tratamiento_id') === (string) $tratamiento->id)>{{ $tratamiento->nombre }} · {{ number_format((float) $tratamiento->costo, 2) }} Bs</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label for="propuesta-pieza">Pieza dental</label><input id="propuesta-pieza" name="pieza_dental" value="{{ old('pieza_dental') }}" class="form-control" maxlength="20"></div>
            <div class="form-group"><label for="propuesta-observacion">Observación</label><textarea id="propuesta-observacion" name="observacion" class="form-control" maxlength="2000">{{ old('observacion') }}</textarea></div>
            <div><button class="btn btn-primary"><x-icono nombre="enviar" /> Enviar propuesta</button></div>
        </form>
    </div>
</details>
@endcanany

<div class="card" id="listadoAtenciones">
    <div class="card-header">
        <h2>{{ $resumenRevision ? 'Seguimiento de la clínica' : 'Tratamientos realizados' }}</h2>
        <span class="tabla-status" id="crud-status" role="status" aria-live="polite"><span class="dot"></span><span id="crud-status-text"></span></span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('tratamientos-realizados.index') }}" class="form-grid" id="filtrosAtenciones">
            <div class="form-group">
                <label for="filtro-buscar">Buscar paciente o tratamiento</label>
                <input id="filtro-buscar" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Nombre, CI, tratamiento o pieza dental..." maxlength="200">
            </div>
            <div class="form-group">
                <label for="filtro-estado">Estado</label>
                <select id="filtro-estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            @if ($resumenRevision)
            <div class="form-group">
                <label for="filtro-revision">Bandeja de revisión</label>
                <select id="filtro-revision" name="revision" class="form-control">
                    <option value="pendientes" @selected($revisionActual === 'pendientes')>Pendientes de revisión</option>
                    <option value="propuestas" @selected($revisionActual === 'propuestas')>Propuestas por autorizar</option>
                    <option value="avances" @selected($revisionActual === 'avances')>Avances por revisar</option>
                    <option value="culminaciones" @selected($revisionActual === 'culminaciones')>Culminaciones pendientes</option>
                    <option value="todos" @selected($revisionActual === 'todos')>Todos los tratamientos</option>
                </select>
            </div>
            @endif
            <div class="form-group"><label>Filtros</label><div class="actions"><button class="btn btn-secondary" type="submit">Buscar</button><button class="btn btn-secondary" type="button" id="btnLimpiarFiltros">Limpiar</button></div></div>
        </form>
    </div>
    <div class="table-wrap">
        <table id="tablaAtenciones" aria-label="Tratamientos realizados">
            <thead><tr><th scope="col">N.º</th><th scope="col">Paciente</th><th scope="col">Equipo clínico</th><th scope="col">Tratamiento</th><th scope="col">Pieza</th><th scope="col">Costo (Bs)</th><th scope="col">Estado</th><th scope="col">Avances</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="tbodyAtenciones">@include('tratamientos-realizados.partials.filas', ['registros' => $registros, 'estados' => $estados])</tbody>
        </table>
    </div>
    <div id="paginacionWrap">{{ $registros->links() }}</div>
</div>
@endsection

@push('styles')
<style>
    .propuesta-tratamiento { margin-bottom: 20px; }
    .propuesta-tratamiento > summary { cursor: pointer; }
    .revision-catedra { display: grid; gap: 18px; margin-bottom: 22px; }
    .revision-catedra-intro { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 22px 24px; border: 1px solid var(--line); border-radius: 18px; background: linear-gradient(120deg, var(--surface), var(--surface-soft)); }
    .revision-catedra-intro h2 { margin: 5px 0; color: var(--ink); font-size: 1.2rem; }
    .revision-catedra-intro p { max-width: 680px; color: var(--muted); line-height: 1.5; }
    .revision-eyebrow { color: var(--teal); font-size: .75rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .revision-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .revision-card { display: flex; align-items: center; gap: 14px; min-width: 0; padding: 17px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); color: var(--ink); text-decoration: none; transition: transform var(--transition-fast), border-color var(--transition-fast), box-shadow var(--transition-fast); }
    .revision-card:hover { transform: translateY(-2px); border-color: var(--teal); box-shadow: var(--glass-shadow); }
    .revision-card:focus-visible { outline: 3px solid var(--info); outline-offset: 2px; }
    .revision-card-icon { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center; border-radius: 13px; background: var(--surface-soft); color: var(--teal); font-size: .76rem; font-weight: 800; }
    .revision-card-copy { display: grid; min-width: 0; gap: 4px; }
    .revision-card-copy strong { font-size: .9rem; }
    .revision-card-copy small { color: var(--muted); line-height: 1.4; }
    .revision-card-count { display: grid; min-width: 36px; height: 36px; margin-left: auto; padding: 0 8px; place-items: center; border-radius: 12px; background: var(--surface-soft); color: var(--ink); font-size: 1rem; font-weight: 800; }
    .revision-card-propuesta .revision-card-count { background: var(--warning-bg); color: var(--warning); }
    .revision-card-avance .revision-card-count { background: var(--info-bg); color: var(--info); }
    .revision-card-cierre .revision-card-count { background: var(--success-bg); color: var(--success); }
    .realizado-detalle[hidden] { display: none; }
    .realizado-detalle > td { background: var(--surface-soft); white-space: normal; }
    .realizado-avance { padding: 14px 0; border-top: 1px solid var(--line); }
    .realizado-avance p { white-space: pre-wrap; overflow-wrap: anywhere; }
    #tbodyAtenciones .actions form { margin: 0; }
    @media (max-width: 900px) { .revision-cards { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .revision-catedra-intro { align-items: flex-start; flex-direction: column; padding: 18px; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    'use strict';
    const tablaUrl = @json(route('tratamientos-realizados.tabla'));
    const indexUrl = @json(route('tratamientos-realizados.index'));
    const tbody = document.getElementById('tbodyAtenciones');
    const paginacion = document.getElementById('paginacionWrap');
    const filtros = document.getElementById('filtrosAtenciones');
    const status = document.getElementById('crud-status');
    const statusTexto = document.getElementById('crud-status-text');
    const mensaje = document.getElementById('mensajeAtencion');
    const revisionFiltro = document.getElementById('filtro-revision');
    const abiertos = new Set();
    let paginaActual = @json($registros->currentPage());
    let solicitudActual = null;

    function mostrarMensaje(tipo, texto) {
        mensaje.textContent = texto;
        mensaje.className = 'alert alert-' + (tipo === 'error' ? 'danger' : 'success');
        mensaje.hidden = false;
        window.mostrarToast(tipo, texto);
    }

    function actualizarDetalles() {
        tbody.querySelectorAll('[data-avances-toggle]').forEach(boton => {
            const abierto = abiertos.has(boton.dataset.avancesToggle);
            const detalle = document.getElementById(boton.getAttribute('aria-controls'));
            if (detalle) detalle.hidden = !abierto;
            boton.setAttribute('aria-expanded', String(abierto));
            boton.querySelector('[data-avances-label]').textContent = abierto ? 'Ocultar avances' : 'Ver avances';
        });
    }

    async function cargarTabla(pagina = 1) {
        solicitudActual?.abort();
        const solicitud = new AbortController();
        solicitudActual = solicitud;
        const params = new URLSearchParams(new FormData(filtros));
        params.set('page', pagina);
        status.classList.add('visible');
        statusTexto.textContent = 'Cargando...';
        tbody.setAttribute('aria-busy', 'true');
        try {
            const { ok, data } = await window.fetchAjax(tablaUrl + '?' + params, { signal: solicitud.signal });
            if (solicitudActual !== solicitud) return;
            if (!ok) throw new Error(data?.message || 'No se pudo cargar el listado.');
            tbody.innerHTML = data.filas;
            paginacion.innerHTML = data.paginacion;
            document.getElementById('crud-total-label').textContent = data.total + ' tratamientos registrados';
            if (data.resumenRevision) {
                document.querySelectorAll('[data-review-count]').forEach(contador => {
                    contador.textContent = data.resumenRevision[contador.dataset.reviewCount] ?? 0;
                });
            }
            paginaActual = pagina;
            actualizarDetalles();
            history.replaceState(null, '', indexUrl + '?' + params);
            status.classList.remove('visible');
        } catch (error) {
            if (error.name === 'AbortError') return;
            statusTexto.textContent = 'Error al cargar';
            mostrarMensaje('error', error.message);
        } finally {
            if (solicitudActual === solicitud) tbody.setAttribute('aria-busy', 'false');
        }
    }

    filtros.addEventListener('submit', evento => { evento.preventDefault(); cargarTabla(1); });
    document.getElementById('filtro-buscar').addEventListener('input', window.debounce(() => cargarTabla(1), 300));
    document.getElementById('filtro-estado').addEventListener('change', () => cargarTabla(1));
    revisionFiltro?.addEventListener('change', () => cargarTabla(1));
    document.getElementById('btnLimpiarFiltros').addEventListener('click', () => {
        document.getElementById('filtro-buscar').value = '';
        document.getElementById('filtro-estado').value = '';
        if (revisionFiltro) revisionFiltro.value = 'pendientes';
        cargarTabla(1);
    });

    paginacion.addEventListener('click', evento => {
        const enlace = evento.target.closest('a');
        if (!enlace) return;
        evento.preventDefault();
        cargarTabla(Number(new URL(enlace.href).searchParams.get('page') || 1));
    });
    tbody.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-avances-toggle]');
        if (!boton) return;
        const id = boton.dataset.avancesToggle;
        if (abiertos.has(id)) abiertos.delete(id); else abiertos.add(id);
        actualizarDetalles();
    });

    document.addEventListener('submit', async evento => {
        const form = evento.target.closest('[data-form-atencion]');
        if (!form) return;
        evento.preventDefault();
        if (form.dataset.enviando === 'true') return;
        form.dataset.enviando = 'true';
        const boton = evento.submitter || form.querySelector('button[type="submit"], button:not([type])');
        if (boton) boton.disabled = true;
        mensaje.hidden = true;
        try {
            const { ok, data } = await window.fetchAjax(form.action, { method: 'POST', body: new FormData(form) });
            if (!ok) {
                const errores = Object.values(data?.errors || {}).flat();
                throw new Error(errores[0] || data?.message || 'No se pudo guardar la atención.');
            }
            mostrarMensaje('success', data.message || 'Atención actualizada.');
            if (form.hasAttribute('data-propuesta')) form.reset();
            await cargarTabla(form.hasAttribute('data-propuesta') ? 1 : paginaActual);
        } catch (error) {
            mostrarMensaje('error', error.message);
        } finally {
            form.dataset.enviando = 'false';
            if (boton) boton.disabled = false;
        }
    });
})();
</script>
@endpush
