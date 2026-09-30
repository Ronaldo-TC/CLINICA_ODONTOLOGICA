<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'recibo',
        'monto',
        'costo_total',
        'cancelado',
        'habilitada',
        'descripcion',
        'ci',
        'tipo',
        'tipo_pago',
        'paciente_nombre_completo',
        'grado',
        'tratamiento_id',
        'clinica_id',
        'remision_id',
        'usuario_id',
    ];

    protected $casts = [
        'habilitada'  => 'boolean',
        'costo_total' => 'decimal:2',
        'cancelado'   => 'decimal:2',
    ];

    public function tratamiento()
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function remision()
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cajaDetalles()
    {
        return $this->hasMany(CajaDetalle::class, 'caja_id');
    }
}
