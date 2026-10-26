<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleHasPermissionsSeeder::class,
            FlujoClinicoPermissionsSeeder::class,
            EsterilizacionPermissionsSeeder::class,
            EspecialidadesSeeder::class,
            InsumosTratamientoSeeder::class,
            TratamientosSeeder::class,
            InsumosSeeder::class,
        ]);
    }
}
