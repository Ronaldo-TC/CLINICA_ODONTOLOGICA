<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            PermissionsSeeder::class,
            ClinicasSeeder::class,
            MotivosConsultaSeeder::class,
            PeriodosSeeder::class,
        ]);
    }
}
