<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $now          = now();
        $passwordHash = Hash::make('123');

        $users = [
            ['ci' => '10000001', 'nombre_usuario' => 'admin',             'nombre_completo' => 'Administrador del Sistema',       'tipo' => 'admin',          'rol' => 'admin'],
            ['ci' => '10000002', 'nombre_usuario' => 'subadmin',          'nombre_completo' => 'Subadministrador Académico',      'tipo' => 'subadmin',       'rol' => 'subadmin'],
            ['ci' => '10000003', 'nombre_usuario' => 'director',          'nombre_completo' => 'Dr. Rolando Néstor Soliz Arias',  'tipo' => 'director',       'rol' => 'director'],
            ['ci' => '10000004', 'nombre_usuario' => 'jefatura-clinicas', 'nombre_completo' => 'Dr. Edwing Max Mollericona Marin', 'tipo' => 'jefatura',      'rol' => 'jefatura-clinicas'],
            ['ci' => '10000005', 'nombre_usuario' => 'jefe-catedra',      'nombre_completo' => 'Dra. Ines Ponce Armaly',          'tipo' => 'jefatura',       'rol' => 'jefe-catedra'],
            ['ci' => '10000006', 'nombre_usuario' => 'docente',           'nombre_completo' => 'Docente de Odontología',          'tipo' => 'docente',        'rol' => 'docente'],
            ['ci' => '10000007', 'nombre_usuario' => 'estudiante',        'nombre_completo' => 'Estudiante de Odontología',       'tipo' => 'estudiante',     'rol' => 'estudiante'],
            ['ci' => '10000008', 'nombre_usuario' => 'interno',           'nombre_completo' => 'Interno de Odontología',          'tipo' => 'interno',        'rol' => 'interno'],
            ['ci' => '10000009', 'nombre_usuario' => 'admision',          'nombre_completo' => 'Administrativo de Admisión',      'tipo' => 'admision',       'rol' => 'admision'],
            ['ci' => '10000010', 'nombre_usuario' => 'kardex',            'nombre_completo' => 'Administrativo de Kardex',        'tipo' => 'kardex',         'rol' => 'kardex'],
            ['ci' => '10000011', 'nombre_usuario' => 'cajas',             'nombre_completo' => 'Cajero de Clínica',               'tipo' => 'cajas',          'rol' => 'cajas'],
            ['ci' => '10000012', 'nombre_usuario' => 'insumos',           'nombre_completo' => 'Responsable de Insumos',          'tipo' => 'insumos',        'rol' => 'insumos'],
            ['ci' => '10000013', 'nombre_usuario' => 'esterilizacion',    'nombre_completo' => 'Responsable de Esterilización',   'tipo' => 'esterilizacion', 'rol' => 'esterilizacion'],
            ['ci' => '10000014', 'nombre_usuario' => 'radiologia',        'nombre_completo' => 'Responsable de Radiología',       'tipo' => 'radiologia',     'rol' => 'radiologia'],
            ['ci' => '10000015', 'nombre_usuario' => 'investigacion',     'nombre_completo' => 'Instituto de Investigación',      'tipo' => 'investigacion',  'rol' => 'investigacion'],
        ];

        foreach ($users as $user) {
            // 1. Crear o recuperar el usuario
            $usuario = Usuario::firstOrCreate(
                ['nombre_usuario' => $user['nombre_usuario']],
                [
                    'ci'             => $user['ci'],
                    'nombre_completo' => $user['nombre_completo'],
                    'descripcion'    => $user['nombre_completo'],
                    'tipo'           => $user['tipo'],
                    'habilitado'     => 1,
                    'permitir_login' => 1,
                    'multilogin'     => $user['rol'] === 'admin' ? 1 : 0,
                    'contrasena'     => $passwordHash,
                    'sesion'         => '',
                    'sesion_extra'   => '',
                    'info'           => '',
                ]
            );

            // 2. Asignar rol con Spatie (usa el FQCN real del modelo automáticamente)
            if (! empty($user['rol'])) {
                $usuario->syncRoles([$user['rol']]);
            }
        }

        $this->command->info('15 usuarios creados y roles asignados correctamente.');
    }
}
