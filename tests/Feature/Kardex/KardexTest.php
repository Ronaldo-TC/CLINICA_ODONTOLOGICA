<?php

namespace Tests\Feature\Kardex;

use App\Models\Caja;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Prueba de integración del módulo KARDEX (solo lectura).
 *
 * Simula el ciclo completo de un paciente: paciente -> admisión -> historia
 * clínica -> tratamiento realizado -> caja -> caja_detalle, y comprueba que el
 * kardex consolida los totales sin duplicar importes.
 *
 * Todas las escrituras se revierten con DatabaseTransactions.
 */
class KardexTest extends TestCase
{
    use DatabaseTransactions;

    public function test_kardex_uses_patient_id_and_excludes_zero_ci_receipts_of_other_patients(): void
    {
        $other = Paciente::create(['ci' => 'KARDEX-OTRO', 'nombre' => 'Otro paciente']);
        $payment = [
            'recibo' => 999010, 'monto' => 100, 'costo_total' => 100, 'cancelado' => 80, 'ci' => 0,
            'tratamiento_id' => DB::table('tratamientos')->where('clinica_id', $this->clinicaId)->value('id'),
            'clinica_id' => $this->clinicaId, 'usuario_id' => $this->usuarioAdmin->id,
        ];
        Caja::create([...$payment, 'paciente_id' => $other->id]);
        Caja::create([...$payment, 'recibo' => 999011, 'paciente_id' => null]);
        Caja::create([...$payment, 'recibo' => 999012, 'paciente_id' => $this->pacienteId, 'costo_total' => 50, 'cancelado' => 15]);

        $response = $this->actingAs($this->usuarioAdmin)->getJson('/kardex/'.$this->pacienteId.'/datos')->assertOk();

        $this->assertSame(55.0, (float) $response->json('total_pagado'));
        $this->assertSame(95.0, (float) $response->json('saldo_pendiente'));
        $this->assertCount(2, $response->json('pagos'));
    }

    protected int $pacienteId;

    protected int $clinicaId;

    protected int $periodoId;

    protected Usuario $usuarioAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Usuario con rol admin: autoriza `reportes.ver` en el controlador.
        $admin = Usuario::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        if (! $admin) {
            $this->markTestSkipped('No existe un usuario con rol admin en la base de datos.');
        }

        $this->usuarioAdmin = $admin;

