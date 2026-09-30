<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'motivo_consulta',
        'antecedentes',
        'gmh',
        'gpe',
        'signos_vitales',
        'ee',
        'cn',
        'odontograma',
        'odontograma_pintado',
        'atm',
        'bm',
        'gm',
        'calculo',
        'oclusion',
        'pieza_dental',
        'diagnostico',
        'examen',
        'firma',
        'consentimiento_datos',
        'consentimiento_investigacion',
        'estudiante_id',
        'especialidad_estudiante',
        'responsable_id',
        'especialidad_responsable',
    ];

    protected $casts = [
        'consentimiento_datos'         => 'boolean',
        'consentimiento_investigacion' => 'boolean',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
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

    public function consentimientos()
    {
        return $this->hasMany(Consentimiento::class, 'admision_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'admision_id');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'admision_id');
    }
}
