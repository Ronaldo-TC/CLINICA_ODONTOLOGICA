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

    //  Relación: un tratamiento pertenece a una clínica
    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }
}
