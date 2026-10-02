<?php

/*
|--------------------------------------------------------------------------
| API REST v1 (app móvil) — Laravel Sanctum
|--------------------------------------------------------------------------
| Prefijo real: /api/v1  (Laravel añade /api; aquí se versiona con /v1)
|
| Autenticación:
|   POST /api/v1/login  { nombre_usuario, contrasena, device_name }
|   → { access_token, token_type: "Bearer" }
|   Enviar el token en la cabecera: Authorization: Bearer <token>
|
| Todos los endpoints protegidos validan además el permiso
| `modulo.accion` del usuario (Spatie).
*/

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    /* ---------- Público ---------- */
    Route::post('/login', [ApiController::class, 'login'])->name('login');

    /* ---------- Protegido con Sanctum ---------- */
    Route::middleware('auth:sanctum')->group(function () {

        /* Sesión */
        Route::get('/me', [ApiController::class, 'me'])->name('me');
        Route::post('/logout', [ApiController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [ApiController::class, 'logoutAll'])->name('logout-all');

        /* Panel */
        Route::get('/dashboard', [ApiController::class, 'dashboard'])->name('dashboard');

        /* Pacientes */
        Route::get('/pacientes', [ApiController::class, 'pacientes'])
            ->middleware('permission:pacientes.ver')->name('pacientes.index');
        Route::get('/pacientes/{id}', [ApiController::class, 'paciente'])
            ->middleware('permission:pacientes.ver')->name('pacientes.show');

        /* Historias clínicas */
        Route::get('/historias', [ApiController::class, 'historias'])
            ->middleware('permission:historias.ver')->name('historias.index');
        Route::get('/historias/{id}', [ApiController::class, 'historia'])
            ->middleware('permission:historias.ver')->name('historias.show');
        Route::post('/historias', [ApiController::class, 'crearHistoria'])
            ->middleware('permission:historias.editar')->name('historias.store');

        /* Insumos */
        Route::get('/insumos', [ApiController::class, 'insumos'])
            ->middleware('permission:insumos.ver')->name('insumos.index');

        /* Solicitudes de insumos */
        Route::get('/solicitudes-insumos', [ApiController::class, 'solicitudes'])
            ->middleware('permission:insumos.ver')->name('solicitudes.index');
        Route::post('/solicitudes-insumos', [ApiController::class, 'crearSolicitud'])
            ->middleware('permission:insumos.crear')->name('solicitudes.store');

        /* Notificaciones */
        Route::get('/notificaciones', [ApiController::class, 'notificaciones'])->name('notificaciones.index');
        Route::post('/notificaciones/{id}/leer', [ApiController::class, 'leerNotificacion'])->name('notificaciones.leer');
    });
});

/* ---------- Compatibilidad con las rutas previas (sin versión) ---------- */
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
