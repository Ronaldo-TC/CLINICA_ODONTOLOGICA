<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    public const CAMPOS_CONTACTO = ['telefono', 'estado_civil', 'escuela', 'ocupacion', 'direccion', 'emergencia', 'telefono_emergencia'];

    protected $table = 'pacientes';

    protected $fillable = [
        'ci',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'genero',
        'fecha_nacimiento',
        ...self::CAMPOS_CONTACTO,
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /* ============================================================
       RELACIONES
       ============================================================ */

    public function admisiones()
    {
        return $this->hasMany(Admision::class, 'paciente_id');
    }

    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class, 'paciente_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'paciente_id');
    }

    public function radiologias()
    {
        return $this->hasMany(Radiologia::class, 'paciente_id');
    }

    /* ============================================================
       ACCESSORS
       ============================================================ */

    public function getNombreCompletoAttribute(): string
    {
        $partes = array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ]);

        return implode(' ', $partes);
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    public function getInicialesAttribute(): string
    {
        $n = mb_substr($this->nombre ?? '', 0, 1);
        $a = mb_substr($this->apellido_paterno ?? '', 0, 1);

        return mb_strtoupper($n.$a);
    }
}
