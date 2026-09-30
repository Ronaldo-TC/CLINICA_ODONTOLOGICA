<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.partials.sidebar', function (ViewInstance $view): void {
            $userId = session('user_id');
            $user = $userId ? Usuario::with('roles')->find($userId) : null;
            $roles = $user?->roles->where('habilitado', true)->values() ?? collect();
            $roleNames = $roles->pluck('nombre')->all();
            $isAdmin = in_array('admin', $roleNames, true);

            $permissionModules = [];
            if ($user) {
                if ($isAdmin) {
                    $permissionModules = DB::table('permisos')
                        ->whereNotNull('modulo')
                        ->where(function ($query): void {
                            $query->where('codigo', 'like', '%.gestionar')
                                ->orWhere('codigo', 'like', '%.ver');
                        })
                        ->distinct()
                        ->pluck('modulo')
                        ->all();
                } else {
                    $permissionModules = DB::table('usuario_rol as ur')
                        ->join('roles as r', 'r.id', '=', 'ur.rol_id')
                        ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
                        ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
                        ->where('ur.usuario_id', $user->id)
                        ->where('r.habilitado', true)
                        ->where('rp.habilitado', true)
                        ->whereNotNull('p.modulo')
                        ->where(function ($query): void {
                            $query->where('p.codigo', 'like', '%.gestionar')
                                ->orWhere('p.codigo', 'like', '%.ver');
                        })
                        ->distinct()
                        ->pluck('p.modulo')
                        ->all();
                }
            }

            $menu = [];
            foreach ($roleNames as $roleName) {
                $dashboardRoute = "{$roleName}.dashboard";
                if (Route::has($dashboardRoute)) {
                    $menu[] = [
                        'label' => 'Panel '.ucfirst(str_replace('-', ' ', $roleName)),
                        'route' => $dashboardRoute,
                        'icon' => 'grid',
                    ];
                }
            }

            $moduleMenus = [
                'usuarios' => ['label' => 'Usuarios', 'route' => 'admin.usuarios.index', 'icon' => 'users'],
                'clinica' => ['label' => 'Clínicas', 'route' => 'admin.clinicas.index', 'icon' => 'layers'],
                'pacientes' => ['label' => 'Pacientes', 'route' => 'admin.pacientes.index', 'icon' => 'user-plus'],
                'tratamientos' => ['label' => 'Tratamientos', 'route' => 'admin.tratamientos.index', 'icon' => 'activity'],
                'insumos' => ['label' => 'Insumos', 'route' => 'admin.insumos.index', 'icon' => 'package'],
                'cajas' => ['label' => 'Cajas', 'route' => 'admin.cajas.index', 'icon' => 'credit-card'],
                'reportes' => ['label' => 'Reportes', 'route' => 'admin.reportes.index', 'icon' => 'chart'],
            ];

            foreach ($moduleMenus as $module => $item) {
                if (in_array($module, $permissionModules, true) && Route::has($item['route'])) {
                    $menu[] = $item + ['pattern' => str_replace('.index', '.*', $item['route'])];
                }
            }

            if ($isAdmin) {
                foreach (
                    [
                        ['label' => 'Roles y permisos', 'route' => 'admin.roles.index', 'icon' => 'shield'],
                        ['label' => 'Periodos', 'route' => 'admin.periodos.index', 'icon' => 'folder'],
                        ['label' => 'Historias clínicas', 'route' => 'admin.historias.index', 'icon' => 'file-text'],
                        ['label' => 'Bitácoras', 'route' => 'admin.bitacoras.index', 'icon' => 'clipboard'],
                    ] as $item
                ) {
                    if (Route::has($item['route'])) {
                        $menu[] = $item + ['pattern' => str_replace('.index', '.*', $item['route'])];
                    }
                }
            }

            $roleLabel = $roleNames
                ? implode(' / ', array_map(fn (string $role): string => ucfirst(str_replace('-', ' ', $role)), $roleNames))
                : (string) session('user_role', 'invitado');

            $view->with([
                'sidebarUser' => $user,
                'sidebarRole' => $roleLabel,
                'sidebarMenu' => $menu,
            ]);
        });
    }
}
