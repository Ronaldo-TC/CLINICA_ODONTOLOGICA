<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajasController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\EsterilizacionController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\InsumosController;
use App\Http\Controllers\InternoController;
use App\Http\Controllers\InvestigacionController;
use App\Http\Controllers\JefaturaClinicasController;
use App\Http\Controllers\JefeCatedraController;
use App\Http\Controllers\RadiologiaController;
use App\Http\Controllers\SubadminController;
use App\Http\Controllers\HistoriaClinicaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/



Route::get('/', fn() => redirect()->route('auth.login'))->name('home');

Route::controller(AuthController::class)->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/auth/login', 'showLogin')->name('auth.login');
        Route::post('/auth/login', 'login')
            ->middleware('throttle:5,1')
            ->name('auth.login.submit');
    });

    Route::post('/auth/logout', 'logout')
        ->middleware('auth.session')
        ->name('auth.logout');

    Route::middleware('auth.session')->group(function (): void {
        // ─────────── PERFIL ───────────
        Route::get('/auth/perfil', 'profile')->name('auth.profile');
        Route::put('/auth/perfil', 'updateProfile')->name('auth.profile.update');
        Route::delete('/auth/perfil/avatar', 'deleteAvatar')->name('auth.profile.avatar.delete');

        // ─────────── CONFIGURACIÓN ───────────
        Route::get('/auth/configuracion', 'settings')->name('auth.settings');
        Route::put('/auth/configuracion/contrasena', 'updatePassword')->name('auth.settings.password');

        // ─────────── SESIONES ───────────
        Route::post('/auth/configuracion/sesiones/cerrar-otras', 'closeOtherSessions')
            ->name('auth.settings.sessions.close-others');
        Route::delete('/auth/configuracion/sesiones/{session}', 'closeSession')
            ->name('auth.settings.sessions.close');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN — Acceso total
|--------------------------------------------------------------------------
| Endpoints /tabla (AJAX) van ANTES de /{id} para que Laravel no confunda
| "tabla" con un ID.
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {

        // ─────────── DASHBOARD ───────────
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [AdminController::class, 'statsDashboard'])->name('dashboard.stats');

        // ─────────── USUARIOS ───────────
        Route::prefix('usuarios')->name('usuarios.')->group(function (): void {
            Route::get('/', [AdminController::class, 'usuarios'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaUsuarios'])->name('tabla');
            Route::get('/crear', [AdminController::class, 'crearUsuario'])->name('crear');
            Route::post('/', [AdminController::class, 'guardarUsuario'])->name('guardar');
            Route::get('/{id}/editar', [AdminController::class, 'editarUsuario'])->name('editar');
            Route::get('/{id}/json', [AdminController::class, 'usuarioJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarUsuario'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarUsuario'])->name('eliminar');
        });

        // ─────────── PACIENTES ───────────
        Route::prefix('pacientes')->name('pacientes.')->group(function (): void {
            Route::get('/', [AdminController::class, 'pacientes'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaPacientes'])->name('tabla');
            Route::get('/crear', [AdminController::class, 'crearPaciente'])->name('crear');
            Route::post('/', [AdminController::class, 'guardarPaciente'])->name('guardar');
            Route::get('/{id}/editar', [AdminController::class, 'editarPaciente'])->name('editar');
            Route::get('/{id}/json', [AdminController::class, 'pacienteJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarPaciente'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarPaciente'])->name('eliminar');
        });

        // ─────────── CLÍNICAS ───────────
        Route::prefix('clinicas')->name('clinicas.')->group(function (): void {
            Route::get('/', [AdminController::class, 'clinicas'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaClinicas'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarClinica'])->name('guardar');
            Route::get('/{id}/json', [AdminController::class, 'clinicaJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarClinica'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarClinica'])->name('eliminar');
        });

        // ─────────── PERIODOS ───────────
        Route::prefix('periodos')->name('periodos.')->group(function (): void {
            Route::get('/', [AdminController::class, 'periodos'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaPeriodos'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarPeriodo'])->name('guardar');
            Route::get('/{id}/json', [AdminController::class, 'periodoJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarPeriodo'])->name('actualizar');
            Route::patch('/{id}/toggle', [AdminController::class, 'togglePeriodo'])->name('toggle');   //
        });

        // ─────────── ROLES Y PERMISOS ───────────
        Route::prefix('roles')->name('roles.')->group(function (): void {
            Route::get('/', [AdminController::class, 'roles'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaRoles'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarRol'])->name('guardar');
            Route::get('/{id}/json', [AdminController::class, 'rolJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarRol'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarRol'])->name('eliminar');
            Route::get('/{id}/permisos', [AdminController::class, 'permisosRol'])->name('permisos');
            Route::post('/{id}/permisos', [AdminController::class, 'guardarPermisosRol'])->name('permisos.guardar');
        });

        // ─────────── TRATAMIENTOS ───────────
        Route::prefix('tratamientos')->name('tratamientos.')->group(function (): void {
            Route::get('/', [AdminController::class, 'tratamientos'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaTratamientos'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarTratamiento'])->name('guardar');
            Route::get('/{id}/json', [AdminController::class, 'tratamientoJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarTratamiento'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarTratamiento'])->name('eliminar');
        });

        // ─────────── CAJAS ───────────
        Route::prefix('cajas')->name('cajas.')->group(function (): void {
            Route::get('/', [AdminController::class, 'cajas'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaCajas'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarCaja'])->name('guardar');
            Route::get('/buscar-pacientes', [AdminController::class, 'buscarPacientes'])->name('buscar.pacientes');
            Route::get('/{id}/json', [AdminController::class, 'cajaJson'])->name('json');
        });

        // ─────────── INSUMOS ───────────
        Route::prefix('insumos')->name('insumos.')->group(function (): void {
            Route::get('/', [AdminController::class, 'insumos'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaInsumos'])->name('tabla');
            Route::post('/', [AdminController::class, 'guardarInsumo'])->name('guardar');
            Route::get('/{id}/json', [AdminController::class, 'insumoJson'])->name('json');
            Route::put('/{id}', [AdminController::class, 'actualizarInsumo'])->name('actualizar');
            Route::delete('/{id}', [AdminController::class, 'eliminarInsumo'])->name('eliminar');

            // NUEVO: tipos de insumo por clínica
            Route::get(
                '/tratamientos-por-clinica/{clinicaId}',
                [AdminController::class, 'insumoTratamientosPorClinica']
            )->name('tratamientos.clinica');
        });

        // ─────────── HISTORIAS CLÍNICAS ───────────
        // ─────────── HISTORIAS CLÍNICAS ───────────
        Route::prefix('historias')->name('historias.')->group(function (): void {
            Route::get('/',                [HistoriaClinicaController::class, 'index'])->name('index');
            Route::get('/tabla',           [HistoriaClinicaController::class, 'tabla'])->name('tabla');

            // Importante: /paciente/{id}/historial va ANTES de /{id}/json
            Route::get(
                '/paciente/{pacienteId}/historial',
                [HistoriaClinicaController::class, 'historialPaciente']
            )->name('paciente.historial');

            Route::get('/{id}/json',       [HistoriaClinicaController::class, 'json'])->name('json');
            Route::post('/guardar',        [HistoriaClinicaController::class, 'guardar'])->name('guardar');
            Route::put('/{id}',            [HistoriaClinicaController::class, 'actualizar'])->name('actualizar');

            Route::post('/{id}/archivo',   [HistoriaClinicaController::class, 'subirArchivo'])->name('archivo.subir');
            Route::get('/{id}/archivo',    [HistoriaClinicaController::class, 'descargarArchivo'])->name('archivo.descargar');

            Route::post('/{id}/autorizar', [HistoriaClinicaController::class, 'autorizar'])->name('autorizar');
            Route::post('/{id}/revisar',   [HistoriaClinicaController::class, 'revisar'])->name('revisar');
            Route::post('/{id}/cerrar',    [HistoriaClinicaController::class, 'cerrar'])->name('cerrar');
            Route::post('/{id}/reasignar', [HistoriaClinicaController::class, 'reasignar'])->name('reasignar');
        });

        // ─────────── BITÁCORAS ───────────
        Route::prefix('bitacoras')->name('bitacoras.')->group(function (): void {
            Route::get('/', [AdminController::class, 'bitacoras'])->name('index');
            Route::get('/tabla', [AdminController::class, 'tablaBitacoras'])->name('tabla');
        });

        // ─────────── REPORTES ───────────
        Route::prefix('reportes')->name('reportes.')->group(function (): void {
            Route::get('/', [AdminController::class, 'reportes'])->name('index');
            Route::get('/pacientes-grupo-etario', [AdminController::class, 'reportePacientesGrupoEtario'])->name('pacientes.edad');
            Route::get('/afluencia-clinicas', [AdminController::class, 'reporteAfluenciaClinicas'])->name('afluencia');
            Route::get('/ingresos-periodo', [AdminController::class, 'reporteIngresosPeriodo'])->name('ingresos');
        });
    });

/*
|--------------------------------------------------------------------------
| SUBADMINISTRADOR
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('subadmin')
    ->name('subadmin.')
    ->group(function (): void {
        Route::get('/dashboard', [SubadminController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| DIRECTOR DE CARRERA
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| DIRECTOR DE CARRERA
|--------------------------------------------------------------------------
| Acceso de solo lectura a todos los módulos + reportes globales.
| No crea, no edita, no elimina. Solo consulta y exporta.
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('director')
    ->name('director.')
    ->group(function (): void {

        // ─────────── DASHBOARD ───────────
        Route::get('/dashboard', [DirectorController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DirectorController::class, 'statsDashboard'])->name('dashboard.stats');

        // ─────────── PACIENTES (solo lectura) ───────────
        Route::prefix('pacientes')->name('pacientes.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'pacientes'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaPacientes'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'pacienteJson'])->name('json');
        });

        // ─────────── CLÍNICAS (solo lectura) ───────────
        Route::prefix('clinicas')->name('clinicas.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'clinicas'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaClinicas'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'clinicaJson'])->name('json');
        });

        // ─────────── PERIODOS (solo lectura) ───────────
        Route::prefix('periodos')->name('periodos.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'periodos'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaPeriodos'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'periodoJson'])->name('json');
        });

        // ─────────── TRATAMIENTOS (solo lectura) ───────────
        Route::prefix('tratamientos')->name('tratamientos.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'tratamientos'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaTratamientos'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'tratamientoJson'])->name('json');
        });

        // ─────────── HISTORIAS CLÍNICAS (solo lectura) ───────────
        Route::prefix('historias')->name('historias.')->group(function (): void {
            Route::get('/',          [DirectorController::class, 'historias'])->name('index');
            Route::get('/tabla',     [DirectorController::class, 'tablaHistorias'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'historiaJson'])->name('json');
        });

        // ─────────── CAJAS (solo lectura) ───────────
        Route::prefix('cajas')->name('cajas.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'cajas'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaCajas'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'cajaJson'])->name('json');
        });

        // ─────────── INSUMOS (solo lectura) ───────────
        Route::prefix('insumos')->name('insumos.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'insumos'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaInsumos'])->name('tabla');
            Route::get('/{id}/json', [DirectorController::class, 'insumoJson'])->name('json');
        });

        // ─────────── ESTERILIZACIÓN (solo lectura) ───────────
        Route::prefix('esterilizaciones')->name('esterilizaciones.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'esterilizaciones'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaEsterilizaciones'])->name('tabla');
        });

        // ─────────── RADIOLOGÍA (solo lectura) ───────────
        Route::prefix('radiologias')->name('radiologias.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'radiologias'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaRadiologias'])->name('tabla');
        });

        // ─────────── BITÁCORAS (solo lectura) ───────────
        Route::prefix('bitacoras')->name('bitacoras.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'bitacoras'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaBitacoras'])->name('tabla');
        });

        // ─────────── REPORTES (todos los del PDF) ───────────
        Route::prefix('reportes')->name('reportes.')->group(function (): void {

            // Panel principal de reportes
            Route::get('/', [DirectorController::class, 'reportes'])->name('index');

            // Reportes obligatorios según PDF
            Route::get('/pacientes-periodo',      [DirectorController::class, 'reportePacientesPorPeriodo'])->name('pacientes.periodo');
            Route::get('/tratamientos-especialidad', [DirectorController::class, 'reporteTratamientosPorEspecialidad'])->name('tratamientos.especialidad');
            Route::get('/atenciones-docente',     [DirectorController::class, 'reporteAtencionesPorDocente'])->name('atenciones.docente');
            Route::get('/atenciones-estudiante',  [DirectorController::class, 'reporteAtencionesPorEstudiante'])->name('atenciones.estudiante');
            Route::get('/afluencia-clinicas',     [DirectorController::class, 'reporteAfluenciaClinicas'])->name('afluencia.clinicas');
            Route::get('/pacientes-grupo-etario', [DirectorController::class, 'reportePacientesGrupoEtario'])->name('pacientes.edad');
            Route::get('/pacientes-sexo',         [DirectorController::class, 'reportePacientesPorSexo'])->name('pacientes.sexo');
            Route::get('/motivos-consulta',       [DirectorController::class, 'reporteMotivosConsulta'])->name('motivos.consulta');
            Route::get('/cajas-periodo',          [DirectorController::class, 'reporteCajasPorPeriodo'])->name('cajas.periodo');
            Route::get('/insumos-utilizados',     [DirectorController::class, 'reporteInsumosUtilizados'])->name('insumos.utilizados');
            Route::get('/radiologia-afluencia',   [DirectorController::class, 'reporteRadiologiaAfluencia'])->name('radiologia.afluencia');
            Route::get('/esterilizacion-afluencia', [DirectorController::class, 'reporteEsterilizacionAfluencia'])->name('esterilizacion.afluencia');

            // Exportaciones
            Route::get('/exportar/pdf/{reporte}',   [DirectorController::class, 'exportarPdf'])->name('exportar.pdf');
            Route::get('/exportar/excel/{reporte}', [DirectorController::class, 'exportarExcel'])->name('exportar.excel');
        });

        // ─────────── INSTITUTO DE INVESTIGACIÓN (solo lectura anonimizada) ───────────
        Route::prefix('investigacion')->name('investigacion.')->group(function (): void {
            Route::get('/',        [DirectorController::class, 'investigacion'])->name('index');
            Route::get('/tabla',   [DirectorController::class, 'tablaInvestigacion'])->name('tabla');
            Route::get('/datos-anonimizados', [DirectorController::class, 'datosAnonimizados'])->name('datos.anonimizados');
        });
    });

/*
|--------------------------------------------------------------------------
| JEFATURA DE CLÍNICAS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('jefatura-clinicas')
    ->name('jefatura-clinicas.')
    ->group(function (): void {
        Route::get('/dashboard', [JefaturaClinicasController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| JEFE DE CÁTEDRA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('jefe-catedra')
    ->name('jefe-catedra.')
    ->group(function (): void {
        Route::get('/dashboard', [JefeCatedraController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| DOCENTE
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| DOCENTE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function (): void {

        // ─────────── DASHBOARD ───────────
        Route::get('/dashboard', [DocenteController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/stats', [DocenteController::class, 'dashboard'])->name('dashboard.stats');

        // ─────────── PACIENTES DERIVADOS ───────────
        Route::get('/pacientes-derivados', [DocenteController::class, 'pacientesDerivados'])->name('pacientes-derivados');
        Route::post('/designar-paciente', [DocenteController::class, 'designarPaciente'])->name('designar-paciente');

        // ─────────── HISTORIAS CLÍNICAS ───────────
        Route::get('/historias-clinicas', [DocenteController::class, 'historiasClinicas'])->name('historias-clinicas');
        Route::get('/historia/{id}', [DocenteController::class, 'verHistoria'])->name('ver-historia');
        Route::get('/historia/{id}/detalle', [DocenteController::class, 'getDetalleHistoria'])->name('historia.detalle');
        Route::post('/evaluar-estudiante/{id}', [DocenteController::class, 'evaluarEstudiante'])->name('evaluar-estudiante');

        // ─────────── ESTERILIZACIÓN ───────────
        Route::get('/esterilizaciones', [DocenteController::class, 'verEsterilizaciones'])->name('esterilizaciones');
        Route::get('/esterilizacion/estudiante/{estudianteId}', [DocenteController::class, 'verEsterilizacionEstudiante'])->name('esterilizacion.estudiante');
        Route::post('/autorizar-esterilizacion', [DocenteController::class, 'autorizarEsterilizacion'])->name('autorizar-esterilizacion');

        // ─────────── CAJAS ───────────
        Route::get('/cajas', [DocenteController::class, 'verCajas'])->name('cajas');
        Route::post('/autorizar-caja/{id}', [DocenteController::class, 'autorizarCaja'])->name('autorizar-caja');
        Route::get('/caja/resumen/{pacienteId}', [DocenteController::class, 'getResumenCajaPaciente'])->name('caja.resumen');

        // ─────────── INSUMOS ───────────
        Route::get('/insumos-solicitados', [DocenteController::class, 'verInsumosSolicitados'])->name('insumos-solicitados');
        Route::post('/autorizar-insumos/{id}', [DocenteController::class, 'autorizarInsumos'])->name('autorizar-insumos');
        Route::get('/insumos-disponibles/{clinicaId}', [DocenteController::class, 'getInsumosDisponibles'])->name('insumos.disponibles');

        // ─────────── TRATAMIENTOS / AVANCES ───────────
        Route::post('/registrar-avance', [DocenteController::class, 'registrarAvance'])->name('registrar-avance');
        Route::post('/finalizar-tratamiento/{id}', [DocenteController::class, 'finalizarTratamiento'])->name('finalizar-tratamiento');

        // ─────────── RADIOLOGÍAS ───────────
        Route::get('/radiologias', [DocenteController::class, 'verRadiologias'])->name('radiologias');

        // ─────────── REPORTES ───────────
        Route::get('/reportes', [DocenteController::class, 'reportes'])->name('reportes');

        // ─────────── AJAX AUXILIARES ───────────
        Route::get('/estudiantes/clinica/{clinicaId}', [DocenteController::class, 'getEstudiantesPorClinica'])->name('estudiantes.clinica');
        Route::get('/tratamientos/clinica/{clinicaId}', [DocenteController::class, 'getTratamientosPorClinica'])->name('tratamientos.clinica');
        Route::get('/buscar-pacientes', [DocenteController::class, 'buscarPacientes'])->name('buscar-pacientes');
        Route::get('/notificaciones', [DocenteController::class, 'getNotificaciones'])->name('notificaciones');
    });

/*
|--------------------------------------------------------------------------
| ESTUDIANTE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('estudiante')
    ->name('estudiante.')
    ->group(function (): void {
        Route::get('/dashboard', [EstudianteController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| INTERNO
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('interno')
    ->name('interno.')
    ->group(function (): void {
        Route::get('/dashboard', [InternoController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| ADMISIÓN (rol requerido por el PDF de la clínica)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('admision')
    ->name('admision.')
    ->group(function (): void {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        // Aquí irían las rutas propias del rol admisión:
        // Route::get('/pacientes/registrar', ...);
        // Route::get('/pacientes/remitir',   ...);
        // Route::get('/fichas',              ...);
    });

/*
|--------------------------------------------------------------------------
| CAJERO
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('cajas')
    ->name('cajas.')
    ->group(function (): void {
        Route::get('/dashboard', [CajasController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| ESTERILIZACIÓN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('esterilizacion')
    ->name('esterilizacion.')
    ->group(function (): void {
        Route::get('/dashboard', [EsterilizacionController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| INSUMOS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('insumos')
    ->name('insumos.')
    ->group(function (): void {
        Route::get('/dashboard', [InsumosController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| RADIOLOGÍA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('radiologia')
    ->name('radiologia.')
    ->group(function (): void {
        Route::get('/dashboard', [RadiologiaController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| INSTITUTO DE INVESTIGACIÓN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.session', 'role'])
    ->prefix('investigacion')
    ->name('investigacion.')
    ->group(function (): void {
        Route::get('/dashboard', [InvestigacionController::class, 'index'])->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| HEALTH CHECK
|--------------------------------------------------------------------------
*/
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
    'app' => config('app.name'),
]))->name('health');

/*
|--------------------------------------------------------------------------
| FALLBACK 404
|--------------------------------------------------------------------------
*/
Route::fallback(fn() => response()->view('errors.404', [], 404));
