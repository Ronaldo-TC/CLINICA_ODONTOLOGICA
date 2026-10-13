<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Esterilizacion extends Model
{
    protected $table = 'esterilizaciones';

    protected $fillable = [
        'remision_id', 'docente_id', 'autorizado', 'fecha_autorizacion', 'firma_docente', 'hora_entrega', 'hora_recojo', 'recibido_por', 'entregado_por',
        'cantidad_sobres',
        'cantidad_paquetes',
        'habilitada',
        'info',
        'descripcion',
        'fecha_entrega',
        'fecha_recojo',
        'estudiante_id',
        'clinica_id',
        'usuario_id',
        'periodo_id',
        'sobres_recibidos', 'paquetes_recibidos', 'codigos_material', 'observacion_recepcion',
        'fecha_preparacion', 'preparado_por', 'observacion_preparacion', 'recogido_por',
        'preparaciones',
    ];

    protected $casts = [
        'autorizado' => 'boolean', 'fecha_autorizacion' => 'datetime',
        'cantidad_sobres' => 'integer',
        'cantidad_paquetes' => 'integer',
        'habilitada' => 'boolean',
        'fecha_entrega' => 'date',
        'fecha_recojo' => 'date',
        'fecha_preparacion' => 'datetime', 'sobres_recibidos' => 'integer',
        'paquetes_recibidos' => 'integer', 'codigos_material' => 'array',
        'preparaciones' => 'array',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function autorizaciones()
    {
        return $this->hasMany(Autorizacion::class, 'esterilizacion_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getTotalPaquetesAttribute(): int
    {
        return (int) $this->cantidad_paquetes;
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(EsterilizacionCiclo::class, 'esterilizacion_id');
    }

    public function ultimoCiclo(): HasOne
    {
        return $this->hasOne(EsterilizacionCiclo::class, 'esterilizacion_id')->latestOfMany();
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por');
    }

    public function preparador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'preparado_por');
    }

    public function entregador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'entregado_por');
    }

    public function recogedor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recogido_por');
    }

    public function getEstadoProcesoAttribute(): string
    {
        if ($this->fecha_recojo) {
            return 'Recogido';
        }
        if (! $this->habilitada) {
            return 'Deshabilitado';
        }
        if (! $this->autorizado) {
            return 'Pendiente de firma';
        }
        if (! $this->fecha_entrega) {
            return 'Pendiente de recepción';
        }
        if (! $this->fecha_preparacion) {
            return 'Pendiente de preparación';
        }

        return match ($this->ultimoCiclo?->estado) {
            'liberado' => 'Disponible para recojo',
            'rechazado' => count($this->preparaciones ?? []) > $this->ultimoCiclo->version_preparacion
                ? 'Pendiente de nuevo ciclo' : 'Bloqueado: reprocesar',
            'revisado' => 'Pendiente de liberación',
            'pendiente_resultados' => 'Pendiente de resultados',
            'registrado' => 'Pendiente de revisión',
            default => 'Pendiente de ciclo',
        };
    }
}
