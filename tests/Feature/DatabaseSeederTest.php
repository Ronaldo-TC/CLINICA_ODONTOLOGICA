<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_seeding_does_not_add_demo_records(): void
    {
        $demoCountsBefore = $this->demoCounts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($demoCountsBefore, $this->demoCounts());
    }

    /** @return array<string, int> */
    private function demoCounts(): array
    {
        return [
            'clinical_patients' => DB::table('pacientes')->where('ci', 'like', 'DEMO-CLINICA-%')->count(),
            'odontogram_patients' => DB::table('pacientes')->where('ci', 'ODONTO-DEMO-3D')->count(),
            'odontogram_users' => DB::table('usuarios')->where('nombre_usuario', 'demo_estudiante')->count(),
            'odontogram_clinics' => DB::table('clinicas')->where('modulo', 'Demo')->count(),
        ];
    }
}
