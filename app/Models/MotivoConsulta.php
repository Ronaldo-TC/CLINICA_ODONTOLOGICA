<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoConsulta extends Model
{
    protected $table = 'motivos_consulta';

    protected $fillable = ['nombre', 'descripcion', 'habilitado'];

    protected $casts = [
        'habilitado' => 'boolean',
    ];
}
