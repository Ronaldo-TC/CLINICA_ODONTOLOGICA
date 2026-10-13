<?php

use App\Http\Middleware\EnsureUserSession;
use App\Http\Middleware\RegistrarAcciones;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ->withMiddleware(function (Middleware $middleware): void {
    //     // Registro del alias 'role'
    //     $middleware->alias([
    //         'auth.session' => \App\Http\Middleware\EnsureUserSession::class,
    //         'role'         => \App\Http\Middleware\CheckRole::class,
    //         'permiso'      => \App\Http\Middleware\CheckPermission::class,
    //     ]);
    // })

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', RegistrarAcciones::class);
        $middleware->appendToGroup('api', RegistrarAcciones::class);
        $middleware->alias([
            'auth.session' => EnsureUserSession::class, // tu web sigue igual
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
