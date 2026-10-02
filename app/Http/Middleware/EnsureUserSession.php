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
        // 1. Si ya está autenticado por el guard web → continuar
        // ─────────────────────────────────────────────────────────
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        $userId       = $request->session()->get('user_id');
        $sessionToken = $request->session()->get('session_token');

        // ─────────────────────────────────────────────────────────
        // 2. Validar la sesión en la tabla `sesiones`
        // ─────────────────────────────────────────────────────────
        $activeSession = null;

        if ($userId && is_string($sessionToken)) {
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

            if ($userId && is_string($sessionToken)) {
                DB::table('sesiones')
                    ->where('usuario_id', $userId)
                    ->where('token', $sessionToken)
                    ->where('activa', true)
                    ->update([
                        'activa'     => false,
                        'fin'        => now(),
                        'updated_at' => now(),
                    ]);
            }

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

        // ─────────────────────────────────────────────────────────
        // 4. ⚠️ CLAVE: Loguear al usuario en el guard de Laravel
        //    Sin esto, Auth::user() es null y Spatie da 403.
        // ─────────────────────────────────────────────────────────
        $usuario = Usuario::with('roles', 'permissions')
            ->find($activeSession->usuario_id);

        if (! $usuario) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login')->withErrors([
                'acceso' => 'Usuario no encontrado.',
            ]);
        }

        Auth::guard('web')->login($usuario);

        // ─────────────────────────────────────────────────────────
        // 5. Actualizar ultimo_acceso de la sesión
        // ─────────────────────────────────────────────────────────
        DB::table('sesiones')->where('id', $activeSession->id)->update([
            'ultimo_acceso' => now(),
            'updated_at'    => now(),
        ]);

        return $next($request);
    }
}
