<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odontograma extends Model
{
    use HasFactory;

    protected $table = 'odontogramas';

    /**
     * Campos asignables en masa.
     * NO incluir 'id' ni timestamps.
     */
    protected $fillable = [
        'admision_id',
        'paciente_id',
        'historia_clinica_id',
        'version',
        'tipo',
        'estado',
        'layout_3d',   // si usas la migración mejorada
        // 'datos_json',  // si usas tu migración original
        'usuario_id',
    ];

    /**
     * Casts: Laravel convierte automáticamente JSON ↔ array.
     * Esto es lo que Three.js va a consumir sin parsear.
     */
    protected $casts = [
        'layout_3d' => 'array',
        'datos_json' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // RELACIONES
    // ─────────────────────────────────────────────

    public function admision(): BelongsTo
    {
        return $this->belongsTo(Admision::class, 'admision_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function hallazgos(): HasMany
    {
        return $this->hasMany(OdontogramaHallazgo::class, 'odontograma_id');
    }

    // ─────────────────────────────────────────────
    // SCOPES (para consultas limpias en el controlador)
    // ─────────────────────────────────────────────

    public function scopeFinalizados(Builder $query): Builder
    {
        return $query->where('estado', 'finalizado');
    }

    public function scopeBorradores(Builder $query): Builder
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeDelPaciente(Builder $query, int $pacienteId): Builder
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopeUltimaVersion(Builder $query): Builder
    {
        return $query->orderByDesc('version');
    }

    public function scopeConHallazgos(Builder $query): Builder
    {
        return $query->with(['hallazgos' => function ($q) {
            $q->orderBy('pieza_dental')->orderBy('caras');
        }]);
    }

    // ─────────────────────────────────────────────
    // ACCESSORS / HELPERS
    // ─────────────────────────────────────────────

    /**
     * Agrupa hallazgos por pieza dental. Esto es EXACTAMENTE lo que
     * Three.js necesita para pintar la arcada.
     *
     * Retorna: [
     *   "16" => [ {cara, condicion, color, ...}, {cara, condicion, ...} ],
     *   "17" => [ ... ],
     * ]
     */
    public function getHallazgosPorPiezaAttribute(): array
    {
        return $this->hallazgos
            ->groupBy('pieza_dental')
            ->map(fn ($grupo) => $grupo->map(fn ($h) => [
                'id' => $h->id,
                'pieza_dental' => $h->pieza_dental,
                'caras' => $h->caras,
                'condicion' => $h->condicion,
                'severidad' => $h->severidad,
                'color' => $h->color_three_js,
                'movilidad' => $h->movilidad,
                'sondaje_mm' => $h->sondaje_mm,
                'notas' => $h->notas,
                'tratamiento_realizado_id' => $h->tratamiento_realizado_id,
            ])->values()->toArray())
            ->toArray();
    }

    /**
     * Resumen rápido para dashboards: cuántas caries, obturaciones, etc.
     */
    public function getResumenCondicionesAttribute(): array
    {
        return $this->hallazgos
            ->groupBy('condicion')
            ->map(fn ($grupo) => $grupo->count())
            ->toArray();
    }

    /**
     * ¿Tiene hallazgos sin tratamiento asociado? Útil para alertas.
     */
    public function getTieneHallazgosPendientesAttribute(): bool
    {
        return $this->hallazgos()
            ->whereNull('tratamiento_realizado_id')
            ->exists();
    }

    /**
     * Datos listos para enviar al frontend (API JSON).
     * Un solo método → un solo JSON → Three.js renderiza.
     */
    public function toThreeJsPayload(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'layout_3d' => $this->layout_3d ?? $this->datos_json,
            'hallazgos' => $this->hallazgos_por_pieza,
            'resumen' => $this->resumen_condiciones,
            'paciente' => $this->paciente?->only(['id', 'nombre', 'apellido_paterno']),
            'actualizado_en' => $this->updated_at?->toIso8601String(),
        ];
    }
}
