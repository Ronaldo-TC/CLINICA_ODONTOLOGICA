<?php

namespace Tests\Feature;

use App\Models\Esterilizacion;
use App\Models\EsterilizacionCiclo;
use Database\Seeders\EsterilizacionPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class EsterilizacionWorkflowTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_direct_record_url_renders_the_control_and_preserves_the_json_response(): void
    {
        $case = $this->caso();

        $this->actingAs($case['staff'])->get(route('esterilizacion.show', $case['record']->id))
            ->assertOk()->assertSee('Recepción e identificación del material');
        $this->getJson(route('esterilizacion.show', $case['record']->id))->assertOk()
            ->assertJsonPath('success', true)->assertJsonPath('registro.id', $case['record']->id);
    }

    public function test_disabled_student_cannot_collect_liberated_material(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $cycle->update(['estado' => 'liberado', 'control_fisico' => true, 'control_quimico' => true,
            'control_biologico' => 'conforme', 'fecha_revision' => now(), 'fecha_liberacion' => now()]);
        $case['student']->update(['habilitado' => false]);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.movimiento', $case['record']->id),
            ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertUnprocessable()
            ->assertJsonPath('message', 'El estudiante debe estar habilitado para recoger el material.');

        $this->assertNull($case['record']->fresh()->fecha_recojo);
    }

    public function test_receipt_cannot_be_repeated_and_draft_deletion_preserves_processed_records(): void
    {
        $case = $this->preparado();
        $originalCodes = $case['record']->codigos_material;

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.movimiento', $case['record']->id),
            ['accion' => 'recibir', 'sobres_recibidos' => 1, 'paquetes_recibidos' => 0])->assertUnprocessable();

        $this->assertSame($originalCodes, $case['record']->fresh()->codigos_material);
        $draft = $this->caso();
        $this->deleteJson(route('esterilizacion.destroy', $draft['record']->id))->assertOk()->assertJsonPath('success', true);
        $this->assertModelMissing($draft['record']);
        $this->deleteJson(route('esterilizacion.destroy', $case['record']->id))->assertUnprocessable();
        $this->assertModelExists($case['record']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EsterilizacionPermissionsSeeder::class);
    }

    public function test_student_creates_a_request_without_a_responsible_field_and_cannot_spoof_its_creator(): void
    {
        $case = $this->clinicalCase();
        $payload = $this->solicitud($case) + ['usuario_id' => $case['teacher']->id, 'autorizado' => true, 'firma_docente' => 'spoofed', 'sobres_recibidos' => 999];

        $response = $this->actingAs($case['student'])->postJson(route('esterilizacion.store'), $payload);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('esterilizaciones', ['id' => $response->json('id'), 'estudiante_id' => $case['student']->id,
            'usuario_id' => $case['student']->id, 'autorizado' => false, 'firma_docente' => null, 'sobres_recibidos' => null]);
        $this->actingAs($this->clinicalUser('esterilizacion'))->get(route('esterilizacion.index'))
            ->assertOk()->assertSee('Nuevo Registro')->assertDontSee('name="fecha_entrega"', false)->assertDontSee('name="fecha_recojo"', false);
    }

    /** @return array<string, array{string}> */
    public static function fechasAdministradas(): array
    {
        return ['recepción' => ['fecha_entrega'], 'recojo' => ['fecha_recojo']];
    }

    #[DataProvider('fechasAdministradas')]
    public function test_dates_cannot_be_forged_in_the_request_form(string $campo): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($case['student'])->postJson(route('esterilizacion.store'), $this->solicitud($case) + [$campo => '2026-01-01'])
            ->assertUnprocessable()->assertJsonValidationErrors($campo);

        $this->assertDatabaseMissing('esterilizaciones', ['estudiante_id' => $case['student']->id]);
    }

    public function test_complete_workflow_records_material_cycles_controls_and_the_person_who_picks_up(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $staff = $this->clinicalUser('esterilizacion');
        $id = $this->actingAs($case['student'])->postJson(route('esterilizacion.store'), $this->solicitud($case))->assertCreated()->json('id');
        $record = Esterilizacion::findOrFail($id);
        $this->actingAs($case['teacher'])->post(route('esterilizacion.autorizar', $id), ['firma_docente' => 'data:image/png;base64,AAAA'])->assertRedirect();
        $this->actingAs($staff)->post(route('esterilizacion.movimiento', $id), ['accion' => 'recibir', 'sobres_recibidos' => 2, 'paquetes_recibidos' => 1])->assertRedirect();
        $this->assertSame(['EST-'.$id.'-S1', 'EST-'.$id.'-S2', 'EST-'.$id.'-P1'], $record->fresh()->codigos_material);
        $this->post(route('esterilizacion.preparar', $id), $this->preparacion())->assertRedirect();
        $this->assertSame($staff->id, $record->fresh()->preparado_por);
        $this->travel(1)->hours();
        $this->post(route('esterilizacion.ciclos.store', $id), $this->datosCiclo())->assertRedirect();
        $cycle = $record->ciclos()->firstOrFail();
        $this->assertSame($staff->id, $cycle->registrado_por);
        $this->assertSame($record->fresh()->codigos_material, $cycle->codigos_material);
        $this->post(route('esterilizacion.ciclos.revisar', [$id, $cycle->id]), $this->revision())->assertRedirect();
        $this->assertSame('revisado', $cycle->fresh()->estado);
        $this->post(route('esterilizacion.ciclos.liberar', [$id, $cycle->id]), $this->liberacion())->assertRedirect();
        $this->assertSame('liberado', $cycle->fresh()->estado);
        $this->assertSame($staff->id, $cycle->fresh()->liberado_por);

        $this->post(route('esterilizacion.movimiento', $id), ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertRedirect();

        $record->refresh();
        $this->assertSame($staff->id, $record->entregado_por);
        $this->assertSame($case['student']->id, $record->recogido_por);
        $this->assertNotNull($record->hora_recojo);
        $this->assertSame('Recogido', $record->estado_proceso);
        $this->get(route('esterilizacion.control', $id))->assertOk()->assertSee('Recogido')->assertSee('Historial de revisiones')
            ->assertDontSee('name="confirmar_recojo"', false);
        $this->postJson(route('esterilizacion.movimiento', $id), ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertUnprocessable();
        $this->assertSame(1, $record->ciclos()->count());
    }

    public function test_receipt_requires_actual_quantities_and_cannot_exceed_the_request(): void
    {
        $case = $this->caso(['autorizado' => true, 'firma_docente' => 'data:image/png;base64,AAAA']);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'recibir'])
            ->assertUnprocessable()->assertJsonValidationErrors(['sobres_recibidos', 'paquetes_recibidos']);
        $this->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'recibir', 'sobres_recibidos' => 3, 'paquetes_recibidos' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('sobres_recibidos');
        $this->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'recibir', 'sobres_recibidos' => 0, 'paquetes_recibidos' => 0])
            ->assertUnprocessable()->assertJsonPath('errors.sobres_recibidos.0', 'Reciba al menos un sobre o paquete.');

        $this->assertNull($case['record']->fresh()->fecha_entrega);
        $this->assertNull($case['record']->fresh()->codigos_material);
    }

    public function test_preparation_requires_receipt_and_all_three_confirmations(): void
    {
        $case = $this->caso(['autorizado' => true, 'firma_docente' => 'data:image/png;base64,AAAA']);
        $this->actingAs($case['staff'])->postJson(route('esterilizacion.preparar', $case['record']->id), $this->preparacion())->assertUnprocessable();
        $case['record']->update(['fecha_entrega' => today()]);

        $this->postJson(route('esterilizacion.preparar', $case['record']->id), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['limpieza_confirmada', 'inspeccion_confirmada', 'empaque_confirmado']);

        $this->assertNull($case['record']->fresh()->fecha_preparacion);
    }

    public function test_legacy_receipt_requires_material_confirmation_and_is_not_automatically_released(): void
    {
        $case = $this->caso(['autorizado' => true, 'firma_docente' => 'data:image/png;base64,AAAA', 'fecha_entrega' => today()]);
        $this->actingAs($case['staff'])->get(route('esterilizacion.control', $case['record']->id))->assertSee('recepción antigua');
        $this->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertUnprocessable();

        $this->post(route('esterilizacion.preparar', $case['record']->id), $this->preparacion() + ['sobres_recibidos' => 1, 'paquetes_recibidos' => 1])->assertRedirect();

        $this->assertSame(1, $case['record']->fresh()->sobres_recibidos);
        $this->assertCount(2, $case['record']->fresh()->codigos_material);
        $this->assertSame(0, $case['record']->ciclos()->count());
        $this->assertNull($case['record']->fresh()->fecha_recojo);
    }

    public function test_cycle_requires_preparation_and_rejects_missing_equipment_parameters(): void
    {
        $this->freezeTime();
        $case = $this->caso(['autorizado' => true, 'firma_docente' => 'data:image/png;base64,AAAA', 'fecha_entrega' => today()]);
        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.store', $case['record']->id), $this->datosCiclo())->assertUnprocessable();

        $this->postJson(route('esterilizacion.ciclos.store', $case['record']->id), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['equipo', 'numero_carga', 'protocolo', 'inicio', 'fin', 'temperatura', 'presion', 'minutos_exposicion']);

        $this->assertSame(0, $case['record']->ciclos()->count());
    }

    /** @return array<string, array{string, mixed}> */
    public static function valoresInvalidosCiclo(): array
    {
        return [
            'inicio futuro' => ['inicio', '+1 day'], 'fin futuro' => ['fin', '+1 day'],
            'fin anterior al inicio' => ['fin', '-2 hours'], 'antes de la preparación' => ['inicio', '-4 hours'],
            'temperatura negativa' => ['temperatura', -1], 'presión negativa' => ['presion', -1],
            'exposición cero' => ['minutos_exposicion', 0], 'exposición mayor al ciclo' => ['minutos_exposicion', 90],
        ];
    }

    #[DataProvider('valoresInvalidosCiclo')]
    public function test_cycle_rejects_invalid_times_and_physical_readings(string $field, mixed $value): void
    {
        $this->freezeTime();
        $case = $this->preparado();
        $payload = $this->datosCiclo();
        $payload[$field] = in_array($field, ['inicio', 'fin'], true) ? now()->modify($value)->toDateTimeString() : $value;

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.store', $case['record']->id), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame(0, $case['record']->ciclos()->count());
    }

    public function test_a_second_active_cycle_is_rejected_without_overwriting_the_first(): void
    {
        $this->freezeTime();
        $case = $this->preparado();
        $cycle = $this->ciclo($case);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.store', $case['record']->id), $this->datosCiclo())->assertUnprocessable();

        $this->assertSame(1, $case['record']->ciclos()->count());
        $this->assertSame('registrado', $cycle->fresh()->estado);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function controlesNoConformes(): array
    {
        return ['físico' => [['control_fisico' => 0]], 'químico' => [['control_quimico' => 0]], 'biológico' => [['control_biologico' => 'no_conforme']]];
    }

    #[DataProvider('controlesNoConformes')]
    public function test_failed_controls_preserve_the_review_and_block_release_and_pickup(array $failures): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $payload = array_replace($this->revision(), $failures, ['observaciones' => 'Resultado no conforme']);

        $this->actingAs($case['staff'])->post(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $payload)->assertRedirect();

        $this->assertSame('rechazado', $cycle->fresh()->estado);
        $this->assertCount(1, $cycle->fresh()->revisiones);
        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertUnprocessable();
        $this->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertUnprocessable();
        $this->assertNull($cycle->fresh()->fecha_liberacion);
        $this->assertNull($case['record']->fresh()->fecha_recojo);
    }

    public function test_pending_biological_results_block_release_until_a_new_review_is_recorded(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $this->actingAs($case['staff'])->post(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]),
            array_replace($this->revision(), ['control_biologico' => 'pendiente']))->assertRedirect();
        $this->assertSame('pendiente_resultados', $cycle->fresh()->estado);
        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertUnprocessable();

        $this->post(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $this->revision())->assertRedirect();

        $this->assertSame('revisado', $cycle->fresh()->estado);
        $this->assertCount(2, $cycle->fresh()->revisiones);
        $this->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $this->revision())->assertUnprocessable();
        $this->assertCount(2, $cycle->fresh()->revisiones);
    }

    public function test_a_non_applicable_biological_control_requires_protocol_justification(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $payload = array_replace($this->revision(), ['control_biologico' => 'no_aplica']);
        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('justificacion_biologico');
        $this->assertSame('registrado', $cycle->fresh()->estado);

        $this->post(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]),
            $payload + ['justificacion_biologico' => 'Referencia de protocolo validada por el responsable'])->assertRedirect();

        $this->post(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertRedirect();
        $this->assertSame('liberado', $cycle->fresh()->estado);
    }

    public function test_failed_controls_require_an_explanation_and_do_not_save_an_incomplete_review(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]),
            array_replace($this->revision(), ['control_fisico' => 0]))->assertUnprocessable()->assertJsonValidationErrors('observaciones');

        $this->assertSame('registrado', $cycle->fresh()->estado);
        $this->assertNull($cycle->fresh()->revisiones);
    }

    public function test_reprocessing_requires_new_preparation_and_keeps_the_failed_cycle(): void
    {
        $this->freezeTime();
        $case = $this->preparado();
        $failed = $this->ciclo($case);
        $this->actingAs($case['staff'])->post(route('esterilizacion.ciclos.revisar', [$case['record']->id, $failed->id]),
            array_replace($this->revision(), ['control_quimico' => 0, 'observaciones' => 'Indicador no conforme']))->assertRedirect();
        $this->postJson(route('esterilizacion.ciclos.store', $case['record']->id), $this->datosCiclo())->assertUnprocessable();
        $this->post(route('esterilizacion.preparar', $case['record']->id), $this->preparacion())->assertRedirect();
        $this->assertCount(2, $case['record']->fresh()->preparaciones);
        $this->travel(1)->hours();

        $this->post(route('esterilizacion.ciclos.store', $case['record']->id), $this->datosCiclo())->assertRedirect();

        $this->assertSame(2, $case['record']->ciclos()->count());
        $this->assertSame('rechazado', $failed->fresh()->estado);
        $this->assertSame(2, $case['record']->fresh()->ultimoCiclo->version_preparacion);
        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $failed->id]), $this->liberacion())->assertUnprocessable();
        $this->assertNull($case['record']->fresh()->fecha_recojo);
    }

    public function test_release_requires_review_and_explicit_package_integrity_confirmation(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertUnprocessable();

        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['empaque_integro', 'confirmar_liberacion']);

        $this->assertNull($cycle->fresh()->fecha_liberacion);
    }

    public function test_pickup_requires_confirmation_and_closes_the_record_to_further_processing(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $cycle->update(['estado' => 'liberado', 'control_fisico' => true, 'control_quimico' => true,
            'control_biologico' => 'conforme', 'fecha_revision' => now(), 'fecha_liberacion' => now(), 'liberado_por' => $case['staff']->id]);
        $this->actingAs($case['staff'])->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'entregar'])
            ->assertUnprocessable()->assertJsonValidationErrors('confirmar_recojo');
        $this->assertNull($case['record']->fresh()->fecha_recojo);

        $this->post(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'entregar', 'confirmar_recojo' => 1])->assertRedirect();

        $this->postJson(route('esterilizacion.preparar', $case['record']->id), $this->preparacion())->assertUnprocessable();
        $this->assertCount(1, $case['record']->fresh()->preparaciones);
        $this->assertSame($case['student']->id, $case['record']->fresh()->recogido_por);
    }

    public function test_cycles_from_another_control_cannot_be_reviewed_or_released(): void
    {
        $case = $this->preparado();
        $other = $this->preparado();
        $cycle = $this->ciclo($other);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $this->revision())->assertNotFound();
        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertNotFound();

        $this->assertSame('registrado', $cycle->fresh()->estado);
        $this->assertNull($cycle->fresh()->fecha_revision);
    }

    /** @return array<string, array{string}> */
    public static function rolesSinServicio(): array
    {
        return ['estudiante' => ['estudiante'], 'interno' => ['interno'], 'docente' => ['docente'], 'jefe de cátedra' => ['jefe-catedra']];
    }

    #[DataProvider('rolesSinServicio')]
    public function test_clinical_roles_cannot_process_material_even_with_a_legacy_management_permission(string $role): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $user = $role === 'docente' ? $case['teacher'] : ($role === 'estudiante' ? $case['student'] : $this->clinicalUser($role));
        $user->givePermissionTo('esterilizaciones.gestionar');
        $this->actingAs($user);

        $this->postJson(route('esterilizacion.movimiento', $case['record']->id), ['accion' => 'recibir'])->assertForbidden();
        $this->postJson(route('esterilizacion.preparar', $case['record']->id), $this->preparacion())->assertForbidden();
        $this->postJson(route('esterilizacion.ciclos.store', $case['record']->id), $this->datosCiclo())->assertForbidden();
        $this->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $this->revision())->assertForbidden();
        $this->postJson(route('esterilizacion.ciclos.liberar', [$case['record']->id, $cycle->id]), $this->liberacion())->assertForbidden();

        $this->assertSame('registrado', $cycle->fresh()->estado);
        $this->assertSame(1, $case['record']->ciclos()->count());
    }

    public function test_other_students_and_teachers_cannot_read_an_assigned_control(): void
    {
        $case = $this->preparado();

        $this->actingAs($this->clinicalUser('estudiante'))->get(route('esterilizacion.control', $case['record']->id))->assertNotFound();
        $this->actingAs($this->clinicalUser('docente'))->get(route('esterilizacion.control', $case['record']->id))->assertNotFound();

        $this->actingAs($case['student'])->get(route('esterilizacion.control', $case['record']->id))->assertOk()
            ->assertDontSee('name="control_fisico"', false)->assertDontSee('name="equipo"', false)->assertDontSee('name="empaque_integro"', false);
    }

    public function test_guests_are_redirected_to_login_for_the_control_and_cycle_actions(): void
    {
        $this->get(route('esterilizacion.control', 1))->assertRedirect(route('auth.login'));
        $this->post(route('esterilizacion.ciclos.store', 1), [])->assertRedirect(route('auth.login'));
    }

    public function test_authorized_records_cannot_be_edited_or_deleted_and_the_creator_is_preserved(): void
    {
        $case = $this->caso();
        $case['record']->update(['usuario_id' => $case['student']->id]);
        $payload = $this->solicitud($case) + ['usuario_id' => $case['staff']->id];
        $this->actingAs($case['staff'])->putJson(route('esterilizacion.update', $case['record']->id), $payload)->assertOk();
        $this->assertSame($case['student']->id, $case['record']->fresh()->usuario_id);
        $case['record']->update(['autorizado' => true]);

        $this->putJson(route('esterilizacion.update', $case['record']->id), $payload)->assertUnprocessable();
        $this->deleteJson(route('esterilizacion.destroy', $case['record']->id))->assertUnprocessable();

        $this->assertModelExists($case['record']);
        $this->assertSame($case['student']->id, $case['record']->fresh()->usuario_id);
    }

    public function test_disabled_controls_do_not_allow_preparation_or_release(): void
    {
        $case = $this->preparado();
        $cycle = $this->ciclo($case);
        $case['record']->update(['habilitada' => false]);

        $this->actingAs($case['staff'])->postJson(route('esterilizacion.preparar', $case['record']->id), $this->preparacion())->assertUnprocessable();
        $this->postJson(route('esterilizacion.ciclos.revisar', [$case['record']->id, $cycle->id]), $this->revision())->assertUnprocessable();

        $this->assertSame('registrado', $cycle->fresh()->estado);
        $this->assertNull($cycle->fresh()->fecha_revision);
    }

    public function test_control_escapes_descriptions_cycle_fields_and_review_notes(): void
    {
        $case = $this->preparado();
        $case['record']->update(['descripcion' => '<script>material</script>', 'info' => '<script>informacion</script>']);
        $cycle = $this->ciclo($case);
        $cycle->update(['equipo' => '<script>equipo</script>', 'protocolo' => '<script>protocolo</script>', 'observaciones' => '<script>resultado</script>']);

        $response = $this->actingAs($case['staff'])->get(route('esterilizacion.control', $case['record']->id));

        $response->assertOk()->assertSee('&lt;script&gt;material&lt;/script&gt;', false)->assertSee('&lt;script&gt;equipo&lt;/script&gt;', false)
            ->assertSee('&lt;script&gt;resultado&lt;/script&gt;', false)->assertDontSee('<script>material</script>', false)
            ->assertDontSee('<script>informacion</script>', false)->assertDontSee('<script>protocolo</script>', false);
    }

    public function test_permission_seed_is_idempotent_and_enables_supervisor_and_service_access(): void
    {
        $this->seed(EsterilizacionPermissionsSeeder::class);
        $case = $this->caso();
        $head = $this->clinicalUser('jefe-catedra');
        $head->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);
        $case['record']->update(['docente_id' => $head->id]);

        $this->actingAs($head)->get(route('esterilizacion.control', $case['record']->id))->assertOk()->assertSee('Firmar y autorizar');
        $this->assertTrue($head->can('esterilizaciones.autorizar'));
        $this->actingAs($this->clinicalUser('subadmin'))->get(route('esterilizacion.index'))->assertOk()->assertSee('Nuevo Registro');
    }

    /** @param array<string, mixed> $case
     * @return array<string, mixed>
     */
    private function solicitud(array $case): array
    {
        return ['estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id, 'clinica_id' => $case['clinic']->id,
            'periodo_id' => $case['history']->periodo_id, 'cantidad_sobres' => 2, 'cantidad_paquetes' => 1, 'habilitada' => true];
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function caso(array $attributes = []): array
    {
        $case = $this->clinicalCase();
        $case['staff'] = $this->clinicalUser('esterilizacion');
        $case['record'] = Esterilizacion::create(array_replace($this->solicitud($case), ['usuario_id' => $case['staff']->id], $attributes));

        return $case;
    }

    /** @return array<string, mixed> */
    private function preparado(): array
    {
        $case = $this->caso(['autorizado' => true, 'firma_docente' => 'data:image/png;base64,AAAA',
            'fecha_entrega' => today(), 'hora_entrega' => '08:00:00', 'sobres_recibidos' => 2, 'paquetes_recibidos' => 1,
            'fecha_preparacion' => now()->subHours(2)]);
        $id = $case['record']->id;
        $case['record']->update(['preparado_por' => $case['staff']->id, 'codigos_material' => ['EST-'.$id.'-S1', 'EST-'.$id.'-S2', 'EST-'.$id.'-P1'],
            'preparaciones' => [['fecha' => now()->subHours(2)->toIso8601String(), 'usuario_id' => $case['staff']->id,
                'responsable' => $case['staff']->nombre_completo, 'observacion' => null]]]);

        return $case;
    }

    /** @param array<string, mixed> $case */
    private function ciclo(array $case): EsterilizacionCiclo
    {
        return EsterilizacionCiclo::factory()->for($case['record'], 'esterilizacion')->create([
            'registrado_por' => $case['staff']->id, 'codigos_material' => $case['record']->codigos_material,
            'preparacion' => $case['record']->preparaciones[0],
        ]);
    }

    /** @return array<string, int> */
    private function preparacion(): array
    {
        return ['limpieza_confirmada' => 1, 'inspeccion_confirmada' => 1, 'empaque_confirmado' => 1];
    }

    /** @return array<string, mixed> */
    private function datosCiclo(): array
    {
        return ['equipo' => 'Autoclave de prueba', 'numero_carga' => 'CARGA-PRUEBA', 'protocolo' => 'Referencia de protocolo de prueba',
            'inicio' => now()->subHour()->toDateTimeString(), 'fin' => now()->subMinutes(20)->toDateTimeString(),
            'temperatura' => 121, 'presion' => 1.1, 'minutos_exposicion' => 15];
    }

    /** @return array{control_fisico: int, control_quimico: int, control_biologico: string} */
    private function revision(): array
    {
        return ['control_fisico' => 1, 'control_quimico' => 1, 'control_biologico' => 'conforme'];
    }

    /** @return array{empaque_integro: int, confirmar_liberacion: int} */
    private function liberacion(): array
    {
        return ['empaque_integro' => 1, 'confirmar_liberacion' => 1];
    }
}
