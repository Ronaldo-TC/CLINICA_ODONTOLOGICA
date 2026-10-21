<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\TratamientoRealizado;
use App\Services\AsignacionRemision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class FlujoClinicoIntegralTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_referral_assignment_transfers_the_admission_proposal_and_cannot_be_taken_twice(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->clinicalUser('jefatura-clinicas');
        $referral = Remision::create([
            'admision_id' => $case['admission']->id,
            'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id,
            'tratamiento_propuesto_id' => $case['treatment']->id,
            'observacion_propuesta' => 'Evaluar pieza 16 antes de iniciar.',
            'estado' => 'pendiente',
            'habilitado' => true,
        ]);

        $history = app(AsignacionRemision::class)->asignar(
            $referral,
            $chief,
            $case['student']->id,
            $case['teacher']->id,
            $case['history']->periodo_id,
        );

        $this->assertDatabaseHas('remisiones', [
            'id' => $referral->id,
            'estado' => 'asignada',
            'asignado_por' => $chief->id,
        ]);
        $proposal = TratamientoRealizado::query()
            ->where('historia_clinica_id', $history->id)
            ->where('tratamiento_id', $case['treatment']->id)
            ->sole();
        $this->assertSame('pendiente', $proposal->estado);
        $this->assertStringContainsString('Evaluar pieza 16 antes de iniciar.', $proposal->observacion);
        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $case['student']->id,
            'tipo' => 'remision_nueva',
        ]);

        try {
            app(AsignacionRemision::class)->asignar(
                $referral->fresh(),
                $chief,
                $case['student']->id,
                $case['teacher']->id,
                $case['history']->periodo_id,
            );
            $this->fail('La derivaciÃ³n no puede asignarse dos veces.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertSame(1, Remision::findOrFail($referral->id)->historiasClinicas()->count());
    }

    public function test_radiology_requires_the_specific_service_to_be_fully_paid_and_shares_it_only_with_the_patients_clinics(): void
    {
        $case = $this->clinicalCase();
        $operator = $this->clinicalUser('radiologia');
        $operator->givePermissionTo(['radiologias.ver', 'radiologias.crear']);
        $treatment = $this->authorizedTreatment($case);
        $firstReceipt = $this->receipt($case, $treatment, $operator->id, 40, 'radiologia');
        $payload = [
            'descripcion' => 'RadiografÃ­a periapical pieza 16',
            'estado' => 'pendiente',
            'caja_id' => $firstReceipt->id,
            'clinica_id' => $case['clinic']->id,
        ];

        $this->actingAs($operator)->postJson(route('radiologia.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('caja_id');
        $this->assertSame('parcial', $treatment->fresh()->resumenPago()['estado']);

        $this->receipt($case, $treatment, $operator->id, 60, 'radiologia');
        $response = $this->postJson(route('radiologia.store'), $payload)->assertCreated();
        $study = Radiologia::findOrFail($response->json('id'));
        $this->assertSame('pagado', $treatment->fresh()->resumenPago()['estado']);

        $otherClinic = $this->clinicalCase();
        $otherClinic['admission']->update(['paciente_id' => $case['history']->paciente_id]);
        $otherClinic['history']->update(['paciente_id' => $case['history']->paciente_id]);
        $otherClinic['student']->givePermissionTo('radiologias.ver');
        $unrelatedStudent = $this->clinicalUser('estudiante');
        $unrelatedStudent->givePermissionTo('radiologias.ver');

        $this->actingAs($otherClinic['student'])->getJson(route('radiologia.datos', $study))->assertOk();
        $this->actingAs($unrelatedStudent)->getJson(route('radiologia.datos', $study))->assertNotFound();
    }

    public function test_restock_requires_two_successive_approvals_and_only_increases_stock_on_receipt(): void
    {
        $case = $this->clinicalCase();
        $type = InsumoTratamiento::create(['nombre' => 'ReposiciÃ³n', 'clinica_id' => $case['clinic']->id]);
        $supply = Insumo::create([
            'nombre' => 'Guantes de reposiciÃ³n',
            'clinica_id' => $case['clinic']->id,
            'insumo_tratamiento_id' => $type->id,
            'cantidad' => 'cajas',
            'cantidad_numerica' => 3,
            'stock_minimo' => 3,
            'costo_unitario' => 20,
        ]);
        $warehouse = $this->clinicalUser('insumos');
        $chief = $this->clinicalUser('jefatura-clinicas');
        $director = $this->clinicalUser('director');
        $inStockSupply = Insumo::create([
            'nombre' => 'Material con stock suficiente',
            'clinica_id' => $case['clinic']->id,
            'insumo_tratamiento_id' => $type->id,
            'cantidad' => 'cajas',
            'cantidad_numerica' => 5,
            'stock_minimo' => 3,
            'costo_unitario' => 20,
        ]);
        $payload = [
            'observaciones' => 'ReposiciÃ³n preventiva por alcanzar el stock mÃ­nimo.',
            'detalles' => [[
                'insumo_id' => $supply->id,
                'cantidad_solicitada' => 5,
            ]],
        ];

        $this->actingAs($warehouse)
            ->get(route('insumos.reposiciones'))
            ->assertSee('Guantes de reposiciÃ³n')
            ->assertDontSee($inStockSupply->nombre);

        $this->postJson(route('insumos.reposiciones-crear'), [
            'observaciones' => 'Reposicion con stock por encima del minimo.',
            'detalles' => [[
                'insumo_id' => $inStockSupply->id,
                'cantidad_solicitada' => 5,
            ]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('detalles.0.insumo_id')
            ->assertJsonFragment(['Solo se pueden solicitar insumos con stock bajo o igual al minimo.']);
        $this->assertDatabaseMissing('insumos_solicitados', [
            'usuario_id' => $warehouse->id,
            'tipo_solicitud' => 'reposicion',
            'observaciones' => 'Reposicion con stock por encima del minimo.',
        ]);

        $this->actingAs($director)->postJson(route('insumos.reposiciones-crear'), $payload)->assertForbidden();
        $response = $this->actingAs($warehouse)->postJson(route('insumos.reposiciones-crear'), $payload)->assertCreated();
        $request = InsumoSolicitado::findOrFail($response->json('id'));
        $detail = $request->detalles()->firstOrFail();

        $this->actingAs($director)->postJson(route('insumos.reposiciones-revisar', $request), ['accion' => 'director'])
            ->assertUnprocessable();
        $this->actingAs($chief)->postJson(route('insumos.reposiciones-revisar', $request), ['accion' => 'jefatura'])
            ->assertOk();
        $this->assertSame('3.00', $supply->fresh()->cantidad_numerica);

        $this->actingAs($director)->postJson(route('insumos.reposiciones-revisar', $request), ['accion' => 'director'])
            ->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', [
            'id' => $request->id,
            'estado' => 'aprobado',
            'jefatura_aprobado_por' => $chief->id,
            'aprobado_por' => $director->id,
        ]);
        $this->assertSame('3.00', $supply->fresh()->cantidad_numerica);

        $this->actingAs($warehouse)->postJson(route('insumos.reposiciones-revisar', $request), [
            'accion' => 'recibir',
            'detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 3]],
        ])->assertOk();
        $this->assertSame('6.00', $supply->fresh()->cantidad_numerica);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $request->id, 'estado' => 'parcial']);

        $this->postJson(route('insumos.reposiciones-revisar', $request), [
            'accion' => 'recibir',
            'detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 2]],
        ])->assertOk();
        $this->assertSame('8.00', $supply->fresh()->cantidad_numerica);
        $this->assertDatabaseHas('insumos_solicitados', [
            'id' => $request->id,
            'estado' => 'recibido',
            'recibido_por' => $warehouse->id,
        ]);
    }

    public function test_director_receives_the_administrative_permissions_without_the_admin_role(): void
    {
        $director = $this->clinicalUser('director');

        foreach (['usuarios.gestionar', 'roles.gestionar', 'cajas.gestionar', 'bitacoras.ver'] as $permission) {
            $this->assertTrue($director->can($permission), $permission);
        }
        $this->assertFalse($director->hasRole('admin'));
        $this->actingAs($director)->get(route('usuarios.index'))->assertOk();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('cajas.index'))->assertOk();
    }

    /** @param array<string, mixed> $case */
    private function authorizedTreatment(array $case): TratamientoRealizado
    {
        return TratamientoRealizado::create([
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id,
            'estado' => 'autorizado',
            'costo_cobrado' => 100,
            'autorizado_por' => $case['teacher']->id,
            'fecha_autorizacion' => now(),
        ]);
    }

    /** @param array<string, mixed> $case */
    private function receipt(array $case, TratamientoRealizado $treatment, int $userId, float $amount, string $type): Caja
    {
        return Caja::create([
            'tratamiento_realizado_id' => $treatment->id,
            'paciente_id' => $case['history']->paciente_id,
            'periodo_id' => $case['history']->periodo_id,
            'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id,
            'usuario_id' => $userId,
            'recibo' => 800000000 + Caja::query()->count(),
            'monto' => (int) $amount,
            'costo_total' => 100,
            'cancelado' => $amount,
            'habilitada' => true,
            'tipo' => $type,
            'ci' => 0,
        ]);
    }
}
