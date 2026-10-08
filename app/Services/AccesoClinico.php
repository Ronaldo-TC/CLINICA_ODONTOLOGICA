<?php

namespace App\Services;

use App\Models\Admision;
use App\Models\HistoriaClinica;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccesoClinico
{
    public static function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->hasAnyRole(['admin', 'subadmin', 'director', 'jefatura-clinicas']);
    }

    public static function esEstudiante(Usuario $usuario): bool
    {
        return ! self::esAdministrador($usuario) && $usuario->hasAnyRole(['estudiante', 'interno']);
    }

    public static function admisiones(Builder $query, Usuario $usuario): Builder
    {
        if (self::esEstudiante($usuario)) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('estudiante_id', $usuario->id)->orWhere('registrado_por', $usuario->id)
                    ->orWhereHas('remisiones', fn (Builder $remision) => $remision->where('habilitado', true)->where('estudiante_id', $usuario->id));
            });
        } elseif (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('responsable_id', $usuario->id)
                    ->orWhere('registrado_por', $usuario->id)
                    ->orWhereIn('especialidad_responsable', self::clinicas($usuario))
                    ->orWhereHas('remisiones', fn (Builder $remision) => $remision->where('habilitado', true)->whereIn('clinica_id', self::clinicas($usuario)));
            });
        }

        return $query;
    }

    public static function historias(Builder $query, Usuario $usuario): Builder
    {
        if (self::esEstudiante($usuario)) {
            $query->where('estudiante_id', $usuario->id);
        } elseif (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('docente_id', $usuario->id)->orWhereIn('clinica_id', self::clinicas($usuario));
            });
        }

        return $query;
    }

    public static function remisiones(Builder $query, Usuario $usuario): Builder
    {
        if (! $usuario->hasRole('admin') && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->whereIn('clinica_id', self::clinicas($usuario));
        } elseif (self::esEstudiante($usuario)) {
            $query->where('estudiante_id', $usuario->id);
        }

        return $query;
    }

    public static function puedeVerHistoria(HistoriaClinica $historia, Usuario $usuario): bool
    {
        return self::historias(HistoriaClinica::query(), $usuario)->whereKey($historia->id)->exists();
    }

    public static function radiologias(Builder $query, Usuario $usuario): Builder
    {
        if (self::esEstudiante($usuario) || (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra']))) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->whereIn('paciente_id', self::admisiones(Admision::query(), $usuario)->select('paciente_id'))
                    ->orWhereIn('paciente_id', self::historias(HistoriaClinica::query(), $usuario)->select('paciente_id'));
            });
        }

        return $query;
    }

    public static function solicitudes(Builder $query, Usuario $usuario): Builder
    {
        if (self::esEstudiante($usuario)) {
            $query->where('usuario_id', $usuario->id);
        } elseif (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('docente_id', $usuario->id)
                    ->orWhereHas('historiaClinica', fn (Builder $historia) => $historia->where('docente_id', $usuario->id))
                    ->orWhereHas('tratamientoRealizado.historiaClinica', fn (Builder $historia) => $historia->where('docente_id', $usuario->id))
                    ->orWhereHas('sesionAtencion', fn (Builder $sesion) => $sesion->where('docente_id', $usuario->id));
            });
        }

        return $query;
    }

    /** @return array<int, int> */
    private static function clinicas(Usuario $usuario): array
    {
        return DB::table('especialidades')->where('usuario_id', $usuario->id)
            ->where('habilitado', true)->pluck('clinica_id')->all();
    }
}
