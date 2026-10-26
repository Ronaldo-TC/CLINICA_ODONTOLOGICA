<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('123');

        $users = [
            ['ci' => '10000001', 'nombre_usuario' => 'admin', 'nombre_completo' => 'Administrador de Ejemplo', 'tipo' => 'admin', 'rol' => 'admin'],
            ['ci' => '10000002', 'nombre_usuario' => 'subadmin', 'nombre_completo' => 'Subadministrador de Ejemplo', 'tipo' => 'subadmin', 'rol' => 'subadmin'],
            ['ci' => '10000003', 'nombre_usuario' => 'director', 'nombre_completo' => 'Director de Ejemplo', 'tipo' => 'director', 'rol' => 'director'],
            ['ci' => '10000004', 'nombre_usuario' => 'jefatura-clinicas', 'nombre_completo' => 'Jefatura de Clínicas de Ejemplo', 'tipo' => 'jefatura', 'rol' => 'jefatura-clinicas'],
            ['ci' => '10000005', 'nombre_usuario' => 'jefe-catedra', 'nombre_completo' => 'Jefe de Cátedra de Ejemplo', 'tipo' => 'jefatura', 'rol' => 'jefe-catedra'],
            ['ci' => '10000006', 'nombre_usuario' => 'docente', 'nombre_completo' => 'Docente de Ejemplo', 'tipo' => 'docente', 'rol' => 'docente'],
            ['ci' => '10000007', 'nombre_usuario' => 'estudiante', 'nombre_completo' => 'Estudiante de Ejemplo', 'tipo' => 'estudiante', 'rol' => 'estudiante'],
            ['ci' => '10000008', 'nombre_usuario' => 'interno', 'nombre_completo' => 'Interno de Ejemplo', 'tipo' => 'interno', 'rol' => 'interno'],
            ['ci' => '10000009', 'nombre_usuario' => 'admision', 'nombre_completo' => 'Admisión de Ejemplo', 'tipo' => 'admision', 'rol' => 'admision'],
            ['ci' => '10000010', 'nombre_usuario' => 'kardex', 'nombre_completo' => 'Kardex de Ejemplo', 'tipo' => 'kardex', 'rol' => 'kardex'],
            ['ci' => '10000011', 'nombre_usuario' => 'cajas', 'nombre_completo' => 'Cajas de Ejemplo', 'tipo' => 'cajas', 'rol' => 'cajas'],
            ['ci' => '10000012', 'nombre_usuario' => 'insumos', 'nombre_completo' => 'Insumos de Ejemplo', 'tipo' => 'insumos', 'rol' => 'insumos'],
            ['ci' => '10000013', 'nombre_usuario' => 'esterilizacion', 'nombre_completo' => 'Esterilización de Ejemplo', 'tipo' => 'esterilizacion', 'rol' => 'esterilizacion'],
            ['ci' => '10000014', 'nombre_usuario' => 'radiologia', 'nombre_completo' => 'Radiología de Ejemplo', 'tipo' => 'radiologia', 'rol' => 'radiologia'],
            ['ci' => '10000015', 'nombre_usuario' => 'investigacion', 'nombre_completo' => 'Investigación de Ejemplo', 'tipo' => 'investigacion', 'rol' => 'investigacion'],
        ];

        foreach ($users as $user) {
            // 1. Crear o recuperar el usuario
            $usuario = Usuario::firstOrCreate(
                ['nombre_usuario' => $user['nombre_usuario']],
                [
                    'ci' => $user['ci'],
                    'nombre_completo' => $user['nombre_completo'],
                    'descripcion' => $user['nombre_completo'],
                    'tipo' => $user['tipo'],
                    'habilitado' => 1,
                    'permitir_login' => 1,
                    'multilogin' => $user['rol'] === 'admin' ? 1 : 0,
                    'contrasena' => $passwordHash,
                    'sesion' => '',
                    'sesion_extra' => '',
                    'info' => '',
                ]
            );

            // 2. Asignar rol con Spatie (usa el FQCN real del modelo automáticamente)
            if (! empty($user['rol'])) {
                $usuario->syncRoles([$user['rol']]);
            }
        }

        $this->command->info('15 usuarios de ejemplo creados y roles asignados correctamente.');
    }
}
