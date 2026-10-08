<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoTratamiento;
use App\Models\SesionAtencion;
use App\Models\TratamientoRealizado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class InsumosWorkflowTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_only_supervisor_approves_and_warehouse_delivers_with_partial_stock_tracking(): void
    {
        $case = $this->clinicalCase();
        $case['teacher']->givePermissionTo(['insumos.autorizar', 'insumos.ver']);
        $treatment = TratamientoRealizado::create(['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id, 'estado' => 'en_proceso', 'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(), 'pieza_dental' => '16']);
        $session = SesionAtencion::create(['tratamiento_realizado_id' => $treatment->id, 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id, 'fecha' => today(), 'descripcion' => 'Avance aprobado', 'autorizado' => true, 'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now()]);
        $type = InsumoTratamiento::create(['clinica_id' => $case['clinic']->id, 'nombre' => 'Tipo prueba', 'descripcion' => 'Material']);
        $material = Insumo::create(['nombre' => 'Material prueba', 'clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id, 'cantidad_numerica' => 10, 'cantidad' => 'unidades', 'stock_minimo' => 0, 'costo_unitario' => 2]);
        $payload = ['clinica_id' => $case['clinic']->id, 'insumo_tratamiento_id' => $type->id, 'tratamiento_realizado_id' => $treatment->id, 'sesion_atencion_id' => $session->id, 'detalles' => [['insumo_id' => $material->id, 'cantidad_solicitada' => 4]]];

        $session->update(['autorizado' => false]);
        $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.store'), $payload)->assertUnprocessable();
        $session->update(['autorizado' => true]);
        $response = $this->postJson(route('solicitudes-insumos.store'), $payload)->assertCreated();
        $id = $response->json('id');
        $this->postJson(route('solicitudes-insumos.autorizar', $id))->assertForbidden();
        $this->actingAs($case['teacher'])->postJson(route('solicitudes-insumos.autorizar', $id))->assertOk();
        $request = InsumoSolicitado::findOrFail($id);
        $detail = $request->detalles()->firstOrFail();
        $this->postJson(route('solicitudes-insumos.entregar', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 1]]])->assertForbidden();

        $warehouse = $this->clinicalUser('insumos');
        $this->actingAs($warehouse)->postJson(route('solicitudes-insumos.entregar', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 2]]])->assertOk();
        $this->assertSame('parcial', $request->fresh()->estado);
        $this->assertSame(8.0, (float) $material->fresh()->cantidad_numerica);
        $this->postJson(route('solicitudes-insumos.entregar', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 3]]])->assertUnprocessable();
        $this->assertSame(8.0, (float) $material->fresh()->cantidad_numerica);
        $this->postJson(route('solicitudes-insumos.entregar', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_entregada' => 2]]])->assertOk();
        $this->assertSame('entregado', $request->fresh()->estado);
        $this->assertSame(2, $request->entregas()->count());
        $this->assertSame(0, $detail->consumos()->count());

        $this->get(route('solicitudes-insumos.show', $id))->assertOk()->assertSee('Material prueba');
        $this->actingAs($case['teacher'])->getJson('/api/v1/solicitudes-insumos')->assertOk()->assertJsonFragment(['id' => $id]);
        $otherTeacher = $this->clinicalUser('docente');
        $otherTeacher->givePermissionTo('insumos.ver');
        $this->actingAs($otherTeacher)->getJson('/api/v1/solicitudes-insumos')->assertOk()->assertJsonMissing(['id' => $id]);

        $this->actingAs($case['student'])->postJson(route('solicitudes-insumos.consumir', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_usada' => 3]]])->assertOk();
        $this->assertSame(3.0, (float) $detail->consumos()->sum('cantidad_usada'));
        $this->postJson(route('solicitudes-insumos.consumir', $id), ['detalles' => [['detalle_id' => $detail->id, 'cantidad_usada' => 2]]])->assertUnprocessable();
        $this->assertSame(6.0, (float) $material->fresh()->cantidad_numerica);
    }
}
