<?php

namespace Tests\Feature\Roles;

use App\Support\MenuBuilder;
use Database\Seeders\FlujoClinicoPermissionsSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RoleHasPermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\SolicitudesInsumosPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_a_guest_is_redirected_to_login_when_viewing_roles(): void
    {
        $response = $this->get(route('roles.index'));

        $response->assertRedirect(route('auth.login'));
    }

    /** @return array<string, array{string, bool}> */
    public static function restrictedRoles(): array
    {
        $cases = [];
        foreach (['estudiante', 'interno', 'admision'] as $role) {
            $cases[$role.' nuevo'] = [$role, false];
            $cases[$role.' con permisos antiguos'] = [$role, true];
        }

        return $cases;
    }

    /** @return array<string, array{string}> */
    public static function academicSupervisors(): array
    {
        return [
            'docente' => ['docente'],
            'jefe de catedra' => ['jefe-catedra'],
            'jefatura de clinicas' => ['jefatura-clinicas'],
        ];
    }

    #[DataProvider('academicSupervisors')]
    public function test_academic_supervisors_can_view_payments_without_modifying_them(string $roleName): void
    {
        $this->seed([RolesSeeder::class, PermissionsSeeder::class]);
        $role = Role::findByName($roleName, 'web');
        $role->syncPermissions([]);

        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);

        $user = $this->clinicalUser($roleName);
        $this->assertTrue($user->can('cajas.ver'));
        $this->assertFalse($user->canAny(['cajas.crear', 'cajas.editar', 'cajas.eliminar', 'cajas.gestionar']));
        $this->assertContains('cajas.index', array_column(MenuBuilder::forUser($user), 'route'));

        $this->actingAs($user)->get(route('cajas.index'))->assertOk();
        $this->getJson(route('cajas.tabla'))->assertOk();
        $this->postJson(route('cajas.store'), [])->assertForbidden();
    }

    #[DataProvider('restrictedRoles')]
    public function test_clinical_roles_cannot_access_cashier_or_inventory_after_any_permission_seeder(string $roleName, bool $hasLegacyPermissions): void
    {
        $this->seed([RolesSeeder::class, PermissionsSeeder::class]);
        $role = Role::findByName($roleName, 'web');
        $role->syncPermissions(['bitacoras.ver']);
        if ($hasLegacyPermissions) {
            $role->givePermissionTo(DB::table('permissions')->where('guard_name', 'web')
                ->where(fn ($query) => $query->where('name', 'like', 'cajas.%')->orWhere('name', 'like', 'insumos.%'))->pluck('name')->all());
        }

        foreach ([RoleHasPermissionsSeeder::class, FlujoClinicoPermissionsSeeder::class, SolicitudesInsumosPermissionsSeeder::class] as $seeder) {
            $this->seed($seeder);
            $this->seed($seeder);
            $permissions = $role->fresh()->permissions->pluck('name');
            $this->assertFalse($permissions->contains(fn (string $name): bool => str_starts_with($name, 'cajas.') || str_starts_with($name, 'insumos.')));
            $this->assertContains('bitacoras.ver', $permissions);
        }

        $user = $this->clinicalUser($roleName);
        $this->actingAs($user);
        $routes = array_column(MenuBuilder::forUser($user), 'route');
        $this->assertNotContains('cajas.index', $routes);
        $this->assertNotContains('insumos.index', $routes);

        foreach (['cajas.index', 'cajas.tabla', 'insumos.index', 'insumos.tabla', 'api.v1.insumos.index'] as $route) {
            $this->getJson(route($route))->assertForbidden();
        }
        $this->postJson(route('cajas.store'), [])->assertForbidden();
        $this->postJson(route('insumos.store'), [])->assertForbidden();

        if ($roleName === 'admision') {
            $this->assertNotContains('solicitudes-insumos.index', $routes);
            $this->getJson(route('solicitudes-insumos.tabla'))->assertForbidden();
            $this->postJson(route('solicitudes-insumos.store'), [])->assertForbidden();
        } else {
            $this->assertContains('solicitudes-insumos.index', $routes);
            $this->getJson(route('solicitudes-insumos.tabla'))->assertOk();
            $this->getJson(route('api.v1.solicitudes.index'))->assertOk();
        }
    }

    public function test_admin_cashier_and_warehouse_keep_their_operational_access(): void
    {
        $this->seed([RolesSeeder::class, PermissionsSeeder::class, RoleHasPermissionsSeeder::class, FlujoClinicoPermissionsSeeder::class]);

        $admin = $this->clinicalUser('admin');
        foreach (DB::table('permissions')->where('guard_name', 'web')->pluck('name') as $permission) {
            $this->assertTrue($admin->can($permission), $permission);
        }
        $this->actingAs($admin)->getJson(route('cajas.tabla'))->assertOk();
        $this->getJson(route('insumos.tabla'))->assertOk();
        $admin->assignRole('estudiante');
        $this->getJson(route('cajas.tabla'))->assertOk();
        $this->getJson(route('insumos.tabla'))->assertOk();
        $this->actingAs($this->clinicalUser('cajas'))->getJson(route('cajas.tabla'))->assertOk();
        $warehouse = $this->clinicalUser('insumos');
        $this->actingAs($warehouse)->getJson(route('insumos.tabla'))->assertOk();
        $this->getJson(route('solicitudes-insumos.tabla'))->assertOk();
        $this->assertTrue($warehouse->can('solicitudes-insumos.aprobar'));
        $this->assertTrue($warehouse->can('solicitudes-insumos.entregar'));
    }
}
