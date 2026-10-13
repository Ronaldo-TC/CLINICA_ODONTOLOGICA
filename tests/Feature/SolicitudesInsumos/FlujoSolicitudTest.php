<?php

namespace Tests\Feature\SolicitudesInsumos;

use App\Models\Clinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\InsumoTratamiento;
use App\Models\Usuario;
use App\Support\MenuBuilder;
use Database\Seeders\SolicitudesInsumosPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

/**
 * Flujo completo de solicitudes de insumos:
 * crear → autorizar → entregar (con descuento de stock).
 */
class FlujoSolicitudTest extends TestCase
{
    use CreatesClinicalCase;
    use DatabaseTransactions;

    private function usuarioConRol(string $rol): Usuario
    {
        $usuario = Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', $rol))->first();

        if (! $usuario) {
            $this->markTestSkipped("No existe un usuario con el rol '{$rol}' en la base de datos.");
        }

        return $usuario;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
    }

    public function test_flujo_completo_de_solicitud_de_insumos(): void
    {
        $case = $this->approvedProgress();
        $case['teacher']->givePermissionTo('solicitudes-insumos.autorizar');
        $estudiante = $case['student'];
        $admin = $this->usuarioConRol('admin');

        // ---------- Catálogo base ----------
        $clinica = $case['clinic'];

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo de prueba',
            'descripcion' => 'Generado por test',
            'clinica_id' => $clinica->id,
        ]);

        $insumo = Insumo::create([
            'nombre' => 'Insumo de prueba',
            'cantidad_numerica' => 10,
            'cantidad' => 'unidades',
            'stock_minimo' => 2,
            'costo_unitario' => 5,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
        ]);

        // ---------- 1. Crear solicitud (pendiente) ----------
        $respuesta = $this->actingAs($estudiante)
            ->postJson(route('solicitudes-insumos.store'), [
                'clinica_id' => $clinica->id,
                'insumo_tratamiento_id' => $tipo->id,
                'pieza' => 16,
                'tratamiento_realizado_id' => $case['realized']->id,
                'sesion_atencion_id' => $case['session']->id,
                'detalles' => [
                    ['insumo_id' => $insumo->id, 'cantidad_solicitada' => 4],
                ],
            ]);

        $respuesta->assertStatus(201);
        $respuesta->assertJsonPath('success', true);

        $solicitudId = $respuesta->json('id');
        $solicitud = InsumoSolicitado::findOrFail($solicitudId);

        $this->assertSame('pendiente', $solicitud->estado);
        $this->assertSame($estudiante->id, $solicitud->usuario_id);
        $this->assertNull($solicitud->remision_id, 'La solicitud puede no tener remisión');
        $this->assertSame(1, $solicitud->detalles()->count());
        $this->assertEqualsWithDelta(4, (float) $solicitud->detalles()->first()->cantidad_solicitada, 0.001);

        // ---------- 2. Autorizar ----------
        $this->actingAs($case['teacher'])
            ->postJson(route('solicitudes-insumos.autorizar', $solicitudId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $solicitud->refresh();
        $this->assertSame('autorizado', $solicitud->estado);
        $this->assertTrue((bool) $solicitud->autorizado);
        $this->assertSame($case['teacher']->id, $solicitud->docente_id);

        // ---------- 3. Intentar entregar más que lo solicitado ----------
        $detalle = $solicitud->detalles()->first();

        $this->actingAs($admin)
            ->postJson(route('solicitudes-insumos.entregar', $solicitudId), [
                'detalles' => [
                    ['detalle_id' => $detalle->id, 'cantidad_entregada' => 99],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(10.0, (float) $insumo->fresh()->cantidad_numerica, 'El stock no debe cambiar si la entrega falla');

        // ---------- 4. Entregar correctamente ----------
        $this->actingAs($admin)
            ->postJson(route('solicitudes-insumos.entregar', $solicitudId), [
                'detalles' => [
                    ['detalle_id' => $detalle->id, 'cantidad_entregada' => 4],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $solicitud->refresh();
        $this->assertSame('entregado', $solicitud->estado);
        $this->assertEqualsWithDelta(4, (float) $solicitud->detalles()->first()->cantidad_entregada, 0.001);
        $this->assertEqualsWithDelta(6, (float) $insumo->fresh()->cantidad_numerica, 0.001, 'El stock debe descontarse');

        // ---------- 5. La tabla responde con el formato AJAX esperado ----------
        $tabla = $this->actingAs($admin)->getJson(route('solicitudes-insumos.tabla', ['estado' => 'entregado']));
        $tabla->assertOk();
        $tabla->assertJsonStructure(['filas', 'paginacion', 'total']);
        $this->assertGreaterThanOrEqual(1, $tabla->json('total'));

        // ---------- 6. Los datos del modal incluyen los detalles ----------
        $datos = $this->actingAs($admin)->getJson(route('solicitudes-insumos.datos', $solicitudId));
        $datos->assertOk();
        $datos->assertJsonPath('success', true);
        $this->assertNotEmpty($datos->json('detalles'));
        $this->assertSame(
            $insumo->id,
            $datos->json('detalles.0.insumo_id')
        );
    }

    public function test_no_se_puede_entregar_una_solicitud_pendiente(): void
    {
        $estudiante = $this->usuarioConRol('estudiante');
        $admin = $this->usuarioConRol('admin');

        $clinica = Clinica::query()->orderBy('id')->firstOrFail();

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo pendiente',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        $insumo = Insumo::create([
            'nombre' => 'Insumo pendiente',
            'cantidad_numerica' => 5,
            'cantidad' => 'unidades',
            'stock_minimo' => 1,
            'costo_unitario' => 1,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
        ]);

        $solicitud = InsumoSolicitado::create([
            'pieza' => 0,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
            'usuario_id' => $estudiante->id,
            'estado' => InsumoSolicitado::ESTADO_PENDIENTE,
        ]);

        $detalle = InsumoSolicitadoDetalle::create([
            'insumo_solicitado_id' => $solicitud->id,
            'insumo_id' => $insumo->id,
            'cantidad_solicitada' => 2,
            'cantidad_entregada' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson(route('solicitudes-insumos.entregar', $solicitud->id), [
                'detalles' => [
                    ['detalle_id' => $detalle->id, 'cantidad_entregada' => 2],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(
            (float) $insumo->cantidad_numerica,
            (float) $insumo->fresh()->cantidad_numerica
        );
    }

    public function test_no_se_puede_editar_una_solicitud_autorizada(): void
    {
        $estudiante = $this->usuarioConRol('estudiante');
        $admin = $this->usuarioConRol('admin');

        $clinica = Clinica::query()->orderBy('id')->firstOrFail();

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo bloqueado',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        $insumo = Insumo::create([
            'nombre' => 'Insumo bloqueado',
            'cantidad_numerica' => 5,
            'cantidad' => 'unidades',
            'stock_minimo' => 1,
            'costo_unitario' => 1,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
        ]);

        $solicitud = InsumoSolicitado::create([
            'pieza' => 0,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
            'usuario_id' => $estudiante->id,
            'estado' => InsumoSolicitado::ESTADO_AUTORIZADO,
        ]);

        $respuesta = $this->actingAs($admin)->putJson(route('solicitudes-insumos.update', $solicitud->id), [
            'clinica_id' => $clinica->id,
            'insumo_tratamiento_id' => $tipo->id,
            'pieza' => 0,
            'detalles' => [
                ['insumo_id' => $insumo->id, 'cantidad_solicitada' => 1],
            ],
        ]);

        $this->assertContains($respuesta->status(), [403, 422]);
        $this->assertSame('autorizado', $solicitud->fresh()->estado);
    }

    public function test_el_index_de_solicitudes_renderiza_los_modales(): void
    {
        $admin = $this->usuarioConRol('admin');

        $respuesta = $this->actingAs($admin)->get(route('solicitudes-insumos.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('id="modalSolicitud"', false);
        $respuesta->assertSee('id="modalEntregar"', false);
        $respuesta->assertSee('window.SolicitudModal', false);
        $respuesta->assertSee('id="tbodySolicitudes"', false);
        $respuesta->assertSee('id="filtro-buscar"', false);
        // La API del modal realmente debe existir para que el botón funcione
        $respuesta->assertSee('abrirCrear()', false);
        $respuesta->assertSee('abrirEdicion(data)', false);
    }

    public function test_el_menu_lateral_incluye_solicitudes_de_insumos(): void
    {
        $admin = $this->usuarioConRol('admin');

        $menu = MenuBuilder::forUser($admin);
        $etiquetas = [];

        foreach ($menu as $item) {
            if (($item['type'] ?? 'link') === 'label') {
                continue;
            }
            $etiquetas[] = $item['label'];
        }

        $this->assertContains('Solicitudes de insumos', $etiquetas);
        $this->assertContains('Insumos', $etiquetas);
    }

    public function test_el_autocomplete_de_insumos_filtra_por_clinica(): void
    {
        $admin = $this->usuarioConRol('admin');

        $clinica = Clinica::query()->orderBy('id')->firstOrFail();

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo autocomplete',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        Insumo::create([
            'nombre' => 'Guantes de nitrilo',
            'cantidad_numerica' => 100,
            'cantidad' => 'cajas',
            'stock_minimo' => 5,
            'costo_unitario' => 30,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
        ]);

        $respuesta = $this->actingAs($admin)
            ->getJson(route('insumos.buscar-insumos', ['clinica' => $clinica->id, 'q' => 'Guantes']));

        $respuesta->assertOk();
        $this->assertNotEmpty($respuesta->json());
        $this->assertSame('Guantes de nitrilo', $respuesta->json('0.texto'));
        $this->assertArrayHasKey('stock', $respuesta->json('0'));
    }

    public function test_los_tipos_de_insumo_se_filtran_por_clinica(): void
    {
        $admin = $this->usuarioConRol('admin');

        $clinica = Clinica::query()->orderBy('id')->firstOrFail();

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo filtrado',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        $respuesta = $this->actingAs($admin)
            ->getJson(route('insumos.tipos-por-clinica', ['clinica' => $clinica->id]));

        $respuesta->assertOk();
        $this->assertContains($tipo->id, array_column($respuesta->json(), 'id'));
    }
}
