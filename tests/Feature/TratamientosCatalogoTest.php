<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class TratamientosCatalogoTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_admin_creates_treatment_for_clinic_without_price(): void
    {
        $clinic = $this->clinic();
        $response = $this->actingAs($this->clinicalUser('admin'))->postJson(route('tratamientos.store'), [
            'clinica_id' => $clinic->id, 'nombre' => 'Restauración',
        ])->assertCreated()->assertJsonPath('registro.precio_asignado', false);

        $this->assertDatabaseHas('tratamientos', ['id' => $response->json('id'), 'clinica_id' => $clinic->id, 'nombre' => 'Restauración', 'costo' => 0, 'precio_asignado' => false]);
        $this->get(route('tratamientos.index', ['clinica_id' => $clinic->id]))->assertOk()->assertSee('Precio pendiente');
    }

    public function test_treatment_editor_can_set_cost_when_creating_and_editing_a_treatment(): void
    {
        $clinic = $this->clinic();
        $this->actingAs($this->clinicalUser('admin'));

        $response = $this->postJson(route('tratamientos.store'), [
            'clinica_id' => $clinic->id,
            'nombre' => 'Tratamiento con costo',
            'costo' => '240.50',
        ])->assertCreated()->assertJsonPath('registro.precio_asignado', true);
        $treatmentId = $response->json('id');

        $this->assertDatabaseHas('tratamientos', [
            'id' => $treatmentId,
            'costo' => 240.50,
            'precio_asignado' => true,
        ]);

        $this->putJson(route('tratamientos.update', $treatmentId), [
            'clinica_id' => $clinic->id,
            'nombre' => 'Tratamiento con costo',
            'costo' => '300.00',
        ])->assertOk();

        $this->assertDatabaseHas('tratamientos', [
            'id' => $treatmentId,
            'costo' => 300.00,
            'precio_asignado' => true,
        ]);

        $this->get(route('tratamientos.index'))->assertOk()
            ->assertSee('Costo')
            ->assertSee('name="costo"', false)
            ->assertDontSee('data-action="precio"', false);
    }

    public function test_clinic_link_filters_initial_and_ajax_catalog(): void
    {
        $first = $this->treatment($this->clinic(), 'Visible aquí');
        $other = $this->treatment($this->clinic(), 'Otra clínica');
        $this->actingAs($this->clinicalUser('admin'));

        $clinics = $this->getJson(route('clinicas.tabla', ['buscar' => $first->clinica->especialidad]))->assertOk();
        $this->assertStringContainsString(route('tratamientos.index', ['clinica_id' => $first->clinica_id]), $clinics->json('filas'));
        $this->get(route('tratamientos.index', ['clinica_id' => $first->clinica_id]))->assertOk()->assertSee('Visible aquí')->assertDontSee($other->nombre);
        $response = $this->getJson(route('tratamientos.tabla', ['clinica_id' => $first->clinica_id]))->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('Visible aquí', $response->json('filas'));
        $this->assertStringNotContainsString($other->nombre, $response->json('filas'));
    }

    public function test_name_is_unique_within_each_clinic(): void
    {
        $treatment = $this->treatment($this->clinic(), 'Restauración');
        $other = $this->clinic();
        $this->actingAs($this->clinicalUser('admin'));

        $this->postJson(route('tratamientos.store'), ['nombre' => $treatment->nombre, 'clinica_id' => $treatment->clinica_id])
            ->assertUnprocessable()->assertJsonValidationErrors('nombre')->assertJsonPath('errors.nombre.0', 'Ya existe ese tratamiento en esta clínica.');
        $this->postJson(route('tratamientos.store'), ['nombre' => $treatment->nombre, 'clinica_id' => $other->id])->assertCreated();
        $this->assertDatabaseHas('tratamientos', ['nombre' => $treatment->nombre, 'clinica_id' => $other->id]);
    }

    public function test_cashier_sets_price_without_changing_catalog_or_historical_charges(): void
    {
        $case = $this->approvedProgress();
        $case['realized']->update(['costo_cobrado' => 100]);
        $this->actingAs($this->clinicalUser('cajas'));

        $this->putJson(route('tratamientos.precio', $case['treatment']), ['costo' => '125.50', 'nombre' => 'Alterado', 'clinica_id' => 0])->assertOk();

        $this->assertDatabaseHas('tratamientos', ['id' => $case['treatment']->id, 'costo' => 125.50, 'precio_asignado' => true, 'nombre' => 'Tratamiento prueba', 'clinica_id' => $case['clinic']->id]);
        $this->assertSame('100.00', $case['realized']->fresh()->costo_cobrado);
        $this->get(route('tratamientos.show', $case['treatment']))->assertOk()->assertSee('Guardar precio')->assertDontSee('Guardar insumos');
        $this->putJson(route('tratamientos.update', $case['treatment']), ['nombre' => 'Alterado', 'clinica_id' => $case['clinic']->id])->assertForbidden();
    }

    #[TestWith([-1])]
    #[TestWith([100000000])]
    #[TestWith(['12.345'])]
    #[TestWith([null])]
    public function test_invalid_price_returns_422_without_changing_treatment(mixed $price): void
    {
        $treatment = $this->treatment($this->clinic());

        $this->actingAs($this->clinicalUser('cajas'))->putJson(route('tratamientos.precio', $treatment), ['costo' => $price])
            ->assertUnprocessable()->assertJsonValidationErrors('costo');

        $this->assertSame('100.00', $treatment->fresh()->costo);
    }

    public function test_stock_staff_assigns_updates_and_removes_materials_without_consuming_stock(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $material = $this->material($clinic);
        $this->actingAs($this->clinicalUser('insumos'));

        $this->get(route('tratamientos.index'))->assertOk()->assertSee('Tratamientos');
        $this->get(route('tratamientos.show', $treatment))->assertOk()->assertSee('Guardar insumos')->assertDontSee('Guardar precio');
        $this->putJson(route('tratamientos.insumos', $treatment), ['insumos' => [['insumo_id' => $material->id, 'cantidad' => 2.5]]])->assertOk();
        $this->assertDatabaseHas('insumo_tratamiento', ['tratamiento_id' => $treatment->id, 'insumo_id' => $material->id, 'cantidad' => 2.5]);
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
        $this->putJson(route('tratamientos.insumos', $treatment), ['insumos' => [['insumo_id' => $material->id, 'cantidad' => 3]]])->assertOk();
        $this->assertDatabaseHas('insumo_tratamiento', ['tratamiento_id' => $treatment->id, 'insumo_id' => $material->id, 'cantidad' => 3]);
        $this->put(route('tratamientos.insumos', $treatment), [])->assertRedirect(route('tratamientos.show', $treatment));
        $this->assertDatabaseMissing('insumo_tratamiento', ['tratamiento_id' => $treatment->id]);
        $this->assertSame('10.00', $material->fresh()->cantidad_numerica);
    }

    public function test_cross_clinic_material_is_rejected_and_existing_assignments_are_preserved(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $material = $this->material($clinic);
        $foreignMaterial = $this->material($this->clinic());
        $treatment->insumos()->attach($material, ['cantidad' => 2]);

        $response = $this->actingAs($this->clinicalUser('insumos'))->putJson(route('tratamientos.insumos', $treatment), ['insumos' => [['insumo_id' => $foreignMaterial->id, 'cantidad' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('insumos.0.insumo_id');
        $this->assertSame('Todos los insumos deben pertenecer a la misma clínica del tratamiento.', $response->json('errors')['insumos.0.insumo_id'][0]);

        $this->assertDatabaseHas('insumo_tratamiento', ['tratamiento_id' => $treatment->id, 'insumo_id' => $material->id, 'cantidad' => 2]);
        $this->assertDatabaseMissing('insumo_tratamiento', ['tratamiento_id' => $treatment->id, 'insumo_id' => $foreignMaterial->id]);
    }

    public function test_duplicate_materials_zero_quantities_and_missing_configuration_return_422(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $material = $this->material($clinic);
        $this->actingAs($this->clinicalUser('insumos'));

        $this->putJson(route('tratamientos.insumos', $treatment), ['insumos' => [['insumo_id' => $material->id, 'cantidad' => 1], ['insumo_id' => $material->id, 'cantidad' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('insumos.0.insumo_id');
        $response = $this->putJson(route('tratamientos.insumos', $treatment), ['insumos' => [['insumo_id' => $material->id, 'cantidad' => 0]]])
            ->assertUnprocessable()->assertJsonValidationErrors('insumos.0.cantidad');
        $this->assertSame('La cantidad debe ser mayor que cero.', $response->json('errors')['insumos.0.cantidad'][0]);
        $this->putJson(route('tratamientos.insumos', $treatment), [])->assertUnprocessable();
        $this->assertDatabaseMissing('insumo_tratamiento', ['tratamiento_id' => $treatment->id]);
    }

    public function test_responsibilities_require_their_own_permissions(): void
    {
        $treatment = $this->treatment($this->clinic());
        $this->actingAs($this->clinicalUser('insumos'))->putJson(route('tratamientos.precio', $treatment), ['costo' => 1])->assertForbidden();
        $this->actingAs($this->clinicalUser('cajas'))->putJson(route('tratamientos.insumos', $treatment), ['insumos' => []])->assertForbidden();
        $this->actingAs($this->clinicalUser('estudiante'))->postJson(route('tratamientos.store'), ['nombre' => 'Nuevo', 'clinica_id' => $treatment->clinica_id])->assertForbidden();
        $this->assertSame('100.00', $treatment->fresh()->costo);
    }

    public function test_catalog_editor_can_change_price_through_generic_update(): void
    {
        $treatment = $this->treatment($this->clinic());
        $this->actingAs($this->clinicalUser('subadmin'));

        $this->putJson(route('tratamientos.update', $treatment), ['nombre' => $treatment->nombre, 'clinica_id' => $treatment->clinica_id, 'costo' => 1])->assertOk();
        $this->assertDatabaseHas('tratamientos', ['id' => $treatment->id, 'costo' => 1, 'precio_asignado' => true]);
        $this->putJson(route('tratamientos.update', $treatment), ['nombre' => 'Nombre actualizado', 'clinica_id' => $treatment->clinica_id])->assertOk();
        $this->assertDatabaseHas('tratamientos', ['id' => $treatment->id, 'nombre' => 'Nombre actualizado', 'costo' => 1, 'precio_asignado' => true]);
    }

    public function test_assigned_treatment_and_material_cannot_move_to_another_clinic(): void
    {
        $clinic = $this->clinic();
        $other = $this->clinic();
        $treatment = $this->treatment($clinic);
        $material = $this->material($clinic);
        $treatment->insumos()->attach($material, ['cantidad' => 1]);
        $this->actingAs($this->clinicalUser('admin'));

        $this->putJson(route('tratamientos.update', $treatment), ['nombre' => $treatment->nombre, 'clinica_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
        $this->putJson(route('insumos.update', $material), [...$material->only(['nombre', 'cantidad', 'cantidad_numerica', 'stock_minimo', 'costo_unitario']), 'clinica_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('clinica_id');
        $this->assertSame($clinic->id, $treatment->fresh()->clinica_id);
        $this->assertSame($clinic->id, $material->fresh()->clinica_id);
    }

    public function test_stock_staff_registers_materials_in_new_clinic_without_existing_categories(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $this->actingAs($this->clinicalUser('insumos'));
        $data = ['clinica_id' => $clinic->id, 'tratamiento_id' => $treatment->id, 'nombre' => 'Guantes', 'cantidad_numerica' => 10, 'stock_minimo' => 1, 'costo_unitario' => 2];

        $this->get(route('insumos.index'))->assertOk()->assertSee('id="btnNuevoInsumo"', false);

        $first = $this->postJson(route('insumos.store'), $data)->assertCreated();
        $this->postJson(route('insumos.store'), [...$data, 'nombre' => 'Gasas'])->assertCreated();

        $this->assertDatabaseHas('insumos_tratamiento', ['id' => $first->json('registro.insumo_tratamiento_id'), 'clinica_id' => $clinic->id, 'nombre' => 'General']);
        $this->assertSame(1, InsumoTratamiento::query()->where('clinica_id', $clinic->id)->count());
        $this->assertDatabaseHas('insumos', ['id' => $first->json('id'), 'clinica_id' => $clinic->id]);
    }

    public function test_material_category_must_belong_to_its_clinic(): void
    {
        $clinic = $this->clinic();
        $foreign = $this->material($this->clinic());

        $this->actingAs($this->clinicalUser('insumos'))->postJson(route('insumos.store'), [
            'clinica_id' => $clinic->id, 'insumo_tratamiento_id' => $foreign->insumo_tratamiento_id,
            'nombre' => 'Material inválido', 'cantidad_numerica' => 1, 'stock_minimo' => 0, 'costo_unitario' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('insumo_tratamiento_id');

        $this->assertDatabaseMissing('insumos', ['clinica_id' => $clinic->id, 'nombre' => 'Material inválido']);
    }

    public function test_material_form_loads_only_treatments_from_selected_clinic(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $this->treatment($this->clinic(), 'Tratamiento de otra clínica');
        $this->actingAs($this->clinicalUser('insumos'));

        $this->getJson(route('insumos.tratamientos-por-clinica', $clinic))->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $treatment->id);
        $this->getJson(route('insumos.tratamientos-por-clinica', 999999999))->assertNotFound();
        $this->get(route('insumos.index'))->assertOk()->assertSee('Insumo para el tratamiento')
            ->assertSee('Medida Volumétrica')->assertSee('name="tratamiento_id"', false)
            ->assertDontSee('name="insumo_tratamiento_id"', false);
    }

    public function test_registering_material_saves_stock_and_links_selected_treatment(): void
    {
        $case = $this->clinicalCase();
        $this->paidTreatment($case);
        $this->actingAs($this->clinicalUser('insumos'));
        $data = [
            'clinica_id' => $case['clinic']->id, 'tratamiento_id' => $case['treatment']->id,
            'nombre' => 'Resina clínica', 'cantidad_numerica' => 25.5, 'cantidad' => 'ml', 'stock_minimo' => 3, 'costo_unitario' => 8.5,
        ];

        $id = $this->postJson(route('insumos.store'), $data)->assertCreated()->json('id');

        $this->assertDatabaseHas('insumos', ['id' => $id, ...collect($data)->except('tratamiento_id')->all()]);
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $id, 'tratamiento_id' => $case['treatment']->id, 'cantidad' => 1]);
        $this->getJson(route('insumos.datos', $id))->assertOk()->assertJsonPath('registro.cantidad', 'ml')
            ->assertJsonPath('tratamientosAsignados.0.id', $case['treatment']->id);
        $this->getJson(route('insumos.tabla', ['buscar' => $case['treatment']->nombre]))->assertOk()->assertJsonPath('total', 1)
            ->assertSee($case['treatment']->nombre);
        $this->actingAs($case['student'])->getJson(route('solicitudes-insumos.catalogo-designado', ['historia_clinica_id' => $case['history']->id]))
            ->assertOk()->assertJsonPath('tratamientos.0.insumos.0.id', $id)->assertJsonPath('tratamientos.0.insumos.0.stock', '25.50');
        $this->postJson(route('solicitudes-insumos.store'), [
            'historia_clinica_id' => $case['history']->id, 'clinica_id' => $case['clinic']->id,
            'tratamiento_id' => $case['treatment']->id, 'detalles' => [['insumo_id' => $id, 'cantidad_solicitada' => 2]],
        ])->assertCreated();
        $this->assertSame('25.50', Insumo::findOrFail($id)->cantidad_numerica);
    }

    public function test_new_material_requires_a_treatment_from_the_selected_clinic(): void
    {
        $clinic = $this->clinic();
        $foreign = $this->treatment($this->clinic());
        $data = ['clinica_id' => $clinic->id, 'nombre' => 'Material sin vínculo', 'cantidad_numerica' => 10, 'stock_minimo' => 1, 'costo_unitario' => 2];
        $this->actingAs($this->clinicalUser('insumos'));

        $this->postJson(route('insumos.store'), $data)->assertUnprocessable()
            ->assertJsonValidationErrors('tratamiento_id')->assertJsonPath('errors.tratamiento_id.0', 'Debe seleccionar el tratamiento para el insumo.');
        $this->postJson(route('insumos.store'), [...$data, 'tratamiento_id' => $foreign->id])->assertUnprocessable()
            ->assertJsonValidationErrors('tratamiento_id')->assertJsonPath('errors.tratamiento_id.0', 'El tratamiento debe pertenecer a la clínica seleccionada.');
        $this->assertDatabaseMissing('insumos', ['clinica_id' => $clinic->id, 'nombre' => 'Material sin vínculo']);
        $this->assertDatabaseMissing('insumos_tratamiento', ['clinica_id' => $clinic->id]);
    }

    public function test_editing_material_changes_single_treatment_without_resetting_configured_quantities(): void
    {
        $clinic = $this->clinic();
        $first = $this->treatment($clinic, 'Primero');
        $second = $this->treatment($clinic, 'Segundo');
        $material = $this->material($clinic);
        $first->insumos()->attach($material, ['cantidad' => 2.5]);
        $data = [...$material->only(['nombre', 'clinica_id', 'cantidad', 'cantidad_numerica', 'stock_minimo', 'costo_unitario']), 'tratamiento_id' => $second->id];
        $this->actingAs($this->clinicalUser('insumos'));

        $this->putJson(route('insumos.update', $material), $data)->assertOk();

        $this->assertDatabaseMissing('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $first->id]);
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $second->id, 'cantidad' => 1]);
        $second->insumos()->updateExistingPivot($material->id, ['cantidad' => 3.5]);
        $this->putJson(route('insumos.update', $material), [...$data, 'cantidad_numerica' => 15])->assertOk();
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $second->id, 'cantidad' => 3.5]);
        $this->assertSame('15.00', $material->fresh()->cantidad_numerica);
        $this->assertDatabaseHas('insumos', ['id' => $material->id, 'insumo_tratamiento_id' => $material->insumo_tratamiento_id]);
    }

    public function test_editing_shared_material_preserves_all_existing_treatments_and_their_quantities(): void
    {
        $clinic = $this->clinic();
        $first = $this->treatment($clinic, 'Primero');
        $second = $this->treatment($clinic, 'Segundo');
        $third = $this->treatment($clinic, 'Tercero');
        $material = $this->material($clinic);
        $material->tratamientos()->attach([$first->id => ['cantidad' => 2], $second->id => ['cantidad' => 3]]);
        $data = $material->only(['nombre', 'clinica_id', 'cantidad', 'cantidad_numerica', 'stock_minimo', 'costo_unitario']);
        $this->actingAs($this->clinicalUser('insumos'));

        $this->putJson(route('insumos.update', $material), [...$data, 'tratamiento_id' => null])->assertOk();
        $this->assertSame(2, $material->tratamientos()->count());
        $this->putJson(route('insumos.update', $material), [...$data, 'tratamiento_id' => $third->id])->assertOk();

        $this->assertSame(3, $material->tratamientos()->count());
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $first->id, 'cantidad' => 2]);
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $second->id, 'cantidad' => 3]);
        $this->assertDatabaseHas('insumo_tratamiento', ['insumo_id' => $material->id, 'tratamiento_id' => $third->id, 'cantidad' => 1]);
    }

    public function test_non_stock_staff_cannot_create_material_even_with_create_permission(): void
    {
        $case = $this->clinicalCase();
        $case['student']->givePermissionTo('insumos.crear');

        $this->actingAs($case['student'])->postJson(route('insumos.store'), [
            'clinica_id' => $case['clinic']->id, 'tratamiento_id' => $case['treatment']->id,
            'nombre' => 'Material prohibido', 'cantidad_numerica' => 10, 'stock_minimo' => 1, 'costo_unitario' => 2,
        ])->assertForbidden();

        $this->assertDatabaseMissing('insumos', ['clinica_id' => $case['clinic']->id, 'nombre' => 'Material prohibido']);
    }

    public function test_pending_price_blocks_attention_until_cashier_sets_it_including_zero_price(): void
    {
        $case = $this->clinicalCase();
        $case['treatment']->update(['precio_asignado' => false, 'costo' => 0]);
        $data = ['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'pieza_dental' => '16'];

        $this->actingAs($case['student'])->postJson(route('tratamientos-realizados.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('tratamiento_id');
        $this->assertDatabaseMissing('tratamientos_realizados', ['tratamiento_id' => $case['treatment']->id]);
        $this->actingAs($this->clinicalUser('cajas'))->putJson(route('tratamientos.precio', $case['treatment']), ['costo' => 0])->assertOk();
        $this->actingAs($case['student'])->postJson(route('tratamientos-realizados.store'), $data)->assertCreated();
        $this->assertDatabaseHas('tratamientos_realizados', ['tratamiento_id' => $case['treatment']->id, 'costo_cobrado' => 0]);
    }

    public function test_detail_escapes_catalog_names_and_requires_authentication(): void
    {
        $treatment = $this->treatment($this->clinic(), '<script>alert(1)</script>');
        $this->get(route('tratamientos.show', $treatment))->assertRedirect(route('auth.login'));

        $this->actingAs($this->clinicalUser('admin'))->get(route('tratamientos.show', $treatment))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_view_price_and_supplies_actions_open_modal_content(): void
    {
        $clinic = $this->clinic();
        $treatment = $this->treatment($clinic);
        $material = $this->material($clinic);
        $treatment->insumos()->attach($material, ['cantidad' => 2]);

        $this->actingAs($this->clinicalUser('admin'))->get(route('tratamientos.index'))
            ->assertOk()
            ->assertSee('id="modalConfiguracionTratamiento"', false)
            ->assertSee('data-action="ver"', false);

        $this->getJson(route('tratamientos.show', ['tratamiento' => $treatment, 'modal' => 'ver']))
            ->assertOk()
            ->assertJsonPath('titulo', 'Detalle del tratamiento')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, $treatment->nombre)
                && str_contains($html, $material->nombre));

        $this->actingAs($this->clinicalUser('cajas'))
            ->getJson(route('tratamientos.show', ['tratamiento' => $treatment, 'modal' => 'precio']))
            ->assertOk()
            ->assertJsonPath('titulo', 'Precio del tratamiento')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'data-modo="precio"')
                && str_contains($html, 'name="costo"')
                && ! str_contains($html, 'porcentaje_pg'));

        $this->actingAs($this->clinicalUser('insumos'))
            ->getJson(route('tratamientos.show', ['tratamiento' => $treatment, 'modal' => 'insumos']))
            ->assertOk()
            ->assertJsonPath('titulo', 'Insumos del tratamiento')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'data-modo="insumos"')
                && str_contains($html, 'data-insumo-row'));

        $this->assertFalse(Schema::hasColumn('tratamientos', 'porcentaje_pg'));
    }

    private function clinic(): Clinica
    {
        return Clinica::create(['especialidad' => fake()->unique()->bothify('Clínica catálogo #### ????'), 'modulo' => 'Prueba', 'curso' => 1]);
    }

    private function treatment(Clinica $clinic, string $name = 'Tratamiento catálogo'): Tratamiento
    {
        return Tratamiento::create([
            'clinica_id' => $clinic->id,
            'nombre' => $name,
            'costo' => 100,
            'precio_asignado' => true,
        ]);
    }

    private function material(Clinica $clinic): Insumo
    {
        $type = InsumoTratamiento::create(['clinica_id' => $clinic->id, 'nombre' => 'Material dental']);

        return Insumo::create(['clinica_id' => $clinic->id, 'insumo_tratamiento_id' => $type->id, 'nombre' => 'Guantes', 'cantidad' => 'pares', 'cantidad_numerica' => 10, 'stock_minimo' => 1, 'costo_unitario' => 2]);
    }
}