        $this->crearDatosDePrueba();
    }

    /**
     * Crea la cadena mínima coherente respetando las claves foráneas NOT NULL.
     */
    protected function crearDatosDePrueba(): void
    {
        $this->clinicaId = (int) (DB::table('clinicas')->where('id', 2)->value('id')
            ?: DB::table('clinicas')->value('id'));
        $this->periodoId = (int) DB::table('periodos')->value('id');

        $tratamiento = DB::table('tratamientos')->where('clinica_id', $this->clinicaId)->first()
            ?: DB::table('tratamientos')->first();

        $estudiante = $this->crearUsuarioClinico('estudiante');
        $docente = $this->crearUsuarioClinico('docente');
        $estudiante->especialidades()->attach($this->clinicaId, ['habilitado' => true]);
        $docente->especialidades()->attach($this->clinicaId, ['habilitado' => true]);
        $estudianteId = $estudiante->id;
        $docenteId = $docente->id;

        $marca = 'KARDEX'.random_int(100000, 999999);

        $this->pacienteId = (int) DB::table('pacientes')->insertGetId([
            'ci' => $marca,
            'nombre' => 'Paciente',
            'apellido_paterno' => $marca,
            'apellido_materno' => 'Prueba',
            'genero' => 'Masculino',
            'fecha_nacimiento' => '1995-05-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admisionId = (int) DB::table('admisiones')->insertGetId([
            'paciente_id' => $this->pacienteId,
            'periodo_id' => $this->periodoId ?: null,
            'estado_civil' => 'Soltero',
            'ocupacion' => 'Estudiante',
            'diagnostico' => 'Prueba de integración Kardex',
            'estudiante_id' => $estudianteId,
            'especialidad_estudiante' => $this->clinicaId,
            'responsable_id' => $docenteId,
            'especialidad_responsable' => $this->clinicaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $historiaId = (int) DB::table('historias_clinicas')->insertGetId([
            'admision_id' => $admisionId,
            'paciente_id' => $this->pacienteId,
            'clinica_id' => $this->clinicaId,
            'periodo_id' => $this->periodoId,
            'estado' => 'abierta',
            'autorizado' => 1,
            'estudiante_id' => $estudianteId,
            'especialidad_estudiante' => $this->clinicaId,
            'docente_id' => $docenteId,
            'especialidad_docente' => $this->clinicaId,
            'fecha_inicio' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tratamientoRealizadoId = (int) DB::table('tratamientos_realizados')->insertGetId([
            'historia_clinica_id' => $historiaId,
            'tratamiento_id' => (int) $tratamiento->id,
            'clinica_id' => $this->clinicaId,
            'pieza_dental' => '16',
            'estado' => 'realizado',
            'costo_cobrado' => 100.00,
            'observacion' => 'Prueba de integración',
            'fecha_inicio' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cajaId = (int) DB::table('cajas')->insertGetId([
            'recibo' => 999001,
            'monto' => 100,
            'costo_total' => 100.00,
            'cancelado' => 40.00,
            'habilitada' => 1,
            'descripcion' => 'Pago de prueba Kardex',
            'ci' => (int) preg_replace('/\D/', '', $marca),
            'tipo' => 'consulta',
            'tipo_pago' => 'efectivo',
            'paciente_nombre_completo' => 'Paciente '.$marca,
            'grado' => '1',
            'tratamiento_id' => (int) $tratamiento->id,
            'clinica_id' => $this->clinicaId,
            'usuario_id' => (int) $this->usuarioAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('caja_detalles')->insert([
            'caja_id' => $cajaId,
            'tratamiento_realizado_id' => $tratamientoRealizadoId,
            'concepto' => 'Tratamiento de prueba',
            'cantidad' => 1,
            'precio_unitario' => 100.00,
            'monto' => 100.00,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuarioClinico(string $rol): Usuario
    {
        $identificador = substr((string) Str::uuid(), 0, 12);
        $usuario = Usuario::create([
            'ci' => $identificador,
            'nombre_usuario' => $identificador,
            'nombre_completo' => 'Prueba '.$rol.' '.$identificador,
            'contrasena' => 'unused',
            'habilitado' => true,
            'permitir_login' => true,
        ]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    /** GET /kardex responde 200 con las métricas y los filtros. */
    public function test_listado_kardex_responde_200(): void
    {
        $response = $this->actingAs($this->usuarioAdmin)->get('/kardex');

        $response->assertStatus(200);
        $response->assertSee('kardex', false);
        $response->assertSee('id="tbodyKardex"', false);
        $response->assertSee('id="filtro-buscar"', false);
        $response->assertSee('id="paginacionWrap"', false);
    }

    /** GET /kardex/tabla devuelve la estructura filas | paginacion | total. */
    public function test_tabla_kardex_devuelve_estructura_json(): void
    {
        $response = $this->actingAs($this->usuarioAdmin)->getJson('/kardex/tabla');

        $response->assertStatus(200);
        $response->assertJsonStructure(['filas', 'paginacion', 'total']);
        $this->assertIsString($response->json('filas'));
        $this->assertIsInt($response->json('total'));
    }

    /** GET /kardex/{id}/datos devuelve el detalle consolidado del paciente. */
    public function test_datos_kardex_devuelve_detalle(): void
    {
        $response = $this->actingAs($this->usuarioAdmin)->getJson("/kardex/{$this->pacienteId}/datos");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('es_edicion', false);
        $response->assertJsonPath('id', $this->pacienteId);
        $response->assertJsonStructure([
            'success',
            'es_edicion',
            'registro',
            'id',
            'total_admisiones',
            'total_historias',
            'total_tratamientos',
            'total_pagado',
            'saldo_pendiente',
            'admisiones',
            'historias',
            'tratamientos',
            'pagos',
        ]);

        // Los totales deben ser 1/1/1 sin duplicar importes (100 facturado, 40 pagado).
        $this->assertSame(1, (int) $response->json('total_admisiones'));
        $this->assertSame(1, (int) $response->json('total_historias'));
        $this->assertSame(1, (int) $response->json('total_tratamientos'));
        $this->assertSame(40.0, (float) $response->json('total_pagado'));
        $this->assertSame(60.0, (float) $response->json('saldo_pendiente'));
        $this->assertCount(1, $response->json('admisiones'));
        $this->assertCount(1, $response->json('historias'));
        $this->assertCount(1, $response->json('tratamientos'));
        $this->assertCount(1, $response->json('pagos'));
    }

    /** Los filtros por nombre, CI, periodo y clínica encuentran al paciente. */
    public function test_filtros_de_kardex(): void
    {
        $porNombre = $this->actingAs($this->usuarioAdmin)
            ->getJson('/kardex/tabla?buscar='.urlencode('Paciente'));
        $porNombre->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $porNombre->json('total'));

        $porPeriodo = $this->actingAs($this->usuarioAdmin)
            ->getJson('/kardex/tabla?periodo_id='.$this->periodoId);
        $porPeriodo->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $porPeriodo->json('total'));

        $porClinica = $this->actingAs($this->usuarioAdmin)
            ->getJson('/kardex/tabla?clinica_id='.$this->clinicaId);
        $porClinica->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $porClinica->json('total'));
    }

    /** El módulo es de solo lectura: no expone rutas de escritura. */
    public function test_kardex_no_tiene_rutas_de_escritura(): void
    {
        // Solo existen rutas GET (index, tabla, datos): ni store, ni update,
        // ni destroy. Se comprueba sobre las rutas registradas para no depender
        // de la página 404.
        $rutas = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'kardex'));

        $this->assertGreaterThanOrEqual(3, $rutas->count());

        foreach ($rutas as $ruta) {
            $this->assertSame(
                ['GET', 'HEAD'],
                $ruta->methods(),
                "La ruta {$ruta->uri()} no debería admitir escritura."
            );
        }

        // Las rutas de kardex que existen son exactamente index, tabla y datos.
        $nombres = $rutas->map(fn ($r) => $r->getName())->filter()->values()->all();
        sort($nombres);
        $this->assertSame(['kardex.datos', 'kardex.index', 'kardex.tabla'], $nombres);
    }
}
