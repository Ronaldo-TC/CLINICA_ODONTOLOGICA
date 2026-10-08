<?php

namespace Tests\Feature;

use App\Models\Clinica;
use Database\Seeders\ClinicasSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class EspecialidadesTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_specialty_catalog_includes_requested_modules_and_preserves_assignments_when_reseeded(): void
    {
        $case = $this->clinicalCase();
        Clinica::whereIn('especialidad', ['Historia Clínica', 'Quirófano'])->delete();

        $this->seed(ClinicasSeeder::class);
        $count = Clinica::count();
        $this->seed(ClinicasSeeder::class);

        $this->assertSame($count, Clinica::count());
        $this->assertDatabaseHas('clinicas', ['especialidad' => 'Historia Clínica']);
        $this->assertDatabaseHas('clinicas', ['especialidad' => 'Quirófano']);
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $case['student']->id, 'clinica_id' => $case['clinic']->id]);
        $this->assertModelExists($case['admission']);
    }

    public function test_admin_assigns_multiple_specialties_without_erasing_other_assignments(): void
    {
        $admin = $this->clinicalUser('admin');
        $student = $this->clinicalUser('estudiante');
        $first = Clinica::query()->firstOrFail();
        $second = Clinica::query()->where('id', '!=', $first->id)->firstOrFail();

        $this->actingAs($admin)->post(route('especialidades.store'), ['usuario_id' => $student->id, 'clinica_ids' => [$first->id, $second->id], 'rotacion' => 2])->assertRedirect();

        $this->assertSame(2, $student->especialidades()->count());
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $student->id, 'clinica_id' => $first->id, 'rotacion' => 2]);
    }

    public function test_disabling_one_specialty_preserves_the_other(): void
    {
        $case = $this->clinicalCase();
        $other = Clinica::query()->where('id', '!=', $case['clinic']->id)->firstOrFail();
        $case['student']->especialidades()->attach($other->id, ['habilitado' => true]);

        $this->actingAs($this->clinicalUser('admin'))->put(route('especialidades.update', [$case['student']->id, $case['clinic']->id]), ['habilitado' => false])->assertRedirect();

        $this->assertDatabaseHas('especialidades', ['usuario_id' => $case['student']->id, 'clinica_id' => $case['clinic']->id, 'habilitado' => false]);
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $case['student']->id, 'clinica_id' => $other->id, 'habilitado' => true]);
        $this->assertModelExists($case['admission']);
    }

    public function test_student_cannot_assign_their_own_specialties(): void
    {
        $student = $this->clinicalUser('estudiante');

        $this->actingAs($student)->post(route('especialidades.store'), [])->assertForbidden();
        $this->assertSame(0, $student->especialidades()->count());
    }

    public function test_assignment_screen_escapes_user_names(): void
    {
        $case = $this->clinicalCase();
        $case['student']->update(['nombre_completo' => '<script>alert(1)</script>']);

        $this->actingAs($this->clinicalUser('admin'))->get(route('especialidades.index'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_los_filtros_ajax_de_especialidades_devuelven_sus_filas_y_total(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->getJson(route('especialidades.tabla', [
            'buscar' => $caso['student']->ci, 'clinica_id' => $caso['clinic']->id, 'estado' => '1',
        ]));

        $respuesta->assertOk()->assertJsonPath('total', 1)->assertJsonStructure(['html', 'paginacion', 'total']);
        $this->assertStringContainsString($caso['student']->nombre_completo, $respuesta->json('html'));
    }

    public function test_el_modal_ajax_asigna_y_deshabilita_sin_eliminar_la_asignacion(): void
    {
        $estudiante = $this->clinicalUser('estudiante');
        $clinica = Clinica::query()->firstOrFail();
        $this->actingAs($this->clinicalUser('admin'))->postJson(route('especialidades.store'), [
            'usuario_id' => $estudiante->id, 'clinica_ids' => [$clinica->id], 'rotacion' => 1,
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $estudiante->id, 'clinica_id' => $clinica->id, 'habilitado' => true]);

        $this->putJson(route('especialidades.update', [$estudiante->id, $clinica->id]), ['habilitado' => false])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $estudiante->id, 'clinica_id' => $clinica->id, 'habilitado' => false]);
    }

    public function test_la_actualizacion_en_lote_requiere_permiso_y_conserva_las_fichas(): void
    {
        $caso = $this->clinicalCase();
        $datos = ['usuario_id' => $caso['student']->id, 'clinica_ids' => [$caso['clinic']->id], 'habilitado' => false];
        $this->actingAs($caso['student'])->postJson(route('especialidades.bulk-update'), $datos)->assertForbidden();

        $this->actingAs($this->clinicalUser('admin'))->postJson(route('especialidades.bulk-update'), $datos)
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('total', 1);
        $this->assertDatabaseHas('especialidades', ['usuario_id' => $caso['student']->id, 'clinica_id' => $caso['clinic']->id, 'habilitado' => false]);
        $this->assertModelExists($caso['admission']);
    }
}
