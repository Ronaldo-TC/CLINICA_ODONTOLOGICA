@extends('layouts.app')

@php
    /** @var \App\Models\Usuario|null $user */
    /** @var array $tarjetas */
    /** @var array $pacientesPorMes */
    /** @var array $topEspecialidades */
    /** @var \Illuminate\Support\Collection $actividadReciente */
    /** @var \Illuminate\Support\Collection $proximosVencimientos */

    $rolPrincipal = $user?->getRoleNames()->first() ?? 'usuario';
    $etiquetasRol = [
        'admin'             => 'Administrador del sistema',
        'subadmin'          => 'Subadministrador académico',
        'director'          => 'Dirección de carrera',
        'jefatura-clinicas' => 'Jefatura de clínicas',
        'jefe-catedra'      => 'Jefatura de cátedra',
        'docente'           => 'Docente supervisor',
        'estudiante'        => 'Estudiante de clínica',
        'interno'           => 'Interno de clínica',
        'admision'          => 'Admisión',
        'cajas'             => 'Cajas',
        'insumos'           => 'Encargado de insumos',
        'esterilizacion'    => 'Esterilización',
        'radiologia'        => 'Radiología',
        'kardex'            => 'Kardex',
        'investigacion'     => 'Investigación',
    ];
    $rolLegible = $etiquetasRol[$rolPrincipal] ?? ucfirst((string) $rolPrincipal);

    $iconos = [
        'users'       => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
        'key'         => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />',
        'hospital'    => '<path d="M12 6v4" /><path d="M14 8h-4" /><path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" /><path d="M3 21h18" /><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4" />',
        'folder'      => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />',
        'activity'    => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />',
        'credit-card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2" /><line x1="1" y1="10" x2="23" y2="10" />',
        'receipt'     => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z" /><line x1="8" y1="8" x2="16" y2="8" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="8" y1="16" x2="13" y2="16" />',
        'file-text'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" />',
        'clipboard'   => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" /><rect x="8" y="2" width="8" height="4" rx="1" ry="1" />',
        'package'     => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21" /><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" /><polyline points="3.27 6.96 12 12.01 20.73 6.96" /><line x1="12" y1="22.08" x2="12" y2="12" />',
        'layers'      => '<polygon points="12 2 2 7 12 12 22 7 12 2" /><polyline points="2 17 12 22 22 17" /><polyline points="2 12 12 17 22 12" />',
        'image'       => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" />',
        'database'    => '<ellipse cx="12" cy="5" rx="9" ry="3" /><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" /><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />',
    ];
@endphp

@section('title', 'Panel')
@section('page-title', 'Panel de Control')
@section('page-subtitle', 'Bienvenido, ' . ($user?->nombre_completo ?? 'usuario') . ' · ' . $rolLegible)

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">
            {{ count($tarjetas) }} indicador(es) para tu rol · {{ now()->translatedFormat('d \d\e F \d\e Y') }}
        </span>
    </div>
    <div class="toolbar-actions">
        <button type="button" class="btn btn-secondary btn-sm" id="btnRefrescarPanel" title="Volver a consultar los datos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10" />
                <polyline points="1 20 1 14 7 14" />
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
            </svg>
            Actualizar
        </button>
    </div>
</div>

{{-- ================= TARJETAS DE MÉTRICAS ================= --}}
@if (count($tarjetas) > 0)
<div class="stats-grid" id="panelMetricas">
    @foreach ($tarjetas as $t)
    <div class="stat-card">
        <div class="stat-icon {{ $t['tone'] }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                {!! $iconos[$t['icon']] ?? $iconos['activity'] !!}
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-value">{{ $t['value'] }}</div>
            <div class="stat-label">
                @if ($t['url'])
                <a href="{{ $t['url'] }}" style="color:inherit; text-decoration:none;">{{ $t['label'] }}</a>
                @else
                {{ $t['label'] }}
                @endif
            </div>
            @if ($t['hint'])
            <div class="stat-label" style="text-transform:none; letter-spacing:0; opacity:.8;">{{ $t['hint'] }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ================= GRÁFICOS ================= --}}
