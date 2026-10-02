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
        'docente_autoriza'   => 'integer',
        'autorizado'         => 'boolean',
        'fecha_autorizacion' => 'datetime',
    ];

    /* CONSTANTES DE ESTADO */
    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_AUTORIZADO = 'autorizado';
    const ESTADO_ENTREGADO  = 'entregado';
    const ESTADO_RECHAZADO  = 'rechazado';
    const ESTADO_CANCELADO  = 'cancelado';

    /* RELACIONES */
    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

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

    public function detalles()
    {
        return $this->hasMany(InsumoSolicitadoDetalle::class, 'insumo_solicitado_id');
    }

    /* ACCESSORS */
    public function getPuedeAutorizarseAttribute(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function getPuedeEntregarseAttribute(): bool
    {
        return $this->estado === self::ESTADO_AUTORIZADO;
    }

    public function getPuedeRechazarseAttribute(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
}
