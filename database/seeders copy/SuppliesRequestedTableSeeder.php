<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliesRequestedTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        $rows = [
            // [id, piece, supplies, teacherid, authorizedsupplies, suppliestreatmentid, clinicalid, remissionid, userid]
            [1, 11, 'Guantes', 2, true, 1, 1, 1, 4],
            [2, 12, 'Anestesia', 3, true, 2, 2, 2, 4],
            [3, 13, 'Sutura', 3, false, 3, 3, 3, 5],
            [4, 21, 'Mascarillas', 2, true, 1, 1, 4, 6],
            [5, 22, 'Gasas', 3, true, 2, 2, 5, 6],
            [6, 23, 'Jeringas', 3, false, 3, 3, 6, 4],
            [7, 24, 'Resina', 2, true, 7, 5, 7, 5],
            [8, 25, 'Yeso', 3, true, 8, 6, 8, 6],
            [9, 26, 'Curetas', 2, true, 9, 4, 9, 4],
            [10, 27, 'Flúor', 2, false, 10, 5, 10, 5],
        ];

        foreach ($rows as $r) {
            DB::table('suppliesrequestedtable')->insert([
                'suppliesrequestedid' => $r[0],
                'piece'               => $r[1],
                'supplies'            => $r[2],
                'authorizedteacher'   => $r[3],
                'teacherid'           => $r[3],
                'authorizedsupplies'  => $r[4],
                'suppliestreatmentid' => $r[5],
                'clinicalid'          => $r[6],
                'remissionid'         => $r[7],
                'userid'              => $r[8],
                'updatetime'          => $now,
            ]);
        }
    }
}
