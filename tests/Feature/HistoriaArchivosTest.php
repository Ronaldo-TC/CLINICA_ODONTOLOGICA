<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Archivo;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Archivos adjuntos de la historia clínica: subida con validación,
 * detección de duplicados por hash y descarga.
 */
class HistoriaArchivosTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
    }

    /**
     * Las claves foráneas compuestas exigen un par real en `especialidades`.
     *
     * @return array{0:int,1:int}
     */
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

    private function historiaDePrueba(): HistoriaClinica
    {
        [$estudianteId, $clinicaEst] = $this->parEspecialidad('estudiante');
        [$docenteId, $clinicaDoc]    = $this->parEspecialidad('docente');

        $paciente = Paciente::create([
            'ci'     => '9100001',
            'nombre' => 'Paciente',
            'apellido_paterno' => 'Archivos',
        ]);

        $periodo = Periodo::query()->orderBy('id')->firstOrFail();

        $admision = Admision::create([
            'paciente_id'              => $paciente->id,
            'periodo_id'               => $periodo->id,
            'estudiante_id'            => $estudianteId,
            'especialidad_estudiante'  => $clinicaEst,
            'responsable_id'           => $docenteId,
            'especialidad_responsable' => $clinicaDoc,
        ]);

        return HistoriaClinica::create([
            'admision_id'             => $admision->id,
            'paciente_id'             => $paciente->id,
            'clinica_id'              => $clinicaEst,
            'periodo_id'              => $periodo->id,
            'estado'                  => 'en_proceso',
            'estudiante_id'           => $estudianteId,
            'especialidad_estudiante' => $clinicaEst,
            'docente_id'              => $docenteId,
            'especialidad_docente'    => $clinicaDoc,
        ]);
    }

    public function test_sube_un_pdf_y_lo_registra_con_hash(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $historia = $this->historiaDePrueba();

        $pdf = UploadedFile::fake()->create('informe.pdf', 120, 'application/pdf');

        $respuesta = $this->actingAs($admin)->postJson(
            route('historias.archivos.subir', ['id' => $historia->id]),
            ['archivo' => $pdf]
        );

        $respuesta->assertStatus(201);
        $respuesta->assertJsonPath('success', true);
        $respuesta->assertJsonStructure(['archivo' => ['id', 'nombre_original', 'extension', 'tamano_bytes', 'url']]);

        $registro = Archivo::where('historia_clinica_id', $historia->id)->firstOrFail();

        $this->assertSame('informe.pdf', $registro->nombre_original);
        $this->assertSame('pdf', $registro->extension);
        $this->assertNotEmpty($registro->hash);
        $this->assertSame($historia->id, $registro->historia_clinica_id);
        $this->assertStringContainsString('patients/', (string) $registro->ruta);

        // La historia conserva el nombre original del adjunto
        $this->assertSame('informe.pdf', $historia->fresh()->archivo_nombre);
    }

    public function test_rechaza_extensiones_no_permitidas(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $historia = $this->historiaDePrueba();

        $exe = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

        $respuesta = $this->actingAs($admin)->post(
            route('historias.archivos.subir', ['id' => $historia->id]),
            ['archivo' => $exe],
            ['Accept' => 'application/json']
        );

        $respuesta->assertStatus(422);
        $this->assertStringContainsString('PDF', (string) $respuesta->json('message'));

        $this->assertSame(0, Archivo::where('historia_clinica_id', $historia->id)->count());
    }

    public function test_detecta_archivos_duplicados_por_hash(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $historia = $this->historiaDePrueba();

        // Mismo contenido en dos subidas
        $contenido = str_repeat('PDF-DEMO-', 50);

        $primera = UploadedFile::fake()->createWithContent('uno.pdf', $contenido);
        $segunda = UploadedFile::fake()->createWithContent('dos.pdf', $contenido);

        $this->actingAs($admin)
            ->postJson(route('historias.archivos.subir', ['id' => $historia->id]), ['archivo' => $primera])
            ->assertStatus(201);

        $this->actingAs($admin)
            ->postJson(route('historias.archivos.subir', ['id' => $historia->id]), ['archivo' => $segunda])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(1, Archivo::where('historia_clinica_id', $historia->id)->count());
    }

    public function test_lista_y_descarga_el_archivo(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $historia = $this->historiaDePrueba();

        $png = UploadedFile::fake()->image('radiografia.png', 40, 40);

        $this->actingAs($admin)
            ->postJson(route('historias.archivos.subir', ['id' => $historia->id]), ['archivo' => $png])
            ->assertStatus(201);

        $listado = $this->actingAs($admin)->getJson(route('historias.archivos', ['id' => $historia->id]));

        $listado->assertOk();
        $listado->assertJsonPath('success', true);
        $this->assertCount(1, $listado->json('archivos'));
        $this->assertTrue($listado->json('archivos.0.es_imagen'));

        $archivoId = $listado->json('archivos.0.id');

        $descarga = $this->actingAs($admin)->get(
            route('historias.archivos.descargar', ['id' => $historia->id, 'archivo' => $archivoId])
        );

        $descarga->assertOk();
        $this->assertStringContainsString('image/', (string) $descarga->headers->get('content-type'));
    }

    public function test_elimina_el_archivo(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $historia = $this->historiaDePrueba();

        $pdf = UploadedFile::fake()->create('borrar.pdf', 20, 'application/pdf');

        $this->actingAs($admin)
            ->postJson(route('historias.archivos.subir', ['id' => $historia->id]), ['archivo' => $pdf])
            ->assertStatus(201);

        $archivo = Archivo::where('historia_clinica_id', $historia->id)->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson(route('historias.archivos.eliminar', ['id' => $historia->id, 'archivo' => $archivo->id]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, Archivo::where('historia_clinica_id', $historia->id)->count());
    }
}
