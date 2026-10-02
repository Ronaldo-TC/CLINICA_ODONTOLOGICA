@extends('layouts.app')

@section('title', 'Investigación')
@section('page-title', 'Investigación')
@section('page-subtitle', 'Cohortes anonimizadas para estudios académicos del Instituto de Investigación')

@section('content')

{{-- ============================================================
     ENCABEZADO
     ============================================================ --}}
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live" id="investigacionDot"></span>
        <span id="investigacionStatus">{{ $registros->total() }} registros anonimizados</span>
    </div>
    <div class="toolbar-actions">
        @canany(['investigacion.ver', 'investigacion.gestionar'])
        <a class="btn btn-primary" id="btnExportar" href="{{ route('investigacion.exportar') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Exportar CSV
        </a>
        @endcanany
    </div>
</div>

{{-- ============================================================
     TARJETAS DE ESTADÍSTICA DESCRIPTIVA
     ============================================================ --}}
<div class="stats-grid">
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
            <span class="stat-value" id="statTotal">—</span>
            <span class="stat-label">Registros en la cohorte</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 3v18h18" />
                <path d="M18 17V9" />
                <path d="M13 17V5" />
                <path d="M8 17v-3" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statMedia">—</span>
            <span class="stat-label">Edad media</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon fuchsia">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <line x1="18" y1="20" x2="18" y2="10" />
                <line x1="12" y1="20" x2="12" y2="4" />
                <line x1="6" y1="20" x2="6" y2="14" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statMediana">—</span>
            <span class="stat-label">Edad mediana</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statModa">—</span>
            <span class="stat-label">Edad modal</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statDesviacion">—</span>
            <span class="stat-label">Desviación estándar</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statRango">—</span>
            <span class="stat-label">Rango de edad</span>
        </div>
    </div>
</div>

