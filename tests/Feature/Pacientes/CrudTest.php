<?php

namespace Tests\Feature\Pacientes;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_a_guest_is_redirected_to_login_when_viewing_patients(): void
    {
        $response = $this->get(route('pacientes.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_patient_list_links_to_a_new_admission_and_existing_history(): void
    {
        $case = $this->clinicalCase();

        $this->actingAs($this->clinicalUser('admin'))->get(route('pacientes.index'))
            ->assertOk()
            ->assertSee('patients-welcome', false)
            ->assertSee('patients-directory', false)
            ->assertSee('patient-card', false)
            ->assertSee('filtro-buscar', false)
            ->assertSee(route('admisiones.create', ['paciente_id' => $case['admission']->paciente_id]), false)
            ->assertSee('Nueva admisión')
            ->assertSee(route('historias.show', $case['history']->id), false)
            ->assertSee('Ver historia');
    }
}
