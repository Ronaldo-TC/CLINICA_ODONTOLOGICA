<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'nombre',
        'cantidad_numerica',
        'cantidad',
        'stock_minimo',
        'costo_unitario',
        'insumo_tratamiento_id',
        'clinica_id',
    ];

    protected $casts = [
        'cantidad_numerica' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
    ];

    /* RELACIONES */
    public function tratamientos(): BelongsToMany
    {
        return $this->belongsToMany(Tratamiento::class, 'insumo_tratamiento', 'insumo_id', 'tratamiento_id')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function insumoTratamiento()
    {
        return $this->belongsTo(InsumoTratamiento::class, 'insumo_tratamiento_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function detalles()
    {
        return $this->hasMany(InsumoSolicitadoDetalle::class, 'insumo_id');
    }

    public function usados()
    {
        return $this->hasMany(InsumoTratamientoUsado::class, 'insumo_id');
    }

    /* ACCESSORS */
    public function getStockBajoAttribute(): bool
    {
        return $this->cantidad_numerica <= $this->stock_minimo;
    }

    public function getAgotadoAttribute(): bool
    {
        return $this->cantidad_numerica <= 0;
    }

    public function getValorTotalAttribute(): float
    {
        return (float) ($this->cantidad_numerica * $this->costo_unitario);
    }

    /* SCOPES */
    public function scopeStockBajo($q)
    {
        return $q->whereColumn('cantidad_numerica', '<=', 'stock_minimo');
    }
}
