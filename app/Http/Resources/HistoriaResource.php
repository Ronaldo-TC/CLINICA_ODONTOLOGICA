<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\HistoriaClinica
 */
class HistoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'estado'      => $this->estado,
            'autorizado'  => (bool) $this->autorizado,
            'paciente'    => $this->whenLoaded('paciente', fn () => [
                'id'              => $this->paciente?->id,
                'nombre_completo' => $this->paciente?->nombre_completo,
                'ci'              => $this->paciente?->ci,
            ]),
            'clinica'     => $this->whenLoaded('clinica', fn () => [
                'id'           => $this->clinica?->id,
                'especialidad' => $this->clinica?->especialidad,
            ]),
            'estudiante'  => $this->whenLoaded('estudiante', fn () => $this->estudiante?->nombre_completo),
            'docente'     => $this->whenLoaded('docente', fn () => $this->docente?->nombre_completo),
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin'   => $this->fecha_fin?->format('Y-m-d'),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
