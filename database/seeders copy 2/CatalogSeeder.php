<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['nombre' => 'admin', 'descripcion' => 'Acceso total'],
            ['nombre' => 'subadmin', 'descripcion' => 'Subadministrador'],
            ['nombre' => 'director', 'descripcion' => 'Director de Carrera'],
            ['nombre' => 'jefatura-clinicas', 'descripcion' => 'Jefatura de Clínicas'],
            ['nombre' => 'jefe-catedra', 'descripcion' => 'Jefe de Cátedra'],
            ['nombre' => 'docente', 'descripcion' => 'Docente'],
            ['nombre' => 'estudiante', 'descripcion' => 'Estudiante'],
            ['nombre' => 'interno', 'descripcion' => 'Interno de Odontología'],
            ['nombre' => 'cajas', 'descripcion' => 'Cajero'],
            ['nombre' => 'esterilizacion', 'descripcion' => 'Esterilización'],
            ['nombre' => 'insumos', 'descripcion' => 'Insumos'],
            ['nombre' => 'radiologia', 'descripcion' => 'Radiología'],
            ['nombre' => 'investigacion', 'descripcion' => 'Instituto de Investigación'],
        ];

        DB::table('roles')->insert(array_map(function (array $role) use ($now): array {
            return $role + ['habilitado' => true, 'created_at' => $now, 'updated_at' => $now];
        }, $roles));

        $permissions = [
            ['codigo' => 'usuarios.ver', 'descripcion' => 'Consultar usuarios', 'modulo' => 'usuarios'],
            ['codigo' => 'usuarios.gestionar', 'descripcion' => 'Gestionar usuarios', 'modulo' => 'usuarios'],
            ['codigo' => 'clinica.gestionar', 'descripcion' => 'Gestionar clínicas', 'modulo' => 'clinica'],
            ['codigo' => 'pacientes.gestionar', 'descripcion' => 'Gestionar pacientes', 'modulo' => 'pacientes'],
            ['codigo' => 'admisiones.gestionar', 'descripcion' => 'Gestionar admisiones', 'modulo' => 'admisiones'],
            ['codigo' => 'tratamientos.gestionar', 'descripcion' => 'Gestionar tratamientos', 'modulo' => 'tratamientos'],
            ['codigo' => 'insumos.gestionar', 'descripcion' => 'Gestionar insumos', 'modulo' => 'insumos'],
            ['codigo' => 'cajas.gestionar', 'descripcion' => 'Gestionar caja', 'modulo' => 'cajas'],
            ['codigo' => 'esterilizacion.gestionar', 'descripcion' => 'Gestionar esterilización', 'modulo' => 'esterilizacion'],
            ['codigo' => 'radiologia.gestionar', 'descripcion' => 'Gestionar radiología', 'modulo' => 'radiologia'],
            ['codigo' => 'reportes.ver', 'descripcion' => 'Consultar reportes', 'modulo' => 'reportes'],
        ];

        DB::table('permisos')->insert(array_map(function (array $permission) use ($now): array {
            return $permission + ['created_at' => $now, 'updated_at' => $now];
        }, $permissions));

        DB::table('clinicas')->insert([
            ['modulo' => 'Clínica Integral', 'especialidad' => 'Odontología General', 'curso' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['modulo' => 'Clínica de Especialidades', 'especialidad' => 'Rehabilitación Oral', 'curso' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['modulo' => 'Clínica de Especialidades', 'especialidad' => 'Cirugía Bucal', 'curso' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('periodos')->insert([
            'nombre' => 'Gestión 2026 - Semestre 1',
            'gestion' => 2026,
            'semestre' => 1,
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-06-30',
            'habilitado' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
