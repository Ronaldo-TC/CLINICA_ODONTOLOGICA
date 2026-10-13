<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class UsuarioEliminacionTest extends TestCase
{
    use UsesAuthDatabase;

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_elimina_un_usuario_sin_historial_activo_o_inactivo_y_revoca_sus_accesos(bool $habilitado): void
    {
        $editor = $this->editor();
        $usuario = $this->otroUsuario(['habilitado' => $habilitado]);
        $usuario->assignRole(Role::create(['name' => 'estudiante', 'guard_name' => 'web']));
        $usuario->givePermissionTo(Permission::create(['name' => 'pacientes.ver', 'guard_name' => 'web']));
        $this->insertSession($usuario, 'sesion-eliminar');
        $token = $usuario->createToken('dispositivo')->accessToken;
        DB::table('notificaciones')->insert(['usuario_id' => $usuario->id]);
        $bitacoraId = DB::table('bitacoras')->insertGetId([
            'usuario_id' => $usuario->id, 'ip' => '127.0.0.1', 'fecha' => now(),
            'tipo' => 'login', 'datos' => 'Acceso previo',
        ]);

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario eliminado definitivamente.');

        $this->assertModelMissing($usuario);
        $this->assertModelMissing($token);
        $this->assertDatabaseMissing('sesiones', ['usuario_id' => $usuario->id]);
        $this->assertDatabaseMissing('notificaciones', ['usuario_id' => $usuario->id]);
        $this->assertDatabaseMissing('model_has_roles', ['model_type' => Usuario::class, 'model_id' => $usuario->id]);
        $this->assertDatabaseMissing('model_has_permissions', ['model_type' => Usuario::class, 'model_id' => $usuario->id]);
        $this->assertDatabaseHas('bitacoras', ['id' => $bitacoraId, 'usuario_id' => null, 'datos' => 'Acceso previo']);
        $this->assertModelExists($editor);
    }

    public function test_eliminar_sin_autenticacion_devuelve_401_y_conserva_el_usuario(): void
    {
        $usuario = $this->otroUsuario();

        $this->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))->assertUnauthorized();

        $this->assertModelExists($usuario);
    }

    public function test_eliminar_sin_permiso_devuelve_403_y_conserva_accesos(): void
    {
        $editor = $this->createUser();
        $usuario = $this->otroUsuario();
        $token = $usuario->createToken('dispositivo')->accessToken;

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))->assertForbidden();

        $this->assertModelExists($usuario);
        $this->assertModelExists($token);
    }

    public function test_eliminar_el_propio_usuario_devuelve_422_y_conserva_su_sesion(): void
    {
        $usuario = $this->editor();
        $this->insertSession($usuario, 'sesion-propia');
        $token = $usuario->createToken('dispositivo')->accessToken;

        $this->actingAs($usuario)->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))
            ->assertUnprocessable()->assertJsonPath('message', 'No puedes eliminar tu propio usuario.');

        $this->assertModelExists($usuario);
        $this->assertModelExists($token);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-propia', 'activa' => true]);
    }

    public function test_eliminar_el_ultimo_admin_activo_devuelve_422(): void
    {
        $editor = $this->editor();
        $admin = $this->otroUsuario();
        $admin->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        $inactivo = $this->createUser(['ci' => 'admin_inactivo', 'nombre_usuario' => 'admin_inactivo', 'permitir_login' => false]);
        $inactivo->assignRole('admin');

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', $admin))
            ->assertUnprocessable()->assertJsonPath('message', 'No puedes eliminar al último administrador activo.');

        $this->assertModelExists($admin);
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
    }

    public function test_elimina_un_admin_si_otro_administrador_activo_conserva_acceso(): void
    {
        $editor = $this->editor();
        $admin = $this->otroUsuario();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole($role);
        $editor->assignRole($role);

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', $admin))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertModelMissing($admin);
        $this->assertDatabaseHas('usuarios', ['id' => $editor->id, 'habilitado' => true, 'permitir_login' => true]);
    }

    #[TestWith(['estudiante_id', 'cascade'])]
    #[TestWith(['docente_id', 'set null'])]
    #[TestWith(['autorizado_por', 'restrict'])]
    public function test_con_historial_devuelve_422_y_conserva_registros_y_accesos(string $column, string $onDelete): void
    {
        $editor = $this->editor();
        $usuario = $this->otroUsuario();
        $this->insertSession($usuario, 'sesion-historial');
        $token = $usuario->createToken('dispositivo')->accessToken;
        Schema::create('registros_clinicos', function (Blueprint $table) use ($column, $onDelete): void {
            $table->id();
            $table->foreignId($column)->nullable()->constrained('usuarios')->onDelete($onDelete);
        });
        $registroId = DB::table('registros_clinicos')->insertGetId([$column => $usuario->id]);

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', $usuario))
            ->assertUnprocessable()->assertJsonPath('message', 'No puedes eliminar este usuario porque tiene registros asociados. Puedes deshabilitarlo para conservar su historial.');

        $this->assertModelExists($usuario);
        $this->assertModelExists($token);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-historial', 'activa' => true]);
        $this->assertDatabaseHas('registros_clinicos', ['id' => $registroId, $column => $usuario->id]);
    }

    public function test_eliminar_un_usuario_inexistente_devuelve_404(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->deleteJson(route('usuarios.eliminar-definitivamente', 999))->assertNotFound();

        $this->assertModelExists($editor);
    }

    public function test_deshabilitar_conserva_el_usuario_y_habilitar_restaura_acceso(): void
    {
        $editor = $this->editor();
        $usuario = $this->otroUsuario();
        $this->insertSession($usuario, 'sesion-deshabilitar');
        $token = $usuario->createToken('dispositivo')->accessToken;

        $this->actingAs($editor)->deleteJson(route('usuarios.destroy', $usuario))
            ->assertOk()->assertJsonPath('message', 'Usuario deshabilitado correctamente.');

        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'habilitado' => false, 'permitir_login' => false]);
        $this->assertDatabaseHas('sesiones', ['token' => 'sesion-deshabilitar', 'activa' => false]);
        $this->assertModelMissing($token);

        $this->actingAs($editor)->postJson(route('usuarios.habilitar', $usuario))
            ->assertOk()->assertJsonPath('message', 'Usuario habilitado correctamente.');

        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'habilitado' => true, 'permitir_login' => true]);
    }

    #[TestWith([true, 'deshabilitar', 'habilitar'])]
    #[TestWith([false, 'habilitar', 'deshabilitar'])]
    public function test_la_fila_muestra_eliminar_y_la_accion_correspondiente_al_estado(bool $habilitado, string $visible, string $hidden): void
    {
        $editor = $this->editor();
        $usuario = $this->otroUsuario(['habilitado' => $habilitado]);
        $this->actingAs($editor);

        $html = view('usuarios.partials.filas', ['items' => collect([$usuario])])->render();

        $this->assertStringContainsString('data-action="eliminar"', $html);
        $this->assertStringContainsString('data-action="'.$visible.'"', $html);
        $this->assertStringNotContainsString('data-action="'.$hidden.'"', $html);
    }

    public function test_la_fila_oculta_eliminar_y_deshabilitar_para_el_propio_usuario(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);

        $html = view('usuarios.partials.filas', ['items' => collect([$editor])])->render();

        $this->assertStringNotContainsString('data-action="eliminar"', $html);
        $this->assertStringNotContainsString('data-action="deshabilitar"', $html);
    }

    private function editor(): Usuario
    {
        $usuario = $this->createUser();
        $usuario->givePermissionTo([
            Permission::create(['name' => 'usuarios.eliminar', 'guard_name' => 'web']),
            Permission::create(['name' => 'usuarios.editar', 'guard_name' => 'web']),
        ]);

        return $usuario;
    }

    /** @param array<string, mixed> $overrides */
    private function otroUsuario(array $overrides = []): Usuario
    {
        return $this->createUser(array_merge([
            'ci' => 'usuario_objetivo', 'nombre_usuario' => 'usuario_objetivo',
        ], $overrides));
    }
}
