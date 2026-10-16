<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TratamientosSeeder
 *
 * Catálogo de tratamientos por clínica.
 * Alineado con la tabla `tratamientos` del BACKUP_LG.SQL.
 */
class TratamientosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $clinicas = DB::table('clinicas')->pluck('id', 'especialidad');

        $catalogo = [
            'Operatoria Dental' => [
                ['nombre' => 'Resina simple',            'costo' => 80.00],
                ['nombre' => 'Resina compuesta',         'costo' => 120.00],
                ['nombre' => 'Sellante de fosas',        'costo' => 60.00],
            ],
            'Endodoncia' => [
                ['nombre' => 'Endodoncia unirradicular',  'costo' => 250.00],
                ['nombre' => 'Endodoncia birradicular',   'costo' => 320.00],
                ['nombre' => 'Endodoncia multirradicular', 'costo' => 400.00],
            ],
            'Cirugía Bucal' => [
                ['nombre' => 'Exodoncia simple',         'costo' => 150.00],
                ['nombre' => 'Exodoncia quirúrgica',     'costo' => 300.00],
                ['nombre' => 'Cirugía de tercer molar',  'costo' => 500.00],
            ],
            'Periodoncia' => [
                ['nombre' => 'Detartraje',               'costo' => 120.00],
                ['nombre' => 'Curetaje',                 'costo' => 200.00],
            ],
            'Radiología' => [
                ['nombre' => 'Radiografía periapical',   'costo' => 30.00],
                ['nombre' => 'Radiografía panorámica',   'costo' => 80.00],
            ],
        ];

        $rows = [];
        foreach ($catalogo as $especialidad => $tratamientos) {
            if (! isset($clinicas[$especialidad])) {
                continue;
            }

            foreach ($tratamientos as $t) {
                $rows[] = [
                    'nombre' => $t['nombre'],
                    'costo' => $t['costo'],
                    'porcentaje_pg' => null,
                    'descripcion' => '',
                    'clinica_id' => $clinicas[$especialidad],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            if (! DB::table('tratamientos')->where('clinica_id', $row['clinica_id'])->where('nombre', $row['nombre'])->exists()) {
                DB::table('tratamientos')->insert($row);
            }
        }
    }
}
