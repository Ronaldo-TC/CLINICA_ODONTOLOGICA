<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Insumo
 */
class InsumoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'nombre'            => $this->nombre,
            'cantidad_numerica' => (float) $this->cantidad_numerica,
            'unidad'            => $this->cantidad,
            'stock_minimo'      => (float) $this->stock_minimo,
            'costo_unitario'    => (float) $this->costo_unitario,
            'stock_bajo'        => $this->stock_bajo,
            'agotado'           => $this->agotado,
            'clinica'           => $this->whenLoaded('clinica', fn () => [
                'id'           => $this->clinica?->id,
                'especialidad' => $this->clinica?->especialidad,
            ]),
            'tipo'              => $this->whenLoaded('insumoTratamiento', fn () => $this->insumoTratamiento?->nombre),
        ];
    }
}
