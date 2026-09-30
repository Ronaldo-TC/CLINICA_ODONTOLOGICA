<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaDetalle extends Model
{
    protected $table = 'caja_detalles';

    protected $fillable = [
        'caja_id',
        'tratamiento_realizado_id',
        'concepto',
        'cantidad',
        'precio_unitario',
        'monto',
        'fecha',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'monto'           => 'decimal:2',
        'fecha'           => 'date',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function tratamientoRealizado()
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }
}
