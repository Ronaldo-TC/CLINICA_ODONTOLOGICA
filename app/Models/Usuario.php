<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'ci',
        'nombre_usuario',
        'nombre_completo',
        'descripcion',
        'tipo',
        'habilitado',
        'permitir_login',
        'multilogin',
        'contrasena',
        'ip',
        'ip_permitida',
        'info',
        'foto',
    ];

    protected $hidden = ['contrasena'];

    protected $casts = [
        'habilitado'      => 'boolean',
        'permitir_login'  => 'boolean',
        'multilogin'      => 'boolean',
        'ultimo_login'    => 'datetime',
        'ultimo_logout'   => 'datetime',
    ];

    // Laravel espera 'password'; aquí es 'contrasena'
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    // ─── Relaciones ───

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'usuario_id', 'rol_id')
            ->withPivot('fecha_asignacion')
            ->withTimestamps();
    }

    public function especialidades()
    {
        return $this->hasMany(Especialidad::class, 'usuario_id');
    }

    public function historiasComoDocente()
    {
        return $this->hasMany(HistoriaClinica::class, 'docente_id');
    }

    public function historiasComoEstudiante()
    {
        return $this->hasMany(HistoriaClinica::class, 'estudiante_id');
    }

    public function sesionesAtencion()
    {
        return $this->hasMany(SesionAtencion::class, 'docente_id');
    }
}
