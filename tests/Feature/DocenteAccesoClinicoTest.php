<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\InsumoTratamientoUsado;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\Usuario;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class DocenteAccesoClinicoTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_teacher_cannot_find_foreign_patients_in_html_ajax_api_or_autocomplete(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);
        $foreignPatient = $other['admission']->paciente;

        $this->actingAs($own['teacher'])->get(route('pacientes.index'))->assertOk()
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$own['history']->paciente_id]);
        $this->getJson(route('pacientes.datos', $foreignPatient->id))->assertNotFound();
        $this->getJson(route('api.v1.pacientes.show', $foreignPatient->id))->assertNotFound();
        $this->getJson(route('api.v1.pacientes.index'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('pacientes.tabla', ['buscar' => $foreignPatient->ci]))->assertOk()->assertJsonPath('total', 0);
        $this->get(route('pacientes.reporte', ['buscar' => $foreignPatient->ci]))->assertOk()
            ->assertViewHas('resumen', ['Registros encontrados' => '0', 'Registros incluidos' => '0']);
        foreach (['admisiones.buscar-pacientes', 'radiologia.buscar-pacientes'] as $route) {
            $this->getJson(route($route, ['q' => $foreignPatient->ci]))->assertOk()->assertExactJson([]);
        }
        $this->getJson(route('admisiones.buscar-pacientes.datos', $foreignPatient->id))->assertNotFound();
    }

    public function test_teacher_reports_only_aggregate_assigned_cases_including_payments_and_consumption(): void
    {
        $this->freezeTime();
        $own = $this->approvedProgress();
        $other = $this->otherCaseInSameClinic($own, true);
        $type = InsumoTratamiento::create(['clinica_id' => $own['clinic']->id, 'nombre' => 'Tipo prueba']);
        $supply = Insumo::create(['nombre' => 'Material prueba', 'clinica_id' => $own['clinic']->id, 'insumo_tratamiento_id' => $type->id, 'cantidad_numerica' => 10]);
        foreach ([$own, $other] as $case) {
            Esterilizacion::create(['estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
                'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id, 'usuario_id' => $case['student']->id,
                'cantidad_sobres' => 1, 'cantidad_paquetes' => 0, 'habilitada' => true]);
            InsumoTratamientoUsado::create(['insumo_id' => $supply->id, 'tratamiento_realizado_id' => $case['realized']->id,
                'usuario_id' => $case['student']->id, 'cantidad_usada' => 1, 'cantidad_solicitada' => 1, 'costo_al_momento' => 1, 'fecha_uso' => today()]);
            $receipt = Caja::create(['paciente_id' => $case['history']->paciente_id, 'tratamiento_realizado_id' => $case['realized']->id,
                'recibo' => (int) Caja::max('recibo') + 1, 'monto' => 100, 'costo_total' => 100, 'cancelado' => 100, 'habilitada' => true,
                'descripcion' => 'Prueba', 'ci' => 0, 'tipo' => 'radiologia', 'tipo_pago' => 'efectivo',
                'paciente_nombre_completo' => $case['admission']->paciente->nombre_completo,
                'grado' => 'adulto', 'clinica_id' => $case['clinic']->id, 'usuario_id' => $case['teacher']->id, 'tratamiento_id' => $case['treatment']->id]);
            Radiologia::create(['paciente_id' => $case['history']->paciente_id, 'clinica_id' => $case['clinic']->id, 'caja_id' => $receipt->id,
                'usuario_id' => $case['student']->id, 'descripcion' => 'Prueba', 'observacion' => 'Prueba', 'estado' => 'pendiente']);
        }

        $this->actingAs($own['teacher']);
        $this->getJson(route('radiologia.buscar-cajas'))->assertOk()->assertJsonCount(1);
        foreach (['pacientes-por-periodo', 'pacientes-por-especialidad', 'pacientes-por-edad', 'pacientes-por-sexo',
            'motivos-consulta', 'tratamientos-por-especialidad', 'atenciones-por-docente', 'atenciones-por-estudiante',
            'insumos-utilizados', 'radiografias-por-mes', 'esterilizaciones-por-mes', 'pagos-por-tipo', 'ingresos-por-mes'] as $report) {
            $this->getJson(route('reportes.'.$report))->assertOk()->assertJsonPath('total',
                in_array($report, ['pagos-por-tipo', 'ingresos-por-mes'], true) ? 100 : 1);
        }
        $this->getJson(route('reportes.atenciones-por-docente'))->assertJsonPath('labels', [$own['teacher']->nombre_completo]);
    }

    public function test_teacher_only_sees_assigned_histories_in_html_ajax_and_api(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);

        $this->actingAs($own['teacher'])->get(route('historias.index'))->assertOk()
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$own['history']->id]);
        $this->get(route('historias.show', $own['history']->id))->assertOk();
        $this->get(route('historias.show', $other['history']->id))->assertNotFound();
        $this->getJson(route('historias.datos', $other['history']->id))->assertNotFound();
        $this->getJson(route('historias.tabla', ['buscar' => $other['admission']->paciente->ci]))->assertOk()->assertJsonPath('total', 0);
        $this->getJson(route('api.v1.historias.index'))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own['history']->id);
        $this->getJson(route('api.v1.historias.show', $other['history']->id))->assertNotFound();
    }

    public function test_teacher_cannot_read_or_authorize_another_teachers_treatment_and_progress(): void
    {
        $own = $this->approvedProgress();
        $other = $this->otherCaseInSameClinic($own, true);

        $this->actingAs($own['teacher'])->getJson(route('tratamientos-realizados.tabla'))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('tratamientos-realizados.tabla', ['buscar' => $other['admission']->paciente->ci]))->assertOk()->assertJsonPath('total', 0);
        $this->postJson(route('tratamientos-realizados.autorizar', $other['realized']->id))->assertNotFound();
        $this->postJson(route('sesiones-atencion.autorizar', $other['session']->id))->assertNotFound();
        $this->assertDatabaseHas('tratamientos_realizados', [
            'id' => $other['realized']->id, 'autorizado_por' => $other['teacher']->id,
        ]);
        $this->assertDatabaseHas('sesiones_atencion', [
            'id' => $other['session']->id, 'autorizado_por' => $other['teacher']->id,
        ]);
    }

    public function test_admissions_assigned_to_another_teacher_are_hidden_even_if_registered_by_the_viewer(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);
        $other['admission']->update(['registrado_por' => $own['teacher']->id]);

        $this->actingAs($own['teacher'])->get(route('admisiones.show', $own['admission']->id))->assertOk();
        $this->get(route('admisiones.show', $other['admission']->id))->assertNotFound();
        $this->getJson(route('admisiones.datos', $other['admission']->id))->assertNotFound();
        $this->get(route('admisiones.ficha-pdf', $other['admission']->id))->assertNotFound();
        $this->postJson(route('admisiones.autorizar', $other['admission']->id))->assertNotFound();
        $this->assertDatabaseHas('admisiones', ['id' => $other['admission']->id, 'autorizado_por' => null]);
    }

    public function test_teacher_inbox_keeps_pending_intake_and_own_referrals_but_hides_other_assignments(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);
        $pending = $this->referralFor($own);
        $assigned = $this->referralFor($own, $own['teacher']);
        $foreign = $this->referralFor($other, $other['teacher']);

        $this->actingAs($own['teacher'])->get(route('pacientes-remitidos.index'))->assertOk()
            ->assertViewHas('registros', fn ($records): bool => $records->getCollection()->modelKeys() === [$assigned->id, $pending->id]);
        $this->get(route('pacientes-remitidos.asignacion', $pending->id))->assertOk();
        $this->get(route('pacientes-remitidos.asignacion', $foreign->id))->assertNotFound();
        $this->getJson(route('pacientes-remitidos.buscar', ['buscar' => $other['admission']->paciente->ci]))
            ->assertOk()->assertJsonPath('total', 0);
    }

    public function test_pending_admission_stops_being_visible_after_referral_assignment_to_another_teacher(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);
        $other['history']->delete();
        $other['admission']->update(['responsable_id' => null, 'especialidad_responsable' => null]);
        $referral = $this->referralFor($other);

        $this->actingAs($own['teacher'])->get(route('admisiones.show', $other['admission']->id))->assertOk();
        $referral->update(['estado' => 'asignada', 'docente_id' => $other['teacher']->id, 'estudiante_id' => $other['student']->id]);
        $this->get(route('admisiones.show', $other['admission']->id))->assertNotFound();
    }

    public function test_teacher_dashboard_excludes_other_cases_and_counts_each_student_once(): void
    {
        $this->freezeTime();
        $own = $this->approvedProgress();
        $other = $this->otherCaseInSameClinic($own, true);
        $own['history']->update(['autorizado' => false]);
        HistoriaClinica::create($own['history']->only([
            'admision_id', 'paciente_id', 'periodo_id', 'clinica_id', 'estudiante_id', 'docente_id',
            'especialidad_estudiante', 'especialidad_docente',
        ]) + ['autorizado' => true, 'estado' => 'abierta']);
        foreach ([$own, $other] as $case) {
            InsumoSolicitado::create([
                'historia_clinica_id' => $case['history']->id, 'clinica_id' => $case['clinic']->id,
                'usuario_id' => $own['student']->id, 'docente_id' => $case['teacher']->id,
                'estado' => 'pendiente', 'fecha_solicitud' => today(),
            ]);
        }

        $this->assertSame(['1', '1', '1', '1'], array_column(DashboardMetrics::tarjetas($own['teacher']), 'value'));
        $response = $this->actingAs($own['teacher'])->getJson(route('dashboard.stats'))->assertOk();
        $this->assertSame(1, array_sum($response->json('por_mes.valores')));
        $response->assertJsonPath('especialidades.valores', [1]);
        $this->getJson(route('api.v1.dashboard'))->assertOk()->assertJsonPath('data.especialidades.valores', [1]);
    }

    public function test_chief_keeps_all_cases_of_enabled_clinics_and_jefatura_keeps_global_visibility(): void
    {
        $own = $this->clinicalCase();
        $other = $this->otherCaseInSameClinic($own);
        $outside = $this->clinicalCase();
        $chief = $this->clinicalUser('jefe-catedra');
        $chief->especialidades()->attach($own['clinic']->id, ['habilitado' => true]);

        $this->actingAs($chief)->get(route('historias.show', $own['history']->id))->assertOk();
        $this->get(route('historias.show', $other['history']->id))->assertOk();
        $this->get(route('historias.show', $outside['history']->id))->assertNotFound();
        $this->getJson(route('reportes.pacientes-por-especialidad', ['clinica_id' => $own['clinic']->id]))->assertOk()->assertJsonPath('total', 2);
        $this->getJson(route('reportes.pacientes-por-especialidad', ['clinica_id' => $outside['clinic']->id]))->assertOk()->assertJsonPath('total', 0);
        $this->actingAs($this->clinicalUser('jefatura-clinicas'))->get(route('historias.show', $outside['history']->id))->assertOk();
        $this->getJson(route('reportes.pacientes-por-especialidad', ['clinica_id' => $outside['clinic']->id]))->assertOk()->assertJsonPath('total', 1);
    }

    /** @param array<string, mixed> $own
     * @return array<string, mixed>
     */
    private function otherCaseInSameClinic(array $own, bool $withProgress = false): array
    {
        $other = $withProgress ? $this->approvedProgress() : $this->clinicalCase();
        $other['teacher']->especialidades()->attach($own['clinic']->id, ['habilitado' => true]);
        $other['student']->especialidades()->attach($own['clinic']->id, ['habilitado' => true]);
        $other['admission']->update(['especialidad_responsable' => $own['clinic']->id, 'especialidad_estudiante' => $own['clinic']->id]);
        $other['history']->update(['clinica_id' => $own['clinic']->id, 'especialidad_docente' => $own['clinic']->id]);
        $other['treatment']->update(['clinica_id' => $own['clinic']->id]);
        if ($withProgress) {
            $other['realized']->update(['clinica_id' => $own['clinic']->id]);
        }
        $other['clinic'] = $own['clinic'];

        return $other;
    }

    /** @param array<string, mixed> $case */
    private function referralFor(array $case, ?Usuario $teacher = null): Remision
    {
        return Remision::create([
            'admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'estado' => $teacher ? 'asignada' : 'pendiente', 'docente_id' => $teacher?->id,
            'estudiante_id' => $teacher ? $case['student']->id : null, 'habilitado' => true,
        ]);
    }
}
