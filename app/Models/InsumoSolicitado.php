<?php

namespace App\Models;

use App\Services\AccesoClinico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoSolicitado extends Model
{
    protected $table = 'insumos_solicitados';

    protected $fillable = [
        'tipo_solicitud', 'jefatura_aprobado_por', 'fecha_aprobacion_jefatura', 'recibido_por', 'fecha_recepcion',
        'historia_clinica_id', 'tratamiento_id', 'aprobado_por', 'fecha_aprobacion',
        'pieza',
        'insumos',
        'docente_autoriza',
        'docente_id',
        'autorizado',
        'insumo_tratamiento_id',
        'clinica_id',
        'remision_id',
        'tratamiento_realizado_id',
        'sesion_atencion_id',
        'usuario_id',
        'estado',
        'fecha_autorizacion',
        'fecha_solicitud',
        'observaciones',
        'rechazado_por',
        'fecha_rechazo',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_aprobacion_jefatura' => 'datetime', 'fecha_recepcion' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'docente_autoriza' => 'integer',
        'autorizado' => 'boolean',
        'fecha_autorizacion' => 'datetime',
        'fecha_solicitud' => 'date',
        'fecha_rechazo' => 'datetime',
    ];

    /* CONSTANTES DE ESTADO */
    const ESTADO_PENDIENTE = 'pendiente';

    const ESTADO_AUTORIZADO = 'autorizado';

    const ESTADO_APROBADO = 'aprobado';

    const ESTADO_PARCIAL = 'parcial';

    const ESTADO_ENTREGADO = 'entregado';

    const ESTADO_RECHAZADO = 'rechazado';

    const ESTADO_CANCELADO = 'cancelado';

    /* RELACIONES */
    public function aprobadorJefatura(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'jefatura_aprobado_por');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por');
    }

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class);
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class);
    }

    public function encargado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function insumoTratamiento(): BelongsTo
    {
        return $this->belongsTo(InsumoTratamiento::class, 'insumo_tratamiento_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }

    public function tratamientoRealizado(): BelongsTo
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function sesionAtencion(): BelongsTo
    {
        return $this->belongsTo(SesionAtencion::class);
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(InsumoEntrega::class, 'insumo_solicitado_id');
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(InsumoTratamientoUsado::class, 'insumo_solicitado_id');
    }

    public function scopeVisibleTo(Builder $query, Usuario $usuario): Builder
    {
        $query->where('tipo_solicitud', 'clinica');
        if ($usuario->hasAnyRole(['admin', 'subadmin', 'insumos'])) {
            return $query;
        }

        if ($usuario->hasRole('jefe-catedra')) {
            return $query->whereIn('clinica_id', AccesoClinico::clinicas($usuario));
        }

        return $query->where(function (Builder $scope) use ($usuario): void {
            $scope->where('usuario_id', $usuario->id);
            if ($usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
                $scope->orWhereHas('sesionAtencion', fn (Builder $sesion) => $sesion->where('docente_id', $usuario->id))
                    ->orWhereHas('historiaClinica', fn (Builder $historia) => $historia->where('docente_id', $usuario->id))
                    ->orWhereHas('tratamientoRealizado.historiaClinica', fn (Builder $historia) => $historia->where('docente_id', $usuario->id));
            }
        });
    }

    public function detalles(): HasMany
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
        return in_array($this->estado, [self::ESTADO_AUTORIZADO, self::ESTADO_APROBADO, self::ESTADO_PARCIAL], true);
    }

    public function getPuedeRechazarseAttribute(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_AUTORIZADO, self::ESTADO_APROBADO], true);
    }
}
