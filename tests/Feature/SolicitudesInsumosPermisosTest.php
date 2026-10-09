<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use Database\Seeders\SolicitudesInsumosPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class SolicitudesInsumosPermisosTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_student_requests_from_both_lists_and_assigned_teacher_authorizes_before_warehouse_delivers(): void
    {
        $this->freezeTime();
        $studentRoleId = DB::table('roles')->where('name', 'estudiante')->where('guard_name', 'web')->value('id');
        $requestPermissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', ['insumos.ver', 'insumos.crear'])->pluck('id');
        DB::table('role_has_permissions')->where('role_id', $studentRoleId)->whereIn('permission_id', $requestPermissionIds)->delete();
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
        $case = $this->clinicalCase();
        $type = InsumoTratamiento::create(['nombre' => 'Material dental', 'clinica_id' => $case['clinic']->id]);
        $material = Insumo::create(['nombre' => 'Guantes', 'clinica_id' => $case['clinic']->id,
            'insumo_tratamiento_id' => $type->id, 'cantidad_numerica' => 10, 'cantidad' => 'pares', 'stock_minimo' => 0, 'costo_unitario' => 2]);
        $case['treatment']->insumos()->attach($material, ['cantidad' => 2]);
        $this->actingAs($case['student']);

        foreach (['estudiante.designados', 'estudiante.historias'] as $listRoute) {
            $this->get(route($listRoute))->assertOk()->assertSee('Ver solicitudes')->assertSee('Solicitar insumos')
                ->assertSee('data-solicitar-designado data-historia="'.$case['history']->id.'"', false)
                ->assertSee('id="formSolicitudDesignado"', false);
            $this->getJson(route($listRoute))->assertOk()->assertJsonPath('total', 1);
        }
        $requestId = $this->postJson(route('solicitudes-insumos.store'), [
            'historia_clinica_id' => $case['history']->id, 'clinica_id' => $case['clinic']->id,
            'tratamiento_id' => $case['treatment']->id, 'detalles' => [['insumo_id' => $material->id, 'cantidad_solicitada' => 2]],
        ])->assertCreated()->json('id');

        $this->assertDatabaseHas('insumos_solicitados', ['id' => $requestId, 'usuario_id' => $case['student']->id, 'estado' => 'pendiente']);
        $this->postJson(route('solicitudes-insumos.autorizar', $requestId))->assertForbidden();
        $warehouse = $this->clinicalUser('insumos');
        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.aprobar', $requestId))->assertUnprocessable();
        $this->actingAs($case['teacher'])->getJson(route('solicitudes-insumos.tabla'))->assertOk()->assertJsonPath('total', 1);
        $this->postJson(route('solicitudes-insumos.autorizar', $requestId))->assertOk();
        $this->assertDatabaseHas('insumos_solicitados', ['id' => $requestId, 'estado' => 'autorizado', 'docente_id' => $case['teacher']->id]);
        $this->postJson(route('solicitudes-insumos.entregar', $requestId))->assertForbidden();
        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.aprobar', $requestId))->assertOk();
        $detail = InsumoSolicitado::findOrFail($requestId)->detalles()->firstOrFail();
        $this->postJson(route('solicitudes-insumos.entregar', $requestId), [
            'detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 2]],
        ])->assertOk();

        $this->assertDatabaseHas('insumos_solicitados', ['id' => $requestId, 'estado' => 'entregado', 'aprobado_por' => $warehouse->id]);
        $this->assertDatabaseHas('insumos_entregas', ['insumo_solicitado_id' => $requestId, 'usuario_id' => $warehouse->id, 'cantidad' => 2]);
        $this->assertSame('8.00', $material->fresh()->cantidad_numerica);
        $this->actingAs($case['student'])->get(route('estudiante.historia', $case['history']))
            ->assertSee('Solicitud #'.$requestId)->assertSee('Entregado')->assertSee('Solicitar nuevamente');
    }

    public function test_closed_history_keeps_request_history_and_hides_the_request_button_in_the_archive(): void
    {
        $this->seed(SolicitudesInsumosPermissionsSeeder::class);
        $case = $this->clinicalCase();
        $case['history']->update(['estado' => 'culminado']);

        $this->actingAs($case['student'])->get(route('estudiante.historias'))->assertOk()->assertSee('Ver solicitudes')
            ->assertDontSee('data-solicitar-designado', false);
        $this->getJson(route('estudiante.designados'))->assertOk()->assertJsonPath('total', 0);
        $this->get(route('estudiante.historia', $case['history']))->assertOk()->assertSee('Historia cerrada')
            ->assertDontSee('data-solicitar-designado', false);
    }
}
