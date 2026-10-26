<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remision extends Model
{
    protected $table = 'remisiones';

    protected $fillable = [
        'tratamiento_propuesto_id', 'observacion_propuesta',
        'estado',
        'estudiante_id',
        'docente_id',
        'asignado_por',
        'fecha_asignacion',
        'admision_id',
        'paciente_id',
        'clinica_id',
        'habilitado',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'habilitado' => 'boolean',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function scopeConDetalle(Builder $query): Builder
    {
        return $query->with([
            'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
            'admision:id,paciente_id',
            'tratamientoPropuesto:id,nombre,clinica_id,costo',
            'clinica:id,especialidad,modulo',
            'docente:id,nombre_completo',
            'estudiante:id,nombre_completo',
        ])->withCount(['historiasClinicas', 'insumosSolicitados']);
    }

    public function admision(): BelongsTo
    {
        return $this->belongsTo(Admision::class, 'admision_id');
    }

    public function tratamientoPropuesto(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_propuesto_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function historiasClinicas(): HasMany
    {
        return $this->hasMany(HistoriaClinica::class, 'remision_id');
    }

    public function insumosSolicitados(): HasMany
    {
        return $this->hasMany(InsumoSolicitado::class, 'remision_id');
    }

    public function radiologias(): HasMany
    {
        return $this->hasMany(Radiologia::class, 'remision_id');
    }
}
