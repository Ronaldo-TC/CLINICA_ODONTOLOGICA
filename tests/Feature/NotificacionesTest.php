<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

/**
 * Notificaciones internas: creación en el flujo de solicitudes, bandeja,
 * contador, marcar como leída y aislamiento entre usuarios.
 */
class NotificacionesTest extends TestCase
{
    use CreatesClinicalCase;
    use DatabaseTransactions;

    private function usuarioConRol(string $rol): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', $rol))->first();
    }

    public function test_la_bandeja_renderiza_y_el_contador_es_correcto(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        Notificacion::create([
            'usuario_id' => $admin->id,
            'tipo' => Notificacion::TIPO_SOLICITUD_CREADA,
            'titulo' => 'Aviso de prueba',
            'mensaje' => 'Detalle del aviso',
            'leida' => false,
        ]);

        $respuesta = $this->actingAs($admin)->get(route('notificaciones.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('Aviso de prueba');
        $respuesta->assertSee('id="tbodyNotificaciones"', false);
        $respuesta->assertSee('btnLeerTodas', false);
    }

    public function test_ultimas_devuelve_solo_las_del_usuario_y_su_contador(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        // Un aviso del admin y otro de OTRO usuario
        Notificacion::create([
            'usuario_id' => $admin->id,
            'tipo' => Notificacion::TIPO_STOCK_BAJO,
            'titulo' => 'Mio sin leer',
            'leida' => false,
        ]);

        $otro = $this->crearUsuarioDePrueba();

        Notificacion::create([
            'usuario_id' => $otro->id,
            'tipo' => Notificacion::TIPO_STOCK_BAJO,
            'titulo' => 'De otro usuario',
            'leida' => false,
        ]);

        $respuesta = $this->actingAs($admin)->getJson(route('notificaciones.ultimas'));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, $respuesta->json('no_leidas'));

        $titulos = array_column($respuesta->json('items'), 'titulo');
        $this->assertContains('Mio sin leer', $titulos);
        $this->assertNotContains('De otro usuario', $titulos);
    }

    public function test_leer_y_leer_todas_actualizan_el_contador(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $a = Notificacion::create([
            'usuario_id' => $admin->id,
            'tipo' => Notificacion::TIPO_HISTORIA_AUTORIZADA,
            'titulo' => 'Primera',
            'url' => url('/historias'),
            'leida' => false,
        ]);

        Notificacion::create([
            'usuario_id' => $admin->id,
            'tipo' => Notificacion::TIPO_HISTORIA_AUTORIZADA,
            'titulo' => 'Segunda',
            'leida' => false,
        ]);

        // Marcar una
        $this->actingAs($admin)
            ->postJson(route('notificaciones.leer', $a->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('url', url('/historias'));

        $this->assertTrue($a->fresh()->leida);
        $this->assertNotNull($a->fresh()->fecha_lectura);

        // Marcar todas
        $this->actingAs($admin)
            ->postJson(route('notificaciones.leer-todas'))
            ->assertOk()
            ->assertJsonPath('no_leidas', 0);

        $this->assertSame(0, Notificacion::query()->delUsuario($admin->id)->noLeidas()->count());
    }

    public function test_no_se_puede_leer_una_notificacion_de_otro_usuario(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $otro = $this->crearUsuarioDePrueba();

        $ajena = Notificacion::create([
            'usuario_id' => $otro->id,
            'tipo' => Notificacion::TIPO_STOCK_BAJO,
            'titulo' => 'Ajena',
            'leida' => false,
        ]);

        $this->actingAs($admin)
            ->postJson(route('notificaciones.leer', $ajena->id))
            ->assertStatus(404);

        $this->assertFalse($ajena->fresh()->leida);
    }

    public function test_crear_una_solicitud_notifica_a_los_docentes(): void
    {
        $case = $this->approvedProgress();
        $this->paidTreatment($case);
        $estudiante = $case['student'];
        $docente = $case['teacher'];

        if (! $estudiante || ! $docente) {
            $this->markTestSkipped('Faltan usuarios estudiante/docente');
        }

        $clinica = $case['clinic'];

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo notificación',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        $insumo = Insumo::create([
            'nombre' => 'Insumo notificación',
            'cantidad_numerica' => 10,
            'cantidad' => 'unidades',
            'stock_minimo' => 2,
            'costo_unitario' => 3,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
        ]);

        $antes = Notificacion::query()->delUsuario($docente->id)->noLeidas()->count();

        $respuesta = $this->actingAs($estudiante)->postJson(route('solicitudes-insumos.store'), [
            'clinica_id' => $clinica->id,
            'insumo_tratamiento_id' => $tipo->id,
            'pieza' => 0,
            'tratamiento_realizado_id' => $case['realized']->id,
            'sesion_atencion_id' => $case['session']->id,
            'detalles' => [
                ['insumo_id' => $insumo->id, 'cantidad_solicitada' => 2],
            ],
        ]);

        $respuesta->assertStatus(201);

        $despues = Notificacion::query()->delUsuario($docente->id)->noLeidas()->count();
        $this->assertGreaterThan($antes, $despues, 'El docente debe recibir un aviso');

        $aviso = Notificacion::query()
            ->delUsuario($docente->id)
            ->where('tipo', Notificacion::TIPO_SOLICITUD_CREADA)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($aviso);
        $this->assertStringContainsString('Solicitud de insumos', $aviso->titulo);
    }

    public function test_autorizar_notifica_al_estudiante(): void
    {
        $case = $this->approvedProgress();
        $this->paidTreatment($case);
        $estudiante = $case['student'];
        $admin = $case['teacher'];

        if (! $estudiante || ! $admin) {
            $this->markTestSkipped('Faltan usuarios');
        }

        $clinica = $case['clinic'];

        $tipo = InsumoTratamiento::create([
            'nombre' => 'Tipo autorizar',
            'descripcion' => '',
            'clinica_id' => $clinica->id,
        ]);

        $solicitud = InsumoSolicitado::create([
            'pieza' => 0,
            'insumo_tratamiento_id' => $tipo->id,
            'clinica_id' => $clinica->id,
            'usuario_id' => $estudiante->id,
            'estado' => InsumoSolicitado::ESTADO_PENDIENTE,
            'tratamiento_realizado_id' => $case['realized']->id,
            'sesion_atencion_id' => $case['session']->id,
        ]);

        $this->actingAs($admin)
            ->postJson(route('solicitudes-insumos.autorizar', $solicitud->id))
            ->assertOk();

        $aviso = Notificacion::query()
            ->delUsuario($estudiante->id)
            ->where('tipo', Notificacion::TIPO_SOLICITUD_AUTORIZADA)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($aviso, 'El estudiante debe recibir el aviso de autorización');
        $this->assertStringContainsString('autorizada', $aviso->titulo);
    }

    private function crearUsuarioDePrueba(): Usuario
    {
        $identificador = substr((string) Str::uuid(), 0, 12);

        return Usuario::create([
            'ci' => $identificador,
            'nombre_usuario' => $identificador,
            'nombre_completo' => 'Otro usuario '.$identificador,
            'contrasena' => 'unused',
            'habilitado' => true,
            'permitir_login' => true,
        ]);
    }
}
