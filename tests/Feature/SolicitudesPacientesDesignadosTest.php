<?php

namespace Tests\Feature;

use App\Models\Caja;
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

class SolicitudesPacientesDesignadosTest extends TestCase
{
    use CreatesClinicalCase {
        clinicalUser as private createClinicalUser;
    }
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
    }

    public function test_designated_student_requests_configured_materials_before_any_authorized_session(): void
    {
        $case = $this->clinicalCase();
        $case['history']->update(['estado' => 'abierta', 'autorizado' => true]);
        $material = $this->material($case);
        $other = $this->material($case, 'Gasas');
        $case['treatment']->insumos()->attach([$material->id => ['cantidad' => 2], $other->id => ['cantidad' => 1]]);
        $this->authorizeAndPay($case);

        $response = $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))
            ->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('insumos_solicitados', ['id' => $response->json('id'), 'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'usuario_id' => $case['student']->id, 'estado' => 'pendiente', 'sesion_atencion_id' => null]);
        $this->assertDatabaseHas('insumos_solicitados_detalle', ['insumo_solicitado_id' => $response->json('id'), 'insumo_id' => $material->id, 'cantidad_solicitada' => 2]);
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
        $this->get(route('estudiante.historias'))->assertOk()->assertSee('data-solicitar-designado', false);
        $this->get(route('estudiante.historia', $case['history']))->assertOk()->assertSee('Solicitud #'.$response->json('id'));
        $this->getJson(route('solicitudes-insumos.datos', $response->json('id')))
            ->assertOk()->assertJsonPath('detalles.0.stock_actual', 10);
    }

    public function test_catalog_exposes_only_materials_of_the_designated_clinic_and_treatment(): void
    {
        $case = $this->clinicalCase();
        $material = $this->material($case);
        $this->material($case, 'No configurado');
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);

        $response = $this->actingAs($case['student'])->getJson(route('solicitudes-insumos.catalogo-designado', ['historia_clinica_id' => $case['history']->id]))
            ->assertOk()->assertJsonPath('historia.ficha', $case['admission']->id)->assertJsonPath('historia.docente', $case['teacher']->nombre_completo);

        $this->assertCount(1, $response->json('tratamientos.0.insumos'));
        $response->assertJsonPath('tratamientos.0.insumos.0.id', $material->id);
        $this->actingAs($this->clinicalUser('estudiante'))->getJson(route('solicitudes-insumos.catalogo-designado', ['historia_clinica_id' => $case['history']->id]))->assertNotFound();
    }

    public function test_student_creates_separate_requests_for_the_same_material_while_previous_requests_are_pending(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $material = $this->material($case);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->authorizeAndPay($case);
        $this->actingAs($case['student']);
        $requestIds = [];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $requestIds[] = $this->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))
                ->assertCreated()->assertJsonPath('success', true)->json('id');
        }

        $this->assertCount(3, array_unique($requestIds));
        foreach ($requestIds as $requestId) {
            $this->assertDatabaseHas('insumos_solicitados', ['id' => $requestId, 'historia_clinica_id' => $case['history']->id,
                'tratamiento_id' => $case['treatment']->id, 'usuario_id' => $case['student']->id, 'estado' => 'pendiente']);
            $this->assertDatabaseHas('insumos_solicitados_detalle', ['insumo_solicitado_id' => $requestId,
                'insumo_id' => $material->id, 'cantidad_solicitada' => 2, 'cantidad_entregada' => 0]);
            $this->assertDatabaseHas('notificaciones', ['usuario_id' => $case['teacher']->id,
                'tipo' => 'solicitud_creada', 'titulo' => 'Solicitud de insumos #'.$requestId]);
        }
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
        $this->get(route('estudiante.historia', $case['history']))->assertSee('Solicitar nuevamente')
            ->assertSee('Solicitud #'.$requestIds[0])->assertSee('Solicitud #'.$requestIds[1])->assertSee('Solicitud #'.$requestIds[2]);
    }

    public function test_only_assigned_teacher_authorizes_then_stock_staff_approves_and_delivers(): void
    {
        $case = $this->clinicalCase();
        $case['teacher']->givePermissionTo(['solicitudes-insumos.autorizar', 'solicitudes-insumos.ver']);
        $material = $this->material($case);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->authorizeAndPay($case);
        $this->actingAs($case['student']);
        $id = $this->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertCreated()->json('id');
        $warehouse = $this->clinicalUser('insumos');
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->givePermissionTo('solicitudes-insumos.autorizar');
        $otherTeacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        $this->postJson(route('solicitudes-insumos.autorizar', $id))->assertForbidden();
        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.aprobar', $id))->assertUnprocessable();
        $this->actingAs($otherTeacher)->postJson(route('solicitudes-insumos.autorizar', $id))->assertNotFound();
        $this->actingAs($case['teacher'])->getJson(route('solicitudes-insumos.tabla'))->assertOk()->assertJsonPath('total', 1);
        $this->postJson(route('solicitudes-insumos.autorizar', $id))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'docente_id' => $case['teacher']->id, 'estado' => 'autorizado']);
        $this->postJson(route('solicitudes-insumos.aprobar', $id))->assertForbidden();

        $request = InsumoSolicitado::findOrFail($id);
        $details = ['detalles' => [['detalle_id' => $request->detalles()->firstOrFail()->id, 'cantidad_entregada' => 2]]];
        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.entregar', $id), $details)->assertUnprocessable();
        $this->postJson(route('solicitudes-insumos.aprobar', $id))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'estado' => 'aprobado', 'aprobado_por' => $warehouse->id]);
        $this->postJson(route('solicitudes-insumos.entregar', $id), $details)->assertOk();
        $this->assertSame('8.00', $material->fresh()->cantidad_numerica);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'estado' => 'entregado']);
        $this->assertDatabaseHas('insumos_entregas', ['insumo_solicitado_id' => $id, 'usuario_id' => $warehouse->id, 'cantidad' => 2]);

        $newId = $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))
            ->assertCreated()->json('id');

        $this->assertNotSame($id, $newId);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $newId, 'historia_clinica_id' => $case['history']->id, 'estado' => 'pendiente', 'autorizado' => false]);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'estado' => 'entregado']);
        $this->assertDatabaseHas('insumos_solicitados_detalle', ['insumo_solicitado_id' => $newId, 'insumo_id' => $material->id, 'cantidad_solicitada' => 2, 'cantidad_entregada' => 0]);
        $this->assertSame('8.00', $material->fresh()->cantidad_numerica);
        $this->get(route('estudiante.historia', $case['history']))->assertSee('Solicitar nuevamente');
    }

    public function test_patient_can_select_each_assigned_clinic_and_its_own_catalog(): void
    {
        $case = $this->clinicalCase();
        $second = $this->anotherClinic($case);
        $firstMaterial = $this->material($case);
        $secondMaterial = $this->material($second, 'Material segunda clínica');
        $case['treatment']->insumos()->attach($firstMaterial, ['cantidad' => 2]);
        $second['treatment']->insumos()->attach($secondMaterial, ['cantidad' => 3]);
        $this->actingAs($case['student']);

        $url = route('solicitudes-insumos.catalogo-designado', ['historia_clinica_id' => $case['history']->id]);
        $this->getJson($url)->assertOk()->assertJsonCount(2, 'clinicas')->assertJsonPath('historia.id', $case['history']->id);
        $this->getJson($url.'&clinica_id='.$second['clinic']->id)->assertOk()
            ->assertJsonPath('historia.id', $second['history']->id)
            ->assertJsonPath('historia.ficha', $second['admission']->id)
            ->assertJsonPath('historia.docente', $second['teacher']->nombre_completo)
            ->assertJsonCount(1, 'tratamientos')->assertJsonPath('tratamientos.0.id', $second['treatment']->id)
            ->assertJsonCount(1, 'tratamientos.0.insumos')->assertJsonPath('tratamientos.0.insumos.0.id', $secondMaterial->id);
    }

    public function test_history_detail_lists_all_active_clinics_assigned_for_that_patient(): void
    {
        $case = $this->clinicalCase();
        $second = $this->anotherClinic($case);
        $second['clinic']->update(['especialidad' => 'Segunda clínica del paciente']);
        $closed = $this->anotherClinic($case);
        $closed['history']->update(['estado' => 'cerrada']);
        $otherPatient = $this->clinicalCase();
        $case['student']->especialidades()->attach($otherPatient['clinic']->id, ['habilitado' => true]);
        $otherPatient['history']->update(['estudiante_id' => $case['student']->id]);
        $otherStudent = $this->clinicalCase();
        $otherStudent['history']->update(['paciente_id' => $case['history']->paciente_id]);

        $expected = [$case['clinic']->id, $second['clinic']->id];
        sort($expected);
        $this->actingAs($case['student'])->get(route('estudiante.historia', $case['history']->id))
            ->assertOk()->assertViewHas('clinicas', fn ($clinics): bool => $clinics->pluck('id')->sort()->values()->all() === $expected)
            ->assertSee('Segunda clínica del paciente')->assertSee('historia_clinica_id=', false);
    }

    public function test_treatment_search_filters_by_selected_clinic_and_the_patient_in_the_open_history(): void
    {
        $case = $this->clinicalCase();
        $second = $this->anotherClinic($case);
        $otherPatient = $this->clinicalCase();
        $case['student']->especialidades()->attach($otherPatient['clinic']->id, ['habilitado' => true]);
        $otherPatient['history']->update(['estudiante_id' => $case['student']->id, 'clinica_id' => $second['clinic']->id]);
        foreach ([$case, $second, $otherPatient] as $assignment) {
            TratamientoRealizado::create(['historia_clinica_id' => $assignment['history']->id,
                'tratamiento_id' => $assignment['treatment']->id, 'clinica_id' => $assignment['history']->clinica_id,
                'estado' => 'autorizado', 'pieza_dental' => '16']);
        }
        $selected = $second['history']->tratamientosRealizados()->sole();
        $this->actingAs($case['student']);
        $url = route('solicitudes-insumos.buscar-tratamientos', ['historia_clinica_id' => $case['history']->id, 'clinica_id' => $second['clinic']->id]);
        $this->getJson($url)->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $selected->id);
        $second['history']->update(['fecha_fin' => today()]);
        $this->getJson($url)->assertOk()->assertExactJson([]);

        $this->actingAs($this->clinicalUser('estudiante'))->getJson($url)->assertNotFound();
    }

    public function test_clinic_selector_excludes_other_students_and_closed_assignments(): void
    {
        $case = $this->clinicalCase();
        $otherStudent = $this->clinicalCase();
        $otherStudent['history']->update(['paciente_id' => $case['history']->paciente_id]);
        $closed = $this->anotherClinic($case);
        $closed['history']->update(['estado' => 'cerrada']);
        $finished = $this->anotherClinic($case);
        $finished['history']->update(['fecha_fin' => today()]);
        $this->actingAs($case['student']);
        $url = route('solicitudes-insumos.catalogo-designado', ['historia_clinica_id' => $case['history']->id]);

        $this->getJson($url)->assertOk()->assertJsonCount(1, 'clinicas')->assertJsonPath('clinicas.0.id', $case['clinic']->id);
        foreach ([$otherStudent, $closed, $finished] as $unavailable) {
            $this->getJson($url.'&clinica_id='.$unavailable['clinic']->id)->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
        }
        $this->get(route('solicitudes-insumos.index'))->assertOk()
            ->assertViewHas('pacientesDesignados', fn ($patients): bool => $patients->count() === 1 && $patients->first()->paciente_id === $case['history']->paciente_id)
            ->assertSee('designado-paciente-select', false)->assertSee('SolicitudDesignado.abrir()', false);
    }

    public function test_selected_clinic_request_uses_its_referral_and_assigned_teacher(): void
    {
        $case = $this->clinicalCase();
        $second = $this->anotherClinic($case);
        $case['teacher']->givePermissionTo('solicitudes-insumos.autorizar');
        $second['teacher']->givePermissionTo('solicitudes-insumos.autorizar');
        $referral = Remision::create([
            'admision_id' => $second['admission']->id, 'paciente_id' => $second['history']->paciente_id,
            'clinica_id' => $second['clinic']->id, 'periodo_id' => $second['history']->periodo_id,
            'estudiante_id' => $case['student']->id, 'docente_id' => $second['teacher']->id,
            'estado' => 'asignado', 'habilitado' => true,
        ]);
        $second['history']->update(['remision_id' => $referral->id]);
        $material = $this->material($second);
        $second['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->authorizeAndPay($second);
        $id = $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), $this->payload($second, $material))->assertCreated()->json('id');

        $this->assertDatabaseHas('insumos_solicitados', [
            'id' => $id, 'historia_clinica_id' => $second['history']->id, 'clinica_id' => $second['clinic']->id,
            'remision_id' => $referral->id, 'tratamiento_id' => $second['treatment']->id, 'usuario_id' => $case['student']->id,
        ]);
        $this->actingAs($case['teacher'])->postJson(route('solicitudes-insumos.autorizar', $id))->assertNotFound();
        $this->actingAs($second['teacher'])->postJson(route('solicitudes-insumos.autorizar', $id))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'docente_id' => $second['teacher']->id, 'estado' => 'autorizado']);
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
    }

    public function test_switching_request_clinic_rejects_mixed_history_treatment_or_materials(): void
    {
        $case = $this->clinicalCase();
        $second = $this->anotherClinic($case);
        $material = $this->material($case);
        $secondMaterial = $this->material($second);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $second['treatment']->insumos()->attach($secondMaterial, ['cantidad' => 3]);
        $this->authorizeAndPay($case);
        $this->authorizeAndPay($second);
        $this->actingAs($case['student']);
        $data = $this->payload($second, $secondMaterial);
        $data['historia_clinica_id'] = $case['history']->id;
        $this->postJson(route('solicitudes-insumos.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
        $data = $this->payload($second, $material);
        $this->postJson(route('solicitudes-insumos.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.insumo_id');
        $data = $this->payload($second, $secondMaterial);
        $data['tratamiento_id'] = $case['treatment']->id;
        $this->postJson(route('solicitudes-insumos.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('tratamiento_id');

        $id = $this->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertCreated()->json('id');
        $case['student']->givePermissionTo('solicitudes-insumos.editar');
        $this->putJson(route('solicitudes-insumos.update', $id), $this->payload($second, $secondMaterial))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'historia_clinica_id' => $second['history']->id, 'clinica_id' => $second['clinic']->id, 'estado' => 'pendiente']);
        $this->assertDatabaseMissing('insumos_solicitados_detalle', ['insumo_solicitado_id' => $id, 'insumo_id' => $material->id]);
        $this->assertDatabaseHas('insumos_solicitados_detalle', ['insumo_solicitado_id' => $id, 'insumo_id' => $secondMaterial->id]);
    }

    public function test_stock_staff_rejects_teacher_authorized_request_without_decrementing_stock(): void
    {
        $case = $this->clinicalCase();
        $case['teacher']->givePermissionTo('solicitudes-insumos.autorizar');
        $material = $this->material($case);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->authorizeAndPay($case);
        $id = $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertCreated()->json('id');
        $this->actingAs($case['teacher'])->postJson(route('solicitudes-insumos.autorizar', $id))->assertOk();
        $warehouse = $this->clinicalUser('insumos');

        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.rechazar', $id), ['motivo' => 'Material reservado'])->assertOk();

        $this->assertDatabaseHas('insumos_solicitados', ['id' => $id, 'estado' => 'rechazado', 'rechazado_por' => $warehouse->id, 'motivo_rechazo' => 'Material reservado']);
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
        $this->postJson(route('solicitudes-insumos.aprobar', $id))->assertUnprocessable();
    }

    public function test_cannot_request_material_for_another_students_history(): void
    {
        $case = $this->clinicalCase();
        $material = $this->material($case);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->authorizeAndPay($case);

        $this->actingAs($this->clinicalUser('estudiante'))->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertForbidden();

        $this->assertDatabaseMissing('insumos_solicitados', ['historia_clinica_id' => $case['history']->id]);
    }

    public function test_unconfigured_material_and_excess_stock_are_rejected(): void
    {
        $case = $this->clinicalCase();
        $material = $this->material($case);
        $this->authorizeAndPay($case);
        $this->actingAs($case['student']);

        $this->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertUnprocessable()->assertJsonValidationErrors('detalles.0.insumo_id');
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $data = $this->payload($case, $material);
        $data['detalles'][0]['cantidad_solicitada'] = 11;
        $this->postJson(route('solicitudes-insumos.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('detalles.0.cantidad_solicitada');
        $case['history']->update(['estado' => 'cerrada']);
        $this->postJson(route('solicitudes-insumos.store'), $this->payload($case, $material))->assertUnprocessable()->assertJsonValidationErrors('historia_clinica_id');
        $this->assertDatabaseMissing('insumos_solicitados', ['historia_clinica_id' => $case['history']->id]);
        $this->get(route('estudiante.historia', $case['history']))->assertDontSee('data-solicitar-designado', false);
    }

    private function clinicalUser(string $role): Usuario
    {
        $user = $this->createClinicalUser($role);
        if ($role === 'estudiante') {
            $user->givePermissionTo(['historias.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.crear']);
        }

        return $user;
    }

    /** @param array<string, mixed> $case */
    private function anotherClinic(array $case): array
    {
        $second = $this->clinicalCase();
        $case['student']->especialidades()->attach($second['clinic']->id, ['habilitado' => true]);
        $second['admission']->update(['paciente_id' => $case['history']->paciente_id, 'estudiante_id' => $case['student']->id]);
        $second['history']->update(['paciente_id' => $case['history']->paciente_id, 'estudiante_id' => $case['student']->id]);
        $second['student'] = $case['student'];

        return $second;
    }

    /** @param array<string, mixed> $case */
    private function material(array $case, string $name = 'Guantes'): Insumo
    {
        $type = InsumoTratamiento::create(['nombre' => $name, 'clinica_id' => $case['clinic']->id]);

        return Insumo::create(['nombre' => $name, 'clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id, 'cantidad_numerica' => 10, 'cantidad' => 'unidades', 'stock_minimo' => 0, 'costo_unitario' => 2]);
    }

    /** @param array<string, mixed> $case @return array<string, mixed> */
    private function payload(array $case, Insumo $material): array
    {
        return ['historia_clinica_id' => $case['history']->id, 'clinica_id' => $case['clinic']->id,
            'tratamiento_id' => $case['treatment']->id, 'detalles' => [['insumo_id' => $material->id, 'cantidad_solicitada' => 2]]];
    }

    /** @param array<string, mixed> $case */
    private function authorizeAndPay(array $case): TratamientoRealizado
    {
        $treatment = TratamientoRealizado::create([
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'estado' => 'autorizado', 'costo_cobrado' => 100,
            'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);
        Caja::create([
            'tratamiento_realizado_id' => $treatment->id, 'paciente_id' => $case['history']->paciente_id,
            'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'usuario_id' => $case['teacher']->id,
            'recibo' => 600000000 + $treatment->id, 'monto' => 100, 'costo_total' => 100,
            'cancelado' => 100, 'habilitada' => true, 'ci' => 0, 'tipo' => 'tratamiento',
        ]);

        return $treatment;
    }
}
