<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class CheckRoleTest extends TestCase
{
    use UsesAuthDatabase {
        setUp as protected setUpAuthDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpAuthDatabase();

        Route::middleware(['web', 'auth.session', 'role:subadmin|admin'])->group(function (): void {
            Route::get('/permission-check/users', fn (): string => 'ok')->middleware('permission:usuarios.ver');
            Route::post('/permission-check/users', fn (): string => 'ok')->middleware('permission:usuarios.editar');
        });
    }

    public function test_assigned_role_permission_allows_read_access(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'usuarios.ver', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->getJson('/permission-check/users')->assertOk();
    }

    public function test_read_permission_alone_returns_403_for_write_access(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'usuarios.ver', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->postJson('/permission-check/users')->assertForbidden();
    }

    public function test_assigned_write_permission_allows_write_access(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'usuarios.editar', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->postJson('/permission-check/users')->assertOk();
    }

    public function test_permission_returns_403_for_a_role_outside_the_allowed_roles(): void
    {
        $user = $this->createUser();
        $user->assignRole(Role::create(['name' => 'estudiante', 'guard_name' => 'web']));
        $user->givePermissionTo(Permission::create(['name' => 'usuarios.ver', 'guard_name' => 'web']));

        $this->actingAs($user)->getJson('/permission-check/users')->assertForbidden();
    }

    public function test_revoked_role_permission_returns_403_for_read_access(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'usuarios.ver', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $role->revokePermissionTo($permission);

        $this->actingAs($user)->getJson('/permission-check/users')->assertForbidden();
    }

    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $this->get('/permission-check/users')->assertRedirect(route('auth.login'));
    }
}
