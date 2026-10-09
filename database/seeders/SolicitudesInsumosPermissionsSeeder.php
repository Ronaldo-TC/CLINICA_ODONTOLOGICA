<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SolicitudesInsumosPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $matrix = [
                'estudiante' => ['insumos.ver', 'insumos.crear'],
                'docente' => ['insumos.ver', 'insumos.autorizar'],
                'insumos' => ['insumos.ver', 'insumos.aprobar', 'insumos.entregar'],
            ];

            foreach (collect($matrix)->flatten()->unique() as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name, 'descripcion' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $permissions = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');
            $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
            foreach ($matrix as $role => $codes) {
                if (! isset($roles[$role])) {
                    continue;
                }

                foreach ($codes as $code) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'role_id' => $roles[$role], 'permission_id' => $permissions[$code],
                    ]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
