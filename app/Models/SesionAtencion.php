<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionAtencion extends Model
{
    protected $table = 'sesiones_atencion';

    protected $fillable = [
        'tratamiento_realizado_id',
        'fecha',
        'descripcion',
        'estudiante_id',
        'docente_id',
        'observacion_docente',
        'autorizado',
        'autorizado_por',
        'fecha_autorizacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'autorizado' => 'boolean',
        'fecha_autorizacion' => 'datetime',
    ];

    public function tratamientoRealizado(): BelongsTo
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por');
    }
}
