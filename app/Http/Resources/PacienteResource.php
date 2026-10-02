<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Paciente
 */
class PacienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'ci'               => $this->ci,
            'nombre'           => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombre_completo'  => $this->nombre_completo,
            'genero'           => $this->genero,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'edad'             => $this->edad,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
