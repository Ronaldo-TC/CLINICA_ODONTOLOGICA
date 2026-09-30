@extends('layouts.app')

@section('title', 'Panel de Dirección')
@section('page-title', 'Panel de Dirección de Carrera')
@section('page-subtitle', 'Supervisión global · Clínica Odontológica UPEA')

@section('content')

{{-- ============ TARJETA DE BIENVENIDA ============ --}}
<div class="welcome-card">
    <div class="welcome-header">
        <div>
            <p class="welcome-eyebrow">Dirección de Carrera · {{ now()->translatedFormat('F Y') }}</p>
            <h2 class="welcome-title">
                Buenos días, <strong>{{ Auth::user()->nombre_usuario ?? session('user_name', 'Director') }}</strong>.
            </h2>
            <p class="welcome-text">
                Este es el resumen ejecutivo de la Clínica Odontológica.
                Desde aquí puedes supervisar la actividad académica y clínica de todas las especialidades,
                consultar reportes para acreditación e investigación, y validar indicadores por periodo,
                docente y estudiante.
            </p>
            <div class="session-status">
                Sesión web activa · Rol: Director de Carrera
                @if($periodoActivo ?? null)
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
        <label class="auto-refresh-toggle" title="Actualiza cada 60 segundos">
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

{{-- ============ MÉTRICAS PRINCIPALES ============ --}}
<div class="metrics-grid">

    <div class="metric-card" id="card-pacientesTotal">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="pacientes_total">
            {{ number_format($stats['pacientes_total'] ?? 0) }}
        </div>
        <div class="metric-label">Pacientes registrados</div>
    </div>

    <div class="metric-card" id="card-pacientesPeriodo">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="8.5" cy="7" r="4" />
                <line x1="20" y1="8" x2="20" y2="14" />
                <line x1="23" y1="11" x2="17" y2="11" />
            </svg>
        </div>
        <div class="metric-value" data-stat="pacientes_periodo">
            {{ number_format($stats['pacientes_periodo'] ?? 0) }}
        </div>
        <div class="metric-label">Pacientes del periodo</div>
    </div>

    <div class="metric-card" id="card-clinicasActivas">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 6v4" />
                <path d="M14 8h-4" />
                <path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
                <path d="M3 21h18" />
                <path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="clinicas_activas">
            {{ number_format($stats['clinicas_activas'] ?? 0) }}
            <span class="metric-sub">/ {{ number_format($stats['clinicas_total'] ?? 0) }}</span>
        </div>
        <div class="metric-label">Clínicas activas</div>
    </div>

    <div class="metric-card" id="card-docentesActivos">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <polyline points="16 11 18 13 22 9" />
            </svg>
        </div>
        <div class="metric-value" data-stat="docentes_activos">
            {{ number_format($stats['docentes_activos'] ?? 0) }}
        </div>
        <div class="metric-label">Docentes activos</div>
    </div>

    <div class="metric-card" id="card-estudiantesActivos">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                <path d="M6 12v5c3 3 9 3 12 0v-5" />
            </svg>
        </div>
        <div class="metric-value" data-stat="estudiantes_activos">
            {{ number_format($stats['estudiantes_activos'] ?? 0) }}
        </div>
        <div class="metric-label">Estudiantes activos</div>
    </div>

    {{-- Segunda fila --}}

    <div class="metric-card" id="card-historiasTotal">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
                <path d="M9 12h6M9 16h4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="historias_total">
            {{ number_format($stats['historias_total'] ?? 0) }}
        </div>
        <div class="metric-label">Historias clínicas</div>
    </div>

    <div class="metric-card" id="card-historiasPendientes">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
        </div>
        <div class="metric-value" data-stat="historias_pendientes">
            {{ number_format($stats['historias_pendientes'] ?? 0) }}
        </div>
        <div class="metric-label">HC pendientes de revisión</div>
    </div>

    <div class="metric-card" id="card-tratamientosCompletados">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
        </div>
        <div class="metric-value" data-stat="tratamientos_completados">
            {{ number_format($stats['tratamientos_completados'] ?? 0) }}
            <span class="metric-sub">/ {{ number_format($stats['tratamientos_total'] ?? 0) }}</span>
        </div>
        <div class="metric-label">Tratamientos completados</div>
    </div>

    <div class="metric-card" id="card-ingresosPeriodo">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="metric-value" data-stat="ingresos_periodo">
            Bs {{ number_format($stats['ingresos_periodo'] ?? 0, 2) }}
        </div>
        <div class="metric-label">Ingresos del periodo</div>
    </div>

    <div class="metric-card" id="card-esterilizacionesTotal">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
            </svg>
        </div>
        <div class="metric-value" data-stat="esterilizaciones_total">
            {{ number_format($stats['esterilizaciones_total'] ?? 0) }}
        </div>
        <div class="metric-label">Esterilizaciones</div>
    </div>

    <div class="metric-card" id="card-radiologiasTotal">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="12" cy="12" r="3" />
                <path d="M12 3v3M12 18v3M3 12h3M18 12h3" />
            </svg>
        </div>
        <div class="metric-value" data-stat="radiologias_total">
            {{ number_format($stats['radiologias_total'] ?? 0) }}
        </div>
        <div class="metric-label">Radiografías tomadas</div>
    </div>

    <div class="metric-card" id="card-insumosBajoStock">
        <div class="metric-icon">
            <svg viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <div class="metric-value" data-stat="insumos_bajo_stock">
            {{ number_format($stats['insumos_bajo_stock'] ?? 0) }}
            <span class="metric-sub">/ {{ number_format($stats['insumos_total'] ?? 0) }}</span>
        </div>
        <div class="metric-label">Insumos bajo stock</div>
    </div>

