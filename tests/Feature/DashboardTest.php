<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Panel principal: debe renderizar para cualquier rol sin errores.
 */
class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioConRol(string $rol): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', $rol))->first();
    }

    public function test_el_panel_renderiza_para_el_administrador(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $respuesta = $this->actingAs($admin)->get(route('dashboard'));

        $respuesta->assertOk();
        $respuesta->assertSee('Panel de Control');
        $respuesta->assertSee('id="panelMetricas"', false);
        $respuesta->assertSee('graficoPacientesMes', false);
        $respuesta->assertSee('graficoEspecialidades', false);
        $respuesta->assertSee('Actividad reciente');
        $respuesta->assertSee('Próximos vencimientos');
    }

    public function test_el_panel_renderiza_para_docente_y_estudiante(): void
    {
        foreach (['docente', 'estudiante'] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            if (! $usuario) {
                continue;
            }

            $this->actingAs($usuario)
                ->get(route('dashboard'))
                ->assertOk();
        }
    }

    public function test_el_endpoint_de_estadisticas_devuelve_series(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $respuesta = $this->actingAs($admin)->getJson(route('dashboard.stats'));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('success', true);
        $respuesta->assertJsonStructure([
            'por_mes' => ['labels', 'valores'],
            'especialidades' => ['labels', 'valores'],
        ]);

        $this->assertCount(12, $respuesta->json('por_mes.labels'));
        $this->assertCount(12, $respuesta->json('por_mes.valores'));
    }
}
