<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de login o redirige si ya está autenticado.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (session('user_id')) {
            return $this->redirectToDashboard(session('user_role'));
        }

        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nombre_usuario' => ['required', 'string', 'max:20'],
            'contrasena' => ['required', 'string'],
        ], [
            'nombre_usuario.required' => 'Ingresa tu usuario.',
            'contrasena.required' => 'Ingresa tu contraseña.',
        ]);

        $user = DB::table('usuarios')
            ->join('usuario_rol', 'usuario_rol.usuario_id', '=', 'usuarios.id')
            ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->where('usuarios.nombre_usuario', $credentials['nombre_usuario'])
            ->where('usuarios.habilitado', true)
            ->where('usuarios.permitir_login', true)
            ->select('usuarios.*', 'roles.nombre as rol_nombre')
            ->first();

        $legacyPassword = $user && $this->isLegacyPasswordHash((string) $user->contrasena);

        if (! $user || ! $this->passwordMatches($credentials['contrasena'], (string) $user->contrasena)) {
            $this->registrarBitacora($user->id ?? null, $request->ip(), 'LOGIN_FALLIDO', 'Intento fallido de inicio de sesión');

            return back()
                ->withInput($request->only('nombre_usuario'))
                ->withErrors(['nombre_usuario' => 'Usuario o contraseña incorrectos.']);
        }

        // Verificar IP permitida
        if (! $this->ipEsPermitida($user->ip_permitida, $request->ip())) {
            $this->registrarBitacora($user->id, $request->ip(), 'LOGIN_BLOQUEADO', 'IP no permitida');

            return back()
                ->withInput($request->only('nombre_usuario'))
                ->withErrors(['nombre_usuario' => 'Tu IP no está autorizada para iniciar sesión.']);
        }

        // Regenerar sesión para prevenir fijación de sesión
        $request->session()->regenerate();
        $token = bin2hex(random_bytes(32));

        // Guardar datos en sesión
        $request->session()->put([
            'user_id' => $user->id,
            'user_username' => $user->nombre_usuario,
            'user_name' => $user->nombre_completo,
            'user_role' => $user->rol_nombre,
            'user_foto' => $user->foto,
            'user_ip' => $request->ip(),
            'session_token' => $token,
        ]);

        DB::transaction(function () use ($user, $request, $credentials, $legacyPassword, $token): void {
            if (! $user->multilogin) {
                $this->cerrarSesionesAnteriores($user->id);
            }

            DB::table('sesiones')->insert([
                'usuario_id' => $user->id,
                'token' => $token,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'inicio' => now(),
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $updates = [
                'ultimo_login' => now(),
                'ip' => $request->ip(),
                'sesion' => $request->session()->getId(),
                'updated_at' => now(),
            ];

            if ($legacyPassword) {
                $updates['contrasena'] = Hash::make($credentials['contrasena']);
            }

            DB::table('usuarios')->where('id', $user->id)->update($updates);
        });

        $this->registrarBitacora($user->id, $request->ip(), 'LOGIN', 'Inicio de sesión exitoso');

        return $this->redirectToDashboard($user->rol_nombre);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = session('user_id');
        $sessionToken = $request->session()->get('session_token');

        if ($userId) {
            DB::transaction(function () use ($userId, $sessionToken): void {
                DB::table('sesiones')
                    ->where('usuario_id', $userId)
                    ->where('token', $sessionToken)
                    ->where('activa', true)
                    ->update([
                        'activa' => false,
                        'fin' => now(),
                        'updated_at' => now(),
                    ]);

                $updates = ['ultimo_logout' => now(), 'updated_at' => now()];
                if (! DB::table('sesiones')->where('usuario_id', $userId)->where('activa', true)->exists()) {
                    $updates['sesion'] = '';
                }

                DB::table('usuarios')->where('id', $userId)->update($updates);
            });

            $this->registrarBitacora($userId, $request->ip(), 'LOGOUT', 'Cierre de sesión');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    /**
     * Muestra el perfil del usuario.
     */
    public function profile(): View|RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        // Estadísticas del usuario
        $stats = [
            'sesiones_activas' => DB::table('sesiones')
                ->where('usuario_id', $user->id)
                ->where('activa', true)
                ->count(),
            'ultimo_login' => $user->ultimo_login,
            'total_sesiones' => DB::table('sesiones')
                ->where('usuario_id', $user->id)
                ->count(),
        ];

        return view('auth.profile', compact('user', 'stats'));
    }

    /**
     * Actualiza el perfil del usuario.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $validated = $request->validate([
            'ci' => ['required', 'string', 'max:50', Rule::unique('usuarios', 'ci')->ignore($user->id)],
            'nombre_usuario' => ['required', 'string', 'max:20', Rule::unique('usuarios', 'nombre_usuario')->ignore($user->id)],
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

        // Procesar foto de perfil
        $fotoPath = $user->foto;
        if ($request->hasFile('foto')) {
            // Eliminar foto anterior si existe
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            // Guardar nueva foto
            $file = $request->file('foto');
            $filename = 'avatar_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $fotoPath = $file->storeAs('avatars', $filename, 'public');
        }

        DB::table('usuarios')->where('id', $user->id)->update([
            'ci' => $validated['ci'],
            'nombre_usuario' => $validated['nombre_usuario'],
            'nombre_completo' => $validated['nombre_completo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'ip_permitida' => $validated['ip_permitida'] ?? null,
            'multilogin' => $request->boolean('multilogin'),
            'foto' => $fotoPath,
            'updated_at' => now(),
        ]);

        if (! $request->boolean('multilogin')) {
            DB::table('sesiones')
                ->where('usuario_id', $user->id)
                ->where('activa', true)
                ->where('token', '!=', $request->session()->get('session_token'))
                ->update([
                    'activa' => false,
                    'fin' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Actualizar sesión
        session()->put([
            'user_username' => $validated['nombre_usuario'],
            'user_name' => $validated['nombre_completo'],
            'user_foto' => $fotoPath,
        ]);

        $this->registrarBitacora($user->id, $request->ip(), 'PERFIL_ACTUALIZADO', 'Actualización de perfil');

        return redirect()->route('auth.profile')->with('status', 'Tu perfil se actualizó correctamente.');
    }

    /**
     * Elimina la foto de perfil del usuario.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        if ($user->foto && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        DB::table('usuarios')->where('id', $user->id)->update([
            'foto' => null,
            'updated_at' => now(),
        ]);

        session()->forget('user_foto');

        $this->registrarBitacora($user->id, $request->ip(), 'AVATAR_ELIMINADO', 'Foto de perfil eliminada');

        return redirect()->route('auth.profile')->with('status', 'Foto de perfil eliminada correctamente.');
    }

    /**
     * Muestra la configuración de seguridad.
     */
    public function settings(): View|RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        // Obtener sesiones activas
        $sesionesActivas = DB::table('sesiones')
            ->where('usuario_id', $user->id)
            ->where('activa', true)
            ->orderBy('inicio', 'desc')
            ->get();

        // Obtener bitácora reciente
        $bitacora = DB::table('bitacoras')
            ->where('usuario_id', $user->id)
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();

        return view('auth.settings', compact('user', 'sesionesActivas', 'bitacora'));
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'new_password.required' => 'Ingresa una nueva contraseña.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'La confirmación de contraseña no coincide.',
            'new_password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.',
        ]);

        if (! $this->passwordMatches($validated['current_password'], (string) $user->contrasena)) {
            return back()->withErrors([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        DB::table('usuarios')->where('id', $user->id)->update([
            'contrasena' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);

        $this->registrarBitacora($user->id, $request->ip(), 'PASSWORD_CAMBIADO', 'Contraseña actualizada');

        $request->session()->regenerate();

        return redirect()->route('auth.settings')->with('status', 'Tu contraseña se cambió correctamente.');
    }

    /**
     * Cierra todas las sesiones excepto la actual.
     */
    public function closeOtherSessions(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $currentSessionToken = $request->session()->get('session_token');

        DB::table('sesiones')
            ->where('usuario_id', $user->id)
            ->where('activa', true)
            ->where('token', '!=', $currentSessionToken)
            ->update([
                'activa' => false,
                'fin' => now(),
                'updated_at' => now(),
            ]);

        $this->registrarBitacora($user->id, $request->ip(), 'SESIONES_CERRADAS', 'Otras sesiones cerradas');

        return redirect()->route('auth.settings')->with('status', 'Se cerraron las otras sesiones correctamente.');
    }

    /**
     * Cierra una sesión específica.
     */
    public function closeSession(Request $request, int $sessionId): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        DB::table('sesiones')
            ->where('id', $sessionId)
            ->where('usuario_id', $user->id)
            ->where('activa', true)
            ->update([
                'activa' => false,
                'fin' => now(),
                'updated_at' => now(),
            ]);

        return redirect()->route('auth.settings')->with('status', 'Sesión cerrada correctamente.');
    }

    /**
     * Obtiene el usuario actual.
     */
    private function currentUser(): ?object
    {
        $userId = session('user_id');

        if (! $userId) {
            return null;
        }

        return DB::table('usuarios')->where('id', $userId)->first();
    }

    /**
     * Verifica si la contraseña coincide.
     */
    private function passwordMatches(string $password, string $storedPassword): bool
    {
        if ($this->isLegacyPasswordHash($storedPassword)) {
            return hash_equals($storedPassword, hash('sha256', $password));
        }

        return Hash::check($password, $storedPassword);
    }

    /**
     * Verifica si es un hash de contraseña legacy (SHA256).
     */
    private function isLegacyPasswordHash(string $storedPassword): bool
    {
        return preg_match('/\A[a-f0-9]{64}\z/i', $storedPassword) === 1;
    }

    /**
     * Verifica si la IP está permitida.
     */
    private function ipEsPermitida(?string $ipPermitida, string $ipActual): bool
    {
        if (empty($ipPermitida)) {
            return true;
        }

        // Soportar múltiples IPs separadas por coma
        $ips = array_map('trim', explode(',', $ipPermitida));

        foreach ($ips as $ip) {
            if ($ip === $ipActual) {
                return true;
            }

            // Soportar rangos CIDR
            if (str_contains($ip, '/') && $this->ipEnRango($ipActual, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si una IP está en un rango CIDR.
     */
    private function ipEnRango(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Cierra sesiones anteriores del usuario.
     */
    private function cerrarSesionesAnteriores(int $userId): void
    {
        DB::table('sesiones')
            ->where('usuario_id', $userId)
            ->where('activa', true)
            ->update([
                'activa' => false,
                'fin' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Registra una entrada en la bitácora.
     */
    private function registrarBitacora(?int $userId, string $ip, string $tipo, string $datos): void
    {
        DB::table('bitacoras')->insert([
            'usuario_id' => $userId,
            'ip' => $ip,
            'fecha' => now(),
            'tipo' => $tipo,
            'datos' => $datos,
            'estado' => 'OK',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Redirige al dashboard correspondiente según el rol.
     */
    protected function redirectToDashboard(?string $role): RedirectResponse
    {
        $route = $role ? "{$role}.dashboard" : null;

        if ($route && Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('auth.login');
    }
}
