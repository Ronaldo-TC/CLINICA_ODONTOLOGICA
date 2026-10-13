<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

/**
 * Panel principal: debe renderizar para cualquier rol sin errores.
 */
class DashboardTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

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

    public function test_admin_ve_actividad_propia_ajena_y_de_usuarios_eliminados_en_el_dashboard(): void
    {
        $admin = $this->clinicalUser('admin');
        $otro = $this->clinicalUser('docente');
        $propio = $this->eventoActividad($admin, 'Actividad del administrador');
        $ajeno = $this->eventoActividad($otro, 'Actividad de otro usuario');
        $historico = $this->eventoActividad(null, json_encode([
            'descripcion' => 'Actividad histórica',
            'usuario' => ['nombre' => 'Responsable eliminado'],
        ]));

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('Todos los usuarios')->assertSee('Actividad del administrador')
            ->assertSee('Actividad de otro usuario')->assertSee('Responsable eliminado')
            ->assertViewHas('actividadReciente', fn ($actividad): bool => collect([$propio->id, $ajeno->id, $historico->id])
                ->diff($actividad->pluck('id'))->isEmpty());
    }

    #[TestWith(['subadmin'])]
    #[TestWith(['director'])]
    #[TestWith(['docente'])]
    #[TestWith(['estudiante'])]
    #[TestWith(['interno'])]
    #[TestWith(['admision'])]
    #[TestWith(['cajas'])]
    #[TestWith(['insumos'])]
    #[TestWith(['esterilizacion'])]
    #[TestWith(['radiologia'])]
    #[TestWith(['kardex'])]
    #[TestWith(['investigacion'])]
    #[TestWith(['jefe-catedra'])]
    #[TestWith(['jefatura-clinicas'])]
    public function test_los_demas_roles_solo_ven_su_actividad_aunque_haya_mas_eventos_ajenos_recientes(string $rol): void
    {
        $usuario = $this->clinicalUser($rol);
        $otro = $this->clinicalUser('admin');
        $propio = $this->eventoActividad($usuario, 'Acción personal visible');
        $propio->update(['fecha' => now()->subHour()]);
        foreach (range(1, 9) as $numero) {
            $this->eventoActividad($otro, 'Acción ajena oculta '.$numero);
        }
        $this->eventoActividad(null, 'Acción sin responsable oculta');

        $this->actingAs($usuario)->get(route('dashboard'))->assertOk()
            ->assertSee('Mi bitácora')->assertSee('Acción personal visible')
            ->assertDontSee('Acción ajena oculta')->assertDontSee('Acción sin responsable oculta')
            ->assertViewHas('actividadReciente', fn ($actividad): bool => $actividad->pluck('id')->all() === [$propio->id]);
    }

    public function test_un_usuario_sin_actividad_ve_el_estado_vacio_y_no_eventos_ajenos(): void
    {
        $usuario = $this->clinicalUser('docente');
        $this->eventoActividad($this->clinicalUser('admin'), 'Actividad ajena que no se debe mostrar');

        $this->actingAs($usuario)->get(route('dashboard'))->assertOk()
            ->assertSee('Sin movimientos registrados')->assertDontSee('Actividad ajena que no se debe mostrar')
            ->assertViewHas('actividadReciente', fn ($actividad): bool => $actividad->isEmpty());
    }

    private function eventoActividad(?Usuario $usuario, string $datos): Bitacora
    {
        return Bitacora::create([
            'usuario_id' => $usuario?->id, 'ip' => '127.0.0.1', 'fecha' => now()->addMinute(),
            'tipo' => 'editar', 'estado' => 'exito', 'datos' => $datos,
        ]);
    }
}
