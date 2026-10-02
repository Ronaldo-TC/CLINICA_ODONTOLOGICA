<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Esterilizacion extends Model
{
    protected $table = 'esterilizaciones';

    protected $fillable = [
        'cantidad_sobres',
        'cantidad_paquetes',
        'habilitada',
        'info',
        'descripcion',
        'fecha_entrega',
        'fecha_recojo',
        'estudiante_id',
        'clinica_id',
        'usuario_id',
        'periodo_id',
    ];

    protected $casts = [
        'cantidad_sobres'   => 'integer',
        'cantidad_paquetes' => 'integer',
        'habilitada'        => 'boolean',
        'fecha_entrega'     => 'date',
        'fecha_recojo'      => 'date',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function autorizaciones()
    {
        return $this->hasMany(Autorizacion::class, 'esterilizacion_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getTotalPaquetesAttribute(): int
    {
        return (int) $this->cantidad_paquetes;
    }
}
