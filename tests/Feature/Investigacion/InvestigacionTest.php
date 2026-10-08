<?php

namespace Tests\Feature\Investigacion;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Módulo INVESTIGACIÓN — dataset anonimizado.
 *
 * Verifica que el módulo funcione y, sobre todo, que NUNCA exponga
 * datos identificables del paciente (CI, nombre, apellidos).
 */
class InvestigacionTest extends TestCase
{
    use DatabaseTransactions;

    private Usuario $admin;

    private int $clinicaId;

    private int $periodoId;

    private int $pacienteId;

    private string $codigoEsperado;

    /** CI y nombre del paciente que SÍ autorizó la investigación. */
    private string $ciConsentido = 'CI-TEST-INV-999999';

    private string $nombreConsentido = 'PacienteConsentimientoInvestigacion';

    /** CI y nombre del paciente que NO autorizó (no debe aparecer nunca). */
    private string $ciSinConsentimiento = 'CI-TEST-INV-000000';

    private string $nombreSinConsentimiento = 'PacienteSinConsentimientoInvestigacion';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Usuario::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->firstOrFail();

        /* ------------------------------------------------------------
           Catálogos mínimos
           ------------------------------------------------------------ */
        $clinica = Clinica::create([
            'modulo' => 'Test',
            'especialidad' => 'Especialidad Test Investigacion',
            'curso' => 1,
        ]);
        $this->clinicaId = $clinica->id;

        $periodo = Periodo::create([
            'nombre' => 'Periodo Test Investigacion',
            'gestion' => 2099,
            'semestre' => 1,
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'habilitado' => true,
        ]);
        $this->periodoId = $periodo->id;

