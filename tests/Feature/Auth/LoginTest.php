<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_a_guest_can_view_the_login_form(): void
    {
        $response = $this->get(route('auth.login'));

        $response->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('name="nombre_usuario"', false)
            ->assertSee('name="contrasena"', false);
    }
}
