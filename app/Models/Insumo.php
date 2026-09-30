<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'stock_minimo'      => 'decimal:2',
        'costo_unitario'    => 'decimal:2',
    ];

    public function insumoTratamiento()
    {
        return $this->belongsTo(InsumoTratamiento::class, 'insumo_tratamiento_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }
}
