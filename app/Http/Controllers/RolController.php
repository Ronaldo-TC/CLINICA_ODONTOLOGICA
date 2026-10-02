<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolController extends Controller
{
    use HasCrudAjax;

    /* ============================================================
       CONFIGURACIÓN DEL TRAIT
       ============================================================ */
    protected string $modelClass       = Role::class;
    protected string $viewPrefix       = 'roles';
    protected string $resourceName     = 'Rol';
    protected string $pluralName       = 'Roles';
    protected string $permissionPrefix = 'roles';
    protected int    $perPage          = 15;

    /**
     * Roles base del sistema que no se pueden eliminar.
     */
    private const ROLES_PROTEGIDOS = ['admin', 'subadmin'];

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('name', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
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
            'permisosAgrupados' => $this->permisosAgrupados(),
            'totalPermisos'     => Permission::count(),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'permisos'              => $this->permisosAgrupados(),
            'permisosSeleccionados' => $model->permissions->pluck('name')->toArray(),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($id),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'habilitado'  => ['nullable', 'boolean'],
            'permisos'    => ['nullable', 'array'],
            'permisos.*'  => ['string', 'exists:permissions,name'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'name.required'      => 'El nombre del rol es obligatorio.',
            'name.unique'        => 'Ya existe un rol con ese nombre.',
            'name.alpha_dash'    => 'Solo letras, números, guión y guión bajo.',
            'name.max'           => 'El nombre no puede exceder 100 caracteres.',
            'descripcion.max'    => 'La descripción no puede exceder 255 caracteres.',
            'permisos.*.exists'  => 'Uno de los permisos seleccionados no existe.',
        ];
    }

    /* ============================================================
       HOOKS DE NEGOCIO
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['descripcion'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data = $this->normalizarStrings($data);

        $data['guard_name'] = 'web';
        $data['habilitado'] = request()->boolean('habilitado', true) ? 1 : 0;

        unset($data['permisos']);   // Se sincronizan en afterStore

        return $data;
    }

    protected function afterStore($model): void
    {
        $this->sincronizarPermisos($model);
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data = $this->normalizarStrings($data);

        $data['habilitado'] = request()->boolean('habilitado') ? 1 : 0;

        unset($data['permisos']);

        return $data;
    }

    protected function afterUpdate($model): void
    {
        $this->sincronizarPermisos($model);
    }

    protected function canDestroy($model): array
    {
        // 1. Roles base del sistema no se eliminan
        if (in_array($model->name, self::ROLES_PROTEGIDOS, true)) {
            return [false, "No se puede eliminar el rol base \"{$model->name}\"."];
        }

        // 2. Roles con usuarios asignados no se eliminan
        $count = $model->users()->count();
        if ($count > 0) {
            return [false, "No se puede eliminar: {$count} usuario(s) tienen este rol asignado."];
        }

        return [true, ''];
    }

    /* ============================================================
       SINCRONIZAR PERMISOS (endpoint separado)
       ============================================================ */

    /**
     * POST /roles/{rol}/permisos
     * Route name: roles.permisos
     * Middleware: permission:roles.editar
     */
    public function syncPermisos(Request $request, $id)
    {
        $this->authorizeAction('editar');

        $request->validate([
            'permisos'   => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ]);

        $rol = Role::findOrFail($id);

        // El rol admin siempre tiene TODOS los permisos
        if ($rol->name === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'El rol "admin" siempre tiene todos los permisos. No se puede modificar.',
            ], 422);
        }

        $rol->syncPermissions($request->input('permisos', []));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente.',
            'total'   => $rol->permissions()->count(),
        ]);
    }

    /* ============================================================
       HELPERS PRIVADOS
       ============================================================ */

    /**
     * Devuelve los permisos agrupados por módulo (prefijo antes del punto).
     * Ej: ['usuarios' => [...], 'roles' => [...], 'pacientes' => [...]]
     */
    private function permisosAgrupados(): array
    {
        $permisos = Permission::orderBy('name')->get();

        $agrupados = [];
        foreach ($permisos as $permiso) {
            $partes = explode('.', $permiso->name);
            $modulo = $partes[0] ?? 'otros';
            $accion = $partes[1] ?? $permiso->name;

            $agrupados[$modulo][] = [
                'id'          => $permiso->id,
                'name'        => $permiso->name,
                'descripcion' => $permiso->descripcion ?? '',
                'accion'      => $accion,
            ];
        }

        ksort($agrupados);

        return $agrupados;
    }

    /**
     * Sincroniza permisos desde la request.
     */
    private function sincronizarPermisos(Role $rol): void
    {
        // Rol admin no se toca
        if ($rol->name === 'admin') {
            return;
        }

        $permisos = request()->input('permisos', []);

        if (is_array($permisos)) {
            $rol->syncPermissions($permisos);
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
}
