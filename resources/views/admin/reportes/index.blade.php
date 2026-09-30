@extends('layouts.app')

@section('title', 'Reportes')

@section('content')

{{-- ============================================================
     ENCABEZADO
     ============================================================ --}}
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot" id="reportesDot"></span>
        <span id="reportesStatus">Listo para generar reportes</span>
    </div>
    <div class="toolbar-actions">
        <button type="button" class="btn-icon" id="btnExportarTodo" title="Exportar todo a CSV">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            <span>Exportar</span>
        </button>
        <button type="button" class="btn-icon" id="btnRefrescarTodo" title="Refrescar todos los reportes">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <polyline points="23 4 23 10 17 10" />
                <polyline points="1 20 1 14 7 14" />
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
            </svg>
            <span>Refrescar</span>
        </button>
    </div>
</div>

{{-- ============================================================
     CARD DE BIENVENIDA / RESUMEN
     ============================================================ --}}
<div class="welcome-card">
    <div class="welcome-header">
        <div>
            <p class="welcome-eyebrow">Análisis y Estadísticas</p>
            <h1 class="welcome-title">Reportes de la <strong>Clínica Odontológica</strong></h1>
            <p class="welcome-text">
                Consulta la distribución de pacientes por grupo etario, la afluencia por clínica y
                los ingresos registrados por periodo. Puedes filtrar por periodo y exportar los
                resultados a CSV.
            </p>
        </div>
    </div>
</div>

{{-- ============================================================
     TARJETAS RESUMEN (KPIs)
     ============================================================ --}}
<div class="stats-grid" id="reportesKpis">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiPacientes">—</span>
            <span class="stat-label">Pacientes</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon fuchsia">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 21h18" />
                <path d="M5 21V7l8-4v18" />
                <path d="M19 21V11l-6-4" />
                <line x1="9" y1="9" x2="9.01" y2="9" />
                <line x1="9" y1="13" x2="9.01" y2="13" />
                <line x1="9" y1="17" x2="9.01" y2="17" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiClinicas">—</span>
            <span class="stat-label">Clínicas activas</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiIngresos">—</span>
            <span class="stat-label">Total cobrado (Bs)</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="9" y1="15" x2="15" y2="15" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiRecibos">—</span>
            <span class="stat-label">Recibos emitidos</span>
        </div>
    </div>
</div>

{{-- ============================================================
     FILTROS
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="filtrosStatus">
                <span class="dot"></span>
                <span>Actualizando…</span>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroPeriodo">Periodo</label>
                <select id="filtroPeriodo" class="form-control">
                    <option value="">Todos los periodos</option>
                    {{-- Se rellena vía JS con los periodos únicos devueltos por los reportes --}}
                </select>
            </div>
            <div class="form-group">
                <label for="filtroTop">Top N clínicas</label>
                <select id="filtroTop" class="form-control">
                    <option value="5">Top 5</option>
                    <option value="10" selected>Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="0">Todas</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filtroMetrica">Métrica afluencia</label>
                <select id="filtroMetrica" class="form-control">
                    <option value="total_pacientes">Pacientes únicos</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-primary" id="btnAplicarFiltros">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                    </svg>
                    Aplicar
                </button>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     GRÁFICO 1: PACIENTES POR GRUPO ETARIO
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Pacientes por grupo etario</h2>
        <div class="card-header-actions">
            <span class="card-meta">
                Total: <strong id="totalGrupoEtario">0</strong> pacientes
            </span>
            <button type="button" class="btn-icon" id="btnExportarEtario" title="Exportar CSV">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px;">
            <canvas id="chartGrupoEtario"></canvas>
        </div>
        <div id="tablaGrupoEtarioWrap" style="margin-top: 20px;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Grupo etario</th>
                            <th>Total</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyGrupoEtario">
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--muted);">
                                Cargando…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     GRÁFICO 2: AFLUENCIA POR CLÍNICA
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Afluencia por clínica y periodo</h2>
        <div class="card-header-actions">
            <span class="card-meta">
                Registros: <strong id="totalAfluencia">0</strong>
            </span>
            <button type="button" class="btn-icon" id="btnExportarAfluencia" title="Exportar CSV">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 360px;">
            <canvas id="chartAfluencia"></canvas>
        </div>
        <div style="margin-top: 20px;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Clínica</th>
                            <th>Total pacientes</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAfluencia">
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--muted);">
                                Cargando…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     GRÁFICO 3: INGRESOS POR PERIODO
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Ingresos por periodo</h2>
        <div class="card-header-actions">
            <span class="card-meta">
                Periodos: <strong id="totalPeriodos">0</strong>
            </span>
            <button type="button" class="btn-icon" id="btnExportarIngresos" title="Exportar CSV">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 340px;">
            <canvas id="chartIngresos"></canvas>
        </div>
        <div style="margin-top: 20px;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Recibos</th>
                            <th>Cobrado (Bs)</th>
                            <th>Cancelado (Bs)</th>
                            <th>Saldo (Bs)</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyIngresos">
                        <tr>
                            <td colspan="5" class="text-center" style="color: var(--muted);">
                                Cargando…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     ESTADO VACÍO GLOBAL (por si falla todo)
     ============================================================ --}}
