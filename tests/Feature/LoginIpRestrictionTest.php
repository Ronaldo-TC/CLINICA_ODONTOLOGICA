<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class LoginIpRestrictionTest extends TestCase
{
    use UsesAuthDatabase;

    #[TestWith([null, '127.0.0.1'])]
    #[TestWith(['127.0.0.1', '127.0.0.1'])]
    #[TestWith(['127.0.0.0/8', '127.0.0.1'])]
    #[TestWith(['192.0.2.10,127.0.0.1 198.51.100.0/24', '127.0.0.1'])]
    #[TestWith(['::1', '::1'])]
    #[TestWith(['2001:db8::/32', '2001:db8::123'])]
    public function test_web_login_accepts_an_allowed_address_or_cidr(?string $allowedIps, string $requestIp): void
    {
        $user = $this->createUser(['ip_permitida' => $allowedIps]);

        $this->withServerVariables(['REMOTE_ADDR' => $requestIp])
            ->postJson(route('auth.login.post'), [
                'nombre_usuario' => $user->nombre_usuario,
                'contrasena' => 'clave-anterior',
            ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('sesiones', ['usuario_id' => $user->id, 'ip' => $requestIp, 'activa' => true]);
        $this->assertAuthenticatedAs($user, 'web');
    }

    #[TestWith(['192.0.2.10'])]
    #[TestWith(['192.0.2.0/24'])]
    #[TestWith(['127.0.0.1/abc'])]
    #[TestWith(['127.0.0.1/33'])]
    public function test_web_login_returns_422_for_an_ip_outside_the_allowed_addresses(string $allowedIps): void
    {
        $user = $this->createUser(['ip_permitida' => $allowedIps]);
        $this->insertSession($user, 'existing-session');

        $this->postJson(route('auth.login.post'), [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.nombre_usuario.0', 'No tienes permitido iniciar sesión desde esta IP.');

        $this->assertDatabaseCount('sesiones', 1);
        $this->assertDatabaseHas('sesiones', ['token' => 'existing-session', 'activa' => true]);
        $this->assertGuest('web');
    }

    #[TestWith(['/api/login'])]
    #[TestWith(['/api/v1/login'])]
    public function test_api_login_returns_403_and_preserves_existing_tokens_for_a_blocked_ip(string $endpoint): void
    {
        $user = $this->createUser(['ip_permitida' => '192.0.2.0/24']);
        $token = $user->createToken('existing-device')->accessToken;

        $this->postJson($endpoint, [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertForbidden()->assertJsonPath('message', 'No tienes permitido iniciar sesión desde esta IP.');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertModelExists($token);
        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'ultimo_login' => null]);
    }

    #[TestWith(['/api/login'])]
    #[TestWith(['/api/v1/login'])]
    public function test_api_login_issues_a_token_for_an_ip_inside_the_allowed_cidr(string $endpoint): void
    {
        $user = $this->createUser(['ip_permitida' => '127.0.0.0/8']);

        $this->postJson($endpoint, [
            'nombre_usuario' => $user->nombre_usuario,
            'contrasena' => 'clave-anterior',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'tokenable_type' => $user::class]);
    }
}
