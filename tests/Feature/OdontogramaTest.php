<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Odontograma interactivo: render del componente y persistencia del JSON
 * en admisiones.odontograma_pintado.
 */
class OdontogramaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioConRol(string $rol): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', $rol))->first();
    }

    /**
     * Las tablas `admisiones` e `historias_clinicas` tienen claves foráneas
     * COMPUESTAS (usuario_id, clinica_id) contra `especialidades`; hay que
     * tomar un par realmente existente.
     *
     * @return array{0:int,1:int} [usuario_id, clinica_id]
     */
    private function parEspecialidad(string $rol): array
    {
        $fila = DB::table('especialidades as e')
            ->join('usuarios as u', 'u.id', '=', 'e.usuario_id')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')
                    ->where('mhr.model_type', '=', Usuario::class);
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('r.name', $rol)
            ->select('e.usuario_id', 'e.clinica_id')
            ->first();

        if (! $fila) {
            $this->markTestSkipped("No hay especialidad para el rol {$rol}");
        }

        return [(int) $fila->usuario_id, (int) $fila->clinica_id];
    }

    public function test_el_componente_renderiza_las_piezas_adultas_e_infantiles_y_su_input(): void
    {
        $html = view('components.odontograma', [
            'nombre' => 'odontograma_pintado',
            'valor' => '{"16":{"o":"caries"}}',
            'lectura' => false,
            'id' => 'odonto-test',
            'mostrarLeyenda' => true,
        ])->render();

        // 32 piezas permanentes + 20 temporales, con 5 caras por pieza.
        $this->assertSame(52, substr_count($html, 'class="diente"'));
        $this->assertSame(260, substr_count($html, 'data-cara="'));

        // Cuadrantes esperados
        foreach ([18, 11, 21, 28, 31, 38, 41, 48, 55, 51, 61, 65, 71, 75, 81, 85] as $pieza) {
            $this->assertStringContainsString('data-pieza="'.$pieza.'"', $html);
        }

        // Input oculto con el valor previo y la leyenda de estados
        $this->assertStringContainsString('data-odontograma-input', $html);
        $this->assertStringContainsString('odontograma_pintado', $html);
        $this->assertStringContainsString('Endodoncia', $html);
        $this->assertStringContainsString('Obturación', $html);
    }

    public function test_el_odontograma_se_integra_en_el_modulo_de_admisiones(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        // El JS viaja por @push('scripts'); en la página completa debe existir.
        $respuesta = $this->actingAs($admin)->get(route('admisiones.create'));

        $respuesta->assertOk();
        $respuesta->assertSee('data-odontograma', false);
        $respuesta->assertSee('window.Odontograma', false);
        $respuesta->assertSee('adm-odontograma', false);
    }

    public function test_el_componente_en_modo_lectura_desactiva_el_input(): void
    {
        $html = view('components.odontograma', [
            'nombre' => 'odontograma_pintado',
            'valor' => '{}',
            'lectura' => true,
            'id' => 'odonto-lectura',
        ])->render();

        $this->assertStringContainsString('data-lectura="1"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('sólo lectura', $html);
    }

    public function test_la_admision_guarda_el_odontograma_normalizado(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        [$estudianteId, $clinicaEstudiante] = $this->parEspecialidad('estudiante');
        [$docenteId, $clinicaDocente] = $this->parEspecialidad('docente');

        $paciente = Paciente::create([
            'ci' => '9000001',
            'nombre' => 'Paciente',
            'apellido_paterno' => 'Odontograma',
            'genero' => 'Masculino',
            'fecha_nacimiento' => '2000-01-01',
        ]);

        $respuesta = $this->actingAs($admin)->postJson(route('admisiones.store'), [
            'paciente_id' => $paciente->id,
            'gpe' => 'Bueno',
            'estudiante_id' => $estudianteId,
            'especialidad_estudiante' => $clinicaEstudiante,
            'responsable_id' => $docenteId,
            'especialidad_responsable' => $clinicaDocente,
            'odontograma_pintado' => json_encode([
                '16' => ['o' => 'caries', 'v' => 'obturado'],
                '36' => ['m' => 'endodoncia'],
                '99' => ['o' => 'caries'],      // pieza inválida → se descarta
                '11' => ['z' => 'caries'],      // cara inválida → se descarta
            ]),
        ]);

        $respuesta->assertStatus(201);

        $admision = Admision::findOrFail($respuesta->json('id'));
        $guardado = json_decode($admision->odontograma_pintado, true);

        $this->assertIsArray($guardado);
        $this->assertArrayHasKey('16', $guardado);
        $this->assertSame('caries', $guardado['16']['o']);
        $this->assertSame('obturado', $guardado['16']['v']);
        $this->assertArrayHasKey('36', $guardado);
        $this->assertArrayNotHasKey('99', $guardado, 'Las piezas fuera del rango FDI se descartan');
        $this->assertArrayNotHasKey('11', $guardado, 'Las caras desconocidas se descartan');
    }

    public function test_un_odontograma_invalido_se_guarda_vacio(): void
    {
        $admin = $this->usuarioConRol('admin');

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        [$estudianteId, $clinicaEstudiante] = $this->parEspecialidad('estudiante');
        [$docenteId, $clinicaDocente] = $this->parEspecialidad('docente');

        $paciente = Paciente::create([
            'ci' => '9000002',
            'nombre' => 'Paciente',
            'apellido_paterno' => 'SinOdontograma',
        ]);

        $admision = Admision::create([
            'paciente_id' => $paciente->id,
            'estudiante_id' => $estudianteId,
            'especialidad_estudiante' => $clinicaEstudiante,
            'responsable_id' => $docenteId,
            'especialidad_responsable' => $clinicaDocente,
        ]);

        $respuesta = $this->actingAs($admin)->putJson(route('admisiones.update', $admision->id), [
            'paciente_id' => $paciente->id,
            'estudiante_id' => $estudianteId,
            'especialidad_estudiante' => $clinicaEstudiante,
            'responsable_id' => $docenteId,
            'especialidad_responsable' => $clinicaDocente,
            'odontograma_pintado' => 'esto no es json',
        ]);

        $respuesta->assertOk();

        $this->assertSame('', (string) $admision->fresh()->odontograma_pintado);
    }
}
