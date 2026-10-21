<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PeriodosSeeder
 *
 * Crea el periodo académico anual activo 2026.
 * Alineado con la tabla `periodos` del BACKUP_LG.SQL.
 */
class PeriodosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('periodos')->insertOrIgnore([
            [
                'nombre' => 'Gestión 2026',
                'gestion' => 2026,
                'fecha_inicio' => '2026-01-05',
                'fecha_fin' => '2026-12-31',
                'habilitado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
