<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoriaClinica extends Model
{
    protected $table = 'historias_clinicas';

    protected $fillable = [
        'remision_id',
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
        'autorizado' => 'boolean',
        'revision_cualquiera' => 'boolean',
        'estado_revision' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function admision()
    {
        return $this->belongsTo(Admision::class, 'admision_id');
    }

    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remision::class);
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

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function tratamientosRealizados()
    {
        return $this->hasMany(TratamientoRealizado::class, 'historia_clinica_id');
    }

    public function solicitudesRadiologicas(): HasMany
    {
        return $this->hasMany(SolicitudRadiologica::class, 'historia_clinica_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'historia_clinica_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getEstaCompletaAttribute(): bool
    {
        return $this->tratamientosRealizados()->count() > 0;
    }
}
