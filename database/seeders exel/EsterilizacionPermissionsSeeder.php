<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class EsterilizacionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matrix = [
            'admin' => ['ver', 'crear', 'editar', 'eliminar', 'gestionar', 'autorizar'],
            'subadmin' => ['ver', 'crear', 'editar', 'eliminar', 'gestionar'],
            'esterilizacion' => ['ver', 'crear', 'editar', 'eliminar', 'gestionar'],
            'docente' => ['ver', 'autorizar'],
            'jefe-catedra' => ['ver', 'autorizar'],
            'estudiante' => ['ver', 'crear'],
            'interno' => ['ver', 'crear'],
        ];
        $codes = collect($matrix)->flatten()->unique()->map(fn (string $action): string => 'esterilizaciones.'.$action);
        DB::table('permissions')->insertOrIgnore($codes->map(fn (string $code): array => [
            'name' => $code, 'descripcion' => $code, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ])->all());
        $permissions = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');
        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $assignments = [];
        foreach ($matrix as $role => $actions) {
            if (! isset($roles[$role])) {
                continue;
            }
            foreach ($actions as $action) {
                $assignments[] = ['role_id' => $roles[$role], 'permission_id' => $permissions['esterilizaciones.'.$action]];
            }
        }
        DB::table('role_has_permissions')->insertOrIgnore($assignments);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
