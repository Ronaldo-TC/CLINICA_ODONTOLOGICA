@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Estadísticas y gráficos para la Dirección')

@section('content')

{{-- ============================================================
     BARRA DE ACCIONES
     ============================================================ --}}
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot" id="reportesDot"></span>
        <span id="reportesStatus">Listo para generar reportes</span>
    </div>
    <div class="toolbar-actions">
        <a class="btn btn-secondary btn-sm" id="btnExportarCsv"
            data-base="{{ route('reportes.exportar.csv') }}"
            href="{{ route('reportes.exportar.csv', request()->query()) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Exportar CSV
        </a>

        <a class="btn btn-secondary btn-sm" id="btnImprimible"
            data-base="{{ route('reportes.exportar.pdf') }}"
            href="{{ route('reportes.exportar.pdf', request()->query()) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9" />
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" />
            </svg>
            Generar PDF
        </a>

        <button type="button" class="btn btn-primary btn-sm" id="btnRefrescarReportes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="23 4 23 10 17 10" />
                <polyline points="1 20 1 14 7 14" />
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
            </svg>
            Refrescar
        </button>
    </div>
</div>

{{-- ============================================================
     FILTROS GLOBALES (se aplican a TODOS los reportes)
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="reportesFiltrosStatus">
                <span class="dot"></span>
                <span>Actualizando…</span>
            </span>
        </div>
    </div>

    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroDesde">Desde</label>
                <input type="date" id="filtroDesde" class="form-control" value="{{ $filtros['desde'] }}">
            </div>

            <div class="form-group">
                <label for="filtroHasta">Hasta</label>
                <input type="date" id="filtroHasta" class="form-control" value="{{ $filtros['hasta'] }}">
            </div>

            <div class="form-group">
                <label for="filtroPeriodo">Periodo</label>
                <select id="filtroPeriodo" class="form-control">
                    <option value="">Todos los periodos</option>
                    @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}" @selected((string) $filtros['periodo_id'] === (string) $periodo->id)>
                        {{ $periodo->nombre }} · {{ $periodo->gestion }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroClinica">Clínica</label>
                <select id="filtroClinica" class="form-control">
                    <option value="">Todas las clínicas</option>
                    @foreach ($clinicas as $clinica)
                    <option value="{{ $clinica->id }}" @selected((string) $filtros['clinica_id'] === (string) $clinica->id)>
                        {{ $clinica->especialidad ?: $clinica->modulo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" id="btnAplicarFiltros">Aplicar</button>
                    <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">Limpiar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     INDICADORES RESUMEN (se alimentan de los propios endpoints)
     ============================================================ --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 21h18" />
                <path d="M5 21V7l8-4v18" />
                <path d="M19 21V11l-6-4" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiAdmisiones">—</span>
            <span class="stat-label">Admisiones</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon fuchsia">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiTratamientos">—</span>
            <span class="stat-label">Tratamientos realizados</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiRadiografias">—</span>
            <span class="stat-label">Radiografías</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="kpiIngresos">—</span>
            <span class="stat-label">Cancelado (Bs)</span>
        </div>
    </div>
</div>

{{-- ============================================================
     TARJETAS DE GRÁFICOS (13 reportes)
     ============================================================ --}}
@include('reportes.partials.graficos')

@endsection

@push('scripts')
{{-- Chart.js por CDN. Si no hay red, cada tarjeta muestra igualmente su tabla. --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    (() => {
        'use strict';

        /* ============================================================
           Configuración
           ============================================================ */
        const TIPOS = {
            'barra': 'bar',
            'barra-horizontal': 'bar',
            'torta': 'pie',
            'dona': 'doughnut',
            'linea': 'line'
        };

        const PALETA = [
            '#087f79', '#078fa0', '#b3449e', '#d97706', '#16a34a',
            '#0284c7', '#dc2626', '#7c3aed', '#0d9488', '#e11d48'
        ];

        const CHART_DISPONIBLE = typeof window.Chart !== 'undefined';

        const fmtInt = new Intl.NumberFormat('es-BO');
        const fmtDec = new Intl.NumberFormat('es-BO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        const $ = (sel, ctx = document) => ctx.querySelector(sel);
        const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

        const tarjetas = $$('[data-reporte]');
        const graficos = new Map();

        const filtros = {
            desde: $('#filtroDesde'),
            hasta: $('#filtroHasta'),
            periodo: $('#filtroPeriodo'),
            clinica: $('#filtroClinica')
        };

        /* ============================================================
           Utilidades
           ============================================================ */
        function formatear(valor) {
            const numero = Number(valor) || 0;
            return Number.isInteger(numero) ? fmtInt.format(numero) : fmtDec.format(numero);
        }

        function escapar(texto) {
            const mapa = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return String(texto === null || texto === undefined ? '' : texto)
                .replace(/[&<>"']/g, (c) => mapa[c]);
        }

        function queryString() {
            const params = new URLSearchParams();
            if (filtros.desde && filtros.desde.value) params.set('desde', filtros.desde.value);
            if (filtros.hasta && filtros.hasta.value) params.set('hasta', filtros.hasta.value);
            if (filtros.periodo && filtros.periodo.value) params.set('periodo_id', filtros.periodo.value);
            if (filtros.clinica && filtros.clinica.value) params.set('clinica_id', filtros.clinica.value);
            return params;
        }

        function setEstado(tipo, texto) {
            const dot = $('#reportesDot');
            const etiqueta = $('#reportesStatus');

            if (dot) {
                dot.classList.toggle('live', tipo === 'live');
                dot.classList.toggle('syncing', tipo === 'syncing');
            }
            if (etiqueta) etiqueta.textContent = texto;
        }

        function setEstadoFiltros(visible) {
            const el = $('#reportesFiltrosStatus');
            if (el) el.classList.toggle('visible', !!visible);
        }

        /* ============================================================
           Degradación elegante: sin Chart.js seguimos mostrando tablas
           ============================================================ */
        function avisarSinChart() {
            tarjetas.forEach((tarjeta) => {
                const aviso = $('[data-rol="aviso-chart"]', tarjeta);
                const wrap = $('[data-rol="canvas-wrap"]', tarjeta);

                if (aviso) aviso.style.display = 'flex';
                if (wrap) wrap.style.display = 'none';
            });
        }

        /* ============================================================
           Tabla (degradación elegante y detalle numérico)
           ============================================================ */
        function pintarTabla(tarjeta, datos) {
            const tbody = $('[data-rol="filas"]', tarjeta);
            if (!tbody) return;

            if (!datos.labels.length) {
                tbody.innerHTML =
                    '<tr><td colspan="2" class="text-center">Sin datos para los filtros seleccionados</td></tr>';
                return;
            }

            tbody.innerHTML = datos.labels.map((etiqueta, i) => {
                return '<tr>' +
                    '<td>' + escapar(etiqueta) + '</td>' +
                    '<td><strong>' + formatear(datos.valores[i]) + '</strong></td>' +
                    '</tr>';
            }).join('');
        }

        /* ============================================================
           Gráfico
           ============================================================ */
        function pintarGrafico(tarjeta, datos) {
            if (!CHART_DISPONIBLE) return;

            const clave = tarjeta.dataset.reporte;
            const tipo = tarjeta.dataset.grafico;
            const canvas = document.getElementById('chart-' + clave);

            if (!canvas) return;

            if (graficos.has(clave)) {
                graficos.get(clave).destroy();
                graficos.delete(clave);
            }

            if (!datos.labels.length) return;

            const esCircular = tipo === 'torta' || tipo === 'dona';
            const colores = datos.labels.map((_, i) => PALETA[i % PALETA.length]);

            const dataset = {
                label: tarjeta.dataset.valor || 'Total',
                data: datos.valores
            };

            if (tipo === 'linea') {
                dataset.borderColor = PALETA[0];
                dataset.backgroundColor = 'rgba(8, 127, 121, 0.16)';
                dataset.pointBackgroundColor = PALETA[0];
                dataset.borderWidth = 2;
                dataset.tension = 0.35;
                dataset.pointRadius = 3;
                dataset.fill = true;
            } else if (esCircular) {
                dataset.backgroundColor = colores;
                dataset.borderWidth = 0;
            } else {
                dataset.backgroundColor = colores;
                dataset.borderRadius = 6;
                dataset.borderSkipped = false;
                dataset.maxBarThickness = 48;
            }

            const opciones = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                indexAxis: tipo === 'barra-horizontal' ? 'y' : 'x',
                plugins: {
                    legend: {
                        display: esCircular,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.label + ': ' +
                                formatear(ctx.parsed && ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed)
                        }
                    }
                },
                scales: esCircular ? {} : {
                    x: {
                        beginAtZero: true,
                        grid: { display: tipo === 'barra-horizontal' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { display: tipo !== 'barra-horizontal' },
                        ticks: tipo === 'barra-horizontal' ? {
                            autoSkip: false,
                            callback: function (valor) {
                                const texto = this.getLabelForValue(valor);
                                return texto.length > 34 ? texto.slice(0, 32) + '…' : texto;
                            }
                        } : {}
                    }
                }
            };

            try {
                graficos.set(clave, new Chart(canvas, {
                    type: TIPOS[tipo] || 'bar',
                    data: {
                        labels: datos.labels,
                        datasets: [dataset]
                    },
                    options: opciones
                }));
            } catch (e) {
                console.error('[Reportes] No se pudo dibujar ' + clave, e);
            }
        }

        /* ============================================================
           KPIs y enlaces de exportación
           ============================================================ */
        function actualizarKpis(resultados) {
            const totales = {};

            resultados.forEach(({ tarjeta, datos }) => {
                if (datos) totales[tarjeta.dataset.reporte] = datos.total;
            });

            const mapa = {
                kpiAdmisiones: 'pacientes-por-periodo',
                kpiTratamientos: 'tratamientos-por-especialidad',
                kpiRadiografias: 'radiografias-por-mes',
                kpiIngresos: 'ingresos-por-mes'
            };

            Object.keys(mapa).forEach((id) => {
                const el = document.getElementById(id);
                const clave = mapa[id];

                if (el && totales[clave] !== undefined) {
                    el.textContent = formatear(totales[clave]);
                }
            });
        }

        function actualizarEnlaces() {
            const qs = queryString().toString();

            ['btnExportarCsv', 'btnImprimible'].forEach((id) => {
                const el = document.getElementById(id);
                if (el && el.dataset.base) {
                    el.href = el.dataset.base + (qs ? '?' + qs : '');
                }
            });
        }

        /* ============================================================
           Carga de datos
           ============================================================ */
        async function cargarTodo() {
            setEstado('syncing', 'Actualizando…');
            setEstadoFiltros(true);

            const qs = queryString().toString();

            const resultados = await Promise.all(tarjetas.map(async (tarjeta) => {
                const url = tarjeta.dataset.url + (qs ? '?' + qs : '');

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    return { tarjeta, datos: await res.json() };
                } catch (e) {
                    console.error('[Reportes] ' + tarjeta.dataset.reporte, e);
                    return { tarjeta, datos: null };
                }
            }));

            resultados.forEach(({ tarjeta, datos }) => {
                const total = $('[data-rol="total"]', tarjeta);

                if (!datos) {
                    if (total) total.textContent = '—';
                    const tbody = $('[data-rol="filas"]', tarjeta);
                    if (tbody) {
                        tbody.innerHTML =
                            '<tr><td colspan="2" class="text-center">No se pudo cargar el reporte</td></tr>';
                    }
                    return;
                }

                if (total) total.textContent = formatear(datos.total);

                pintarTabla(tarjeta, datos);
                pintarGrafico(tarjeta, datos);
            });

            actualizarKpis(resultados);
            actualizarEnlaces();

            setEstadoFiltros(false);
            setEstado('live', 'Datos actualizados');
        }

        /* ============================================================
           Eventos
           ============================================================ */
        function limpiarFiltros() {
            if (filtros.desde) filtros.desde.value = '';
            if (filtros.hasta) filtros.hasta.value = '';
            if (filtros.periodo) filtros.periodo.value = '';
            if (filtros.clinica) filtros.clinica.value = '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (!CHART_DISPONIBLE) {
                console.warn('[Reportes] Chart.js no está disponible: se muestran solo las tablas.');
                avisarSinChart();
            }

            const btnAplicar = $('#btnAplicarFiltros');
            const btnLimpiar = $('#btnLimpiarFiltros');
            const btnRefrescar = $('#btnRefrescarReportes');

            if (btnAplicar) btnAplicar.addEventListener('click', cargarTodo);
            if (btnRefrescar) btnRefrescar.addEventListener('click', cargarTodo);

            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => {
                    limpiarFiltros();
                    cargarTodo();
                });
            }

            cargarTodo();
        });
    })();
</script>
@endpush
