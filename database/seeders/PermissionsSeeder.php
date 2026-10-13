<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * PermissionsSeeder
 *
 * Crea permisos en formato `modulo.accion` según el PDF.
 * Alineado con la tabla `permissions` del BACKUP_LG.SQL:
 *   - name (unique con guard_name)
 *   - descripcion
 *   - guard_name
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $modulos = [
            'usuarios' => 'Gestión de cuentas',
            'roles' => 'Roles y permisos',
            'pacientes' => 'Datos de pacientes',
            'clinicas' => 'Especialidades',
            'periodos' => 'Periodos académicos',
            'tratamientos' => 'Catálogo de tratamientos',
            'admisiones' => 'Registro inicial',
            'remisiones' => 'Derivaciones entre clínicas',
            'historias' => 'Historias clínicas',
            'sesiones' => 'Avance de tratamientos',
            'cajas' => 'Pagos y recibos',
            'insumos' => 'Inventario de insumos',
            'solicitudes-insumos' => 'Solicitudes clínicas de insumos',
            'esterilizaciones' => 'Control de esterilización',
            'radiologias' => 'Radiografías',
            'bitacoras' => 'Auditoría',
            'reportes' => 'Estadísticas y reportes',
            'investigacion' => 'Datos anonimizados',
        ];

        $acciones = [
            'ver' => 'Visualizar',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar/Deshabilitar',
            'gestionar' => 'Gestión completa',
        ];

        $rows = [];
        foreach ($modulos as $modulo => $descripcionModulo) {
            foreach ($acciones as $accion => $descripcionAccion) {
                $rows[] = [
                    'name' => "{$modulo}.{$accion}",
                    'descripcion' => "{$descripcionAccion} — {$descripcionModulo}",
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('permissions')->insertOrIgnore($rows);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
