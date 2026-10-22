<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * InsumosTratamientoSeeder
 *
 * Tipos de insumos por clínica.
 * Alineado con la tabla `insumos_tratamiento`.
 */
class InsumosTratamientoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $clinicas = DB::table('clinicas')->pluck('id', 'especialidad');

        $tipos = [
            'Operatoria Dental' => ['Resina compuesta', 'Anestésico', 'Aislamiento'],
            'Endodoncia' => ['Limas endodónticas', 'Hipoclorito', 'Conos de gutapercha'],
            'Cirugía Bucal' => ['Sutura', 'Anestésico', 'Gasa estéril'],
            'Periodoncia' => ['Curetas', 'Clorhexidina', 'Anestésico'],
            'Radiología' => ['Película radiográfica', 'Revelador', 'Fijador'],
        ];

        $rows = [];
        foreach ($tipos as $especialidad => $nombres) {
            if (! isset($clinicas[$especialidad])) {
                continue;
            }

            foreach ($nombres as $nombre) {
                $rows[] = [
                    'nombre' => $nombre,
                    'descripcion' => '',
                    'clinica_id' => $clinicas[$especialidad],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            if (! DB::table('insumos_tratamiento')->where('clinica_id', $row['clinica_id'])->where('nombre', $row['nombre'])->exists()) {
                DB::table('insumos_tratamiento')->insert($row);
            }
        }
    }
}
