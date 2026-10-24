<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * EspecialidadesSeeder
 *
 * Asigna usuarios (docentes, estudiantes, etc.) a clínicas.
 * Alineado con la tabla `especialidades` (PK compuesta: usuario_id + clinica_id).
 */
class EspecialidadesSeeder extends Seeder
{
    public function run(): void
    {
        $now      = now();
        $clinicas = DB::table('clinicas')->pluck('id', 'especialidad');
        $usuarios = DB::table('usuarios')->pluck('id', 'nombre_usuario');

        $asignaciones = [
            // [usuario, especialidad, responsable_admision]
            ['jefe-catedra',   'Clínica de Admisión', true],
            ['docente',        'Clínica de Admisión', true],
            ['docente',        'Operatoria Dental',   false],
            ['docente',        'Endodoncia',          false],
            ['estudiante',     'Operatoria Dental',   false],
            ['estudiante',     'Endodoncia',          false],
            ['interno',        'Cirugía Bucal',       false],
            ['admision',       'Clínica de Admisión', true],
            ['radiologia',     'Radiología',          false],
            ['esterilizacion', 'Esterilización',      false],
        ];

        $rows = [];
        foreach ($asignaciones as [$usuarioNombre, $especialidad, $responsable]) {
            if (! isset($usuarios[$usuarioNombre], $clinicas[$especialidad])) {
                continue;
            }

            $rows[] = [
                'usuario_id'           => $usuarios[$usuarioNombre],
                'clinica_id'           => $clinicas[$especialidad],
                'habilitado'           => 1,
                'rotacion'             => $responsable ? 0 : 1,
                'responsable_admision' => $responsable ? 1 : 0,
                'fecha_inicio'         => '2026-01-05',
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        if ($rows) {
            DB::table('especialidades')->insertOrIgnore($rows);
        }
    }
}
