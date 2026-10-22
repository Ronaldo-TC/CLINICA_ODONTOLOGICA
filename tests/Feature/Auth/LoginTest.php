<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\RegistrarAcciones;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_a_guest_can_view_the_login_form(): void
    {
        $response = $this->get(route('auth.login'));

        $response->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('name="nombre_usuario"', false)
            ->assertSee('name="contrasena"', false)
            ->assertSee('Acceso institucional seguro')
            ->assertSee('class="password-toggle"', false)
            ->assertSee(asset('images/login/logo-icon.png'), false)
            ->assertSee(asset('images/login/logo.png'), false)
            ->assertSee(asset('images/login/logo-compacto.webp'), false)
            ->assertSee(asset('images/login/fondo-pc.png'), false)
            ->assertSee(asset('images/login/fondo-pc.webp'), false)
            ->assertSee(asset('images/login/fondo-movil.png'), false)
            ->assertSee(asset('images/login/fondo-movil.webp'), false);
    }

    public function test_login_validation_messages_are_displayed_in_spanish(): void
    {
        $this->withoutMiddleware(RegistrarAcciones::class);

        $this->postJson(route('auth.login.post'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Datos inválidos.')
            ->assertJsonPath('errors.nombre_usuario.0', 'Ingresa tu usuario institucional.')
            ->assertJsonPath('errors.contrasena.0', 'Ingresa tu contraseña.');
    }
}
