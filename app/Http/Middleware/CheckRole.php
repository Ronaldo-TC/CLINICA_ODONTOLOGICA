<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Uso:
     * ->middleware('role') // cualquier logueado
     * ->middleware('role:docente') // Spatie: hasRole
     * ->middleware('role:docente|jefe-catedra') // Spatie: OR con |
     * Admin siempre pasa
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        // Soporta tanto Sanctum (API) como sesión legacy (web)
        $user = $request->user() ?? \App\Models\Usuario::find(session('user_id'));

        if (! $user) {
            return $this->denyAccess($request, 'No autenticado.', 401);
        }

        // Spatie ya te da los roles habilitados, sin join manual
        if ($user->roles->isEmpty()) {
            return $this->denyAccess($request, 'El usuario no tiene un rol asignado.', 403);
        }

        // Admin bypass - usas Spatie
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Validar roles permitidos. Spatie acepta | como OR
        if (! empty($allowedRoles)) {
            // Limpia: role:docente,jefe -> docente|jefe
            $rolesParaChequear = implode('|', $allowedRoles);
            $rolesParaChequear = str_replace(',', '|', $rolesParaChequear);

            if (! $user->hasAnyRole(explode('|', $rolesParaChequear))) {
                Log::warning('Acceso denegado por rol', [
                    'user_id' => $user->id,
                    'user_roles' => $user->getRoleNames(),
                    'required' => $allowedRoles,
                    'url' => $request->fullUrl(),
                ]);
                return $this->denyAccess($request, 'No tienes permiso para acceder a este recurso.', 403);
            }
        }

        // Validación por módulo -> ahora con permisos Spatie
        // tu tabla permisos.modulo + codigo (ej: pacientes.ver) -> Spatie permission name: pacientes.ver
        $permissionModules = $this->permissionModulesFor($request);

        if ($permissionModules !== []) {
            $isRead = in_array($request->method(), ['GET', 'HEAD'], true);

            $hasPermission = false;
            foreach ($permissionModules as $modulo) {
                if ($user->can("{$modulo}.gestionar") || ($isRead && $user->can("{$modulo}.ver"))) {
                    $hasPermission = true;
                    break;
                }
                // Fallback para permisos viejos migrados: pacientes.gestionar, pacientes.ver
                if ($user->hasPermissionTo("{$modulo}.gestionar") || ($isRead && $user->hasPermissionTo("{$modulo}.ver"))) {
                    $hasPermission = true;
                    break;
                }
            }

            if (! $hasPermission) {
                Log::warning('Acceso denegado por permiso', [
                    'user_id' => $user->id,
                    'modulos' => $permissionModules,
                ]);
                return $this->denyAccess($request, 'No tienes permiso para acceder a este módulo.', 403);
            }
        }

        return $next($request);
    }

    private function permissionModulesFor(Request $request): array
    {
        $segments = $request->segments();
        if (($segments[0] ?? null) === 'api') array_shift($segments);
        if (($segments[0] ?? null) === 'admin') array_shift($segments);

        // Si el primer segmento es un rol conocido (docente, etc), lo saltamos
        // Usamos cache de Spatie, no DB directa
        if (isset($segments[0]) && \Spatie\Permission\Models\Role::where('name', $segments[0])->exists()) {
            array_shift($segments);
        }

        $module = $segments[0] ?? null;
        if (! $module) return [];

        return array_unique([$module, Str::singular($module), Str::plural($module)]);
    }

    protected function denyAccess(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'roles' => $request->user()?->getRoleNames() ?? session('user_role'),
            ], $status);
        }
        return redirect()->route('auth.login')->withErrors(['acceso' => $message]);
    }
}