<div class="metrics-grid">
    <div class="card" style="grid-column: span 2; min-width:0;">
        <div class="card-header">
            <h2>Pacientes por mes</h2>
            <span class="card-meta">Últimos 12 meses · <strong id="totalPacientesAnio">{{ array_sum($pacientesPorMes['valores']) }}</strong> admisiones</span>
        </div>
        <div class="card-body">
            <canvas id="graficoPacientesMes" height="110" aria-label="Admisiones por mes" role="img"></canvas>
            <div class="table-wrap" id="tablaPacientesMes" hidden>
                <table>
                    <thead>
                        <tr><th>Mes</th><th>Admisiones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($pacientesPorMes['labels'] as $i => $mes)
                        <tr>
                            <td>{{ $mes }}</td>
                            <td>{{ $pacientesPorMes['valores'][$i] ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Top especialidades</h2>
            <span class="card-meta">Por admisiones</span>
        </div>
        <div class="card-body">
            <canvas id="graficoEspecialidades" height="200" aria-label="Admisiones por especialidad" role="img"></canvas>
            <div class="table-wrap" id="tablaEspecialidades" hidden>
                <table>
                    <thead>
                        <tr><th>Especialidad</th><th>Admisiones</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($topEspecialidades['labels'] as $i => $nombre)
                        <tr>
                            <td>{{ $nombre }}</td>
                            <td>{{ $topEspecialidades['valores'][$i] ?? 0 }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2"><div class="empty-state"><p>Sin datos todavía</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= ACTIVIDAD Y VENCIMIENTOS ================= --}}
<div class="metrics-grid">
    <div class="card" style="min-width:0;">
        <div class="card-header">
            <h2>Actividad reciente</h2>
            @if (Route::has('bitacoras.index'))
            <a href="{{ route('bitacoras.index') }}" class="card-meta">Ver bitácora</a>
            @endif
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividadReciente as $a)
                    <tr>
                        <td><small>{{ \Illuminate\Support\Carbon::parse($a->fecha)->format('d/m/Y H:i') }}</small></td>
                        <td><small>{{ $a->nombre_completo ?? 'Sistema' }}</small></td>
                        <td><span class="badge badge-info">{{ $a->tipo }}</span></td>
                        <td><small>{{ \Illuminate\Support\Str::limit((string) $a->datos, 70) }}</small></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state"><p>Sin movimientos registrados</p></div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="min-width:0;">
        <div class="card-header">
            <h2>Próximos vencimientos</h2>
            <span class="card-meta">Periodos · 30 días</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Vence</th>
                        <th>Días</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proximosVencimientos as $p)
                    <tr>
                        <td><small>{{ $p->nombre }}</small></td>
                        <td><small>{{ $p->fecha_fin?->format('d/m/Y') }}</small></td>
                        <td>
                            <span class="badge {{ $p->fecha_fin && $p->fecha_fin->diffInDays(today()) <= 7 ? 'badge-warning' : 'badge-muted' }}">
                                {{ $p->fecha_fin ? (int) $p->fecha_fin->diffInDays(today()) : '—' }} d
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state"><p>Sin vencimientos próximos</p></div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (() => {
        'use strict';

        const DATOS = {
            porMes: @json($pacientesPorMes),
            especialidades: @json($topEspecialidades),
            statsUrl: @json(route('dashboard.stats')),
        };

        /* Si Chart.js no cargó (sin red), se muestran las tablas de respaldo */
        const hayChart = () => typeof window.Chart !== 'undefined';

        function respaldo(idTabla) {
            document.getElementById(idTabla)?.removeAttribute('hidden');
        }

        function pintar() {
            if (!hayChart()) {
                respaldo('tablaPacientesMes');
                respaldo('tablaEspecialidades');
                return;
            }

            const estilo = getComputedStyle(document.documentElement);
            const teal = estilo.getPropertyValue('--teal').trim() || '#087f79';
            const cyan = estilo.getPropertyValue('--cyan').trim() || '#06b6d4';
            const fuchsia = estilo.getPropertyValue('--fuchsia').trim() || '#d946ef';
            const muted = estilo.getPropertyValue('--muted').trim() || '#718087';
            const line = estilo.getPropertyValue('--line').trim() || '#dce6e2';

            const eje = {
                ticks: { color: muted, font: { size: 11 } },
                grid: { color: line, drawBorder: false },
            };

            const lienzoMes = document.getElementById('graficoPacientesMes');
            if (lienzoMes) {
                new Chart(lienzoMes, {
                    type: 'line',
                    data: {
                        labels: DATOS.porMes.labels,
                        datasets: [{
                            label: 'Admisiones',
                            data: DATOS.porMes.valores,
                            borderColor: teal,
                            backgroundColor: 'rgba(8, 127, 121, 0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: teal,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ...eje }, x: eje },
                    },
                });
            }

            const lienzoEsp = document.getElementById('graficoEspecialidades');
            if (lienzoEsp && DATOS.especialidades.labels.length > 0) {
                new Chart(lienzoEsp, {
                    type: 'bar',
                    data: {
                        labels: DATOS.especialidades.labels,
                        datasets: [{
                            label: 'Admisiones',
                            data: DATOS.especialidades.valores,
                            backgroundColor: [teal, cyan, fuchsia, '#f59e0b', '#64748b'],
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ...eje }, y: eje },
                    },
                });
            } else if (lienzoEsp) {
                respaldo('tablaEspecialidades');
            }
        }

        /* Refresco sin recargar */
        document.getElementById('btnRefrescarPanel')?.addEventListener('click', async (e) => {
            const boton = e.currentTarget;
            boton.disabled = true;
            boton.classList.add('spinning');

            try {
                const res = await fetch(DATOS.statsUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error();
                const data = await res.json();

                DATOS.porMes = data.por_mes;
                DATOS.especialidades = data.especialidades;

                const total = document.getElementById('totalPacientesAnio');
                if (total) {
                    total.textContent = (data.por_mes.valores || []).reduce((a, b) => a + Number(b), 0);
                }

                // Redibujar: se destruyen los gráficos previos
                Object.values(Chart.instances || {}).forEach((c) => c.destroy());
                pintar();
                window.mostrarToast?.('success', 'Panel actualizado');
            } catch (err) {
                window.mostrarToast?.('error', 'No se pudo actualizar el panel');
            } finally {
                boton.disabled = false;
                boton.classList.remove('spinning');
            }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', pintar);
        } else {
            pintar();
        }
    })();
</script>
@endpush
