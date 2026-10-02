<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificación interna de la aplicación.
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'mensaje',
        'url',
        'leida',
        'fecha_lectura',
    ];

    protected $casts = [
        'leida'         => 'boolean',
        'fecha_lectura' => 'datetime',
    ];

    /* ============================================================
       TIPOS
       ============================================================ */

    public const TIPO_SOLICITUD_CREADA    = 'solicitud_creada';
    public const TIPO_SOLICITUD_AUTORIZADA = 'solicitud_autorizada';
    public const TIPO_SOLICITUD_RECHAZADA = 'solicitud_rechazada';
    public const TIPO_SOLICITUD_ENTREGADA = 'solicitud_entregada';
    public const TIPO_STOCK_BAJO          = 'stock_bajo';
    public const TIPO_ADMISION_NUEVA      = 'admision_nueva';
    public const TIPO_HISTORIA_AUTORIZADA = 'historia_autorizada';
    public const TIPO_RADIOGRAFIA_NUEVA   = 'radiografia_nueva';
    public const TIPO_REMISION_NUEVA      = 'remision_nueva';
    public const TIPO_TRATAMIENTO_AUTORIZADO = 'tratamiento_autorizado';

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /* ============================================================
       SCOPES
       ============================================================ */

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->where('leida', false);
    }

    public function scopeLeidas(Builder $query): Builder
    {
        return $query->where('leida', true);
    }

    public function scopeDelUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /* ============================================================
       ACCIONES
       ============================================================ */

    public function marcarLeida(): bool
    {
        if ($this->leida) {
            return true;
        }

        return $this->update([
            'leida'         => true,
            'fecha_lectura' => now(),
        ]);
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getTiempoRelativoAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    public function getIconoAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_SOLICITUD_CREADA     => 'clipboard',
            self::TIPO_SOLICITUD_AUTORIZADA => 'check-square',
            self::TIPO_SOLICITUD_RECHAZADA  => 'x',
            self::TIPO_SOLICITUD_ENTREGADA  => 'package',
            self::TIPO_STOCK_BAJO           => 'package',
            self::TIPO_ADMISION_NUEVA       => 'user-plus',
            self::TIPO_HISTORIA_AUTORIZADA  => 'file-text',
            self::TIPO_RADIOGRAFIA_NUEVA    => 'image',
            self::TIPO_REMISION_NUEVA       => 'inbox',
            default                         => 'bell',
        };
    }

    public function getTonoAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_SOLICITUD_RECHAZADA => 'danger',
            self::TIPO_STOCK_BAJO          => 'warning',
            self::TIPO_SOLICITUD_AUTORIZADA,
            self::TIPO_SOLICITUD_ENTREGADA,
            self::TIPO_HISTORIA_AUTORIZADA => 'success',
            default                        => 'info',
        };
    }
}
