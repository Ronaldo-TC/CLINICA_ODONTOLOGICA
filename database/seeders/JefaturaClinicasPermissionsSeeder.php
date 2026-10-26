<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class JefaturaClinicasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roleId = DB::table('roles')
                ->where('name', 'jefatura-clinicas')
                ->where('guard_name', 'web')
                ->value('id');

            if ($roleId === null) {
                return;
            }

            $codes = [
                'clinicas.ver', 'clinicas.gestionar',
                'pacientes.ver', 'pacientes.gestionar',
                'tratamientos.ver', 'tratamientos.gestionar',
                'especialidades.ver', 'especialidades.gestionar',
                'admisiones.ver',
                'remisiones.ver', 'remisiones.asignar',
                'historias.ver', 'atenciones.ver', 'sesiones.ver',
                'insumos.ver', 'solicitudes-insumos.ver',
                'cajas.ver',
                'esterilizaciones.ver', 'radiologias.ver', 'reportes.ver',
            ];

            DB::table('permissions')->insertOrIgnore(array_map(fn (string $code): array => [
                'name' => $code,
                'descripcion' => $code,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ], $codes));

            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $codes)
                ->pluck('id');

            DB::table('role_has_permissions')->insertOrIgnore(
                $permissionIds->map(fn (int $permissionId): array => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ])->all(),
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
