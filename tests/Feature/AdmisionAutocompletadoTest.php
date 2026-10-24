<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Remision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class AdmisionAutocompletadoTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_latest_admission_uses_admission_date_and_belongs_to_the_selected_patient(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
        $patient = $this->registeredPatient();
        $this->previousAdmission($patient, ['fecha_admision' => '2026-09-30', 'telefono' => '11111111'], '2026-10-05 10:00:00');
        $latest = $this->previousAdmission($patient, ['fecha_admision' => '2026-10-01', 'telefono' => '07000001'], '2026-08-01 10:00:00');
        $this->previousAdmission($this->registeredPatient(), ['fecha_admision' => '2026-10-05', 'telefono' => '99999999']);

        $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('id', $patient->id)->assertJsonPath('ci', $patient->ci)
            ->assertJsonPath('nombre_completo', 'Paciente Autocompletado Prueba')
            ->assertJsonPath('genero', 'F')->assertJsonPath('fecha_nacimiento', '1990-01-01')
            ->assertJsonPath('edad', 36)->assertJsonPath('admision_anterior.id', $latest->id)
            ->assertJsonPath('admision_anterior.fecha_admision', '2026-10-01')
            ->assertJsonPath('admision_anterior.datos.telefono', '07000001');
    }

    public function test_admission_without_a_date_uses_creation_date_for_ordering_and_its_label(): void
    {
        $patient = $this->registeredPatient();
        $this->previousAdmission($patient, ['fecha_admision' => '2026-09-01'], '2026-10-06 10:00:00');
        $latest = $this->previousAdmission($patient, ['fecha_admision' => null, 'direccion' => 'Dirección reciente'], '2026-10-05 10:00:00');

        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $latest->id)
            ->assertJsonPath('admision_anterior.fecha_admision', '2026-10-05')
            ->assertJsonPath('admision_anterior.datos.direccion', 'Dirección reciente');
    }

    public function test_admissions_on_the_same_date_use_creation_time_before_id(): void
    {
        $patient = $this->registeredPatient();
        $latest = $this->previousAdmission($patient, ['fecha_admision' => '2026-10-01'], '2026-10-02 10:00:00');
        $this->previousAdmission($patient, ['fecha_admision' => '2026-10-01'], '2026-10-02 09:00:00');

        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $latest->id);
    }

    public function test_admissions_with_identical_dates_and_creation_times_use_the_last_id(): void
    {
        $patient = $this->registeredPatient();
        $this->previousAdmission($patient, ['fecha_admision' => '2026-10-01'], '2026-10-02 09:00:00');
        $latest = $this->previousAdmission($patient, ['fecha_admision' => '2026-10-01'], '2026-10-02 09:00:00');

        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $latest->id);
    }

    public function test_prefill_preserves_zero_false_decimal_and_phone_values(): void
    {
        $patient = $this->registeredPatient();
        $this->previousAdmission($patient, [
            'telefono' => '007000001', 'telefono_emergencia' => '000700002',
            'pulso' => 0, 'peso_kg' => 0, 'talla_mts' => 1.65,
            'asimetria_facial' => false, 'cambio_color' => true, 'abultamiento' => null,
            'antecedentes' => 'Alergia referida', 'gpe' => 'Regular',
            'bm_estado' => 'Con alteraciones', 'bm' => 'Observación conservada',
            'odontograma_denticion' => 'mixto', 'odontograma_pintado' => '{"55":{"o":"caries"}}',
        ]);

        $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.datos.telefono', '007000001')
            ->assertJsonPath('admision_anterior.datos.telefono_emergencia', '000700002')
            ->assertJsonPath('admision_anterior.datos.pulso', 0)
            ->assertJsonPath('admision_anterior.datos.peso_kg', '0.00')
            ->assertJsonPath('admision_anterior.datos.talla_mts', '1.65')
            ->assertJsonPath('admision_anterior.datos.asimetria_facial', false)
            ->assertJsonPath('admision_anterior.datos.cambio_color', true)
            ->assertJsonPath('admision_anterior.datos.abultamiento', null)
            ->assertJsonPath('admision_anterior.datos.antecedentes', 'Alergia referida')
            ->assertJsonPath('admision_anterior.datos.gpe', 'Regular')
            ->assertJsonPath('admision_anterior.datos.bm', 'Observación conservada')
            ->assertJsonPath('admision_anterior.datos.odontograma_denticion', 'mixto')
            ->assertJsonPath('admision_anterior.datos.odontograma_pintado', '{"55":{"o":"caries"}}');
    }

    public function test_prefill_excludes_signatures_consents_and_previous_academic_assignments(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->update([
            'firma' => 'data:image/png;base64,AAAA', 'consentimiento_datos' => true,
            'consentimiento_fotografias' => true, 'consentimiento_investigacion' => true,
            'area_derivada' => 'Plan de atención anterior', 'estado' => 'autorizada',
            'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);

        $response = $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', $case['admission']->paciente_id));

        $response->assertOk()->assertJsonPath('admision_anterior.id', $case['admission']->id)
            ->assertJsonMissingPaths(array_map(fn (string $field): string => 'admision_anterior.datos.'.$field, [
                'id', 'paciente_id', 'periodo_id', 'estudiante_id', 'responsable_id', 'especialidad_estudiante',
                'especialidad_responsable', 'registrado_por', 'clinica_ids', 'remisiones', 'area_derivada',
                'firma', 'consentimiento_datos', 'consentimiento_fotografias', 'consentimiento_investigacion',
                'estado', 'autorizado_por', 'fecha_autorizacion', 'fecha_admision', 'created_at', 'updated_at',
            ]));
    }

    public function test_disabled_consultation_reason_keeps_its_name_for_the_previous_admission(): void
    {
        $patient = $this->registeredPatient();
        $reason = MotivoConsulta::create(['nombre' => 'Motivo anterior inhabilitado', 'descripcion' => '', 'habilitado' => false]);
        $this->previousAdmission($patient, ['motivo_consulta_id' => $reason->id]);

        $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.datos.motivo_consulta_id', $reason->id)
            ->assertJsonPath('admision_anterior.motivo_consulta_nombre', 'Motivo anterior inhabilitado');
    }

    public function test_registered_patient_without_admissions_returns_null_previous_admission(): void
    {
        $patient = $this->registeredPatient();
        $patient->update([
            'telefono' => '007000009',
            'direccion' => 'Zona Central',
            'emergencia' => 'Familiar',
        ]);

        $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('id', $patient->id)->assertJsonPath('admision_anterior', null)
            ->assertJsonPath('telefono', '007000009')->assertJsonPath('direccion', 'Zona Central')
            ->assertJsonPath('emergencia', 'Familiar')->assertJsonPath('historia_clinica', null);
    }

    public function test_create_form_accepts_a_patient_and_exposes_their_accessible_history(): void
    {
        $case = $this->clinicalCase();
        $operator = $this->clinicalUser('admision');

        $this->actingAs($operator)->get(route('admisiones.create', ['paciente_id' => $case['admission']->paciente_id]))
            ->assertOk()
            ->assertViewHas('pacienteInicial', fn (Paciente $patient): bool => $patient->is($case['admission']->paciente))
            ->assertSee('id="adm-paciente-id" value="'.$case['admission']->paciente_id.'"', false)
            ->assertSee($case['admission']->paciente->nombre_completo);

        $this->getJson(route('admisiones.buscar-pacientes.datos', $case['admission']->paciente_id))
            ->assertOk()
            ->assertJsonPath('historia_clinica.id', $case['history']->id);
    }

    public function test_missing_patient_returns_404(): void
    {
        $this->actingAs($this->clinicalUser('admision'))->getJson(route('admisiones.buscar-pacientes.datos', 2147483647))->assertNotFound();
    }

    public function test_patient_prefill_without_read_permission_returns_403(): void
    {
        $patient = $this->registeredPatient();

        $this->actingAs($this->clinicalUser('insumos'))->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))->assertForbidden();
    }

    public function test_unauthenticated_patient_prefill_redirects_to_login(): void
    {
        $this->get(route('admisiones.buscar-pacientes.datos', 2147483647))->assertRedirect(route('auth.login'));
    }

    public function test_student_cannot_prefill_another_students_admission(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->update(['telefono' => '07000099', 'antecedentes' => 'Antecedente reservado']);

        $this->actingAs($this->clinicalUser('estudiante'))->getJson(route('admisiones.buscar-pacientes.datos', $case['admission']->paciente_id))
            ->assertOk()->assertJsonPath('id', $case['admission']->paciente_id)->assertJsonPath('admision_anterior', null)
            ->assertJsonPath('historia_clinica', null)
            ->assertJsonMissing(['telefono' => '07000099', 'antecedentes' => 'Antecedente reservado']);
    }

    public function test_student_receives_latest_accessible_source_instead_of_a_newer_foreign_admission(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->update(['fecha_admision' => '2026-09-01', 'antecedentes' => 'Antecedente propio']);
        $this->previousAdmission($case['admission']->paciente, ['fecha_admision' => '2026-10-01', 'antecedentes' => 'Antecedente de otro autor']);

        $this->actingAs($case['student'])->getJson(route('admisiones.buscar-pacientes.datos', $case['admission']->paciente_id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $case['admission']->id)
            ->assertJsonPath('admision_anterior.datos.antecedentes', 'Antecedente propio')
            ->assertJsonMissing(['antecedentes' => 'Antecedente de otro autor']);
    }

    public function test_student_can_prefill_an_unassigned_admission_that_they_registered(): void
    {
        $patient = $this->registeredPatient();
        $student = $this->clinicalUser('estudiante');
        $previous = $this->previousAdmission($patient, ['registrado_por' => $student->id, 'telefono' => '07000007']);

        $this->actingAs($student)->getJson(route('admisiones.buscar-pacientes.datos', $patient->id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $previous->id)
            ->assertJsonPath('admision_anterior.datos.telefono', '07000007');
    }

    public function test_teacher_prefill_uses_only_admissions_accessible_to_their_specialty(): void
    {
        $case = $this->clinicalCase();
        $case['admission']->update(['fecha_admision' => '2026-09-01', 'direccion' => 'Dirección accesible']);
        $this->previousAdmission($case['admission']->paciente, ['fecha_admision' => '2026-10-01', 'direccion' => 'Dirección ajena']);

        $this->actingAs($case['teacher'])->getJson(route('admisiones.buscar-pacientes.datos', $case['admission']->paciente_id))
            ->assertOk()->assertJsonPath('admision_anterior.id', $case['admission']->id)
            ->assertJsonPath('admision_anterior.datos.direccion', 'Dirección accesible')
            ->assertJsonMissing(['direccion' => 'Dirección ajena']);
    }

    public function test_saving_reused_values_creates_a_new_admission_with_independent_referrals(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
        $case = $this->clinicalCase();
        $previous = $case['admission'];
        $previous->update(['fecha_admision' => '2026-09-01', 'gpe' => 'Bueno', 'telefono' => '007000001', 'antecedentes' => 'Antecedente que se reutiliza', 'firma' => 'data:image/png;base64,AAAA', 'consentimiento_datos' => true]);
        $oldReferral = Remision::create(['admision_id' => $previous->id, 'paciente_id' => $previous->paciente_id, 'clinica_id' => $case['clinic']->id, 'periodo_id' => $previous->periodo_id, 'estado' => 'pendiente', 'habilitado' => true]);
        $originalAdmission = $previous->fresh()->getAttributes();
        $originalReferral = $oldReferral->fresh()->getAttributes();
        $newClinic = Clinica::create(['especialidad' => 'Nueva área de atención', 'modulo' => 'Prueba', 'curso' => 1]);
        $operator = $this->clinicalUser('admision');
        $source = $this->actingAs($operator)->getJson(route('admisiones.buscar-pacientes.datos', $previous->paciente_id))->assertOk();

        $response = $this->postJson(route('admisiones.store'), $source->json('admision_anterior.datos') + ['paciente_id' => $previous->paciente_id, 'clinica_ids' => [$newClinic->id]])->assertCreated();

        $newAdmission = Admision::findOrFail($response->json('id'));
        $this->assertNotSame($previous->id, $newAdmission->id);
        $this->assertDatabaseHas('admisiones', [
            'id' => $newAdmission->id, 'paciente_id' => $previous->paciente_id, 'telefono' => '007000001',
            'antecedentes' => 'Antecedente que se reutiliza', 'fecha_admision' => '2026-10-06', 'registrado_por' => $operator->id,
            'estado' => 'revision', 'estudiante_id' => null, 'responsable_id' => null, 'periodo_id' => null,
            'consentimiento_datos' => false, 'consentimiento_fotografias' => false, 'consentimiento_investigacion' => false,
        ]);
        $this->assertEmpty($newAdmission->firma);
        $this->assertSame([$newClinic->id], $newAdmission->remisiones()->pluck('clinica_id')->all());
        $this->assertSame($originalAdmission, $previous->fresh()->getAttributes());
        $this->assertSame($originalReferral, $oldReferral->fresh()->getAttributes());
    }

    public function test_create_and_edit_forms_render_prefill_configuration_and_preserve_legacy_select_values(): void
    {
        $case = $this->clinicalCase();
        $reason = MotivoConsulta::create(['nombre' => 'Motivo conservado', 'descripcion' => '', 'habilitado' => false]);
        $case['admission']->update([
            'motivo_consulta_id' => $reason->id, 'calculo' => 'Presente', 'oclusion' => 'Clase I',
            'telefono' => '007000001', 'antecedentes' => 'Evaluación propia de esta ficha',
        ]);

        $this->actingAs($this->clinicalUser('admision'))->get(route('admisiones.create'))->assertOk()
            ->assertViewHas('camposAutocompletar', fn (array $fields): bool => in_array('telefono', $fields, true) && in_array('odontograma_pintado', $fields, true) && ! in_array('firma', $fields, true))
            ->assertSee('admision-autocompletado.js', false)->assertSee('inicializarFormularioAdmision')
            ->assertSee('modo: "crear"', false)->assertSee('__ID__', false);
        $edit = $this->get(route('admisiones.edit', $case['admission']->id))->assertOk()
            ->assertSee('modo: "editar"', false)->assertSee('Evaluación propia de esta ficha')
            ->assertSee('value="007000001"', false)
            ->assertSee('<option value="'.$reason->id.'" selected>Motivo registrado en la ficha</option>', false)
            ->assertSee('<option value="Presente" selected>Presente</option>', false)
            ->assertSee('<option value="Clase I" selected>Clase I</option>', false);
        $this->assertMatchesRegularExpression('/id="adm-paciente-buscar"[^>]*\breadonly/', $edit->getContent());
        $this->assertMatchesRegularExpression('/class="autocomplete-clear"[^>]*\bdisabled/', $edit->getContent());
    }

    private function registeredPatient(): Paciente
    {
        return Paciente::create([
            'ci' => substr((string) Str::uuid(), 0, 12), 'nombre' => 'Paciente', 'apellido_paterno' => 'Autocompletado',
            'apellido_materno' => 'Prueba', 'genero' => 'F', 'fecha_nacimiento' => '1990-01-01',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function previousAdmission(Paciente $patient, array $attributes = [], string $createdAt = '2026-10-06 08:00:00'): Admision
    {
        $admission = Admision::create(array_replace(['paciente_id' => $patient->id, 'gpe' => 'Bueno', 'fecha_admision' => '2026-10-01'], $attributes));
        $admission->forceFill(['created_at' => $createdAt])->save();

        return $admission;
    }
}
