<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicalTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            ['clinicalid' => 1, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Ortodoncia', 'clinicalcourse' => 1],
            ['clinicalid' => 2, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Endodoncia', 'clinicalcourse' => 2],
            ['clinicalid' => 3, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Cirugía Oral', 'clinicalcourse' => 3],
            ['clinicalid' => 4, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Periodoncia', 'clinicalcourse' => 4],
            ['clinicalid' => 5, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Odontopediatría', 'clinicalcourse' => 5],
            ['clinicalid' => 6, 'clinicalmodule' => 'Odontología', 'clinicalspecialty' => 'Prótesis', 'clinicalcourse' => 6],
        ];
        foreach ($rows as $r) {
            DB::table('clinicaltable')->insert(array_merge($r, ['updatetime' => $now]));
        }
    }
}
