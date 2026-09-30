@extends('layouts.app')

@section('title', 'Bitácoras')

@section('content')

{{-- Barra de progreso superior --}}
<div class="loading-bar" id="loadingBar" aria-hidden="true"></div>

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Bitácoras del sistema</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Registro de auditoría de acciones realizadas por los usuarios
        </p>
    </div>
    <button type="button" class="btn btn-secondary" onclick="recargarTabla(true)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <polyline points="23 4 23 10 17 10" />
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
        </svg>
        Refrescar
    </button>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-bitacoras">{{ $bitacoras->total() }}</strong> registros
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroTipo">Tipo de acción</label>
                <select id="filtroTipo" class="form-control">
                    <option value="">— Todos —</option>
                    @foreach ($tipos as $t)
                    <option value="{{ $t }}" @selected(request('tipo')===$t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroUsuario">Usuario</label>
                <select id="filtroUsuario" class="form-control">
                    <option value="">— Todos —</option>
                    @foreach ($usuarios as $u)
                    <option value="{{ $u->id }}" @selected((string)request('usuario_id')===(string)$u->id)>
                        {{ $u->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroFechaInicio">Desde</label>
                <input type="date" id="filtroFechaInicio" class="form-control"
                    value="{{ request('fecha_inicio') }}">
            </div>

            <div class="form-group">
                <label for="filtroFechaFin">Hasta</label>
                <input type="date" id="filtroFechaFin" class="form-control"
                    value="{{ request('fecha_fin') }}">
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTabla(true)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Registro de actividad</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="tablaStatus" aria-live="polite">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm btn-actualizar"
                id="btnActualizarTabla" onclick="recargarTabla(true)" title="Actualizar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <polyline points="23 4 23 10 17 10" />
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                </svg>
                Actualizar
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Tipo</th>
                    <th>Datos</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tbodyBitacoras">
                @include('admin.bitacoras.partials.filas', ['bitacoras' => $bitacoras])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionBitacoras">
        @if ($bitacoras->hasPages())
        <span class="info">
            Mostrando <strong>{{ $bitacoras->firstItem() }}</strong>–<strong>{{ $bitacoras->lastItem() }}</strong>
            de <strong>{{ $bitacoras->total() }}</strong> registros
        </span>
        <div>{{ $bitacoras->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
    /* ============================================================
       CONFIG
       ============================================================ */
    const URL_TABLA_BITACORAS = "{{ route('admin.bitacoras.tabla') }}";

    /* ============================================================
       BARRA DE PROGRESO SUPERIOR
       ============================================================ */
    let loadingBarTimeout = null;

    function mostrarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        clearTimeout(loadingBarTimeout);
        bar.classList.remove('complete');
        bar.classList.add('active');
    }

    function completarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        bar.classList.remove('active');
        bar.classList.add('complete');
        loadingBarTimeout = setTimeout(() => {
            bar.classList.remove('complete');
            bar.style.width = '0';
        }, 400);
    }

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    let recargandoTabla = false;

    window.recargarTabla = async function(manual = false) {
        if (recargandoTabla) return;
        recargandoTabla = true;

        const tbody = document.getElementById('tbodyBitacoras');
        const status = document.getElementById('tablaStatus');
        const btnActualizar = document.getElementById('btnActualizarTabla');

        mostrarBarraCarga();
        tbody?.classList.add('loading');
        status?.classList.add('visible');
        btnActualizar?.classList.add('spinning');
        if (btnActualizar) btnActualizar.disabled = true;

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_BITACORAS,
                tbodyId: 'tbodyBitacoras',
                paginacionId: 'paginacionBitacoras',
                totalId: 'total-bitacoras',
                filtros: {
                    tipo: 'filtroTipo',
                    usuario_id: 'filtroUsuario',
                    fecha_inicio: 'filtroFechaInicio',
                    fecha_fin: 'filtroFechaFin',
                },
            });

            tbody?.querySelectorAll('tr').forEach((tr, i) => {
                tr.style.animation = 'none';
                void tr.offsetHeight;
                tr.style.animation = '';
                tr.style.animationDelay = `${Math.min(i * 0.02, 0.4)}s`;
            });

            if (manual) window.mostrarToast('success', 'Bitácoras actualizadas');
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error al recargar la tabla');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
            btnActualizar?.classList.remove('spinning');
            if (btnActualizar) btnActualizar.disabled = false;
            completarBarraCarga();
            recargandoTabla = false;
        }
    };

    /* ============================================================
       FILTROS
       ============================================================ */
    window.limpiarFiltros = function() {
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroUsuario').value = '';
        document.getElementById('filtroFechaInicio').value = '';
        document.getElementById('filtroFechaFin').value = '';
        window.recargarTabla();
    };

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('tbodyBitacoras');
        tbody?.querySelectorAll('tr').forEach((tr, i) => {
            tr.style.animationDelay = `${Math.min(i * 0.02, 0.4)}s`;
        });

        // Filtros en vivo
        document.getElementById('filtroTipo')
            ?.addEventListener('change', () => window.recargarTabla());

        document.getElementById('filtroUsuario')
            ?.addEventListener('change', () => window.recargarTabla());

        document.getElementById('filtroFechaInicio')
            ?.addEventListener('change', () => window.recargarTabla());

        document.getElementById('filtroFechaFin')
            ?.addEventListener('change', () => window.recargarTabla());

        // Wrapper global para fetchAjax (sin sobrescribir si ya existe)
        if (!window.__fetchAjaxWrapped) {
            const originalFetch = window.fetchAjax;
            window.fetchAjax = async function(url, opts) {
                mostrarBarraCarga();
                try {
                    return await originalFetch(url, opts);
                } finally {
                    completarBarraCarga();
                }
            };
            window.__fetchAjaxWrapped = true;
        }
    });
</script>
@endpush