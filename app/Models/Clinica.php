<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    protected $table = 'clinicas';

    protected $fillable = ['modulo', 'especialidad', 'curso'];

    // ─── Relaciones ───

    public function especialidades()
    {
        return $this->hasMany(Especialidad::class, 'clinica_id');
    }

    public function tratamientos()
    {
        return $this->hasMany(Tratamiento::class, 'clinica_id');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'clinica_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'clinica_id');
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class, 'clinica_id');
    }
}
