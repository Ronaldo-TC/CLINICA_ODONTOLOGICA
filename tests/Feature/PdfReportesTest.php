<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class PdfReportesTest extends TestCase
{
    use CreatesClinicalCase;
    use DatabaseTransactions;

    /** @return array<string, array{0: string}> */
    public static function modulos(): array
    {
        return ReportesClinicosTest::modulos();
    }

    #[DataProvider('modulos')]
    public function test_cada_modulo_descarga_un_pdf_horizontal_sin_los_botones_de_la_vista_previa(string $modulo): void
    {
        Pdf::fake();
        $this->actingAs($this->clinicalUser('admin'))->get(route($modulo.'.reporte', ['formato' => 'pdf']))->assertOk();

        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($modulo): bool {
            $html = $pdf->getHtml();

            return $pdf->isDownload() && str_starts_with($pdf->downloadName, $modulo.'-')
                && $pdf->orientation === Orientation::Landscape->value && $pdf->viewName === 'reportes.modulo'
                && str_contains($html, 'Universidad Pública de El Alto')
                && ! str_contains($html, 'id="imprimir-reporte"');
        });
    }

    public function test_el_pdf_filtra_las_historias_y_respeta_el_acceso_del_estudiante(): void
    {
        Pdf::fake();
        $caso = $this->clinicalCase();
        $this->clinicalCase();
        $caso['student']->givePermissionTo('historias.ver');

        $this->actingAs($caso['student'])->get(route('historias.reporte', [
            'formato' => 'pdf', 'buscar' => $caso['admission']->paciente->ci,
            'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id,
        ]))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewData['filas']->count() === 1
            && $pdf->viewData['filas']->first()[0] === (string) $caso['history']->id);
    }

    public function test_el_pdf_del_paciente_incrusta_las_imagenes_y_escapa_los_datos_clinicos(): void
    {
        Pdf::fake();
        Storage::fake('public');
        $imagen = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=');
        Storage::disk('public')->put('odontograma/adulto/posterior/frontal/11_incisivo_central_superior_derecho_frontal.png', $imagen);
        $caso = $this->clinicalCase();
        $caso['admission']->update(['antecedentes' => '<script>alert(1)</script>', 'odontograma_pintado' => json_encode(['11' => ['o' => 'caries']])]);

        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']))->assertOk();

        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($imagen): bool {
            $html = $pdf->getHtml();

            return $pdf->isDownload() && $pdf->viewName === 'reportes.paciente'
                && str_contains($html, 'data:image/png;base64,'.base64_encode($imagen))
                && str_contains($html, 'Caries') && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
                && ! str_contains($html, '<script>alert(1)</script>')
                && ! str_contains($html, route('odontograma.imagen', ['denticion' => 'adulto', 'pieza' => '11', 'vista' => 'frontal']));
        });
    }

    public function test_el_pdf_del_paciente_rechaza_un_caso_ajeno(): void
    {
        Pdf::fake();
        $caso = $this->clinicalCase();
        $otro = $this->clinicalUser('estudiante');
        $otro->givePermissionTo('admisiones.ver');

        $this->actingAs($otro)->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']))->assertNotFound();
    }

    public function test_la_descarga_requiere_permisos_y_rechaza_formatos_invalidos(): void
    {
        Pdf::fake();
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $this->actingAs($usuario)->get(route('admisiones.reporte', ['formato' => 'pdf']))->assertForbidden();
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.reporte', ['formato' => 'exe']))
            ->assertUnprocessable()->assertJsonValidationErrors('formato');
    }

    public function test_el_reporte_estadistico_exporta_todos_los_indicadores_sin_recursos_externos(): void
    {
        Pdf::fake();
        $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf'))->assertOk();

        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
            $html = $pdf->getHtml();

            return count($pdf->viewData['catalogo']) === count(ReporteController::catalogo())
                && $pdf->viewName === 'reportes.estadisticas' && ! str_contains($html, 'cdn.jsdelivr.net');
        });
    }

    public function test_la_exportacion_estadistica_rechaza_indicadores_y_fechas_invalidos(): void
    {
        Pdf::fake();
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('reportes.exportar.pdf', ['reporte' => 'inexistente']))
            ->assertUnprocessable()->assertJsonValidationErrors('reporte');
        $this->getJson(route('reportes.exportar.pdf', ['desde' => '2026-10-05', 'hasta' => '2026-10-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }

    public function test_el_pdf_estadistico_permite_un_indicador_y_conserva_sus_filtros(): void
    {
        Pdf::fake();
        $caso = $this->clinicalCase();
        $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf', [
            'reporte' => 'tratamientos-por-especialidad', 'clinica_id' => $caso['clinic']->id,
        ]))->assertOk();

        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => array_keys($pdf->viewData['catalogo']) === ['tratamientos-por-especialidad']
            && str_contains($pdf->getHtml(), 'clínica #'.$caso['clinic']->id));
    }

    public function test_el_pdf_estadistico_requiere_permiso_de_reportes(): void
    {
        Pdf::fake();
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $this->actingAs($usuario)->get(route('reportes.exportar.pdf'))->assertForbidden();
    }

    public function test_chrome_genera_un_pdf_real_del_paciente_con_odontograma(): void
    {
        $chrome = config('laravel-pdf.browsershot.chrome_path');
        if (! $chrome || ! is_file($chrome) || ! is_file(base_path('node_modules/puppeteer/package.json'))) {
            $this->markTestSkipped('La integración PDF requiere Chrome y Puppeteer instalados.');
        }
        $caso = $this->approvedProgress();
        $caso['admission']->update(['motivo_consulta' => 'Control odontológico', 'odontograma_pintado' => json_encode(['16' => ['o' => 'caries', 'marcas' => [['estado' => 'corona', 'color' => 'azul']]]])]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'attachment;')
            ->assertHeaderContains('Cache-Control', 'no-store');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(20000, strlen($respuesta->getContent()));
    }

    public function test_chrome_genera_un_pdf_real_horizontal_del_modulo_filtrado(): void
    {
        $chrome = config('laravel-pdf.browsershot.chrome_path');
        if (! $chrome || ! is_file($chrome) || ! is_file(base_path('node_modules/puppeteer/package.json'))) {
            $this->markTestSkipped('La integración PDF requiere Chrome y Puppeteer instalados.');
        }
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte', ['formato' => 'pdf', 'buscar' => $caso['admission']->paciente->ci]));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'admisiones-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_chrome_genera_un_pdf_real_de_las_estadisticas(): void
    {
        $chrome = config('laravel-pdf.browsershot.chrome_path');
        if (! $chrome || ! is_file($chrome) || ! is_file(base_path('node_modules/puppeteer/package.json'))) {
            $this->markTestSkipped('La integración PDF requiere Chrome y Puppeteer instalados.');
        }
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf'));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'estadisticas-clinica-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }
}
