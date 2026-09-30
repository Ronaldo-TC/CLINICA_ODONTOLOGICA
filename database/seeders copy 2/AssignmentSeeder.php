<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $allPermissionIds = DB::table('permisos')->pluck('id');
        $roleIds = DB::table('roles')->pluck('id', 'nombre');

        foreach ($roleIds as $roleName => $roleId) {
            $permissionIds = $roleName === 'admin'
                ? $allPermissionIds
                : DB::table('permisos')->whereIn('codigo', $this->permissionsFor($roleName))->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('rol_permiso')->insert([
                    'rol_id' => $roleId,
                    'permiso_id' => $permissionId,
                    'habilitado' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $clinicIds = DB::table('clinicas')->orderBy('id')->pluck('id')->values();
        $assignments = [
            ['usuario' => 'jefe-catedra', 'clinica' => 1, 'responsable' => true],
            ['usuario' => 'docente', 'clinica' => 1, 'responsable' => true],
            ['usuario' => 'estudiante', 'clinica' => 1, 'responsable' => false],
            ['usuario' => 'interno', 'clinica' => 2, 'responsable' => false],
        ];

        foreach ($assignments as $assignment) {
            $userId = DB::table('usuarios')->where('nombre_usuario', $assignment['usuario'])->value('id');
            $clinicId = $clinicIds[$assignment['clinica'] - 1];

            DB::table('especialidades')->insert([
                'usuario_id' => $userId,
                'clinica_id' => $clinicId,
                'habilitado' => true,
                'rotacion' => $assignment['responsable'] ? 0 : 1,
                'responsable_admision' => $assignment['responsable'],
                'fecha_inicio' => '2026-01-05',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function permissionsFor(string $role): array
    {
        return match ($role) {
            'subadmin', 'director', 'jefatura-clinicas' => [
                'usuarios.ver',
                'clinica.gestionar',
                'pacientes.gestionar',
                'admisiones.gestionar',
                'tratamientos.gestionar',
                'reportes.ver',
            ],
            'jefe-catedra', 'docente' => [
                'pacientes.gestionar',
                'admisiones.gestionar',
                'tratamientos.gestionar',
                'reportes.ver',
            ],
            'estudiante', 'interno' => [
                'pacientes.gestionar',
                'admisiones.gestionar',
                'tratamientos.gestionar',
            ],
            'cajas' => ['cajas.gestionar', 'pacientes.gestionar', 'reportes.ver'],
            'esterilizacion' => ['esterilizacion.gestionar', 'reportes.ver'],
            'insumos' => ['insumos.gestionar', 'reportes.ver'],
            'radiologia' => ['radiologia.gestionar', 'reportes.ver'],
            'investigacion' => ['reportes.ver', 'pacientes.gestionar'],
            default => [],
        };
    }
}
