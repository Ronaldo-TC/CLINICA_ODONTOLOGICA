<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorizationTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        // [sterilizationid, remissionid, status, desc, teacher, envelope]
        $rows = [
            [1, 1, true, 'Autorizado', 'Dr. Juan Pérez', 101],
            [2, 2, true, 'Autorizado', 'Dra. Carla Méndez', 102],
            [3, 3, false, 'Rechazado', 'Dra. Carla Méndez', 103],
            [4, 4, true, 'Autorizado', 'Dr. Juan Pérez', 104],
            [5, 5, true, 'Autorizado', 'Dr. Juan Pérez', 105],
            [6, 6, false, 'Rechazado', 'Dra. Carla Méndez', 106],
            [7, 7, true, 'Autorizado', 'Dr. Juan Pérez', 107],
            [8, 8, true, 'Autorizado', 'Dra. Carla Méndez', 108],
            [9, 9, true, 'Autorizado', 'Dr. Juan Pérez', 109],
            [10, 10, true, 'Autorizado', 'Dra. Carla Méndez', 110],
        ];

        foreach ($rows as $r) {
            DB::table('authorizationtable')->insert([
                'sterilizationid'        => $r[0],
                'remissionid'            => $r[1],
                'authorizationstatus'    => $r[2],
                'authorizationdesc'      => $r[3],
                'authorizationteacher'   => $r[4],
                'authorizationenvelope'  => $r[5],
                'updatetime'             => $now,
            ]);
        }
    }
}
