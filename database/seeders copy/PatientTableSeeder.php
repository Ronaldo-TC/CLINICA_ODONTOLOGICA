<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            ['patientid' => 1, 'patientci' => '9001', 'patientname' => 'José', 'patientfatherlastname' => 'Ramírez', 'patientmotherlastname' => 'Soto', 'patientgender' => 'M', 'patientbirthdate' => 631152000],
            ['patientid' => 2, 'patientci' => '9002', 'patientname' => 'Lucía', 'patientfatherlastname' => 'Fernández', 'patientmotherlastname' => 'Rojas', 'patientgender' => 'F', 'patientbirthdate' => 694224000],
            ['patientid' => 3, 'patientci' => '9003', 'patientname' => 'Diego', 'patientfatherlastname' => 'Morales', 'patientmotherlastname' => 'Pinto', 'patientgender' => 'M', 'patientbirthdate' => 757382400],
            ['patientid' => 4, 'patientci' => '9004', 'patientname' => 'Carla', 'patientfatherlastname' => 'Vargas', 'patientmotherlastname' => 'Luna', 'patientgender' => 'F', 'patientbirthdate' => 820454400],
            ['patientid' => 5, 'patientci' => '9005', 'patientname' => 'Mario', 'patientfatherlastname' => 'Chávez', 'patientmotherlastname' => 'Nina', 'patientgender' => 'M', 'patientbirthdate' => 883612800],
            ['patientid' => 6, 'patientci' => '9006', 'patientname' => 'Elena', 'patientfatherlastname' => 'Cruz', 'patientmotherlastname' => 'Apaza', 'patientgender' => 'F', 'patientbirthdate' => 946684800],
            ['patientid' => 7, 'patientci' => '9007', 'patientname' => 'Raúl', 'patientfatherlastname' => 'Mamani', 'patientmotherlastname' => 'Choque', 'patientgender' => 'M', 'patientbirthdate' => 1009843200],
            ['patientid' => 8, 'patientci' => '9008', 'patientname' => 'Paola', 'patientfatherlastname' => 'Gutiérrez', 'patientmotherlastname' => 'Ríos', 'patientgender' => 'F', 'patientbirthdate' => 1072915200],
            ['patientid' => 9, 'patientci' => '9009', 'patientname' => 'Iván', 'patientfatherlastname' => 'Salazar', 'patientmotherlastname' => 'Peña', 'patientgender' => 'M', 'patientbirthdate' => 1136073600],
            ['patientid' => 10, 'patientci' => '9010', 'patientname' => 'Nadia', 'patientfatherlastname' => 'Rojas', 'patientmotherlastname' => 'Vega', 'patientgender' => 'F', 'patientbirthdate' => 1199145600],
        ];
        foreach ($rows as $r) {
            DB::table('patienttable')->insert(array_merge($r, ['updatetime' => $now]));
        }
    }
}
