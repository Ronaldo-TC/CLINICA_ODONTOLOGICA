<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;
        $rows = [
            [1, 'Guantes', 100, 'unidades', 1, 1],
            [2, 'Anestesia', 50, 'ampollas', 2, 2],
            [3, 'Sutura', 30, 'rollos', 3, 3],
            [4, 'Mascarillas', 200, 'unidades', 1, 1],
            [5, 'Gasas', 300, 'paquetes', 2, 2],
            [6, 'Jeringas', 150, 'unidades', 3, 3],
            [7, 'Resina', 40, 'tubos', 7, 5],
            [8, 'Yeso', 25, 'bolsas', 8, 6],
            [9, 'Curetas', 20, 'unidades', 9, 4],
            [10, 'Flúor', 60, 'frascos', 10, 5],
        ];
        foreach ($rows as $r) {
            DB::table('suppliestable')->insert([
                'suppliesid'             => $r[0],
                'suppliesname'           => $r[1],
                'suppliesquantitynumber' => $r[2],
                'suppliesquantity'       => $r[3],
                'suppliestreatmentid'    => $r[4],
                'clinicalid'             => $r[5],
                'updatetime'             => $now,
            ]);
        }
    }
}
