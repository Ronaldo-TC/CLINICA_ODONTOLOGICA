<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Uso en rutas:
     *   ->middleware('role')                      // cualquier usuario autenticado con rol habilitado
     *   ->middleware('role:docente')              // solo rol docente
     *   ->middleware('role:docente,jefe-catedra') // docente O jefe de cátedra
     *
     * El usuario con rol "admin" siempre pasa.
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $userId = session('user_id');

        if (! $userId) {
            return $this->denyAccess($request, 'No autenticado.', 401);
        }

        // Roles reales del usuario (puede tener varios)
        $userRoles = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->where('ur.usuario_id', $userId)
            ->where('r.habilitado', true)
            ->pluck('r.nombre')
            ->all();

        if (empty($userRoles)) {
            return $this->denyAccess($request, 'El usuario no tiene un rol asignado.', 403);
        }

        // Admin tiene pase libre
        if (in_array('admin', $userRoles, true)) {
            return $next($request);
        }

        // Si se especificaron roles permitidos, validar
        if (! empty($allowedRoles)) {
            $matched = array_intersect($allowedRoles, $userRoles);
            if (empty($matched)) {
                Log::warning('Acceso denegado por rol insuficiente', [
                    'user_id'        => $userId,
                    'user_roles'     => $userRoles,
                    'roles_required' => $allowedRoles,
                    'url'            => $request->fullUrl(),
                    'ip'             => $request->ip(),
                ]);

                return $this->denyAccess($request, 'No tienes permiso para acceder a este recurso.', 403);
            }
        }

        // Validar permisos por módulo (si el módulo existe en la tabla permisos)
        $permissionModules = $this->permissionModulesFor($request);

        if ($permissionModules !== []) {
            $isReadRequest = in_array($request->method(), ['GET', 'HEAD'], true);

            $hasPermission = DB::table('roles as r')
                ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
                ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
                ->whereIn('r.nombre', $userRoles)
                ->where('rp.habilitado', true)
                ->whereIn('p.modulo', $permissionModules)
                ->where(function ($query) use ($isReadRequest): void {
                    $query->where('p.codigo', 'like', '%.gestionar');
                    if ($isReadRequest) {
                        $query->orWhere('p.codigo', 'like', '%.ver');
                    }
                })
                ->exists();

            if (! $hasPermission) {
                Log::warning('Acceso denegado por permiso insuficiente', [
                    'user_id'            => $userId,
                    'user_roles'         => $userRoles,
                    'modulos_requeridos' => $permissionModules,
                    'url'                => $request->fullUrl(),
                    'ip'                 => $request->ip(),
                ]);

                return $this->denyAccess($request, 'No tienes permiso para acceder a este recurso.', 403);
            }
        }

        return $next($request);
    }

    /**
     * Deduce el módulo a partir de la URL, considerando:
     *   - prefijo /api
     *   - prefijo /admin/{modulo}
     *   - prefijo /{rol}/{modulo}  (se salta el primer segmento si coincide con un rol conocido)
     */
    private function permissionModulesFor(Request $request): array
    {
        $segments = $request->segments();

        if (($segments[0] ?? null) === 'api') {
            array_shift($segments);
        }

        // Saltar prefijo /admin
        if (($segments[0] ?? null) === 'admin') {
            array_shift($segments);
        }

        // Saltar prefijo de rol (/docente, /estudiante, /cajas, etc.)
        if (isset($segments[0]) && $this->isKnownRole($segments[0])) {
            array_shift($segments);
        }

        $module = $segments[0] ?? null;

        if (! $module) {
            return [];
        }

        $moduleCandidates = array_unique([
            $module,
            Str::singular($module),
            Str::plural($module),
        ]);

        return DB::table('permisos')
            ->whereIn('modulo', $moduleCandidates)
            ->distinct()
            ->pluck('modulo')
            ->all();
    }

    private function isKnownRole(string $segment): bool
    {
        static $cache = null;

        if ($cache === null) {
            $cache = DB::table('roles')->pluck('nombre')->all();
        }

        return in_array($segment, $cache, true);
    }

    protected function denyAccess(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'roles'   => session('user_role'),
            ], $status);
        }

        return redirect()
            ->route('auth.login')
            ->withErrors(['acceso' => $message]);
    }
}
