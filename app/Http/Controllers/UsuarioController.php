<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass = Usuario::class;

    protected string $viewPrefix = 'usuarios';

    protected string $resourceName = 'Usuario';

    protected string $pluralName = 'Usuarios';

    protected string $permissionPrefix = 'usuarios';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Usuario::with('roles')->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = Usuario::with('roles')->orderByDesc('id');

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('ci', 'like', "%{$buscar}%")
                    ->orWhere('nombre_usuario', 'like', "%{$buscar}%")
                    ->orWhere('nombre_completo', 'like', "%{$buscar}%");
            });
        }

        if ($rol = trim((string) $request->input('rol'))) {
            $q->whereHas('roles', fn ($x) => $x->where('name', $rol));
        }

        if (($estado = $request->input('estado')) !== null && $estado !== '') {
            $q->where('habilitado', (int) $estado);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA PARA VISTAS
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'roles' => Role::orderBy('name')->get(),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'roles' => Role::orderBy('name')->get(),
            'rolesSeleccionados' => $model->roles->pluck('id')->toArray(),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'ci' => [
                'required',
                'string',
                'max:50',
                Rule::unique('usuarios', 'ci')->ignore($id),
            ],
            'nombre_usuario' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($id),
            ],
            'nombre_completo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'tipo' => ['nullable', 'string', 'max:20'],
            'info' => ['nullable', 'string', 'max:300'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'contrasena' => $id
                ? ['nullable', 'string', 'min:6', 'max:100']
                : ['required', 'string', 'min:6', 'max:100'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'ci.required' => 'La cédula es obligatoria.',
            'ci.unique' => 'Ya existe un usuario con esa cédula.',
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'nombre_usuario.alpha_dash' => 'Solo letras, números, guion y guion bajo.',
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'Mínimo 6 caracteres.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }

    /* ============================================================
       HOOKS DE NEGOCIO
       ============================================================ */

    /**
     * Normaliza strings opcionales: null → '' para columnas NOT NULL DEFAULT ''.
     * Evita el error "Column 'X' cannot be null".
     */
    private function normalizarStrings(array $data): array
    {
        foreach (['descripcion', 'tipo', 'info'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        // ⚠️ FIX: null → '' antes de tocar la BD
        $data = $this->normalizarStrings($data);

        $data['contrasena'] = Hash::make($data['contrasena']);
        $data['habilitado'] = request()->boolean('habilitado', true) ? 1 : 0;
        $data['permitir_login'] = request()->boolean('permitir_login', true) ? 1 : 0;
        $data['multilogin'] = request()->boolean('multilogin', false) ? 1 : 0;
        $data['sesion'] = '';
        $data['sesion_extra'] = '';

        // Si descripcion viene vacía, usar el nombre completo
        if (empty($data['descripcion'])) {
            $data['descripcion'] = $data['nombre_completo'];
        }

        unset($data['roles']);

        return $data;
    }

    protected function afterStore($model): void
    {
        $this->syncRoles($model);
    }

    protected function beforeUpdate(Usuario $model, array $data): array
    {
        // ⚠️ FIX: null → '' antes de tocar la BD
        $data = $this->normalizarStrings($data);

        if (! empty($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        $data['habilitado'] = request()->boolean('habilitado') ? 1 : 0;
        $data['permitir_login'] = request()->boolean('permitir_login') ? 1 : 0;
        $data['multilogin'] = request()->boolean('multilogin') ? 1 : 0;

        if (! $data['habilitado'] || ! $data['permitir_login']) {
            [$canDisable, $message] = $this->canDestroy($model);

            if (! $canDisable) {
                $field = ! $data['habilitado'] ? 'habilitado' : 'permitir_login';

                throw ValidationException::withMessages([$field => $message]);
            }
        }

        $this->assertAdminRolePreserved($model, $data['roles'] ?? []);

        unset($data['roles']);

        return $data;
    }

    protected function afterUpdate(Usuario $model): void
    {
        $this->syncRoles($model);

        if (! $model->habilitado || ! $model->permitir_login) {
            $this->revokeActiveSessions($model);
        }
    }

    /**
     * @return array{bool, string}
     */
    protected function canDestroy(Usuario $model): array
    {
        if ($model->id === auth()->id()) {
            return [false, 'No puedes deshabilitar tu propio usuario.'];
        }

        if ($this->isLastActiveAdmin($model)) {
            return [false, 'No puedes deshabilitar al último administrador activo.'];
        }

        return [true, ''];
    }

    /* ============================================================
       OVERRIDE DEL DESTROY — Soft delete (deshabilitar)
       ============================================================ */

    public function destroy(Request $request, $id)
    {
        $this->authorizeAction('eliminar');

        $usuario = Usuario::findOrFail($id);

        $puede = $this->canDestroy($usuario);
        if ($puede[0] === false) {
            return response()->json([
                'success' => false,
                'message' => $puede[1],
            ], 422);
        }

        $usuario->update([
            'habilitado' => 0,
            'permitir_login' => 0,
            'sesion' => '',
            'sesion_extra' => '',
            'ultimo_logout' => now(),
        ]);

        $this->revokeActiveSessions($usuario);

        return response()->json([
            'success' => true,
            'message' => 'Usuario deshabilitado correctamente.',
        ]);
    }

    /* ============================================================
       MÉTODOS ESPECÍFICOS
       ============================================================ */

    public function habilitar(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $usuario = Usuario::findOrFail($id);
        $usuario->update([
            'habilitado' => 1,
            'permitir_login' => 1,
            'ultimo_logout' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario habilitado correctamente.',
        ]);
    }

    public function cambiarRol(Request $request, int|string $id): JsonResponse
    {
        $this->authorizeAction('editar');

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $usuario = Usuario::findOrFail($id);
        $this->assertAdminRolePreserved($usuario, $validated['roles']);

        $nombres = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
        $usuario->syncRoles($nombres);

        return response()->json([
            'success' => true,
            'message' => 'Roles actualizados correctamente.',
        ]);
    }

    /* ============================================================
       HELPER PRIVADO
       ============================================================ */

    private function syncRoles(Usuario $usuario): void
    {
        $roles = request()->input('roles', []);
        $nombres = Role::whereIn('id', (array) $roles)->pluck('name')->toArray();
        $usuario->syncRoles($nombres);
    }

    private function isLastActiveAdmin(Usuario $usuario): bool
    {
        if (! $usuario->habilitado || ! $usuario->permitir_login || ! $usuario->hasRole('admin')) {
            return false;
        }

        return ! Usuario::query()
            ->where('id', '!=', $usuario->id)
            ->where('habilitado', true)
            ->where('permitir_login', true)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'admin'))
            ->exists();
    }

    /**
     * @param  array<int, int|string>  $roleIds
     */
    private function assertAdminRolePreserved(Usuario $usuario, array $roleIds): void
    {
        if ($this->isLastActiveAdmin($usuario)
            && ! Role::whereIn('id', $roleIds)->where('name', 'admin')->exists()) {
            throw ValidationException::withMessages([
                'roles' => 'No puedes retirar el rol de administrador al último administrador activo.',
            ]);
        }
    }

    private function revokeActiveSessions(Usuario $usuario): void
    {
        DB::table('sesiones')
            ->where('usuario_id', $usuario->id)
            ->where('activa', true)
            ->update([
                'activa' => false,
                'fin' => now(),
                'updated_at' => now(),
            ]);

        $usuario->tokens()->delete();
    }
}
