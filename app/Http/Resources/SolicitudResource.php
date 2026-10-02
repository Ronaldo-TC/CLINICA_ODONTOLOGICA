<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InsumoSolicitado
 */
class SolicitudResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'estado'    => $this->estado,
            'pieza'     => $this->pieza,
            'clinica'   => $this->whenLoaded('clinica', fn () => [
                'id'           => $this->clinica?->id,
                'especialidad' => $this->clinica?->especialidad,
            ]),
            'solicitante' => $this->whenLoaded('estudiante', fn () => $this->estudiante?->nombre_completo),
            'docente'     => $this->whenLoaded('docente', fn () => $this->docente?->nombre_completo),
            'autorizado'  => (bool) $this->autorizado,
            'fecha_autorizacion' => $this->fecha_autorizacion?->toIso8601String(),
            'detalles'    => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'id'                  => $d->id,
                'insumo_id'           => $d->insumo_id,
                'insumo'              => $d->insumo?->nombre,
                'unidad'              => $d->insumo?->cantidad,
                'cantidad_solicitada' => (float) $d->cantidad_solicitada,
                'cantidad_entregada'  => (float) $d->cantidad_entregada,
                'pendiente'           => $d->pendiente,
            ])),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
