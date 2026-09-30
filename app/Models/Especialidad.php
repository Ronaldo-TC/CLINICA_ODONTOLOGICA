<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';

    // PK compuesta: Eloquent no la soporta oficialmente,
    // pero para queries de lectura funciona bien.
    protected $primaryKey = 'usuario_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'usuario_id',
        'clinica_id',
        'habilitado',
        'rotacion',
        'responsable_admision',
        'fecha_inicio',
    ];

    protected $casts = [
        'habilitado'            => 'boolean',
        'responsable_admision'  => 'boolean',
        'fecha_inicio'          => 'date',
    ];

    // ─── Relaciones ───

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }
}