<div class="card" id="estadoVacioReportes" style="display: none;">
    <div class="card-body">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18" />
                <path d="M18 17V9" />
                <path d="M13 17V5" />
                <path d="M8 17v-3" />
            </svg>
            <p>No hay datos disponibles para los filtros seleccionados.</p>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Ajustes finos solo para la vista de reportes */
    .reporte-canvas-wrap {
        position: relative;
    }

    .reporte-tabla-scroll {
        max-height: 420px;
        overflow-y: auto;
    }

    .reporte-tabla-scroll table {
        min-width: 520px;
    }

    .reporte-badge-total {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 10px;
        border-radius: var(--radius-full);
        background: rgba(6, 182, 212, 0.10);
        color: var(--teal-dark);
        font: 600 11.5px -apple-system, sans-serif;
    }
</style>
@endpush

@push('scripts')
{{-- Chart.js vía CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    (() => {
        'use strict';

        // ------------------------------------------------------------
        // Configuración de rutas
        // ------------------------------------------------------------
        const RUTAS = {
            grupoEtario: "{{ route('admin.reportes.pacientes.edad') }}",
            afluencia: "{{ route('admin.reportes.afluencia') }}",
            ingresos: "{{ route('admin.reportes.ingresos') }}",
        };

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // ------------------------------------------------------------
        // Estado local
        // ------------------------------------------------------------
        const estado = {
            grupoEtario: [],
            afluencia: [],
            ingresos: [],
        };

        const charts = {
            grupoEtario: null,
            afluencia: null,
            ingresos: null,
        };

        // ------------------------------------------------------------
        // Helpers
        // ------------------------------------------------------------
        const $ = (sel, ctx = document) => ctx.querySelector(sel);
        const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

        const fmt = new Intl.NumberFormat('es-BO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const fmtInt = new Intl.NumberFormat('es-BO');

        function setDotLive() {
            const dot = $('#reportesDot');
            const status = $('#reportesStatus');
            if (dot) {
                dot.classList.remove('syncing');
                dot.classList.add('live');
            }
            if (status) status.textContent = 'Datos actualizados';
        }

        function setDotSyncing() {
            const dot = $('#reportesDot');
            const status = $('#reportesStatus');
            if (dot) {
                dot.classList.remove('live');
                dot.classList.add('syncing');
            }
            if (status) status.textContent = 'Actualizando…';
        }

        function mostrarFiltrosStatus(show) {
            const el = $('#filtrosStatus');
            if (!el) return;
            el.classList.toggle('visible', !!show);
        }

        function toast(mensaje, tipo = 'info') {
            const el = document.createElement('div');
            el.className = `toast toast-${tipo}`;
            el.textContent = mensaje;
            document.body.appendChild(el);
            requestAnimationFrame(() => el.classList.add('show'));
            setTimeout(() => {
                el.classList.remove('show');
                setTimeout(() => el.remove(), 300);
            }, 3200);
        }

        async function fetchJson(url) {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        }

        // ------------------------------------------------------------
        // CSV
        // ------------------------------------------------------------
        function descargarCSV(filename, headers, rows) {
            const escape = (v) => {
                const s = v === null || v === undefined ? '' : String(v);
                return /[",\n;]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
            };
            const contenido = [
                headers.map(escape).join(','),
                ...rows.map(r => r.map(escape).join(',')),
            ].join('\n');

            const blob = new Blob(['\uFEFF' + contenido], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            setTimeout(() => {
                URL.revokeObjectURL(link.href);
                link.remove();
            }, 0);
        }

        // ------------------------------------------------------------
        // Colores adaptados a dark/light
        // ------------------------------------------------------------
        function getChartColors() {
            const dark = document.documentElement.dataset.theme === 'dark';
            return {
                text: dark ? '#e6f0ed' : '#1b2e2b',
                muted: dark ? '#a6bbb5' : '#60736f',
                grid: dark ? 'rgba(166,187,181,0.12)' : 'rgba(96,115,111,0.12)',
                teal: dark ? '#55c4b2' : '#087f79',
                cyan: dark ? '#42b7c2' : '#078fa0',
                fuchsia: dark ? '#c47bb9' : '#b3449e',
                amber: dark ? '#f4c66b' : '#d97706',
            };
        }

        // ------------------------------------------------------------
        // Chart: Grupo etario
        // ------------------------------------------------------------
        function renderGrupoEtario(data) {
            const colores = getChartColors();
            const labels = data.map(d => d.grupo_etario);
            const valores = data.map(d => Number(d.total));

            const total = valores.reduce((a, b) => a + b, 0);
            $('#kpiPacientes').textContent = fmtInt.format(total);
            $('#totalGrupoEtario').textContent = fmtInt.format(total);

            const palette = [
                colores.cyan,
                colores.teal,
                colores.fuchsia,
                colores.amber,
                colores.cyan,
                colores.teal,
            ];

            const ctx = $('#chartGrupoEtario');
            if (charts.grupoEtario) charts.grupoEtario.destroy();

            charts.grupoEtario = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Pacientes',
                        data: valores,
                        backgroundColor: labels.map((_, i) => palette[i % palette.length]),
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 56,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: colores.text,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (item) => {
                                    const pct = total ? ((item.parsed.y / total) * 100).toFixed(1) : '0.0';
                                    return ` ${fmtInt.format(item.parsed.y)} pacientes (${pct}%)`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: colores.muted,
                                font: {
                                    size: 12
                                }
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: colores.grid
                            },
                            ticks: {
                                color: colores.muted,
                                precision: 0,
                                font: {
                                    size: 12
                                },
                            },
                        },
                    },
                },
            });

            const tbody = $('#tbodyGrupoEtario');
            if (!data.length) {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center" style="color: var(--muted);">Sin datos</td></tr>`;
                return;
            }
            tbody.innerHTML = data.map(d => {
                const pct = total ? ((Number(d.total) / total) * 100).toFixed(1) : '0.0';
                return `
                <tr>
                    <td><span class="badge badge-info">${d.grupo_etario}</span></td>
                    <td><strong>${fmtInt.format(d.total)}</strong></td>
                    <td><span class="reporte-badge-total">${pct}%</span></td>
                </tr>`;
            }).join('');
        }

        // ------------------------------------------------------------
        // Chart: Afluencia por clínica
        // ------------------------------------------------------------
        function renderAfluencia(data) {
            const colores = getChartColors();
            const top = parseInt($('#filtroTop').value, 10) || 0;
            const recortado = top > 0 ? data.slice(0, top) : data;

            $('#totalAfluencia').textContent = fmtInt.format(data.length);
            $('#kpiClinicas').textContent = fmtInt.format(
                new Set(data.map(d => d.clinica_id)).size
            );

            const labels = recortado.map(d => `${d.especialidad} · ${d.periodo}`);
            const valores = recortado.map(d => Number(d.total_pacientes));

            const ctx = $('#chartAfluencia');
            if (charts.afluencia) charts.afluencia.destroy();

            charts.afluencia = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Pacientes únicos',
                        data: valores,
                        backgroundColor: colores.cyan,
                        hoverBackgroundColor: colores.teal,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: colores.text,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 10,
                            cornerRadius: 8,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: colores.grid
                            },
                            ticks: {
                                color: colores.muted,
                                precision: 0,
                                font: {
                                    size: 12
                                }
                            },
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: colores.muted,
                                font: {
                                    size: 11.5
                                },
                                autoSkip: false,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 42 ? label.slice(0, 40) + '…' : label;
                                },
                            },
                        },
                    },
                },
            });

            const tbody = $('#tbodyAfluencia');
            if (!data.length) {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center" style="color: var(--muted);">Sin datos</td></tr>`;
                return;
            }
            tbody.innerHTML = data.map(d => `
            <tr>
                <td><span class="badge badge-muted">${d.periodo}</span></td>
                <td>${d.especialidad}</td>
                <td><strong>${fmtInt.format(d.total_pacientes)}</strong></td>
            </tr>
        `).join('');
        }

        // ------------------------------------------------------------
        // Chart: Ingresos por periodo
        // ------------------------------------------------------------
        function renderIngresos(data) {
            const colores = getChartColors();

            const totalCobrado = data.reduce((a, d) => a + Number(d.total_cobrado || 0), 0);
            const totalCancelado = data.reduce((a, d) => a + Number(d.total_cancelado || 0), 0);
            const totalRecibos = data.reduce((a, d) => a + Number(d.total_recibos || 0), 0);

            $('#kpiIngresos').textContent = fmt.format(totalCobrado);
            $('#kpiRecibos').textContent = fmtInt.format(totalRecibos);
            $('#totalPeriodos').textContent = fmtInt.format(data.length);

            const labels = data.map(d => d.periodo || `Periodo ${d.periodo_id ?? '—'}`);
            const cobrado = data.map(d => Number(d.total_cobrado || 0));
            const cancelado = data.map(d => Number(d.total_cancelado || 0));

            const ctx = $('#chartIngresos');
            if (charts.ingresos) charts.ingresos.destroy();

            charts.ingresos = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: 'Cobrado',
                            data: cobrado,
                            backgroundColor: colores.teal,
                            borderRadius: 6,
                            maxBarThickness: 36,
                        },
                        {
                            label: 'Cancelado',
                            data: cancelado,
                            backgroundColor: colores.fuchsia,
                            borderRadius: 6,
                            maxBarThickness: 36,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: colores.text,
                                boxWidth: 12,
                                boxHeight: 12,
                                padding: 16,
                                font: {
                                    size: 12
                                },
                            },
                        },
                        tooltip: {
                            backgroundColor: colores.text,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (item) => ` ${item.dataset.label}: Bs ${fmt.format(item.parsed.y)}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: colores.muted,
                                font: {
                                    size: 12
                                }
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: colores.grid
                            },
                            ticks: {
                                color: colores.muted,
                                font: {
                                    size: 12
                                },
                                callback: (v) => 'Bs ' + fmtInt.format(v),
                            },
                        },
                    },
                },
            });

            const tbody = $('#tbodyIngresos');
            if (!data.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="color: var(--muted);">Sin datos</td></tr>`;
                return;
            }
            tbody.innerHTML = data.map(d => {
                const s = Math.max(0, (d.total_cobrado || 0) - (d.total_cancelado || 0));
                return `
                <tr>
                    <td><span class="badge badge-info">${d.periodo ?? '—'}</span></td>
                    <td>${fmtInt.format(d.total_recibos || 0)}</td>
                    <td><strong>Bs ${fmt.format(d.total_cobrado || 0)}</strong></td>
                    <td>Bs ${fmt.format(d.total_cancelado || 0)}</td>
                    <td>
                        <span class="badge ${s > 0 ? 'badge-success' : 'badge-muted'}">
                            Bs ${fmt.format(s)}
                        </span>
                    </td>
                </tr>`;
            }).join('');
        }

        // ------------------------------------------------------------
        // Carga de datos
        // ------------------------------------------------------------
        function buildQuery() {
            const periodo = $('#filtroPeriodo')?.value || '';
            return periodo ? `?periodo_id=${encodeURIComponent(periodo)}` : '';
        }

        async function cargarGrupoEtario() {
            try {
                const data = await fetchJson(RUTAS.grupoEtario);
                estado.grupoEtario = Array.isArray(data) ? data : [];
                renderGrupoEtario(estado.grupoEtario);
            } catch (e) {
                console.error('Error grupo etario', e);
                toast('No se pudo cargar el reporte de grupos etarios', 'error');
            }
        }

        async function cargarAfluencia() {
            try {
                const data = await fetchJson(RUTAS.afluencia + buildQuery());
                estado.afluencia = Array.isArray(data) ? data : [];
                renderAfluencia(estado.afluencia);
            } catch (e) {
                console.error('Error afluencia', e);
                toast('No se pudo cargar el reporte de afluencia', 'error');
            }
        }

        async function cargarIngresos() {
            try {
                const data = await fetchJson(RUTAS.ingresos + buildQuery());
                estado.ingresos = Array.isArray(data) ? data : [];
                renderIngresos(estado.ingresos);
            } catch (e) {
                console.error('Error ingresos', e);
                toast('No se pudo cargar el reporte de ingresos', 'error');
            }
        }

        async function cargarTodo() {
            setDotSyncing();
            mostrarFiltrosStatus(true);
            await Promise.all([cargarGrupoEtario(), cargarAfluencia(), cargarIngresos()]);
            mostrarFiltrosStatus(false);
            setDotLive();
        }

        // ------------------------------------------------------------
        // Select de periodos dinámico
        // ------------------------------------------------------------
        function poblarPeriodos() {
            const sel = $('#filtroPeriodo');
            if (!sel) return;

            const mapa = new Map();
            [...estado.afluencia, ...estado.ingresos].forEach(item => {
                if (item.periodo_id != null) {
                    mapa.set(String(item.periodo_id), item.periodo || `Periodo ${item.periodo_id}`);
                }
            });

            const actual = sel.value;
            sel.innerHTML = '<option value="">Todos los periodos</option>' +
                Array.from(mapa.entries())
                .sort((a, b) => Number(b[0]) - Number(a[0]))
                .map(([id, nombre]) => `<option value="${id}">${nombre}</option>`)
                .join('');

            if (mapa.has(actual)) sel.value = actual;
        }

        // ------------------------------------------------------------
        // Exportar
        // ------------------------------------------------------------
        function exportarGrupoEtario() {
            descargarCSV(
                'reporte_pacientes_grupo_etario.csv',
                ['Grupo etario', 'Total'],
                estado.grupoEtario.map(d => [d.grupo_etario, d.total])
            );
        }

        function exportarAfluencia() {
            descargarCSV(
                'reporte_afluencia_clinicas.csv',
                ['Periodo', 'Clínica', 'Total pacientes'],
                estado.afluencia.map(d => [d.periodo, d.especialidad, d.total_pacientes])
            );
        }

        function exportarIngresos() {
            descargarCSV(
                'reporte_ingresos_periodo.csv',
                ['Periodo', 'Recibos', 'Cobrado', 'Cancelado', 'Saldo'],
                estado.ingresos.map(d => [
                    d.periodo,
                    d.total_recibos,
                    d.total_cobrado,
                    d.total_cancelado,
                    Math.max(0, (d.total_cobrado || 0) - (d.total_cancelado || 0)),
                ])
            );
        }

        function exportarTodo() {
            const lineas = [];
            lineas.push('# Reporte de pacientes por grupo etario');
            lineas.push('Grupo etario,Total');
            estado.grupoEtario.forEach(d => lineas.push(`${d.grupo_etario},${d.total}`));
            lineas.push('');
            lineas.push('# Reporte de afluencia por clínica');
            lineas.push('Periodo,Clínica,Total pacientes');
            estado.afluencia.forEach(d => lineas.push(`${d.periodo},${d.especialidad},${d.total_pacientes}`));
            lineas.push('');
            lineas.push('# Reporte de ingresos por periodo');
            lineas.push('Periodo,Recibos,Cobrado,Cancelado,Saldo');
            estado.ingresos.forEach(d => lineas.push(
                `${d.periodo},${d.total_recibos},${d.total_cobrado},${d.total_cancelado},` +
                Math.max(0, (d.total_cobrado || 0) - (d.total_cancelado || 0))
            ));

            const blob = new Blob(['\uFEFF' + lineas.join('\n')], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reportes_clinica.csv';
            document.body.appendChild(link);
            link.click();
            setTimeout(() => {
                URL.revokeObjectURL(link.href);
                link.remove();
            }, 0);
        }

        // ------------------------------------------------------------
        // Eventos
        // ------------------------------------------------------------
        function bindEventos() {
            $('#btnRefrescarTodo')?.addEventListener('click', async (e) => {
                const btn = e.currentTarget;
                btn.classList.add('spinning');
                try {
                    await cargarTodo();
                    poblarPeriodos();
                    toast('Reportes actualizados', 'success');
                } finally {
                    btn.classList.remove('spinning');
                }
            });

            $('#btnAplicarFiltros')?.addEventListener('click', async () => {
                await cargarTodo();
                poblarPeriodos();
                toast('Filtros aplicados', 'success');
            });

            $('#btnLimpiarFiltros')?.addEventListener('click', async () => {
                const sel = $('#filtroPeriodo');
                const top = $('#filtroTop');
                if (sel) sel.value = '';
                if (top) top.value = '10';
                await cargarTodo();
                toast('Filtros limpiados', 'info');
            });

            $('#filtroTop')?.addEventListener('change', () => {
                if (estado.afluencia.length) renderAfluencia(estado.afluencia);
            });

            $('#btnExportarEtario')?.addEventListener('click', exportarGrupoEtario);
            $('#btnExportarAfluencia')?.addEventListener('click', exportarAfluencia);
            $('#btnExportarIngresos')?.addEventListener('click', exportarIngresos);
            $('#btnExportarTodo')?.addEventListener('click', exportarTodo);

            // Re-render al cambiar de tema
            const obs = new MutationObserver(() => {
                if (estado.grupoEtario.length) renderGrupoEtario(estado.grupoEtario);
                if (estado.afluencia.length) renderAfluencia(estado.afluencia);
                if (estado.ingresos.length) renderIngresos(estado.ingresos);
            });
            obs.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme']
            });
        }

        // ------------------------------------------------------------
        // Init
        // ------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', async () => {
            bindEventos();
            await cargarTodo();
            poblarPeriodos();
        });
    })();
</script>
@endpush