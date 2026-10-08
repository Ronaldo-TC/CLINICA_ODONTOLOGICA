<?php

namespace Tests\Feature;

use App\Models\Usuario;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class UsuarioProtectionTest extends TestCase
{
    use UsesAuthDatabase;

    #[TestWith(['habilitado'])]
    #[TestWith(['permitir_login'])]
    public function test_put_devuelve_422_al_desactivar_el_propio_usuario_y_conserva_sus_datos(string $flag): void
    {
        $usuario = $this->editor();
        $this->insertSession($usuario, 'sesion-propia');
        $token = $usuario->createToken('dispositivo')->accessToken;
        $payload = $this->datosEdicion($usuario, [$flag => false, 'nombre_completo' => 'Cambio rechazado']);

        $this->actingAs($usuario)->putJson(route('usuarios.update', $usuario), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors([
                $flag => 'No puedes deshabilitar tu propio usuario.',
            ]);

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuario->id, 'habilitado' => true, 'permitir_login' => true,
            'nombre_completo' => $usuario->nombre_completo,
        ]);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-propia', 'activa' => true]);
        $this->assertModelExists($token);
    }

    #[TestWith(['habilitado', 'habilitado'])]
    #[TestWith(['habilitado', 'permitir_login'])]
    #[TestWith(['permitir_login', 'habilitado'])]
    #[TestWith(['permitir_login', 'permitir_login'])]
    public function test_put_devuelve_422_al_desactivar_el_ultimo_admin_accesible(string $flag, string $inactiveFlag): void
    {
        $editor = $this->editor();
        $admin = $this->administrador('admin_activo');
        $this->administrador('admin_inactivo', [$inactiveFlag => false]);
        $this->insertSession($admin, 'sesion-admin');
        $token = $admin->createToken('dispositivo')->accessToken;

        $this->actingAs($editor)->putJson(route('usuarios.update', $admin), $this->datosEdicion($admin, [
            $flag => false, 'nombre_completo' => 'Cambio rechazado',
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            $flag => 'No puedes deshabilitar al último administrador activo.',
        ]);

        $this->assertDatabaseHas('usuarios', [
            'id' => $admin->id, 'habilitado' => true, 'permitir_login' => true,
            'nombre_completo' => $admin->nombre_completo,
        ]);
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-admin', 'activa' => true]);
        $this->assertModelExists($token);
    }

    #[TestWith(['usuarios.update', 'putJson'])]
    #[TestWith(['usuarios.cambiar-rol', 'postJson'])]
    public function test_retira_admin_devuelve_422_para_el_ultimo_admin_y_conserva_roles_y_sesiones(string $routeName, string $method): void
    {
        $editor = $this->editor();
        $admin = $this->administrador('admin_activo');
        $this->administrador('admin_sin_login', ['permitir_login' => false]);
        $docente = Role::create(['name' => 'docente', 'guard_name' => 'web']);
        $this->insertSession($admin, 'sesion-admin');
        $payload = $this->datosEdicion($admin, [
            'roles' => [$docente->id], 'nombre_completo' => 'Cambio rechazado',
        ]);

        $this->actingAs($editor)->{$method}(route($routeName, $admin), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors([
                'roles' => 'No puedes retirar el rol de administrador al último administrador activo.',
            ]);

        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, 'nombre_completo' => $admin->nombre_completo]);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-admin', 'activa' => true]);
    }

    public function test_put_sin_roles_devuelve_422_y_no_retira_el_ultimo_admin(): void
    {
        $editor = $this->editor();
        $admin = $this->administrador('admin_activo');
        $payload = $this->datosEdicion($admin);
        unset($payload['roles']);

        $this->actingAs($editor)->putJson(route('usuarios.update', $admin), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors([
                'roles' => 'No puedes retirar el rol de administrador al último administrador activo.',
            ]);

        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
    }

    #[TestWith(['usuarios.update', 'putJson'])]
    #[TestWith(['usuarios.cambiar-rol', 'postJson'])]
    public function test_retira_admin_si_otro_administrador_accesible_conserva_su_rol(string $routeName, string $method): void
    {
        $editor = $this->editor();
        $admin = $this->administrador('admin_objetivo');
        $otroAdmin = $this->administrador('admin_restante');
        $docente = Role::create(['name' => 'docente', 'guard_name' => 'web']);

        $this->actingAs($editor)->{$method}(route($routeName, $admin), $this->datosEdicion($admin, [
            'roles' => [$docente->id],
        ]))->assertOk()->assertJsonPath('success', true);

        $this->assertSame(['docente'], $admin->fresh()->getRoleNames()->all());
        $this->assertSame(['admin'], $otroAdmin->fresh()->getRoleNames()->all());
    }

    #[TestWith(['habilitado'])]
    #[TestWith(['permitir_login'])]
    public function test_put_desactiva_otro_admin_si_queda_un_admin_accesible(string $flag): void
    {
        $editor = $this->editor();
        $admin = $this->administrador('admin_objetivo');
        $otroAdmin = $this->administrador('admin_restante');
        $this->insertSession($admin, 'sesion-admin');
        $token = $admin->createToken('dispositivo')->accessToken;

        $this->actingAs($editor)->putJson(route('usuarios.update', $admin), $this->datosEdicion($admin, [
            $flag => false,
        ]))->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, $flag => false]);
        $this->assertDatabaseHas('usuarios', [
            'id' => $otroAdmin->id, 'habilitado' => true, 'permitir_login' => true,
        ]);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-admin', 'activa' => false]);
        $this->assertModelMissing($token);
    }

    public function test_el_ultimo_admin_puede_editar_sus_datos_conservando_acceso_y_rol(): void
    {
        $admin = $this->administrador('admin_activo');
        $admin->givePermissionTo(Permission::create(['name' => 'usuarios.editar', 'guard_name' => 'web']));

        $this->actingAs($admin)->putJson(route('usuarios.update', $admin), $this->datosEdicion($admin, [
            'nombre_completo' => 'Administrador actualizado',
        ]))->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('usuarios', [
            'id' => $admin->id, 'nombre_completo' => 'Administrador actualizado',
            'habilitado' => true, 'permitir_login' => true,
        ]);
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
    }

    public function test_delete_no_cuenta_admins_sin_permiso_de_login_y_protege_el_ultimo_admin(): void
    {
        $editor = $this->editor();
        $editor->givePermissionTo(Permission::create(['name' => 'usuarios.eliminar', 'guard_name' => 'web']));
        $admin = $this->administrador('admin_activo');
        $this->administrador('admin_sin_login', ['permitir_login' => false]);

        $this->actingAs($editor)->deleteJson(route('usuarios.destroy', $admin))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes deshabilitar al último administrador activo.');

        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, 'habilitado' => true, 'permitir_login' => true]);
    }

    #[TestWith(['usuarios.update', 'putJson'])]
    #[TestWith(['usuarios.cambiar-rol', 'postJson'])]
    public function test_sin_permiso_devuelve_403_antes_de_validar_proteccion_admin(string $routeName, string $method): void
    {
        $usuario = $this->createUser();
        $admin = $this->administrador('admin_activo');

        $this->actingAs($usuario)->{$method}(route($routeName, $admin), [])
            ->assertForbidden();

        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, 'habilitado' => true, 'permitir_login' => true]);
    }

    private function editor(): Usuario
    {
        $usuario = $this->createUser();
        $usuario->givePermissionTo(Permission::create(['name' => 'usuarios.editar', 'guard_name' => 'web']));

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function administrador(string $nombreUsuario, array $overrides = []): Usuario
    {
        $usuario = $this->createUser(array_merge([
            'ci' => $nombreUsuario, 'nombre_usuario' => $nombreUsuario,
        ], $overrides));
        $usuario->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function datosEdicion(Usuario $usuario, array $overrides = []): array
    {
        return array_merge([
            'ci' => $usuario->ci,
            'nombre_usuario' => $usuario->nombre_usuario,
            'nombre_completo' => $usuario->nombre_completo,
            'habilitado' => true,
            'permitir_login' => true,
            'roles' => $usuario->roles->pluck('id')->all(),
        ], $overrides);
    }
}
