<?php

namespace Tests\Feature;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class CrudTransactionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');

        $migration = require database_path('migrations/2026_09_25_160454_create_pacientes_table.php');
        $migration->up();

        Route::post('/crud-transactions/pacientes', [CrudTransactionsController::class, 'store']);
        Route::put('/crud-transactions/pacientes/{id}', [CrudTransactionsController::class, 'update']);
        Route::delete('/crud-transactions/pacientes/{id}', [CrudTransactionsController::class, 'destroy']);
    }

    protected function tearDown(): void
    {
        DB::rollBack(0);
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_la_validacion_al_crear_no_revierte_la_transaccion_externa(): void
    {
        DB::beginTransaction();
        $paciente = Paciente::create(['nombre' => 'Paciente existente']);

        $this->postJson('/crud-transactions/pacientes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nombre');

        $this->assertModelExists($paciente);
        $this->assertSame(1, DB::transactionLevel());
    }

    public function test_la_validacion_al_actualizar_conserva_el_paciente_y_la_transaccion(): void
    {
        DB::beginTransaction();
        $paciente = Paciente::create(['nombre' => 'Paciente existente']);

        $this->putJson('/crud-transactions/pacientes/'.$paciente->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nombre');

        $this->assertSame('Paciente existente', $paciente->fresh()->nombre);
        $this->assertSame(1, DB::transactionLevel());
    }

    public function test_un_error_de_guardado_revierte_solo_la_operacion_interna(): void
    {
        Exceptions::fake();
        DB::beginTransaction();
        $paciente = Paciente::create(['nombre' => 'Paciente existente']);

        $this->postJson('/crud-transactions/pacientes', ['nombre' => 'Fallo de guardado'])
            ->assertInternalServerError()
            ->assertJsonPath('success', false);

        $this->assertModelExists($paciente);
        $this->assertDatabaseCount('pacientes', 1);
        $this->assertSame(1, DB::transactionLevel());
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_un_guardado_valido_persiste_el_paciente_sin_cerrar_la_transaccion_externa(): void
    {
        DB::beginTransaction();

        $this->postJson('/crud-transactions/pacientes', ['nombre' => 'Paciente nuevo'])
            ->assertCreated()
            ->assertJsonPath('registro.nombre', 'Paciente nuevo');

        $this->assertDatabaseHas('pacientes', ['nombre' => 'Paciente nuevo']);
        $this->assertSame(1, DB::transactionLevel());
    }

    public function test_actualizar_un_paciente_inexistente_devuelve_404(): void
    {
        $this->putJson('/crud-transactions/pacientes/999999', ['nombre' => 'Paciente nuevo'])
            ->assertNotFound();
    }

    public function test_eliminar_un_paciente_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/crud-transactions/pacientes/999999')
            ->assertNotFound();
    }
}

class CrudTransactionsController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass = Paciente::class;

    protected string $resourceName = 'Paciente';

    protected string $permissionPrefix = '';

    protected function validationRules(?int $id): array
    {
        return ['nombre' => ['required', 'string']];
    }

    protected function beforeStore(array $data): array
    {
        if ($data['nombre'] === 'Fallo de guardado') {
            throw new RuntimeException('No se pudo guardar el paciente.');
        }

        return $data;
    }
}
