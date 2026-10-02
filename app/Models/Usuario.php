<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $guard_name = 'web'; // Para Spatie

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
        'info',
        'foto',
    ];

    protected $hidden = [
        'contrasena',
        'sesion',
        'sesion_extra',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'habilitado' => 'boolean',
            'permitir_login' => 'boolean',
            'multilogin' => 'boolean',
            'ultimo_login' => 'datetime',
            'ultimo_logout' => 'datetime',
            'contrasena' => 'hashed',
        ];
    }

    // Auth usa columna contrasena, no password
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    // Accessor nuevo Laravel 10+
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = preg_split('/\s+/', trim($this->nombre_completo ?? ''));
                if (empty($parts[0])) return 'US';
                $first = mb_substr($parts[0], 0, 1);
                $last = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
                return mb_strtoupper($first . $last);
            }
        );
    }

    // Scopes útiles para tu clínica
    public function scopeHabilitados($query)
    {
        return $query->where('habilitado', true)->where('permitir_login', true);
    }

    public function especialidades()
    {
        return $this->belongsToMany(
            Clinica::class, // o Especialidad::class si tienes ese modelo
            'especialidades',
            'usuario_id',
            'clinica_id'
        )->withPivot(['habilitado', 'rotacion', 'responsable_admision', 'fecha_inicio'])
            ->withTimestamps();
    }
}
