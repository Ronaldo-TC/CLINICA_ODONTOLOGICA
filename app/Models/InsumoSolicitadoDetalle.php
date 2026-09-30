<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'cantidad_entregada'  => 'decimal:2',
        'fecha'               => 'date',
    ];

    public function insumoSolicitado()
    {
        return $this->belongsTo(InsumoSolicitado::class, 'insumo_solicitado_id');
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }
}
