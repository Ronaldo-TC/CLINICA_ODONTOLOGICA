<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admision extends Model
{
    protected $table = 'admisiones';

    protected $fillable = [
        'paciente_id',
        'periodo_id',
        'motivo_consulta_id',
        'estado_civil',
        'escuela',
        'ocupacion',
        'direccion',
        'emergencia',
        'telefono',
        'telefono_emergencia',
        'historia_enfermedad_actual',
        'peso_kg',
        'talla_mts',
        'temperatura_c',
        'pulso',
        'presion_arterial',
        'frecuencia_respiratoria',
        'motivo_consulta',
        'antecedentes',
        'gmh',
        'gpe',
        'asimetria_facial',
        'cambio_color',
        'abultamiento',
        'ganglios_palpables',
        'ruidos_articulares',
        'bm_estado',
        'gm_estado',
        'registrado_por',
        'signos_vitales',
        'ee',
        'cn',
        'odontograma',
        'odontograma_pintado',
        'odontograma_denticion',
        'atm',
        'bm',
        'gm',
        'calculo',
        'oclusion',
        'pieza_dental',
        'diagnostico',
        'area_derivada',
        'examen',
        'firma',
        'consentimiento_datos',
        'consentimiento_fotografias',
        'consentimiento_investigacion',
        'fecha_admision',
        'estado',
        'autorizado_por',
        'fecha_autorizacion',
        'estudiante_id',
        'especialidad_estudiante',
        'responsable_id',
        'especialidad_responsable',
    ];

    protected $casts = [
        'telefono' => 'string',
        'asimetria_facial' => 'boolean',
        'cambio_color' => 'boolean',
        'abultamiento' => 'boolean',
        'ganglios_palpables' => 'boolean',
        'ruidos_articulares' => 'boolean',
        'consentimiento_datos' => 'boolean',
        'consentimiento_fotografias' => 'boolean',
        'consentimiento_investigacion' => 'boolean',
        'fecha_admision' => 'date',
        'fecha_autorizacion' => 'datetime',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function getFirmaImagenAttribute(): ?string
    {
        return preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', (string) $this->firma) === 1 ? $this->firma : null;
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function motivoConsulta()
    {
        return $this->belongsTo(MotivoConsulta::class, 'motivo_consulta_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function clinicaEstudiante()
    {
        return $this->belongsTo(Clinica::class, 'especialidad_estudiante');
    }

    public function clinicaResponsable()
    {
        return $this->belongsTo(Clinica::class, 'especialidad_responsable');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'admision_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'admision_id');
    }

    public function consentimientos()
    {
        return $this->hasMany(Consentimiento::class, 'admision_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getPacienteNombreAttribute(): string
    {
        return $this->paciente?->nombre_completo ?? '—';
    }
}
