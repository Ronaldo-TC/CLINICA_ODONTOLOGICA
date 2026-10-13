<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RoleHasPermissionsSeeder
 *
 * Matriz de permisos por rol, según el PDF de roles.
 * Alineado con la tabla `role_has_permissions` de Spatie.
 */
class RoleHasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $permisos = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        // Admin: TODOS los permisos
        if (isset($roles['admin'])) {
            $rows = $permisos->map(fn ($pid) => [
                'role_id' => $roles['admin'],
                'permission_id' => $pid,
            ])->values()->all();

            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }

        // Matriz del resto de roles
        $matriz = [
            'subadmin' => [
                'usuarios.ver',
                'usuarios.gestionar',
                'roles.ver',
                'pacientes.gestionar',
                'clinicas.gestionar',
                'periodos.ver',
                'tratamientos.gestionar',
                'reportes.ver',
            ],
            'director' => [
                'pacientes.ver',
                'clinicas.ver',
                'periodos.ver',
                'tratamientos.ver',
                'historias.ver',
                'cajas.ver',
                'insumos.ver',
                'esterilizaciones.ver',
                'radiologias.ver',
                'bitacoras.ver',
                'reportes.ver',
                'investigacion.ver',
            ],
            'jefatura-clinicas' => [
                'clinicas.gestionar',
                'pacientes.gestionar',
                'tratamientos.gestionar',
                'historias.ver',
                'reportes.ver',
            ],
            'jefe-catedra' => [
                'historias.ver',
                'reportes.ver',
            ],
            'docente' => [
                'pacientes.ver',
                'historias.ver',
                'historias.gestionar',
                'sesiones.ver',
                'sesiones.gestionar',
                'tratamientos.ver',
                'solicitudes-insumos.ver',
                'esterilizaciones.ver',
                'esterilizaciones.gestionar',
                'radiologias.ver',
                'reportes.ver',
            ],
            'estudiante' => [
                'pacientes.ver',
                'historias.ver',
                'historias.crear',
                'historias.editar',
                'sesiones.ver',
                'sesiones.crear',
                'solicitudes-insumos.ver',
                'solicitudes-insumos.crear',
                'esterilizaciones.ver',
                'esterilizaciones.crear',
                'radiologias.ver',
                'radiologias.crear',
            ],
            'interno' => [
                'pacientes.ver',
                'historias.ver',
                'historias.crear',
                'historias.editar',
                'sesiones.ver',
                'sesiones.crear',
                'solicitudes-insumos.ver',
                'solicitudes-insumos.crear',
                'esterilizaciones.ver',
                'esterilizaciones.crear',
                'radiologias.ver',
                'radiologias.crear',
            ],
            'admision' => [
                'pacientes.ver',
                'pacientes.crear',
                'pacientes.editar',
                'admisiones.ver',
                'admisiones.crear',
                'admisiones.editar',
                'remisiones.ver',
                'remisiones.crear',
                'historias.ver',
            ],
            'kardex' => [
                'pacientes.ver',
                'admisiones.ver',
                'historias.ver',
            ],
            'cajas' => [
                'pacientes.ver',
                'tratamientos.ver',
                'cajas.ver',
                'cajas.crear',
                'cajas.editar',
                'reportes.ver',
            ],
            'insumos' => [
                'insumos.ver',
                'insumos.gestionar',
                'reportes.ver',
            ],
            'esterilizacion' => [
                'esterilizaciones.ver',
                'esterilizaciones.gestionar',
                'reportes.ver',
            ],
            'radiologia' => [
                'pacientes.ver',
                'cajas.ver',
                'radiologias.ver',
                'radiologias.gestionar',
                'reportes.ver',
            ],
            'investigacion' => [
                'investigacion.ver',
                'reportes.ver',
            ],
        ];

        $pivote = [];
        foreach ($matriz as $rolNombre => $codigos) {
            if (! isset($roles[$rolNombre])) {
                continue;
            }

            $rolId = $roles[$rolNombre];
            foreach ($codigos as $codigo) {
                if (! isset($permisos[$codigo])) {
                    continue;
                }

                $pivote[] = [
                    'role_id' => $rolId,
                    'permission_id' => $permisos[$codigo],
                ];
            }
        }

        DB::table('role_has_permissions')->insertOrIgnore($pivote);
        $this->call(SolicitudesInsumosPermissionsSeeder::class);
    }
}
