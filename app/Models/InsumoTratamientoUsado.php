<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoTratamientoUsado extends Model
{
    protected $table = 'insumos_tratamiento_usados';

    protected $fillable = [
        'insumo_id',
        'insumo_solicitado_id',
        'insumo_solicitado_detalle_id',
        'sesion_atencion_id',
        'usuario_id',
        'cantidad_solicitada',
        'tratamiento_realizado_id',
        'cantidad_usada',
        'costo_al_momento',
        'fecha_uso',
    ];

    protected $casts = [
        'cantidad_usada' => 'decimal:2',
        'costo_al_momento' => 'decimal:2',
        'fecha_uso' => 'date',
        'cantidad_solicitada' => 'decimal:2',
    ];

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function tratamientoRealizado(): BelongsTo
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitado::class, 'insumo_solicitado_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(InsumoSolicitadoDetalle::class, 'insumo_solicitado_detalle_id');
    }

    public function sesionAtencion(): BelongsTo
    {
        return $this->belongsTo(SesionAtencion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
