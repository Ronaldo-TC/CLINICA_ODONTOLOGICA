<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtyTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        // userid, clinicalid
        $rows = [
            // Admin: todas
            [1, 1, true, true],
            [1, 2, true, true],
            [1, 3, true, true],
            [1, 4, true, true],
            [1, 5, true, true],
            [1, 6, true, true],
            // Docentes
            [2, 1, true, true],
            [2, 2, true, true],
            [3, 2, true, true],
            [3, 3, true, true],
            // Estudiantes
            [4, 1, true, false],
            [4, 2, true, false],
            [5, 2, true, false],
            [5, 3, true, false],
            [6, 4, true, false],
            [6, 5, true, false],
        ];

        foreach ($rows as [$userid, $clinicalid, $responsible]) {
            DB::table('specialtytable')->insert([
                'userid'               => $userid,
                'clinicalid'           => $clinicalid,
                'enabled'              => true,
                'rotate'               => -1,
                'responsibleadmission' => $responsible,
                'stdatetime'           => $now,
                'updatetime'           => $now,
            ]);
        }
    }
}
