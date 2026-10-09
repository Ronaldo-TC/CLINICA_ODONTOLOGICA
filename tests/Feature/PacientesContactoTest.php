<?php

namespace Tests\Feature;

use App\Models\Paciente;
use Database\Seeders\PacientesContactoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class PacientesContactoTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    private const CONTACTO = [
        'telefono' => '+591 070000001',
        'estado_civil' => 'Soltero',
        'escuela' => 'Escuela de prueba',
        'ocupacion' => 'Estudiante',
        'direccion' => 'El Alto',
        'emergencia' => 'Familiar responsable',
        'telefono_emergencia' => '007000002',
    ];

    public function test_puede_registrar_consultar_y_editar_los_datos_de_contacto_del_paciente(): void
    {
        $this->actingAs($this->clinicalUser('admin'));
        $respuesta = $this->postJson(route('pacientes.store'), ['nombre' => 'Paciente contacto', ...self::CONTACTO])->assertCreated();
        $id = $respuesta->json('id');

        $consulta = $this->getJson(route('pacientes.datos', $id))->assertOk();
        foreach (self::CONTACTO as $campo => $valor) {
            $consulta->assertJsonPath('registro.'.$campo, $valor);
        }
        $this->assertDatabaseHas('pacientes', ['id' => $id, ...self::CONTACTO]);

        $this->putJson(route('pacientes.update', $id), ['nombre' => 'Paciente contacto', 'telefono' => '007000003', 'direccion' => 'La Paz', 'emergencia' => ''])
            ->assertOk();
        $this->assertDatabaseHas('pacientes', ['id' => $id, 'telefono' => '007000003', 'direccion' => 'La Paz', 'emergencia' => null, 'telefono_emergencia' => self::CONTACTO['telefono_emergencia']]);
    }

    public function test_los_contactos_son_opcionales_y_validan_la_longitud_sin_crear_pacientes_invalidos(): void
    {
        $this->actingAs($this->clinicalUser('admin'));
        $this->postJson(route('pacientes.store'), ['nombre' => 'Paciente sin contacto'])->assertCreated()
            ->assertJsonPath('registro.telefono', null)->assertJsonPath('registro.emergencia', null);

        $cantidad = Paciente::count();
        $invalidos = array_map(fn (string $campo): string => str_repeat('x', str_starts_with($campo, 'telefono') ? 31 : 201), Paciente::CAMPOS_CONTACTO);
        $this->postJson(route('pacientes.store'), ['nombre' => 'Paciente invalido', ...array_combine(Paciente::CAMPOS_CONTACTO, $invalidos)])
            ->assertUnprocessable()->assertJsonValidationErrors(Paciente::CAMPOS_CONTACTO);
        $this->assertSame($cantidad, Paciente::count());
    }

    public function test_los_campos_se_registran_en_pacientes_y_no_se_muestran_al_crear_admisiones(): void
    {
        $this->actingAs($this->clinicalUser('admin'));
        $pacientes = $this->get(route('pacientes.index'))->assertOk();
        $creacion = $this->get(route('admisiones.create'))->assertOk();
        $creacion3d = $this->get(route('admisiones.create-3d'))->assertOk();
        foreach (Paciente::CAMPOS_CONTACTO as $campo) {
            if ($campo === 'escuela') {
                $pacientes->assertDontSee('name="'.$campo.'"', false);
            } else {
                $pacientes->assertSee('name="'.$campo.'"', false);
            }
            $creacion->assertDontSee('name="'.$campo.'"', false);
            $creacion3d->assertDontSee('name="'.$campo.'"', false);
        }
    }

    public function test_una_nueva_admision_toma_el_contacto_del_paciente_y_conserva_su_copia_historica(): void
    {
        $caso = $this->clinicalCase();
        $paciente = $caso['admission']->paciente;
        $paciente->update(self::CONTACTO);
        $this->actingAs($this->clinicalUser('admin'));
        $respuesta = $this->postJson(route('admisiones.store'), ['paciente_id' => $paciente->id, 'gpe' => 'Bueno'])->assertCreated();
        $id = $respuesta->json('id');
        $this->assertDatabaseHas('admisiones', ['id' => $id, ...self::CONTACTO]);

        $this->putJson(route('pacientes.update', $paciente->id), ['nombre' => $paciente->nombre, 'telefono' => '007000004', 'direccion' => 'Cochabamba'])->assertOk();
        $this->assertDatabaseHas('admisiones', ['id' => $id, ...self::CONTACTO]);
        $edicion = $this->get(route('admisiones.edit', $id))->assertOk();
        foreach (Paciente::CAMPOS_CONTACTO as $campo) {
            $edicion->assertSee('name="'.$campo.'"', false);
        }
    }

    public function test_la_migracion_recupera_los_ultimos_datos_existentes_sin_sobrescribir_el_paciente(): void
    {
        $caso = $this->clinicalCase();
        $paciente = $caso['admission']->paciente;
        $paciente->update(['direccion' => 'Direccion del paciente']);
        $anterior = $caso['admission'];
        $anterior->update([...self::CONTACTO, 'fecha_admision' => '2024-01-01']);
        $reciente = $anterior->replicate();
        $reciente->fill(['fecha_admision' => '2025-01-01', 'telefono' => '007000005', 'emergencia' => '', 'direccion' => 'Direccion antigua']);
        $reciente->save();

        $this->seed(PacientesContactoSeeder::class);
        $this->seed(PacientesContactoSeeder::class);
        $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, ...self::CONTACTO, 'telefono' => '007000005', 'direccion' => 'Direccion del paciente']);
        $this->assertDatabaseHas('admisiones', ['id' => $anterior->id, ...self::CONTACTO]);
        $this->assertDatabaseHas('admisiones', ['id' => $reciente->id, 'emergencia' => '', 'telefono' => '007000005']);
    }
}
