<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user_id');
        $sessionToken = $request->session()->get('session_token');

        $activeSession = null;
        if ($userId && is_string($sessionToken)) {
            $activeSession = DB::table('sesiones')
                ->join('usuarios', 'usuarios.id', '=', 'sesiones.usuario_id')
                ->where('sesiones.usuario_id', $userId)
                ->where('sesiones.token', $sessionToken)
                ->where('sesiones.activa', true)
                ->where('usuarios.habilitado', true)
                ->where('usuarios.permitir_login', true)
                ->select('sesiones.id')
                ->first();
        }

        if (! $activeSession) {
            if ($userId && is_string($sessionToken)) {
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

        DB::table('sesiones')->where('id', $activeSession->id)->update([
            'ultimo_acceso' => now(),
            'updated_at' => now(),
        ]);

        return $next($request);
    }
}
