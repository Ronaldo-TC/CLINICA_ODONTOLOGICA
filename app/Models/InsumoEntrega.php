<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoEntrega extends Model
{
    protected $table = 'insumos_entregas';

    protected $fillable = ['insumo_solicitado_id', 'insumo_solicitado_detalle_id', 'insumo_id', 'tratamiento_realizado_id', 'sesion_atencion_id', 'usuario_id', 'cantidad', 'costo_unitario', 'fecha_entrega'];

    protected $casts = ['cantidad' => 'decimal:2', 'costo_unitario' => 'decimal:2', 'fecha_entrega' => 'datetime'];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitadoDetalle::class, 'insumo_solicitado_detalle_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitado::class, 'insumo_solicitado_id');
    }
}
