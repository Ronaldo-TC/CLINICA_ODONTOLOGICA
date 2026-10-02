<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notificacion
 */
class NotificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'tipo'    => $this->tipo,
            'titulo'  => $this->titulo,
            'mensaje' => $this->mensaje,
            'url'     => $this->url,
            'leida'   => (bool) $this->leida,
            'tono'    => $this->tono,
            'fecha'   => $this->created_at?->toIso8601String(),
            'hace'    => $this->tiempo_relativo,
        ];
    }
}
