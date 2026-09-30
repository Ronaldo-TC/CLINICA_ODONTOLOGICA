<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TratamientoRealizado extends Model
{
    protected $table = 'tratamientos_realizados';

    protected $fillable = [
        'historia_clinica_id',
        'tratamiento_id',
        'clinica_id',
        'pieza_dental',
        'estado',
        'costo_cobrado',
        'observacion',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'costo_cobrado' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class);
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class);
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function sesionesAtencion(): HasMany
    {
        return $this->hasMany(SesionAtencion::class, 'tratamiento_realizado_id');
    }

    public function insumosUsados(): HasMany
    {
        return $this->hasMany(InsumoTratamientoUsado::class, 'tratamiento_realizado_id');
    }

    public function cajaDetalles(): HasMany
    {
        return $this->hasMany(CajaDetalle::class, 'tratamiento_realizado_id');
    }
}
