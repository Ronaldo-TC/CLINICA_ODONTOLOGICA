<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Esterilizacion;
use App\Models\Remision;
use App\Services\FlujoTratamiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class ServiciosClinicosTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_cash_receipt_requires_teacher_approval_and_uses_authorized_cost(): void
    {
        $case = $this->clinicalCase();
        $flow = app(FlujoTratamiento::class);
        $proposal = $flow->proponer($case['student'], ['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id]);
        $cashier = $this->clinicalUser('cajas');
        $payload = ['tratamiento_realizado_id' => $proposal->id, 'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id, 'usuario_id' => $cashier->id, 'recibo' => 123456, 'costo_total' => 1, 'cancelado' => 100];

        $this->actingAs($cashier)->postJson(route('cajas.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('tratamiento_realizado_id');
        $this->assertSame(0, Caja::where('tratamiento_realizado_id', $proposal->id)->count());
        $flow->autorizar($proposal, $case['teacher']);
        $response = $this->postJson(route('cajas.store'), $payload)->assertCreated();

        $cash = Caja::findOrFail($response->json('id'));
        $this->assertSame('100.00', $cash->costo_total);
        $this->assertSame($case['admission']->paciente_id, $cash->paciente_id);
        $this->assertTrue($flow->tienePago($proposal));
        $this->assertSame($proposal->id, $cash->detalles()->firstOrFail()->tratamiento_realizado_id);
    }

    public function test_sterilization_requires_assigned_teacher_signature_before_receipt_and_pickup(): void
    {
        $case = $this->clinicalCase();
        $staff = $this->clinicalUser('esterilizacion');
        $record = Esterilizacion::create(['cantidad_sobres' => 2, 'cantidad_paquetes' => 1, 'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id, 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id, 'usuario_id' => $staff->id]);

        $this->actingAs($staff)->postJson(route('esterilizacion.movimiento', $record->id), ['accion' => 'recibir'])->assertUnprocessable();
        $this->assertNull($record->fresh()->fecha_entrega);
        $this->actingAs($case['teacher'])->post(route('esterilizacion.autorizar', $record->id), ['firma_docente' => 'data:image/png;base64,AAAA'])->assertRedirect();
        $this->actingAs($staff)->post(route('esterilizacion.movimiento', $record->id), ['accion' => 'recibir'])->assertRedirect();
        $this->assertNotNull($record->fresh()->hora_entrega);
        $this->post(route('esterilizacion.movimiento', $record->id), ['accion' => 'entregar'])->assertRedirect();
        $this->assertSame($staff->id, $record->fresh()->entregado_por);
        $this->assertNotNull($record->fresh()->hora_recojo);
        $this->postJson(route('esterilizacion.movimiento', $record->id), ['accion' => 'entregar'])->assertUnprocessable();
    }

    public function test_remission_assignment_creates_history_with_consistent_specialty(): void
    {
        $case = $this->clinicalCase();
        $record = Remision::create(['admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id, 'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id]);
        $staff = $this->clinicalUser('admision');

        $this->actingAs($staff)->post(route('remisiones.asignar', $record->id), ['estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id])->assertRedirect(route('historias.index'));

        $history = $record->historiasClinicas()->firstOrFail();
        $this->assertFalse($history->autorizado);
        $this->assertSame($case['clinic']->id, $history->especialidad_docente);
        $this->actingAs($case['student'])->post(route('historias.autorizar', $history->id))->assertForbidden();
        $this->actingAs($case['teacher'])->post(route('historias.autorizar', $history->id))->assertRedirect();
        $this->assertTrue($history->fresh()->autorizado);
    }
}
