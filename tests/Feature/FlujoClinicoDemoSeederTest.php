<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoEntrega;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamientoUsado;
use App\Models\Paciente;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use App\Services\FlujoTratamiento;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FlujoClinicoDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class FlujoClinicoDemoSeederTest extends TestCase
{
    use DatabaseTransactions;

    private function seedDemo(): void
    {
        $seeder = $this->app->make(FlujoClinicoDemoSeeder::class);
        $seeder->setContainer($this->app)->__invoke(['prefijo' => 'TEST-FLUJO-']);
    }

    public function test_default_seeding_populates_the_modules_without_duplicating_catalogs_when_repeated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tables = ['pacientes', 'admisiones', 'remisiones', 'historias_clinicas', 'tratamientos', 'tratamientos_realizados', 'insumos', 'insumos_tratamiento', 'insumos_solicitados', 'insumos_entregas', 'motivos_consulta', 'periodos', 'radiologias', 'esterilizaciones', 'cajas', 'sesiones_atencion'];
        $counts = array_map(fn (string $table): int => DB::table($table)->count(), $tables);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Paciente::where('ci', 'like', 'DEMO-CLINICA-%')->count());
        $this->assertSame($counts, array_map(fn (string $table): int => DB::table($table)->count(), $tables));
    }

    public function test_five_patients_have_consistent_referrals_treatments_payments_and_material_movements(): void
    {
        $this->freezeTime();
        $this->prepararAsignacionesDemo();

        $this->seedDemo();

        $patients = Paciente::where('ci', 'like', 'TEST-FLUJO-%')->orderBy('ci')->get();
        $this->assertCount(5, $patients);
        $expectedStates = ['pendiente', 'autorizado', 'parcial', 'entregado', 'entregado'];
        $expectedTreatments = ['en_proceso', 'en_proceso', 'en_proceso', 'finalizado', 'culminado'];
        $expectedStock = [100.0, 100.0, 98.0, 96.0, 96.0];
        $expectedUsage = [0.0, 0.0, 1.0, 3.0, 4.0];
        foreach ($patients as $index => $patient) {
            $admission = $patient->admisiones()->sole();
            $referral = $patient->remisiones()->sole();
            $history = $patient->historiasClinicas()->sole();
            $treatment = $history->tratamientosRealizados()->sole();
            $this->assertSame($patient->id, $referral->paciente_id);
            $this->assertSame($admission->id, $referral->admision_id);
            $this->assertSame($referral->id, $history->remision_id);
            $this->assertSame($referral->clinica_id, $history->clinica_id);
            $this->assertSame($referral->estudiante_id, $history->estudiante_id);
            $this->assertSame($referral->docente_id, $history->docente_id);
            $this->assertTrue(app(FlujoTratamiento::class)->tienePago($treatment));
            $this->assertSame($expectedTreatments[$index], $treatment->estado);
            $this->assertSame(2, $treatment->sesionesAtencion()->count());
            $this->assertSame($patient->id, Caja::where('tratamiento_realizado_id', $treatment->id)->sole()->paciente_id);
            $request = InsumoSolicitado::where('tratamiento_realizado_id', $treatment->id)->sole();
            $this->assertSame($expectedStates[$index], $request->estado);
            $this->assertSame($history->estudiante_id, $request->usuario_id);
            $this->assertTrue($request->sesionAtencion->autorizado);
            $detail = $request->detalles()->sole();
            $this->assertSame($expectedStock[$index], (float) $detail->insumo->cantidad_numerica);
            $this->assertSame($expectedUsage[$index], (float) $detail->consumos()->sum('cantidad_usada'));
            $this->assertSame(1, Esterilizacion::where('remision_id', $referral->id)->count());
            $this->assertSame('pendiente', $patient->radiologias()->sole()->estado);
        }
        $this->assertSame('infantil', $patients[3]->admisiones()->sole()->odontograma_denticion);
        $this->assertSame('mixto', $patients[4]->admisiones()->sole()->odontograma_denticion);
        $this->actingAs(Usuario::where('nombre_usuario', 'admin')->firstOrFail());
        foreach (['admisiones', 'historias', 'tratamientos', 'remisiones', 'insumos', 'solicitudes-insumos', 'esterilizacion', 'radiologia', 'cajas', 'tratamientos-realizados'] as $module) {
            $this->get(route($module.'.index'))->assertOk();
        }
    }

    public function test_reseeding_preserves_edited_records_and_does_not_repeat_deliveries_or_stock_deductions(): void
    {
        $this->prepararAsignacionesDemo();
        $this->seedDemo();
        $patient = Paciente::where('ci', 'TEST-FLUJO-001')->firstOrFail();
        $patient->update(['nombre' => 'Nombre editado']);
        $patient->admisiones()->sole()->update(['antecedentes' => 'Antecedentes editados']);
        $models = [Paciente::class, Admision::class, Remision::class, HistoriaClinica::class, Tratamiento::class, TratamientoRealizado::class, SesionAtencion::class, Caja::class, InsumoSolicitado::class, InsumoEntrega::class, InsumoTratamientoUsado::class, Esterilizacion::class, Radiologia::class];
        $counts = array_map(fn (string $model): int => $model::count(), $models);
        $stock = Insumo::orderBy('id')->pluck('cantidad_numerica', 'id')->all();

        $this->seedDemo();

        $this->assertSame($counts, array_map(fn (string $model): int => $model::count(), $models));
        $this->assertSame($stock, Insumo::orderBy('id')->pluck('cantidad_numerica', 'id')->all());
        $this->assertSame('Nombre editado', $patient->fresh()->nombre);
        $this->assertSame('Antecedentes editados', $patient->admisiones()->sole()->antecedentes);
    }

    public function test_failure_rolls_back_all_demo_cases_and_preserves_existing_stock(): void
    {
        $this->prepararAsignacionesDemo();
        $teacher = Usuario::where('nombre_usuario', 'docente')->firstOrFail();
        $clinic = Clinica::where('especialidad', 'Periodoncia')->firstOrFail();
        $teacher->especialidades()->syncWithoutDetaching([$clinic->id => ['habilitado' => false]]);
        $patients = Paciente::count();
        $admissions = Admision::count();
        $stock = Insumo::orderBy('id')->pluck('cantidad_numerica', 'id')->all();

        try {
            $this->seedDemo();
            $this->fail('Debe rechazar una especialidad docente deshabilitada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('deshabilitada', $exception->getMessage());
        }

        $this->assertSame($patients, Paciente::count());
        $this->assertSame($admissions, Admision::count());
        $this->assertSame($stock, Insumo::orderBy('id')->pluck('cantidad_numerica', 'id')->all());
        $this->assertSame(0, Paciente::where('ci', 'like', 'TEST-FLUJO-%')->count());
    }

    private function prepararAsignacionesDemo(): void
    {
        $clinicas = Clinica::whereIn('especialidad', ['Operatoria Dental', 'Endodoncia', 'Periodoncia', 'Odontopediatría', 'Integral Niños'])->pluck('id');
        foreach (['estudiante', 'docente'] as $nombre) {
            $usuario = Usuario::where('nombre_usuario', $nombre)->firstOrFail();
            foreach ($clinicas as $clinica) {
                $usuario->especialidades()->syncWithoutDetaching([$clinica => ['habilitado' => true]]);
            }
        }
    }
}
