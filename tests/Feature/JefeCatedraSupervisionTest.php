<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Esterilizacion;
use App\Models\InsumoSolicitado;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class JefeCatedraSupervisionTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    private const FIRMA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=';

    public function test_dashboard_counts_only_assigned_clinics_and_links_to_accessible_modules(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $outside = $this->clinicalCase();
        $chief = $this->chief($case);
        $period = Periodo::where('habilitado', true)->orderByDesc('gestion')->firstOrFail();
        foreach ([$case, $outside] as $data) {
            $data['history']->update(['autorizado' => false, 'periodo_id' => $period->id]);
            $this->requestFor($data);
        }

        $cards = DashboardMetrics::tarjetas($chief);
        $this->assertSame(['1', '1', '1', '1'], array_column($cards, 'value'));
        $this->assertSame([route('historias.index'), route('solicitudes-insumos.index'), route('admisiones.index'), route('tratamientos-realizados.index')], array_column($cards, 'url'));

        $this->actingAs($chief);
        foreach ($cards as $card) {
            $this->get($card['url'])->assertOk();
        }
    }

    public function test_dashboard_ajax_charts_do_not_include_other_clinics(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $this->clinicalCase();
        $chief = $this->chief($case);

        $response = $this->actingAs($chief)->getJson(route('dashboard.stats'))->assertOk();
        $this->assertSame(1, array_sum($response->json('por_mes.valores')));
        $response->assertJsonPath('especialidades.valores', [1])->assertJsonPath('especialidades.labels', [$case['clinic']->especialidad]);
    }

    public function test_ajax_review_counts_change_after_authorizing_a_proposal(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $treatment = $this->treatmentFor($case);

        $this->actingAs($chief)->getJson(route('tratamientos-realizados.tabla'))
            ->assertOk()->assertJsonPath('resumenRevision.propuestas', 1);
        $this->postJson(route('tratamientos-realizados.autorizar', $treatment->id))->assertOk();
        $this->getJson(route('tratamientos-realizados.tabla'))->assertOk()
            ->assertJsonPath('resumenRevision.propuestas', 0)->assertJsonPath('total', 0);
    }

    public function test_chief_authorizes_a_history_supervised_by_another_teacher_in_the_same_clinic(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $case['history']->update(['autorizado' => false, 'estado' => 'revision']);

        $this->actingAs($chief)->post(route('historias.autorizar', $case['history']->id))->assertRedirect();
        $this->assertDatabaseHas('historias_clinicas', [
            'id' => $case['history']->id, 'autorizado' => true, 'docente_id' => $case['teacher']->id,
        ]);
        $treatment = $this->treatmentFor($case);
        $this->get(route('historias.show', $case['history']->id))->assertOk()
            ->assertSee('action="'.route('tratamientos-realizados.autorizar', $treatment->id).'"', false);
    }

    public function test_chief_authorizes_an_admission_without_replacing_its_responsible_teacher(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $case['admission']->update(['consentimiento_datos' => true, 'firma' => self::FIRMA]);

        $this->actingAs($chief)->postJson(route('admisiones.autorizar', $case['admission']->id))->assertOk();
        $this->assertDatabaseHas('admisiones', [
            'id' => $case['admission']->id, 'responsable_id' => $case['teacher']->id,
            'autorizado_por' => $chief->id, 'fecha_autorizacion' => now(),
        ]);
    }

    public function test_chief_can_view_authorize_and_reject_supply_requests_of_the_same_clinic(): void
    {
        $this->freezeTime();
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $approved = $this->requestFor($case);
        $rejected = $this->requestFor($case);

        $this->actingAs($chief)->getJson(route('solicitudes-insumos.tabla'))->assertOk()->assertJsonPath('total', 2);
        $this->postJson(route('solicitudes-insumos.autorizar', $approved->id))->assertOk();
        $this->postJson(route('solicitudes-insumos.rechazar', $rejected->id), ['motivo' => 'Revisión de cátedra'])->assertOk();

        $this->assertDatabaseHas('insumos_solicitados', [
            'id' => $approved->id, 'estado' => 'autorizado', 'docente_id' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);
        $this->assertDatabaseHas('insumos_solicitados', [
            'id' => $rejected->id, 'estado' => 'rechazado', 'rechazado_por' => $chief->id,
        ]);
    }

    public function test_chief_authorizes_unassigned_admissions_remitted_to_an_enabled_clinic(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $case['admission']->update([
            'responsable_id' => null, 'especialidad_responsable' => null,
            'estudiante_id' => null, 'especialidad_estudiante' => null,
            'consentimiento_datos' => true, 'firma' => self::FIRMA,
        ]);
        Remision::create([
            'admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'habilitado' => true, 'estado' => 'pendiente',
        ]);

        $this->actingAs($chief)->postJson(route('admisiones.autorizar', $case['admission']->id))->assertOk();
        $this->assertDatabaseHas('admisiones', [
            'id' => $case['admission']->id, 'estado' => 'derivada', 'autorizado_por' => $chief->id,
        ]);
    }

    public function test_chief_can_review_requests_linked_to_an_authorized_session_of_the_same_clinic(): void
    {
        $case = $this->approvedProgress();
        $chief = $this->chief($case);
        $request = $this->requestFor($case);
        $request->update(['tratamiento_realizado_id' => $case['realized']->id, 'sesion_atencion_id' => $case['session']->id]);

        $this->actingAs($chief)->postJson(route('solicitudes-insumos.autorizar', $request->id))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $request->id, 'estado' => 'autorizado']);
    }

    public function test_chief_can_sign_sterilization_requests_of_the_same_clinic(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $record = $this->sterilizationFor($case);

        $this->actingAs($chief)->get(route('esterilizacion.control', $record->id))->assertOk()->assertSee('Firmar y autorizar');
        $this->post(route('esterilizacion.autorizar', $record->id), ['firma_docente' => self::FIRMA])->assertRedirect();
        $this->assertDatabaseHas('esterilizaciones', [
            'id' => $record->id, 'autorizado' => true, 'firma_docente' => self::FIRMA, 'docente_id' => $case['teacher']->id,
        ]);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_foreign_and_revoked_clinics_cannot_be_viewed_or_authorized(bool $revoked): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $target = $revoked ? $case : $this->clinicalCase();
        if ($revoked) {
            $chief->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);
        }
        $request = $this->requestFor($target);
        $sterilization = $this->sterilizationFor($target);
        $target['history']->update(['autorizado' => false]);

        $this->actingAs($chief);
        $this->get(route('historias.show', $target['history']->id))->assertNotFound();
        $this->postJson(route('historias.autorizar', $target['history']->id))->assertNotFound();
        $this->postJson(route('admisiones.autorizar', $target['admission']->id))->assertNotFound();
        $this->getJson(route('solicitudes-insumos.datos', $request->id))->assertNotFound();
        $this->postJson(route('solicitudes-insumos.autorizar', $request->id))->assertNotFound();
        $this->get(route('esterilizacion.control', $sterilization->id))->assertNotFound();
        $this->postJson(route('esterilizacion.autorizar', $sterilization->id), ['firma_docente' => self::FIRMA])->assertNotFound();

        $this->assertDatabaseHas('historias_clinicas', ['id' => $target['history']->id, 'autorizado' => false]);
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $request->id, 'estado' => 'pendiente']);
        $this->assertDatabaseHas('esterilizaciones', ['id' => $sterilization->id, 'autorizado' => false]);
    }

    public function test_revoking_a_chiefs_clinic_blocks_records_even_when_the_chief_was_the_assigned_teacher(): void
    {
        $case = $this->clinicalCase();
        $chief = $this->chief($case);
        $case['history']->update(['docente_id' => $chief->id, 'autorizado' => false]);
        $case['admission']->update(['responsable_id' => $chief->id]);
        $chief->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);

        $this->actingAs($chief)->postJson(route('historias.autorizar', $case['history']->id))->assertNotFound();
        $this->postJson(route('admisiones.autorizar', $case['admission']->id))->assertNotFound();
        $this->assertSame(['0', '0', '0', '0'], array_column(DashboardMetrics::tarjetas($chief), 'value'));
    }

    public function test_teachers_still_cannot_authorize_another_teachers_history_or_admission(): void
    {
        $case = $this->clinicalCase();
        $teacher = $this->clinicalUser('docente');
        $teacher->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        $this->actingAs($teacher)->postJson(route('historias.autorizar', $case['history']->id))->assertNotFound();
        $this->postJson(route('admisiones.autorizar', $case['admission']->id))->assertNotFound();
    }

    /** @param array<string, mixed> $case */
    private function chief(array $case): Usuario
    {
        $chief = $this->clinicalUser('jefe-catedra');
        $chief->especialidades()->attach($case['clinic']->id, ['habilitado' => true]);

        return $chief;
    }

    /** @param array<string, mixed> $case */
    private function requestFor(array $case): InsumoSolicitado
    {
        $treatment = TratamientoRealizado::query()->firstOrCreate([
            'historia_clinica_id' => $case['history']->id,
            'tratamiento_id' => $case['treatment']->id,
        ], [
            'clinica_id' => $case['clinic']->id, 'estado' => 'autorizado',
            'costo_cobrado' => $case['treatment']->costo,
            'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);
        $treatment->update([
            'estado' => $treatment->estado === 'pendiente' ? 'autorizado' : $treatment->estado,
            'costo_cobrado' => $case['treatment']->costo,
            'autorizado_por' => $treatment->autorizado_por ?? $case['teacher']->id,
            'fecha_autorizacion' => $treatment->fecha_autorizacion ?? now(),
        ]);
        if (! $treatment->cajas()->where('habilitada', true)->exists()) {
            Caja::create([
                'tratamiento_realizado_id' => $treatment->id, 'paciente_id' => $case['history']->paciente_id,
                'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id,
                'periodo_id' => $case['history']->periodo_id, 'usuario_id' => $case['teacher']->id,
                'recibo' => 700000000 + $treatment->id, 'monto' => 100, 'costo_total' => 100,
                'cancelado' => 100, 'habilitada' => true, 'ci' => 0, 'tipo' => 'tratamiento',
            ]);
        }

        return InsumoSolicitado::create([
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id,
            'tratamiento_realizado_id' => $treatment->id,
            'clinica_id' => $case['clinic']->id, 'usuario_id' => $case['student']->id,
            'docente_id' => $case['teacher']->id, 'estado' => 'pendiente', 'fecha_solicitud' => today(),
        ]);
    }

    /** @param array<string, mixed> $case */
    private function sterilizationFor(array $case): Esterilizacion
    {
        return Esterilizacion::create([
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'usuario_id' => $case['student']->id, 'cantidad_sobres' => 1, 'cantidad_paquetes' => 0, 'habilitada' => true,
        ]);
    }

    /** @param array<string, mixed> $case */
    private function treatmentFor(array $case): TratamientoRealizado
    {
        return TratamientoRealizado::create([
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'estado' => 'pendiente',
        ]);
    }
}
