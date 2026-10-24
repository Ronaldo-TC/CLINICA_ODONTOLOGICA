<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Catálogos base (sin dependencias).
            RolesSeeder::class,
            PermissionsSeeder::class,
            MotivosConsultaSeeder::class,
            PeriodosSeeder::class,

            // 2. Datos institucionales importados desde database/excel.
            ExcelDataSeeder::class,

            // 3. Permisos asignados a los roles.
            RoleHasPermissionsSeeder::class,
            FlujoClinicoPermissionsSeeder::class,
            SolicitudesInsumosPermissionsSeeder::class,
            JefaturaClinicasPermissionsSeeder::class,
            EsterilizacionPermissionsSeeder::class,
            CondicionesOdontologicasSeeder::class,
        ]);
    }
}