</div>

{{-- ============ GRID: AFLUENCIA POR CLÍNICA + CAJAS POR PERIODO ============ --}}
<div class="grid-2">

    {{-- Afluencia por clínica --}}
    <div class="card">
        <div class="card-header">
            <h2>Afluencia por clínica <span class="card-sub">(top 10 · periodo activo)</span></h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Especialidad</th>
                        <th class="text-right">Pacientes</th>
                    </tr>
                </thead>
                <tbody id="tbodyAfluencia">
                    @forelse ($afluenciaClinicas ?? [] as $i => $a)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $a->especialidad }}</td>
                        <td class="text-right">
                            <span class="badge-soft">{{ number_format($a->total_pacientes) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">Sin datos de afluencia para el periodo activo.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cajas por periodo --}}
    <div class="card">
        <div class="card-header">
            <h2>Recaudación por periodo <span class="card-sub">(últimos 5)</span></h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th class="text-right">Recibos</th>
                        <th class="text-right">Cobrado</th>
                        <th class="text-right">Cancelado</th>
                    </tr>
                </thead>
                <tbody id="tbodyCajas">
                    @forelse ($cajasPeriodo ?? [] as $c)
                    <tr>
                        <td>{{ $c->periodo ?? '—' }}</td>
                        <td class="text-right">{{ number_format($c->total_recibos) }}</td>
                        <td class="text-right">Bs {{ number_format($c->total_cobrado, 2) }}</td>
                        <td class="text-right">
                            <strong>Bs {{ number_format($c->total_cancelado, 2) }}</strong>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">Sin movimientos de caja registrados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ============ TRATAMIENTOS POR ESTADO ============ --}}
<div class="card">
    <div class="card-header">
        <h2>Tratamientos por estado <span class="card-sub">(global)</span></h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">% del total</th>
                </tr>
            </thead>
            <tbody id="tbodyEstados">
                @php
                $totalTrat = collect($tratamientosPorEstado ?? [])->sum('total') ?: 1;
                @endphp
                @forelse ($tratamientosPorEstado ?? [] as $t)
                <tr>
                    <td>
                        <span class="badge-estado badge-{{ $t->estado }}">
                            {{ ucfirst(str_replace('_', ' ', $t->estado)) }}
                        </span>
                    </td>
                    <td class="text-right">{{ number_format($t->total) }}</td>
                    <td class="text-right">{{ number_format(($t->total / $totalTrat) * 100, 1) }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">
                        <div class="empty-state">Sin tratamientos registrados.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============ ACCESOS RÁPIDOS A REPORTES ============ --}}
<div class="card">
    <div class="card-header">
        <h2>Reportes para acreditación</h2>
    </div>
    <div class="reports-grid">
        <a href="{{ route('director.reportes.pacientes.periodo') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            <span>Pacientes por periodo</span>
        </a>
        <a href="{{ route('director.reportes.tratamientos.especialidad') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M12 6v4M14 8h-4M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
            </svg>
            <span>Tratamientos por especialidad</span>
        </a>
        <a href="{{ route('director.reportes.atenciones.docente') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
            </svg>
            <span>Atenciones por docente</span>
        </a>
        <a href="{{ route('director.reportes.atenciones.estudiante') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                <path d="M6 12v5c3 3 9 3 12 0v-5" />
            </svg>
            <span>Atenciones por estudiante</span>
        </a>
        <a href="{{ route('director.reportes.afluencia.clinicas') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
                <path d="M3 21h18" />
            </svg>
            <span>Afluencia por clínica</span>
        </a>
        <a href="{{ route('director.reportes.pacientes.edad') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
            <span>Pacientes por edad</span>
        </a>
        <a href="{{ route('director.reportes.pacientes.sexo') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v8M8 12h8" />
            </svg>
            <span>Pacientes por sexo</span>
        </a>
        <a href="{{ route('director.reportes.motivos.consulta') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <span>Motivos de consulta</span>
        </a>
        <a href="{{ route('director.reportes.cajas.periodo') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
            <span>Ingresos de cajas</span>
        </a>
        <a href="{{ route('director.reportes.insumos.utilizados') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span>Insumos utilizados</span>
        </a>
        <a href="{{ route('director.reportes.radiologia.afluencia') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <span>Afluencia de radiología</span>
        </a>
        <a href="{{ route('director.reportes.esterilizacion.afluencia') }}" class="report-tile">
            <svg viewBox="0 0 24 24">
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
            </svg>
            <span>Afluencia de esterilización</span>
        </a>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* ============ TARJETA DE BIENVENIDA ============ */
    .welcome-card {
        background: linear-gradient(135deg, #0f2b28 0%, #087f79 60%, #078fa0 100%);
        color: #fff;
        border-radius: var(--radius-lg);
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-md);
    }

    .welcome-eyebrow {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        opacity: 0.85;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .welcome-title {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .welcome-text {
        font-size: 14px;
        opacity: 0.95;
        max-width: 780px;
        line-height: 1.6;
    }

    .session-status {
        display: inline-block;
        margin-top: 14px;
        padding: 6px 14px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    /* ============ BARRA DE ACCIONES ============ */
    .dashboard-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 12px 18px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .toolbar-info {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .toolbar-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--text-muted);
        transition: background .3s;
    }

    .toolbar-dot.live {
        background: var(--success);
        box-shadow: 0 0 0 4px rgba(22, 163, 74, .15);
    }

    .toolbar-dot.syncing {
        background: var(--warning);
        animation: pulse 1s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .4;
        }
    }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .auto-refresh-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--text-muted);
        cursor: pointer;
    }

    .auto-refresh-toggle input {
        display: none;
    }

    .switch-track {
        width: 34px;
        height: 18px;
        background: var(--line-strong);
        border-radius: 999px;
        position: relative;
        transition: background .2s;
    }

    .switch-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 14px;
        height: 14px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
    }

    .auto-refresh-toggle input:checked+.switch-track {
        background: var(--teal);
    }

    .auto-refresh-toggle input:checked+.switch-track .switch-thumb {
        transform: translateX(16px);
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: var(--surface-soft);
        border: 1px solid var(--line);
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-icon:hover {
        background: var(--teal);
        color: #fff;
        border-color: var(--teal);
    }

    .btn-icon svg {
        width: 15px;
        height: 15px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .btn-icon.spinning svg {
        animation: spin .8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* ============ MÉTRICAS ============ */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .metric-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 18px 20px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        border-color: var(--teal);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .metric-card.updating {
        opacity: .6;
    }

    .metric-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--surface-soft);
        display: grid;
        place-items: center;
        margin-bottom: 12px;
    }

    .metric-icon svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: var(--teal);
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .metric-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.02em;
        line-height: 1.1;
    }

    .metric-sub {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-muted);
    }

    .metric-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        font-weight: 600;
        margin-top: 6px;
    }

    /* ============ GRID 2 COLUMNAS ============ */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 900px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }

    /* ============ CARDS ============ */
    .card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        padding: 22px 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .card-header {
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--teal);
    }

    .card-header h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
    }

    .card-sub {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-muted);
    }

    /* ============ TABLAS ============ */
    .table-wrap {
        overflow-x: auto;
        border: 1px solid var(--line);
        border-radius: var(--radius);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    thead th {
        background: var(--surface-soft);
        padding: 10px 14px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--line);
    }

    tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid var(--line);
        vertical-align: middle;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    tbody tr:hover {
        background: rgba(8, 127, 121, .03);
    }

    .text-right {
        text-align: right;
    }

    .empty-state {
        padding: 20px;
        text-align: center;
        color: var(--text-muted);
        font-style: italic;
        font-size: 13px;
    }

    /* ============ BADGES ============ */
    .badge-soft {
        display: inline-block;
        padding: 2px 10px;
        background: var(--surface-soft);
        border: 1px solid var(--line);
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        color: var(--teal);
    }

    .badge-estado {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .badge-pendiente {
        background: var(--warning-bg);
        color: #78350f;
    }

    .badge-en_proceso {
        background: var(--info-bg);
        color: #1e40af;
    }

    .badge-completado {
        background: var(--success-bg);
        color: #14532d;
    }

    .badge-cancelado {
        background: var(--danger-bg);
        color: #7f1d1d;
    }

    /* ============ TILES DE REPORTES ============ */
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .report-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 18px 12px;
        background: var(--surface-soft);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        text-decoration: none;
        color: var(--text);
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        transition: var(--transition);
    }

    .report-tile:hover {
        border-color: var(--teal);
        background: #fff;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: var(--teal);
    }

    .report-tile svg {
        width: 24px;
        height: 24px;
        fill: none;
        stroke: var(--teal);
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
</style>
@endpush

@push('scripts')
<script>
    /* ============================================================
       CONFIG
       ============================================================ */
    const URL_STATS = "{{ route('director.dashboard.stats') }}";
    const REFRESH_INTERVAL_MS = 60000; // 60s (director no necesita 30s)

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

        document.querySelectorAll('.metric-card').forEach(c => c.classList.add('updating'));

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(URL_STATS);
            if (!ok) throw new Error('Error al obtener estadísticas');

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
            pacientes_total: (v) => numberFormat(v),
            pacientes_periodo: (v) => numberFormat(v),
            clinicas_activas: (v) => `${numberFormat(v)} <span class="metric-sub">/ ${numberFormat(data.clinicas_total)}</span>`,
            docentes_activos: (v) => numberFormat(v),
            estudiantes_activos: (v) => numberFormat(v),
            historias_total: (v) => numberFormat(v),
            historias_pendientes: (v) => numberFormat(v),
            tratamientos_completados: (v) => `${numberFormat(v)} <span class="metric-sub">/ ${numberFormat(data.tratamientos_total)}</span>`,
            ingresos_periodo: (v) => `Bs ${numberFormat(v, 2)}`,
            esterilizaciones_total: (v) => numberFormat(v),
            radiologias_total: (v) => numberFormat(v),
            insumos_bajo_stock: (v) => `${numberFormat(v)} <span class="metric-sub">/ ${numberFormat(data.insumos_total)}</span>`,
        };

        Object.entries(map).forEach(([key, format]) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (!el || typeof data[key] === 'undefined') return;

            const nuevo = format(data[key]);
            if (el.innerHTML.trim() !== nuevo) {
                el.innerHTML = nuevo;
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

    setInterval(() => {
        const text = document.getElementById('toolbarText');
        if (text && !text.textContent.startsWith('Actualizando')) {
            text.textContent = `Última actualización: ${formatearHace(lastUpdateAt)}`;
        }
    }, 5000);

    /* ============================================================
       AUTO-REFRESH
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

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('toolbarDot')?.classList.add('live');
    });
</script>
@endpush