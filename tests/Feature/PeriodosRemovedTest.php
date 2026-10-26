<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeriodosRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_modulo_y_sus_referencias_fueron_eliminados(): void
    {
        $this->assertFalse(Schema::hasTable('periodos'));

        foreach (['admisiones', 'remisiones', 'historias_clinicas', 'cajas', 'esterilizaciones', 'radiologias'] as $tabla) {
            $this->assertFalse(Schema::hasColumn($tabla, 'periodo_id'));
        }

        $this->get('/periodos')->assertNotFound();
    }
}