        $motivo = MotivoConsulta::create([
            'nombre' => 'Motivo Test Investigacion',
            'descripcion' => 'Generado por test',
            'habilitado' => true,
        ]);

        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento Test Investigacion',
            'costo' => 100,
            'clinica_id' => $this->clinicaId,
        ]);

        /* ------------------------------------------------------------
           Usuario de apoyo: docente/estudiante de la clínica.
           `admisiones` y `historias_clinicas` tienen claves foráneas
           compuestas contra `especialidades`, por eso la fila pivote.
           ------------------------------------------------------------ */
        $docente = Usuario::create([
            'ci' => 'CI-DOC-TEST-'.random_int(100000, 999999),
            'nombre_usuario' => 'inv'.random_int(100000, 999999),
            'nombre_completo' => 'Docente Test Investigacion',
            'tipo' => 'docente',
            'contrasena' => 'secret123',
        ]);

        DB::table('especialidades')->insert([
            'usuario_id' => $docente->id,
            'clinica_id' => $this->clinicaId,
            'habilitado' => true,
            'rotacion' => -1,
            'responsable_admision' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /* ------------------------------------------------------------
           Paciente CON consentimiento de investigación (visible)
           ------------------------------------------------------------ */
        $paciente = Paciente::create([
            'ci' => $this->ciConsentido,
            'nombre' => $this->nombreConsentido,
            'apellido_paterno' => 'ApellidoConsentido',
            'apellido_materno' => 'ApellidoConsentidoDos',
            'genero' => 'Femenino',
            'fecha_nacimiento' => now()->subYears(30)->toDateString(),
        ]);
        $this->pacienteId = $paciente->id;

        $admision = Admision::create([
            'paciente_id' => $paciente->id,
            'periodo_id' => $this->periodoId,
            'motivo_consulta_id' => $motivo->id,
            'motivo_consulta' => 'Motivo en texto libre',
            'diagnostico' => 'Diagnóstico de prueba',
            'consentimiento_datos' => true,
            'consentimiento_investigacion' => true,
            'estudiante_id' => $docente->id,
            'especialidad_estudiante' => $this->clinicaId,
            'responsable_id' => $docente->id,
            'especialidad_responsable' => $this->clinicaId,
        ]);

        $historia = HistoriaClinica::create([
            'admision_id' => $admision->id,
            'paciente_id' => $paciente->id,
            'clinica_id' => $this->clinicaId,
            'periodo_id' => $this->periodoId,
            'estado' => 'concluida',
            'autorizado' => true,
            'estudiante_id' => $docente->id,
            'especialidad_estudiante' => $this->clinicaId,
            'docente_id' => $docente->id,
            'especialidad_docente' => $this->clinicaId,
        ]);

        TratamientoRealizado::create([
            'historia_clinica_id' => $historia->id,
            'tratamiento_id' => $tratamiento->id,
            'clinica_id' => $this->clinicaId,
            'pieza_dental' => '11',
            'estado' => 'realizado',
            'costo_cobrado' => 100,
            'fecha_inicio' => now()->toDateString(),
        ]);

        $this->codigoEsperado = substr(
            hash('sha256', $this->pacienteId.config('app.key')),
            0,
            12
        );

        /* ------------------------------------------------------------
           Paciente SIN consentimiento de investigación (oculto)
           ------------------------------------------------------------ */
        $pacienteOculto = Paciente::create([
            'ci' => $this->ciSinConsentimiento,
            'nombre' => $this->nombreSinConsentimiento,
            'apellido_paterno' => 'ApellidoSinConsentimiento',
            'genero' => 'Masculino',
            'fecha_nacimiento' => now()->subYears(45)->toDateString(),
        ]);

        $admisionOculta = Admision::create([
            'paciente_id' => $pacienteOculto->id,
            'periodo_id' => $this->periodoId,
            'motivo_consulta_id' => $motivo->id,
            'consentimiento_datos' => true,
            'consentimiento_investigacion' => false,
            'estudiante_id' => $docente->id,
            'especialidad_estudiante' => $this->clinicaId,
            'responsable_id' => $docente->id,
            'especialidad_responsable' => $this->clinicaId,
        ]);

        $historiaOculta = HistoriaClinica::create([
            'admision_id' => $admisionOculta->id,
            'paciente_id' => $pacienteOculto->id,
            'clinica_id' => $this->clinicaId,
            'periodo_id' => $this->periodoId,
            'estado' => 'concluida',
            'autorizado' => true,
            'estudiante_id' => $docente->id,
            'especialidad_estudiante' => $this->clinicaId,
            'docente_id' => $docente->id,
            'especialidad_docente' => $this->clinicaId,
        ]);

        TratamientoRealizado::create([
            'historia_clinica_id' => $historiaOculta->id,
            'tratamiento_id' => $tratamiento->id,
            'clinica_id' => $this->clinicaId,
            'pieza_dental' => '21',
            'estado' => 'realizado',
            'costo_cobrado' => 100,
            'fecha_inicio' => now()->toDateString(),
        ]);
    }

    /* ============================================================
       TESTS
       ============================================================ */

    public function test_el_index_de_investigacion_responde_200(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/investigacion');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('id="tbodyInvestigacion"', false);
        $respuesta->assertSee('id="filtro-anio"', false);
        $respuesta->assertSee('id="chartPeriodos"', false);
    }

    public function test_la_tabla_devuelve_filas_paginacion_y_total(): void
    {
        $respuesta = $this->actingAs($this->admin)->getJson('/investigacion/tabla');

        $respuesta->assertStatus(200);
        $respuesta->assertJsonStructure(['filas', 'paginacion', 'total']);
        $this->assertGreaterThanOrEqual(1, (int) $respuesta->json('total'));
    }

    public function test_la_tabla_no_expone_ci_ni_nombre_del_paciente(): void
    {
        $respuesta = $this->actingAs($this->admin)->getJson('/investigacion/tabla');

        $respuesta->assertStatus(200);

        $contenido = $respuesta->getContent();

        // Datos identificables del paciente que SÍ consintió: jamás deben salir.
        $this->assertStringNotContainsString($this->ciConsentido, $contenido, 'El JSON de /tabla expone el CI del paciente.');
        $this->assertStringNotContainsString($this->nombreConsentido, $contenido, 'El JSON de /tabla expone el nombre del paciente.');
        $this->assertStringNotContainsString('ApellidoConsentido', $contenido, 'El JSON de /tabla expone los apellidos del paciente.');

        // El paciente sin consentimiento tampoco debe aparecer.
        $this->assertStringNotContainsString($this->ciSinConsentimiento, $contenido, 'El JSON de /tabla incluye a un paciente sin consentimiento.');
        $this->assertStringNotContainsString($this->nombreSinConsentimiento, $contenido, 'El JSON de /tabla incluye a un paciente sin consentimiento.');

        // La identidad anonimizada sí debe estar presente.
        $this->assertStringContainsString($this->codigoEsperado, $contenido, 'Falta el código anonimizado del paciente.');
    }

    public function test_las_estadisticas_devuelven_la_estructura_esperada(): void
    {
        $respuesta = $this->actingAs($this->admin)->getJson('/investigacion/estadisticas?clinica='.$this->clinicaId);

        $respuesta->assertStatus(200);
        $respuesta->assertJsonStructure([
            'edad' => ['media', 'mediana', 'moda', 'min', 'max', 'desviacion'],
            'genero',
            'especialidad',
            'periodo',
            'total',
        ]);

        $this->assertGreaterThanOrEqual(1, (int) $respuesta->json('total'));
        $this->assertNotNull($respuesta->json('edad.media'));
        $this->assertGreaterThanOrEqual(29, (int) $respuesta->json('edad.media'));
        $this->assertLessThanOrEqual(31, (int) $respuesta->json('edad.media'));
        $this->assertArrayHasKey('Femenino', (array) $respuesta->json('genero'));
    }

    public function test_la_exportacion_devuelve_un_csv(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/investigacion/exportar');

        $respuesta->assertStatus(200);

        $this->assertStringContainsString(
            'text/csv',
            (string) $respuesta->headers->get('content-type'),
            'El Content-Type de la exportación no es text/csv.'
        );

        $this->assertStringContainsString(
            'attachment',
            (string) $respuesta->headers->get('content-disposition')
        );

        $csv = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'El CSV no incluye el BOM UTF-8.');
        $this->assertStringContainsString('codigo', $csv);
        $this->assertStringContainsString($this->codigoEsperado, $csv);

        // El CSV tampoco puede contener datos identificables.
        $this->assertStringNotContainsString($this->ciConsentido, $csv);
        $this->assertStringNotContainsString($this->nombreConsentido, $csv);
        $this->assertStringNotContainsString($this->ciSinConsentimiento, $csv);
    }

    public function test_los_filtros_por_genero_y_edad_responden(): void
    {
        $respuesta = $this->actingAs($this->admin)->getJson('/investigacion/tabla?genero=Femenino&edad_min=18&edad_max=40');

        $respuesta->assertStatus(200);
        $respuesta->assertJsonStructure(['filas', 'paginacion', 'total']);
        $this->assertGreaterThanOrEqual(1, (int) $respuesta->json('total'));

        $vacio = $this->actingAs($this->admin)->getJson('/investigacion/tabla?genero=Femenino&edad_min=90&edad_max=99');
        $vacio->assertStatus(200);
        $this->assertSame(0, (int) $vacio->json('total'));
    }
}
