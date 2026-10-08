<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radiologia extends Model
{
    protected $table = 'radiologias';

    protected $fillable = [
        'descripcion',
        'observacion',
        'estado',
        'usuario_id',
        'caja_id',
        'clinica_id',
        'paciente_id',
        'periodo_id',
        'remision_id',
        'archivo_ruta',
        'fecha_toma',
    ];

    protected $casts = [
        'fecha_toma' => 'datetime',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function remision()
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'radiologia_id');
    }
}
