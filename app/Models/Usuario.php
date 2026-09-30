<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    /** Tabla real */
    protected $table = 'usuarios';

    /** Campos asignables */
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
        'ultimo_login',
        'sesion',
        'sesion_extra',
        'ultimo_logout',
        'ip_permitida',
        'info',
        'foto',
    ];

    /** Campos ocultos */
    protected $hidden = [
        'contrasena',
        'sesion',
        'sesion_extra',
    ];

    /** Casts */
    protected function casts(): array
    {
        return [
            'habilitado'       => 'boolean',
            'permitir_login'   => 'boolean',
            'multilogin'       => 'boolean',
            'ultimo_login'     => 'datetime',
            'ultimo_logout'    => 'datetime',
            'contrasena'       => 'hashed',   // Laravel 10+
        ];
    }

    /** ⚠️ Apunta a la columna correcta de password */
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    /** ⚠️ Campo usado por el login */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /** ⚠️ Sanctum y Spatie necesitan el guard correcto */
    protected string $guard_name = 'web';

    /** Accessor útil para vistas */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->nombre_completo));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);

        return mb_strtoupper($first . $last);
    }

    /** Relaciones útiles */
    public function especialidades()
    {
        return $this->belongsToMany(
            \App\Models\Clinica::class,
            'especialidades',
            'usuario_id',
            'clinica_id'
        )->withPivot(['habilitado', 'rotacion', 'responsable_admision', 'fecha_inicio']);
    }
}
