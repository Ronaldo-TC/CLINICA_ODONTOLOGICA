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
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Tests\TestCase;

/**
 * Archivos adjuntos de la historia clínica: subida con validación,
 * detección de duplicados por hash y descarga.
 */
class HistoriaArchivosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

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
        $contenido = "%PDF-1.4\n".str_repeat('PDF-DEMO-', 50);

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
        Storage::disk('local')->assertMissing($archivo->ruta);
    }

    public function test_large_pdf_is_stored_privately_and_only_its_name_is_saved_in_the_database(): void
    {
        $historia = $this->historiaDePrueba();
        $contenido = "%PDF-1.4\n".str_repeat('Documento de prueba ', 20000);
        $pdf = UploadedFile::fake()->createWithContent('historia-clinica.pdf', $contenido);

        $this->actingAs($this->admin())->postJson(route('historias.archivos.subir', $historia->id), ['archivo' => $pdf])
            ->assertCreated()->assertJsonPath('success', true);

        $registro = $historia->archivos()->sole();
        $this->assertSame($registro->nombre_guardado, $historia->fresh()->archivo);
        $this->assertSame('historia-clinica.pdf', $historia->fresh()->archivo_nombre);
        $this->assertSame(strlen($contenido), $registro->tamano_bytes);
        Storage::disk('local')->assertExists($registro->ruta);
        Storage::disk('public')->assertMissing($registro->ruta);
        $this->assertSame($contenido, Storage::disk('local')->get($registro->ruta));
        $this->get(route('historias.archivos.descargar', [$historia->id, $registro->id]))
            ->assertOk()->assertContent($contenido);
    }

    public function test_storage_failure_returns_500_without_creating_attachment_metadata(): void
    {
        $historia = $this->historiaDePrueba();
        $disco = \Mockery::mock(FilesystemAdapter::class);
        $disco->shouldReceive('exists')->once()->andReturn(false);
        $disco->shouldReceive('putFileAs')->once()->andReturn(false);
        $disco->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disco);
        Exceptions::fake();

        $this->actingAs($this->admin())->postJson(route('historias.archivos.subir', $historia->id), [
            'archivo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ])->assertStatus(500)->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se pudo guardar el archivo. Intente nuevamente.');

        $this->assertSame(0, $historia->archivos()->count());
        $this->assertEmpty($historia->fresh()->archivo);
        Exceptions::assertReported(\RuntimeException::class);
    }

    public function test_database_failure_removes_the_new_file_and_keeps_the_history_unchanged(): void
    {
        $historia = $this->historiaDePrueba();
        Exceptions::fake();
        Archivo::creating(function (): void {
            throw new \RuntimeException('Error al registrar metadatos.');
        });
        try {
            $this->actingAs($this->admin())->postJson(route('historias.archivos.subir', $historia->id), [
                'archivo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            ])->assertStatus(500)->assertJsonPath('success', false);
        } finally {
            Archivo::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, $historia->archivos()->count());
        $this->assertEmpty($historia->fresh()->archivo);
        Exceptions::assertReported(\RuntimeException::class);
    }

    public function test_legacy_backup_only_downloads_the_attachment_with_matching_contents(): void
    {
        $historia = $this->historiaDePrueba();
        $contenido = "%PDF-1.4\nDocumento anterior";
        $historia->update(['archivo' => base64_encode($contenido)]);
        $registro = $historia->archivos()->create([
            'nombre_original' => 'anterior.pdf', 'nombre_guardado' => 'anterior.pdf',
            'ruta' => 'patients/anterior.pdf', 'extension' => 'pdf', 'tipo' => 'application/pdf',
            'tamano_bytes' => strlen($contenido), 'hash' => hash('sha256', $contenido),
        ]);

        $this->actingAs($this->admin())->get(route('historias.archivos.descargar', [$historia->id, $registro->id]))
            ->assertOk()->assertContent($contenido);
        $registro->update(['hash' => hash('sha256', 'Otro documento')]);
        $this->get(route('historias.archivos.descargar', [$historia->id, $registro->id]))->assertNotFound();
    }

    public function test_upload_recovers_a_legacy_backup_in_storage_before_saving_the_new_filename(): void
    {
        $historia = $this->historiaDePrueba();
        $contenido = "%PDF-1.4\nDocumento anterior";
        $historia->update(['archivo' => base64_encode($contenido)]);
        $anterior = $historia->archivos()->create([
            'nombre_original' => 'anterior.pdf', 'nombre_guardado' => 'anterior.pdf',
            'ruta' => 'patients/anterior.pdf', 'extension' => 'pdf', 'tipo' => 'application/pdf',
            'tamano_bytes' => strlen($contenido), 'hash' => hash('sha256', $contenido),
        ]);

        $this->actingAs($this->admin())->postJson(route('historias.archivos.subir', $historia->id), [
            'archivo' => UploadedFile::fake()->createWithContent('nuevo.pdf', "%PDF-1.4\nNuevo documento"),
        ])->assertCreated();

        $this->assertSame($contenido, Storage::disk('local')->get($anterior->ruta));
        $this->get(route('historias.archivos.descargar', [$historia->id, $anterior->id]))->assertOk()->assertContent($contenido);
        $this->assertSame(2, $historia->archivos()->count());
        $this->assertSame('nuevo.pdf', $historia->fresh()->archivo_nombre);
    }
}
