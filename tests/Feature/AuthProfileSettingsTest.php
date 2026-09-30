<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthProfileSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('ci', 50)->unique();
            $table->string('nombre_usuario', 20)->unique();
            $table->string('nombre_completo', 200);
            $table->string('descripcion', 300)->nullable();
            $table->string('tipo')->nullable();
            $table->boolean('habilitado')->default(true);
            $table->boolean('permitir_login')->default(true);
            $table->boolean('multilogin')->default(false);
            $table->string('contrasena', 200);
            $table->string('ip', 300)->nullable();
            $table->string('ip_permitida', 300)->nullable();
            $table->string('info', 300)->nullable();
            $table->string('foto')->nullable();
            $table->string('sesion')->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->timestamp('ultimo_logout')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->boolean('habilitado');
        });

        Schema::create('usuario_rol', function (Blueprint $table): void {
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('rol_id');
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamps();
        });

        Schema::create('permisos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 200)->nullable();
            $table->string('modulo', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('rol_permiso', function (Blueprint $table): void {
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('permiso_id');
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('sesiones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('token')->unique();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('inicio')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('bitacoras', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('fecha')->nullable();
            $table->string('tipo');
            $table->text('datos')->nullable();
            $table->string('estado')->nullable();
            $table->timestamps();
        });
    }

    public function test_any_authenticated_role_can_update_its_own_profile(): void
    {
        $userId = $this->createUser();
        $session = $this->createActiveSession($userId, 'perfil-session');

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('Datos personales');

        $this->withSession($session)
            ->put(route('auth.profile.update'), [
                'ci' => '87654321',
                'nombre_usuario' => 'usuario_nuevo',
                'nombre_completo' => 'Nombre actualizado',
                'descripcion' => 'Perfil actualizado',
            ])
            ->assertRedirect(route('auth.profile'))
            ->assertSessionHas('user_name', 'Nombre actualizado')
            ->assertSessionHas('user_username', 'usuario_nuevo');

        $this->assertDatabaseHas('usuarios', [
            'id' => $userId,
            'ci' => '87654321',
            'nombre_usuario' => 'usuario_nuevo',
            'nombre_completo' => 'Nombre actualizado',
            'descripcion' => 'Perfil actualizado',
        ]);
    }

    public function test_profile_and_settings_require_a_valid_session_user(): void
    {
        $this->get(route('auth.profile'))->assertRedirect(route('auth.login'));
        $this->get(route('auth.settings'))->assertRedirect(route('auth.login'));
    }

    public function test_sidebar_shows_only_modules_enabled_for_the_users_roles(): void
    {
        $userId = $this->createUser(['tipo' => 'docente']);
        $roleId = DB::table('roles')->insertGetId([
            'nombre' => 'docente',
            'habilitado' => true,
        ]);
        $permissionId = DB::table('permisos')->insertGetId([
            'codigo' => 'pacientes.gestionar',
            'descripcion' => 'Gestionar pacientes',
            'modulo' => 'pacientes',
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => $userId,
            'rol_id' => $roleId,
        ]);
        DB::table('rol_permiso')->insert([
            'rol_id' => $roleId,
            'permiso_id' => $permissionId,
            'habilitado' => true,
        ]);
        $session = $this->createActiveSession($userId, 'sidebar-session');

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('href="'.route('docente.pacientes-derivados').'"', false)
            ->assertDontSee('href="'.route('admin.pacientes.index').'"', false)
            ->assertDontSee('href="'.route('admin.usuarios.index').'"', false);
    }

    public function test_sidebar_only_shows_administrative_modules_with_effective_permissions(): void
    {
        $userId = $this->createUser(['tipo' => 'subadmin']);
        $roleId = DB::table('roles')->insertGetId([
            'nombre' => 'subadmin',
            'habilitado' => true,
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => $userId,
            'rol_id' => $roleId,
        ]);
        $clinicPermissionId = DB::table('permisos')->insertGetId([
            'codigo' => 'clinica.gestionar',
            'descripcion' => 'Gestionar clínicas',
            'modulo' => 'clinica',
        ]);
        DB::table('rol_permiso')->insert([
            'rol_id' => $roleId,
            'permiso_id' => $clinicPermissionId,
            'habilitado' => true,
        ]);

        $this->assertSame(
            ['subadmin'],
            Usuario::with('roles')->findOrFail($userId)->roles->pluck('nombre')->all(),
        );

        $session = $this->createActiveSession($userId, 'subadmin-sidebar-session');

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('href="'.route('admin.clinicas.index').'"', false)
            ->assertDontSee('href="'.route('admin.pacientes.index').'"', false)
            ->assertDontSee('href="'.route('admin.usuarios.index').'"', false)
            ->assertDontSee('href="'.route('admin.tratamientos.index').'"', false);

        $patientPermissionId = DB::table('permisos')->insertGetId([
            'codigo' => 'pacientes.gestionar',
            'descripcion' => 'Gestionar pacientes',
            'modulo' => 'pacientes',
        ]);
        DB::table('rol_permiso')->insert([
            'rol_id' => $roleId,
            'permiso_id' => $patientPermissionId,
            'habilitado' => true,
        ]);

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('href="'.route('admin.clinicas.index').'"', false)
            ->assertSee('href="'.route('admin.pacientes.index').'"', false)
            ->assertDontSee('href="'.route('admin.usuarios.index').'"', false)
            ->assertDontSee('href="'.route('admin.tratamientos.index').'"', false);
    }

    public function test_admin_sidebar_shows_administration_links(): void
    {
        $userId = $this->createUser(['tipo' => 'admin']);
        $roleId = DB::table('roles')->insertGetId([
            'nombre' => 'admin',
            'habilitado' => true,
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => $userId,
            'rol_id' => $roleId,
        ]);
        $session = $this->createActiveSession($userId, 'admin-sidebar-session');

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('href="'.route('admin.roles.index').'"', false)
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_admin_user_rows_show_type_or_fall_back_to_assigned_roles(): void
    {
        $users = collect([
            (object) [
                'id' => 1,
                'ci' => '11111111',
                'nombre_usuario' => 'tipo_definido',
                'nombre_completo' => 'Tipo Definido',
                'tipo' => 'personal',
                'roles' => 'docente',
                'habilitado' => true,
                'ultimo_login' => null,
            ],
            (object) [
                'id' => 2,
                'ci' => '22222222',
                'nombre_usuario' => 'tipo_vacio',
                'nombre_completo' => 'Tipo Vacío',
                'tipo' => null,
                'roles' => 'docente',
                'habilitado' => true,
                'ultimo_login' => null,
            ],
        ]);

        $html = view('admin.usuarios.partials.filas', compact('users'))
            ->with('usuarios', $users)
            ->render();

        $this->assertSame(1, substr_count($html, '<span class="badge badge-secondary">Personal</span>'));
        $this->assertSame(1, substr_count($html, '<span class="badge badge-secondary">Docente</span>'));
    }

    public function test_settings_change_password_only_after_current_password_is_verified(): void
    {
        $userId = $this->createUser();
        $session = $this->createActiveSession($userId, 'password-session');

        $this->withSession($session)
            ->put(route('auth.settings.password'), [
                'current_password' => 'incorrecta',
                'new_password' => 'Nueva-clave-123',
                'new_password_confirmation' => 'Nueva-clave-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('usuarios', [
            'id' => $userId,
            'contrasena' => hash('sha256', 'clave-anterior'),
        ]);

        $this->withSession($session)
            ->put(route('auth.settings.password'), [
                'current_password' => 'clave-anterior',
                'new_password' => 'Nueva-clave-123',
                'new_password_confirmation' => 'Nueva-clave-123',
            ])
            ->assertRedirect(route('auth.settings'));

        $this->assertDatabaseHas('usuarios', [
            'id' => $userId,
        ]);

        $this->assertTrue(Hash::check(
            'Nueva-clave-123',
            (string) DB::table('usuarios')->where('id', $userId)->value('contrasena'),
        ));
    }

    public function test_legacy_password_is_upgraded_after_a_successful_login(): void
    {
        $userId = $this->createUser();
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'estudiante', 'habilitado' => true]);
        DB::table('usuario_rol')->insert(['usuario_id' => $userId, 'rol_id' => 1]);

        $this->post(route('auth.login.submit'), [
            'nombre_usuario' => 'usuario_prueba',
            'contrasena' => 'clave-anterior',
        ])->assertRedirect(route('estudiante.dashboard'));

        $this->assertTrue(Hash::check(
            'clave-anterior',
            (string) DB::table('usuarios')->where('id', $userId)->value('contrasena'),
        ));

        $this->assertDatabaseHas('sesiones', [
            'usuario_id' => $userId,
            'activa' => true,
        ]);
        $this->assertNotEmpty(session('session_token'));
    }

    public function test_login_is_denied_when_user_is_not_allowed_to_login(): void
    {
        $userId = $this->createUser(['permitir_login' => false]);
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'estudiante', 'habilitado' => true]);
        DB::table('usuario_rol')->insert(['usuario_id' => $userId, 'rol_id' => 1]);

        $this->post(route('auth.login.submit'), [
            'nombre_usuario' => 'usuario_prueba',
            'contrasena' => 'clave-anterior',
        ])->assertSessionHasErrors('nombre_usuario');

        $this->assertDatabaseCount('sesiones', 0);
    }

    public function test_disabling_login_revokes_an_existing_session(): void
    {
        $userId = $this->createUser();
        $session = $this->createActiveSession($userId, 'disabled-login-session');
        DB::table('usuarios')->where('id', $userId)->update(['permitir_login' => false]);

        $this->withSession($session)
            ->get(route('auth.profile'))
            ->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('sesiones', [
            'token' => 'disabled-login-session',
            'activa' => false,
        ]);
    }

    public function test_new_login_revokes_the_previous_session_when_multilogin_is_disabled(): void
    {
        $userId = $this->createUser();
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'estudiante', 'habilitado' => true]);
        DB::table('usuario_rol')->insert(['usuario_id' => $userId, 'rol_id' => 1]);
        $this->insertSession($userId, 'previous-session');

        $this->post(route('auth.login.submit'), [
            'nombre_usuario' => 'usuario_prueba',
            'contrasena' => 'clave-anterior',
        ])->assertRedirect(route('estudiante.dashboard'));

        $this->assertDatabaseHas('sesiones', [
            'token' => 'previous-session',
            'activa' => false,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'token' => session('session_token'),
            'activa' => true,
        ]);
    }

    public function test_multilogin_keeps_existing_sessions_active(): void
    {
        $userId = $this->createUser(['multilogin' => true]);
        DB::table('roles')->insert(['id' => 1, 'nombre' => 'estudiante', 'habilitado' => true]);
        DB::table('usuario_rol')->insert(['usuario_id' => $userId, 'rol_id' => 1]);
        $this->insertSession($userId, 'existing-multilogin-session');

        $this->post(route('auth.login.submit'), [
            'nombre_usuario' => 'usuario_prueba',
            'contrasena' => 'clave-anterior',
        ])->assertRedirect(route('estudiante.dashboard'));

        $this->assertDatabaseHas('sesiones', [
            'token' => 'existing-multilogin-session',
            'activa' => true,
        ]);
        $this->assertSame(2, DB::table('sesiones')->where('usuario_id', $userId)->where('activa', true)->count());
    }

    public function test_logout_marks_the_current_session_inactive(): void
    {
        $userId = $this->createUser();
        $session = $this->createActiveSession($userId, 'logout-session');

        $this->withSession($session)
            ->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('sesiones', [
            'token' => 'logout-session',
            'activa' => false,
        ]);
    }

    public function test_closing_other_sessions_keeps_only_the_current_session_active(): void
    {
        $userId = $this->createUser(['multilogin' => true]);
        $session = $this->createActiveSession($userId, 'current-session');
        $this->insertSession($userId, 'other-session');

        $this->withSession($session)
            ->post(route('auth.settings.sessions.close-others'))
            ->assertRedirect(route('auth.settings'));

        $this->assertDatabaseHas('sesiones', [
            'token' => 'current-session',
            'activa' => true,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'token' => 'other-session',
            'activa' => false,
        ]);
    }

    public function test_closing_an_individual_session_blocks_its_next_request(): void
    {
        $userId = $this->createUser(['multilogin' => true]);
        $currentSession = $this->createActiveSession($userId, 'current-session');
        $this->insertSession($userId, 'revoked-session');
        $revokedSessionId = DB::table('sesiones')->where('token', 'revoked-session')->value('id');

        $this->withSession($currentSession)
            ->delete(route('auth.settings.sessions.close', $revokedSessionId))
            ->assertRedirect(route('auth.settings'));

        $this->assertDatabaseHas('sesiones', [
            'id' => $revokedSessionId,
            'activa' => false,
        ]);

        $this->withSession([
            'user_id' => $userId,
            'user_role' => 'estudiante',
            'session_token' => 'revoked-session',
        ])->get(route('auth.profile'))->assertRedirect(route('auth.login'));
    }

    public function test_admin_can_disable_login_and_revoke_the_users_sessions(): void
    {
        $adminId = $this->createUser([
            'ci' => 'admin-ci',
            'nombre_usuario' => 'admin_user',
            'nombre_completo' => 'Administrador',
            'tipo' => 'admin',
        ]);
        $userId = $this->createUser([
            'ci' => '87654321',
            'nombre_usuario' => 'usuario_objetivo',
            'nombre_completo' => 'Usuario Objetivo',
            'tipo' => 'docente',
            'multilogin' => true,
        ]);
        $adminRoleId = DB::table('roles')->insertGetId([
            'nombre' => 'admin',
            'habilitado' => true,
        ]);
        $docenteRoleId = DB::table('roles')->insertGetId([
            'nombre' => 'docente',
            'habilitado' => true,
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => $adminId,
            'rol_id' => $adminRoleId,
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => $userId,
            'rol_id' => $docenteRoleId,
        ]);
        $adminSession = $this->createActiveSession($adminId, 'admin-session');
        $this->insertSession($userId, 'target-session-one');
        $this->insertSession($userId, 'target-session-two');

        $this->withSession($adminSession)
            ->put(route('admin.usuarios.actualizar', $userId), [
                'ci' => '87654321',
                'nombre_usuario' => 'usuario_objetivo',
                'nombre_completo' => 'Usuario Objetivo',
                'descripcion' => '',
                'tipo' => 'docente',
                'roles' => [$docenteRoleId],
                'habilitado' => '1',
                'permitir_login' => '0',
                'multilogin' => '0',
                'contrasena' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('usuarios', [
            'id' => $userId,
            'habilitado' => true,
            'permitir_login' => false,
            'multilogin' => false,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'token' => 'target-session-one',
            'activa' => false,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'token' => 'target-session-two',
            'activa' => false,
        ]);
    }

    private function createUser(array $overrides = []): int
    {
        return DB::table('usuarios')->insertGetId(array_merge([
            'ci' => '12345678',
            'nombre_usuario' => 'usuario_prueba',
            'nombre_completo' => 'Usuario de Prueba',
            'descripcion' => null,
            'contrasena' => hash('sha256', 'clave-anterior'),
        ], $overrides));
    }

    private function createActiveSession(int $userId, string $token): array
    {
        $this->insertSession($userId, $token);

        return [
            'user_id' => $userId,
            'user_role' => 'estudiante',
            'session_token' => $token,
        ];
    }

    private function insertSession(int $userId, string $token): void
    {
        DB::table('sesiones')->insert([
            'usuario_id' => $userId,
            'token' => $token,
            'inicio' => now(),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
