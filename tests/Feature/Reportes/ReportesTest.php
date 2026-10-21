<?php

namespace Tests\Feature\Reportes;

use App\Http\Controllers\ReporteController;
use App\Models\Usuario;
use App\Support\MenuBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Módulo REPORTES.
 *
 * Verifica que TODOS los endpoints de gráficos respondan 200 con el contrato
 * {labels, valores, total} — incluso con las tablas vacías (labels puede ser []).
 *
 * Se autentica con `actingAs($admin)`: funciona porque EnsureUserSession sale
 * antes si Auth::guard('web')->check() es verdadero.
 */
class ReportesTest extends TestCase
{
    use DatabaseTransactions;

    /** Rutas de los 13 reportes (todas GET). */
    private const REPORTES = [
        'reportes.pacientes-por-periodo',
        'reportes.pacientes-por-especialidad',
        'reportes.pacientes-por-edad',
        'reportes.pacientes-por-sexo',
        'reportes.motivos-consulta',
        'reportes.tratamientos-por-especialidad',
        'reportes.atenciones-por-docente',
        'reportes.atenciones-por-estudiante',
        'reportes.insumos-utilizados',
        'reportes.radiografias-por-mes',
        'reportes.esterilizaciones-por-mes',
        'reportes.pagos-por-tipo',
        'reportes.ingresos-por-mes',
    ];

    private function admin(): Usuario
    {
        $admin = Usuario::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->orderBy('id')
            ->first();

        if (! $admin) {
            $this->markTestSkipped("No existe un usuario con el rol 'admin' en la base de datos.");
        }

        return $admin;
    }

    /* ============================================================
       VISTA PRINCIPAL
       ============================================================ */

    public function test_el_index_de_reportes_responde_200_con_los_filtros_y_las_tarjetas(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.index'));

        $respuesta->assertOk();

        // Filtros globales
        $respuesta->assertSee('id="filtroDesde"', false);
        $respuesta->assertSee('id="filtroHasta"', false);
        $respuesta->assertSee('id="filtroPeriodo"', false);
        $respuesta->assertSee('id="filtroClinica"', false);

        // Acciones de exportación
        $respuesta->assertSee('id="btnExportarCsv"', false);
        $respuesta->assertSee('id="btnImprimible"', false);

        // Chart.js por CDN
        $respuesta->assertSee('chart.umd.min.js', false);

        // Degradación elegante: aviso si Chart no está definido
        $respuesta->assertSee('data-rol="aviso-chart"', false);
        $respuesta->assertSee('window.Chart', false);
    }

    public function test_el_index_renderiza_una_tarjeta_por_cada_reporte(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.index'));
        $respuesta->assertOk();

        foreach (array_keys(ReporteController::catalogo()) as $clave) {
            $respuesta->assertSee('data-reporte="'.$clave.'"', false);
            $respuesta->assertSee('id="chart-'.$clave.'"', false);
        }
    }

    /* ============================================================
       ENDPOINTS JSON
       ============================================================ */

    public function test_cada_endpoint_responde_200_con_labels_valores_y_total(): void
    {
        $admin = $this->admin();

        foreach (self::REPORTES as $nombre) {
            $respuesta = $this->actingAs($admin)->getJson(route($nombre));

            $respuesta->assertOk();
            $respuesta->assertJsonStructure(['labels', 'valores', 'total']);

            $labels = $respuesta->json('labels');
            $valores = $respuesta->json('valores');

            $this->assertIsArray($labels, "`labels` no es un arreglo en {$nombre}");
            $this->assertIsArray($valores, "`valores` no es un arreglo en {$nombre}");
            $this->assertCount(
                count($labels),
                $valores,
                "`labels` y `valores` están desalineados en {$nombre}"
            );
            $this->assertIsNumeric($respuesta->json('total'), "`total` no es numérico en {$nombre}");
        }
    }

    public function test_los_endpoints_aceptan_los_filtros_globales(): void
    {
        $admin = $this->admin();

        $query = [
            'desde' => '2000-01-01',
            'hasta' => '2100-12-31',
            'periodo_id' => 1,
            'clinica_id' => 1,
        ];

        foreach (self::REPORTES as $nombre) {
            $respuesta = $this->actingAs($admin)->getJson(route($nombre, $query));

            $respuesta->assertOk();
            $respuesta->assertJsonStructure(['labels', 'valores', 'total']);
        }
    }

    public function test_los_filtros_invalidos_no_rompen_los_endpoints(): void
    {
        $admin = $this->admin();

        $query = [
            'desde' => 'no-es-una-fecha',
            'hasta' => '99/99/9999',
            'periodo_id' => 'abc',
            'clinica_id' => '-5',
        ];

        foreach (self::REPORTES as $nombre) {
            $this->actingAs($admin)
                ->getJson(route($nombre, $query))
                ->assertOk()
                ->assertJsonStructure(['labels', 'valores', 'total']);
        }
    }

    /* ============================================================
       EXPORTACIONES
       ============================================================ */

    public function test_el_csv_se_descarga_con_bom_utf8_y_todos_los_reportes(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.exportar.csv'));

        $respuesta->assertOk();

        $this->assertStringContainsString(
            'text/csv',
            (string) $respuesta->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'attachment',
            (string) $respuesta->headers->get('content-disposition')
        );

        $contenido = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido, 'El CSV debe empezar con BOM UTF-8');
        $this->assertStringContainsString('# Admisiones por periodo', $contenido);
        $this->assertStringContainsString('# Ingresos por mes (monto cancelado)', $contenido);
    }

    public function test_el_csv_admite_un_solo_reporte_por_query_string(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->get(route('reportes.exportar.csv', ['reporte' => 'pagos-por-tipo']));

        $respuesta->assertOk();

        $contenido = $respuesta->streamedContent();

        $this->assertStringContainsString('# Pagos por tipo (monto cancelado)', $contenido);
        $this->assertStringNotContainsString('# Ingresos por mes', $contenido);
    }

    public function test_la_ruta_imprimible_usa_dompdf(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.exportar.imprimible'));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'estadisticas-clinica-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    /* ============================================================
       SOLO LECTURA Y MENÚ
       ============================================================ */

    public function test_el_modulo_de_reportes_no_permite_crear_registros(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('reportes.store'))
            ->assertStatus(405)
            ->assertJsonPath('success', false);
    }

    public function test_el_menu_incluye_reportes_con_el_patron_correcto(): void
    {
        $menu = MenuBuilder::forUser($this->admin());

        $reportes = null;

        foreach ($menu as $item) {
            if (($item['label'] ?? null) === 'Reportes') {
                $reportes = $item;
                break;
            }
        }

        $this->assertNotNull($reportes, 'El menú debe incluir la entrada "Reportes"');
        $this->assertSame('reportes.index', $reportes['route']);
        $this->assertSame('reportes.*', $reportes['pattern']);
    }
}
