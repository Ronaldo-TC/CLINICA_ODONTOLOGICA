<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoTratamientoUsado extends Model
{
    protected $table = 'insumos_tratamiento_usados';

    protected $fillable = [
        'insumo_id',
        'tratamiento_realizado_id',
        'cantidad_usada',
        'costo_al_momento',
        'fecha',
    ];

    protected $casts = [
        'cantidad_usada'   => 'decimal:2',
        'costo_al_momento' => 'decimal:2',
        'fecha'            => 'date',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function tratamientoRealizado()
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }
}
