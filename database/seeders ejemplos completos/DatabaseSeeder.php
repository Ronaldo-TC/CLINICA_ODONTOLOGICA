<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Catálogos base (sin dependencias)
            RolesSeeder::class,
            PermissionsSeeder::class,
            ClinicasSeeder::class,
            MotivosConsultaSeeder::class,
            PeriodosSeeder::class,

            // 2. Usuarios (dependen de roles)
            UsersSeeder::class,

            // 3. Asignaciones (dependen de roles, permisos, usuarios, clínicas)
            RoleHasPermissionsSeeder::class,
            FlujoClinicoPermissionsSeeder::class,
            SolicitudesInsumosPermissionsSeeder::class,
            EsterilizacionPermissionsSeeder::class,
            EspecialidadesSeeder::class,
            InsumosTratamientoSeeder::class,
            TratamientosSeeder::class,
            InsumosSeeder::class,
            PacientesDerivadosSeeder::class,

            CondicionesOdontologicasSeeder::class,
        ]);
    }
}
