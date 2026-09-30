@extends('layouts.app')

@section('title', 'Panel de Administración')
@section('page-title', 'Panel de Control')
@section('page-subtitle', 'Resumen general de la plataforma clínica y académica')



@section('content')

{{-- ============ TARJETA DE BIENVENIDA ============ --}}
<div class="welcome-card">
    <div class="welcome-header">
        <div>
            <p class="welcome-eyebrow">Panel de control · {{ now()->translatedFormat('F Y') }}</p>
            <h2 class="welcome-title">
                Buenos días, <strong>{{ Auth::user()->nombre_usuario ?? session('user_name', 'Usuario') }}</strong>.
            </h2>
            <p class="welcome-text">
                Este es el resumen actual de la plataforma clínica y académica de la Carrera de Odontología.
                Desde aquí puedes supervisar la actividad general del sistema.
            </p>
            <div class="session-status">
                Sesión web activa · Rol: {{ ucfirst(Auth::user()->tipo ?? session('user_role', 'admin')) }}
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

{{-- ============ MÉTRICAS ============ --}}
<div class="metrics-grid">

    <div class="metric-card" id="card-userCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
        </div>
        <div class="metric-value" data-stat="userCount">
            {{ number_format($userCount ?? 0) }}
        </div>
        <div class="metric-label">Usuarios registrados</div>
    </div>

    <div class="metric-card" id="card-patientCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="patientCount">
            {{ number_format($patientCount ?? 0) }}
        </div>
        <div class="metric-label">Pacientes</div>
    </div>

    <div class="metric-card" id="card-clinicCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 6v4" />
                <path d="M14 8h-4" />
                <path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
                <path d="M3 21h18" />
                <path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="clinicCount">
            {{ number_format($clinicCount ?? 0) }}
        </div>
        <div class="metric-label">Clínicas</div>
    </div>

    <div class="metric-card" id="card-periodCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
        </div>
        <div class="metric-value" data-stat="periodCount">
            {{ number_format($periodCount ?? 0) }}
        </div>
        <div class="metric-label">Periodos activos</div>
    </div>

    {{-- Segunda fila de métricas --}}
    <div class="metric-card" id="card-admisionCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
            </svg>
        </div>
        <div class="metric-value" data-stat="admisionCount">
            {{ number_format($admisionCount ?? 0) }}
        </div>
        <div class="metric-label">Admisiones</div>
    </div>

    <div class="metric-card" id="card-tratamientoCount">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
        </div>
        <div class="metric-value" data-stat="tratamientoCount">
            {{ number_format($tratamientoCount ?? 0) }}
        </div>
        <div class="metric-label">Tratamientos</div>
    </div>

    <div class="metric-card" id="card-ingresosMes">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="metric-value" data-stat="ingresosMes">
            Bs {{ number_format($ingresosMes ?? 0, 2) }}
        </div>
        <div class="metric-label">Ingresos del mes</div>
    </div>

    <div class="metric-card" id="card-pendientes">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
        </div>
        <div class="metric-value" data-stat="pendientes">
            {{ number_format($pendientes ?? 0) }}
        </div>
        <div class="metric-label">Pendientes de revisión</div>
    </div>

</div>

{{-- ============ ÚLTIMAS ADMISIONES ============ --}}
<div class="card">
    <div class="card-header">
        <h2>Últimas admisiones</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Periodo</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="tbodyAdmisiones">
                @forelse ($ultimasAdmisiones ?? [] as $a)
                <tr>
                    <td>{{ $a->id }}</td>
                    <td>{{ $a->nombre }} {{ $a->apellido_paterno }} {{ $a->apellido_materno }}</td>
                    <td>{{ $a->periodo }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">Sin admisiones recientes.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    /* ============================================================
   CONFIG
   ============================================================ */
    const URL_STATS = "{{ route('admin.dashboard.stats') }}";
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
            actualizarStats(data);

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
        const map = {
            userCount: (v) => numberFormat(v),
            patientCount: (v) => numberFormat(v),
            clinicCount: (v) => numberFormat(v),
            periodCount: (v) => numberFormat(v),
            admisionCount: (v) => numberFormat(v),
            tratamientoCount: (v) => numberFormat(v),
            ingresosMes: (v) => `Bs ${numberFormat(v, 2)}`,
            pendientes: (v) => numberFormat(v),
        };

        Object.entries(map).forEach(([key, format]) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el || typeof data[key] === 'undefined') return;

            const nuevo = format(data[key]);
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

    /* ============================================================
       MARCAR DOT COMO "LIVE" AL CARGAR
       ============================================================ */
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('toolbarDot')?.classList.add('live');
    });
</script>
@endpush