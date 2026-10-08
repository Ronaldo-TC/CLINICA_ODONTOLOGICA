<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\Usuario;
use Database\Seeders\OdontogramaDemoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Odontograma3dTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'odontograma_testing',
            'database.connections.odontograma_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        foreach ([
            '2026_09_25_160452_create_clinicas_table.php',
            '2026_09_25_160454_create_pacientes_table.php',
            '2026_09_25_160455_create_usuarios_table.php',
            '2026_09_25_160500_create_especialidades_table.php',
            '2026_09_30_153027_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('admisiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('estudiante_id')->nullable()->constrained('usuarios');
            $table->foreignId('especialidad_estudiante')->nullable()->constrained('clinicas');
            $table->foreignId('responsable_id')->nullable()->constrained('usuarios');
            $table->foreignId('especialidad_responsable')->nullable()->constrained('clinicas');
            $table->string('estado_civil')->default('');
            $table->integer('telefono')->default(0);
            $table->timestamps();
        });

        foreach (['historias_clinicas', 'tratamientos_realizados'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
            });
        }

        (require database_path('migrations/2026_10_02_233656_create_odontogramas_3d_table.php'))->up();
    }

    public function test_la_api_devuelve_401_sin_autenticacion(): void
    {
        $this->getJson('/api/v1/odontogramas/1/threejs')->assertUnauthorized();
    }

    public function test_la_api_devuelve_403_sin_permiso_de_historias(): void
    {
        $usuario = $this->usuario();
        $odontograma = $this->odontograma($usuario);

        $this->actingAs($usuario)->getJson('/api/v1/odontogramas/'.$odontograma->id.'/threejs')
            ->assertForbidden();
    }

    public function test_el_payload_identifica_los_hallazgos_y_su_color_por_defecto(): void
    {
        $usuario = $this->usuario('historias.ver');
        $odontograma = $this->odontograma($usuario);
        $hallazgo = $odontograma->hallazgos()->create([
            'pieza_dental' => '16', 'caras' => 'MOD', 'condicion' => 'caries',
        ]);

        $this->actingAs($usuario)->getJson('/api/v1/odontogramas/'.$odontograma->id.'/threejs')
            ->assertOk()
            ->assertJsonPath('data.hallazgos.16.0.id', $hallazgo->id)
            ->assertJsonPath('data.hallazgos.16.0.pieza_dental', '16')
            ->assertJsonPath('data.hallazgos.16.0.caras', 'MOD')
            ->assertJsonPath('data.hallazgos.16.0.color', '#FF0000')
            ->assertJsonPath('data.resumen.caries', 1);
    }

    public function test_guardar_actualiza_ids_y_conserva_mediciones_tratamiento_y_creador(): void
    {
        $usuario = $this->usuario('historias.editar');
        $creador = $this->usuario();
        $odontograma = $this->odontograma($usuario);
        $tratamientoId = DB::table('tratamientos_realizados')->insertGetId([]);
        $hallazgo = $odontograma->hallazgos()->create([
            'pieza_dental' => '16', 'caras' => 'O', 'condicion' => 'caries',
            'severidad' => 'severa', 'movilidad' => 2, 'sondaje_mm' => 4.5,
            'tratamiento_realizado_id' => $tratamientoId, 'created_by' => $creador->id,
        ]);
        $omitido = $odontograma->hallazgos()->create([
            'pieza_dental' => '17', 'condicion' => 'caries',
        ]);

        $this->actingAs($usuario)->postJson('/api/v1/odontogramas/'.$odontograma->id.'/hallazgos', [
            'hallazgos' => [
                [
                    'id' => $hallazgo->id, 'pieza_dental' => '16',
                    'condicion' => 'obturacion',
                    'created_by' => $usuario->id, 'tratamiento_realizado_id' => null,
                    'odontograma_id' => 999,
                ],
                ['pieza_dental' => '51', 'condicion' => 'caries', 'movilidad' => 1, 'sondaje_mm' => 3.2],
            ],
        ])->assertOk()->assertJsonPath('data.hallazgos.16.0.id', $hallazgo->id);

        $this->assertDatabaseHas('odontograma_hallazgos', [
            'id' => $hallazgo->id, 'odontograma_id' => $odontograma->id,
            'condicion' => 'obturacion', 'severidad' => 'severa', 'caras' => 'O',
            'movilidad' => 2, 'sondaje_mm' => 4.5,
            'tratamiento_realizado_id' => $tratamientoId,
            'created_by' => $creador->id, 'updated_by' => $usuario->id,
        ]);
        $this->assertDatabaseMissing('odontograma_hallazgos', ['id' => $omitido->id]);
        $this->assertDatabaseHas('odontograma_hallazgos', [
            'odontograma_id' => $odontograma->id, 'pieza_dental' => '51',
            'movilidad' => 1, 'sondaje_mm' => 3.2, 'created_by' => $usuario->id,
        ]);
        $this->assertDatabaseCount('odontograma_hallazgos', 2);
    }

    public function test_guardar_lista_vacia_limpia_hallazgos_y_layout_y_actualiza_fecha(): void
    {
        $this->travelTo('2026-10-04 10:00:00');
        $usuario = $this->usuario('historias.editar');
        $odontograma = $this->odontograma($usuario, ['layout_3d' => ['zoom' => 2]]);
        $odontograma->hallazgos()->create(['pieza_dental' => '16', 'condicion' => 'caries']);
        $this->travel(1)->minute();

        $this->actingAs($usuario)->postJson('/api/v1/odontogramas/'.$odontograma->id.'/hallazgos', [
            'hallazgos' => [], 'layout_3d' => [],
        ])->assertOk()->assertJsonPath('data.hallazgos', [])->assertJsonPath('data.layout_3d', []);

        $this->assertDatabaseCount('odontograma_hallazgos', 0);
        $this->assertSame('2026-10-04 10:01:00', $odontograma->fresh()->updated_at->format('Y-m-d H:i:s'));
    }

    #[DataProvider('hallazgosInvalidos')]
    public function test_datos_invalidos_devuelven_422_y_conservan_los_hallazgos(string $campo, mixed $valor, string $mensaje): void
    {
        $usuario = $this->usuario('historias.editar');
        $odontograma = $this->odontograma($usuario);
        $hallazgo = $odontograma->hallazgos()->create(['pieza_dental' => '16', 'condicion' => 'caries']);
        $data = ['pieza_dental' => '16', 'condicion' => 'caries'];
        $data[$campo] = $valor;

        $this->actingAs($usuario)->postJson('/api/v1/odontogramas/'.$odontograma->id.'/hallazgos', [
            'hallazgos' => [$data],
        ])->assertUnprocessable()->assertJsonValidationErrors(['hallazgos.0.'.$campo => $mensaje]);

        $this->assertDatabaseHas('odontograma_hallazgos', ['id' => $hallazgo->id, 'condicion' => 'caries']);
        $this->assertDatabaseCount('odontograma_hallazgos', 1);
    }

    /**
     * @return array<string, array{string, mixed, string}>
     */
    public static function hallazgosInvalidos(): array
    {
        return [
            'pieza fuera de FDI' => ['pieza_dental', '99', 'La pieza dental debe usar una numeración FDI válida.'],
            'cara desconocida' => ['caras', 'Z', 'Las caras deben contener únicamente M, O, D, V, L o P.'],
            'condicion fuera del enum' => ['condicion', 'desconocida', 'La condición odontológica no es válida.'],
            'color inseguro' => ['color', 'red"', 'El color debe tener el formato hexadecimal #RRGGBB.'],
        ];
    }

    public function test_un_id_de_otro_odontograma_devuelve_422_sin_modificarlo(): void
    {
        $usuario = $this->usuario('historias.editar');
        $odontograma = $this->odontograma($usuario);
        $otro = $this->odontograma($usuario);
        $hallazgoAjeno = $otro->hallazgos()->create(['pieza_dental' => '16', 'condicion' => 'caries']);

        $this->actingAs($usuario)->postJson('/api/v1/odontogramas/'.$odontograma->id.'/hallazgos', [
            'hallazgos' => [[
                'id' => $hallazgoAjeno->id, 'pieza_dental' => '16', 'condicion' => 'obturacion',
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'hallazgos.0.id' => 'El hallazgo no pertenece a este odontograma.',
        ]);

        $this->assertDatabaseHas('odontograma_hallazgos', [
            'id' => $hallazgoAjeno->id, 'odontograma_id' => $otro->id, 'condicion' => 'caries',
        ]);
    }

    public function test_el_historial_cuenta_hallazgos_y_selecciona_la_admision_mas_reciente(): void
    {
        $this->travelTo('2026-10-04 10:00:00');
        $usuario = $this->usuario('historias.ver');
        $antiguo = $this->odontograma($usuario, ['version' => 5]);
        $paciente = $antiguo->paciente;
        $this->travel(1)->hour();
        $reciente = $this->odontograma($usuario, ['version' => 1], $paciente);
        $reciente->hallazgos()->create(['pieza_dental' => '16', 'condicion' => 'caries']);

        $this->actingAs($usuario)->getJson('/api/v1/pacientes/'.$paciente->id.'/odontogramas')
            ->assertOk()->assertJsonPath('data.0.id', $reciente->id)
            ->assertJsonPath('data.0.hallazgos_count', 1)
            ->assertJsonPath('data.1.hallazgos_count', 0);

        $this->getJson('/api/v1/pacientes/'.$paciente->id.'/odontograma-actual')
            ->assertOk()->assertJsonPath('data.id', $reciente->id);
    }

    public function test_un_paciente_existente_sin_odontograma_devuelve_arcada_vacia(): void
    {
        $usuario = $this->usuario('historias.ver');
        $paciente = Paciente::create(['nombre' => 'Paciente nuevo']);

        $this->actingAs($usuario)->getJson('/api/v1/pacientes/'.$paciente->id.'/odontograma-actual')
            ->assertOk()->assertJsonPath('data.hallazgos', [])->assertJsonPath('data.version', 0);
    }

    public function test_un_paciente_inexistente_devuelve_404(): void
    {
        $usuario = $this->usuario('historias.ver');

        $this->actingAs($usuario)->getJson('/api/v1/pacientes/999/odontograma-actual')->assertNotFound();
    }

    public function test_las_rutas_web_muestran_y_guardan_con_la_sesion_y_permisos(): void
    {
        $usuario = $this->usuario('historias.ver', 'historias.editar');
        $odontograma = $this->odontograma($usuario);

        $this->actingAs($usuario)->get(route('odontogramas.show', $odontograma))
            ->assertSee('odontograma-root', false);
        $this->getJson(route('odontogramas.threejs', $odontograma))
            ->assertOk()->assertJsonPath('data.id', $odontograma->id);
        $this->postJson(route('odontogramas.hallazgos.guardar', $odontograma), [
            'hallazgos' => [['pieza_dental' => '16', 'condicion' => 'caries']],
        ])->assertOk();

        $this->assertDatabaseHas('odontograma_hallazgos', [
            'odontograma_id' => $odontograma->id, 'pieza_dental' => '16', 'created_by' => $usuario->id,
        ]);
    }

    public function test_el_permiso_de_lectura_no_permite_guardar_y_devuelve_403(): void
    {
        $usuario = $this->usuario('historias.ver');
        $odontograma = $this->odontograma($usuario);

        $this->actingAs($usuario)->postJson(route('odontogramas.hallazgos.guardar', $odontograma), [
            'hallazgos' => [['pieza_dental' => '16', 'condicion' => 'caries']],
        ])->assertForbidden();

        $this->assertDatabaseCount('odontograma_hallazgos', 0);
    }

    public function test_el_seeder_demo_conserva_los_odontogramas_de_pacientes_reales(): void
    {
        $usuario = $this->usuario();
        $pacienteReal = Paciente::create(['ci' => '12345678', 'nombre' => 'Paciente real']);
        $odontogramaReal = $this->odontograma($usuario, [], $pacienteReal);
        $hallazgoReal = $odontogramaReal->hallazgos()->create(['pieza_dental' => '16', 'condicion' => 'caries']);

        $this->seed(OdontogramaDemoSeeder::class);
        $demoId = Odontograma::where('paciente_id', Paciente::where('ci', 'ODONTO-DEMO-3D')->value('id'))->value('id');
        $this->seed(OdontogramaDemoSeeder::class);

        $this->assertModelExists($odontogramaReal);
        $this->assertModelExists($hallazgoReal);
        $this->assertDatabaseCount('odontogramas', 2);
        $this->assertDatabaseHas('odontogramas', ['id' => $demoId]);
        $this->assertDatabaseCount('odontograma_hallazgos', 18);
    }

    private function usuario(string ...$permisos): Usuario
    {
        $numero = Usuario::count() + 1;
        $usuario = Usuario::create([
            'ci' => 'ODONTO-TEST-'.$numero,
            'nombre_usuario' => 'odonto_test_'.$numero,
            'nombre_completo' => 'Usuario de prueba',
            'contrasena' => 'clave-pruebas',
        ]);

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::firstOrCreate([
                'name' => $permiso, 'guard_name' => 'web',
            ], ['descripcion' => $permiso]));
        }

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function odontograma(Usuario $usuario, array $atributos = [], ?Paciente $paciente = null): Odontograma
    {
        $paciente ??= Paciente::create(['nombre' => 'Paciente de prueba']);
        $admision = Admision::create(['paciente_id' => $paciente->id]);

        return Odontograma::create(array_merge([
            'paciente_id' => $paciente->id, 'admision_id' => $admision->id,
            'usuario_id' => $usuario->id, 'version' => 1, 'tipo' => 'inicial', 'estado' => 'borrador',
        ], $atributos));
    }
}
