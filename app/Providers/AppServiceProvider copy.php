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
     * Registra servicios de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap de servicios de la aplicación.
     */
    public function boot(): void
    {
        $this->registerSidebarComposer();
    }

    /**
     * Composer del sidebar: calcula usuario, roles, permisos y menú.
     */
    private function registerSidebarComposer(): void
    {
        View::composer('layouts.partials.sidebar', function (ViewInstance $view): void {
            $userId = session('user_id');
            $user = $userId ? Usuario::with('roles')->find($userId) : null;

            $roles = $user?->roles->where('habilitado', true)->values() ?? collect();
            $roleNames = $roles->pluck('nombre')->map(fn ($r) => (string) $r)->all();

            $isAdmin = in_array('admin', $roleNames, true);

            // Permisos efectivos: array de códigos "modulo.accion"
            $permisos = $user ? $this->obtenerPermisos($user->id, $isAdmin) : [];

            $menu = $this->construirMenu($roleNames, $permisos, $isAdmin);

            $roleLabel = $roleNames
                ? implode(' / ', array_map(
                    fn (string $role): string => ucfirst(str_replace('-', ' ', $role)),
                    $roleNames
                ))
                : (string) session('user_role', 'invitado');

            $view->with([
                'sidebarUser' => $user,
                'sidebarRole' => $roleLabel,
                'sidebarMenu' => $menu,
                'sidebarRoles' => $roleNames,
                'sidebarPerms' => $permisos,
            ]);
        });
    }

    // ============================================================
    // PERMISOS
    // ============================================================

    /**
     * Obtiene los permisos efectivos del usuario.
     *
     * - Admin: todos los permisos existentes (sin consultar rol_permiso).
     * - Otros: union de permisos habilitados en sus roles habilitados.
     *
     * @return array<int, string> Códigos tipo "modulo.accion"
     */
    private function obtenerPermisos(int $userId, bool $isAdmin): array
    {
        $query = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
            ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
            ->where('ur.usuario_id', $userId)
            ->where('r.habilitado', true)
            ->where('rp.habilitado', true);

        if ($isAdmin) {
            return DB::table('permisos')
                ->pluck('codigo')
                ->map(fn ($code) => (string) $code)
                ->all();
        }

        return $query
            ->distinct()
            ->pluck('p.codigo')
            ->map(fn ($code) => (string) $code)
            ->all();
    }

    /**
     * ¿El usuario tiene el permiso "modulo.accion"?
     */
    private function tienePermiso(array $permisos, string $codigo): bool
    {
        return in_array($codigo, $permisos, true);
    }

    /**
     * ¿El usuario tiene CUALQUIERA de estos permisos?
     *
     * @param  array<int, string>  $codigos
     */
    private function tieneAlguno(array $permisos, array $codigos): bool
    {
        foreach ($codigos as $codigo) {
            if (in_array($codigo, $permisos, true)) {
                return true;
            }
        }

        return false;
    }

    // ============================================================
    // CONSTRUCCIÓN DEL MENÚ
    // ============================================================

    /**
     * Construye el menú del sidebar agrupado por secciones.
     *
     * @param  array<int, string>  $roleNames
     * @param  array<int, string>  $permisos
     * @return array<int, array<string, mixed>>
     */
    private function construirMenu(array $roleNames, array $permisos, bool $isAdmin): array
    {
        $menu = [];

        // ── 1. Dashboards por rol ─────────────────────────────
        $dashboardItems = [];
        foreach ($roleNames as $roleName) {
            $dashboardRoute = "{$roleName}.dashboard";
            if (Route::has($dashboardRoute)) {
                $dashboardItems[] = [
                    'label' => 'Panel '.$this->humanizarRol($roleName),
                    'route' => $dashboardRoute,
                    'icon' => 'grid',
                    'pattern' => $dashboardRoute,
                ];
            }
        }

        if ($dashboardItems !== []) {
            $menu[] = ['type' => 'label', 'label' => 'Panel'];
            array_push($menu, ...$dashboardItems);
        }

        // ── 2. Menús específicos por rol ──────────────────────
        // Se construyen aquí abajo con datos declarativos.

        $secciones = [];

        // ============ ADMINISTRACIÓN ============
        if ($isAdmin) {
            $secciones['Administración'] = [
                ['label' => 'Usuarios',         'route' => 'admin.usuarios.index',      'icon' => 'users'],
                ['label' => 'Roles y permisos', 'route' => 'admin.roles.index',         'icon' => 'shield'],
                ['label' => 'Clínicas',         'route' => 'admin.clinicas.index',      'icon' => 'layers'],
                ['label' => 'Periodos',         'route' => 'admin.periodos.index',      'icon' => 'folder'],
                ['label' => 'Pacientes',        'route' => 'admin.pacientes.index',     'icon' => 'user-plus'],
                ['label' => 'Tratamientos',     'route' => 'admin.tratamientos.index',  'icon' => 'activity'],
                ['label' => 'Historias',        'route' => 'admin.historias.index',     'icon' => 'file-text'],
                ['label' => 'Cajas',            'route' => 'admin.cajas.index',         'icon' => 'credit-card'],
                ['label' => 'Insumos',          'route' => 'admin.insumos.index',       'icon' => 'package'],
                ['label' => 'Bitácoras',        'route' => 'admin.bitacoras.index',     'icon' => 'clipboard'],
                ['label' => 'Reportes',         'route' => 'admin.reportes.index',      'icon' => 'chart'],
            ];
        }

        // ============ DIRECCIÓN ============
        if (! $isAdmin && $this->tieneAlguno($roleNames, ['director', 'jefatura-clinicas', 'jefe-catedra'])) {
            $items = [];

            if (in_array('director', $roleNames, true)) {
                // Director: solo lectura global + reportes
                $items = [
                    ['label' => 'Pacientes',      'route' => 'director.pacientes.index',      'icon' => 'user-plus'],
                    ['label' => 'Clínicas',       'route' => 'director.clinicas.index',       'icon' => 'layers'],
                    ['label' => 'Periodos',       'route' => 'director.periodos.index',       'icon' => 'folder'],
                    ['label' => 'Tratamientos',   'route' => 'director.tratamientos.index',   'icon' => 'activity'],
                    ['label' => 'Historias',      'route' => 'director.historias.index',      'icon' => 'file-text'],
                    ['label' => 'Cajas',          'route' => 'director.cajas.index',          'icon' => 'credit-card'],
                    ['label' => 'Insumos',        'route' => 'director.insumos.index',        'icon' => 'package'],
                    ['label' => 'Esterilización', 'route' => 'director.esterilizaciones.index', 'icon' => 'activity'],
                    ['label' => 'Radiología',     'route' => 'director.radiologias.index',    'icon' => 'image'],
                    ['label' => 'Bitácoras',      'route' => 'director.bitacoras.index',      'icon' => 'clipboard'],
                    ['label' => 'Reportes',       'route' => 'director.reportes.index',       'icon' => 'chart'],
                    ['label' => 'Investigación',  'route' => 'director.investigacion.index',  'icon' => 'trending'],
                ];
            }

            if ($items) {
                $secciones['Dirección'] = $items;
            }
        }

        // ============ DOCENTE ============
        if (! $isAdmin && in_array('docente', $roleNames, true)) {
            $secciones['Docencia'] = [
                ['label' => 'Pacientes derivados', 'route' => 'docente.pacientes-derivados', 'icon' => 'inbox'],
                ['label' => 'Historias clínicas',  'route' => 'docente.historias-clinicas',  'icon' => 'file-text'],
                ['label' => 'Insumos solicitados', 'route' => 'docente.insumos-solicitados', 'icon' => 'package'],
                ['label' => 'Esterilizaciones',    'route' => 'docente.esterilizaciones',    'icon' => 'activity'],
                ['label' => 'Cajas',               'route' => 'docente.cajas',               'icon' => 'credit-card'],
                ['label' => 'Radiologías',         'route' => 'docente.radiologias',         'icon' => 'image'],
                ['label' => 'Reportes',            'route' => 'docente.reportes',            'icon' => 'chart'],
            ];
        }

        // ============ MÓDULOS HABILITADOS POR PERMISO ============
        // Los roles clínicos usan sus rutas propias; los permisos genéricos
        // sólo habilitan enlaces para perfiles administrativos compatibles.
        if (! $isAdmin && ! $this->tieneAlguno($roleNames, [
            'director',
            'docente',
            'estudiante',
            'interno',
            'admision',
            'cajas',
            'insumos',
            'esterilizacion',
            'radiologia',
            'investigacion',
        ])) {
            $extras = $this->modulosPorPermiso($permisos, $roleNames);
            if ($extras) {
                $secciones['Módulos habilitados'] = $extras;
            }
        }

        // ── 3. Aplanar el menú respetando el orden ────────────
        foreach ($secciones as $titulo => $items) {
            $rutasExistentes = collect($menu)
                ->pluck('route')
                ->filter()
                ->all();
            $itemsValidos = [];

            foreach ($items as $item) {
                if (! Route::has($item['route']) || in_array($item['route'], $rutasExistentes, true)) {
                    continue;
                }

                $rutasExistentes[] = $item['route'];
                $itemsValidos[] = $item;
            }

            if (! $itemsValidos) {
                continue;
            }

            $menu[] = ['type' => 'label', 'label' => $titulo];

            foreach ($itemsValidos as $item) {
                $menu[] = $item + [
                    'pattern' => str_replace('.index', '.*', $item['route']),
                ];
            }
        }

        // ── 4. Botón de "Mi perfil" / "Configuración" siempre al final ──
        $menu[] = ['type' => 'label', 'label' => 'Cuenta'];

        if (Route::has('auth.profile')) {
            $menu[] = ['label' => 'Mi perfil', 'route' => 'auth.profile', 'icon' => 'users'];
        }
        if (Route::has('auth.settings')) {
            $menu[] = ['label' => 'Configuración', 'route' => 'auth.settings', 'icon' => 'settings'];
        }

        return $menu;
    }

    /**
     * Enlaces secundarios definidos por permisos del catálogo.
     *
     * @param  array<int, string>  $permisos
     * @param  array<int, string>  $roleNames
     * @return array<int, array<string, mixed>>
     */
    private function modulosPorPermiso(array $permisos, array $roleNames): array
    {
        $extras = [];

        // Permisos sembrados para perfiles administrativos sin menú propio.
        $mapa = [
            'usuarios.ver' => [
                'label' => 'Usuarios',
                'route' => 'admin.usuarios.index',
                'icon' => 'users',
                'roles' => ['subadmin'],
            ],
            'clinica.gestionar' => [
                'label' => 'Clínicas',
                'route' => 'admin.clinicas.index',
                'icon' => 'layers',
                'roles' => ['subadmin', 'jefatura-clinicas'],
            ],
            'pacientes.gestionar' => [
                'label' => 'Pacientes',
                'route' => 'admin.pacientes.index',
                'icon' => 'user-plus',
                'roles' => ['subadmin', 'jefatura-clinicas'],
            ],
            'tratamientos.gestionar' => [
                'label' => 'Tratamientos',
                'route' => 'admin.tratamientos.index',
                'icon' => 'activity',
                'roles' => ['subadmin', 'jefatura-clinicas'],
            ],
            'reportes.ver' => [
                'label' => 'Reportes',
                'route' => 'admin.reportes.index',
                'icon' => 'chart',
                'roles' => ['subadmin'],
            ],
        ];

        foreach ($mapa as $codigoPermiso => $item) {
            if (! $this->tienePermiso($permisos, $codigoPermiso)) {
                continue;
            }

            if (count(array_intersect($roleNames, $item['roles'])) === 0) {
                continue;
            }

            if (! Route::has($item['route'])) {
                continue;
            }

            $extras[] = [
                'label' => $item['label'],
                'route' => $item['route'],
                'icon' => $item['icon'],
            ];
        }

        return $extras;
    }

    /**
     * Convierte un nombre de rol técnico a etiqueta legible.
     * Ej: "jefe-catedra" → "Jefe Cátedra"
     */
    private function humanizarRol(string $role): string
    {
        $map = [
            'admin' => 'Administrador',
            'subadmin' => 'Subadministrador',
            'director' => 'Director',
            'jefatura-clinicas' => 'Jefatura de Clínicas',
            'jefe-catedra' => 'Jefe de Cátedra',
            'docente' => 'Docente',
            'estudiante' => 'Estudiante',
            'interno' => 'Interno',
            'admision' => 'Admisión',
            'kardex' => 'Kardex',
            'cajas' => 'Cajas',
            'insumos' => 'Insumos',
            'esterilizacion' => 'Esterilización',
            'radiologia' => 'Radiología',
            'investigacion' => 'Investigación',
        ];

        return $map[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }
}
