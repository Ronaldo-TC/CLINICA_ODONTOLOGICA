<?php

namespace Tests\Feature;

use App\Models\SesionAtencion;
use App\Services\FlujoTratamiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class TreatmentProposalReviewTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_teacher_rejects_a_pending_proposal_with_a_reason(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
            'observacion' => 'Requiere evaluación previa.',
        ]);

        $response = $this->actingAs($case['teacher'])->postJson(
            route('tratamientos-realizados.rechazar', $proposal),
            ['motivo' => 'Debe corregir la pieza dental indicada.'],
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Propuesta rechazada. El estudiante puede corregirla y reenviarla.');
        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $proposal->id,
            'estado' => 'rechazado',
        ]);
        $this->assertStringContainsString('Debe corregir la pieza dental indicada.', (string) $proposal->fresh()->observacion);
    }

    public function test_rejection_requires_a_reason_and_keeps_the_proposal_pending(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);

        $response = $this->actingAs($case['teacher'])->postJson(
            route('tratamientos-realizados.rechazar', $proposal),
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('motivo')
            ->assertJsonPath('errors.motivo.0', 'Debe indicar el motivo del rechazo.');
        $this->assertSame('pendiente', $proposal->fresh()->estado);
    }

    public function test_student_corrects_and_resubmits_a_rejected_proposal(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);
        app(FlujoTratamiento::class)->rechazar($proposal, $case['teacher'], 'Corrija la observación clínica.');

        $response = $this->actingAs($case['student'])->putJson(
            route('tratamientos-realizados.update', $proposal),
            [
                'tratamiento_id' => $case['treatment']->id,
                'pieza_dental' => '26',
                'observacion' => 'Observación clínica corregida.',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Propuesta actualizada y enviada para revisión docente.');
        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $proposal->id,
            'estado' => 'pendiente',
            'pieza_dental' => '26',
            'observacion' => 'Observación clínica corregida.',
        ]);
    }

    public function test_student_deletes_a_pending_proposal(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);

        $response = $this->actingAs($case['student'])->deleteJson(
            route('tratamientos-realizados.destroy', $proposal),
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Propuesta eliminada.');
        $this->assertModelMissing($proposal);
    }

    public function test_student_deletes_a_rejected_proposal(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);
        app(FlujoTratamiento::class)->rechazar($proposal, $case['teacher'], 'Seleccione otra alternativa.');

        $response = $this->actingAs($case['student'])->deleteJson(
            route('tratamientos-realizados.destroy', $proposal),
        );

        $response->assertOk();
        $this->assertModelMissing($proposal);
    }

    public function test_student_cannot_delete_an_approved_proposal(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);
        app(FlujoTratamiento::class)->autorizar($proposal, $case['teacher']);

        $response = $this->actingAs($case['student'])->deleteJson(
            route('tratamientos-realizados.destroy', $proposal),
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->assertModelExists($proposal);
        $this->assertSame('autorizado', $proposal->fresh()->estado);
    }

    public function test_student_cannot_delete_a_proposal_with_clinical_records(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);
        $session = SesionAtencion::query()->create([
            'tratamiento_realizado_id' => $proposal->id,
            'estudiante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id,
            'fecha' => today(),
            'descripcion' => 'Registro clínico existente.',
            'autorizado' => false,
        ]);

        $response = $this->actingAs($case['student'])->deleteJson(
            route('tratamientos-realizados.destroy', $proposal),
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->assertModelExists($proposal);
        $this->assertModelExists($session);
    }

    public function test_another_student_cannot_delete_the_proposal(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], [
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ]);
        $otherStudent = $this->clinicalUser('estudiante');

        $response = $this->actingAs($otherStudent)->deleteJson(
            route('tratamientos-realizados.destroy', $proposal),
        );

        $response->assertNotFound();
        $this->assertModelExists($proposal);
    }
}
