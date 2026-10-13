<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Esterilizacion;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\Remision;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Database\Seeders\SolicitudesInsumosPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class HistoriasSeguimientoTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
    }

    public function test_history_list_and_ajax_rows_link_each_follow_up_column_to_its_history(): void
    {
        $case = $this->approvedProgress();
        $this->teacher($case);
        $url = route('historias.show', $case['history']->id);

        $this->get(route('historias.index'))->assertOk()->assertSee('<th>Cajas</th>', false)
            ->assertSee('<th>Insumos</th>', false)->assertSee('<th>Esterilizaciones</th>', false)
            ->assertSee($url.'#insumos', false)->assertSee($url.'#cajas', false)->assertSee($url.'#esterilizaciones', false);
        $response = $this->getJson(route('historias.tabla'))->assertOk();
        $this->assertStringContainsString($url.'#insumos', $response->json('filas'));
        $this->assertStringContainsString($url.'#cajas', $response->json('filas'));
        $this->get(route('historias.index'))->assertViewHas('registros', fn ($rows): bool => $rows->first()->tratamientos_realizados_count === 1);
    }

    public function test_follow_up_keeps_supplies_payments_and_sterilization_in_their_history(): void
    {
        $case = $this->approvedProgress();
        $other = $this->approvedProgress();
        $other['history']->update(['paciente_id' => $case['history']->paciente_id]);
        $referral = $this->referral($case);
        $otherReferral = $referral->replicate();
        $otherReferral->save();
        $ownRequest = $this->supplyRequest($case, '<script>Material propio</script>');
        $this->supplyRequest($other, 'Material de otra clínica');
        $payment = $this->payment($case, 765411);
        $this->payment($other, 765412);
        $fields = ['estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'usuario_id' => $case['student']->id, 'cantidad_sobres' => 1, 'cantidad_paquetes' => 0, 'habilitada' => true];
        $own = Esterilizacion::create($fields + ['remision_id' => $referral->id, 'descripcion' => 'Material del paciente']);
        $general = Esterilizacion::create($fields + ['descripcion' => 'Material general']);
        Esterilizacion::create($fields + ['remision_id' => $otherReferral->id, 'descripcion' => 'Material de otro paciente']);
        $this->teacher($case);

        $this->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertViewHas('solicitudes', fn ($rows): bool => $rows->modelKeys() === [$ownRequest->id])
            ->assertViewHas('pagos', fn ($rows): bool => $rows->modelKeys() === [$payment->id])
            ->assertViewHas('esterilizaciones', fn ($rows): bool => $rows->modelKeys() === [$general->id, $own->id])
            ->assertSee('&lt;script&gt;Material propio&lt;/script&gt;', false)->assertDontSee('<script>Material propio</script>', false)
            ->assertSee('765411')->assertDontSee('765412')->assertDontSee('Material de otra clínica')
            ->assertSee('Material del paciente')->assertDontSee('Material de otro paciente')->assertSee('Autorizar solicitud');
    }

    public function test_assigned_teacher_authorizes_treatment_from_history_and_cashier_can_then_issue_a_receipt(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $treatment = $this->pendingTreatment($case);
        $historyUrl = route('historias.show', $case['history']->id);
        $cashier = $this->clinicalUser('cajas');
        $receipt = ['tratamiento_realizado_id' => $treatment->id, 'ficha' => $case['admission']->id,
            'clinica_id' => $case['clinic']->id, 'recibo' => 1900000110, 'cancelado' => 25, 'habilitada' => true];
        $this->actingAs($cashier)->postJson(route('cajas.store'), $receipt)->assertUnprocessable()
            ->assertJsonValidationErrors('tratamiento_realizado_id');
        $this->assertDatabaseMissing('cajas', ['tratamiento_realizado_id' => $treatment->id]);
        $this->teacher($case)->givePermissionTo('atenciones.autorizar');

        $this->get(route('historias.index'))->assertSee($historyUrl.'#tratamientos', false)->assertSee('Ver tratamientos');
        $this->get($historyUrl)->assertSee('Autorizar tratamiento')
            ->assertSee('action="'.route('tratamientos-realizados.autorizar', $treatment->id).'"', false);
        $this->from($historyUrl)->post(route('tratamientos-realizados.autorizar', $treatment->id))
            ->assertRedirect($historyUrl)->assertSessionHas('success', 'Tratamiento autorizado para Cajas.');

        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $treatment->id, 'estado' => 'autorizado',
            'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now()]);
        $this->assertDatabaseMissing('cajas', ['tratamiento_realizado_id' => $treatment->id]);
        $this->get($historyUrl)->assertDontSee('Autorizar tratamiento')->assertSee('Disponible para emitir recibo en Caja.');
        $this->actingAs($cashier)->getJson(route('cajas.buscar-ficha', ['ficha' => $case['admission']->id]))
            ->assertOk()->assertJsonPath('atenciones.0.id', $treatment->id);
        $response = $this->postJson(route('cajas.store'), $receipt)->assertCreated();
        $this->assertDatabaseHas('cajas', ['id' => $response->json('id'), 'tratamiento_realizado_id' => $treatment->id,
            'paciente_id' => $case['history']->paciente_id, 'usuario_id' => $cashier->id, 'cancelado' => 25]);
    }

    public function test_other_teacher_and_student_cannot_authorize_treatment_from_history(): void
    {
        $case = $this->clinicalCase();
        $treatment = $this->pendingTreatment($case);
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);
        $otherTeacher->givePermissionTo(['historias.ver', 'atenciones.autorizar']);

        $this->actingAs($otherTeacher)->get(route('historias.show', $case['history']->id))->assertSee($case['treatment']->nombre)
            ->assertDontSee('Autorizar tratamiento');
        $this->postJson(route('tratamientos-realizados.autorizar', $treatment->id))->assertForbidden();
        $case['student']->givePermissionTo(['historias.ver', 'atenciones.autorizar']);
        $this->actingAs($case['student'])->get(route('historias.show', $case['history']->id))->assertDontSee('Autorizar tratamiento');
        $this->postJson(route('tratamientos-realizados.autorizar', $treatment->id))->assertForbidden();
        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $treatment->id, 'estado' => 'pendiente', 'autorizado_por' => null]);
    }

    public function test_closed_history_hides_treatment_authorization_and_rejects_direct_approval(): void
    {
        $case = $this->clinicalCase();
        $treatment = $this->pendingTreatment($case);
        $case['history']->update(['estado' => 'cerrada']);
        $this->teacher($case)->givePermissionTo('atenciones.autorizar');

        $this->get(route('historias.show', $case['history']->id))->assertSee($case['treatment']->nombre)->assertDontSee('Autorizar tratamiento');
        $this->postJson(route('tratamientos-realizados.autorizar', $treatment->id))->assertUnprocessable()
            ->assertJsonValidationErrors('historia_clinica_id');
        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $treatment->id, 'estado' => 'pendiente', 'autorizado_por' => null]);
    }

    public function test_history_with_end_date_hides_treatment_authorization_and_rejects_direct_approval(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $treatment = $this->pendingTreatment($case);
        $case['history']->update(['fecha_fin' => today()]);
        $this->teacher($case)->givePermissionTo('atenciones.autorizar');

        $this->get(route('historias.show', $case['history']->id))->assertDontSee('Autorizar tratamiento');
        $this->postJson(route('tratamientos-realizados.autorizar', $treatment->id))->assertUnprocessable()
            ->assertJsonValidationErrors('historia_clinica_id');
        $this->assertDatabaseHas('tratamientos_realizados', ['id' => $treatment->id, 'estado' => 'pendiente', 'autorizado_por' => null]);
    }

    public function test_teacher_authorizes_from_history_and_warehouse_approves_from_request_detail(): void
    {
        $case = $this->clinicalCase();
        $solicitud = $this->supplyRequest($case);
        $warehouse = $this->clinicalUser('insumos');
        $warehouse->givePermissionTo(['solicitudes-insumos.ver', 'solicitudes-insumos.aprobar', 'solicitudes-insumos.rechazar']);
        $detailUrl = route('solicitudes-insumos.show', $solicitud->id);
        $historyUrl = route('historias.show', $case['history']->id);
        $this->actingAs($warehouse)->get($detailUrl)->assertOk()->assertDontSee('Aprobar en Insumos')->assertDontSee('Autorizar solicitud');
        $this->postJson(route('solicitudes-insumos.aprobar', $solicitud->id))->assertUnprocessable();
        $this->teacher($case);

        $this->from($historyUrl)->post(route('solicitudes-insumos.autorizar', $solicitud->id))
            ->assertRedirect($historyUrl)->assertSessionHas('success');
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $solicitud->id, 'estado' => 'autorizado', 'docente_id' => $case['teacher']->id]);
        $this->get($historyUrl)->assertSee('Pendiente de aprobación en Insumos')->assertDontSee('Autorizar solicitud')->assertDontSee('Aprobar en Insumos');
        $this->actingAs($warehouse)->get($detailUrl)->assertOk()->assertSee('Aprobar en Insumos')->assertSee('Rechazar solicitud');
        $this->from($detailUrl)->post(route('solicitudes-insumos.aprobar', $solicitud->id))->assertRedirect($detailUrl);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $solicitud->id, 'estado' => 'aprobado', 'aprobado_por' => $warehouse->id]);
        $this->get($detailUrl)->assertDontSee('Aprobar en Insumos');
        $this->assertSame('10.00', $solicitud->detalles()->firstOrFail()->insumo->cantidad_numerica);
    }

    public function test_teacher_and_warehouse_can_reject_at_their_respective_review_stage(): void
    {
        $case = $this->clinicalCase();
        $first = $this->supplyRequest($case);
        $second = $this->supplyRequest($case, 'Gasas');
        $this->teacher($case);
        $historyUrl = route('historias.show', $case['history']->id);

        $this->from($historyUrl)->post(route('solicitudes-insumos.rechazar', $first->id), ['motivo' => 'Cantidad a corregir'])->assertRedirect($historyUrl);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $first->id, 'estado' => 'rechazado', 'rechazado_por' => $case['teacher']->id, 'motivo_rechazo' => 'Cantidad a corregir']);
        $this->postJson(route('solicitudes-insumos.autorizar', $second->id))->assertOk();
        $warehouse = $this->clinicalUser('insumos');
        $warehouse->givePermissionTo(['solicitudes-insumos.ver', 'solicitudes-insumos.rechazar']);
        $this->actingAs($warehouse)->from(route('solicitudes-insumos.show', $second->id))
            ->post(route('solicitudes-insumos.rechazar', $second->id), ['motivo' => 'Sin disponibilidad'])->assertRedirect();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $second->id, 'estado' => 'rechazado', 'rechazado_por' => $warehouse->id, 'motivo_rechazo' => 'Sin disponibilidad']);
    }

    public function test_another_teacher_in_the_clinic_cannot_view_or_authorize_assigned_students_requests(): void
    {
        $case = $this->clinicalCase();
        $solicitud = $this->supplyRequest($case);
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);
        $otherTeacher->givePermissionTo(['historias.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.autorizar']);

        $this->actingAs($otherTeacher)->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertViewHas('solicitudes', fn ($rows): bool => $rows->isEmpty())->assertDontSee('Autorizar solicitud');
        $this->postJson(route('solicitudes-insumos.autorizar', $solicitud->id))->assertNotFound();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $solicitud->id, 'estado' => 'pendiente']);
        $this->actingAs($this->clinicalUser('estudiante'))->get(route('historias.show', $case['history']->id))->assertNotFound();
    }

    public function test_history_detail_hides_modules_without_read_permission_and_preserves_ajax_contract(): void
    {
        $case = $this->clinicalCase();
        $reader = $this->clinicalUser('kardex');
        $reader->syncRoles([]);
        $reader->givePermissionTo('historias.ver');

        $this->actingAs($reader)->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertViewHas('solicitudes', fn ($rows): bool => $rows->isEmpty())
            ->assertViewHas('pagos', fn ($rows): bool => $rows->isEmpty())
            ->assertViewHas('esterilizaciones', fn ($rows): bool => $rows->isEmpty())
            ->assertDontSee('id="cajas"', false)->assertDontSee('id="insumos"', false)->assertDontSee('id="esterilizaciones"', false);
        $this->getJson(route('historias.show', $case['history']->id))->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('registro.id', $case['history']->id);
    }

    public function test_closed_history_keeps_request_visible_without_teacher_review_controls(): void
    {
        $case = $this->clinicalCase();
        $solicitud = $this->supplyRequest($case);
        $case['history']->update(['estado' => 'cerrada']);
        $this->teacher($case);

        $this->get(route('historias.show', $case['history']->id))->assertOk()->assertSee('Guantes')
            ->assertDontSee('Autorizar solicitud')->assertDontSee('Rechazar solicitud');
        $this->postJson(route('solicitudes-insumos.autorizar', $solicitud->id))->assertUnprocessable()
            ->assertJsonValidationErrors('historia_clinica_id');
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $solicitud->id, 'estado' => 'pendiente']);
    }

    public function test_student_cannot_authorize_own_request_even_with_authorize_permission(): void
    {
        $case = $this->clinicalCase();
        $solicitud = $this->supplyRequest($case);
        $case['student']->givePermissionTo('solicitudes-insumos.autorizar');

        $this->actingAs($case['student'])->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertSee('Guantes')->assertDontSee('Autorizar solicitud')->assertDontSee('Aprobar en Insumos');
        $this->postJson(route('solicitudes-insumos.autorizar', $solicitud->id))->assertForbidden();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $solicitud->id, 'estado' => 'pendiente']);
    }

    public function test_legacy_session_request_and_shared_receipt_show_only_the_current_history_amount(): void
    {
        $case = $this->approvedProgress();
        $other = $this->approvedProgress();
        $request = $this->supplyRequest($case);
        $type = $request->detalles()->firstOrFail()->insumo->insumo_tratamiento_id;
        $request->update(['historia_clinica_id' => null, 'tratamiento_id' => null, 'insumo_tratamiento_id' => $type,
            'tratamiento_realizado_id' => $case['realized']->id, 'sesion_atencion_id' => $case['session']->id]);
        $payment = $this->payment($other, 765415);
        $payment->update(['cancelado' => 75]);
        $payment->detalles()->create(['tratamiento_realizado_id' => $case['realized']->id, 'concepto' => 'Pago de esta historia', 'cantidad' => 1, 'precio_unitario' => 25, 'monto' => 25, 'fecha' => today()]);
        $payment->detalles()->create(['tratamiento_realizado_id' => $other['realized']->id, 'concepto' => 'Pago de otra historia', 'cantidad' => 1, 'precio_unitario' => 50, 'monto' => 50, 'fecha' => today()]);
        $this->teacher($case);

        $this->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertViewHas('solicitudes', fn ($rows): bool => $rows->modelKeys() === [$request->id])
            ->assertViewHas('pagos', fn ($rows): bool => $rows->count() === 1 && $rows->first()->detalles->count() === 1)
            ->assertSee('25.00')->assertDontSee('75.00')->assertSee('Autorizar solicitud');
    }

    /** @param array<string, mixed> $case */
    private function teacher(array $case): Usuario
    {
        $case['teacher']->givePermissionTo(['historias.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.rechazar', 'cajas.ver', 'esterilizaciones.ver']);
        $this->actingAs($case['teacher']);

        return $case['teacher'];
    }

    /** @param array<string, mixed> $case */
    private function pendingTreatment(array $case): TratamientoRealizado
    {
        return TratamientoRealizado::create(['historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
            'pieza_dental' => '16', 'costo_cobrado' => 100, 'estado' => 'pendiente']);
    }

    /** @param array<string, mixed> $case */
    private function supplyRequest(array $case, string $name = 'Guantes'): InsumoSolicitado
    {
        $type = InsumoTratamiento::create(['clinica_id' => $case['clinic']->id, 'nombre' => 'Material dental']);
        $material = Insumo::create(['nombre' => $name, 'clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id,
            'cantidad' => 'unidades', 'cantidad_numerica' => 10, 'stock_minimo' => 1, 'costo_unitario' => 2]);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $id = $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), [
            'historia_clinica_id' => $case['history']->id, 'clinica_id' => $case['clinic']->id,
            'tratamiento_id' => $case['treatment']->id, 'detalles' => [['insumo_id' => $material->id, 'cantidad_solicitada' => 2]],
        ])->assertCreated()->json('id');

        return InsumoSolicitado::findOrFail($id);
    }

    /** @param array<string, mixed> $case */
    private function referral(array $case): Remision
    {
        $referral = Remision::create(['admision_id' => $case['admission']->id, 'paciente_id' => $case['history']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id, 'estado' => 'asignada', 'habilitado' => true]);
        $case['history']->update(['remision_id' => $referral->id]);

        return $referral;
    }

    /** @param array<string, mixed> $case */
    private function payment(array $case, int $receipt): Caja
    {
        return Caja::create(['tratamiento_realizado_id' => $case['realized']->id, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'usuario_id' => $case['teacher']->id, 'costo_total' => 100, 'cancelado' => 25,
            'habilitada' => true, 'monto' => 100, 'ci' => 0, 'descripcion' => '', 'tipo' => '', 'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => 'Paciente', 'grado' => '', 'recibo' => $receipt]);
    }
}
