<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Support\FilesystemTolerante;
use App\Support\MenuBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Escritura de archivos tolerante a los avisos de `tempnam()` en Windows
        // (afecta a la compilación de vistas Blade).
        $this->app->singleton('files', fn () => new FilesystemTolerante());
    }

    public function boot(): void
    {
        // Aplica a sidebar y topbar
        View::composer(
            ['layouts.partials.sidebar', 'layouts.partials.topbar'],
            function ($view) {

                // 1. Usuario autenticado: primero por Auth, luego por sesión custom
                $user = Auth::user()
                    ?? Usuario::with('roles', 'permissions')
                    ->find(session('user_id'));

                $sidebarRole  = null;
                $sidebarRoleName = null;
                $sidebarMenu  = [];

                if ($user) {
                    // Rol principal (primero de la lista)
                    $firstRole = $user->roles->first();

                    // Para el sidebar: descripción legible
                    $sidebarRole = $firstRole?->descripcion
                        ?? $firstRole?->name
                        ?? null;

                    // Por si necesitas el name técnico (ej. para clases CSS)
                    $sidebarRoleName = $firstRole?->name;

                    // Menú filtrado por permisos del usuario
                    $sidebarMenu = MenuBuilder::forUser($user);
                }

                $view->with([
                    'sidebarUser'     => $user,
                    'sidebarRole'     => $sidebarRole,       // descripción legible
                    'sidebarRoleName' => $sidebarRoleName,   // 'admin', 'docente', etc.
                    'sidebarMenu'     => $sidebarMenu,
                ]);
            }
        );
    }
}
