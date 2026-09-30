<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoSolicitado extends Model
{
    protected $table = 'insumos_solicitados';

    protected $fillable = [
        'pieza',
        'insumos',
        'docente_autoriza',
        'docente_id',
        'autorizado',
        'insumo_tratamiento_id',
        'clinica_id',
        'remision_id',
        'tratamiento_realizado_id',
        'usuario_id',
        'estado',
        'fecha_autorizacion',
    ];

    protected $casts = [
        'autorizado'         => 'boolean',
        'fecha_autorizacion' => 'datetime',
    ];

    public function insumoTratamiento()
    {
        return $this->belongsTo(InsumoTratamiento::class, 'insumo_tratamiento_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function remision()
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }

    public function tratamientoRealizado()
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function detalles()
    {
        return $this->hasMany(InsumoSolicitadoDetalle::class, 'insumo_solicitado_id');
    }
}
