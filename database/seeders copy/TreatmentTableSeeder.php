<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            [1, 'Limpieza dental', 150.00, '50', 'Profilaxis', 1],
            [2, 'Endodoncia', 500.00, '60', 'Tratamiento conducto', 2],
            [3, 'Extracción', 300.00, '70', 'Cirugía menor', 3],
            [4, 'Blanqueamiento', 400.00, '40', 'Estético', 1],
            [5, 'Ortodoncia mensual', 250.00, '30', 'Control brackets', 1],
            [6, 'Radiografía', 80.00, '0', 'Diagnóstico', 1],
            [7, 'Sellantes', 120.00, '50', 'Preventivo', 5],
            [8, 'Prótesis parcial', 800.00, '60', 'Rehabilitación', 6],
            [9, 'Periodoncia', 350.00, '55', 'Tratamiento encías', 4],
            [10, 'Pulpotomía', 200.00, '50', 'Odontopediatría', 5],
        ];
        foreach ($rows as $r) {
            DB::table('treatmenttable')->insert([
                'treatmentid'         => $r[0],
                'treatmentname'       => $r[1],
                'treatmentcost'       => $r[2],
                'treatmentpgpercent'  => $r[3],
                'treatmentdesc'       => $r[4],
                'clinicalid'          => $r[5],
                'updatetime'          => $now,
            ]);
        }
    }
}
