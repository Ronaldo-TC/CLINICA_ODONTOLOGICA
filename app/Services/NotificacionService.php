<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;

/**
 * NotificacionService
 *
 * Punto único para crear notificaciones internas. Nunca interrumpe el flujo
 * que la invoca: si algo falla, se registra y se continúa.
 *
 * Uso típico:
 *   NotificacionService::aRol('docente', Notificacion::TIPO_SOLICITUD_CREADA, 'Nueva solicitud', 'Detalle', $url);
 *   NotificacionService::a($usuarioId, ...);
 */
class NotificacionService
{
    /**
     * Crea una notificación para un usuario concreto.
     */
    public static function a(
        ?int $usuarioId,
        string $tipo,
        string $titulo,
        ?string $mensaje = null,
        ?string $url = null
    ): ?Notificacion {
        if (! $usuarioId) {
            return null;
        }

        try {
            return Notificacion::create([
                'usuario_id' => $usuarioId,
                'tipo'       => $tipo,
                'titulo'     => mb_substr($titulo, 0, 200),
                'mensaje'    => $mensaje,
                'url'        => $url ? mb_substr($url, 0, 500) : null,
                'leida'      => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificación', [
                'usuario_id' => $usuarioId,
                'tipo'       => $tipo,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Crea la misma notificación para todos los usuarios de uno o varios roles.
     *
     * @param  string|array<int, string>  $roles
     * @return int  número de notificaciones creadas
     */
    public static function aRol(
        string|array $roles,
        string $tipo,
        string $titulo,
        ?string $mensaje = null,
        ?string $url = null,
        ?int $exceptoUsuarioId = null
    ): int {
        $roles = (array) $roles;
        $creadas = 0;

        try {
            $usuarios = Usuario::query()
                ->where('habilitado', true)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
                ->when($exceptoUsuarioId, fn ($q) => $q->where('id', '!=', $exceptoUsuarioId))
                ->pluck('id');

            foreach ($usuarios as $usuarioId) {
                if (self::a((int) $usuarioId, $tipo, $titulo, $mensaje, $url)) {
                    $creadas++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron notificar los roles', [
                'roles' => $roles,
                'error' => $e->getMessage(),
            ]);
        }

        return $creadas;
    }

    /**
     * Avisa al encargado de insumos cuando un insumo queda en o bajo el mínimo.
     * No repite el aviso si ya hay uno sin leer del mismo insumo.
     */
    public static function stockBajo(string $nombreInsumo, float $stock, string $unidad, ?string $url = null): void
    {
        try {
            $titulo = "Stock bajo: {$nombreInsumo}";

            $existe = Notificacion::query()
                ->noLeidas()
                ->where('tipo', Notificacion::TIPO_STOCK_BAJO)
                ->where('titulo', $titulo)
                ->exists();

            if ($existe) {
                return;
            }

            self::aRol(
                ['insumos', 'admin', 'subadmin'],
                Notificacion::TIPO_STOCK_BAJO,
                $titulo,
                'Quedan ' . number_format($stock, 2) . " {$unidad}. Requiere reposición.",
                $url
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar del stock bajo', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cuenta de notificaciones sin leer de un usuario.
     */
    public static function contarNoLeidas(?int $usuarioId): int
    {
        if (! $usuarioId) {
            return 0;
        }

        return Notificacion::query()->delUsuario($usuarioId)->noLeidas()->count();
    }
}
