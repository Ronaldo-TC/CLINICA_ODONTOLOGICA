<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Firma digital del paciente: render del componente y persistencia
 * del data URL en admisiones.firma.
 */
class FirmaDigitalTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
    }

    /** @return array{0:int,1:int} */
    private function parEspecialidad(string $rol): array
    {
        $fila = DB::table('especialidades as e')
            ->join('usuarios as u', 'u.id', '=', 'e.usuario_id')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')->where('mhr.model_type', '=', Usuario::class);
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

    public function test_el_componente_de_firma_renderiza_canvas_y_campo(): void
    {
        $html = view('components.firma-digital', [
            'nombre' => 'firma',
            'valor'  => null,
            'id'     => 'firma-test',
            'alto'   => 180,
        ])->render();

        $this->assertStringContainsString('data-firma', $html);
        $this->assertStringContainsString('data-firma-canvas', $html);
        $this->assertStringContainsString('name="firma"', $html);
        $this->assertStringContainsString('data-firma-limpiar', $html);
        $this->assertStringContainsString('Firme aquí', $html);
    }

    public function test_la_firma_se_guarda_y_se_recupera(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        [$estudianteId, $clinicaEst] = $this->parEspecialidad('estudiante');
        [$docenteId, $clinicaDoc]    = $this->parEspecialidad('docente');

        $paciente = Paciente::create([
            'ci'     => '9200001',
            'nombre' => 'Paciente',
            'apellido_paterno' => 'Firma',
        ]);

        $periodo = Periodo::query()->orderBy('id')->firstOrFail();

        // PNG 1x1 válido en base64 (simula lo que produce el canvas)
        $firma = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8AAAwAB/AF+7g0dAAAAAElFTkSuQmCC';

        $respuesta = $this->actingAs($admin)->postJson(route('admisiones.store'), [
            'paciente_id'              => $paciente->id,
            'periodo_id'               => $periodo->id,
            'estudiante_id'            => $estudianteId,
            'especialidad_estudiante'  => $clinicaEst,
            'responsable_id'           => $docenteId,
            'especialidad_responsable' => $clinicaDoc,
            'firma'                    => $firma,
        ]);

        $respuesta->assertStatus(201);

        $admision = Admision::findOrFail($respuesta->json('id'));

        $this->assertSame($firma, $admision->firma);
        $this->assertStringStartsWith('data:image/png;base64,', (string) $admision->firma);

        // Los datos de la admisión para el modal incluyen la firma
        $datos = $this->actingAs($admin)->getJson(route('admisiones.datos', ['admision' => $admision->id]));

        $datos->assertOk();
        $this->assertSame($firma, $datos->json('registro.firma'));
    }

    public function test_una_firma_demasiado_grande_se_rechaza(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        [$estudianteId, $clinicaEst] = $this->parEspecialidad('estudiante');
        [$docenteId, $clinicaDoc]    = $this->parEspecialidad('docente');

        $paciente = Paciente::create([
            'ci'     => '9200002',
            'nombre' => 'Paciente',
            'apellido_paterno' => 'FirmaGrande',
        ]);

        // ~2.1 MB de texto: supera el límite de 2.000.000 caracteres
        $firma = 'data:image/png;base64,' . str_repeat('A', 2100000);

        $this->actingAs($admin)
            ->postJson(route('admisiones.store'), [
                'paciente_id'              => $paciente->id,
                'estudiante_id'            => $estudianteId,
                'especialidad_estudiante'  => $clinicaEst,
                'responsable_id'           => $docenteId,
                'especialidad_responsable' => $clinicaDoc,
                'firma'                    => $firma,
            ])
            ->assertStatus(422);
    }
}
