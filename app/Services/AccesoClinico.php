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

    public static function supervisaClinica(Usuario $usuario, ?int $clinicaId): bool
    {
        return $clinicaId !== null && $usuario->hasRole('jefe-catedra')
            && DB::table('especialidades')->where('usuario_id', $usuario->id)
                ->where('clinica_id', $clinicaId)->where('habilitado', true)->exists();
    }

    public static function supervisaHistoria(Usuario $usuario, HistoriaClinica $historia): bool
    {
        if ($usuario->hasRole('jefe-catedra')) {
            return self::supervisaClinica($usuario, $historia->clinica_id);
        }

        return $usuario->hasRole('docente') && (int) $historia->docente_id === $usuario->id;
    }

    public static function supervisaAdmision(Usuario $usuario, Admision $admision): bool
    {
        return self::supervisaClinica($usuario, $admision->especialidad_responsable)
            || self::supervisaClinica($usuario, $admision->especialidad_estudiante)
            || ($usuario->hasRole('jefe-catedra') && $admision->remisiones()
                ->where('habilitado', true)->whereIn('clinica_id', self::clinicas($usuario))->exists());
    }

    public static function admisiones(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario) && $usuario->hasRole('jefe-catedra')) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->whereIn('especialidad_responsable', self::clinicas($usuario))
                    ->orWhereIn('especialidad_estudiante', self::clinicas($usuario))
                    ->orWhereHas('remisiones', fn (Builder $remision) => $remision->where('habilitado', true)->whereIn('clinica_id', self::clinicas($usuario)));
            });
        } elseif (self::esEstudiante($usuario)) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('estudiante_id', $usuario->id)->orWhere('registrado_por', $usuario->id)
                    ->orWhereHas('remisiones', fn (Builder $remision) => $remision->where('habilitado', true)->where('estudiante_id', $usuario->id));
            });
        } elseif (! self::esAdministrador($usuario) && $usuario->hasRole('docente')) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->where('responsable_id', $usuario->id)
                    ->orWhereHas('historiasClinicas', fn (Builder $historia) => $historia->where('docente_id', $usuario->id))
                    ->orWhereHas('remisiones', fn (Builder $remision) => self::remisiones($remision, $usuario)->where('habilitado', true));
            });
        }

        return $query;
    }

    public static function historias(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario) && $usuario->hasRole('jefe-catedra')) {
            $query->whereIn('clinica_id', self::clinicas($usuario));
        } elseif (self::esEstudiante($usuario)) {
            $query->where('estudiante_id', $usuario->id);
        } elseif (! self::esAdministrador($usuario) && $usuario->hasRole('docente')) {
            $query->where('docente_id', $usuario->id);
        }

        return $query;
    }

    public static function remisiones(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->whereIn('clinica_id', self::clinicas($usuario));
            if (! $usuario->hasRole('jefe-catedra')) {
                $query->where(function (Builder $query) use ($usuario): void {
                    $query->where('docente_id', $usuario->id)
                        ->orWhereHas('historiasClinicas', fn (Builder $historia) => $historia->where('docente_id', $usuario->id))
                        ->orWhere(function (Builder $pendiente): void {
                            $pendiente->where('estado', 'pendiente')->whereNull('docente_id')->doesntHave('historiasClinicas');
                        });
                });
            }
        } elseif (self::esEstudiante($usuario)) {
            $query->where('estudiante_id', $usuario->id);
        }

        return $query;
    }

    public static function puedeVerHistoria(HistoriaClinica $historia, Usuario $usuario): bool
    {
        return self::historias(HistoriaClinica::query(), $usuario)->whereKey($historia->id)->exists();
    }

    public static function pacientes(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra'])) {
            $query->where(function (Builder $query) use ($usuario): void {
                $query->whereIn('id', self::historias(HistoriaClinica::query(), $usuario)->select('paciente_id'))
                    ->orWhereIn('id', self::admisiones(Admision::query(), $usuario)->select('paciente_id'));
            });
        }

        return $query;
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

    public static function solicitudesRadiologicas(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario)) {
            $query->whereIn('historia_clinica_id', self::historias(HistoriaClinica::query(), $usuario)->select('id'));
        }

        return $query;
    }

    public static function solicitudes(Builder $query, Usuario $usuario): Builder
    {
        $query->where('tipo_solicitud', 'clinica');
        if (! self::esAdministrador($usuario) && $usuario->hasRole('jefe-catedra')) {
            $query->whereIn('clinica_id', self::clinicas($usuario));
        } elseif (self::esEstudiante($usuario)) {
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

    public static function esterilizaciones(Builder $query, Usuario $usuario): Builder
    {
        if (! self::esAdministrador($usuario) && $usuario->hasRole('jefe-catedra')) {
            $query->whereIn('clinica_id', self::clinicas($usuario));
        } elseif (self::esEstudiante($usuario)) {
            $query->where('estudiante_id', $usuario->id);
        } elseif (! self::esAdministrador($usuario) && $usuario->hasRole('docente')) {
            $query->where('docente_id', $usuario->id);
        }

        return $query;
    }

    /** @return array<int, int> */
    public static function clinicas(Usuario $usuario): array
    {
        return DB::table('especialidades')->where('usuario_id', $usuario->id)
            ->where('habilitado', true)->pluck('clinica_id')->all();
    }
}
