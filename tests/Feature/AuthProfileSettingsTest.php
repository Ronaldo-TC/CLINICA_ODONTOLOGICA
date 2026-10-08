<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class AuthProfileSettingsTest extends TestCase
{
    use UsesAuthDatabase;

    public function test_profile_renders_personal_data_and_session_statistics(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'profile-session');

        $this->withSession($session)->get(route('auth.profile'))
            ->assertOk()
            ->assertViewIs('auth.profile')
            ->assertSee('Datos personales')
            ->assertViewHas('stats', fn (array $stats): bool => $stats['sesiones_activas'] === 1 && $stats['total_sesiones'] === 1);
    }

    public function test_profile_uses_local_initials_when_no_photo_is_uploaded(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('auth.profile'))
            ->assertSee('Usuario de Prueba')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('UP')
            ->assertDontSee('ui-avatars.com');
    }

    public function test_uploaded_profile_photo_is_shown_in_the_profile_and_sidebar(): void
    {
        $user = $this->createUser(['foto' => 'avatars/profile.png']);

        $this->actingAs($user)->get(route('auth.profile'))
            ->assertSee('src="'.asset('storage/avatars/profile.png').'"', false)
            ->assertSee('alt="Foto de Usuario de Prueba"', false)
            ->assertSee('class="sidebar-user"', false);
    }

    public function test_any_authenticated_role_can_update_its_own_profile(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'profile-update-session');

        $this->withSession($session)->put(route('auth.profile.update'), [
            'ci' => '87654321',
            'nombre_usuario' => 'usuario_nuevo',
            'nombre_completo' => 'Nombre actualizado',
            'descripcion' => null,
            'habilitado' => false,
            'tipo' => 'admin',
            'roles' => ['admin'],
        ])->assertRedirect(route('auth.profile'))
            ->assertSessionHas('user_name', 'Nombre actualizado')
            ->assertSessionHas('user_username', 'usuario_nuevo');

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'ci' => '87654321',
            'nombre_usuario' => 'usuario_nuevo',
            'nombre_completo' => 'Nombre actualizado',
            'descripcion' => '',
            'habilitado' => true,
            'tipo' => '',
        ]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $user->id]);
    }

    public function test_profile_and_settings_require_a_valid_session_user(): void
    {
        $this->get(route('auth.profile'))->assertRedirect(route('auth.login'));
        $this->get(route('auth.settings'))->assertRedirect(route('auth.login'));
    }

    public function test_sidebar_shows_only_modules_enabled_for_the_users_roles(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'docente', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'pacientes.ver', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->get(route('auth.profile'))
            ->assertSee('href="'.route('pacientes.index').'"', false)
            ->assertDontSee('href="'.route('usuarios.index').'"', false)
            ->assertDontSee('href="'.route('clinicas.index').'"', false);
    }

    public function test_sidebar_only_shows_administrative_modules_with_effective_permissions(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'clinicas.ver', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->get(route('auth.profile'))
            ->assertSee('href="'.route('clinicas.index').'"', false)
            ->assertDontSee('href="'.route('pacientes.index').'"', false)
            ->assertDontSee('href="'.route('usuarios.index').'"', false);
    }

    public function test_admin_sidebar_shows_assigned_administration_links(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo([
            Permission::create(['name' => 'roles.ver', 'guard_name' => 'web']),
            Permission::create(['name' => 'usuarios.ver', 'guard_name' => 'web']),
        ]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('auth.profile'))
            ->assertSee('href="'.route('roles.index').'"', false)
            ->assertSee('href="'.route('usuarios.index').'"', false);
    }

    public function test_user_rows_show_assigned_spatie_roles(): void
    {
        $user = $this->createUser();
        $user->assignRole(Role::create(['name' => 'docente', 'guard_name' => 'web']));
        $userWithoutRole = $this->createUser(['ci' => 'other-ci', 'nombre_usuario' => 'otro_usuario']);

        $this->view('usuarios.partials.filas', ['items' => collect([$user, $userWithoutRole])])
            ->assertSee('docente')
            ->assertSee('Sin rol');
    }

    public function test_user_rows_render_uploaded_profile_photos(): void
    {
        $user = $this->createUser(['foto' => 'avatars/usuario.png']);

        $this->view('usuarios.partials.filas', ['items' => collect([$user])])
            ->assertSee('src="'.asset('storage/avatars/usuario.png').'"', false);
    }

    public function test_settings_render_only_the_users_active_sessions(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'settings-session');
        $otherUser = $this->createUser(['ci' => 'other-ci', 'nombre_usuario' => 'otro_usuario']);
        $this->insertSession($otherUser, 'other-session');

        $this->withSession($session)->get(route('auth.settings'))
            ->assertOk()
            ->assertViewIs('auth.settings')
            ->assertSee('Cambiar contraseña')
            ->assertViewHas('sesionesActivas', fn ($sessions): bool => $sessions->count() === 1 && $sessions->first()->usuario_id === $user->id);
    }

    public function test_settings_reject_a_wrong_current_password_without_changing_it(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'password-session');
        $previousHash = $user->contrasena;

        $this->withSession($session)->put(route('auth.settings.password'), [
            'current_password' => 'incorrecta',
            'new_password' => 'Nueva-clave-123',
            'new_password_confirmation' => 'Nueva-clave-123',
        ])->assertSessionHasErrors(['current_password' => 'La contraseña actual no es correcta.']);

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'contrasena' => $previousHash]);
    }

    public function test_settings_change_password_after_current_password_is_verified(): void
    {
        $user = $this->createUser(['contrasena' => hash('sha256', 'clave-anterior')]);
        $session = $this->createActiveSession($user, 'password-session');

        $this->withSession($session)->put(route('auth.settings.password'), [
            'current_password' => 'clave-anterior',
            'new_password' => 'Nueva-clave-123',
            'new_password_confirmation' => 'Nueva-clave-123',
        ])->assertRedirect(route('auth.settings'));

        $this->assertTrue(Hash::check('Nueva-clave-123', $user->fresh()->contrasena));
    }

    #[TestWith(['abc', 'abc', 'La nueva contraseña debe tener al menos 8 caracteres.'])]
    #[TestWith(['sinmayusculas123', 'sinmayusculas123', 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.'])]
    #[TestWith(['Nueva-clave-123', 'Otra-clave-123', 'La confirmación de contraseña no coincide.'])]
    public function test_settings_reject_invalid_new_passwords(string $password, string $confirmation, string $message): void
    {
        $user = $this->createUser();
        $previousHash = $user->contrasena;

        $this->actingAs($user)->put(route('auth.settings.password'), [
            'current_password' => 'clave-anterior',
            'new_password' => $password,
            'new_password_confirmation' => $confirmation,
        ])->assertSessionHasErrors(['new_password' => $message]);

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'contrasena' => $previousHash]);
    }

    public function test_legacy_password_is_upgraded_after_a_successful_login(): void
    {
        $user = $this->createUser(['contrasena' => hash('sha256', 'clave-anterior')]);

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertOk()->assertJsonPath('redirect', route('dashboard'));

        $this->assertTrue(Hash::check('clave-anterior', $user->fresh()->contrasena));
        $this->assertDatabaseHas('sesiones', ['usuario_id' => $user->id, 'activa' => true]);
        $this->assertAuthenticatedAs($user, 'web');
    }

    #[TestWith(['habilitado'])]
    #[TestWith(['permitir_login'])]
    public function test_login_is_denied_when_the_account_is_disabled(string $flag): void
    {
        $user = $this->createUser([$flag => false]);

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertUnprocessable()->assertJsonPath('success', false);

        $this->assertDatabaseCount('sesiones', 0);
        $this->assertGuest('web');
    }

    public function test_disabling_login_revokes_an_existing_session(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'disabled-session');
        DB::table('usuarios')->where('id', $user->id)->update(['permitir_login' => false]);

        $this->withSession($session)->get(route('auth.profile'))->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('sesiones', ['token' => 'disabled-session', 'activa' => false]);
    }

    public function test_new_login_revokes_the_previous_session_when_multilogin_is_disabled(): void
    {
        $user = $this->createUser();
        $this->insertSession($user, 'previous-session');

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertOk();

        $this->assertDatabaseHas('sesiones', ['token' => 'previous-session', 'activa' => false]);
        $this->assertDatabaseHas('sesiones', ['token' => session('session_token'), 'activa' => true]);
    }

    public function test_multilogin_keeps_existing_sessions_active(): void
    {
        $user = $this->createUser(['multilogin' => true]);
        $this->insertSession($user, 'existing-session');

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertOk();

        $this->assertDatabaseHas('sesiones', ['token' => 'existing-session', 'activa' => true]);
        $this->assertDatabaseCount('sesiones', 2);
    }

    public function test_logout_marks_the_current_session_inactive(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'logout-session');

        $this->actingAs($user)->withSession($session)->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('sesiones', ['token' => 'logout-session', 'activa' => false]);
        $this->assertGuest('web');
    }

    public function test_closing_other_sessions_keeps_only_the_current_session_active(): void
    {
        $user = $this->createUser(['multilogin' => true]);
        $session = $this->createActiveSession($user, 'current-session');
        $this->insertSession($user, 'other-session');

        $this->withSession($session)->post(route('auth.settings.sessions.close-others'))
            ->assertRedirect(route('auth.settings'));

        $this->assertDatabaseHas('sesiones', ['token' => 'current-session', 'activa' => true]);
        $this->assertDatabaseHas('sesiones', ['token' => 'other-session', 'activa' => false]);
    }

    public function test_closing_an_individual_session_blocks_its_next_request(): void
    {
        $user = $this->createUser(['multilogin' => true]);
        $session = $this->createActiveSession($user, 'current-session');
        $revokedId = $this->insertSession($user, 'revoked-session');

        $this->withSession($session)->delete(route('auth.settings.sessions.close', $revokedId))
            ->assertRedirect(route('auth.settings'));

        $this->assertDatabaseHas('sesiones', ['id' => $revokedId, 'activa' => false]);
        $this->actingAs($user)->withSession(['user_id' => $user->id, 'session_token' => 'revoked-session'])
            ->get(route('auth.profile'))->assertRedirect(route('auth.login'));
    }

    public function test_closing_the_current_session_logs_the_user_out(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'current-session');
        $sessionId = DB::table('sesiones')->where('token', 'current-session')->value('id');

        $this->withSession($session)->delete(route('auth.settings.sessions.close', $sessionId))
            ->assertRedirect(route('auth.login'));

        $this->assertDatabaseHas('sesiones', ['id' => $sessionId, 'activa' => false]);
        $this->assertGuest('web');
    }

    public function test_returns_404_for_attempting_to_close_another_users_session(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser(['ci' => 'other-ci', 'nombre_usuario' => 'otro_usuario']);
        $otherSessionId = $this->insertSession($otherUser, 'other-user-session');

        $this->actingAs($user)->delete(route('auth.settings.sessions.close', $otherSessionId))->assertNotFound();

        $this->assertDatabaseHas('sesiones', ['id' => $otherSessionId, 'activa' => true]);
    }

    #[TestWith(['habilitado'])]
    #[TestWith(['permitir_login'])]
    public function test_admin_disabling_an_account_revokes_its_sessions_and_tokens(string $flag): void
    {
        $admin = $this->createUser();
        $admin->givePermissionTo(Permission::create(['name' => 'usuarios.editar', 'guard_name' => 'web']));
        $user = $this->createUser(['ci' => 'target-ci', 'nombre_usuario' => 'usuario_objetivo']);
        $this->insertSession($user, 'target-session');
        $token = $user->createToken('test-device')->accessToken;

        $this->actingAs($admin)->putJson(route('usuarios.update', $user->id), [
            'ci' => $user->ci,
            'nombre_usuario' => $user->nombre_usuario,
            'nombre_completo' => $user->nombre_completo,
            'habilitado' => $flag !== 'habilitado',
            'permitir_login' => $flag !== 'permitir_login',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, $flag => false]);
        $this->assertDatabaseHas('sesiones', ['token' => 'target-session', 'activa' => false]);
        $this->assertModelMissing($token);
    }

    public function test_admin_disabling_a_user_revokes_its_sessions_and_tokens(): void
    {
        $admin = $this->createUser();
        $admin->givePermissionTo(Permission::create(['name' => 'usuarios.eliminar', 'guard_name' => 'web']));
        $user = $this->createUser(['ci' => 'target-ci', 'nombre_usuario' => 'usuario_objetivo']);
        $this->insertSession($user, 'target-session');
        $token = $user->createToken('test-device')->accessToken;

        $this->actingAs($admin)->deleteJson(route('usuarios.destroy', $user->id))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'habilitado' => false, 'permitir_login' => false]);
        $this->assertDatabaseHas('sesiones', ['token' => 'target-session', 'activa' => false]);
        $this->assertModelMissing($token);
    }

    public function test_updating_profile_replaces_the_photo_and_deletes_the_old_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.png', 'old-photo');
        $user = $this->createUser(['foto' => 'avatars/old.png']);
        $photo = UploadedFile::fake()->image('avatar.png');

        $this->actingAs($user)->put(route('auth.profile.update'), [
            'ci' => $user->ci,
            'nombre_usuario' => $user->nombre_usuario,
            'nombre_completo' => $user->nombre_completo,
            'foto' => $photo,
        ])->assertRedirect(route('auth.profile'));

        Storage::disk('public')->assertExists($user->fresh()->foto);
        Storage::disk('public')->assertMissing('avatars/old.png');
    }

    public function test_deleting_avatar_removes_the_file_and_clears_the_profile_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.png', 'old-photo');
        $user = $this->createUser(['foto' => 'avatars/old.png']);

        $this->actingAs($user)->delete(route('auth.profile.avatar.delete'))->assertRedirect(route('auth.profile'));

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'foto' => null]);
        Storage::disk('public')->assertMissing('avatars/old.png');
    }
}
