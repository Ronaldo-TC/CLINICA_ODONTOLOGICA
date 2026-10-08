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
        'autorizado_por',
        'fecha_autorizacion',
        'finalizado_por',
        'fecha_finalizacion',
        'culminado_por',
        'fecha_culminacion',
    ];

    protected $casts = [
        'costo_cobrado' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_autorizacion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'fecha_culminacion' => 'datetime',
    ];

    public function estaAutorizado(): bool
    {
        return $this->autorizado_por !== null && $this->fecha_autorizacion !== null;
    }

    public function estaCerrado(): bool
    {
        return in_array($this->estado, ['finalizado', 'culminado', 'realizado', 'completado', 'cancelado'], true);
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por');
    }

    public function finalizador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'finalizado_por');
    }

    public function culminador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'culminado_por');
    }

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
