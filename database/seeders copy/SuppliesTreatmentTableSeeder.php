<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliesTreatmentTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            [1, 'Kit limpieza', 'Cepillo+pasta', 1],
            [2, 'Kit endodoncia', 'Limas+hipoclorito', 2],
            [3, 'Kit cirugía', 'Bisturí+sutura', 3],
            [4, 'Kit blanqueamiento', 'Gel+molde', 1],
            [5, 'Kit ortodoncia', 'Brackets+alambre', 1],
            [6, 'Kit radiografía', 'Película+revelador', 1],
            [7, 'Kit sellantes', 'Resina+ácido', 5],
            [8, 'Kit prótesis', 'Yeso+acrílico', 6],
            [9, 'Kit periodoncia', 'Curetas+irrigante', 4],
            [10, 'Kit odontopediatría', 'Fluor+instrumental', 5],
        ];
        foreach ($rows as $r) {
            DB::table('suppliestreatmenttable')->insert([
                'suppliestreatmentid'    => $r[0],
                'suppliestreatmentname'  => $r[1],
                'suppliesdesc'           => $r[2],
                'clinicalid'             => $r[3],
                'updatetime'             => $now,
            ]);
        }
    }
}
