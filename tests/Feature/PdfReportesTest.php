<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route($modulo.'.reporte', ['formato' => 'pdf']));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'attachment; filename="'.$modulo.'-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(1000, strlen($respuesta->getContent()));
    }

    public function test_el_pdf_filtra_las_historias_y_respeta_el_acceso_del_estudiante(): void
    {
        $caso = $this->clinicalCase();
        $this->clinicalCase();
        $caso['student']->givePermissionTo('historias.ver');

        $this->actingAs($caso['student'])->get(route('historias.reporte', [
            'buscar' => $caso['admission']->paciente->ci,
            'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id,
        ]))->assertOk()->assertViewHas('filas', fn ($filas): bool => $filas->count() === 1
            && $filas->first()[0] === (string) $caso['history']->id);

        $respuesta = $this->get(route('historias.reporte', [
            'formato' => 'pdf', 'buscar' => $caso['admission']->paciente->ci,
            'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id,
        ]))->assertOk();
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_el_pdf_del_paciente_incrusta_las_imagenes_y_escapa_los_datos_clinicos(): void
    {
        Storage::fake('public');
        $imagen = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=');
        Storage::disk('public')->put('odontograma/adulto/posterior/frontal/11_incisivo_central_superior_derecho_frontal.png', $imagen);
        $caso = $this->clinicalCase();
        $caso['admission']->update(['antecedentes' => '<script>alert(1)</script>', 'odontograma_pintado' => json_encode(['11' => ['o' => 'caries']])]);

        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id]))
            ->assertOk()->assertSee('Caries')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $respuesta = $this->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(1000, strlen($respuesta->getContent()));
    }

    public function test_el_pdf_del_paciente_rechaza_un_caso_ajeno(): void
    {
        $caso = $this->clinicalCase();
        $otro = $this->clinicalUser('estudiante');
        $otro->givePermissionTo('admisiones.ver');

        $this->actingAs($otro)->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']))->assertNotFound();
    }

    public function test_la_descarga_requiere_permisos_y_rechaza_formatos_invalidos(): void
    {
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $this->actingAs($usuario)->get(route('admisiones.reporte', ['formato' => 'pdf']))->assertForbidden();
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.reporte', ['formato' => 'exe']))
            ->assertUnprocessable()->assertJsonValidationErrors('formato');
    }

    public function test_el_reporte_estadistico_exporta_todos_los_indicadores_sin_recursos_externos(): void
    {
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertCount(13, ReporteController::catalogo());
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_la_exportacion_estadistica_rechaza_indicadores_y_fechas_invalidos(): void
    {
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('reportes.exportar.pdf', ['reporte' => 'inexistente']))
            ->assertUnprocessable()->assertJsonValidationErrors('reporte');
        $this->getJson(route('reportes.exportar.pdf', ['desde' => '2026-10-05', 'hasta' => '2026-10-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }

    public function test_el_pdf_estadistico_permite_un_indicador_y_conserva_sus_filtros(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf', [
            'reporte' => 'tratamientos-por-especialidad', 'clinica_id' => $caso['clinic']->id,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_el_pdf_estadistico_requiere_permiso_de_reportes(): void
    {
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $this->actingAs($usuario)->get(route('reportes.exportar.pdf'))->assertForbidden();
    }

    public function test_dompdf_genera_un_pdf_real_del_paciente_con_odontograma(): void
    {
        $caso = $this->approvedProgress();
        $caso['admission']->update(['motivo_consulta' => 'Control odontológico', 'odontograma_pintado' => json_encode(['16' => ['o' => 'caries', 'marcas' => [['estado' => 'corona', 'color' => 'azul']]]])]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', ['admision' => $caso['admission']->id, 'formato' => 'pdf']));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'attachment;')
            ->assertHeaderContains('Cache-Control', 'no-store');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(20000, strlen($respuesta->getContent()));
    }

    public function test_dompdf_genera_un_pdf_real_horizontal_del_modulo_filtrado(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte', ['formato' => 'pdf', 'buscar' => $caso['admission']->paciente->ci]));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'admisiones-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_dompdf_genera_un_pdf_real_de_las_estadisticas(): void
    {
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('reportes.exportar.pdf'));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'estadisticas-clinica-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }
}
