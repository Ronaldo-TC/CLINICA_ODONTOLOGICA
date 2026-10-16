<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\SesionAtencion;
use App\Models\TratamientoRealizado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class TratamientosRealizadosAjaxTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_index_displays_a_table_with_patient_status_and_expandable_progress(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);

        $this->actingAs($case['student'])->get(route('tratamientos-realizados.index'))->assertOk()
            ->assertSee('id="tablaAtenciones"', false)->assertSee('id="tbodyAtenciones"', false)
            ->assertSee('Paciente')->assertSee('Costo (Bs)')->assertSee('En proceso')
            ->assertSee('100.00')->assertSee('Ver avances')->assertSee('Registrar avance')
            ->assertSee('aria-controls="avances-'.$case['realized']->id.'"', false)
            ->assertSee('data-form-atencion', false)->assertSee('window.fetchAjax', false);
    }

    public function test_ajax_table_filters_by_patient_and_state_and_retains_progress(): void
    {
        $case = $this->approvedProgress();
        $pending = $this->createTreatment($case, 'pendiente');

        $response = $this->actingAs($case['student'])->getJson(route('tratamientos-realizados.tabla', [
            'buscar' => $case['admission']->paciente->ci, 'estado' => 'en_proceso',
        ]))->assertOk()->assertJsonPath('total', 1)->assertJsonStructure(['filas', 'paginacion', 'total']);

        $this->assertStringContainsString('data-id="'.$case['realized']->id.'"', $response->json('filas'));
        $this->assertStringNotContainsString('data-id="'.$pending->id.'"', $response->json('filas'));
        $this->assertStringContainsString('Avance #'.$case['session']->id, $response->json('filas'));
    }

    public function test_ajax_pagination_preserves_filters_and_renders_the_second_page(): void
    {
        $case = $this->clinicalCase();
        foreach (range(1, 16) as $number) {
            $this->createTreatment($case, 'pendiente');
        }

        $response = $this->actingAs($case['student'])->getJson(route('tratamientos-realizados.tabla', [
            'buscar' => $case['admission']->paciente->ci, 'estado' => 'pendiente', 'page' => 2,
        ]))->assertOk()->assertJsonPath('total', 16);

        $this->assertSame(1, substr_count($response->json('filas'), '<tr data-id='));
        $this->assertStringContainsString('Página 2 de 2', $response->json('paginacion'));
        $this->assertStringContainsString('estado=pendiente', $response->json('paginacion'));
        $this->assertStringContainsString(urlencode($case['admission']->paciente->ci), $response->json('paginacion'));
    }

    public function test_ajax_search_cannot_expose_another_students_treatments(): void
    {
        $own = $this->approvedProgress();
        $other = $this->approvedProgress();

        $this->actingAs($own['student'])->getJson(route('tratamientos-realizados.tabla', [
            'buscar' => $other['admission']->paciente->ci,
        ]))->assertOk()->assertJsonPath('total', 0);

        $response = $this->getJson(route('tratamientos-realizados.tabla'))->assertOk()->assertJsonPath('total', 1);
        $this->assertStringNotContainsString('data-id="'.$other['realized']->id.'"', $response->json('filas'));
    }

    public function test_ajax_table_forbids_a_user_without_attention_permissions(): void
    {
        $this->actingAs($this->clinicalUser('cajas'))->getJson(route('tratamientos-realizados.tabla'))->assertForbidden();
    }

    public function test_ajax_table_redirects_guests_to_login(): void
    {
        $this->get(route('tratamientos-realizados.tabla'))->assertRedirect(route('auth.login'));
    }

    public function test_invalid_state_returns_validation_errors_without_querying_unrelated_treatments(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($case['student'])->getJson(route('tratamientos-realizados.tabla', ['estado' => 'desconocido']))
            ->assertUnprocessable()->assertJsonValidationErrors('estado');
    }

    public function test_ajax_rows_escape_patient_names_treatment_names_and_progress_descriptions(): void
    {
        $case = $this->approvedProgress();
        $case['admission']->paciente->update(['nombre' => '<script>paciente</script>']);
        $case['treatment']->update(['nombre' => '<script>tratamiento</script>']);
        $case['session']->update(['descripcion' => '<script>avance</script>']);

        $response = $this->actingAs($case['student'])->getJson(route('tratamientos-realizados.tabla'))->assertOk();
        foreach (['paciente', 'tratamiento', 'avance'] as $value) {
            $this->assertStringContainsString('&lt;script&gt;'.$value.'&lt;/script&gt;', $response->json('filas'));
            $this->assertStringNotContainsString('<script>'.$value.'</script>', $response->json('filas'));
        }
    }

    public function test_teacher_authorization_returns_json_and_updates_the_treatment(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $treatment = $this->createTreatment($case, 'pendiente');

        $this->actingAs($case['teacher'])->postJson(route('tratamientos-realizados.autorizar', $treatment->id))
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('message', 'Tratamiento autorizado para Cajas.');

        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $treatment->id, 'estado' => 'autorizado', 'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);
    }

    public function test_chair_head_can_review_the_assigned_clinic_and_see_its_responsible_team(): void
    {
        $case = $this->clinicalCase();
        $otherCase = $this->clinicalCase();
        $chief = $this->clinicalUser('jefe-catedra');
        $chief->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);
        $chief->givePermissionTo(['atenciones.ver', 'atenciones.autorizar', 'historias.autorizar']);

        $this->createTreatment($case, 'pendiente');
        $progress = $this->createTreatment($case, 'en_proceso');
        SesionAtencion::create([
            'tratamiento_realizado_id' => $progress->id,
            'estudiante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id,
            'fecha' => today(),
            'descripcion' => 'Avance pendiente de revisión',
            'autorizado' => false,
        ]);
        $this->createTreatment($case, 'finalizado');
        $outsideClinic = $this->createTreatment($otherCase, 'pendiente');

        $this->actingAs($chief)->get(route('tratamientos-realizados.index'))
            ->assertOk()
            ->assertSee('Bandeja de revisión de cátedra')
            ->assertSee('data-review-count="propuestas">1</span>', false)
            ->assertSee('data-review-count="avances">1</span>', false)
            ->assertSee('data-review-count="culminaciones">1</span>', false)
            ->assertSee($case['student']->nombre_completo)
            ->assertSee($case['teacher']->nombre_completo)
            ->assertDontSee('data-id="'.$outsideClinic->id.'"', false);

        $this->getJson(route('tratamientos-realizados.tabla', ['revision' => 'propuestas']))
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('tratamientos-realizados.tabla', ['revision' => 'avances']))
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('tratamientos-realizados.tabla', ['revision' => 'culminaciones']))
            ->assertOk()->assertJsonPath('total', 1);

        $allTreatments = $this->getJson(route('tratamientos-realizados.tabla', ['revision' => 'todos']))
            ->assertOk()->assertJsonPath('total', 3);
        $this->assertStringNotContainsString('data-id="'.$outsideClinic->id.'"', $allTreatments->json('filas'));
    }

    public function test_chair_head_can_authorize_treatments_and_progress_for_the_assigned_clinic_only(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $otherCase = $this->clinicalCase();
        $chief = $this->clinicalUser('jefe-catedra');
        $chief->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);
        $chief->givePermissionTo(['atenciones.autorizar', 'historias.autorizar']);
        $treatment = $this->createTreatment($case, 'pendiente');
        $outsideClinic = $this->createTreatment($otherCase, 'pendiente');

        $this->actingAs($chief)->postJson(route('tratamientos-realizados.autorizar', $treatment->id))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $treatment->id,
            'estado' => 'autorizado',
            'autorizado_por' => $chief->id,
            'fecha_autorizacion' => now(),
        ]);

        $treatment->refresh()->update(['estado' => 'en_proceso']);
        $session = SesionAtencion::create([
            'tratamiento_realizado_id' => $treatment->id,
            'estudiante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id,
            'fecha' => today(),
            'descripcion' => 'Avance para autorizar',
            'autorizado' => false,
        ]);
        $cash = Caja::create([
            'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
            'usuario_id' => $case['teacher']->id, 'recibo' => 987653, 'cancelado' => 100, 'costo_total' => 100,
            'habilitada' => true, 'monto' => 100, 'ci' => 0, 'descripcion' => '', 'tipo' => '', 'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => 'Paciente', 'grado' => '',
        ]);
        $cash->detalles()->create(['tratamiento_realizado_id' => $treatment->id, 'concepto' => 'Tratamiento', 'monto' => 100]);

        $this->postJson(route('sesiones-atencion.autorizar', $session->id), [
            'observacion_docente' => 'Revisado por jefatura de cátedra',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('sesiones_atencion', [
            'id' => $session->id,
            'autorizado' => true,
            'autorizado_por' => $chief->id,
            'observacion_docente' => 'Revisado por jefatura de cátedra',
        ]);

        $treatment->update([
            'estado' => 'finalizado',
            'finalizado_por' => $case['student']->id,
            'fecha_finalizacion' => now(),
        ]);

        $this->postJson(route('tratamientos-realizados.culminar', $treatment->id))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $treatment->id,
            'estado' => 'culminado',
            'culminado_por' => $chief->id,
        ]);

        $this->postJson(route('tratamientos-realizados.autorizar', $outsideClinic->id))
            ->assertNotFound();
    }

    public function test_teacher_progress_authorization_returns_json_and_saves_the_observation(): void
    {
        $this->freezeTime();
        $case = $this->approvedProgress();
        $case['session']->update(['autorizado' => false, 'autorizado_por' => null, 'fecha_autorizacion' => null]);
        $cash = Caja::create([
            'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
            'usuario_id' => $case['teacher']->id, 'recibo' => 987652, 'cancelado' => 100, 'costo_total' => 100,
            'habilitada' => true, 'monto' => 100, 'ci' => 0, 'descripcion' => '', 'tipo' => '', 'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => 'Paciente', 'grado' => '',
        ]);
        $cash->detalles()->create(['tratamiento_realizado_id' => $case['realized']->id, 'concepto' => 'Tratamiento', 'monto' => 100]);

        $this->actingAs($case['teacher'])->postJson(route('sesiones-atencion.autorizar', $case['session']->id), [
            'observacion_docente' => 'Avance revisado',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('sesiones_atencion', [
            'id' => $case['session']->id, 'autorizado' => true, 'autorizado_por' => $case['teacher']->id,
            'fecha_autorizacion' => now(), 'observacion_docente' => 'Avance revisado',
        ]);
    }

    public function test_teacher_completion_returns_json_and_closes_the_treatment(): void
    {
        $this->freezeTime();
        $case = $this->approvedProgress();
        $case['realized']->update([
            'estado' => 'finalizado', 'finalizado_por' => $case['student']->id, 'fecha_finalizacion' => now(),
        ]);

        $this->actingAs($case['teacher'])->postJson(route('tratamientos-realizados.culminar', $case['realized']->id))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $case['realized']->id, 'estado' => 'culminado', 'culminado_por' => $case['teacher']->id,
            'fecha_culminacion' => now(),
        ]);
    }

    public function test_read_only_supervision_displays_progress_without_operation_forms(): void
    {
        $case = $this->approvedProgress();
        $response = $this->actingAs($this->clinicalUser('jefatura-clinicas'))
            ->getJson(route('tratamientos-realizados.tabla'))->assertOk();

        $this->assertStringContainsString('Ver avances', $response->json('filas'));
        $this->assertStringNotContainsString('data-form-atencion', $response->json('filas'));
    }

    /** @param array<string, mixed> $case */
    private function createTreatment(array $case, string $state): TratamientoRealizado
    {
        return TratamientoRealizado::create([
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'estado' => $state, 'costo_cobrado' => 100,
        ]);
    }
}
