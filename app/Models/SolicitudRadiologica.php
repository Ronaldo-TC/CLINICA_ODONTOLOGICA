<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolicitudRadiologica extends Model
{
    public const PENDIENTE = 'pendiente';

    public const OBSERVADA = 'observada';

    public const RECHAZADA = 'rechazada';

    public const AUTORIZADA = 'autorizada';

    protected $table = 'solicitudes_radiologicas';

    protected $fillable = [
        'historia_clinica_id',
        'paciente_id',
        'clinica_id',
        'solicitante_id',
        'docente_id',
        'revisado_por',
        'estado',
        'descripcion',
        'observacion',
        'costo_autorizado',
        'fecha_revision',
    ];

    protected $casts = [
        'costo_autorizado' => 'decimal:2',
        'fecha_revision' => 'datetime',
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(Caja::class, 'solicitud_radiologica_id');
    }

    public function estudio(): HasOne
    {
        return $this->hasOne(Radiologia::class, 'solicitud_radiologica_id');
    }

    public function montoPagado(): float
    {
        if ($this->relationLoaded('recibos')) {
            return (float) $this->recibos->where('habilitada', true)->sum('cancelado');
        }

        return (float) $this->recibos()->where('habilitada', true)->sum('cancelado');
    }

    /** @return array{estado: string, autorizado: float, pagado: float, saldo: float} */
    public function resumenPago(): array
    {
        $autorizado = (float) ($this->costo_autorizado ?? 0);
        $pagado = min($autorizado, $this->montoPagado());

        return [
            'estado' => $pagado <= 0 ? 'pendiente' : ($pagado >= $autorizado ? 'pagado' : 'parcial'),
            'autorizado' => $autorizado,
            'pagado' => $pagado,
            'saldo' => max(0, $autorizado - $pagado),
        ];
    }
}
