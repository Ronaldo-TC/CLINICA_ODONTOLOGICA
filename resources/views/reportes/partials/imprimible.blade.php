{{--
    ============================================================================
    Vista IMPRIMIBLE de reportes — equivalente a "exportar a PDF" sin dependencias
    externas (el proyecto no tiene barryvdh/laravel-dompdf y no se instala).

    EXCEPCIÓN DE ESTILOS JUSTIFICADA (la única del módulo):
    La regla del proyecto prohíbe etiquetas <style> en las vistas del módulo.
    Este archivo es un documento HTML AUTÓNOMO a propósito: no extiende
    layouts.app para que NO se impriman el sidebar, el topbar ni los menús.
    Por eso necesita:
      - su propia hoja de estilos para dar formato al documento, y
      - reglas @media print para forzar A4, evitar que las tarjetas se corten
        entre páginas y ocultar la barra de acciones al imprimir.
    El <style> vive SOLO aquí, tal y como autoriza la regla del módulo.

    El documento invoca window.print() automáticamente al terminar de dibujar
    los gráficos, para que el usuario elija "Guardar como PDF".
    ============================================================================
--}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes · Clínica Odontológica UPEA</title>

    <style>
        :root {
            --teal: #087f79;
            --ink: #1b2e2b;
            --muted: #60736f;
            --line: #dce6e2;
            --soft: #edf3f1;
            --warning-bg: #fef3c7;
            --warning-ink: #78350f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #fff;
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            letter-spacing: -0.02em;
        }

        h1 strong {
            color: var(--teal);
        }

        .barra-acciones {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .barra-acciones button,
        .barra-acciones a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            font: 600 13px inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .barra-acciones button {
            background: var(--teal);
            border-color: var(--teal);
            color: #fff;
        }

        .encabezado {
            padding-bottom: 14px;
            margin-bottom: 18px;
            border-bottom: 2px solid var(--line);
        }

        .encabezado p {
            margin: 0;
            color: var(--muted);
            font-size: 12.5px;
        }

        .encabezado .filtros {
            margin-top: 6px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .reporte {
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .reporte h2 {
            margin: 0 0 4px;
            font-size: 14px;
            letter-spacing: -0.01em;
        }

        .reporte .total {
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 12px;
        }

        .reporte .total strong {
            color: var(--ink);
        }

        .grafico {
            position: relative;
            height: 230px;
        }

        .aviso-chart {
            display: none;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 6px 10px;
            border-radius: 8px;
            background: var(--warning-bg);
            color: var(--warning-ink);
            font-size: 11.5px;
        }

        table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            padding: 5px 8px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        thead th {
            background: var(--soft);
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        td.numero {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }

        .sin-datos {
            color: var(--muted);
            font-style: italic;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 7mm 7mm 10mm;
            }

            body {
                padding: 0;
                font-size: 10.5px;
                line-height: 1.25;
            }

            .no-imprimir {
                display: none !important;
            }

            .grid {
                gap: 8px;
                align-items: start;
            }

            .encabezado { padding-bottom: 8px; margin-bottom: 10px; }
            h1 { font-size: 18px; }
            .encabezado p { font-size: 10px; }

            .reporte {
                border-color: #cccccc;
                padding: 7px 8px;
                break-inside: auto;
                page-break-inside: auto;
            }

            tr,
            .grafico {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .grafico {
                height: 170px;
            }

            .reporte h2 { font-size: 12px; break-after: avoid; }
            .reporte .total { font-size: 10px; margin-bottom: 6px; }
            table { font-size: 10px; margin-top: 6px; break-inside: auto; }
            th, td { padding: 3px 5px; overflow-wrap: anywhere; }
            thead th { font-size: 9px; white-space: normal; }
            td.numero { overflow-wrap: normal; }

            thead {
                display: table-header-group;
            }
        }
    </style>
</head>

<body>

    <div class="barra-acciones no-imprimir">
        <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
        <a href="{{ route('reportes.index', request()->query()) }}">Volver a reportes</a>
        <a href="{{ route('reportes.exportar.csv', request()->query()) }}">Descargar CSV</a>
    </div>

    <header class="encabezado">
        <h1>Reportes de la <strong>Clínica Odontológica UPEA</strong></h1>
        <p>Filtros aplicados: {{ $descripcion }}</p>
        <p class="filtros">
            Emitido el {{ $emitido->format('d/m/Y H:i') }} ·
            {{ count($catalogo) }} reportes · Sistema de Clínica Odontológica
        </p>
    </header>

    <div class="grid">
        @foreach ($catalogo as $clave => $definicion)
        @php $d = $datos[$clave] ?? ['labels' => [], 'valores' => [], 'total' => 0]; @endphp
        <section class="reporte" data-reporte="{{ $clave }}" data-grafico="{{ $definicion['grafico'] }}">
            <h2>{{ $definicion['titulo'] }}</h2>
            <p class="total">
                Total: <strong>{{ number_format((float) $d['total'], 0, ',', '.') }}</strong>
                {{ $definicion['valor'] }}
            </p>

            <div class="grafico" data-rol="canvas-wrap">
                <canvas id="chart-{{ $clave }}" role="img" aria-label="{{ $definicion['titulo'] }}"></canvas>
            </div>

            <p class="aviso-chart" data-rol="aviso-chart">
                Chart.js no se cargó (sin conexión): el gráfico no está disponible en este documento.
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ $definicion['etiqueta'] }}</th>
                            <th>{{ $definicion['valor'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($d['labels'] as $i => $etiqueta)
                        <tr>
                            <td>{{ $etiqueta }}</td>
                            <td class="numero">{{ number_format((float) ($d['valores'][$i] ?? 0), is_int($d['valores'][$i] ?? 0) ? 0 : 2, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="sin-datos">Sin datos para los filtros seleccionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endforeach
    </div>

    {{-- Chart.js por CDN: si no hay red, se imprimen igualmente las tablas. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        (function () {
            'use strict';

            var DATOS = @json($datos);
            var CATALOGO = @json($catalogo);

            var TIPOS = {
                'barra': 'bar',
                'barra-horizontal': 'bar',
                'torta': 'pie',
                'dona': 'doughnut',
                'linea': 'line'
            };

            var PALETA = [
                '#087f79', '#078fa0', '#b3449e', '#d97706', '#16a34a',
                '#0284c7', '#dc2626', '#7c3aed', '#0d9488', '#e11d48'
            ];

            function dibujarTodo() {
                Object.keys(CATALOGO).forEach(function (clave) {
                    var canvas = document.getElementById('chart-' + clave);
                    var datos = DATOS[clave];

                    if (!canvas || !datos || !datos.labels.length) return;

                    var tipo = CATALOGO[clave].grafico;
                    var esCircular = tipo === 'torta' || tipo === 'dona';
                    var colores = datos.labels.map(function (_, i) {
                        return PALETA[i % PALETA.length];
                    });

                    var dataset = {
                        label: CATALOGO[clave].valor,
                        data: datos.valores
                    };

                    if (tipo === 'linea') {
                        dataset.borderColor = PALETA[0];
                        dataset.backgroundColor = 'rgba(8, 127, 121, 0.16)';
                        dataset.pointBackgroundColor = PALETA[0];
                        dataset.borderWidth = 2;
                        dataset.tension = 0.35;
                        dataset.pointRadius = 2;
                        dataset.fill = true;
                    } else if (esCircular) {
                        dataset.backgroundColor = colores;
                        dataset.borderWidth = 0;
                    } else {
                        dataset.backgroundColor = colores;
                        dataset.borderRadius = 5;
                        dataset.borderSkipped = false;
                        dataset.maxBarThickness = 40;
                    }

                    try {
                        new Chart(canvas, {
                            type: TIPOS[tipo] || 'bar',
                            data: { labels: datos.labels, datasets: [dataset] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                animation: false,
                                indexAxis: tipo === 'barra-horizontal' ? 'y' : 'x',
                                plugins: {
                                    legend: { display: esCircular, position: 'bottom' },
                                    tooltip: { enabled: false }
                                },
                                scales: esCircular ? {} : {
                                    x: { beginAtZero: true, grid: { display: tipo === 'barra-horizontal' } },
                                    y: {
                                        beginAtZero: true,
                                        grid: { display: tipo !== 'barra-horizontal' },
                                        ticks: tipo === 'barra-horizontal' ? {
                                            autoSkip: false,
                                            callback: function (valor) {
                                                var texto = this.getLabelForValue(valor);
                                                return texto.length > 30 ? texto.slice(0, 28) + '…' : texto;
                                            }
                                        } : {}
                                    }
                                }
                            }
                        });
                    } catch (e) {
                        console.error('[Reportes imprimible] ' + clave, e);
                    }
                });
            }

            function avisarSinChart() {
                var avisos = document.querySelectorAll('[data-rol="aviso-chart"]');
                var wraps = document.querySelectorAll('[data-rol="canvas-wrap"]');

                Array.prototype.forEach.call(avisos, function (el) {
                    el.style.display = 'flex';
                });
                Array.prototype.forEach.call(wraps, function (el) {
                    el.style.display = 'none';
                });
            }

            window.addEventListener('load', function () {
                if (typeof window.Chart === 'undefined') {
                    avisarSinChart();
                    window.print();
                    return;
                }

                try {
                    dibujarTodo();
                } catch (e) {
                    console.error('[Reportes imprimible]', e);
                }

                // Pequeña espera para que Chart.js termine de pintar los canvas.
                setTimeout(function () {
                    window.print();
                }, 700);
            });
        })();
    </script>

</body>

</html>
