<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function usuario(): BelongsTo
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

    public function scopeVisiblePara(Builder $query, Usuario $usuario): Builder
    {
        return $usuario->hasRole('admin')
            ? $query
            : $query->where('usuario_id', $usuario->id);
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

    /** @return array<string, mixed> */
    public function getDetallesAttribute(): array
    {
        $data = json_decode((string) $this->datos, true);

        return is_array($data) ? $data : [];
    }

    public function getDescripcionAttribute(): string
    {
        if (is_string($this->detalles['descripcion'] ?? null)) {
            return $this->detalles['descripcion'];
        }

        if ($this->tipo === 'odontograma' && isset($this->detalles['admision_id'])) {
            return 'Actualización de odontograma · Admisión #'.$this->detalles['admision_id'];
        }

        return (string) $this->datos;
    }

    public function getUsuarioNombreAttribute(): string
    {
        return data_get($this->detalles, 'usuario.nombre') ?? $this->usuario?->nombre_completo ?? 'Sin usuario registrado';
    }

    public function getUsuarioCuentaAttribute(): string
    {
        return data_get($this->detalles, 'usuario.cuenta') ?? $this->usuario?->nombre_usuario ?? '';
    }

    public function getEstadoNombreAttribute(): string
    {
        return match ($this->estado) {
            'exito', 'success', 'ok' => 'Exitosa',
            'error', 'fallo' => 'Fallida',
            default => $this->estado ?: 'Sin resultado',
        };
    }
}
