<?php

namespace Database\Seeders;

use App\Models\Paciente;
use Illuminate\Database\Seeder;

class PacientesDemoSeeder extends Seeder
{
    public const PACIENTES = [
        ['ci' => 'DEMO-CLINICA-001', 'nombre' => 'Ana', 'apellido_paterno' => 'Mamani', 'apellido_materno' => 'Quispe', 'genero' => 'F', 'fecha_nacimiento' => '1994-04-12'],
        ['ci' => 'DEMO-CLINICA-002', 'nombre' => 'Carlos', 'apellido_paterno' => 'Flores', 'apellido_materno' => 'Condori', 'genero' => 'M', 'fecha_nacimiento' => '1988-09-20'],
        ['ci' => 'DEMO-CLINICA-003', 'nombre' => 'María', 'apellido_paterno' => 'Choque', 'apellido_materno' => 'Vargas', 'genero' => 'F', 'fecha_nacimiento' => '1975-02-08'],
        ['ci' => 'DEMO-CLINICA-004', 'nombre' => 'Sofía', 'apellido_paterno' => 'Rivera', 'apellido_materno' => 'Huanca', 'genero' => 'F', 'fecha_nacimiento' => '2018-06-15'],
        ['ci' => 'DEMO-CLINICA-005', 'nombre' => 'Diego', 'apellido_paterno' => 'Apaza', 'apellido_materno' => 'López', 'genero' => 'M', 'fecha_nacimiento' => '2014-03-10'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(string $prefijo = 'DEMO-CLINICA-'): void
    {
        foreach (self::PACIENTES as $indice => $datos) {
            $ci = $prefijo.str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT);
            unset($datos['ci']);
            Paciente::firstOrCreate(['ci' => $ci], $datos);
        }
    }
}
