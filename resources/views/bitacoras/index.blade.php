@extends('layouts.app')

@section('title', 'Bitácoras del Sistema')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} eventos registrados</span>
    </div>
    <div class="toolbar-actions">
        <span class="badge badge-muted">
            🔒 Solo lectura
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bitácora de Auditoría</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    value="{{ request('buscar') }}"
                    placeholder="Usuario, IP o datos..."
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select id="filtro-tipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $tipo)
                    <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ ucfirst($tipo) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select id="filtro-estado" class="form-control">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Desde</label>
                <input type="date" id="filtro-desde" class="form-control" value="{{ request('desde') }}">
            </div>

            <div class="form-group">
                <label>Hasta</label>
                <input type="date" id="filtro-hasta" class="form-control" value="{{ request('hasta') }}">
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Acción realizada</th>
                    <th>Estado</th>
                    <th>IP</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyBitacoras">
                @include('bitacoras.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap">{{ $registros->links() }}</div>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const URLS = {
            tabla: @json(route('bitacoras.tabla')),
        };

        const $tbody = document.getElementById('tbodyBitacoras');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroTipo = document.getElementById('filtro-tipo');
        const $filtroEstado = document.getElementById('filtro-estado');
        const $filtroDesde = document.getElementById('filtro-desde');
        const $filtroHasta = document.getElementById('filtro-hasta');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        let debounceTimer = null;
        let peticionActual = null;

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
            peticionActual?.abort();
            const peticion = new AbortController();
            peticionActual = peticion;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroTipo?.value) params.set('tipo', $filtroTipo.value);
            if ($filtroEstado?.value) params.set('estado', $filtroEstado.value);
            if ($filtroDesde?.value) params.set('desde', $filtroDesde.value);
            if ($filtroHasta?.value) params.set('hasta', $filtroHasta.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    signal: peticion.signal,
                });

                const data = await res.json();
                if (peticionActual !== peticion) return;
                if (!res.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || `HTTP ${res.status}`);

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} eventos registrados`;

                setStatus('idle');
            } catch (err) {
                if (err.name === 'AbortError' || peticionActual !== peticion) return;
                console.error('[Bitacoras] Error al cargar:', err);
                $tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state">
                        <p>Error al cargar bitácoras.</p>
                    </div>
                </td></tr>`;
                setStatus('error', err.message || 'Error al cargar');
            } finally {
                if (peticionActual === peticion) {
                    $tbody?.classList.remove('loading');
                    peticionActual = null;
                }
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });

        $filtroTipo?.addEventListener('change', () => cargarTabla(1));
        $filtroEstado?.addEventListener('change', () => cargarTabla(1));
        $filtroDesde?.addEventListener('change', () => cargarTabla(1));
        $filtroHasta?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroTipo.value = '';
            $filtroEstado.value = '';
            $filtroDesde.value = '';
            $filtroHasta.value = '';
            cargarTabla(1);
        });

        // Paginación AJAX
        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                cargarTabla(page);
            }
        });

    })();
</script>
@endpush
