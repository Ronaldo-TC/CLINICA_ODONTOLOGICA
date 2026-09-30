@extends('layouts.app')

@section('title', 'Panel del Docente')
@section('page-title', 'Panel del Docente')
@section('page-subtitle', 'Seguimiento clínico de tus pacientes y actividades pendientes')



@section('content')

<div class="welcome-card">
    <div class="welcome-header">
        <div>
            <p class="welcome-eyebrow">Actividad clínica · {{ now()->translatedFormat('F Y') }}</p>
            <h2 class="welcome-title">
                Bienvenido, <strong>{{ session('user_name', 'Docente') }}</strong>.
            </h2>
            <p class="welcome-text">
                Revisa tus historias clínicas, pacientes derivados y autorizaciones pendientes.
            </p>
            <div class="session-status">
                Rol: Docente
                @if ($periodoActivo)
                · Periodo activo: <strong>{{ $periodoActivo->nombre }}</strong>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ BARRA DE ACCIONES ============ --}}
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot" id="toolbarDot"></span>
        <span id="toolbarText">Última actualización: hace un momento</span>
    </div>

    <div class="toolbar-actions">
        <label class="auto-refresh-toggle" title="Actualiza cada 30 segundos">
            <input type="checkbox" id="autoRefreshCheck">
            <span class="switch-track"><span class="switch-thumb"></span></span>
            <span>Auto-refresco</span>
        </label>

        <button type="button" class="btn-icon" id="btnRefresh" onclick="refrescarStats(true)">
            <svg viewBox="0 0 24 24">
                <polyline points="23 4 23 10 17 10" />
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
            </svg>
            Actualizar
        </button>
    </div>
</div>

@php
$dashboardMetrics = [
['key' => 'total_pacientes', 'label' => 'Pacientes atendidos'],
['key' => 'historias_pendientes', 'label' => 'Historias por revisar'],
['key' => 'historias_autorizadas', 'label' => 'Historias autorizadas'],
['key' => 'tratamientos_pendientes', 'label' => 'Tratamientos pendientes'],
['key' => 'insumos_pendientes', 'label' => 'Solicitudes de insumos'],
['key' => 'esterilizaciones_pendientes', 'label' => 'Esterilizaciones por autorizar'],
];
@endphp

<div class="metrics-grid">
    @foreach ($dashboardMetrics as $metric)
    <div class="metric-card">
        <div class="metric-value" data-stat="{{ $metric['key'] }}">
            {{ number_format($stats[$metric['key']] ?? 0) }}
        </div>
        <div class="metric-label">{{ $metric['label'] }}</div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h2>Accesos de trabajo</h2>
    </div>
    <div class="card-body" style="display:flex; flex-wrap:wrap; gap:10px;">
        <a class="btn btn-secondary" href="{{ route('docente.pacientes-derivados') }}">Pacientes derivados</a>
        <a class="btn btn-secondary" href="{{ route('docente.historias-clinicas') }}">Historias clínicas</a>
        <a class="btn btn-secondary" href="{{ route('docente.insumos-solicitados') }}">Solicitudes de insumos</a>
        <a class="btn btn-secondary" href="{{ route('docente.cajas') }}">Cajas</a>
        <a class="btn btn-secondary" href="{{ route('docente.esterilizaciones') }}">Esterilización</a>
        <a class="btn btn-secondary" href="{{ route('docente.radiologias') }}">Radiología</a>
        <a class="btn btn-secondary" href="{{ route('docente.reportes') }}">Reportes</a>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Pendientes</h2>
        <button type="button" class="btn btn-secondary btn-sm" id="btnRefreshNotifications" onclick="cargarNotificaciones()">
            Actualizar
        </button>
    </div>
    <div class="card-body">
        <ul id="notificacionesDocente" style="display:grid; gap:10px; list-style:none;">
            <li class="empty-state">Cargando pendientes...</li>
        </ul>
    </div>
</div>

@endsection

