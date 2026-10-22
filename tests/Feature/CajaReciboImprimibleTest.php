<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\SolicitudRadiologica;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class CajaReciboImprimibleTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_cashier_can_print_an_individual_patient_receipt_with_the_current_balance(): void
    {
        $case = $this->approvedProgress();
        $case['history']->paciente->update(['ci' => '8456123', 'nombre' => 'María', 'apellido_paterno' => 'Flores', 'apellido_materno' => 'Quispe']);
        $case['realized']->update(['costo_cobrado' => 120]);
        $cashier = $this->clinicalUser('cajas');
        $receipt = $this->receipt($case, ['recibo' => 780001, 'cancelado' => 40, 'usuario_id' => $cashier->id]);
        $this->receipt($case, ['recibo' => 780002, 'cancelado' => 35, 'usuario_id' => $cashier->id]);

        $response = $this->actingAs($cashier)->get(route('cajas.recibo', $receipt));

        $response->assertOk()
            ->assertViewIs('cajas.recibo')
            ->assertSee(asset('images/login/logo.png'), false)
            ->assertSee('Recibo de pago')
            ->assertSee('#780001')
            ->assertSee('María Flores Quispe')
            ->assertSee('8456123')
            ->assertSee('Ficha de admisión')
            ->assertSee('#'.$case['admission']->id)
            ->assertSee('Tratamiento prueba')
            ->assertSee('Bs 40.00')
            ->assertSee('Bs 75.00')
            ->assertSee('Bs 45.00')
            ->assertSee('Imprimir recibo');

        $this->get(route('cajas.index'))
            ->assertSee(route('cajas.recibo', $receipt), false)
            ->assertSee('Imprimir');
    }

    public function test_receipt_printing_requires_authentication_and_a_cash_permission(): void
    {
        $case = $this->approvedProgress();
        $receipt = $this->receipt($case);

        $this->get(route('cajas.recibo', $receipt))->assertRedirectToRoute('auth.login');

        $student = $this->clinicalUser('estudiante');
        $this->actingAs($student)->get(route('cajas.recibo', $receipt))->assertForbidden();

        $student->givePermissionTo('cajas.crear');
        $this->get(route('cajas.recibo', $receipt))->assertOk();
    }

    public function test_void_receipt_is_marked_and_patient_content_is_escaped(): void
    {
        $case = $this->approvedProgress();
        $case['history']->paciente->update(['nombre' => '<script>alert(1)</script>']);
        $receipt = $this->receipt($case, ['habilitada' => false]);

        $response = $this->actingAs($this->clinicalUser('cajas'))->get(route('cajas.recibo', $receipt));

        $response->assertOk()
            ->assertSee('ANULADO')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_radiology_payment_has_an_individual_printable_receipt(): void
    {
        $case = $this->clinicalCase();
        $cashier = $this->clinicalUser('cajas');
        $request = SolicitudRadiologica::create([
            'historia_clinica_id' => $case['history']->id,
            'paciente_id' => $case['history']->paciente_id,
            'clinica_id' => $case['clinic']->id,
            'solicitante_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id,
            'revisado_por' => $case['teacher']->id,
            'estado' => SolicitudRadiologica::AUTORIZADA,
            'descripcion' => 'Radiografía panorámica completa',
            'costo_autorizado' => 80,
            'fecha_revision' => now(),
        ]);
        $receipt = Caja::create([
            'solicitud_radiologica_id' => $request->id,
            'paciente_id' => $case['history']->paciente_id,
            'periodo_id' => $case['history']->periodo_id,
            'clinica_id' => $case['clinic']->id,
            'usuario_id' => $cashier->id,
            'recibo' => 780003,
            'monto' => 0,
            'costo_total' => 80,
            'cancelado' => 50,
            'habilitada' => true,
            'descripcion' => 'Radiografía panorámica completa',
            'ci' => 0,
            'tipo' => 'radiologia',
            'tipo_pago' => 'QR',
            'paciente_nombre_completo' => $case['history']->paciente->nombre_completo,
        ]);

        $this->actingAs($cashier)->get(route('cajas.recibo', $receipt))
            ->assertOk()
            ->assertSee('Estudio radiológico')
            ->assertSee('Radiografía panorámica completa')
            ->assertSee('Bs 50.00')
            ->assertSee('Bs 30.00');
    }

    /** @param array<string, mixed> $case */
    private function receipt(array $case, array $attributes = []): Caja
    {
        return Caja::create(array_merge([
            'tratamiento_realizado_id' => $case['realized']->id,
            'paciente_id' => $case['history']->paciente_id,
            'periodo_id' => $case['history']->periodo_id,
            'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id,
            'usuario_id' => $case['teacher']->id,
            'recibo' => 780000,
            'monto' => 0,
            'costo_total' => 120,
            'cancelado' => 40,
            'habilitada' => true,
            'descripcion' => 'Pago de tratamiento',
            'ci' => 0,
            'tipo' => 'tratamiento',
            'tipo_pago' => 'Efectivo',
            'paciente_nombre_completo' => $case['history']->paciente->nombre_completo,
        ], $attributes));
    }
}
