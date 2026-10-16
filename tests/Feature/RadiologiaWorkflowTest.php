<?php

namespace Tests\Feature;

use App\Models\SolicitudRadiologica;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class RadiologiaWorkflowTest extends TestCase
{
    use CreatesClinicalCase;
    use DatabaseTransactions;

    public function test_student_request_requires_teacher_approval_and_can_be_corrected_after_observation(): void
    {
        $case = $this->clinicalCase();
        $this->permitir($case['student'], 'radiologias.crear');
        $this->permitir($case['student'], 'radiologias.ver');
        $this->permitir($case['teacher'], 'radiologias.revisar');

        $this->actingAs($case['student'])->post(route('historias.solicitudes-radiologicas.store', $case['history']), [
            'descripcion' => 'Radiografía periapical pieza 16',
        ])->assertRedirect();

        $request = SolicitudRadiologica::query()->sole();
        $this->assertSame(SolicitudRadiologica::PENDIENTE, $request->estado);
        $this->assertNull($request->costo_autorizado);

        $this->actingAs($case['teacher'])->post(route('solicitudes-radiologicas.revisar', $request), [
            'accion' => 'observar',
            'observacion' => 'Indicar la pieza y proyección requeridas.',
        ])->assertRedirect();
        $this->assertSame(SolicitudRadiologica::OBSERVADA, $request->fresh()->estado);

        $this->actingAs($case['student'])->post(route('solicitudes-radiologicas.reenviar', $request), [
            'descripcion' => 'Radiografía periapical pieza 16, proyección distal',
        ])->assertRedirect();
        $this->assertSame(SolicitudRadiologica::PENDIENTE, $request->fresh()->estado);
        $this->assertSame('Radiografía periapical pieza 16, proyección distal', $request->fresh()->descripcion);

        $this->actingAs($case['teacher'])->post(route('solicitudes-radiologicas.revisar', $request), [
            'accion' => 'autorizar',
            'costo_autorizado' => 100,
        ])->assertRedirect();
        $this->assertSame(SolicitudRadiologica::AUTORIZADA, $request->fresh()->estado);
        $this->assertSame('100.00', $request->fresh()->costo_autorizado);
    }

    public function test_responsible_teacher_can_start_request_and_radiology_requires_full_payment(): void
    {
        $case = $this->clinicalCase();
        $this->permitir($case['teacher'], 'radiologias.crear');
        $this->permitir($case['teacher'], 'radiologias.ver');

        $this->actingAs($case['teacher'])->post(route('historias.solicitudes-radiologicas.store', $case['history']), [
            'descripcion' => 'Radiografía panorámica',
            'costo_autorizado' => 100,
        ])->assertRedirect();

        $request = SolicitudRadiologica::query()->sole();
        $this->assertSame(SolicitudRadiologica::AUTORIZADA, $request->estado);

        $cashier = $this->clinicalUser('cajas');
        $this->permitir($cashier, 'cajas.crear');
        $this->actingAs($cashier)->post(route('cajas.solicitudes-radiologicas.pago', $request), [
            'monto' => 60,
            'tipo_pago' => 'Efectivo',
        ])->assertRedirect();
        $this->assertSame('parcial', $request->fresh()->resumenPago()['estado']);

        $radiology = $this->clinicalUser('radiologia');
        $this->permitir($radiology, 'radiologias.gestionar');
        $this->actingAs($radiology)->post(route('radiologia.solicitudes.realizar', $request))
            ->assertSessionHasErrors('solicitud');
        $this->assertDatabaseMissing('radiologias', ['solicitud_radiologica_id' => $request->id]);

        $this->actingAs($cashier)->post(route('cajas.solicitudes-radiologicas.pago', $request), [
            'monto' => 41,
            'tipo_pago' => 'Efectivo',
        ])->assertSessionHasErrors('monto');
        $this->assertSame('parcial', $request->fresh()->resumenPago()['estado']);

        $this->actingAs($cashier)->post(route('cajas.solicitudes-radiologicas.pago', $request), [
            'monto' => 40,
            'tipo_pago' => 'Transferencia',
        ])->assertRedirect();
        $this->assertSame('pagado', $request->fresh()->resumenPago()['estado']);
        $recibos = $request->recibos()->orderBy('id')->get();
        $this->assertCount(2, $recibos);
        $this->assertSame((int) $recibos[0]->recibo + 1, (int) $recibos[1]->recibo);

        $this->actingAs($radiology)->post(route('radiologia.solicitudes.realizar', $request))
            ->assertRedirect();
        $this->assertDatabaseHas('radiologias', [
            'solicitud_radiologica_id' => $request->id,
            'paciente_id' => $case['history']->paciente_id,
            'clinica_id' => $case['clinic']->id,
        ]);
    }

    public function test_student_cannot_create_request_for_another_students_history(): void
    {
        $case = $this->clinicalCase();
        $otherStudent = $this->clinicalUser('estudiante');
        $this->permitir($otherStudent, 'radiologias.crear');

        $this->actingAs($otherStudent)->post(route('historias.solicitudes-radiologicas.store', $case['history']), [
            'descripcion' => 'Radiografía solicitada sin asignación',
        ])->assertNotFound();
        $this->assertDatabaseCount('solicitudes_radiologicas', 0);
    }

    public function test_cash_radiology_and_history_screens_show_the_request_at_the_right_stage(): void
    {
        $case = $this->clinicalCase();
        $this->permitir($case['teacher'], 'radiologias.crear');
        $this->permitir($case['teacher'], 'radiologias.ver');
        $this->actingAs($case['teacher'])->post(route('historias.solicitudes-radiologicas.store', $case['history']), [
            'descripcion' => 'Radiografía bitewing pieza 26',
            'costo_autorizado' => 80,
        ])->assertRedirect();
        $request = SolicitudRadiologica::query()->sole();

        $cashier = $this->clinicalUser('cajas');
        $this->permitir($cashier, 'cajas.ver');
        $this->permitir($cashier, 'cajas.crear');
        $this->actingAs($cashier)->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Radiografía bitewing pieza 26')
            ->assertSee('Buscar por CI de estudiante o número de ficha');
        $this->actingAs($cashier)->post(route('cajas.solicitudes-radiologicas.pago', $request), [
            'monto' => 80,
            'tipo_pago' => 'Efectivo',
        ])->assertRedirect();

        $radiology = $this->clinicalUser('radiologia');
        $this->permitir($radiology, 'radiologias.ver');
        $this->permitir($radiology, 'radiologias.gestionar');
        $this->actingAs($radiology)->get(route('radiologia.index'))
            ->assertOk()
            ->assertSee('Radiografía bitewing pieza 26')
            ->assertSee('Registrar estudio y cargar imágenes');
        $this->actingAs($radiology)->post(route('radiologia.solicitudes.realizar', $request))
            ->assertRedirect();

        $this->permitir($case['student'], 'historias.ver');
        $this->permitir($case['student'], 'radiologias.ver');
        $this->actingAs($case['student'])->get(route('historias.show', $case['history']))
            ->assertOk()
            ->assertSee('Radiografía bitewing pieza 26')
            ->assertSee('Consultar estudio e imágenes');

        $otherClinicCase = $this->clinicalCase();
        $otherClinicCase['admission']->update(['paciente_id' => $case['history']->paciente_id]);
        $otherClinicCase['history']->update(['paciente_id' => $case['history']->paciente_id]);
        $this->permitir($otherClinicCase['student'], 'historias.ver');
        $this->permitir($otherClinicCase['student'], 'radiologias.ver');
        $this->actingAs($otherClinicCase['student'])->get(route('historias.show', $otherClinicCase['history']))
            ->assertOk()
            ->assertSee('Resultados radiológicos de este paciente')
            ->assertSee('Radiografía bitewing pieza 26');
    }

    private function permitir(Usuario $usuario, string $permiso): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => $permiso,
            'descripcion' => $permiso,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permission = Permission::query()->where('name', $permiso)->where('guard_name', 'web')->firstOrFail();
        $usuario->givePermissionTo($permission);
    }
}
