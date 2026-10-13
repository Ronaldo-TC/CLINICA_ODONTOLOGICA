<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AssignmentSeeder
 *
 * Puebla:
 *  1. rol_permiso  → matriz rol↔permiso según la matriz del PDF.
 *  2. especialidades → asignación de docentes/estudiantes/internos a clínicas.
 *  3. insumos_tratamiento → tipos de insumos por clínica.
 *  4. tratamientos → catálogo de tratamientos por clínica.
 */
class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->seedRolPermiso($now);
        $this->seedEspecialidades($now);
        $this->seedInsumosTratamiento($now);
        $this->seedTratamientos($now);
    }

    // ═══════════════════════════════════════════════════════════
    // 1. MATRIZ ROL ↔ PERMISO (según PDF + index.html)
    // ═══════════════════════════════════════════════════════════
    private function seedRolPermiso($now): void
    {
        $permisos = DB::table('permisos')->pluck('id', 'codigo');
        $roles = DB::table('roles')->pluck('id', 'nombre');

        // Admin: todos los permisos
        $adminId = $roles['admin'];
        DB::table('rol_permiso')->insert(
            $permisos->values()->map(fn ($pid) => [
                'rol_id' => $adminId,
                'permiso_id' => $pid,
                'habilitado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        // Matriz del resto de roles (según index.html)
        $matriz = [
            'subadmin' => [
                'usuarios.ver',
                'usuarios.gestionar',
                'pacientes.gestionar',
                'clinicas.gestionar',
                'periodos.ver',
                'tratamientos.gestionar',
                'reportes.ver',
            ],
            'director' => [
                // Solo lectura global + reportes + investigación
                'pacientes.ver',
                'clinicas.ver',
                'periodos.ver',
                'tratamientos.ver',
                'historias.ver',
                'cajas.ver',
                'insumos.ver',
                'esterilizaciones.ver',
                'radiologias.ver',
                'bitacoras.ver',
                'reportes.ver',
                'investigacion.ver',
            ],
            'jefatura-clinicas' => [
                'clinicas.gestionar',
                'pacientes.gestionar',
                'tratamientos.gestionar',
                'historias.ver',
                'reportes.ver',
            ],
            'jefe-catedra' => [
                'historias.ver',
                'reportes.ver',
            ],
            'docente' => [
                'pacientes.ver',
                'historias.ver',
                'historias.gestionar',
                'sesiones.ver',
                'sesiones.gestionar',
                'tratamientos.ver',
                'esterilizaciones.ver',
                'esterilizaciones.gestionar',
                'radiologias.ver',
                'reportes.ver',
            ],
            'estudiante' => [
                'pacientes.ver',
                'historias.ver',
                'historias.crear',
                'historias.editar',
                'sesiones.ver',
                'sesiones.crear',
                'esterilizaciones.ver',
                'esterilizaciones.crear',
                'radiologias.ver',
                'radiologias.crear',
            ],
            'interno' => [
                'pacientes.ver',
                'historias.ver',
                'historias.crear',
                'historias.editar',
                'sesiones.ver',
                'sesiones.crear',
                'esterilizaciones.ver',
                'esterilizaciones.crear',
                'radiologias.ver',
                'radiologias.crear',
            ],
            'admision' => [
                'pacientes.ver',
                'pacientes.crear',
                'pacientes.editar',
                'admisiones.ver',
                'admisiones.crear',
                'admisiones.editar',
                'remisiones.ver',
                'remisiones.crear',
                'historias.ver',
            ],
            'kardex' => [
                'pacientes.ver',
                'admisiones.ver',
                'historias.ver',
            ],
            'cajas' => [
                'pacientes.ver',
                'tratamientos.ver',
                'cajas.ver',
                'cajas.crear',
                'cajas.editar',
                'reportes.ver',
            ],
            'insumos' => [
                'insumos.ver',
                'insumos.gestionar',
                'reportes.ver',
            ],
            'esterilizacion' => [
                'esterilizaciones.ver',
                'esterilizaciones.gestionar',
                'reportes.ver',
            ],
            'radiologia' => [
                'pacientes.ver',
                'cajas.ver',
                'radiologias.ver',
                'radiologias.gestionar',
                'reportes.ver',
            ],
            'investigacion' => [
                'investigacion.ver',
                'reportes.ver',
            ],
        ];

        $pivote = [];
        foreach ($matriz as $rolNombre => $codigos) {
            if (! isset($roles[$rolNombre])) {
                continue;
            }

            $rolId = $roles[$rolNombre];
            foreach ($codigos as $codigo) {
                if (! isset($permisos[$codigo])) {
                    continue;
                }

                $pivote[] = [
                    'rol_id' => $rolId,
                    'permiso_id' => $permisos[$codigo],
                    'habilitado' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('rol_permiso')->insert($pivote);
    }

    // ═══════════════════════════════════════════════════════════
    // 2. ASIGNACIÓN USUARIOS ↔ CLÍNICAS (especialidades)
    // ═══════════════════════════════════════════════════════════
    private function seedEspecialidades($now): void
    {
        // Traemos las clínicas por especialidad (no por ID fijo)
        $clinicas = DB::table('clinicas')->pluck('id', 'especialidad');
        $usuarios = DB::table('usuarios')->pluck('id', 'nombre_usuario');

        $asignaciones = [
            // [usuario, especialidad, responsable_admision]
            ['jefe-catedra',  'Clínica de Admisión', true],
            ['docente',       'Clínica de Admisión', true],
            ['docente',       'Operatoria Dental',   false],
            ['docente',       'Endodoncia',          false],
            ['estudiante',    'Operatoria Dental',   false],
            ['estudiante',    'Endodoncia',          false],
            ['interno',       'Cirugía Bucal',       false],
            ['admision',      'Clínica de Admisión', true],
            ['radiologia',    'Radiología',          false],
            ['esterilizacion', 'Esterilización',      false],
        ];

        $rows = [];
        foreach ($asignaciones as [$usuarioNombre, $especialidad, $responsable]) {
            if (! isset($usuarios[$usuarioNombre], $clinicas[$especialidad])) {
                continue;
            }

            $rows[] = [
                'usuario_id' => $usuarios[$usuarioNombre],
                'clinica_id' => $clinicas[$especialidad],
                'habilitado' => true,
                'rotacion' => $responsable ? 0 : 1,
                'responsable_admision' => $responsable,
                'fecha_inicio' => '2026-01-05',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('especialidades')->insert($rows);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 3. TIPOS DE INSUMOS POR CLÍNICA
    // ═══════════════════════════════════════════════════════════
    private function seedInsumosTratamiento($now): void
    {
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

        if ($rows) {
            DB::table('insumos_tratamiento')->insert($rows);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 4. CATÁLOGO DE TRATAMIENTOS POR CLÍNICA
    // ═══════════════════════════════════════════════════════════
    private function seedTratamientos($now): void
    {
        $clinicas = DB::table('clinicas')->pluck('id', 'especialidad');

        $catalogo = [
            'Operatoria Dental' => [
                ['nombre' => 'Resina simple',           'costo' => 80.00],
                ['nombre' => 'Resina compuesta',        'costo' => 120.00],
                ['nombre' => 'Sellante de fosas',       'costo' => 60.00],
            ],
            'Endodoncia' => [
                ['nombre' => 'Endodoncia unirradicular',  'costo' => 250.00],
                ['nombre' => 'Endodoncia birradicular',   'costo' => 320.00],
                ['nombre' => 'Endodoncia multirradicular', 'costo' => 400.00],
            ],
            'Cirugía Bucal' => [
                ['nombre' => 'Exodoncia simple',        'costo' => 150.00],
                ['nombre' => 'Exodoncia quirúrgica',    'costo' => 300.00],
                ['nombre' => 'Cirugía de tercer molar', 'costo' => 500.00],
            ],
            'Periodoncia' => [
                ['nombre' => 'Detartraje',              'costo' => 120.00],
                ['nombre' => 'Curetaje',                'costo' => 200.00],
            ],
            'Radiología' => [
                ['nombre' => 'Radiografía periapical',  'costo' => 30.00],
                ['nombre' => 'Radiografía panorámica',  'costo' => 80.00],
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

        if ($rows) {
            DB::table('tratamientos')->insert($rows);
        }
    }
}
