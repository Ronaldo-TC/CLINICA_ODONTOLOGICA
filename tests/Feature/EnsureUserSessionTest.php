<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class EnsureUserSessionTest extends TestCase
{
    use UsesAuthDatabase;

    public function test_returns_401_and_logs_out_a_guard_user_whose_web_session_was_revoked(): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'revoked-session');
        DB::table('sesiones')->where('token', 'revoked-session')->update(['activa' => false]);

        $this->actingAs($user)->withSession($session)
            ->getJson(route('notificaciones.ultimas'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertSessionMissing('user_id')
            ->assertSessionMissing('session_token');

        $this->assertGuest('web');
    }

    #[TestWith(['habilitado'])]
    #[TestWith(['permitir_login'])]
    public function test_returns_401_and_revokes_the_session_when_the_account_is_disabled(string $flag): void
    {
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'disabled-session');
        DB::table('usuarios')->where('id', $user->id)->update([$flag => false]);

        $this->actingAs($user)->withSession($session)
            ->getJson(route('notificaciones.ultimas'))
            ->assertUnauthorized();

        $this->assertGuest('web');
        $this->assertDatabaseHas('sesiones', ['token' => 'disabled-session', 'activa' => false]);
    }

    public function test_returns_401_for_a_disabled_guard_user_without_legacy_session_metadata(): void
    {
        $user = $this->createUser();
        DB::table('usuarios')->where('id', $user->id)->update(['permitir_login' => false]);

        $this->actingAs($user)->getJson(route('notificaciones.ultimas'))->assertUnauthorized();

        $this->assertGuest('web');
    }

    public function test_an_active_guard_user_can_access_without_legacy_session_metadata(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->getJson(route('notificaciones.ultimas'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_valid_legacy_session_authenticates_the_user_and_updates_last_access(): void
    {
        $this->freezeTime();
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'legacy-session');

        $this->withSession($session)->getJson(route('notificaciones.ultimas'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('sesiones', [
            'token' => 'legacy-session',
            'ultimo_acceso' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_authenticated_web_session_keeps_updating_last_access(): void
    {
        $this->freezeTime();
        $user = $this->createUser();
        $session = $this->createActiveSession($user, 'guard-session');

        $this->actingAs($user)->withSession($session)
            ->getJson(route('notificaciones.ultimas'))->assertOk();

        $this->assertDatabaseHas('sesiones', [
            'token' => 'guard-session',
            'ultimo_acceso' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_returns_401_when_guard_identity_does_not_match_the_web_session(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser(['ci' => 'other-ci', 'nombre_usuario' => 'otro_usuario']);
        $session = $this->createActiveSession($otherUser, 'other-user-session');

        $this->actingAs($user)->withSession($session)
            ->getJson(route('notificaciones.ultimas'))->assertUnauthorized();

        $this->assertGuest('web');
        $this->assertDatabaseHas('sesiones', ['token' => 'other-user-session', 'activa' => true]);
    }

    public function test_redirects_to_login_for_a_guard_user_with_missing_web_session_token(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->withSession(['user_id' => $user->id])
            ->get(route('auth.profile'))->assertRedirect(route('auth.login'));

        $this->assertGuest('web');
    }
}
