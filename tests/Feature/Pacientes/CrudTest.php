<?php

namespace Tests\Feature\Pacientes;

use Tests\TestCase;

class CrudTest extends TestCase
{
    public function test_a_guest_is_redirected_to_login_when_viewing_patients(): void
    {
        $response = $this->get(route('pacientes.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
