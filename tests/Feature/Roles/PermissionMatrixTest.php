<?php

namespace Tests\Feature\Roles;

use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    public function test_a_guest_is_redirected_to_login_when_viewing_roles(): void
    {
        $response = $this->get(route('roles.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
