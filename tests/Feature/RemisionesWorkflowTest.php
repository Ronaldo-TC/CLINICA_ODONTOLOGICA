<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class RemisionesWorkflowTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_admission_staff_sees_complete_patient_and_assignment_details(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['estado' => 'asignada', 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id]);
        $case['history']->update(['remision_id' => $referral->id]);

        $this->actingAs($this->clinicalUser('admision'))->get(route('remisiones.index', ['clinica_id' => $case['clinic']->id]))
            ->assertSee($referral->paciente->nombre_completo)
            ->assertSee($case['clinic']->especialidad)
            ->assertSee($case['student']->nombre_completo)
            ->assertSee($case['teacher']->nombre_completo)
            ->assertSee('Asignada');

        $this->getJson(route('remisiones.datos', $referral->id))->assertOk()
            ->assertJsonPath('registro.paciente.nombre_completo', $referral->paciente->nombre_completo)
            ->assertJsonPath('registro.admision.id', $case['admission']->id)
            ->assertJsonPath('registro.clinica.especialidad', $case['clinic']->especialidad)
            ->assertJsonPath('registro.periodo.id', $case['history']->periodo_id)
            ->assertJsonPath('registro.estudiante.nombre_completo', $case['student']->nombre_completo)
            ->assertJsonPath('registro.docente.nombre_completo', $case['teacher']->nombre_completo)
            ->assertJsonPath('registro.historias_clinicas_count', 1);
    }

    public function test_administrative_ajax_filters_workflow_state_and_maternal_surname(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->paciente->update(['apellido_materno' => 'Apellido exclusivo remisiones']);
        $referral = $this->pendingReferral($case, ['estado' => 'asignada']);
        $this->pendingReferral($case);

        $response = $this->actingAs($this->clinicalUser('admin'))->getJson(route('remisiones.tabla', [
            'buscar' => 'Apellido exclusivo remisiones', 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'flujo_estado' => 'asignada',
        ]));

        $response->assertOk()->assertJsonPath('total', 1)->assertJsonStructure(['filas', 'paginacion', 'total']);
        $this->assertStringContainsString('data-id="'.$referral->id.'"', $response->json('filas'));
        $this->assertStringContainsString('Asignada', $response->json('filas'));
    }

    public function test_teacher_cannot_enter_administrative_remissions_even_with_permission(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($case['teacher'])->get(route('remisiones.index'))->assertForbidden();
        $this->getJson(route('remisiones.tabla'))->assertForbidden();
        $this->getJson(route('remisiones.datos', $referral->id))->assertForbidden();
        $this->get(route('remisiones.asignacion', $referral->id))->assertForbidden();
    }

    public function test_unauthenticated_patient_inbox_redirects_to_login(): void
    {
        $this->get(route('pacientes-remitidos.index'))->assertRedirect(route('auth.login'));
    }

    public function test_teacher_with_director_role_keeps_global_referral_visibility(): void
    {
        $case = $this->clinicalCase();
        $case['teacher']->assignRole('director');
        $visible = $this->pendingReferral($case);
        $this->pendingReferral($case, ['habilitado' => false]);
        $otherClinic = Clinica::create(['especialidad' => 'Especialidad ajena remisiones', 'modulo' => 'Prueba', 'curso' => 1]);
        $otherVisible = $this->pendingReferral($case, ['clinica_id' => $otherClinic->id, 'docente_id' => $case['teacher']->id]);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.index', ['buscar' => $case['admission']->paciente->ci]))
            ->assertViewIs('pacientes-remitidos.index')
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$otherVisible->id, $visible->id])
            ->assertViewHas('clinicas', fn ($clinics): bool => $clinics->modelKeys() === Clinica::query()->orderBy('especialidad')->pluck('id')->all())
            ->assertSee('href="'.route('pacientes-remitidos.index').'"', false)
            ->assertSee('href="'.route('remisiones.index').'"', false);
    }

    public function test_admission_staff_cannot_enter_patient_inbox_even_with_read_permission(): void
    {
        $this->actingAs($this->clinicalUser('admision'))->get(route('pacientes-remitidos.index'))->assertForbidden();
    }

    public function test_admin_can_review_enabled_referrals_from_every_specialty(): void
    {
        $case = $this->clinicalCase();
        $first = $this->pendingReferral($case);
        $otherClinic = Clinica::create(['especialidad' => 'Otra especialidad para supervisión', 'modulo' => 'Prueba', 'curso' => 1]);
        $second = $this->pendingReferral($case, ['clinica_id' => $otherClinic->id]);
        $this->pendingReferral($case, ['habilitado' => false]);

        $this->actingAs($this->clinicalUser('admin'))->get(route('pacientes-remitidos.index', ['buscar' => $case['admission']->paciente->ci]))
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$second->id, $first->id]);
    }

    public function test_student_cannot_enter_patient_inbox_even_with_read_permission(): void
    {
        $student = $this->clinicalUser('estudiante');
        $student->givePermissionTo('remisiones.ver');

        $this->actingAs($student)->get(route('pacientes-remitidos.index'))->assertForbidden();
    }

    public function test_patient_inbox_filters_by_patient_and_workflow_state(): void
    {
        $case = $this->clinicalCase();
        $pending = $this->pendingReferral($case);
        $this->pendingReferral($case, ['estado' => 'asignada']);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.index', [
            'buscar' => $case['admission']->paciente->ci, 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'flujo_estado' => 'pendiente',
        ]))->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$pending->id]);
    }

    public function test_teacher_assignment_creates_consistent_history_with_the_current_teacher(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($case['teacher'])->post(route('pacientes-remitidos.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'asignado_por' => $case['student']->id,
            'estado' => 'finalizada', 'autorizado' => true,
        ])
            ->assertRedirect(route('pacientes-remitidos.index'));

        $this->assertDatabaseHas('remisiones', [
            'id' => $referral->id, 'estado' => 'asignada', 'estudiante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id, 'asignado_por' => $case['teacher']->id, 'fecha_asignacion' => now(),
        ]);
        $this->assertDatabaseHas('historias_clinicas', [
            'remision_id' => $referral->id, 'admision_id' => $case['admission']->id,
            'paciente_id' => $case['admission']->paciente_id, 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'estudiante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id, 'especialidad_estudiante' => $case['clinic']->id,
            'especialidad_docente' => $case['clinic']->id, 'estado' => 'abierta', 'autorizado' => true,
            'estado_revision' => true,
        ]);
    }

    public function test_patient_inbox_opens_assignment_form_in_a_modal(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.index'))
            ->assertOk()
            ->assertSee('id="modalAsignacionRemision"', false)
            ->assertSee('data-asignar-estudiante', false)
            ->assertDontSee('href="'.route('pacientes-remitidos.asignacion', $referral->id).'"', false);

        $response = $this->getJson(route('pacientes-remitidos.asignacion', $referral->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('titulo', 'Asignar estudiante');

        $this->assertStringContainsString('id="formAsignacionRemision"', $response->json('html'));
        $this->assertStringContainsString('Buscar estudiante por CI o nombre', $response->json('html'));
        $this->assertStringContainsString('data-estudiante-opcion', $response->json('html'));
        $this->assertStringNotContainsString('<select id="modal-asignacion-estudiante"', $response->json('html'));
        $this->assertStringContainsString($case['student']->nombre_completo, $response->json('html'));
        $this->assertStringContainsString('creará y autorizará automáticamente', $response->json('html'));
    }

    public function test_ajax_assignment_returns_the_authorized_history(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $response = $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Asignación registrada. La historia clínica fue creada y autorizada automáticamente.');

        $this->assertDatabaseHas('historias_clinicas', [
            'id' => $response->json('historia_id'),
            'remision_id' => $referral->id,
            'estado' => 'abierta',
            'autorizado' => true,
            'estado_revision' => true,
        ]);
    }

    public function test_assignment_form_only_offers_enabled_students_of_the_referral_specialty(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $unassigned = $this->clinicalUser('estudiante');
        $disabled = $this->clinicalUser('estudiante');
        $disabled->especialidades()->attach($case['clinic']->id, ['habilitado' => false]);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.asignacion', $referral->id))
            ->assertViewIs('pacientes-remitidos.asignar')
            ->assertViewHas('docenteActual', fn (Usuario $teacher): bool => $teacher->id === $case['teacher']->id)
            ->assertSee('Buscar estudiante por CI o nombre')
            ->assertSee($case['student']->nombre_completo)
            ->assertSee('CI '.$case['student']->ci)
            ->assertSee('data-estudiante-opcion', false)
            ->assertDontSee('<select id="asignacion-estudiante"', false)
            ->assertDontSee($unassigned->nombre_completo)
            ->assertDontSee($disabled->nombre_completo);
    }

    public function test_teacher_selects_period_for_a_referral_created_without_one(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['periodo_id' => null]);

        $this->actingAs($case['teacher'])->post(route('pacientes-remitidos.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'periodo_id' => $case['history']->periodo_id,
        ])->assertRedirect(route('pacientes-remitidos.index'));

        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'periodo_id' => $case['history']->periodo_id, 'estado' => 'asignada']);
        $this->assertDatabaseHas('historias_clinicas', ['remision_id' => $referral->id, 'periodo_id' => $case['history']->periodo_id]);
    }

    public function test_teacher_cannot_replace_an_existing_referral_period(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $otherPeriod = Periodo::create([
            'nombre' => 'Otro periodo remisiones', 'gestion' => Periodo::max('gestion') + 1,
            'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-12-31', 'habilitado' => true,
        ]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'periodo_id' => $otherPeriod->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('periodo_id')
            ->assertJsonPath('errors.periodo_id.0', 'Debe conservar el periodo de la remisión.');

        $this->assertReferralPending($referral);
        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'periodo_id' => $case['history']->periodo_id]);
    }

    public function test_teacher_cannot_assign_another_teacher_as_responsible(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'docente_id' => $otherTeacher->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('docente_id')
            ->assertJsonPath('errors.docente_id.0', 'El docente responsable debe ser quien realiza la asignación.');

        $this->assertReferralPending($referral);
    }

    public function test_teacher_cannot_assign_a_student_from_another_specialty(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $unassigned = $this->clinicalUser('estudiante');

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $unassigned->id])
            ->assertUnprocessable()->assertJsonValidationErrors('estudiante_id')
            ->assertJsonPath('errors.estudiante_id.0', 'Seleccione personal habilitado de esta especialidad.');

        $this->assertReferralPending($referral);
    }

    public function test_teacher_cannot_assign_a_disabled_student(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $case['student']->update(['habilitado' => false]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])
            ->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');

        $this->assertReferralPending($referral);
    }

    public function test_teacher_cannot_assign_a_student_with_disabled_specialty(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $case['student']->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])
            ->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');

        $this->assertReferralPending($referral);
    }

    public function test_revoked_teacher_specialty_hides_even_previously_assigned_referrals(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['docente_id' => $case['teacher']->id]);
        $case['teacher']->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.index'))
            ->assertViewHas('registros', fn ($records): bool => $records->total() === 0);
        $this->get(route('pacientes-remitidos.asignacion', $referral->id))->assertNotFound();
        $this->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])->assertNotFound();

        $this->assertDatabaseMissing('historias_clinicas', ['remision_id' => $referral->id]);
    }

    public function test_teacher_cannot_assign_an_inactive_referral(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['habilitado' => false]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])->assertUnprocessable();

        $this->assertReferralPending($referral);
        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'habilitado' => false]);
    }

    public function test_teacher_cannot_create_another_history_for_an_assigned_referral(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['estado' => 'asignada', 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id]);
        $case['history']->update(['remision_id' => $referral->id]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])->assertUnprocessable();

        $this->assertSame([$case['history']->id], $referral->historiasClinicas()->pluck('id')->all());
        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'estado' => 'asignada', 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id]);
    }

    public function test_administrative_edit_preserves_assigned_state_and_prevents_specialty_changes(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['estado' => 'asignada', 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id]);
        $payload = $referral->only(['admision_id', 'paciente_id', 'clinica_id', 'periodo_id']);

        $this->actingAs($this->clinicalUser('admin'))->putJson(route('remisiones.update', $referral->id), $payload + ['habilitado' => true])
            ->assertOk()->assertJsonPath('registro.estado', 'asignada');
        $otherClinic = Clinica::query()->whereKeyNot($case['clinic']->id)->firstOrFail();
        $this->putJson(route('remisiones.update', $referral->id), array_replace($payload, ['clinica_id' => $otherClinic->id, 'habilitado' => true]))
            ->assertUnprocessable()->assertJsonValidationErrors('clinica_id');

        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'estado' => 'asignada', 'clinica_id' => $case['clinic']->id]);
    }

    public function test_existing_history_prevents_assignment_even_with_pending_state(): void
    {
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $case['history']->update(['remision_id' => $referral->id]);

        $this->actingAs($case['teacher'])->postJson(route('pacientes-remitidos.asignar', $referral->id), ['estudiante_id' => $case['student']->id])->assertUnprocessable();

        $this->assertSame([$case['history']->id], $referral->historiasClinicas()->pluck('id')->all());
        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'estado' => 'pendiente', 'estudiante_id' => null, 'fecha_asignacion' => null]);
    }

    public function test_patient_inbox_escapes_patient_and_responsible_names(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->paciente->update(['nombre' => '<script>paciente</script>']);
        $case['student']->update(['nombre_completo' => '<script>estudiante</script>']);
        $this->pendingReferral($case, ['estado' => 'asignada', 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id]);

        $this->actingAs($case['teacher'])->get(route('pacientes-remitidos.index'))
            ->assertSee('&lt;script&gt;paciente&lt;/script&gt;', false)->assertDontSee('<script>paciente</script>', false)
            ->assertSee('&lt;script&gt;estudiante&lt;/script&gt;', false)->assertDontSee('<script>estudiante</script>', false);
    }

    /**
     * @param  array{student: Usuario, teacher: Usuario, clinic: Clinica, admission: Admision, history: HistoriaClinica, treatment: Tratamiento}  $case
     * @param  array<string, mixed>  $overrides
     */
    private function pendingReferral(array $case, array $overrides = []): Remision
    {
        return Remision::create(array_replace([
            'admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'estado' => 'pendiente', 'habilitado' => true,
        ], $overrides));
    }

    private function assertReferralPending(Remision $referral): void
    {
        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'estado' => 'pendiente', 'estudiante_id' => null, 'fecha_asignacion' => null]);
        $this->assertDatabaseMissing('historias_clinicas', ['remision_id' => $referral->id]);
    }
}
