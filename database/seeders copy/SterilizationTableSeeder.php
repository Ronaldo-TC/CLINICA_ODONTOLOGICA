<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SterilizationTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        $rows = [
            // [id, envelope, package, studentid, clinicalid, userid(encargado esteril), info]
            [1, 101, 201, 4, 1, 10, 'Paquete A'],
            [2, 102, 202, 4, 2, 10, 'Paquete B'],
            [3, 103, 203, 5, 3, 10, 'Paquete C'],
            [4, 104, 204, 5, 3, 11, 'Paquete D'],
            [5, 105, 205, 6, 4, 11, 'Paquete E'],
            [6, 106, 206, 6, 5, 10, 'Paquete F'],
            [7, 107, 207, 4, 1, 10, 'Paquete G'],
            [8, 108, 208, 4, 2, 11, 'Paquete H'],
            [9, 109, 209, 5, 3, 10, 'Paquete I'],
            [10, 110, 210, 6, 5, 11, 'Paquete J'],
        ];

        foreach ($rows as $r) {
            DB::table('sterilizationtable')->insert([
                'sterilizationid'       => $r[0],
                'sterilizationenvelope' => $r[1],
                'sterilizationpackage'  => $r[2],
                'sterilizationenabled'  => true,
                'sterilizationinfo'     => $r[6],
                'sterilizationdesc'     => 'Esterilizado',
                'deliverydatetime'      => $now,
                'pickupdatetime'        => $now,
                'studentid'             => $r[3],
                'clinicalid'            => $r[4],
                'userid'                => $r[5],
                'updatetime'            => $now,
            ]);
        }
    }
}
