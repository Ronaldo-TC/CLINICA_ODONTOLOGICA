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
                'estudiante' => ['solicitudes-insumos.ver', 'solicitudes-insumos.crear', 'solicitudes-insumos.consumir'],
                'interno' => ['solicitudes-insumos.ver', 'solicitudes-insumos.crear', 'solicitudes-insumos.consumir'],
                'docente' => ['cajas.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.rechazar'],
                'jefe-catedra' => ['cajas.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.rechazar'],
                'jefatura-clinicas' => ['cajas.ver'],
                'director' => ['solicitudes-insumos.ver'],
                'insumos' => ['solicitudes-insumos.ver', 'solicitudes-insumos.aprobar', 'solicitudes-insumos.rechazar', 'solicitudes-insumos.entregar', 'solicitudes-insumos.consumir'],
            ];

            $requestPermissions = collect(['ver', 'crear', 'editar', 'eliminar', 'gestionar', 'autorizar', 'aprobar', 'rechazar', 'entregar', 'consumir'])
                ->map(fn (string $action): string => 'solicitudes-insumos.'.$action);
            DB::table('permissions')->insertOrIgnore($requestPermissions->map(fn (string $name): array => [
                'name' => $name, 'descripcion' => $name, 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ])->all());

            $permissions = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');
            $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
            $restrictedRoles = $roles->only(['estudiante', 'interno', 'docente', 'admision'])->values();
            $inventoryPermissions = $permissions->filter(fn (int $id, string $name): bool => str_starts_with($name, 'cajas.') || str_starts_with($name, 'insumos.'))->values();

            DB::table('role_has_permissions')->whereIn('role_id', $restrictedRoles)
                ->whereIn('permission_id', $inventoryPermissions)->delete();

            $matrix['admin'] = $permissions->keys()->all();
            $assignments = [];
            foreach ($matrix as $role => $codes) {
                if (! isset($roles[$role])) {
                    continue;
                }

                foreach ($codes as $code) {
                    $assignments[] = [
                        'role_id' => $roles[$role], 'permission_id' => $permissions[$code],
                    ];
                }
            }
            DB::table('role_has_permissions')->insertOrIgnore($assignments);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
