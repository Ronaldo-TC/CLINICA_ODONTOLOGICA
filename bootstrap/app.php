<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
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
        $middleware->alias([
            'auth.session' => \App\Http\Middleware\EnsureUserSession::class, // tu web sigue igual
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
