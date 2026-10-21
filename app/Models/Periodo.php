<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $table = 'periodos';

    protected $fillable = [
        'nombre',
        'gestion',
        'fecha_inicio',
        'fecha_fin',
        'habilitado',
    ];

    protected $casts = [
        'gestion' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'habilitado' => 'boolean',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function admisiones()
    {
        return $this->hasMany(Admision::class, 'periodo_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'periodo_id');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'periodo_id');
    }

    public function esterilizaciones()
    {
        return $this->hasMany(Esterilizacion::class, 'periodo_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} ({$this->gestion})";
    }

    public function getEstaActivoAttribute(): bool
    {
        $hoy = now();

        return $this->habilitado
            && $hoy->between($this->fecha_inicio, $this->fecha_fin);
    }
}
