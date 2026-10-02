{{-- ============================================================
     Gráficos del módulo de reportes.

     Cada tarjeta contiene:
       1. un <canvas> para Chart.js (se carga por CDN en index.blade.php)
       2. una tabla con los MISMOS datos -> degradación elegante cuando no hay
          red o Chart.js no se cargó (el JS muestra un aviso discreto).

     El JS del index localiza las tarjetas por [data-reporte] y usa data-url,
     data-grafico y data-valor; no hay nada cableado a mano por reporte.
     ============================================================ --}}
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr));">
    @foreach ($catalogo as $clave => $definicion)
    <div class="card" data-reporte="{{ $clave }}" data-grafico="{{ $definicion['grafico'] }}"
        data-valor="{{ $definicion['valor'] }}" data-url="{{ route($definicion['ruta']) }}">
        <div class="card-header">
            <h2>{{ $definicion['titulo'] }}</h2>
            <div class="card-header-actions">
                <span class="card-meta">
                    Total: <strong data-rol="total">—</strong>
                </span>
            </div>
        </div>

        <div class="card-body">
            <div data-rol="canvas-wrap" style="position: relative; height: 280px;">
                <canvas id="chart-{{ $clave }}" role="img"
                    aria-label="{{ $definicion['titulo'] }}"></canvas>
            </div>

            <p class="toolbar-info" data-rol="aviso-chart" style="display: none;">
                <span class="badge badge-warning">Gráfico no disponible</span>
                <span>No se pudo cargar Chart.js (sin conexión). Los datos se muestran igualmente en la tabla.</span>
            </p>

            <div class="table-wrap" style="margin-top: 16px;">
                <table>
                    <thead>
                        <tr>
                            <th>{{ $definicion['etiqueta'] }}</th>
                            <th>{{ $definicion['valor'] }}</th>
                        </tr>
                    </thead>
                    <tbody data-rol="filas">
                        <tr>
                            <td colspan="2" class="text-center">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>
