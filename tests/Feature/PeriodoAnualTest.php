<?php

namespace Tests\Feature;

use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeriodoAnualTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_formulario_y_la_tabla_no_muestran_semestre(): void
    {
        $respuesta = $this->actingAs($this->administrador())->get(route('periodos.index'));

        $respuesta->assertOk()
            ->assertDontSee('name="semestre"', false)
            ->assertDontSee('periodo-semestre', false)
            ->assertDontSee('<th>Semestre</th>', false);
    }

    public function test_se_registra_un_unico_periodo_anual_sin_semestre(): void
    {
        $gestion = $this->gestionDisponible();
        $datos = [
            'nombre' => "Gestión {$gestion}",
            'gestion' => $gestion,
            'fecha_inicio' => "{$gestion}-01-01",
            'fecha_fin' => "{$gestion}-12-31",
            'habilitado' => true,
        ];

        $respuesta = $this->actingAs($this->administrador())
            ->postJson(route('periodos.store'), $datos)
            ->assertCreated()
            ->assertJsonPath('registro.gestion', $gestion);

        $periodo = Periodo::findOrFail($respuesta->json('id'));
        $this->assertArrayNotHasKey('semestre', $periodo->getAttributes());
        $this->assertDatabaseHas('periodos', $datos);

        $this->postJson(route('periodos.store'), $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gestion')
            ->assertJsonPath('errors.gestion.0', 'Ya existe un periodo con esa gestión.');
    }

    public function test_la_tabla_de_periodos_usa_gestion_como_identificador_anual(): void
    {
        $this->assertFalse(Schema::hasColumn('periodos', 'semestre'));
        $this->assertTrue(Schema::hasIndex('periodos', ['gestion'], 'unique'));
    }

    private function administrador(): Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->firstOrFail();
    }

    private function gestionDisponible(): int
    {
        $gestion = collect(range(2100, 2000))->first(
            fn (int $year): bool => ! Periodo::where('gestion', $year)->exists()
        );

        $this->assertNotNull($gestion, 'No hay una gestión disponible para la prueba.');

        return $gestion;
    }
}
