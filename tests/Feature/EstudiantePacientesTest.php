<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Esterilizacion;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\Remision;
use App\Services\FlujoTratamiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class EstudiantePacientesTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_student_menu_and_history_list_show_only_assigned_patients(): void
    {
        $case = $this->clinicalCase();
        $other = $this->clinicalCase();
        $other['admission']->paciente->update(['nombre' => 'Paciente ajeno']);

        $this->actingAs($case['student'])->get(route('estudiante.historias'))
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$case['history']->id])
            ->assertSee('Mis Pacientes Designados')->assertSee('Mis Historias Clínicas')->assertSee('Mis Pacientes Admitidos')
            ->assertSee('Pacientes derivados')->assertSee('Esterilización')->assertSee('data-estudiante-filtros', false)
            ->assertDontSee('Paciente ajeno');
    }

    public function test_history_ajax_search_returns_filtered_html_and_preserves_pagination_filters(): void
    {
        $case = $this->clinicalCase();
        for ($number = 0; $number < 15; $number++) {
            $case['history']->replicate()->save();
        }
        $other = $this->clinicalCase();
        $case['student']->especialidades()->attach($other['clinic']->id, ['habilitado' => true]);
        $other['history']->update(['estudiante_id' => $case['student']->id]);
        $case['admission']->paciente->update(['nombre' => '<script>Paciente propio</script>']);

        $response = $this->actingAs($case['student'])->getJson(route('estudiante.historias', [
            'buscar' => $case['admission']->paciente->ci, 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'page' => 2,
        ]))->assertOk()->assertJsonPath('success', true)->assertJsonPath('total', 16);

        $this->assertStringContainsString('&lt;script&gt;Paciente propio&lt;/script&gt;', $response->json('tabla'));
        $this->assertStringNotContainsString('<script>Paciente propio</script>', $response->json('tabla'));
        $this->assertStringContainsString('buscar='.$case['admission']->paciente->ci, $response->json('tabla'));
        $this->assertStringNotContainsString('<html', $response->json('tabla'));
    }

    public function test_designated_patients_exclude_closed_histories_but_history_archive_keeps_them(): void
    {
        $case = $this->clinicalCase();
        $case['history']->update(['estado' => 'culminado']);

        $this->actingAs($case['student'])->getJson(route('estudiante.designados'))->assertJsonPath('total', 0);
        $this->getJson(route('estudiante.historias'))->assertJsonPath('total', 1);
        $this->getJson(route('estudiante.historia', $case['history']->id))->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_derived_patients_ajax_list_excludes_other_students_and_disabled_referrals(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->assignedReferral($case);
        $disabled = $referral->replicate();
        $disabled->habilitado = false;
        $disabled->save();
        $this->assignedReferral($this->clinicalCase());

        $response = $this->actingAs($case['student'])->getJson(route('estudiante.derivados'))
            ->assertOk()->assertJsonPath('total', 1);

        $this->assertStringContainsString(route('estudiante.historia', $case['history']->id), $response->json('tabla'));
    }

    public function test_admitted_patients_include_own_admissions_and_exclude_cases_only_referred_to_student(): void
    {
        $case = $this->clinicalCase();
        $other = $this->clinicalCase();
        $other['admission']->update(['registrado_por' => $case['student']->id]);
        $referred = $this->clinicalCase();
        $this->assignedReferral($referred, ['estudiante_id' => $case['student']->id]);

        $this->actingAs($case['student'])->get(route('estudiante.admitidos'))
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$other['admission']->id, $case['admission']->id]);
        $this->getJson(route('estudiante.admitidos', ['buscar' => $other['admission']->paciente->ci]))
            ->assertOk()->assertJsonPath('total', 1);
    }

    public function test_student_cannot_read_another_students_history_through_ajax(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($this->clinicalUser('estudiante'))->getJson(route('estudiante.historia', $case['history']->id))->assertNotFound();
    }

    public function test_teacher_cannot_enter_student_portal_even_with_history_permission(): void
    {
        $this->actingAs($this->clinicalUser('docente'))->get(route('estudiante.historias'))->assertForbidden();
    }

    public function test_student_without_read_permission_cannot_enter_history_portal(): void
    {
        $student = $this->clinicalUser('estudiante');
        $student->roles->first()->revokePermissionTo('historias.ver');
        $student->unsetRelation('roles');

        $this->actingAs($student)->get(route('estudiante.historias'))->assertForbidden();
    }

    public function test_unauthenticated_student_page_redirects_to_login(): void
    {
        $this->get(route('estudiante.historias'))->assertRedirect(route('auth.login'));
    }

    public function test_intern_can_access_own_student_portal(): void
    {
        $intern = $this->clinicalUser('interno');

        $this->actingAs($intern)->getJson(route('estudiante.historias'))->assertOk()->assertJsonPath('total', 0);
    }

    public function test_history_detail_shows_pending_teacher_review_and_accepts_own_attachment_via_ajax(): void
    {
        $case = $this->clinicalCase();
        $case['history']->update(['autorizado' => false, 'estado' => 'revision']);
        Storage::fake('local');

        $response = $this->actingAs($case['student'])->getJson(route('estudiante.historia', $case['history']->id))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('Pendiente de revisión docente', $response->json('detalle'));
        $this->assertStringNotContainsString('Enviar propuesta al docente', $response->json('detalle'));

        $this->postJson(route('historias.archivos.subir', $case['history']->id), ['archivo' => UploadedFile::fake()->image('historia.jpg')])
            ->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('archivos', ['historia_clinica_id' => $case['history']->id, 'nombre_original' => 'historia.jpg']);
        Storage::disk('local')->assertExists($case['history']->archivos()->firstOrFail()->ruta);
    }

    public function test_student_cannot_upload_attachment_to_another_students_history(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($this->clinicalUser('estudiante'))->postJson(route('historias.archivos.subir', $case['history']->id),
            ['archivo' => UploadedFile::fake()->image('ajeno.jpg')])->assertNotFound();

        $this->assertDatabaseMissing('archivos', ['historia_clinica_id' => $case['history']->id]);
    }

    public function test_student_proposes_treatment_and_registers_paid_progress_via_ajax(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();

        $response = $this->actingAs($case['student'])->postJson(route('tratamientos-realizados.store'), [
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'pieza_dental' => '16',
        ])->assertCreated()->assertJsonPath('success', true);
        $id = $response->json('id');
        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $id, 'historia_clinica_id' => $case['history']->id, 'estado' => 'pendiente']);

        $treatment = $case['history']->tratamientosRealizados()->findOrFail($id);
        app(FlujoTratamiento::class)->autorizar($treatment, $case['teacher']);
        $cash = $this->payment($case, ['tratamiento_realizado_id' => $id, 'recibo' => 987654, 'cancelado' => 100]);
        $cash->detalles()->create(['tratamiento_realizado_id' => $id, 'concepto' => 'Pago', 'cantidad' => 1, 'precio_unitario' => 100, 'monto' => 100, 'fecha' => today()]);

        $this->postJson(route('sesiones-atencion.store', $id), ['fecha' => today()->toDateString(), 'descripcion' => 'Avance AJAX'])
            ->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('sesiones_atencion', ['tratamiento_realizado_id' => $id, 'descripcion' => 'Avance AJAX', 'autorizado' => false]);
    }

    public function test_detail_shows_only_supplies_and_payments_from_the_current_history(): void
    {
        $case = $this->approvedProgress();
        $other = $this->approvedProgress();
        $case['student']->especialidades()->attach($other['clinic']->id, ['habilitado' => true]);
        $other['history']->update(['estudiante_id' => $case['student']->id]);
        $type = InsumoTratamiento::create(['clinica_id' => $case['clinic']->id, 'nombre' => 'Tipo caso', 'descripcion' => 'Material']);
        $material = Insumo::create(['nombre' => '<script>Material propio</script>', 'clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id, 'cantidad' => 'unidades', 'cantidad_numerica' => 10]);
        $request = InsumoSolicitado::create(['usuario_id' => $case['student']->id, 'clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id,
            'tratamiento_realizado_id' => $case['realized']->id, 'sesion_atencion_id' => $case['session']->id, 'estado' => 'parcial']);
        $request->detalles()->create(['insumo_id' => $material->id, 'cantidad_solicitada' => 5, 'cantidad_entregada' => 2]);
        $otherRequest = $request->replicate();
        $otherRequest->tratamiento_realizado_id = $other['realized']->id;
        $otherRequest->sesion_atencion_id = $other['session']->id;
        $otherRequest->save();
        $cash = $this->payment($case, ['tratamiento_realizado_id' => $case['realized']->id, 'recibo' => 765432, 'descripcion' => 'Pago propio']);
        $this->payment($other, ['tratamiento_realizado_id' => $other['realized']->id, 'recibo' => 765431, 'descripcion' => 'Pago ajeno']);

        $this->actingAs($case['student'])->get(route('estudiante.historia', $case['history']->id))
            ->assertViewHas('solicitudes', fn ($requests): bool => $requests->modelKeys() === [$request->id])
            ->assertViewHas('pagos', fn ($payments): bool => $payments->modelKeys() === [$cash->id])
            ->assertSee('&lt;script&gt;Material propio&lt;/script&gt;', false)->assertDontSee('<script>Material propio</script>', false)
            ->assertSee('Guardar cantidades utilizadas')->assertSee('Pago propio')->assertDontSee('Pago ajeno');
    }

    public function test_student_creates_sterilization_control_for_the_assigned_patient_via_ajax(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->assignedReferral($case);

        $this->actingAs($case['student'])->postJson(route('esterilizacion.store'), [
            'remision_id' => $referral->id, 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id, 'usuario_id' => $case['student']->id,
            'cantidad_sobres' => 2, 'cantidad_paquetes' => 1, 'habilitada' => true,
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('esterilizaciones', ['remision_id' => $referral->id, 'estudiante_id' => $case['student']->id, 'autorizado' => false, 'cantidad_sobres' => 2]);
    }

    public function test_ajax_filter_rejects_invalid_specialty(): void
    {
        $this->actingAs($this->clinicalUser('estudiante'))->getJson(route('estudiante.historias', ['clinica_id' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
    }

    public function test_sterilization_control_rejects_a_teacher_different_from_patient_assignment(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->assignedReferral($case);
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        $this->actingAs($case['student'])->postJson(route('esterilizacion.store'), [
            'remision_id' => $referral->id, 'estudiante_id' => $case['student']->id, 'docente_id' => $otherTeacher->id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id, 'usuario_id' => $case['student']->id,
            'cantidad_sobres' => 2, 'cantidad_paquetes' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('docente_id')
            ->assertJsonPath('errors.docente_id.0', 'El control debe corresponder a la asignación del paciente.');

        $this->assertDatabaseMissing('esterilizaciones', ['remision_id' => $referral->id]);
    }

    public function test_history_detail_excludes_sterilization_controls_of_other_patients_in_the_same_specialty(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->assignedReferral($case);
        $otherReferral = $referral->replicate();
        $otherReferral->save();
        $fields = ['estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'usuario_id' => $case['student']->id, 'cantidad_sobres' => 1, 'cantidad_paquetes' => 0, 'habilitada' => true];
        $own = Esterilizacion::create($fields + ['remision_id' => $referral->id, 'descripcion' => 'Material del paciente']);
        $general = Esterilizacion::create($fields + ['descripcion' => 'Material general']);
        Esterilizacion::create($fields + ['remision_id' => $otherReferral->id, 'descripcion' => 'Material de otro paciente']);

        $this->actingAs($case['student'])->get(route('estudiante.historia', $case['history']->id))
            ->assertViewHas('esterilizaciones', fn ($records): bool => $records->modelKeys() === [$general->id, $own->id])
            ->assertSee('Material del paciente')->assertSee('Material general')->assertDontSee('Material de otro paciente');
    }

    public function test_student_finalizes_a_treatment_via_ajax_and_leaves_teacher_confirmation_pending(): void
    {
        $this->freezeTime();
        $case = $this->approvedProgress();
        $cash = $this->payment($case, ['recibo' => 765430, 'cancelado' => 100]);
        $cash->detalles()->create(['tratamiento_realizado_id' => $case['realized']->id, 'concepto' => 'Tratamiento', 'monto' => 100]);

        $this->actingAs($case['student'])->postJson(route('tratamientos-realizados.finalizar', $case['realized']->id))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $case['realized']->id, 'estado' => 'finalizado',
            'finalizado_por' => $case['student']->id, 'fecha_finalizacion' => now(), 'culminado_por' => null]);
    }

    /** @param array<string, mixed> $case
     * @param  array<string, mixed>  $overrides
     */
    private function assignedReferral(array $case, array $overrides = []): Remision
    {
        $referral = Remision::create(array_replace([
            'admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'estado' => 'asignada', 'habilitado' => true, 'fecha_asignacion' => now(),
        ], $overrides));
        $case['history']->update(['remision_id' => $referral->id]);

        return $referral;
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  array<string, mixed>  $overrides
     */
    private function payment(array $case, array $overrides): Caja
    {
        return Caja::create(array_replace([
            'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
            'usuario_id' => $case['teacher']->id, 'costo_total' => 100, 'cancelado' => 25, 'habilitada' => true,
            'monto' => 100, 'ci' => 0, 'descripcion' => '', 'tipo' => '', 'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => 'Paciente', 'grado' => '',
        ], $overrides));
    }
}
