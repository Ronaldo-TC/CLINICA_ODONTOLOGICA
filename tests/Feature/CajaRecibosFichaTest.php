<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class CajaRecibosFichaTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_cashier_finds_assigned_student_patient_and_authorized_treatments_by_admission_number(): void
    {
        $case = $this->approvedProgress();
        $this->clinicalCase();

        $response = $this->actingAs($this->clinicalUser('cajas'))->getJson(route('cajas.buscar-ficha', ['ficha' => $case['admission']->id, 'clinica_id' => $case['clinic']->id]))
            ->assertOk()->assertJsonPath('ficha', $case['admission']->id)->assertJsonPath('atenciones.0.estudiante', $case['student']->nombre_completo)
            ->assertJsonPath('atenciones.0.paciente', $case['history']->paciente->nombre_completo)->assertJsonPath('atenciones.0.id', $case['realized']->id);

        $this->assertCount(1, $response->json('atenciones'));
        $this->get(route('cajas.index'))->assertOk()->assertSee('Número de ficha de admisión')->assertSee('Precios de tratamientos por clínica');
    }

    public function test_multiple_receipts_accumulate_payments_and_cannot_exceed_authorized_balance(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);
        $this->actingAs($this->clinicalUser('cajas'));
        $first = $this->postJson(route('cajas.store'), $this->receipt($case, 1900000101, 30))->assertCreated()->json('id');
        $this->postJson(route('cajas.store'), $this->receipt($case, 1900000102, 25))->assertCreated();

        $response = $this->getJson(route('cajas.buscar-ficha', ['ficha' => $case['admission']->id, 'clinica_id' => $case['clinic']->id]))->assertOk();
        $this->assertSame(55.0, (float) $response->json('atenciones.0.cancelado_anterior'));
        $this->assertSame(45, $response->json('atenciones.0.saldo'));
        $this->assertSame('30.00', Caja::findOrFail($first)->cancelado);
        $this->postJson(route('cajas.store'), $this->receipt($case, 1900000103, 50))->assertUnprocessable()->assertJsonValidationErrors('cancelado');
        $this->assertSame(2, Caja::query()->where('tratamiento_realizado_id', $case['realized']->id)->count());
        $this->getJson(route('cajas.tabla', ['clinica_id' => $case['clinic']->id]))->assertOk()->assertJsonPath('total', 2);
    }

    public function test_duplicate_receipt_number_returns_422_without_adding_payment(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);
        $this->actingAs($this->clinicalUser('cajas'))->postJson(route('cajas.store'), $this->receipt($case, 1900000104, 10))->assertCreated();

        $this->postJson(route('cajas.store'), $this->receipt($case, 1900000104, 10))->assertUnprocessable()->assertJsonValidationErrors('recibo');

        $this->assertSame(1, Caja::query()->where('tratamiento_realizado_id', $case['realized']->id)->count());
    }

    public function test_receipt_uses_case_data_and_rejects_wrong_admission_or_clinic(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);
        $other = $this->clinicalCase();
        $this->actingAs($this->clinicalUser('cajas'));

        $this->postJson(route('cajas.store'), [...$this->receipt($case, 1900000105, 10), 'ficha' => $other['admission']->id])->assertUnprocessable()->assertJsonValidationErrors('ficha');
        $this->postJson(route('cajas.store'), [...$this->receipt($case, 1900000105, 10), 'clinica_id' => $other['clinic']->id])->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
        $response = $this->postJson(route('cajas.store'), [...$this->receipt($case, 1900000105, 10), 'paciente_nombre_completo' => 'Paciente inventado', 'costo_total' => 1, 'usuario_id' => $case['student']->id])->assertCreated();
        $this->assertDatabaseHas('cajas', ['id' => $response->json('id'), 'paciente_id' => $case['history']->paciente_id, 'costo_total' => 100, 'usuario_id' => auth()->id()]);
    }

    public function test_disabled_receipts_are_excluded_and_edit_cannot_overpay(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);
        $this->actingAs($this->clinicalUser('cajas'));
        $first = $this->postJson(route('cajas.store'), $this->receipt($case, 1900000106, 30))->assertCreated()->json('id');
        $this->postJson(route('cajas.store'), [...$this->receipt($case, 1900000107, 100), 'habilitada' => false])->assertCreated();
        $this->postJson(route('cajas.store'), $this->receipt($case, 1900000108, 50))->assertCreated();

        $this->putJson(route('cajas.update', $first), $this->receipt($case, 1900000106, 60))->assertUnprocessable()->assertJsonValidationErrors('cancelado');

        $this->assertSame('30.00', Caja::findOrFail($first)->cancelado);
        $response = $this->getJson(route('cajas.datos', $first))->assertOk();
        $this->assertSame(50.0, (float) $response->json('cancelado_anterior'));
        $this->assertSame($case['student']->nombre_completo, $response->json('atencion.historia_clinica.estudiante.nombre_completo'));
    }

    public function test_missing_ficha_or_wrong_clinic_returns_404_and_student_cannot_read_cash_lookup(): void
    {
        $case = $this->approvedProgress();
        $otherClinic = Clinica::query()->where('id', '!=', $case['clinic']->id)->firstOrFail();
        $this->actingAs($this->clinicalUser('cajas'))->getJson(route('cajas.buscar-ficha', ['ficha' => $case['admission']->id, 'clinica_id' => $otherClinic->id]))->assertNotFound();
        $this->actingAs($case['student'])->getJson(route('cajas.buscar-ficha', ['ficha' => $case['admission']->id]))->assertForbidden();
    }

    /** @param array<string, mixed> $case @return array<string, mixed> */
    private function receipt(array $case, int $number, int $amount): array
    {
        return ['tratamiento_realizado_id' => $case['realized']->id, 'ficha' => $case['admission']->id, 'clinica_id' => $case['clinic']->id,
            'recibo' => $number, 'cancelado' => $amount, 'habilitada' => true];
    }
}
