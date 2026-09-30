<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RadiologyTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        // [radiologyid, desc, obs, state, userid(radiologo), boxid]
        $rows = [
            [1, 'Radiografía panorámica', 'Sin hallazgos', 'Completado', 14, 1],
            [2, 'Radiografía periapical', 'Caries profunda', 'Completado', 14, 2],
            [3, 'Tomografía', 'Fractura radicular', 'Pendiente', 15, 3],
            [4, 'Radiografía bitewing', 'Sin hallazgos', 'Completado', 15, 4],
            [5, 'Radiografía oclusal', 'Diente incluido', 'Completado', 14, 5],
            [6, 'Tomografía 3D', 'Evaluación implante', 'Pendiente', 15, 6],
            [7, 'Radiografía panorámica', 'Control ortodoncia', 'Completado', 14, 7],
            [8, 'Radiografía periapical', 'Endodoncia OK', 'Completado', 15, 8],
            [9, 'Radiografía bitewing', 'Caries incipiente', 'Completado', 14, 9],
            [10, 'Tomografía', 'Quiste maxilar', 'Pendiente', 15, 10],
        ];

        foreach ($rows as $r) {
            DB::table('radiologytable')->insert([
                'radiologyid'    => $r[0],
                'radiologydesc'  => $r[1],
                'radiologyobs'   => $r[2],
                'radiologystate' => $r[3],
                'userid'         => $r[4],
                'boxid'          => $r[5],
                'updatetime'     => $now,
            ]);
        }
    }
}
