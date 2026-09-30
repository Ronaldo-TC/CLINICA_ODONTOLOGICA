<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $table = 'periodos';

    protected $fillable = [
        'nombre',
        'gestion',
        'semestre',
        'fecha_inicio',
        'fecha_fin',
        'habilitado',
    ];

    protected $casts = [
        'habilitado'   => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    // ─── Relaciones ───

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
}
