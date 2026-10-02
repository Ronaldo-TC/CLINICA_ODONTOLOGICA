<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacoras';

    protected $fillable = [
        'usuario_id',
        'ip',
        'fecha',
        'tipo',
        'datos',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /* ============================================================
       SCOPES
       ============================================================ */

    public function scopeDeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDelUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha?->format('d/m/Y H:i:s') ?? '—';
    }
}
