<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tratamiento extends Model
{
    protected $table = 'tratamientos';

    protected $fillable = [
        'nombre',
        'costo',
        'precio_asignado',
        'descripcion',
        'clinica_id',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'precio_asignado' => 'boolean',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function tratamientosRealizados(): HasMany
    {
        return $this->hasMany(TratamientoRealizado::class, 'tratamiento_id');
    }

    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(Insumo::class, 'insumo_tratamiento', 'tratamiento_id', 'insumo_id')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
