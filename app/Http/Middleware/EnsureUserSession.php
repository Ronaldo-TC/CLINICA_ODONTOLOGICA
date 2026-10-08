<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // ─────────────────────────────────────────────────────────
        // 1. Comprobar el usuario del guard web y la sesión registrada
        // ─────────────────────────────────────────────────────────
        $guard = Auth::guard('web');
        $guardUser = $guard->user();

        if ($guardUser && ! $request->session()->has('user_id') && ! $request->session()->has('session_token')) {
            $usuario = Usuario::with('roles', 'permissions')
                ->where('habilitado', true)
                ->where('permitir_login', true)
                ->find($guardUser->getAuthIdentifier());

            if ($usuario) {
                $guard->setUser($usuario);

                return $next($request);
            }

            return $this->denyAccess($request);
        }

        $userId = $request->session()->get('user_id');
        $sessionToken = $request->session()->get('session_token');

        // ─────────────────────────────────────────────────────────
        // 2. Validar la sesión en la tabla `sesiones`
        // ─────────────────────────────────────────────────────────
        $activeSession = null;

        $hasMatchingUser = ! $guardUser || (string) $guardUser->getAuthIdentifier() === (string) $userId;

        if ($hasMatchingUser && $userId && is_string($sessionToken) && $sessionToken !== '') {
            $activeSession = DB::table('sesiones')
                ->join('usuarios', 'usuarios.id', '=', 'sesiones.usuario_id')
                ->where('sesiones.usuario_id', $userId)
                ->where('sesiones.token', $sessionToken)
                ->where('sesiones.activa', true)
                ->where('usuarios.habilitado', true)
                ->where('usuarios.permitir_login', true)
                ->select('sesiones.id', 'usuarios.id as usuario_id')
                ->first();
        }

        // ─────────────────────────────────────────────────────────
        // 3. Sesión inválida → invalidar y redirigir
        // ─────────────────────────────────────────────────────────
        if (! $activeSession) {

            if ($hasMatchingUser && $userId && is_string($sessionToken)) {
                DB::table('sesiones')
                    ->where('usuario_id', $userId)
                    ->where('token', $sessionToken)
                    ->where('activa', true)
                    ->update([
                        'activa' => false,
                        'fin' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $this->denyAccess($request);
        }

        // ─────────────────────────────────────────────────────────
        // 4. ⚠️ CLAVE: Loguear al usuario en el guard de Laravel
        //    Sin esto, Auth::user() es null y Spatie da 403.
        // ─────────────────────────────────────────────────────────
        $usuario = Usuario::with('roles', 'permissions')
            ->find($activeSession->usuario_id);

        if (! $usuario) {
            return $this->denyAccess($request);
        }

        if ($guardUser) {
            $guard->setUser($usuario);
        } else {
            $guard->login($usuario);
        }

        // ─────────────────────────────────────────────────────────
        // 5. Actualizar ultimo_acceso de la sesión
        // ─────────────────────────────────────────────────────────
        DB::table('sesiones')->where('id', $activeSession->id)->update([
            'ultimo_acceso' => now(),
            'updated_at' => now(),
        ]);

        return $next($request);
    }

    private function denyAccess(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'La sesión no es válida o ya fue cerrada.',
            ], 401);
        }

        return redirect()->route('auth.login')->withErrors([
            'acceso' => 'La sesión no es válida o ya fue cerrada.',
        ]);
    }
}