@push('scripts')
<script>
    /* ============================================================
   CONFIG
   ============================================================ */
    const URL_STATS = "{{ route('docente.dashboard.stats') }}";
    const URL_NOTIFICACIONES = "{{ route('docente.notificaciones') }}";
    const REFRESH_INTERVAL_MS = 30000; // 30s

    let autoRefreshTimer = null;
    let lastUpdateAt = new Date();

    /* ============================================================
       REFRESCAR STATS POR AJAX
       ============================================================ */
    window.refrescarStats = async function(manual = false) {
        const dot = document.getElementById('toolbarDot');
        const text = document.getElementById('toolbarText');
        const btn = document.getElementById('btnRefresh');

        dot?.classList.remove('live');
        dot?.classList.add('syncing');
        text.textContent = 'Actualizando…';
        if (manual) btn?.classList.add('spinning');

        // Marcar todas las tarjetas como "updating"
        document.querySelectorAll('.metric-card').forEach(c => c.classList.add('updating'));

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(URL_STATS);
            if (!ok) throw new Error('Error al obtener estadísticas');

            // Actualizar cada tarjeta
            actualizarStats(data.stats ?? data);

            lastUpdateAt = new Date();
            dot?.classList.remove('syncing');
            dot?.classList.add('live');
            text.textContent = `Última actualización: ${formatearHace(lastUpdateAt)}`;

            if (manual) window.mostrarToast('success', 'Estadísticas actualizadas');
        } catch (e) {
            dot?.classList.remove('syncing');
            text.textContent = `Error: ${e.message}`;
            if (manual) window.mostrarToast('error', 'No se pudieron actualizar las estadísticas');
        } finally {
            document.querySelectorAll('.metric-card').forEach(c => c.classList.remove('updating'));
            setTimeout(() => btn?.classList.remove('spinning'), 400);
        }
    };

    /* ============================================================
       ACTUALIZAR CADA VALOR EN SU TARJETA
       ============================================================ */
    function actualizarStats(data) {
        const statKeys = [
            'total_pacientes',
            'historias_pendientes',
            'historias_autorizadas',
            'tratamientos_pendientes',
            'insumos_pendientes',
            'esterilizaciones_pendientes',
        ];

        statKeys.forEach((key) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el || typeof data[key] === 'undefined') {
                return;
            }

            const nuevo = numberFormat(data[key]);
            if (el.textContent.trim() !== nuevo) {
                el.textContent = nuevo;
                el.style.transition = 'color .3s ease';
                el.style.color = '#06b6d4';
                setTimeout(() => {
                    el.style.color = '';
                }, 800);
            }
        });
    }

    function numberFormat(v, decimals = 0) {
        const n = Number(v) || 0;
        return n.toLocaleString('es-BO', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    /* ============================================================
       "HACE X SEGUNDOS" EN HUMANO
       ============================================================ */
    function formatearHace(date) {
        const seg = Math.floor((new Date() - date) / 1000);
        if (seg < 5) return 'hace un momento';
        if (seg < 60) return `hace ${seg}s`;
        if (seg < 120) return 'hace 1 minuto';
        return `hace ${Math.floor(seg / 60)} minutos`;
    }

    /* Actualiza el texto "hace Xs" cada 5s (sin pegar al backend) */
    setInterval(() => {
        const text = document.getElementById('toolbarText');
        if (text && !text.textContent.startsWith('Actualizando')) {
            text.textContent = `Última actualización: ${formatearHace(lastUpdateAt)}`;
        }
    }, 5000);

    /* ============================================================
       AUTO-REFRESH CON CHECKBOX
       ============================================================ */
    document.getElementById('autoRefreshCheck')?.addEventListener('change', function() {
        if (this.checked) {
            autoRefreshTimer = setInterval(() => window.refrescarStats(false), REFRESH_INTERVAL_MS);
            window.mostrarToast('info', `Auto-refresco activado (cada ${REFRESH_INTERVAL_MS / 1000}s)`);
            document.getElementById('toolbarDot')?.classList.add('live');
        } else {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
            window.mostrarToast('info', 'Auto-refresco desactivado');
        }
    });

    window.cargarNotificaciones = async function() {
        const lista = document.getElementById('notificacionesDocente');

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(URL_NOTIFICACIONES);
            if (!ok || !data.success) {
                throw new Error('No se pudieron cargar los pendientes');
            }

            lista.replaceChildren();
            if (!data.data.length) {
                const empty = document.createElement('li');
                empty.className = 'empty-state';
                empty.textContent = 'No tienes pendientes.';
                lista.append(empty);
                return;
            }

            data.data.forEach((item) => {
                const row = document.createElement('li');
                const link = document.createElement('a');
                link.className = 'btn btn-secondary';
                link.href = item.url;
                link.textContent = item.mensaje;
                row.append(link);
                lista.append(row);
            });
        } catch (error) {
            lista.replaceChildren();
            const errorMessage = document.createElement('li');
            errorMessage.className = 'empty-state';
            errorMessage.textContent = 'No se pudieron cargar los pendientes.';
            lista.append(errorMessage);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('toolbarDot')?.classList.add('live');
        window.cargarNotificaciones();
    });
</script>
@endpush