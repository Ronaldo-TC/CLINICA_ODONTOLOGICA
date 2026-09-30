<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoTratamiento extends Model
{
    protected $table = 'insumos_tratamiento';

    protected $fillable = ['nombre', 'descripcion', 'clinica_id'];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'insumo_tratamiento_id');
    }
}
