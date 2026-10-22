<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RolesSeeder
 *
 * Crea los 15 roles del catálogo estático según el PDF (págs. 6-9).
 * Alineado con la tabla `roles` del BACKUP_LG.SQL:
 *   - name (unique con guard_name)
 *   - descripcion
 *   - guard_name (por defecto "web" para compatibilidad con Sanctum)
 *   - habilitado
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['name' => 'admin',             'descripcion' => 'Administrador del sistema (U-TIC)'],
            ['name' => 'subadmin',          'descripcion' => 'Subadministrador'],
            ['name' => 'director',          'descripcion' => 'Director de Carrera'],
            ['name' => 'jefatura-clinicas', 'descripcion' => 'Jefe de Área Clínica'],
            ['name' => 'jefe-catedra',      'descripcion' => 'Jefe de Cátedra'],
            ['name' => 'docente',           'descripcion' => 'Docente de asignaturas clínicas'],
            ['name' => 'estudiante',        'descripcion' => 'Estudiante de 3° a 5° año'],
            ['name' => 'interno',           'descripcion' => 'Interno de Odontología'],
            ['name' => 'admision',          'descripcion' => 'Administrativo de Admisión'],
            ['name' => 'kardex',            'descripcion' => 'Administrativo de Kardex'],
            ['name' => 'cajas',             'descripcion' => 'Administrativo de Cajas'],
            ['name' => 'insumos',           'descripcion' => 'Administrativo de Insumos'],
            ['name' => 'esterilizacion',    'descripcion' => 'Administrativo de Esterilización'],
            ['name' => 'radiologia',        'descripcion' => 'Administrativo de Radiología'],
            ['name' => 'investigacion',     'descripcion' => 'Instituto de Investigación'],
        ];

        $rows = array_map(
            fn(array $r): array => [
                'name'        => $r['name'],
                'descripcion' => $r['descripcion'],
                'guard_name'  => 'web',
                'habilitado'  => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            $roles
        );

        DB::table('roles')->insertOrIgnore($rows);
    }
}
