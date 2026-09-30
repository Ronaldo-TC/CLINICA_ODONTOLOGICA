<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoriaClinica extends Model
{
    protected $table = 'historias_clinicas';

    protected $fillable = [
        'admision_id',
        'paciente_id',
        'clinica_id',
        'periodo_id',
        'estado',
        'archivo_nombre',
        'archivo',
        'archivo_hash',
        'autorizado',
        'estudiante_id',
        'especialidad_estudiante',
        'docente_id',
        'especialidad_docente',
        'revision_docente',
        'revision_cualquiera',
        'estado_revision',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'autorizado'          => 'boolean',
        'estado_revision'     => 'boolean',
        'revision_cualquiera' => 'boolean',
        'fecha_inicio'        => 'date',
        'fecha_fin'           => 'date',
    ];

    public function admision(): BelongsTo
    {
        return $this->belongsTo(Admision::class);
    }
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }
    public function tratamientosRealizados(): HasMany
    {
        return $this->hasMany(TratamientoRealizado::class);
    }
    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class);
    }
}
