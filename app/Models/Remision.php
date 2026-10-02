<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remision extends Model
{
    protected $table = 'remisiones';

    protected $fillable = [
        'admision_id',
        'paciente_id',
        'clinica_id',
        'periodo_id',
        'habilitado',
    ];

    protected $casts = [
        'habilitado' => 'boolean',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function admision()
    {
        return $this->belongsTo(Admision::class, 'admision_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'remision_id');
    }

    public function insumosSolicitados()
    {
        return $this->hasMany(InsumoSolicitado::class, 'remision_id');
    }

    public function radiologias()
    {
        return $this->hasMany(Radiologia::class, 'remision_id');
    }
}
