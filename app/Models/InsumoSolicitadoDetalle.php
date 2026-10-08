<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoSolicitadoDetalle extends Model
{
    protected $table = 'insumos_solicitados_detalle';

    protected $fillable = [
        'insumo_solicitado_id',
        'insumo_id',
        'cantidad_solicitada',
        'cantidad_entregada',
        'fecha',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:2',
        'cantidad_entregada' => 'decimal:2',
        'fecha' => 'date',
    ];

    /* RELACIONES */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitado::class, 'insumo_solicitado_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    /* ACCESSORS */
    public function consumos(): HasMany
    {
        return $this->hasMany(InsumoTratamientoUsado::class, 'insumo_solicitado_detalle_id');
    }

    public function getDisponibleUsoAttribute(): float
    {
        return round((float) $this->cantidad_entregada - (float) ($this->consumos_sum_cantidad_usada ?? $this->consumos()->sum('cantidad_usada')), 2);
    }

    public function getPendienteAttribute(): float
    {
        return (float) ($this->cantidad_solicitada - $this->cantidad_entregada);
    }

    public function getEntregadoCompletoAttribute(): bool
    {
        return $this->cantidad_entregada >= $this->cantidad_solicitada;
    }
}
