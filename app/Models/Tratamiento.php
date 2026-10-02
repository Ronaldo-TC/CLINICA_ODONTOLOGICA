<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    protected $table = 'tratamientos';

    protected $fillable = [
        'nombre',
        'costo',
        'porcentaje_pg',
        'descripcion',
        'clinica_id',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function tratamientosRealizados()
    {
        return $this->hasMany(TratamientoRealizado::class, 'tratamiento_id');
    }
}
