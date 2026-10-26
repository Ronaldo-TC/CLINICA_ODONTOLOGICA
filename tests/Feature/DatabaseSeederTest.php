<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AssignmentSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PacientesContactoSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    private const PACIENTE_CI = ['6743210', '8124567', '5987342'];

    use DatabaseTransactions;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_default_seeding_does_not_add_demo_records(): void
    {
        $demoCountsBefore = $this->demoCounts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($demoCountsBefore, $this->demoCounts());
    }

    public function test_default_seeding_creates_schema_compatible_example_data_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $docente = Usuario::query()->where('nombre_usuario', 'docente')->firstOrFail();
        $clinicaId = DB::table('clinicas')->where('especialidad', 'Operatoria Dental')->value('id');
        $countsAfterFirstRun = $this->seededCounts();

        $this->assertSame(15, Usuario::query()->whereBetween('ci', ['10000001', '10000015'])->count());
        $this->assertTrue($docente->hasRole('docente'));
        $this->assertFalse(Schema::hasTable('periodos'));
        $this->assertDatabaseHas('especialidades', [
            'usuario_id' => $docente->id,
            'clinica_id' => $clinicaId,
        ]);
        $this->assertDatabaseHas('tratamientos', [
            'nombre' => 'Resina simple',
            'precio_asignado' => true,
        ]);
        $this->assertDatabaseHas('insumos', [
            'nombre' => 'Resina fotopolimerizable A2',
            'costo_unitario' => 150,
        ]);

        $tratamientoId = DB::table('tratamientos')->where('nombre', 'Resina simple')->value('id');
        $insumoId = DB::table('insumos')->where('nombre', 'Resina fotopolimerizable A2')->value('id');
        $patientIds = DB::table('pacientes')->whereIn('ci', self::PACIENTE_CI)->pluck('id');

        $this->assertSame(80.0, (float) DB::table('tratamientos')->where('id', $tratamientoId)->value('costo'));
        $this->assertDatabaseHas('insumo_tratamiento', [
            'tratamiento_id' => $tratamientoId,
            'insumo_id' => $insumoId,
        ]);
        $this->assertSame(0.25, (float) DB::table('insumo_tratamiento')
            ->where('tratamiento_id', $tratamientoId)
            ->where('insumo_id', $insumoId)
            ->value('cantidad'));
        $this->assertSame(3, $patientIds->count());
        $this->assertSame(0, DB::table('pacientes')
            ->whereIn('ci', self::PACIENTE_CI)
            ->where('nombre', 'like', '%demo%')
            ->count());
        $this->assertSame(3, DB::table('remisiones')->whereIn('paciente_id', $patientIds)->count());
        $this->assertSame(3, DB::table('historias_clinicas')->whereIn('paciente_id', $patientIds)->count());
        $this->assertSame(3, DB::table('solicitudes_radiologicas')->whereIn('paciente_id', $patientIds)->count());
        $this->assertSame(3, DB::table('cajas')
            ->whereIn('paciente_id', $patientIds)
            ->whereNotNull('solicitud_radiologica_id')
            ->count());
        $this->assertSame(3, DB::table('radiologias')
            ->whereIn('paciente_id', $patientIds)
            ->whereNotNull('solicitud_radiologica_id')
            ->count());

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($countsAfterFirstRun, $this->seededCounts());
    }

    public function test_consolidated_and_backfill_seeders_run_with_the_current_schema(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(UsersSeeder::class);
        $this->seed(AssignmentSeeder::class);
        $this->seed(PacientesContactoSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('usuarios', ['nombre_usuario' => 'estudiante']);
        $this->assertDatabaseHas('tratamientos', ['nombre' => 'Endodoncia unirradicular']);
        $this->assertDatabaseHas('insumos', ['nombre' => 'Conos de gutapercha surtidos']);
    }

    /** @return array<string, int> */
    private function demoCounts(): array
    {
        return [
            'clinical_patients' => DB::table('pacientes')->where('ci', 'like', 'DEMO-CLINICA-%')->count(),
            'odontogram_patients' => DB::table('pacientes')->where('ci', 'ODONTO-DEMO-3D')->count(),
            'odontogram_users' => DB::table('usuarios')->where('nombre_usuario', 'demo_estudiante')->count(),
            'odontogram_clinics' => DB::table('clinicas')->where('modulo', 'Demo')->count(),
        ];
    }

    /** @return array<string, int> */
    private function seededCounts(): array
    {
        return [
            'roles' => DB::table('roles')->count(),
            'permissions' => DB::table('permissions')->count(),
            'users' => DB::table('usuarios')->count(),
            'specialties' => DB::table('especialidades')->count(),
            'clinics' => DB::table('clinicas')->count(),
            'treatments' => DB::table('tratamientos')->count(),
            'supplies' => DB::table('insumos_tratamiento')->count(),
            'inventory' => DB::table('insumos')->count(),
            'treatment_supply_links' => DB::table('insumo_tratamiento')->count(),
            'sample_patients' => DB::table('pacientes')->whereIn('ci', self::PACIENTE_CI)->count(),
            'referrals' => DB::table('remisiones')->whereIn('paciente_id', function ($query): void {
                $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
            })->count(),
            'admissions' => DB::table('admisiones')->whereIn('paciente_id', function ($query): void {
                $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
            })->count(),
            'histories' => DB::table('historias_clinicas')->whereIn('paciente_id', function ($query): void {
                $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
            })->count(),
            'completed_treatments' => DB::table('tratamientos_realizados')->whereIn('historia_clinica_id', function ($query): void {
                $query->select('id')->from('historias_clinicas')->whereIn('paciente_id', function ($subquery): void {
                    $subquery->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
                });
            })->count(),
            'radiology_requests' => DB::table('solicitudes_radiologicas')->whereIn('paciente_id', function ($query): void {
                $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
            })->count(),
            'radiology_receipts' => DB::table('cajas')
                ->whereNotNull('solicitud_radiologica_id')
                ->whereIn('paciente_id', function ($query): void {
                    $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
                })->count(),
            'radiologies' => DB::table('radiologias')->whereIn('paciente_id', function ($query): void {
                $query->select('id')->from('pacientes')->whereIn('ci', self::PACIENTE_CI);
            })->count(),
        ];
    }
}
