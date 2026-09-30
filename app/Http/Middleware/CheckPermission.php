<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Uso en rutas:
     *   ->middleware('permiso:usuarios.ver')
     *   ->middleware('permiso:usuarios.crear,usuarios.editar')
     */
    public function handle(Request $request, Closure $next, string ...$codes): Response
    {
        $userId = session('user_id');
        if (! $userId) {
            return $this->deny($request, 'No autenticado.', 401);
        }

        // Admin pasa siempre
        $isAdmin = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->where('ur.usuario_id', $userId)
            ->where('r.nombre', 'admin')
            ->where('r.habilitado', true)
            ->exists();

        if ($isAdmin) {
            return $next($request);
        }

        $has = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
            ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
            ->where('ur.usuario_id', $userId)
            ->where('r.habilitado', true)
            ->where('rp.habilitado', true)
            ->whereIn('p.codigo', $codes)
            ->exists();

        if (! $has) {
            return $this->deny($request, 'No tienes permiso para esta acción.', 403);
        }

        return $next($request);
    }

    protected function deny(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->route('auth.login')->withErrors(['acceso' => $message]);
    }
}