{{-- ============================================================
     FILTROS
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de la cohorte</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="investigacionFiltrosStatus">
                <span class="dot"></span>
                <span>Actualizando...</span>
            </span>
        </div>
    </div>
    <div class="card-body">
        @include('investigacion.partials.filtros')
    </div>
</div>

{{-- ============================================================
     DATASET ANONIMIZADO
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Dataset anonimizado</h2>
        <div class="card-header-actions">
            <span class="card-meta">
                Registros: <strong id="investigacionTotalTabla">{{ $registros->total() }}</strong>
            </span>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Edad</th>
                    <th>Género</th>
                    <th>Motivo</th>
                    <th>Especialidad</th>
                    <th>Tratamiento</th>
                    <th>Periodo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tbodyInvestigacion">
                @include('investigacion.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

{{-- ============================================================
     GRÁFICO COMPARATIVO ENTRE PERIODOS (con tabla de respaldo)
     ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Comparativo de cohortes por periodo</h2>
        <div class="card-header-actions">
            <span class="card-meta">
                Periodos: <strong id="investigacionTotalPeriodos">0</strong>
            </span>
        </div>
    </div>
    <div class="card-body">
        <canvas id="chartPeriodos" height="220"></canvas>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Registros</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody id="tbodyPeriodos">
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <p>Cargando estadísticas...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Chart.js vía CDN (si no carga, queda la tabla comparativa como respaldo) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    (() => {
        'use strict';

        const URLS = {
            tabla: @json(route('investigacion.tabla')),
            estadisticas: @json(route('investigacion.estadisticas')),
            exportar: @json(route('investigacion.exportar')),
        };

        const $tbody = document.getElementById('tbodyInvestigacion');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('investigacionStatus');
        const $dot = document.getElementById('investigacionDot');
        const $filtrosStatus = document.getElementById('investigacionFiltrosStatus');
        const $totalTabla = document.getElementById('investigacionTotalTabla');
        const $totalPeriodos = document.getElementById('investigacionTotalPeriodos');
        const $btnExportar = document.getElementById('btnExportar');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        const filtros = {
            anio: document.getElementById('filtro-anio'),
            clinica: document.getElementById('filtro-clinica'),
            edad_min: document.getElementById('filtro-edad-min'),
            edad_max: document.getElementById('filtro-edad-max'),
            genero: document.getElementById('filtro-genero'),
            motivo: document.getElementById('filtro-motivo'),
        };

        let debounceTimer = null;
        let cargando = false;
        let chart = null;

        function esc(valor) {
            return String(valor === null || valor === undefined ? '' : valor)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function params() {
            const p = new URLSearchParams();
            Object.entries(filtros).forEach(([clave, el]) => {
                const valor = (el && el.value ? el.value : '').trim();
                if (valor !== '') p.set(clave, valor);
            });
            return p;
        }

        function setEstado(texto, sincronizando) {
            if ($status) $status.textContent = texto;
            if (!$dot) return;
            $dot.classList.toggle('syncing', !!sincronizando);
            $dot.classList.toggle('live', !sincronizando);
        }

        function setFiltrosStatus(visible) {
            if ($filtrosStatus) $filtrosStatus.classList.toggle('visible', !!visible);
        }

        function sincronizarExportar() {
            if (!$btnExportar) return;
            const qs = params().toString();
            $btnExportar.href = qs ? URLS.exportar + '?' + qs : URLS.exportar;
        }

        async function cargarTabla(page = 1) {
            if (cargando || !$tbody) return;
            cargando = true;
            setFiltrosStatus(true);

            const p = params();
            if (page > 1) p.set('page', page);

            try {
                const res = await fetch(URLS.tabla + '?' + p.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);

                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                if ($pagWrap) $pagWrap.innerHTML = data.paginacion || '';
                if ($totalTabla) $totalTabla.textContent = data.total || 0;
            } catch (err) {
                console.error('[Investigación] Error al cargar la tabla:', err);
                $tbody.innerHTML =
                    '<tr><td colspan="8"><div class="empty-state"><p>Error al cargar el dataset anonimizado.</p></div></td></tr>';
            } finally {
                setFiltrosStatus(false);
                cargando = false;
            }
        }

        function renderPeriodos(periodo, total) {
            const entradas = Object.entries(periodo || {});
            const tbody = document.getElementById('tbodyPeriodos');

            if ($totalPeriodos) $totalPeriodos.textContent = entradas.length;

            if (tbody) {
                if (!entradas.length) {
                    tbody.innerHTML =
                        '<tr><td colspan="3"><div class="empty-state"><p>Sin datos por periodo.</p></div></td></tr>';
                } else {
                    tbody.innerHTML = entradas.map(([nombre, conteo]) => {
                        const pct = total ? ((Number(conteo) / Number(total)) * 100).toFixed(1) : '0.0';
                        return '<tr>' +
                            '<td><span class="badge badge-muted">' + esc(nombre) + '</span></td>' +
                            '<td><strong>' + esc(conteo) + '</strong></td>' +
                            '<td>' + pct + '%</td>' +
                            '</tr>';
                    }).join('');
                }
            }

            const canvas = document.getElementById('chartPeriodos');

            // Degradación elegante: sin Chart.js disponible queda la tabla.
            if (typeof window.Chart !== 'function' || !canvas) return;

            const etiquetas = entradas.map(([nombre]) => nombre);
            const valores = entradas.map(([, conteo]) => Number(conteo));
            const oscuro = document.documentElement.dataset.theme === 'dark';

            if (chart) chart.destroy();

            chart = new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Registros por periodo',
                        data: valores,
                        backgroundColor: oscuro ? '#42b7c2' : '#078fa0',
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 56,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: oscuro ? '#a6bbb5' : '#60736f' },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: oscuro ? '#a6bbb5' : '#60736f',
                                precision: 0,
                            },
                        },
                    },
                },
            });
        }

        function renderEstadisticas(data) {
            const edad = data.edad || {};
            const total = data.total || 0;

            const set = (id, valor) => {
                const el = document.getElementById(id);
                if (el) el.textContent = valor;
            };

            set('statTotal', total);
            set('statMedia', edad.media !== null && edad.media !== undefined ? edad.media + ' años' : '—');
            set('statMediana', edad.mediana !== null && edad.mediana !== undefined ? edad.mediana + ' años' : '—');
            set('statModa', edad.moda !== null && edad.moda !== undefined ? edad.moda + ' años' : '—');
            set('statDesviacion', edad.desviacion !== null && edad.desviacion !== undefined ? '± ' + edad.desviacion : '—');
            set('statRango',
                edad.min !== null && edad.min !== undefined && edad.max !== null && edad.max !== undefined
                    ? edad.min + ' - ' + edad.max + ' años'
                    : '—');

            setEstado(total + ' registros anonimizados en la cohorte', false);
            renderPeriodos(data.periodo, total);
        }

        async function cargarEstadisticas() {
            try {
                const res = await fetch(URLS.estadisticas + '?' + params().toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);

                renderEstadisticas(await res.json());
            } catch (err) {
                console.error('[Investigación] Error al cargar estadísticas:', err);
                setEstado('No se pudieron cargar las estadísticas', false);
            }
        }

        function refrescar() {
            sincronizarExportar();
            cargarTabla(1);
            cargarEstadisticas();
        }

        function conDebounce() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(refrescar, 300);
        }

        Object.values(filtros).forEach((el) => {
            if (!el) return;
            el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', conDebounce);
        });

        $btnLimpiar?.addEventListener('click', () => {
            Object.values(filtros).forEach((el) => {
                if (el) el.value = '';
            });
            refrescar();
        });

        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (!pagLink) return;

            e.preventDefault();
            const url = new URL(pagLink.href, window.location.origin);
            cargarTabla(parseInt(url.searchParams.get('page') || '1', 10));
        });

        document.addEventListener('DOMContentLoaded', () => {
            sincronizarExportar();
            cargarEstadisticas();
        });

        window.InvestigacionLista = {
            cargar: cargarTabla,
            estadisticas: cargarEstadisticas,
            refrescar: refrescar,
        };
    })();
</script>
@endpush
