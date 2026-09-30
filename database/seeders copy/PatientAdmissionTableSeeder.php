<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientAdmissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        $rows = [
            // [admisionid, patientid, studentid, studentspecialty, responsibleid, responsiblespecialty, motivo, diagnostico]
            [1, 1, 4, 1, 2, 1, 'Dolor molar', 'Caries'],
            [2, 2, 4, 2, 3, 2, 'Revisión general', 'Gingivitis'],
            [3, 3, 5, 2, 3, 2, 'Fractura pieza 11', 'Fractura'],
            [4, 4, 5, 3, 3, 3, 'Extracción muela', 'Tercer molar incluido'],
            [5, 5, 6, 4, 2, 1, 'Sangrado encías', 'Periodontitis'],
            [6, 6, 6, 5, 2, 1, 'Control niño', 'Caries infantil'],
            [7, 7, 4, 1, 2, 1, 'Alineación dientes', 'Maloclusión'],
            [8, 8, 5, 2, 3, 2, 'Endodoncia', 'Pulpitis'],
            [9, 9, 6, 4, 2, 1, 'Limpieza', 'Sarros'],
            [10, 10, 4, 1, 2, 1, 'Prótesis', 'Edentulismo parcial'],
        ];

        foreach ($rows as $r) {
            DB::table('patientadmissiontable')->insert([
                'patientadmissionid'    => $r[0],
                'patientid'             => $r[1],
                'patientcivilstatus'    => 'Soltero',
                'patientschool'         => 'UMSA',
                'patientoccupation'     => 'Estudiante',
                'patientdirection'      => 'Zona Central #' . $r[0],
                'patientemergency'      => 'Familiar 777000' . $r[0],
                'patientphone'          => 70000000 + $r[0],
                'patientmotconsult'     => $r[6],
                'patientdiseasehistory' => 'Ninguno',
                'patientgmh'            => '',
                'patientgpe'            => '',
                'patientvitalsigns'     => 'PA 120/80',
                'patientee'             => '',
                'patientcn'             => '',
                'patientodontogram'     => '',
                'patientodontopaint'    => '',
                'patientatm'            => '',
                'patientbm'             => '',
                'patientgm'             => '',
                'patientcalculation'    => '',
                'patientocclusion'      => 'Angle I',
                'patientdentalpiece'    => '',
                'patientdiagnosis'      => $r[7],
                'patientexam'           => '',
                'patientfirm'           => '',
                'studentid'             => $r[2],
                'studentspecialty'      => $r[3],
                'responsibleid'         => $r[4],
                'responsiblespecialty'  => $r[5],
                'updatetime'            => $now,
            ]);
        }
    }
}
