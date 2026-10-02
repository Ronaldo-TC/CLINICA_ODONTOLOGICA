<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* ============================================================
       MOSTRAR LOGIN
       ============================================================ */
    public function showLogin()
    {
        // Si ya está autenticado por el guard web → dashboard
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /* ============================================================
       LOGIN WEB (AJAX — devuelve JSON)
       ============================================================ */
    public function loginWeb(Request $request)
    {
        try {
            // 1. Validación
            $request->validate([
                'nombre_usuario' => 'required|string|max:50',
                'contrasena'     => 'required|string',
            ]);

            // 2. Buscar usuario
            $user = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

            // 3. Verificar credenciales
            if (! $user || ! Hash::check($request->contrasena, $user->contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos.',
                    'errors'  => [
                        'nombre_usuario' => ['Usuario o contraseña incorrectos.'],
                    ],
                ], 422);
            }

            // 4. Verificar que pueda loguearse
            if (! $user->habilitado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está deshabilitada.',
                    'errors'  => [
                        'nombre_usuario' => ['Tu cuenta está deshabilitada.'],
                    ],
                ], 422);
            }

            if (! $user->permitir_login) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permitido iniciar sesión.',
                    'errors'  => [
                        'nombre_usuario' => ['No tienes permitido iniciar sesión.'],
                    ],
                ], 422);
            }

            // 5. Cerrar sesiones anteriores si NO tiene multilogin
            if (! $user->multilogin) {
                DB::table('sesiones')
                    ->where('usuario_id', $user->id)
                    ->where('activa', true)
                    ->update([
                        'activa'     => false,
                        'fin'        => now(),
                        'updated_at' => now(),
                    ]);
            }

            // 6. Crear token de sesión
            $token = Str::random(64);

            DB::table('sesiones')->insert([
                'usuario_id'    => $user->id,
                'token'         => $token,
                'ip'            => $request->ip(),
                'user_agent'    => (string) $request->userAgent(),
                'inicio'        => now(),
                'ultimo_acceso' => now(),
                'activa'        => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 7. Guardar en sesión de Laravel (lo que lee EnsureUserSession)
            $request->session()->put('user_id', $user->id);
            $request->session()->put('session_token', $token);
            $request->session()->regenerate();

            // 8. ⚠️ CRÍTICO: Loguear en el guard web (Spatie lo necesita)
            Auth::guard('web')->login($user);

            // 9. Actualizar último login
            //    (NO se guarda 'sesion' porque la columna es varchar(50) y el token
            //     es de 64 chars — el token vive únicamente en la tabla `sesiones`)
            $user->forceFill([
                'ultimo_login' => now(),
                'ip'           => $request->ip(),
            ])->save();

            // 10. Respuesta JSON
            return response()->json([
                'success'  => true,
                'message'  => 'Bienvenido, ' . $user->nombre_completo,
                'redirect' => route('dashboard'),
                'user'     => [
                    'id'              => $user->id,
                    'ci'              => $user->ci,
                    'nombre_completo' => $user->nombre_completo,
                    'nombre_usuario'  => $user->nombre_usuario,
                    'foto'            => $user->foto,
                    'roles'           => $user->getRoleNames(),
                    'permisos'        => $user->getAllPermissions()->pluck('name'),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error del servidor. Intenta de nuevo.',
            ], 500);
        }
    }

    /* ============================================================
       LOGOUT WEB (AJAX + fallback HTML)
       ============================================================ */
    public function logoutWeb(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $token  = $request->session()->get('session_token');

        // 1. Cerrar la sesión en la tabla
        if ($userId && $token) {
            DB::table('sesiones')
                ->where('usuario_id', $userId)
                ->where('token', $token)
                ->where('activa', true)
                ->update([
                    'activa'     => false,
                    'fin'        => now(),
                    'updated_at' => now(),
                ]);
        }

        // 2. Actualizar ultimo_logout
        if ($userId) {
            Usuario::where('id', $userId)->update([
                'ultimo_logout' => now(),
            ]);
        }

        // 3. Logout del guard
        Auth::guard('web')->logout();

        // 4. Invalidar sesión
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 5. Respuesta según el tipo de request
        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Sesión cerrada correctamente.',
                'redirect' => route('auth.login'),
            ]);
        }

        return redirect()->route('auth.login')
            ->with('success', 'Sesión cerrada correctamente.');
    }

    /* ============================================================
       PERFIL
       ============================================================ */
    public function perfil()
    {
        $user = Auth::user();

        return view('auth.perfil', compact('user'));
    }

    /* ============================================================
       CONFIGURACIÓN
       ============================================================ */
    public function configuracion()
    {
        $user = Auth::user();

        return view('auth.configuracion', compact('user'));
    }

    /* ============================================================
       API — LOGIN (Sanctum)
       ============================================================ */
    public function login(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string|max:50',
            'contrasena'     => 'required|string',
        ]);

        $user = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

        if (! $user || ! Hash::check($request->contrasena, $user->contrasena)) {
            throw ValidationException::withMessages([
                'nombre_usuario' => ['Credenciales incorrectas.'],
            ]);
        }

        if (! $user->habilitado || ! $user->permitir_login) {
            return response()->json(['message' => 'Usuario deshabilitado'], 403);
        }

        if (! $user->multilogin) {
            $user->tokens()->delete();
        }

        $user->forceFill([
            'ultimo_login' => now(),
            'ip'           => $request->ip(),
        ])->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'              => $user->id,
                'ci'              => $user->ci,
                'nombre_completo' => $user->nombre_completo,
                'nombre_usuario'  => $user->nombre_usuario,
                'foto'            => $user->foto,
                'roles'           => $user->getRoleNames(),
                'permisos'        => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /* ============================================================
       API — ME
       ============================================================ */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles', 'permissions');

        return response()->json([
            'user' => [
                'id'              => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'nombre_usuario'  => $user->nombre_usuario,
                'foto'            => $user->foto,
                'roles'           => $user->getRoleNames(),
                'permisos'        => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /* ============================================================
       API — LOGOUT
       ============================================================ */
    public function logout(Request $request)
    {
        $request->user()->forceFill(['ultimo_logout' => now()])->save();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /* ============================================================
       API — LOGOUT ALL
       ============================================================ */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Todas las sesiones cerradas']);
    }
}
