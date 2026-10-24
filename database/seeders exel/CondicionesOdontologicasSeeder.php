<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CondicionesOdontologicasSeeder extends Seeder
{
    public function run(): void
    {
        $condiciones = [
            // codigo, nombre,               color,       mesh_3d,          icono
            ['CAR',  'Caries',               '#FF0000',   'mesh_caries',    'caries.png'],
            ['OBT',  'Obturación',           '#0066FF',   'mesh_obtura',    'obturacion.png'],
            ['AUS',  'Ausente',              '#808080',   'mesh_ausente',   'ausente.png'],
            ['COR',  'Corona',               '#FFD700',   'mesh_corona',    'corona.png'],
            ['END',  'Endodoncia',           '#8B00FF',   'mesh_endodoncia', 'endodoncia.png'],
            ['IMP',  'Implante',             '#00CED1',   'mesh_implante',  'implante.png'],
            ['SEL',  'Sellante',             '#32CD32',   'mesh_sellante',  'sellante.png'],
            ['FRA',  'Fractura',             '#FF4500',   'mesh_fractura',  'fractura.png'],
            ['MOV',  'Movilidad',            '#FFA500',   'mesh_movilidad', 'movilidad.png'],
            ['BOL',  'Bolsa periodontal',    '#8B4513',   'mesh_bolsa',     'bolsa.png'],
            ['MAN',  'Mancha',               '#D2B48C',   'mesh_mancha',    'mancha.png'],
            ['DES',  'Desgaste',             '#A9A9A9',   'mesh_desgaste',  'desgaste.png'],
            ['GIR',  'Giroversión',          '#FF69B4',   'mesh_giro',      'giro.png'],
            ['DIA',  'Diastema',             '#20B2AA',   'mesh_diastema',  'diastema.png'],
            ['SUP',  'Supernumerario',       '#DC143C',   'mesh_super',     'super.png'],
        ];

        foreach ($condiciones as [$codigo, $nombre, $color, $mesh, $icono]) {
            DB::table('condiciones_odontologicas')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'color_hex' => $color,
                    'mesh_3d' => $mesh,
                    'icono' => $icono,
                    'habilitado' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
