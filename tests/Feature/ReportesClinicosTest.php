<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\CajaDetalle;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\Remision;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class ReportesClinicosTest extends TestCase
{
    use CreatesClinicalCase;
    use DatabaseTransactions;

    /** @return array<string, array{0: string}> */
    public static function modulos(): array
    {
        return array_combine(
            $nombres = ['pacientes', 'admisiones', 'historias', 'tratamientos', 'remisiones', 'tratamientos-realizados', 'insumos', 'solicitudes-insumos', 'esterilizacion', 'radiologia', 'cajas'],
            array_map(fn (string $nombre): array => [$nombre], $nombres)
        );
    }

    #[DataProvider('modulos')]
    public function test_los_modulos_generan_reportes_y_muestran_la_opcion_en_su_pantalla(string $modulo): void
    {
        $admin = $this->clinicalUser('admin');

        $respuesta = $this->actingAs($admin)->get(route($modulo.'.reporte'));

        $respuesta->assertOk()->assertViewIs('reportes.modulo')
            ->assertSeeText('Imprimir / Guardar PDF')
            ->assertHeaderContains('Cache-Control', 'no-store');
        $this->get(route($modulo.'.index'))->assertSee(route($modulo.'.reporte'), false);
    }

    public function test_el_reporte_incluye_ficha_odontograma_derivaciones_avances_y_pagos_del_paciente(): void
    {
        $caso = $this->approvedProgress();
        $caso['admission']->update(['motivo_consulta' => 'Dolor al masticar', 'antecedentes' => 'Antecedente de ejemplo', 'peso_kg' => 65, 'odontograma_pintado' => json_encode(['16' => ['o' => 'caries', 'marcas' => [['estado' => 'corona', 'color' => 'azul']]]])]);
        $remision = Remision::create(['paciente_id' => $caso['admission']->paciente_id, 'admision_id' => $caso['admission']->id, 'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id, 'estado' => 'asignada', 'habilitado' => true]);
        $pago = $this->crearPago($caso, ['costo_total' => 100, 'cancelado' => 40]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', $caso['admission']->id));

        $respuesta->assertOk()->assertViewIs('reportes.paciente')
            ->assertSeeText(['Dolor al masticar', 'Antecedente de ejemplo', '65.00 kg', 'Caries', 'Corona', 'Avance'])
            ->assertViewHas('remisiones', fn ($registros) => $registros->modelKeys() === [$remision->id])
            ->assertViewHas('pagos', fn ($registros) => $registros->modelKeys() === [$pago->id])
            ->assertHeaderContains('Cache-Control', 'no-store');
        $this->get(route('admisiones.tabla', ['buscar' => $caso['admission']->paciente->ci]))->assertSee('Reporte del paciente');
    }

    public function test_un_estudiante_no_puede_generar_el_reporte_de_otro_estudiante(): void
    {
        $caso = $this->clinicalCase();
        $otro = $this->clinicalUser('estudiante');
        $otro->givePermissionTo('admisiones.ver');

        $this->actingAs($otro)->get(route('admisiones.reporte-paciente', $caso['admission']->id))->assertNotFound();
    }

    public function test_el_reporte_del_estudiante_excluye_admisiones_e_historias_ajenas_del_mismo_paciente(): void
    {
        $caso = $this->clinicalCase();
        $caso['student']->givePermissionTo(['admisiones.ver', 'historias.ver']);
        $otro = $this->clinicalUser('estudiante');
        $otro->especialidades()->attach($caso['clinic']->id, ['habilitado' => true]);
        $otraAdmision = $caso['admission']->replicate();
        $otraAdmision->fill(['estudiante_id' => $otro->id, 'antecedentes' => 'ANTECEDENTE AJENO RESERVADO'])->save();
        $otraHistoria = $caso['history']->replicate();
        $otraHistoria->fill(['admision_id' => $otraAdmision->id, 'estudiante_id' => $otro->id])->save();

        $respuesta = $this->actingAs($caso['student'])->get(route('admisiones.reporte-paciente', $caso['admission']->id));

        $respuesta->assertOk()->assertDontSeeText('ANTECEDENTE AJENO RESERVADO')
            ->assertViewHas('admisiones', fn ($registros) => $registros->modelKeys() === [$caso['admission']->id])
            ->assertViewHas('historias', fn ($registros) => $registros->modelKeys() === [$caso['history']->id]);
        $this->assertModelExists($otraHistoria);
    }

    public function test_solo_se_incluyen_las_secciones_autorizadas_para_el_usuario(): void
    {
        $caso = $this->clinicalCase();
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);
        $usuario->givePermissionTo('admisiones.ver');

        $respuesta = $this->actingAs($usuario)->get(route('admisiones.reporte-paciente', $caso['admission']->id));

        $respuesta->assertOk()->assertDontSeeText(['Pagos del paciente', 'Historias clínicas y evolución', 'Estudios radiológicos'])
            ->assertViewHas('historias', fn ($registros) => $registros->isEmpty())
            ->assertViewHas('pagos', fn ($registros) => $registros->isEmpty());
    }

    public function test_el_reporte_excluye_recibos_ajenos_anulados_y_ci_cero_y_admite_el_vinculo_por_detalle(): void
    {
        $caso = $this->approvedProgress();
        $otro = $this->clinicalCase();
        $propio = $this->crearPago($caso, ['cancelado' => 40]);
        $legado = $this->crearPago($caso, ['paciente_id' => null, 'cancelado' => 20]);
        CajaDetalle::create(['caja_id' => $legado->id, 'tratamiento_realizado_id' => $caso['realized']->id, 'concepto' => 'Pago legado', 'cantidad' => 1, 'precio_unitario' => 20, 'monto' => 20]);
        $this->crearPago($otro, ['cancelado' => 999]);
        $this->crearPago($caso, ['paciente_id' => null, 'cancelado' => 888]);
        $this->crearPago($caso, ['habilitada' => false, 'cancelado' => 777]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', $caso['admission']->id));

        $respuesta->assertOk()->assertViewHas('pagos', fn ($registros) => $registros->modelKeys() === [$legado->id, $propio->id]);
    }

    public function test_los_datos_clinicos_se_muestran_escapados(): void
    {
        $caso = $this->clinicalCase();
        $texto = '<script>alert("dato clínico")</script>';
        $caso['admission']->update(['antecedentes' => $texto]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte-paciente', $caso['admission']->id));

        $respuesta->assertSee($texto)->assertDontSee($texto, false);
    }

    public function test_el_reporte_por_paciente_requiere_iniciar_sesion(): void
    {
        $caso = $this->clinicalCase();

        $this->get(route('admisiones.reporte-paciente', $caso['admission']->id))->assertRedirect(route('auth.login'));
    }

    #[DataProvider('modulos')]
    public function test_los_reportes_de_modulos_rechazan_usuarios_sin_permisos(string $modulo): void
    {
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);

        $this->actingAs($usuario)->get(route($modulo.'.reporte'))->assertForbidden();
    }

    public function test_el_reporte_por_paciente_rechaza_usuarios_sin_permiso_de_admisiones(): void
    {
        $caso = $this->clinicalCase();
        $usuario = $this->clinicalUser('admision');
        $usuario->syncRoles([]);

        $this->actingAs($usuario)->get(route('admisiones.reporte-paciente', $caso['admission']->id))->assertForbidden();
    }

    public function test_el_reporte_de_historias_y_su_tabla_respetan_busqueda_especialidad_y_periodo(): void
    {
        $caso = $this->clinicalCase();
        $this->clinicalCase();
        $filtros = ['buscar' => $caso['admission']->paciente->ci, 'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id];

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('historias.reporte', $filtros));

        $respuesta->assertOk()->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && $filas->first()[0] === (string) $caso['history']->id);
        $this->getJson(route('historias.tabla', $filtros))->assertJsonPath('total', 1);
    }

    public function test_el_reporte_de_admisiones_respeta_la_busqueda_y_el_acceso_del_estudiante(): void
    {
        $caso = $this->clinicalCase();
        $this->clinicalCase();
        $caso['student']->givePermissionTo('admisiones.ver');

        $respuesta = $this->actingAs($caso['student'])->get(route('admisiones.reporte', ['buscar' => $caso['admission']->paciente->ci]));

        $respuesta->assertOk()->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && $filas->first()[0] === (string) $caso['admission']->id);
    }

    public function test_el_reporte_de_cajas_calcula_totales_filtrados_sin_comprobantes_anulados(): void
    {
        $caso = $this->clinicalCase();
        $this->crearPago($caso, ['costo_total' => 100, 'cancelado' => 40]);
        $this->crearPago($caso, ['costo_total' => 200, 'cancelado' => 100]);
        $this->crearPago($caso, ['costo_total' => 999, 'cancelado' => 999, 'habilitada' => false]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('cajas.reporte', ['clinica_id' => $caso['clinic']->id]));

        $respuesta->assertOk()->assertViewHas('resumen', [
            'Registros encontrados' => '3', 'Registros incluidos' => '3',
            'Costo vigente Bs' => '300.00', 'Pagado Bs' => '140.00', 'Saldo Bs' => '160.00',
        ]);
    }

    public function test_el_reporte_de_inventario_filtra_stock_bajo_y_calcula_su_valor(): void
    {
        $caso = $this->clinicalCase();
        $tipo = InsumoTratamiento::create(['clinica_id' => $caso['clinic']->id, 'nombre' => 'Tipo prueba']);
        Insumo::create(['nombre' => 'Material bajo', 'clinica_id' => $caso['clinic']->id, 'insumo_tratamiento_id' => $tipo->id, 'cantidad_numerica' => 2, 'stock_minimo' => 5, 'costo_unitario' => 7]);
        Insumo::create(['nombre' => 'Material suficiente', 'clinica_id' => $caso['clinic']->id, 'insumo_tratamiento_id' => $tipo->id, 'cantidad_numerica' => 10, 'stock_minimo' => 5, 'costo_unitario' => 3]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('insumos.reporte', ['clinica_id' => $caso['clinic']->id, 'stock_bajo' => 1]));

        $respuesta->assertOk()->assertSeeText('Material bajo')->assertDontSeeText('Material suficiente')
            ->assertViewHas('resumen', ['Registros encontrados' => '1', 'Registros incluidos' => '1', 'Con stock bajo' => '1', 'Valor del inventario Bs' => '14.00']);
    }

    public function test_un_reporte_sin_coincidencias_muestra_un_estado_vacio(): void
    {
        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte', ['buscar' => 'SIN-COINCIDENCIAS-'.fake()->uuid()]));

        $respuesta->assertSeeText('No hay registros para los filtros seleccionados.');
    }

    public function test_el_reporte_rechaza_fechas_invalidas(): void
    {
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.reporte', ['desde' => 'no-es-fecha']))
            ->assertUnprocessable()->assertJsonValidationErrors('desde');
    }

    public function test_un_reporte_limitado_avisa_y_mantiene_el_total_de_registros_filtrados(): void
    {
        $caso = $this->clinicalCase();
        $remision = Remision::create(['admision_id' => $caso['admission']->id, 'paciente_id' => $caso['admission']->paciente_id, 'clinica_id' => $caso['clinic']->id, 'periodo_id' => $caso['history']->periodo_id]);
        Remision::insert(array_fill(0, 500, Arr::except($remision->getAttributes(), ['id'])));

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('remisiones.reporte', ['clinica_id' => $caso['clinic']->id]));

        $respuesta->assertOk()->assertSeeText('Se incluyen los primeros 500 registros.')
            ->assertViewHas('limitado', true)->assertViewHas('filas', fn ($filas) => $filas->count() === 500)
            ->assertViewHas('resumen', ['Registros encontrados' => '501', 'Registros incluidos' => '500']);
    }

    public function test_el_reporte_rechaza_un_rango_de_fechas_invertido(): void
    {
        $this->actingAs($this->clinicalUser('admin'))->getJson(route('admisiones.reporte', ['desde' => '2026-10-05', 'hasta' => '2026-10-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }

    public function test_el_reporte_de_tabla_escapa_tanto_los_datos_como_los_filtros(): void
    {
        $caso = $this->clinicalCase();
        $texto = '<img src=x onerror=alert(1)>';
        $caso['admission']->paciente->update(['nombre' => $texto]);

        $respuesta = $this->actingAs($this->clinicalUser('admin'))->get(route('admisiones.reporte', ['buscar' => $texto]));

        $respuesta->assertSee($texto)->assertDontSee($texto, false)
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 1);
    }

    /** @param array<string, mixed> $caso @param array<string, mixed> $cambios */
    private function crearPago(array $caso, array $cambios = []): Caja
    {
        return Caja::create(array_merge([
            'paciente_id' => $caso['admission']->paciente_id, 'recibo' => (int) Caja::max('recibo') + 1,
            'monto' => 100, 'costo_total' => 100, 'cancelado' => 100, 'habilitada' => true,
            'descripcion' => 'Concepto de prueba', 'ci' => 0, 'tipo' => 'tratamiento', 'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => $caso['admission']->paciente->nombre_completo,
            'grado' => 'adulto', 'clinica_id' => $caso['clinic']->id, 'usuario_id' => $caso['teacher']->id, 'tratamiento_id' => $caso['treatment']->id,
        ], $cambios));
    }
}
