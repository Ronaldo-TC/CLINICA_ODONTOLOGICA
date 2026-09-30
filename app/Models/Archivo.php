<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    protected $table = 'archivos';

    protected $fillable = [
        'paciente_id',
        'historia_clinica_id',
        'remision_id',
        'radiologia_id',
        'nombre_original',
        'nombre_guardado',
        'ruta',
        'extension',
        'tamano_bytes',
        'tipo',
        'hash',
        'usuario_id',
        'clinica_id',
    ];
}
