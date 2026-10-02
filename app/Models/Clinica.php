<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    protected $table = 'clinicas';

    protected $fillable = [
        'modulo',
        'especialidad',
        'curso',
    ];

    protected $casts = [
        'curso' => 'integer',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    /**
     * Usuarios asignados a esta clínica (a través de la tabla pivote).
     */
    public function especialidades()
    {
        return $this->hasMany(Especialidad::class, 'clinica_id');
    }

    /**
     * Tratamientos disponibles en esta clínica.
     */
    public function tratamientos()
    {
        return $this->hasMany(Tratamiento::class, 'clinica_id');
    }

    /**
     * Tipos de insumos de esta clínica.
     */
    public function insumosTratamiento()
    {
        return $this->hasMany(InsumoTratamiento::class, 'clinica_id');
    }

    /**
     * Insumos directos de esta clínica.
     */
    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'clinica_id');
    }

    /**
     * Remisiones que llegan a esta clínica.
     */
    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'clinica_id');
    }

    /**
     * Historias clínicas de esta clínica.
     */
    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'clinica_id');
    }

    /**
     * Cajas asociadas a esta clínica.
     */
    public function cajas()
    {
        return $this->hasMany(Caja::class, 'clinica_id');
    }

    /**
     * Radiologías de esta clínica.
     */
    public function radiologias()
    {
        return $this->hasMany(Radiologia::class, 'clinica_id');
    }

    /**
     * Esterilizaciones de esta clínica.
     */
    public function esterilizaciones()
    {
        return $this->hasMany(Esterilizacion::class, 'clinica_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->modulo} — {$this->especialidad}";
    }
}
