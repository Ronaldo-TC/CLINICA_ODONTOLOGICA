<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ClinicasSeeder
 *
 * Catálogo de áreas clínicas y módulos de atención.
 * Alineado con la tabla `clinicas` del BACKUP_LG.SQL.
 */
class ClinicasSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $clinicas = [
            ['modulo' => 'Admisión',                    'especialidad' => 'Clínica de Admisión',    'curso' => 1],
            ['modulo' => 'Clínicas Básicas',            'especialidad' => 'Operatoria Dental',      'curso' => 2],
            ['modulo' => 'Clínicas Básicas',            'especialidad' => 'Endodoncia',             'curso' => 3],
            ['modulo' => 'Clínicas Básicas',            'especialidad' => 'Periodoncia',            'curso' => 3],
            ['modulo' => 'Clínicas Quirúrgicas',        'especialidad' => 'Cirugía Bucal',          'curso' => 4],
            ['modulo' => 'Clínicas Quirúrgicas',        'especialidad' => 'Cirugía Mayor',          'curso' => 5],
            ['modulo' => 'Clínicas Integrales',         'especialidad' => 'Integral Niños',         'curso' => 4],
            ['modulo' => 'Clínicas Integrales',         'especialidad' => 'Integral Adultos',       'curso' => 4],
            ['modulo' => 'Clínicas Integrales',         'especialidad' => 'Odontopediatría',        'curso' => 4],
            ['modulo' => 'Clínicas de Rehabilitación',  'especialidad' => 'Prostodoncia Fija',      'curso' => 5],
            ['modulo' => 'Clínicas de Rehabilitación',  'especialidad' => 'Prostodoncia Removible', 'curso' => 5],
            ['modulo' => 'Clínicas de Rehabilitación',  'especialidad' => 'Ortodoncia',             'curso' => 4],
            ['modulo' => 'Servicios de Apoyo',          'especialidad' => 'Radiología',             'curso' => 0],
            ['modulo' => 'Servicios de Apoyo',          'especialidad' => 'Esterilización',         'curso' => 0],
            ['modulo' => 'Admisión',                    'especialidad' => 'Historia Clínica',       'curso' => 1],
            ['modulo' => 'Clínicas Quirúrgicas',        'especialidad' => 'Quirófano',               'curso' => 5],
        ];

        foreach ($clinicas as $clinica) {
            if (! DB::table('clinicas')->where('especialidad', $clinica['especialidad'])->exists()) {
                DB::table('clinicas')->insert($clinica + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
