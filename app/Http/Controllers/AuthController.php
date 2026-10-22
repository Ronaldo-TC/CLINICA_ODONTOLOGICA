<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* ============================================================
       MOSTRAR LOGIN
       ============================================================ */
    public function showLogin(): View|RedirectResponse
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
    public function loginWeb(Request $request): JsonResponse
    {
        try {
            // 1. Validación
            $request->validate([
                'nombre_usuario' => 'required|string|max:50',
                'contrasena' => 'required|string',
                'device_name' => 'nullable|string|max:100',
            ], [
                'nombre_usuario.required' => 'Ingresa tu usuario institucional.',
                'nombre_usuario.max' => 'El usuario no puede superar los 50 caracteres.',
                'contrasena.required' => 'Ingresa tu contraseña.',
            ]);

            // 2. Buscar usuario
            $user = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

            // 3. Verificar credenciales
            if (! $user || ! $this->passwordMatches($request->contrasena, $user->contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos.',
                    'errors' => [
                        'nombre_usuario' => ['Usuario o contraseña incorrectos.'],
                    ],
                ], 422);
            }

            // 4. Verificar que pueda loguearse
            if (! $user->habilitado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está deshabilitada.',
                    'errors' => [
                        'nombre_usuario' => ['Tu cuenta está deshabilitada.'],
                    ],
                ], 422);
            }

            if (! $user->permitir_login) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permitido iniciar sesión.',
                    'errors' => [
                        'nombre_usuario' => ['No tienes permitido iniciar sesión.'],
                    ],
                ], 422);
            }

            if (! $user->allowsLoginFromIp($request->ip() ?? '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permitido iniciar sesión desde esta IP.',
                    'errors' => [
                        'nombre_usuario' => ['No tienes permitido iniciar sesión desde esta IP.'],
                    ],
                ], 422);
            }

            // 5. Cerrar sesiones anteriores si NO tiene multilogin
            if (! $user->multilogin) {
                DB::table('sesiones')
                    ->where('usuario_id', $user->id)
                    ->where('activa', true)
                    ->update([
                        'activa' => false,
                        'fin' => now(),
                        'updated_at' => now(),
                    ]);
            }

            // 6. Crear token de sesión
            $token = Str::random(64);

            DB::table('sesiones')->insert([
                'usuario_id' => $user->id,
                'token' => $token,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'inicio' => now(),
                'ultimo_acceso' => now(),
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 7. Guardar en sesión de Laravel (lo que lee EnsureUserSession)
            $request->session()->put('user_id', $user->id);
            $request->session()->put('session_token', $token);
            $request->session()->regenerate();

            // 8. ⚠️ CRÍTICO: Loguear en el guard web (Spatie lo necesita)
            Auth::guard('web')->login($user);
            $request->attributes->set('bitacora.usuario', $user);

            // 9. Actualizar último login
            //    (NO se guarda 'sesion' porque la columna es varchar(50) y el token
            //     es de 64 chars — el token vive únicamente en la tabla `sesiones`)
            $user->forceFill([
                'ultimo_login' => now(),
                'ip' => $request->ip(),
            ])->save();

            if (Hash::needsRehash($user->contrasena)) {
                $user->forceFill(['contrasena' => Hash::make($request->contrasena)])->save();
            }

            // 10. Respuesta JSON
            return response()->json([
                'success' => true,
                'message' => 'Bienvenido, '.$user->nombre_completo,
                'redirect' => route('dashboard'),
                'user' => [
                    'id' => $user->id,
                    'ci' => $user->ci,
                    'nombre_completo' => $user->nombre_completo,
                    'nombre_usuario' => $user->nombre_usuario,
                    'foto' => $user->foto,
                    'roles' => $user->getRoleNames(),
                    'permisos' => $user->getAllPermissions()->pluck('name'),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
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
    public function logoutWeb(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        $token = $request->session()->get('session_token');

        // 1. Cerrar la sesión en la tabla
        if ($userId && $token) {
            DB::table('sesiones')
                ->where('usuario_id', $userId)
                ->where('token', $token)
                ->where('activa', true)
                ->update([
                    'activa' => false,
                    'fin' => now(),
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
                'success' => true,
                'message' => 'Sesión cerrada correctamente.',
                'redirect' => route('auth.login'),
            ]);
        }

        return redirect()->route('auth.login')
            ->with('success', 'Sesión cerrada correctamente.');
    }

    /* ============================================================
       PERFIL
       ============================================================ */
    public function perfil(Request $request): View
    {
        $user = $this->currentUser($request);
        $sessions = DB::table('sesiones')->where('usuario_id', $user->id);
        $stats = [
            'sesiones_activas' => (clone $sessions)->where('activa', true)->count(),
            'total_sesiones' => $sessions->count(),
            'ultimo_login' => $user->ultimo_login,
        ];

        return view('auth.profile', compact('user', 'stats'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validate([
            'ci' => ['required', 'string', 'max:50', Rule::unique('usuarios', 'ci')->ignore($user->id)],
            'nombre_usuario' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('usuarios', 'nombre_usuario')->ignore($user->id)],
            'nombre_completo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'ip_permitida' => ['nullable', 'string', 'max:300'],
            'multilogin' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'ci.required' => 'El CI es obligatorio.',
            'ci.unique' => 'Ese CI ya está registrado.',
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif, webp.',
            'foto.max' => 'La imagen no debe superar los 2MB.',
        ]);

        $previousPhoto = $user->foto;
        $photoPath = $previousPhoto;

        if ($request->hasFile('foto')) {
            $photoPath = $request->file('foto')->store('avatars', 'public');

            if ($photoPath === false) {
                throw new \RuntimeException('No se pudo guardar la foto de perfil.');
            }
        }

        $user->forceFill([
            'ci' => $validated['ci'],
            'nombre_usuario' => $validated['nombre_usuario'],
            'nombre_completo' => $validated['nombre_completo'],
            'descripcion' => $validated['descripcion'] ?? '',
            'ip_permitida' => $validated['ip_permitida'] ?? null,
            'multilogin' => $request->boolean('multilogin'),
            'foto' => $photoPath,
        ])->save();

        if ($photoPath !== $previousPhoto && $previousPhoto) {
            Storage::disk('public')->delete($previousPhoto);
        }

        if (! $user->multilogin) {
            $this->revokeOtherWebSessions($request, $user);
        }

        $request->session()->put([
            'user_username' => $user->nombre_usuario,
            'user_name' => $user->nombre_completo,
            'user_foto' => $user->foto,
        ]);

        return redirect()->route('auth.profile')->with('status', 'Tu perfil se actualizó correctamente.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $previousPhoto = $user->foto;
        $user->update(['foto' => null]);

        if ($previousPhoto) {
            Storage::disk('public')->delete($previousPhoto);
        }

        $request->session()->forget('user_foto');

        return redirect()->route('auth.profile')->with('status', 'Foto de perfil eliminada correctamente.');
    }

    /* ============================================================
       CONFIGURACIÓN
       ============================================================ */
    public function configuracion(Request $request): View
    {
        $user = $this->currentUser($request);
        $sesionesActivas = DB::table('sesiones')
            ->where('usuario_id', $user->id)
            ->where('activa', true)
            ->orderByDesc('inicio')
            ->get();
        $bitacora = DB::table('bitacoras')
            ->where('usuario_id', $user->id)
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        return view('auth.settings', compact('user', 'sesionesActivas', 'bitacora'));
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'new_password.required' => 'Ingresa una nueva contraseña.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'La confirmación de contraseña no coincide.',
            'new_password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.',
        ]);

        if (! $this->passwordMatches($validated['current_password'], $user->contrasena)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['contrasena' => Hash::make($validated['new_password'])]);
        $request->session()->regenerate();

        return redirect()->route('auth.settings')->with('status', 'Tu contraseña se cambió correctamente.');
    }

    public function closeOtherSessions(Request $request): RedirectResponse
    {
        $this->revokeOtherWebSessions($request, $this->currentUser($request));

        return redirect()->route('auth.settings')->with('status', 'Se cerraron las otras sesiones correctamente.');
    }

    public function closeSession(Request $request, int $sesion): RedirectResponse
    {
        $user = $this->currentUser($request);
        $session = DB::table('sesiones')->where('usuario_id', $user->id)->find($sesion);
        abort_unless($session, 404);

        DB::table('sesiones')->where('id', $session->id)->update([
            'activa' => false,
            'fin' => now(),
            'updated_at' => now(),
        ]);

        if ($session->token === $request->session()->get('session_token')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login');
        }

        return redirect()->route('auth.settings')->with('status', 'Sesión cerrada correctamente.');
    }

    private function currentUser(Request $request): Usuario
    {
        $user = $request->user('web');
        abort_unless($user instanceof Usuario, 401);

        return $user;
    }

    private function revokeOtherWebSessions(Request $request, Usuario $user): void
    {
        $currentToken = $request->session()->get('session_token');
        DB::table('sesiones')
            ->where('usuario_id', $user->id)
            ->where('activa', true)
            ->when(is_string($currentToken) && $currentToken !== '', fn ($query) => $query->where('token', '!=', $currentToken))
            ->update([
                'activa' => false,
                'fin' => now(),
                'updated_at' => now(),
            ]);
    }

    private function passwordMatches(string $password, string $storedPassword): bool
    {
        if (preg_match('/\A[a-f0-9]{64}\z/i', $storedPassword) === 1) {
            return hash_equals(strtolower($storedPassword), hash('sha256', $password));
        }

        return Hash::check($password, $storedPassword);
    }

    /* ============================================================
       API — LOGIN (Sanctum)
       ============================================================ */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'nombre_usuario' => 'required|string|max:50',
            'contrasena' => 'required|string',
        ]);

        $user = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

        if (! $user || ! $this->passwordMatches($request->contrasena, $user->contrasena)) {
            throw ValidationException::withMessages([
                'nombre_usuario' => ['Credenciales incorrectas.'],
            ]);
        }

        if (! $user->habilitado || ! $user->permitir_login) {
            return response()->json(['message' => 'Usuario deshabilitado'], 403);
        }

        if (! $user->allowsLoginFromIp($request->ip() ?? '')) {
            return response()->json(['message' => 'No tienes permitido iniciar sesión desde esta IP.'], 403);
        }

        if (! $user->multilogin) {
            $user->tokens()->delete();
        }

        $user->forceFill([
            'ultimo_login' => now(),
            'ip' => $request->ip(),
        ])->save();

        if (Hash::needsRehash($user->contrasena)) {
            $user->forceFill(['contrasena' => Hash::make($request->contrasena)])->save();
        }

        $token = $user->createToken($request->input('device_name') ?: 'auth-token')->plainTextToken;

        $request->attributes->set('bitacora.usuario', $user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'ci' => $user->ci,
                'nombre_completo' => $user->nombre_completo,
                'nombre_usuario' => $user->nombre_usuario,
                'foto' => $user->foto,
                'roles' => $user->getRoleNames(),
                'permisos' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /* ============================================================
       API — ME
       ============================================================ */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'permissions');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'nombre_usuario' => $user->nombre_usuario,
                'foto' => $user->foto,
                'roles' => $user->getRoleNames(),
                'permisos' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /* ============================================================
       API — LOGOUT
       ============================================================ */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['ultimo_logout' => now()])->save();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /* ============================================================
       API — LOGOUT ALL
       ============================================================ */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Todas las sesiones cerradas']);
    }

    public function loginV1(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre_usuario' => ['required', 'string', 'max:50'],
                'contrasena' => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => 'Revisa los campos enviados.', 'errors' => $exception->errors()], 422);
        }
        try {
            $respuesta = $this->login($request);
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => 'Credenciales incorrectas.'], 401);
        }
        $datos = $respuesta->getData(true);

        return $respuesta->isSuccessful()
            ? response()->json(['success' => true, 'message' => 'Sesión iniciada.', 'data' => $datos])
            : response()->json(['success' => false] + $datos, $respuesta->getStatusCode());
    }

    public function meV1(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json(['success' => true, 'data' => [
            'id' => $usuario->id, 'nombre_usuario' => $usuario->nombre_usuario,
            'nombre_completo' => $usuario->nombre_completo, 'ci' => $usuario->ci,
            'roles' => $usuario->getRoleNames(), 'permisos' => $usuario->getAllPermissions()->pluck('name'),
        ]]);
    }

    public function logoutV1(Request $request): JsonResponse
    {
        $respuesta = $this->logout($request);

        return response()->json(['success' => true] + $respuesta->getData(true));
    }

    public function logoutAllV1(Request $request): JsonResponse
    {
        $respuesta = $this->logoutAll($request);

        return response()->json(['success' => true] + $respuesta->getData(true));
    }
}
