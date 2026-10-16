<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $passwordHash = Hash::make('123');

        $users = [
            ['ci' => '10000001', 'nombre_usuario' => 'usuario-ejemplo', 'nombre_completo' => 'Usuario de Ejemplo', 'tipo' => 'admin', 'rol' => 'admin'],
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

        $this->command->info('Usuario de ejemplo creado y rol asignado correctamente.');
    }
}
