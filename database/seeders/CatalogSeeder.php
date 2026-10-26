<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CatalogSeeder
 *
 * Puebla los catálogos base del sistema según el PDF:
 *  - 15 roles (catálogo estático, no editable por admin)
 *  - 80 permisos (16 módulos × 5 acciones)
 *  - 14 clínicas (13 especialidades + admisión)
 *  - Motivos de consulta
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ═══════════════════════════════════════════════════════
        // 1. ROLES (15 según el PDF, págs. 6-9)
        // ═══════════════════════════════════════════════════════
        $roles = [
            ['name' => 'admin',              'descripcion' => 'Administrador del sistema (U-TIC)'],
            ['name' => 'subadmin',           'descripcion' => 'Subadministrador'],
            ['name' => 'director',           'descripcion' => 'Director de Carrera'],
            ['name' => 'jefatura-clinicas',  'descripcion' => 'Jefe de Área Clínica'],
            ['name' => 'jefe-catedra',       'descripcion' => 'Jefe de Cátedra'],
            ['name' => 'docente',            'descripcion' => 'Docente de asignaturas clínicas'],
            ['name' => 'estudiante',         'descripcion' => 'Estudiante de 3° a 5° año'],
            ['name' => 'interno',            'descripcion' => 'Interno de Odontología'],
            ['name' => 'admision',           'descripcion' => 'Administrativo de Admisión'],
            ['name' => 'kardex',             'descripcion' => 'Administrativo de Kardex'],
            ['name' => 'cajas',              'descripcion' => 'Administrativo de Cajas'],
            ['name' => 'insumos',            'descripcion' => 'Administrativo de Insumos'],
            ['name' => 'esterilizacion',     'descripcion' => 'Administrativo de Esterilización'],
            ['name' => 'radiologia',         'descripcion' => 'Administrativo de Radiología'],
            ['name' => 'investigacion',      'descripcion' => 'Instituto de Investigación'],
        ];

        DB::table('roles')->insert(array_map(
            fn (array $r): array => $r + [
                'habilitado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $roles
        ));

        // ═══════════════════════════════════════════════════════
        // 2. PERMISOS (16 módulos × 5 acciones = 80)
        // ═══════════════════════════════════════════════════════
        $modulos = [
            'usuarios' => 'Gestión de cuentas',
            'roles' => 'Roles y permisos',
            'pacientes' => 'Datos de pacientes',
            'clinicas' => 'Especialidades',
            'tratamientos' => 'Catálogo de tratamientos',
            'admisiones' => 'Registro inicial',
            'remisiones' => 'Derivaciones entre clínicas',
            'historias' => 'Historias clínicas',
            'sesiones' => 'Avance de tratamientos',
            'cajas' => 'Pagos y recibos',
            'insumos' => 'Insumos y solicitudes',
            'esterilizaciones' => 'Control de esterilización',
            'radiologias' => 'Radiografías',
            'bitacoras' => 'Auditoría',
            'reportes' => 'Estadísticas y reportes',
            'investigacion' => 'Datos anonimizados',
        ];

        $acciones = [
            'ver' => 'Visualizar',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar/deshabilitar',
            'gestionar' => 'Gestión completa',
        ];

        $permisos = [];
        foreach ($modulos as $modulo => $descripcionModulo) {
            foreach ($acciones as $accion => $descripcionAccion) {
                $permisos[] = [
                    'codigo' => "{$modulo}.{$accion}",
                    'descripcion' => "{$descripcionAccion} — {$descripcionModulo}",
                    'modulo' => $modulo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('permisos')->insert($permisos);

        // ═══════════════════════════════════════════════════════
        // 3. CLÍNICAS (14 áreas según PDF pág. 6)
        // ═══════════════════════════════════════════════════════
        $clinicas = [
            ['modulo' => 'Admisión',                 'especialidad' => 'Clínica de Admisión',       'curso' => 1],
            ['modulo' => 'Clínicas Básicas',         'especialidad' => 'Operatoria Dental',          'curso' => 2],
            ['modulo' => 'Clínicas Básicas',         'especialidad' => 'Endodoncia',                 'curso' => 3],
            ['modulo' => 'Clínicas Básicas',         'especialidad' => 'Periodoncia',                'curso' => 3],
            ['modulo' => 'Clínicas Quirúrgicas',     'especialidad' => 'Cirugía Bucal',              'curso' => 4],
            ['modulo' => 'Clínicas Quirúrgicas',     'especialidad' => 'Cirugía Mayor',              'curso' => 5],
            ['modulo' => 'Clínicas Integrales',      'especialidad' => 'Integral Niños',             'curso' => 4],
            ['modulo' => 'Clínicas Integrales',      'especialidad' => 'Integral Adultos',           'curso' => 4],
            ['modulo' => 'Clínicas Integrales',      'especialidad' => 'Odontopediatría',            'curso' => 4],
            ['modulo' => 'Clínicas de Rehabilitación', 'especialidad' => 'Prostodoncia Fija',         'curso' => 5],
            ['modulo' => 'Clínicas de Rehabilitación', 'especialidad' => 'Prostodoncia Removible',    'curso' => 5],
            ['modulo' => 'Clínicas de Rehabilitación', 'especialidad' => 'Ortodoncia',                'curso' => 4],
            ['modulo' => 'Servicios de Apoyo',       'especialidad' => 'Radiología',                 'curso' => 0],
            ['modulo' => 'Servicios de Apoyo',       'especialidad' => 'Esterilización',             'curso' => 0],
        ];

        DB::table('clinicas')->insert(array_map(
            fn (array $c): array => $c + ['created_at' => $now, 'updated_at' => $now],
            $clinicas
        ));

        // ═══════════════════════════════════════════════════════
        // 4. MOTIVOS DE CONSULTA (para reportes del PDF pág. 6)
        // ═══════════════════════════════════════════════════════
        $motivos = [
            ['nombre' => 'Dolor dental',              'descripcion' => 'Odontalgia aguda o crónica'],
            ['nombre' => 'Control rutinario',         'descripcion' => 'Chequeo periódico'],
            ['nombre' => 'Limpieza dental',           'descripcion' => 'Profilaxis'],
            ['nombre' => 'Caries',                    'descripcion' => 'Lesión de caries dental'],
            ['nombre' => 'Fractura dental',           'descripcion' => 'Traumatismo dental'],
            ['nombre' => 'Extracción',                'descripcion' => 'Exodoncia indicada'],
            ['nombre' => 'Ortodoncia',                'descripcion' => 'Evaluación ortodóncica'],
            ['nombre' => 'Prótesis',                  'descripcion' => 'Rehabilitación protésica'],
            ['nombre' => 'Enfermedad periodontal',    'descripcion' => 'Gingivitis o periodontitis'],
            ['nombre' => 'Urgencia',                  'descripcion' => 'Atención de urgencia'],
        ];

        DB::table('motivos_consulta')->insert(array_map(
            fn (array $m): array => $m + [
                'habilitado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $motivos
        ));

    }
}
