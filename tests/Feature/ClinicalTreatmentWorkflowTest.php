<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Services\FlujoTratamiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class ClinicalTreatmentWorkflowTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_teacher_approval_and_payment_are_required_before_registering_progress(): void
    {
        $case = $this->clinicalCase();
        $flow = app(FlujoTratamiento::class);
        $proposal = $flow->proponer($case['student'], ['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'pieza_dental' => '16']);

        $this->actingAs($case['student'])->postJson(route('sesiones-atencion.store', $proposal->id), ['fecha' => today()->toDateString(), 'descripcion' => 'Avance'])->assertUnprocessable();
        $this->assertSame(0, $proposal->sesionesAtencion()->count());
        $this->actingAs($case['student'])->post(route('tratamientos-realizados.autorizar', $proposal->id))->assertForbidden();

        $this->actingAs($case['teacher'])->post(route('tratamientos-realizados.autorizar', $proposal->id))->assertRedirect();
        $this->actingAs($case['student'])->postJson(route('sesiones-atencion.store', $proposal->id), ['fecha' => today()->toDateString(), 'descripcion' => 'Avance'])->assertUnprocessable()->assertJsonValidationErrors('pago');
        $this->assertSame(0, $proposal->sesionesAtencion()->count());

        $cash = Caja::create(['tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id, 'usuario_id' => $case['teacher']->id, 'recibo' => 987654, 'cancelado' => 100, 'costo_total' => 100, 'habilitada' => true, 'monto' => 100, 'ci' => 0, 'descripcion' => '', 'tipo' => '', 'tipo_pago' => 'efectivo', 'paciente_nombre_completo' => 'Paciente', 'grado' => '']);
        $cash->detalles()->create(['tratamiento_realizado_id' => $proposal->id, 'concepto' => 'Tratamiento', 'monto' => 100]);
        $this->post(route('sesiones-atencion.store', $proposal->id), ['fecha' => today()->toDateString(), 'descripcion' => 'Avance'])->assertRedirect();

        $session = $proposal->sesionesAtencion()->firstOrFail();
        $this->assertFalse($session->autorizado);
        $this->actingAs($case['teacher'])->post(route('sesiones-atencion.autorizar', $session->id))->assertRedirect();
        $this->assertTrue($session->fresh()->autorizado);
        $this->actingAs($case['student'])->post(route('tratamientos-realizados.finalizar', $proposal->id))->assertRedirect();
        $this->assertSame('finalizado', $proposal->fresh()->estado);
        $this->actingAs($case['teacher'])->post(route('tratamientos-realizados.culminar', $proposal->id))->assertRedirect();
        $this->assertSame('culminado', $proposal->fresh()->estado);
    }

    public function test_an_unassigned_teacher_cannot_approve_a_treatment(): void
    {
        $case = $this->clinicalCase();
        $proposal = app(FlujoTratamiento::class)->proponer($case['student'], ['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id]);
        $other = $this->clinicalUser('docente');
        $other->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        $this->actingAs($other)->post(route('tratamientos-realizados.autorizar', $proposal->id))->assertNotFound();

        $this->assertSame('pendiente', $proposal->fresh()->estado);
    }

    public function test_student_cannot_change_the_treatment_catalog(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($case['student'])->postJson(route('tratamientos.store'), ['nombre' => 'Alterado', 'costo' => 1, 'clinica_id' => $case['clinic']->id])->assertForbidden();

        $this->assertSame('100.00', $case['treatment']->fresh()->costo);
    }
}
