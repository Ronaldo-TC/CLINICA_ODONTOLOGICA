<?php

namespace Tests\Feature;

use App\Models\Admision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class FlujoAdmisionTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_admission_opens_in_a_dedicated_page(): void
    {
        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.create'))->assertSee('Historia de la enfermedad actual')->assertSee('formAdmision', false)->assertDontSee('modalAdmision', false);
    }

    public function test_lookup_returns_only_enabled_specialties_of_selected_student(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.especialidades-usuario', ['usuario' => $case['student']->id, 'tipo' => 'estudiante']))->assertJsonCount(1)->assertJsonPath('0.id', $case['clinic']->id);

        $case['student']->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);
        $this->getJson(route('admisiones.especialidades-usuario', ['usuario' => $case['student']->id, 'tipo' => 'estudiante']))->assertExactJson([]);
    }

    public function test_student_cannot_edit_another_students_admission(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($this->clinicalUser('estudiante'))->get(route('admisiones.edit', $case['admission']->id))->assertNotFound();
    }

    public function test_admission_saves_temporary_teeth_and_expanded_conditions(): void
    {
        $case = $this->clinicalCase();
        $data = $case['admission']->only(['paciente_id', 'periodo_id', 'estudiante_id', 'especialidad_estudiante', 'responsable_id', 'especialidad_responsable']);
        $data['odontograma_denticion'] = 'mixto';
        $data['gpe'] = 'Bueno';
        $data['odontograma_pintado'] = json_encode(['55' => ['o' => 'corona_temporal', 'marcas' => [['estado' => 'supernumerario', 'color' => 'azul']]], '16' => ['m' => 'caries'], '59' => ['o' => 'caries']]);

        $response = $this->actingAs($case['student'])->postJson(route('admisiones.store'), $data)->assertCreated();

        $saved = Admision::findOrFail($response->json('id'));
        $this->assertSame('mixto', $saved->odontograma_denticion);
        $this->assertSame(['55' => ['o' => 'corona_temporal', 'marcas' => [['estado' => 'supernumerario', 'color' => 'azul']]], '16' => ['m' => 'caries']], json_decode($saved->odontograma_pintado, true));
    }

    public function test_unassigned_specialty_is_rejected_without_creating_an_admission(): void
    {
        $case = $this->clinicalCase();
        $case['student']->especialidades()->updateExistingPivot($case['clinic']->id, ['habilitado' => false]);
        $count = Admision::count();

        $this->actingAs($case['student'])->postJson(route('admisiones.store'), $case['admission']->only(['paciente_id', 'periodo_id', 'estudiante_id', 'especialidad_estudiante', 'responsable_id', 'especialidad_responsable']) + ['gpe' => 'Bueno'])->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');

        $this->assertSame($count, Admision::count());
    }
}
