<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\ExcelDataSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExcelDataSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_imports_all_csv_sources_with_relations_and_is_idempotent(): void
    {
        $this->seed(RolesSeeder::class);
        $this->seed(ExcelDataSeeder::class);

        $admin = Usuario::query()->where('nombre_usuario', 'admin')->firstOrFail();
        $blankIdentity = Usuario::query()->where('nombre_usuario', 'legacy_12')->firstOrFail();
        $duplicateUsername = Usuario::query()->where('nombre_usuario', 'ruben_8')->firstOrFail();
        $admission = Usuario::query()->where('nombre_usuario', 'Analia')->firstOrFail();
        $historiaClinicaId = DB::table('clinicas')
            ->where('modulo', 'CLINICA 0')
            ->where('especialidad', 'HISTORIA CLINICA')
            ->value('id');

        $this->assertTrue(Hash::check('8_2026', $admin->contrasena));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame('LEGACY-12', $blankIdentity->ci);
        $this->assertSame('Usuario importado 12', $blankIdentity->nombre_completo);
        $this->assertTrue($blankIdentity->hasRole('admision'));
        $this->assertTrue($duplicateUsername->hasRole('esterilizacion'));
        $this->assertSame('', $admission->sesion);
        $this->assertSame('', $admission->sesion_extra);
        $this->assertSame('2025-02-25 18:16:00', $admission->ultimo_login?->format('Y-m-d H:i:s'));

        $this->assertNotNull($historiaClinicaId);
        $this->assertDatabaseHas('especialidades', [
            'usuario_id' => $admin->id,
            'clinica_id' => $historiaClinicaId,
            'habilitado' => true,
        ]);
        $this->assertDatabaseHas('tratamientos', [
            'nombre' => 'PUBLICO PERIAPICAL DIGITAL',
            'costo' => 20,
            'precio_asignado' => true,
        ]);
        $this->assertDatabaseHas('insumos', [
            'nombre' => 'ENDODONCIA MULTIRRADICULAR',
            'cantidad_numerica' => 0,
            'stock_minimo' => 0,
        ]);

        $countsAfterFirstImport = $this->importedTableCounts();

        $this->seed(ExcelDataSeeder::class);

        $this->assertSame($countsAfterFirstImport, $this->importedTableCounts());
    }

    /** @return array<string, int> */
    private function importedTableCounts(): array
    {
        return [
            'clinicas' => DB::table('clinicas')->count(),
            'usuarios' => DB::table('usuarios')->count(),
            'especialidades' => DB::table('especialidades')->count(),
            'tipos_insumo' => DB::table('insumos_tratamiento')->count(),
            'insumos' => DB::table('insumos')->count(),
            'tratamientos' => DB::table('tratamientos')->count(),
            'roles_usuario' => DB::table('model_has_roles')->count(),
        ];
    }
}
