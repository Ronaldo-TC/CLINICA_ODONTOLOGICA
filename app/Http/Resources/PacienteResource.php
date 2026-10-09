<?php

namespace App\Http\Resources;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Paciente
 */
class PacienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ci' => $this->ci,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombre_completo' => $this->nombre_completo,
            'genero' => $this->genero,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'edad' => $this->edad,
            'telefono' => $this->telefono,
            'estado_civil' => $this->estado_civil,
            'escuela' => $this->escuela,
            'ocupacion' => $this->ocupacion,
            'direccion' => $this->direccion,
            'emergencia' => $this->emergencia,
            'telefono_emergencia' => $this->telefono_emergencia,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
