<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autorizacion extends Model
{
    protected $table = 'autorizaciones';

    protected $primaryKey = 'esterilizacion_id';
    public $incrementing = false;

    protected $fillable = [
        'esterilizacion_id',
        'remision_id',
        'estado',
        'descripcion',
        'docente',
        'sobre',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function esterilizacion()
    {
        return $this->belongsTo(Esterilizacion::class, 'esterilizacion_id');
    }

    public function remision()
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }
}
