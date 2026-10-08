<?php

namespace Database\Seeders;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\Especialidad;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OdontogramaDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Usuario (estudiante) ───────────────────────
        $usuario = Usuario::firstOrCreate(
            ['nombre_usuario' => 'demo_estudiante'],
            [
                'ci' => '99999999',
                'nombre_completo' => 'Estudiante Demo',
                'tipo' => 'estudiante',
                'contrasena' => Hash::make('demo123'),
                'habilitado' => true,
            ]
        );

        // ─── 2. Paciente ───────────────────────────────────
        $paciente = Paciente::firstOrCreate(
            ['ci' => 'ODONTO-DEMO-3D'],
            [
                'nombre' => 'Juan',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Gómez',
                'genero' => 'M',
                'fecha_nacimiento' => '1990-05-15',
            ]
        );

        // ─── 3. Clínica ────────────────────────────────────
        $clinica = Clinica::firstOrCreate(
            ['modulo' => 'Demo', 'especialidad' => 'Odontología General'],
            ['curso' => 1]
        );

        // ─── 4. Especialidad (pivote usuario↔clinica) ──────
        Especialidad::firstOrCreate(
            ['usuario_id' => $usuario->id, 'clinica_id' => $clinica->id],
            ['habilitado' => true, 'rotacion' => 1]
        );

        // ─── 5. Admisión (si no existe, la crea) ───────────
        $admision = Admision::firstOrCreate(
            [
                'paciente_id' => $paciente->id,
                'estudiante_id' => $usuario->id,
                'especialidad_estudiante' => $clinica->id,
            ],
            [
                'estudiante_id' => $usuario->id,
                'especialidad_estudiante' => $clinica->id,
                'responsable_id' => $usuario->id,
                'especialidad_responsable' => $clinica->id,
                'estado_civil' => 'soltero',
                'telefono' => 0,
            ]
        );

        $this->command->info("✅ Admisión lista: #{$admision->id}");

        // ─── 7. Layout 3D ──────────────────────────────────
        $layout3d = [
            'camera' => [0, 10, 22],
            'target' => [0, 0, 1.2],
            'lights' => [
                'ambient' => ['color' => '#ffffff', 'intensity' => 0.6],
                'directional' => [
                    'color' => '#ffffff',
                    'intensity' => 1.0,
                    'position' => ['x' => 5, 'y' => 10, 'z' => 7.5],
                ],
            ],
            'renderer' => ['antialias' => true, 'background' => '#0a0a0a'],
            'zoom' => 1.0,
            'mesh_map' => [
                'arcada_superior' => 'models/arcada_superior.glb',
                'arcada_inferior' => 'models/arcada_inferior.glb',
                'diente_base' => 'models/diente_base.glb',
            ],
        ];

        // ─── 8. Crear odontograma ──────────────────────────
        $odontograma = Odontograma::updateOrCreate([
            'admision_id' => $admision->id,
            'paciente_id' => $paciente->id,
            'version' => 1,
        ], [
            'historia_clinica_id' => null,
            'tipo' => 'inicial',
            'estado' => 'finalizado',
            'layout_3d' => $layout3d,
            'usuario_id' => $usuario->id,
        ]);

        // ─── 9. Hallazgos ──────────────────────────────────
        $odontograma->hallazgos()->delete();

        $hallazgos = [
            ['16', 'O',   'caries',     'moderada', null, null, 'Caries oclusal profunda'],
            ['16', 'M',   'caries',     'leve',     null, null, 'Extensión mesial'],
            ['17', 'MOD', 'obturacion', 'leve',     null, null, 'Obturación de resina previa'],
            ['18', '',    'ausente',    'severa',   null, null, 'Extraído hace 2 años'],
            ['26', 'O',   'caries',     'severa',   null, null, 'Requiere endodoncia'],
            ['26', 'V',   'mancha',     'leve',     null, null, 'Mancha blanca'],
            ['36', 'O',   'obturacion', 'leve',     null, null, 'Obturación amalgama'],
            ['37', '',    'endodoncia', 'severa',   null, null, 'Endodoncia realizada'],
            ['37', '',    'corona',     'severa',   null, null, 'Corona metal-cerámica'],
            ['38', '',    'ausente',    'severa',   null, null, 'Extraído'],
            ['46', 'O',   'caries',     'moderada', null, null, 'Caries oclusal'],
            ['47', 'M',   'caries',     'leve',     null, null, 'Caries mesial incipiente'],
            ['31', '',    'movilidad',  'moderada', 2,    4.5,  'Movilidad grado 2'],
            ['41', '',    'movilidad',  'leve',     1,    3.2,  'Movilidad grado 1'],
            ['11', 'V',   'fractura',   'severa',   null, null, 'Fractura de esmalte'],
            ['21', 'M',   'sellante',   'leve',     null, null, 'Sellante preventivo'],
            ['24', '',    'implante',   'severa',   null, null, 'Implante oseointegrado'],
        ];

        foreach ($hallazgos as [$pieza, $caras, $condicion, $severidad, $movilidad, $sondaje, $notas]) {
            $color = match ($condicion) {
                'caries' => '#FF0000',
                'obturacion' => '#0066FF',
                'ausente' => '#808080',
                'corona' => '#FFD700',
                'endodoncia' => '#8B00FF',
                'implante' => '#00CED1',
                'sellante' => '#32CD32',
                'fractura' => '#FF4500',
                'movilidad' => '#FFA500',
                'mancha' => '#D2B48C',
                default => '#FFFFFF',
            };

            $odontograma->hallazgos()->create([
                'pieza_dental' => $pieza,
                'caras' => $caras,
                'condicion' => $condicion,
                'severidad' => $severidad,
                'color' => $color,
                'movilidad' => $movilidad,
                'sondaje_mm' => $sondaje,
                'notas' => $notas,
                'created_by' => $usuario->id,
                'updated_by' => $usuario->id,
            ]);
        }

        $this->command->info("✅ Odontograma demo #{$odontograma->id} creado.");
        $this->command->info("   → {$odontograma->hallazgos()->count()} hallazgos generados.");
    }
}
