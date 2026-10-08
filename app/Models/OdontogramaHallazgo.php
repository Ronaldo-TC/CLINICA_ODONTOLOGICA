<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramaHallazgo extends Model
{
    use HasFactory;

    protected $table = 'odontograma_hallazgos';

    protected $fillable = [
        'odontograma_id',
        'pieza_dental',
        'caras',
        'condicion',
        'severidad',
        'color',
        'movilidad',
        'sondaje_mm',
        'notas',
        'tratamiento_realizado_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Casts: aquí está la clave para que Three.js reciba
     * números reales, no strings.
     */
    protected $casts = [
        'pieza_dental' => 'string',
        'movilidad' => 'integer',
        'sondaje_mm' => 'decimal:1',
        'tratamiento_realizado_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // RELACIONES
    // ─────────────────────────────────────────────

    public function odontograma(): BelongsTo
    {
        return $this->belongsTo(Odontograma::class, 'odontograma_id');
    }

    public function tratamientoRealizado(): BelongsTo
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'updated_by');
    }

    // ─────────────────────────────────────────────
    // SCOPES (muy útiles para reportes y filtros)
    // ─────────────────────────────────────────────

    public function scopeDePieza(Builder $query, string $pieza): Builder
    {
        return $query->where('pieza_dental', $pieza);
    }

    public function scopeDeCondicion(Builder $query, string $condicion): Builder
    {
        return $query->where('condicion', $condicion);
    }

    public function scopeConCara(Builder $query, string $cara): Builder
    {
        // Busca en "M", "MOD", "MO", etc.
        return $query->where('caras', 'LIKE', "%{$cara}%");
    }

    public function scopePendientesDeTratamiento(Builder $query): Builder
    {
        return $query->whereNull('tratamiento_realizado_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        // Excluye los que ya tienen tratamiento
        return $query->whereNull('tratamiento_realizado_id');
    }

    // ─────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────

    /**
     * Convierte "MOD" → ["mesial", "oclusal", "distal"].
     * Three.js pinta cada cara por separado.
     */
    public function getCarasArrayAttribute(): array
    {
        $mapa = [
            'M' => 'mesial',
            'O' => 'oclusal',
            'D' => 'distal',
            'V' => 'vestibular',
            'L' => 'lingual',
            'P' => 'palatino',
        ];

        $caras = str_split(strtoupper($this->caras ?? ''));

        return array_values(array_filter(
            array_map(fn ($c) => $mapa[$c] ?? null, $caras)
        ));
    }

    /**
     * Color para Three.js. Si no hay color guardado, se calcula
     * según la condición usando una paleta clínica estándar.
     */
    public function getColorThreeJsAttribute(): string
    {
        if (! empty($this->color)) {
            return $this->color;
        }

        return match ($this->condicion) {
            'caries' => '#FF0000',
            'obturacion' => '#0066FF',
            'ausente' => '#808080',
            'corona' => '#FFD700',
            'endodoncia' => '#8B00FF',
            'implante' => '#00CED1',
            'sellante' => '#32CD32',
            'fractura' => '#FF4500',
            'movilidad' => '#FFA500',
            'bolsa_periodontal' => '#8B4513',
            'mancha' => '#D2B48C',
            'desgaste' => '#A9A9A9',
            default => '#FFFFFF',
        };
    }

    /**
     * Payload limpio para Three.js (una sola pieza dental).
     */
    public function toThreeJsPayload(): array
    {
        return [
            'id' => $this->id,
            'pieza' => $this->pieza_dental,
            'caras' => $this->caras_array,
            'condicion' => $this->condicion,
            'severidad' => $this->severidad,
            'color' => $this->color_three_js,
            'movilidad' => $this->movilidad,
            'sondaje_mm' => $this->sondaje_mm,
            'notas' => $this->notas,
            'tratado' => ! is_null($this->tratamiento_realizado_id),
        ];
    }

    // ─────────────────────────────────────────────
    // EVENTOS (auditoría automática)
    // ─────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
