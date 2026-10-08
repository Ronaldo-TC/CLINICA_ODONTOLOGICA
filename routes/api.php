<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\OdontogramaController;
use App\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'loginV1'])->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::controller(AuthController::class)->group(function (): void {
            Route::get('/me', 'meV1')->name('me');
            Route::post('/logout', 'logoutV1')->name('logout');
            Route::post('/logout-all', 'logoutAllV1')->name('logout-all');
        });

        Route::get('/dashboard', [DashboardController::class, 'datosApi'])->name('dashboard');

        Route::prefix('pacientes')->name('pacientes.')->controller(PacienteController::class)
            ->middleware('permission:pacientes.ver')->group(function (): void {
                Route::get('/', 'indexApi')->name('index');
                Route::get('/{id}', 'showApi')->name('show');
            });

        Route::prefix('historias')->name('historias.')->controller(HistoriaController::class)->group(function (): void {
            Route::middleware('permission:historias.ver')->group(function (): void {
                Route::get('/', 'indexApi')->name('index');
                Route::get('/{id}', 'showApi')->name('show');
            });
            Route::post('/', 'actualizarAvanceApi')->middleware('permission:historias.editar')->name('store');
        });

        Route::controller(InsumoController::class)->group(function (): void {
            Route::get('/insumos', 'catalogoApi')->middleware('permission:insumos.ver')->name('insumos.index');
            Route::prefix('solicitudes-insumos')->name('solicitudes.')->group(function (): void {
                Route::get('/', 'solicitudesApi')->middleware('permission:insumos.ver')->name('index');
                Route::post('/', 'crearSolicitudApi')->middleware('permission:insumos.crear')->name('store');
            });
        });

        Route::prefix('notificaciones')->name('notificaciones.')->controller(NotificacionController::class)->group(function (): void {
            Route::get('/', 'indexApi')->name('index');
            Route::post('/{id}/leer', 'leerApi')->name('leer');
        });

        Route::controller(OdontogramaController::class)->group(function (): void {
            Route::middleware('permission:historias.ver')->group(function (): void {
                Route::get('/odontogramas/{odontograma}/threejs', 'threejs')->name('odontogramas.threejs');
                Route::get('/pacientes/{paciente}/odontograma-actual', 'actualPorPaciente')->name('odontogramas.actual');
                Route::get('/pacientes/{paciente}/odontogramas', 'indexPorPaciente')->name('odontogramas.index');
            });
            Route::post('/odontogramas/{odontograma}/hallazgos', 'guardarHallazgos')
                ->middleware('permission:historias.editar|historias.gestionar')->name('odontogramas.hallazgos.guardar');
        });
    });
});

Route::controller(AuthController::class)->group(function (): void {
    Route::post('/login', 'login');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
    });
});
