<?php

namespace Database\Factories;

use App\Models\EsterilizacionCiclo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsterilizacionCiclo>
 */
class EsterilizacionCicloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipo' => 'Autoclave de prueba',
            'numero_carga' => fake()->unique()->numerify('PRUEBA-#####'),
            'protocolo' => 'Protocolo de prueba',
            'inicio' => now()->subHour(),
            'fin' => now()->subMinutes(30),
            'temperatura' => 121,
            'presion' => 1.1,
            'minutos_exposicion' => 15,
            'codigos_material' => [],
            'version_preparacion' => 1,
            'preparacion' => [],
            'estado' => 'registrado',
        ];
    }

    public function revisado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => 'revisado', 'control_fisico' => true, 'control_quimico' => true,
            'control_biologico' => 'conforme', 'fecha_revision' => now(),
        ]);
    }
}
