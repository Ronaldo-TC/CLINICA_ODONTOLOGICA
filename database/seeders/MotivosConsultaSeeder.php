<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MotivosConsultaSeeder
 *
 * Motivos de consulta para reportes del PDF pág. 6.
 * Alineado con la tabla `motivos_consulta` del BACKUP_LG.SQL.
 */
class MotivosConsultaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $motivos = [
            ['nombre' => 'Dolor dental',             'descripcion' => 'Odontalgia aguda o crónica'],
            ['nombre' => 'Control rutinario',        'descripcion' => 'Chequeo periódico'],
            ['nombre' => 'Limpieza dental',          'descripcion' => 'Profilaxis'],
            ['nombre' => 'Caries',                   'descripcion' => 'Lesión de caries dental'],
            ['nombre' => 'Fractura dental',          'descripcion' => 'Traumatismo dental'],
            ['nombre' => 'Extracción',               'descripcion' => 'Exodoncia indicada'],
            ['nombre' => 'Ortodoncia',               'descripcion' => 'Evaluación ortodóncica'],
            ['nombre' => 'Prótesis',                 'descripcion' => 'Rehabilitación protésica'],
            ['nombre' => 'Enfermedad periodontal',   'descripcion' => 'Gingivitis o periodontitis'],
            ['nombre' => 'Urgencia',                 'descripcion' => 'Atención de urgencia'],
        ];

        $rows = array_map(
            fn(array $m): array => $m + [
                'habilitado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $motivos
        );

        DB::table('motivos_consulta')->insert($rows);
    }
}
