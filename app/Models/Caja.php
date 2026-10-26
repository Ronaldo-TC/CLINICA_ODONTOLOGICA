<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'cajas';

    public static function siguienteNumeroRecibo(): ?int
    {
        $ultimoNumero = static::query()
            ->orderByDesc('recibo')
            ->lockForUpdate()
            ->value('recibo');
        $siguienteNumero = max(0, (int) $ultimoNumero) + 1;

        return $siguienteNumero <= 2_147_483_647 ? $siguienteNumero : null;
    }

    protected $fillable = [
        'tratamiento_realizado_id', 'paciente_id',
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
        'solicitud_radiologica_id',
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
        return $this->hasMany(self::class, 'tratamiento_realizado_id', 'tratamiento_realizado_id')
            ->whereNotNull('tratamiento_realizado_id')
            ->whereNull('solicitud_radiologica_id')
            ->where('habilitada', true);
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

    public function solicitudRadiologica(): BelongsTo
    {
        return $this->belongsTo(SolicitudRadiologica::class, 'solicitud_radiologica_id');
    }

    public function recibosRadiologicos(): HasMany
    {
        return $this->hasMany(self::class, 'solicitud_radiologica_id', 'solicitud_radiologica_id')
            ->whereNotNull('solicitud_radiologica_id')
            ->where('habilitada', true);
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
