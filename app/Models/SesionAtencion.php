<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionAtencion extends Model
{
    protected $table = 'sesiones_atencion';

    protected $fillable = [
        'tratamiento_realizado_id',
        'fecha',
        'descripcion',
        'estudiante_id',
        'docente_id',
        'observacion_docente',
        'autorizado',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'autorizado' => 'boolean',
    ];

    public function tratamientoRealizado()
    {
        return $this->belongsTo(TratamientoRealizado::class, 'tratamiento_realizado_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }
}
