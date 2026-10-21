<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Remision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class AdmisionInicialTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    private const FIRMA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=';

    public function test_la_lista_de_admisiones_muestra_el_visor_3d_sin_una_ruta_api(): void
    {
        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.index'))
            ->assertOk()
            ->assertSee('id="admision-odontograma-canvas"', false)
            ->assertSee('data-model-url="'.asset('models/odontograma.glb').'"', false)
            ->assertSee('id="admision-odontograma-status"', false)
            ->assertSee('id="abrir-odontograma-3d"', false)
            ->assertSee('id="modalOdontograma3D"', false)
            ->assertSee('role="dialog" aria-modal="true"', false)
            ->assertSee('id="odontograma-3d-modal-stage"', false);
    }

    public function test_la_ficha_inicial_guarda_la_evaluacion_y_deriva_sin_asignar_personal(): void
    {
        $caso = $this->clinicalCase();
        $segunda = Clinica::create(['especialidad' => 'Segunda clínica de prueba', 'modulo' => 'Prueba', 'curso' => 1]);
        $admin = $this->clinicalUser('admin');
        $datos = [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno',
            'telefono' => '+591 070000001', 'estado_civil' => 'Soltero', 'ocupacion' => 'Estudiante',
            'direccion' => 'El Alto', 'emergencia' => 'Familiar responsable', 'telefono_emergencia' => '070000002',
            'motivo_consulta' => 'Dolor dental', 'historia_enfermedad_actual' => 'Dolor desde ayer', 'antecedentes' => 'Alergia referida',
            'peso_kg' => 65.5, 'talla_mts' => 1.65, 'temperatura_c' => 36.5, 'pulso' => 72,
            'presion_arterial' => '120/80', 'frecuencia_respiratoria' => 18,
            'asimetria_facial' => false, 'cambio_color' => true, 'abultamiento' => false,
            'ganglios_palpables' => true, 'ruidos_articulares' => false,
            'ee' => 'Observación extraoral', 'cn' => 'Ganglios observados', 'atm' => 'Sin ruidos',
            'bm_estado' => 'Sin alteraciones aparentes', 'bm' => 'Mucosa bucal observada',
            'gm_estado' => 'Con alteraciones', 'gm' => 'Mucosa gingival observada',
            'calculo' => 'Presente', 'oclusion' => 'Clase I', 'diagnostico' => 'Diagnóstico presuntivo',
            'examen' => 'Radiografía periapical', 'firma' => self::FIRMA, 'consentimiento_datos' => true,
            'odontograma_denticion' => 'mixto', 'odontograma_pintado' => json_encode(['55' => ['o' => 'caries'], '16' => ['marcas' => [['estado' => 'corona', 'color' => 'azul']]]]),
            'clinica_ids' => [$caso['clinic']->id, $segunda->id], 'registrado_por' => $caso['student']->id,
        ];

        $respuesta = $this->actingAs($admin)->postJson(route('admisiones.store'), $datos)->assertCreated();
        $ficha = Admision::findOrFail($respuesta->json('id'));
        foreach (['estudiante_id', 'responsable_id', 'especialidad_estudiante', 'especialidad_responsable', 'periodo_id'] as $campo) {
            $this->assertNull($ficha->$campo);
        }
        $this->assertSame($admin->id, $ficha->registrado_por);
        foreach (['telefono', 'direccion', 'emergencia', 'telefono_emergencia', 'historia_enfermedad_actual', 'antecedentes', 'bm_estado', 'gm_estado', 'bm', 'gm', 'ee', 'cn', 'atm', 'diagnostico', 'examen', 'firma'] as $campo) {
            $this->assertSame($datos[$campo], $ficha->$campo);
        }
        $this->assertSame('1.65', $ficha->talla_mts);
        $this->assertSame('65.50', $ficha->peso_kg);
        $this->assertFalse($ficha->asimetria_facial);
        $this->assertTrue($ficha->cambio_color);
        $this->assertFalse($ficha->ruidos_articulares);
        $this->assertSame(json_decode($datos['odontograma_pintado'], true), json_decode($ficha->odontograma_pintado, true));
        $this->assertCount(2, $ficha->remisiones);
        foreach ($ficha->remisiones as $derivacion) {
            $this->assertSame('pendiente', $derivacion->estado);
            $this->assertTrue($derivacion->habilitado);
            $this->assertNull($derivacion->estudiante_id);
            $this->assertNull($derivacion->docente_id);
            $this->assertNull($derivacion->periodo_id);
        }
        $this->assertSame(0, $ficha->historiasClinicas()->count());
        $this->assertStringContainsString($segunda->especialidad, $ficha->area_derivada);
    }

    public function test_la_ficha_minima_no_exige_periodo_clinica_ni_asignacion(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Regular', 'clinica_ids' => null,
        ])->assertCreated();
        $ficha = Admision::findOrFail($respuesta->json('id'));
        $this->assertNull($ficha->telefono);
        $this->assertNull($ficha->asimetria_facial);
        $this->assertNull($ficha->periodo_id);
        $this->assertSame(0, $ficha->remisiones()->count());
    }

    public function test_la_evaluacion_invalida_no_crea_ficha_ni_derivaciones(): void
    {
        $caso = $this->clinicalCase();
        $cantidad = Admision::count();
        $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'asimetria_facial' => 'tal vez',
            'bm_estado' => 'Inválido', 'firma' => 'https://ejemplo.test/firma.png',
            'clinica_ids' => [$caso['clinic']->id, $caso['clinic']->id, 2147483647],
        ])->assertUnprocessable()->assertJsonValidationErrors(['gpe', 'asimetria_facial', 'bm_estado', 'firma', 'clinica_ids.0', 'clinica_ids.2'])
            ->assertJsonPath('errors.gpe.0', 'Seleccione el estado físico general.');
        $this->assertSame($cantidad, Admision::count());
        $this->assertSame(0, Remision::where('admision_id', $caso['admission']->id)->count());
    }

    public function test_las_vistas_recuperan_observaciones_firma_y_respuestas_negativas(): void
    {
        $caso = $this->clinicalCase();
        $caso['admission']->update(['gpe' => 'Bueno', 'asimetria_facial' => false, 'firma' => self::FIRMA, 'bm_estado' => 'Con alteraciones', 'bm' => 'Observación bucal conservada', 'talla_mts' => 1.65]);
        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.create'))->assertOk()
            ->assertSee('name="talla_mts"', false)->assertSee('name="clinica_ids[]"', false)
            ->assertSee('name="odontograma_pintado"', false)->assertDontSee('name="estudiante_id"', false)->assertDontSee('name="responsable_id"', false);
        $edicion = $this->get(route('admisiones.edit', $caso['admission']->id))->assertOk()
            ->assertSee('Observación bucal conservada')->assertSee(self::FIRMA, false);
        $this->assertMatchesRegularExpression('/id="adm-asimetria_facial".*?<option value="0"\s+selected/s', $edicion->getContent());
        $this->get(route('admisiones.show', $caso['admission']->id))->assertOk()
            ->assertSee('Observación bucal conservada')->assertSee('1.65 m')->assertSee(self::FIRMA, false)->assertSee('PDF de la ficha');
    }

    public function test_el_estudiante_recupera_su_ficha_sin_asignacion_y_no_la_de_otro(): void
    {
        $caso = $this->clinicalCase();
        $autor = $this->clinicalUser('estudiante');
        $autor->givePermissionTo(['admisiones.crear', 'admisiones.ver', 'admisiones.editar']);
        $respuesta = $this->actingAs($autor)->postJson(route('admisiones.store'), ['paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno'])->assertCreated();
        $id = $respuesta->json('id');
        $this->get(route('admisiones.show', $id))->assertOk();
        $this->get(route('admisiones.edit', $id))->assertOk();
        $otro = $this->clinicalUser('estudiante');
        $otro->givePermissionTo(['admisiones.ver', 'admisiones.editar']);
        $this->actingAs($otro)->get(route('admisiones.show', $id))->assertNotFound();
        $this->get(route('admisiones.edit', $id))->assertNotFound();
        $this->get(route('admisiones.ficha-pdf', ['admision' => $id, 'formato' => 'pdf']))->assertNotFound();
    }

    public function test_el_docente_accede_solo_a_las_fichas_derivadas_a_su_clinica(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno', 'clinica_ids' => [$caso['clinic']->id],
        ])->assertCreated();
        $id = $respuesta->json('id');
        $caso['teacher']->givePermissionTo('admisiones.ver');
        $this->actingAs($caso['teacher'])->get(route('admisiones.show', $id))->assertOk();
        $otro = $this->clinicalUser('docente');
        $otro->givePermissionTo('admisiones.ver');
        $this->actingAs($otro)->get(route('admisiones.show', $id))->assertNotFound();
        $this->get(route('admisiones.ficha-pdf', $id))->assertNotFound();
    }

    public function test_editar_areas_no_duplica_derivaciones_y_conserva_las_retiradas(): void
    {
        $caso = $this->clinicalCase();
        $datos = ['paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno', 'clinica_ids' => [$caso['clinic']->id]];
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), $datos)->assertCreated();
        $id = $respuesta->json('id');
        $derivacion = Remision::where('admision_id', $id)->firstOrFail();
        $this->putJson(route('admisiones.update', $id), $datos + ['periodo_id' => $caso['admission']->periodo_id])->assertOk();
        $this->assertSame(1, Remision::where('admision_id', $id)->count());
        $this->assertSame($caso['admission']->periodo_id, $derivacion->fresh()->periodo_id);
        $this->putJson(route('admisiones.update', $id), ['paciente_id' => $datos['paciente_id'], 'clinica_ids' => [], 'actualizar_derivaciones' => true])->assertOk();
        $this->assertFalse($derivacion->fresh()->habilitado);
        $this->assertSame('', Admision::findOrFail($id)->area_derivada);
        $this->putJson(route('admisiones.update', $id), $datos)->assertOk();
        $this->assertTrue($derivacion->fresh()->habilitado);
        $this->assertSame(1, Remision::where('admision_id', $id)->count());
    }

    public function test_retirar_una_derivacion_con_atencion_revierte_toda_la_edicion(): void
    {
        $caso = $this->clinicalCase();
        $caso['admission']->update(['diagnostico' => 'Diagnóstico original']);
        $derivacion = Remision::create(['admision_id' => $caso['admission']->id, 'paciente_id' => $caso['admission']->paciente_id, 'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['admission']->periodo_id, 'estado' => 'asignada', 'habilitado' => true]);
        $this->actingAs($this->clinicalUser('admin'))->putJson(route('admisiones.update', $caso['admission']->id), [
            'paciente_id' => $caso['admission']->paciente_id, 'diagnostico' => 'Cambio que debe revertirse', 'actualizar_derivaciones' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('clinica_ids');
        $this->assertSame('Diagnóstico original', $caso['admission']->fresh()->diagnostico);
        $this->assertTrue($derivacion->fresh()->habilitado);
    }

    public function test_asignar_una_ficha_sin_periodo_muestra_validacion_y_no_crea_historia(): void
    {
        $caso = $this->clinicalCase();
        $derivacion = Remision::create(['admision_id' => $caso['admission']->id, 'paciente_id' => $caso['admission']->paciente_id, 'clinica_id' => $caso['clinic']->id, 'estado' => 'pendiente', 'habilitado' => true]);
        $cantidad = HistoriaClinica::count();
        $this->actingAs($this->clinicalUser('admin'))->postJson(route('remisiones.asignar', $derivacion->id), [
            'estudiante_id' => $caso['student']->id, 'docente_id' => $caso['teacher']->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('periodo_id');
        $this->assertSame($cantidad, HistoriaClinica::count());
        $this->assertSame('pendiente', $derivacion->fresh()->estado);
    }

    public function test_el_pdf_de_ficha_incluye_la_evaluacion_y_excluye_otras_admisiones_del_paciente(): void
    {
        $caso = $this->clinicalCase();
        $caso['admission']->update(['talla_mts' => 1.65, 'firma' => self::FIRMA, 'bm' => 'Observación única de esta ficha', 'asimetria_facial' => false]);
        Admision::create(['paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno', 'motivo_consulta' => 'Otra admisión que no debe incluirse']);
        $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.ficha-pdf', ['admision' => $caso['admission']->id]))
            ->assertOk()->assertViewHas('admisiones', fn ($admisiones): bool => $admisiones->count() === 1)
            ->assertSee('1.65 m')->assertSee('Observación única de esta ficha')
            ->assertSee(self::FIRMA, false)->assertDontSee('Otra admisión que no debe incluirse')
            ->assertDontSee('Historias clínicas y evolución de tratamientos');

        $respuesta = $this->get(route('admisiones.ficha-pdf', ['admision' => $caso['admission']->id, 'formato' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeaderContains('Content-Disposition', 'attachment; filename="ficha-'.$caso['admission']->id.'-');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_la_ficha_pdf_exige_autenticacion_y_permiso(): void
    {
        $caso = $this->clinicalCase();
        $this->get(route('admisiones.ficha-pdf', $caso['admission']->id))->assertRedirect();
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $this->actingAs($usuario)->get(route('admisiones.ficha-pdf', $caso['admission']->id))->assertForbidden();
    }

    public function test_completar_solo_el_periodo_actualiza_las_derivaciones_pendientes(): void
    {
        $caso = $this->clinicalCase();
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno', 'clinica_ids' => [$caso['clinic']->id],
        ])->assertCreated();
        $id = $respuesta->json('id');
        $this->putJson(route('admisiones.update', $id), [
            'paciente_id' => $caso['admission']->paciente_id, 'periodo_id' => $caso['admission']->periodo_id,
        ])->assertOk();
        $this->assertSame($caso['admission']->periodo_id, Remision::where('admision_id', $id)->firstOrFail()->periodo_id);
    }

    public function test_no_se_reactiva_desde_la_ficha_una_derivacion_asignada_e_inhabilitada(): void
    {
        $caso = $this->clinicalCase();
        $derivacion = Remision::create(['admision_id' => $caso['admission']->id, 'paciente_id' => $caso['admission']->paciente_id, 'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['admission']->periodo_id, 'estado' => 'asignada', 'habilitado' => false]);
        $this->actingAs($this->clinicalUser('admin'))->putJson(route('admisiones.update', $caso['admission']->id), [
            'paciente_id' => $caso['admission']->paciente_id, 'clinica_ids' => [$caso['clinic']->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('clinica_ids');
        $this->assertFalse($derivacion->fresh()->habilitado);
    }

    public function test_una_firma_mayor_que_text_se_guarda_sin_truncarse(): void
    {
        $caso = $this->clinicalCase();
        $firma = self::FIRMA.str_repeat('A', 70000);
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno', 'firma' => $firma,
        ])->assertCreated();
        $this->assertSame($firma, Admision::findOrFail($respuesta->json('id'))->firma);
    }

    public function test_el_panel_del_personal_de_admision_cuenta_sus_nuevas_fichas(): void
    {
        $caso = $this->clinicalCase();
        $personal = $this->clinicalUser('admision');
        $personal->givePermissionTo('admisiones.crear');
        $this->actingAs($personal)->postJson(route('admisiones.store'), [
            'paciente_id' => $caso['admission']->paciente_id, 'gpe' => 'Bueno',
        ])->assertCreated();
        $this->get(route('dashboard'))->assertOk()->assertViewHas('tarjetas', fn (array $tarjetas): bool => collect($tarjetas)->firstWhere('label', 'Admisiones registradas')['value'] === '1');
    }

    public function test_dompdf_genera_la_ficha_pdf_con_evaluacion_firma_y_odontograma_mixto(): void
    {
        $caso = $this->clinicalCase();
        $caso['admission']->update(['gpe' => 'Bueno', 'talla_mts' => 1.65, 'firma' => self::FIRMA, 'odontograma_denticion' => 'mixto', 'odontograma_pintado' => json_encode(['55' => ['o' => 'caries'], '16' => ['marcas' => [['estado' => 'corona', 'color' => 'azul']]]])]);
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.ficha-pdf', ['admision' => $caso['admission']->id, 'formato' => 'pdf']));
        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeaderContains('Content-Disposition', 'ficha-')->assertHeaderContains('Cache-Control', 'no-store');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(20000, strlen($respuesta->getContent()));
    }
}
