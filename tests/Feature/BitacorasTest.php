<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class BitacorasTest extends TestCase
{
    use UsesAuthDatabase;

    public function test_crear_usuario_registra_responsable_y_registro_sin_guardar_secretos(): void
    {
        $editor = $this->usuarioConPermisos(['usuarios.crear']);
        $this->freezeTime();

        $response = $this->actingAs($editor)->postJson(route('usuarios.store'), [
            'ci' => 'nuevo', 'nombre_usuario' => 'nuevo', 'nombre_completo' => 'Usuario nuevo',
            'contrasena' => 'secreto-no-auditar', 'usuario_id' => 999,
            'observacion' => 'Dato clínico que no debe copiarse',
        ])->assertCreated();

        $evento = Bitacora::sole();
        $this->assertSame($editor->id, $evento->usuario_id);
        $this->assertSame('crear', $evento->tipo);
        $this->assertSame('exito', $evento->estado);
        $this->assertSame($editor->nombre_completo, $evento->usuario_nombre);
        $this->assertSame($response->json('id'), $evento->detalles['registros']['registro']);
        $this->assertSame(now()->toDateTimeString(), $evento->fecha->toDateTimeString());
        $this->assertStringNotContainsString('secreto-no-auditar', $evento->datos);
        $this->assertStringNotContainsString('Dato clínico que no debe copiarse', $evento->datos);
        $this->assertDatabaseHas('usuarios', ['id' => $response->json('id'), 'nombre_usuario' => 'nuevo']);
    }

    public function test_deshabilitar_habilitar_y_eliminar_son_tres_acciones_distintas(): void
    {
        $editor = $this->usuarioConPermisos(['usuarios.eliminar', 'usuarios.editar']);
        $usuario = $this->otroUsuario();
        $this->actingAs($editor);

        $this->deleteJson(route('usuarios.destroy', $usuario))->assertOk();
        $this->assertSame('deshabilitar', Bitacora::latest('id')->first()->tipo);
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'habilitado' => false]);
        $this->postJson(route('usuarios.habilitar', $usuario))->assertOk();
        $this->assertSame('habilitar', Bitacora::latest('id')->first()->tipo);
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'habilitado' => true]);
        $this->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))->assertOk();
        $this->assertSame('eliminar', Bitacora::latest('id')->first()->tipo);
        $this->assertModelMissing($usuario);
        $this->assertDatabaseCount('bitacoras', 3);
    }

    public function test_error_de_validacion_devuelve_422_y_se_registra_como_fallido(): void
    {
        $editor = $this->usuarioConPermisos(['usuarios.crear']);

        $this->actingAs($editor)->postJson(route('usuarios.store'), [])->assertUnprocessable();

        $evento = Bitacora::sole();
        $this->assertSame('error', $evento->estado);
        $this->assertSame(422, $evento->detalles['codigo_http']);
        $this->assertSame($editor->id, $evento->usuario_id);
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_accion_sin_permiso_devuelve_403_y_registra_al_usuario_del_intento(): void
    {
        $usuario = $this->createUser();
        $objetivo = $this->otroUsuario();

        $this->actingAs($usuario)->deleteJson(route('usuarios.destroy', $objetivo))->assertForbidden();

        $this->assertDatabaseHas('bitacoras', ['usuario_id' => $usuario->id, 'estado' => 'error', 'tipo' => 'deshabilitar']);
        $this->assertDatabaseHas('usuarios', ['id' => $objetivo->id, 'habilitado' => true]);
    }

    public function test_login_y_logout_web_conservan_el_responsable_aunque_se_cierre_la_sesion(): void
    {
        $usuario = $this->createUser();

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $usuario->nombre_usuario, 'contrasena' => 'clave-anterior',
        ])->assertOk();
        $this->postJson(route('auth.logout'))->assertOk();

        $this->assertSame(['login', 'logout'], Bitacora::orderBy('id')->pluck('tipo')->all());
        $this->assertSame([$usuario->id, $usuario->id], Bitacora::orderBy('id')->pluck('usuario_id')->all());
        $this->assertStringNotContainsString('clave-anterior', Bitacora::first()->datos);
    }

    public function test_login_api_registra_el_responsable_sin_copiar_el_token(): void
    {
        $usuario = $this->createUser();

        $response = $this->postJson(route('api.v1.login'), [
            'nombre_usuario' => $usuario->nombre_usuario, 'contrasena' => 'clave-anterior',
        ])->assertOk();

        $evento = Bitacora::sole();
        $this->assertSame($usuario->id, $evento->usuario_id);
        $this->assertSame('login', $evento->tipo);
        $this->assertSame('api.v1.login', $evento->detalles['ruta']);
        $this->assertStringNotContainsString($response->json('data.access_token'), $evento->datos);
    }

    public function test_mutacion_api_identifica_al_usuario_autenticado_con_sanctum(): void
    {
        $usuario = $this->createUser();
        Sanctum::actingAs($usuario);

        $this->postJson(route('api.v1.logout-all'))->assertOk();

        $this->assertDatabaseHas('bitacoras', ['usuario_id' => $usuario->id, 'tipo' => 'logout', 'estado' => 'exito']);
    }

    public function test_conserva_direcciones_ipv6_completas(): void
    {
        $editor = $this->usuarioConPermisos(['usuarios.editar']);
        $usuario = $this->otroUsuario(['habilitado' => false]);
        $ip = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $this->actingAs($editor)->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('usuarios.habilitar', $usuario))->assertOk();

        $this->assertSame($ip, Bitacora::sole()->ip);
    }

    public function test_un_fallo_de_auditoria_se_reporta_sin_interrumpir_la_operacion(): void
    {
        $editor = $this->usuarioConPermisos(['usuarios.editar']);
        $usuario = $this->otroUsuario(['habilitado' => false]);
        Exceptions::fake();
        Bitacora::creating(function (): void {
            throw new RuntimeException('Registro de auditoría no disponible');
        });

        $this->actingAs($editor)->postJson(route('usuarios.habilitar', $usuario))->assertOk();

        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'habilitado' => true]);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_index_y_tabla_aplican_los_mismos_filtros_y_no_generan_eventos_al_consultar(): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);
        $evento = $this->evento($editor, ['datos' => 'Edición de prueba', 'fecha' => '2026-10-10 10:00:00']);
        $this->evento($editor, ['datos' => 'Otro evento', 'tipo' => 'eliminar']);
        $filters = ['buscar' => 'Edición de prueba', 'tipo' => 'editar', 'estado' => 'exito', 'desde' => '2026-10-10', 'hasta' => '2026-10-10'];
        $this->actingAs($editor);

        $this->get(route('bitacoras.index', $filters))->assertOk()->assertSee('Edición de prueba')->assertDontSee('Otro evento');
        $this->getJson(route('bitacoras.tabla', $filters))->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('filas', fn (string $html): bool => str_contains($html, 'data-id="'.$evento->id.'"'));

        $this->assertDatabaseCount('bitacoras', 2);
    }

    public function test_listado_inicial_tiene_paginacion_y_orden_estable_por_fecha_e_id(): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);
        foreach (range(1, 26) as $numero) {
            $this->evento($editor, ['datos' => 'Evento '.$numero, 'fecha' => '2026-10-10 10:00:00']);
        }

        $this->actingAs($editor)->get(route('bitacoras.index'))->assertOk()
            ->assertSee('page=2', false)->assertViewHas('registros', fn ($registros): bool => $registros->first()->id === 26 && $registros->count() === 25);
    }

    #[TestWith(['desde', 'fecha-invalida'])]
    #[TestWith(['hasta', '2026-02-30'])]
    public function test_fecha_invalida_devuelve_422_en_lugar_de_fallar_la_consulta(string $campo, string $valor): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);

        $this->actingAs($editor)->getJson(route('bitacoras.tabla', [$campo => $valor]))
            ->assertUnprocessable()->assertJsonValidationErrors([$campo]);
    }

    public function test_un_rango_invertido_devuelve_422_con_un_mensaje_claro(): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);

        $this->actingAs($editor)->getJson(route('bitacoras.tabla', ['desde' => '2026-10-11', 'hasta' => '2026-10-10']))
            ->assertUnprocessable()->assertJsonValidationErrors(['hasta' => 'La fecha Hasta debe ser igual o posterior a Desde.']);
    }

    public function test_detalle_conserva_el_nombre_del_responsable_si_su_usuario_fue_eliminado(): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);
        $editor->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        $autor = $this->otroUsuario();
        $evento = $this->evento($autor, ['datos' => json_encode([
            'descripcion' => 'Edición de inventario',
            'usuario' => ['nombre' => 'Autor histórico', 'cuenta' => 'autor_historico'],
            'resultado' => 'La operación se completó correctamente.',
            'registros' => ['insumo' => 15],
        ])]);
        $autor->delete();

        $this->actingAs($editor)->get(route('bitacoras.show', $evento))->assertOk()
            ->assertSee('Autor histórico')->assertSee('autor_historico')->assertSee('Edición de inventario')->assertSee('#15');

        $this->assertDatabaseHas('bitacoras', ['id' => $evento->id, 'usuario_id' => null]);
    }

    public function test_detalle_escapa_datos_antiguos_y_reconoce_el_resultado_ok(): void
    {
        $editor = $this->usuarioConPermisos(['bitacoras.ver']);
        $evento = $this->evento($editor, ['datos' => '<script>alert("prueba")</script>', 'estado' => 'ok']);

        $this->actingAs($editor)->get(route('bitacoras.show', $evento))->assertOk()
            ->assertSee('Exitosa')->assertSee('<script>alert("prueba")</script>')->assertDontSee('<script>alert("prueba")</script>', false);
    }

    #[TestWith(['bitacoras.index'])]
    #[TestWith(['bitacoras.tabla'])]
    #[TestWith(['bitacoras.show'])]
    public function test_consultar_sin_permiso_devuelve_403(string $routeName): void
    {
        $usuario = $this->createUser();
        $evento = $this->evento($usuario);

        $this->actingAs($usuario)->getJson(route($routeName, $routeName === 'bitacoras.show' ? $evento : []))->assertForbidden();
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
    public function test_roles_no_administradores_solo_ven_su_bitacora_en_listado_y_detalle(string $rol): void
    {
        $usuario = $this->usuarioConPermisos(['bitacoras.ver']);
        $usuario->assignRole(Role::create(['name' => $rol, 'guard_name' => 'web']));
        $propio = $this->evento($usuario, ['datos' => 'Evento propio']);
        $ajeno = $this->evento($this->otroUsuario(), ['datos' => 'Evento de otro usuario', 'tipo' => 'autorizar']);
        $this->actingAs($usuario);

        $lista = $this->get(route('bitacoras.index'))->assertOk()->assertSee('Evento propio');
        $tabla = $this->getJson(route('bitacoras.tabla', ['usuario_id' => $ajeno->usuario_id]))->assertOk();
        $this->getJson(route('bitacoras.show', $propio))->assertOk()->assertJsonPath('registro.id', $propio->id);

        $lista->assertDontSee('Evento de otro usuario')
            ->assertViewHas('tipos', fn ($tipos): bool => $tipos->all() === ['editar']);
        $tabla->assertJsonPath('total', 1);
        $this->getJson(route('bitacoras.show', $ajeno))->assertNotFound();
        $this->getJson(route('bitacoras.tabla', ['buscar' => 'Evento de otro usuario']))->assertOk()->assertJsonPath('total', 0);
    }

    public function test_admin_ve_bitacoras_de_todos_en_listado_y_detalle(): void
    {
        $admin = $this->usuarioConPermisos(['bitacoras.ver']);
        $admin->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        $this->evento($admin, ['datos' => 'Evento propio']);
        $ajeno = $this->evento($this->otroUsuario(), ['datos' => 'Evento de otro usuario']);
        $this->actingAs($admin);

        $this->get(route('bitacoras.index'))->assertOk()->assertSee('Evento propio')->assertSee('Evento de otro usuario');
        $this->getJson(route('bitacoras.tabla'))->assertOk()->assertJsonPath('total', 2);
        $this->getJson(route('bitacoras.show', $ajeno))->assertOk()->assertJsonPath('registro.id', $ajeno->id);
    }

    /** @param array<int, string> $permisos */
    private function usuarioConPermisos(array $permisos): Usuario
    {
        $usuario = $this->createUser();
        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::create(['name' => $permiso, 'guard_name' => 'web']));
        }

        return $usuario;
    }

    /** @param array<string, mixed> $overrides */
    private function otroUsuario(array $overrides = []): Usuario
    {
        return $this->createUser(array_merge(['ci' => 'otro', 'nombre_usuario' => 'otro'], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function evento(Usuario $usuario, array $overrides = []): Bitacora
    {
        return Bitacora::create(array_merge([
            'usuario_id' => $usuario->id, 'ip' => '127.0.0.1', 'fecha' => now(),
            'tipo' => 'editar', 'estado' => 'exito', 'datos' => 'Evento de prueba',
        ], $overrides));
    }
}
