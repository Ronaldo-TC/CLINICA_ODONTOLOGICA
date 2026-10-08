<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'tratamiento_realizado_id', 'paciente_id', 'periodo_id',
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
        'monto' => 'integer',
        'costo_total' => 'decimal:2',
        'cancelado' => 'decimal:2',
        'habilitada' => 'boolean',
        'ci' => 'integer',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function tratamientoRealizado(): BelongsTo
    {
        return $this->belongsTo(TratamientoRealizado::class);
    }

    public function recibosAtencion(): HasMany
    {
        return $this->hasMany(self::class, 'tratamiento_realizado_id', 'tratamiento_realizado_id')->where('habilitada', true);
    }

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

    public function detalles()
    {
        return $this->hasMany(CajaDetalle::class, 'caja_id');
    }

    public function radiologias()
    {
        return $this->hasMany(Radiologia::class, 'caja_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getSaldoAttribute(): float
    {
        return (float) $this->costo_total - (float) $this->cancelado;
    }

    public function getEstadoPagoAttribute(): string
    {
        if ($this->cancelado <= 0) {
            return 'pendiente';
        }
        if ($this->cancelado >= $this->costo_total) {
            return 'pagado';
        }

        return 'parcial';
    }
}
