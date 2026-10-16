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

    /** @return array{estado: string, costo: float, pagado: float, saldo: float} */
    public function resumenPago(): array
    {
        $this->loadMissing(['tratamiento', 'cajas.detalles', 'cajaDetalles.caja.detalles']);
        $recibos = $this->cajas
            ->concat($this->cajaDetalles->pluck('caja')->filter())
            ->unique('id')
            ->filter(function (Caja $recibo): bool {
                return $recibo->habilitada && (int) $recibo->recibo > 0
                    && ((int) $recibo->tratamiento_realizado_id === $this->id
                        || ($recibo->tratamiento_realizado_id === null
                            && $recibo->detalles->pluck('tratamiento_realizado_id')->unique()->all() === [$this->id]));
            });
        $costo = (int) round((float) ($this->costo_cobrado ?? $this->tratamiento?->costo) * 100);
        $pagado = (int) $recibos->sum(fn (Caja $recibo): int => (int) round((float) $recibo->cancelado * 100));

        return ['estado' => $recibos->isNotEmpty() && $pagado >= $costo ? 'pagado' : ($pagado > 0 ? 'parcial' : 'sin_pago'),
            'costo' => $costo / 100, 'pagado' => $pagado / 100, 'saldo' => max(0, $costo - $pagado) / 100];
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

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'tratamiento_realizado_id');
    }
}
