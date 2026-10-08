<?php

namespace Tests\Feature;

use App\Models\Admision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class Admision3dTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_admissions_offer_a_3d_form_in_another_window(): void
    {
        $this->actingAs($this->clinicalUser('admision'))->get(route('admisiones.index'))
            ->assertSee('href="'.route('admisiones.create-3d').'" class="btn btn-primary" target="_blank" rel="noopener"', false)
            ->assertSee('Nueva admisión 3D');
    }

    public function test_the_3d_form_uses_the_same_patient_signature_referral_and_save_flow(): void
    {
        $this->actingAs($this->clinicalUser('admision'))->get(route('admisiones.create-3d'))
            ->assertViewIs('admisiones.admision')
            ->assertViewHas('odontograma3d', true)
            ->assertSee('Nueva admisión 3D')
            ->assertSee('data-model-url="'.asset('models/odontograma.glb').'"', false)
            ->assertSee('action="'.route('admisiones.store').'"', false)
            ->assertSee('id="adm-3d-canvas"', false)
            ->assertSee('name="odontograma_pintado"', false)
            ->assertSee('name="odontograma_denticion"', false)
            ->assertSee('id="adm-paciente-buscar"', false)
            ->assertSee('id="adm-firma"', false)
            ->assertSee('name="clinica_ids[]"', false)
            ->assertSee('inicializarFormularioAdmision')
            ->assertSee('Marcar ausente / extraído');
    }

    public function test_the_original_create_form_keeps_its_2d_odontogram(): void
    {
        $this->actingAs($this->clinicalUser('admision'))->get(route('admisiones.create'))
            ->assertViewHas('odontograma3d', false)
            ->assertSee('id="adm-odontograma"', false)
            ->assertDontSee('id="adm-3d-canvas"', false);
    }

    public function test_the_3d_form_requires_authentication(): void
    {
        $this->get(route('admisiones.create-3d'))->assertRedirectToRoute('auth.login');
    }

    public function test_a_student_cannot_create_a_3d_admission(): void
    {
        $this->actingAs($this->clinicalUser('estudiante'))->get(route('admisiones.create-3d'))->assertForbidden();
    }

    public function test_the_3d_form_preserves_old_dental_input(): void
    {
        $pintado = json_encode(['55' => ['o' => 'caries'], '16' => ['marcas' => [['estado' => 'ausente', 'color' => 'azul']]]]);
        $this->actingAs($this->clinicalUser('admision'))
            ->withSession(['_old_input' => ['odontograma_pintado' => $pintado, 'odontograma_denticion' => 'mixto']])
            ->get(route('admisiones.create-3d'))
            ->assertSee('data-valor="'.e($pintado).'"', false)
            ->assertSee('name="odontograma_denticion" value="mixto"', false);
    }

    public function test_3d_surface_paint_and_multiple_marks_are_saved_with_the_admission(): void
    {
        $caso = $this->clinicalCase();
        $pintado = [
            '16' => ['o' => 'caries', 'm' => 'obturado', 'marcas' => [['estado' => 'corona_temporal', 'color' => 'azul'], ['estado' => 'rotacion', 'color' => 'rojo']]],
            '24' => ['marcas' => [['estado' => 'ausente', 'color' => 'rojo']]],
            '55' => ['v' => 'sellante'],
        ];

        $respuesta = $this->actingAs($this->clinicalUser('admision'))
            ->from(route('admisiones.create-3d'))->postJson(route('admisiones.store'), [
                'paciente_id' => $caso['admission']->paciente_id,
                'gpe' => 'Bueno', 'diagnostico' => 'Evaluación con odontograma 3D',
                'odontograma_pintado' => json_encode($pintado), 'odontograma_denticion' => 'mixto',
                'clinica_ids' => [$caso['clinic']->id],
            ])->assertCreated();

        $admision = Admision::findOrFail($respuesta->json('id'));
        $this->assertSame($pintado, json_decode($admision->odontograma_pintado, true));
        $this->assertSame('mixto', $admision->odontograma_denticion);
        $this->assertSame([$caso['clinic']->id], $admision->remisiones()->pluck('clinica_id')->all());
        $this->assertSame('Evaluación con odontograma 3D', $admision->diagnostico);
    }
}
