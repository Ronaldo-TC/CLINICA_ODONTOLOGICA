<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            [1, 1, 1, 1],
            [2, 2, 2, 2],
            [3, 3, 3, 3],
            [4, 4, 4, 3],
            [5, 5, 5, 4],
            [6, 6, 6, 5],
            [7, 7, 7, 1],
            [8, 8, 8, 2],
            [9, 9, 9, 4],
            [10, 10, 10, 6],
        ];
        foreach ($rows as $r) {
            DB::table('remissiontable')->insert([
                'remissionid' => $r[0],
                'admissionid' => $r[1],
                'patientid'   => $r[2],
                'clinicalid'  => $r[3],
                'enabled'     => true,
                'updatetime'  => $now,
            ]);
        }
    }
}
