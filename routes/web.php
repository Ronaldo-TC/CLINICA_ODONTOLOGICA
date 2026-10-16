<?php

use App\Http\Controllers\AdmisionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\EsterilizacionController;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\InvestigacionController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\OdontogramaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\RadiologiaController;
use App\Http\Controllers\RemisionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SolicitudRadiologicaController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/* ============================================================
   AUTENTICACIÓN
   ============================================================ */

Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('auth.login.post');
Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('auth.logout');

Route::get('/odontograma/imagenes/{denticion}/{pieza}/{vista}', [OdontogramaController::class, 'imagen'])
    ->where('pieza', '[1-8][1-8]')->name('odontograma.imagen');

/* ============================================================
   RUTAS PROTEGIDAS
   ============================================================ */
Route::middleware(['auth.session'])->group(function () {
    Route::get('/mis-inscripciones', [EspecialidadController::class, 'misInscripciones'])
        ->middleware('auth.session')
        ->name('especialidades.mis-inscripciones');

    Route::prefix('especialidades')->name('especialidades.')->controller(EspecialidadController::class)->group(function () {

        Route::middleware('permission:especialidades.ver|especialidades.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/buscar-usuarios', 'buscarUsuarios')->name('buscar-usuarios');
            Route::get('/{usuario}/{clinica}/datos', 'datos')->name('datos');
        });

        Route::middleware('permission:especialidades.crear|especialidades.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:especialidades.editar|especialidades.gestionar')->group(function () {
            // ⚠️ Estáticas PRIMERO
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');

            // Dinámicas después
            Route::put('/{usuario}/{clinica}', 'update')
                ->whereNumber('usuario')
                ->whereNumber('clinica')
                ->name('update');
        });

        Route::middleware('permission:especialidades.eliminar|especialidades.gestionar')->group(function () {
            Route::delete('/{usuario}/{clinica}', 'destroy')
                ->whereNumber('usuario')
                ->whereNumber('clinica')
                ->name('destroy');
        });
    });

    /* ---- Dashboard ---- */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    /* ---- Perfil y configuración ---- */
    Route::get('/perfil', [AuthController::class, 'perfil'])->name('auth.profile');
    Route::get('/configuracion', [AuthController::class, 'configuracion'])->name('auth.settings');
    Route::put('/perfil', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
    Route::delete('/perfil/avatar', [AuthController::class, 'deleteAvatar'])->name('auth.profile.avatar.delete');
    Route::put('/configuracion/password', [AuthController::class, 'changePassword'])->name('auth.settings.password');
    Route::post('/configuracion/sesiones/cerrar-otras', [AuthController::class, 'closeOtherSessions'])->name('auth.settings.sessions.close-others');
    Route::delete('/configuracion/sesiones/{sesion}/cerrar', [AuthController::class, 'closeSession'])->name('auth.settings.sessions.close');

    /* ---- Notificaciones internas (bandeja + campana del topbar) ---- */
    Route::prefix('notificaciones')->name('notificaciones.')->controller(NotificacionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/ultimas', 'ultimas')->name('ultimas');
        Route::post('/leer-todas', 'leerTodas')->name('leer-todas');
        Route::post('/{id}/leer', 'leer')->name('leer');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });
    Route::middleware('permission:historias.ver|historias.gestionar')->group(function () {
        Route::get('/odontogramas/{odontograma}/threejs', [OdontogramaController::class, 'threejs'])
            ->name('odontogramas.threejs');

        Route::get('/odontogramas/{odontograma}', [OdontogramaController::class, 'show'])
            ->name('odontogramas.show');
    });

    Route::post('/odontogramas/{odontograma}/hallazgos', [OdontogramaController::class, 'guardarHallazgos'])
        ->middleware('permission:historias.editar|historias.gestionar')->name('odontogramas.hallazgos.guardar');

    /* ============================================================
       PACIENTES
       ============================================================ */
    Route::prefix('pacientes')->name('pacientes.')->controller(PacienteController::class)->group(function () {

        Route::middleware('permission:pacientes.ver|pacientes.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{paciente}/datos', 'datos')->name('datos');
            Route::get('/{paciente}', 'show')->name('show');
        });

        Route::middleware('permission:pacientes.crear|pacientes.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:pacientes.editar|pacientes.gestionar')->group(function () {
            Route::put('/{paciente}', 'update')->name('update');
        });

        Route::middleware('permission:pacientes.eliminar|pacientes.gestionar')->group(function () {
            Route::delete('/{paciente}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       ADMISIONES
       ============================================================ */
    /* ============================================================
   ADMISIONES
   ============================================================ */
    Route::prefix('estudiante')->name('estudiante.')->middleware(['role:estudiante|interno', 'permission:admisiones.ver|admisiones.gestionar'])->controller(AdmisionController::class)->group(function (): void {
        Route::get('/pacientes-admitidos', 'admitidosEstudiante')->name('admitidos');
    });

    Route::prefix('admisiones')->name('admisiones.')->controller(AdmisionController::class)->group(function () {
        Route::get('/crear', 'create')->middleware('permission:admisiones.crear|admisiones.gestionar')->name('create');
        Route::get('/crear-3d', 'create')->middleware('permission:admisiones.crear|admisiones.gestionar')->name('create-3d');
        Route::get('/{admision}/editar', 'edit')->middleware('permission:admisiones.editar|admisiones.gestionar')->name('edit');
        Route::post('/{admision}/autorizar', 'autorizar')->middleware('permission:admisiones.autorizar|admisiones.gestionar')->name('autorizar');
        Route::get('/usuarios/{usuario}/especialidades', 'especialidadesUsuario')->middleware('permission:admisiones.ver|admisiones.gestionar')->name('especialidades-usuario');

        Route::middleware('permission:admisiones.ver|admisiones.gestionar')->group(function () {
            //  Rutas estáticas PRIMERO
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/{admision}/reporte-paciente', 'reportePaciente')->whereNumber('admision')->name('reporte-paciente');
            Route::get('/{admision}/ficha', 'fichaPdf')->whereNumber('admision')->name('ficha-pdf');
            Route::get('/tabla', 'tabla')->name('tabla');

            //  Rutas de búsqueda AJAX (también estáticas)
            Route::get('/buscar-pacientes', 'buscarPacientes')->name('buscar-pacientes');
            Route::get('/buscar-pacientes/{paciente}/datos', 'datosPaciente')->name('buscar-pacientes.datos');
            Route::get('/buscar-usuarios', 'buscarUsuarios')->name('buscar-usuarios');
            Route::get('/datos-iniciales', 'datosIniciales')->name('datos-iniciales');

            //  Rutas con {admision} AL FINAL
            Route::get('/{admision}/datos', 'datos')->name('datos');
            Route::get('/{admision}', 'show')->name('show');
        });

        Route::middleware('permission:admisiones.crear|admisiones.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:admisiones.editar|admisiones.gestionar')->group(function () {
            Route::put('/{admision}', 'update')->name('update');
        });

        Route::middleware('permission:admisiones.eliminar|admisiones.gestionar')->group(function () {
            Route::delete('/{admision}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       HISTORIAS CLÍNICAS
       ============================================================ */
    Route::prefix('estudiante')->name('estudiante.')->middleware(['role:estudiante|interno', 'permission:historias.ver|historias.gestionar'])->controller(HistoriaController::class)->group(function (): void {
        Route::get('/pacientes-designados', 'designados')->name('designados');
        Route::get('/historias', 'historiasEstudiante')->name('historias');
        Route::get('/historias/{historia}', 'historiaEstudiante')->name('historia');
    });

    Route::prefix('historias')->name('historias.')->controller(HistoriaController::class)->group(function () {
        Route::post('/{historia}/autorizar', 'autorizar')->middleware('permission:historias.autorizar|historias.gestionar')->name('autorizar');

        Route::middleware('permission:historias.ver|historias.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');

            Route::get('/buscar-admisiones', 'buscarAdmisiones')->name('buscar-admisiones');
            Route::get('/buscar-usuarios', 'buscarUsuarios')->name('buscar-usuarios');
            Route::get('/datos-iniciales', 'datosIniciales')->name('datos-iniciales');

            Route::get('/{historia}/datos', 'datos')->name('datos');
            Route::get('/{historia}/seguimiento/{seccion}', 'seguimiento')->name('seguimiento');

            /* ---- Archivos adjuntos ---- */
            Route::get('/{id}/archivos', 'archivos')->name('archivos');
            Route::get('/{id}/archivos/{archivo}', 'descargarArchivo')->name('archivos.descargar');
            Route::get('/{historia}', 'show')->name('show');
        });

        Route::middleware('permission:historias.crear|historias.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:historias.editar|historias.gestionar')->group(function () {
            Route::put('/{historia}', 'update')->name('update');

            /* ---- Archivos adjuntos (escritura) ---- */
            Route::post('/{id}/archivos', 'subirArchivo')->name('archivos.subir');
            Route::delete('/{id}/archivos/{archivo}', 'eliminarArchivo')->name('archivos.eliminar');
        });

        Route::middleware('permission:historias.eliminar|historias.gestionar')->group(function () {
            Route::delete('/{historia}', 'destroy')->name('destroy');
        });
    });

    Route::post('/historias/{historia}/solicitudes-radiologicas', [SolicitudRadiologicaController::class, 'store'])
        ->middleware('permission:radiologias.crear|radiologias.gestionar')
        ->name('historias.solicitudes-radiologicas.store');
    Route::post('/solicitudes-radiologicas/{solicitud}/revisar', [SolicitudRadiologicaController::class, 'revisar'])
        ->middleware('permission:radiologias.revisar|radiologias.gestionar')
        ->name('solicitudes-radiologicas.revisar');
    Route::post('/solicitudes-radiologicas/{solicitud}/reenviar', [SolicitudRadiologicaController::class, 'reenviar'])
        ->middleware('permission:radiologias.crear|radiologias.gestionar')
        ->name('solicitudes-radiologicas.reenviar');
    Route::post('/cajas/solicitudes-radiologicas/{solicitud}/pago', [SolicitudRadiologicaController::class, 'pagar'])
        ->middleware('permission:cajas.crear|cajas.gestionar')
        ->name('cajas.solicitudes-radiologicas.pago');
    Route::post('/radiologia/solicitudes/{solicitud}/realizar', [SolicitudRadiologicaController::class, 'realizar'])
        ->middleware('permission:radiologias.gestionar')
        ->name('radiologia.solicitudes.realizar');
    /* ============================================================
       TRATAMIENTOS
       ============================================================ */
    Route::prefix('tratamientos-realizados')->name('tratamientos-realizados.')->controller(TratamientoController::class)->group(function (): void {
        Route::get('/', 'realizados')->middleware('permission:atenciones.ver|atenciones.gestionar')->name('index');
        Route::get('/tabla', 'tablaRealizados')->middleware('permission:atenciones.ver|atenciones.gestionar')->name('tabla');
        Route::get('/reporte', 'reporteRealizados')->middleware('permission:atenciones.ver|atenciones.gestionar')->name('reporte');
        Route::post('/', 'proponer')->middleware('permission:atenciones.crear|atenciones.gestionar')->name('store');
        Route::put('/{tratamiento}', 'actualizarPropuesta')->middleware('permission:atenciones.editar|atenciones.gestionar')->name('update');
        Route::post('/{tratamiento}/autorizar', 'autorizarRealizado')->middleware('permission:atenciones.autorizar|atenciones.gestionar')->name('autorizar');
        Route::post('/{tratamiento}/rechazar', 'rechazarRealizado')->middleware('permission:atenciones.autorizar|atenciones.gestionar')->name('rechazar');
        Route::delete('/{tratamiento}', 'eliminarPropuesta')->middleware('permission:atenciones.editar|atenciones.gestionar')->name('destroy');
        Route::post('/{tratamiento}/finalizar', 'finalizar')->middleware('permission:atenciones.editar|atenciones.gestionar')->name('finalizar');
        Route::post('/{tratamiento}/culminar', 'culminar')->middleware('permission:atenciones.autorizar|atenciones.gestionar')->name('culminar');
    });

    Route::post('/tratamientos-realizados/{tratamiento}/sesiones', [TratamientoController::class, 'registrarSesion'])->middleware('permission:historias.editar|historias.gestionar')->name('sesiones-atencion.store');
    Route::post('/sesiones-atencion/{sesion}/autorizar', [TratamientoController::class, 'autorizarSesion'])->middleware('permission:historias.autorizar|historias.gestionar')->name('sesiones-atencion.autorizar');

    Route::prefix('tratamientos')->name('tratamientos.')->controller(TratamientoController::class)->group(function () {

        Route::middleware('permission:tratamientos.ver|tratamientos.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{tratamiento}/datos', 'datos')->name('datos');
            Route::get('/{tratamiento}', 'show')->name('show');
        });

        Route::middleware('permission:tratamientos.crear|tratamientos.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:tratamientos.editar|tratamientos.gestionar')->group(function () {
            Route::put('/{tratamiento}', 'update')->name('update');
        });

        Route::put('/{tratamiento}/precio', 'actualizarPrecio')
            ->middleware('permission:tratamientos.precios')->name('precio');
        Route::put('/{tratamiento}/insumos', 'actualizarInsumos')
            ->middleware('permission:tratamientos.insumos')->name('insumos');

        Route::middleware('permission:tratamientos.eliminar|tratamientos.gestionar')->group(function () {
            Route::delete('/{tratamiento}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       REMISIONES
       ============================================================ */
    Route::prefix('pacientes-remitidos')->name('pacientes-remitidos.')->middleware('role:admin|director|docente|jefe-catedra')->controller(RemisionController::class)->group(function (): void {
        // ---- Estáticas primero ----
        Route::get('/', 'remitidos')
            ->middleware('permission:remisiones.ver|remisiones.gestionar')
            ->name('index');

        Route::get('/buscar', 'buscarRemitidos')
            ->middleware('permission:remisiones.ver|remisiones.gestionar')
            ->name('buscar');

        // ---- Dinámicas después ----
        Route::get('/{remision}/asignar', 'asignacion')
            ->middleware('permission:remisiones.asignar|remisiones.gestionar')
            ->name('asignacion');

        Route::post('/{remision}/asignar', 'asignar')
            ->middleware('permission:remisiones.asignar|remisiones.gestionar')
            ->name('asignar');
    });

    Route::prefix('estudiante')->name('estudiante.')->middleware(['role:estudiante|interno', 'permission:historias.ver|historias.gestionar'])->controller(RemisionController::class)->group(function (): void {
        Route::get('/pacientes-derivados', 'derivadosEstudiante')->name('derivados');
    });

    Route::prefix('remisiones')->name('remisiones.')->middleware('role:admin|director|admision|jefatura-clinicas')->controller(RemisionController::class)->group(function () {
        Route::get('/{remision}/asignar', 'asignacion')->middleware('permission:remisiones.asignar|remisiones.gestionar')->name('asignacion');
        Route::post('/{remision}/asignar', 'asignar')->middleware('permission:remisiones.asignar|remisiones.gestionar')->name('asignar');

        Route::middleware('permission:remisiones.ver|remisiones.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');

            Route::get('/buscar-admisiones', 'buscarAdmisiones')->name('buscar-admisiones');
            Route::get('/datos-iniciales', 'datosIniciales')->name('datos-iniciales');

            Route::get('/{remision}/datos', 'datos')->name('datos');
            Route::get('/{remision}', 'show')->name('show');
        });

        Route::middleware('permission:remisiones.crear|remisiones.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:remisiones.editar|remisiones.gestionar')->group(function () {
            Route::put('/{remision}', 'update')->name('update');
        });

        Route::middleware('permission:remisiones.eliminar|remisiones.gestionar')->group(function () {
            Route::delete('/{remision}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       INSUMOS
       ============================================================ */
    /* ============================================================
   INSUMOS — Catálogo
   ============================================================ */
    Route::prefix('insumos')->name('insumos.')->controller(InsumoController::class)->group(function () {

        Route::middleware('permission:insumos.ver|insumos.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');

            Route::get('/tipos-por-clinica/{clinica}', 'tiposPorClinica')->name('tipos-por-clinica');
            Route::get('/tratamientos-por-clinica/{clinica}', 'tratamientosPorClinica')->name('tratamientos-por-clinica');
            Route::get('/buscar-insumos/{clinica}', 'buscarInsumos')->name('buscar-insumos');

            Route::get('/{insumo}/datos', 'datos')->name('datos');
            Route::get('/{insumo}', 'show')->name('show');
        });

        Route::middleware('permission:insumos.crear|insumos.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:insumos.editar|insumos.gestionar')->group(function () {
            Route::put('/{insumo}', 'update')->name('update');
            Route::post('/{insumo}/ajustar-stock', 'ajustarStock')->name('ajustar-stock');
        });

        Route::middleware('permission:insumos.eliminar|insumos.gestionar')->group(function () {
            Route::delete('/{insumo}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
   SOLICITUDES DE INSUMOS — Flujo de aprobación
   ============================================================ */
    Route::prefix('reposiciones-insumos')->name('insumos.')->middleware('role:insumos|jefatura-clinicas|director|admin')->controller(InsumoController::class)->group(function (): void {
        Route::get('/', 'reposiciones')->name('reposiciones');
        Route::get('/tabla', 'tablaReposiciones')->name('reposiciones-tabla');
        Route::post('/', 'crearReposicion')->name('reposiciones-crear');
        Route::post('/{solicitud}/revision', 'revisarReposicion')->name('reposiciones-revisar');
    });

    Route::prefix('solicitudes-insumos')->name('solicitudes-insumos.')->controller(InsumoController::class)->group(function () {

        Route::middleware('permission:solicitudes-insumos.ver|solicitudes-insumos.gestionar|insumos.gestionar')->group(function () {
            Route::get('/', 'solicitudes')->name('index');
            Route::get('/reporte', 'reporteSolicitudes')->name('reporte');
            Route::get('/tabla', 'tablaSolicitudes')->name('tabla');
            Route::get('/tipos-por-clinica/{clinica}', 'tiposPorClinica')->name('tipos-por-clinica');
            Route::get('/buscar-insumos/{clinica}', 'buscarInsumos')->name('buscar-insumos');
            Route::get('/buscar-tratamientos', 'buscarTratamientos')->name('buscar-tratamientos');
            Route::get('/buscar-sesiones', 'buscarSesiones')->name('buscar-sesiones');
            Route::get('/catalogo-designado', 'catalogoDesignado')->name('catalogo-designado');
            Route::get('/{id}/datos', 'datosSolicitud')->name('datos');
            Route::get('/{id}', 'verSolicitud')->name('show');
        });

        Route::middleware('permission:solicitudes-insumos.crear|solicitudes-insumos.gestionar|insumos.gestionar')->group(function () {
            Route::post('/', 'crearSolicitud')->name('store');
        });

        Route::middleware('permission:solicitudes-insumos.editar|solicitudes-insumos.gestionar|insumos.gestionar')->group(function () {
            Route::put('/{id}', 'actualizarSolicitud')->name('update');
        });

        Route::post('/{id}/autorizar', 'autorizarSolicitud')->middleware('permission:solicitudes-insumos.autorizar|solicitudes-insumos.gestionar|insumos.gestionar')->name('autorizar');
        Route::post('/{id}/aprobar', 'aprobarSolicitud')->middleware('permission:solicitudes-insumos.aprobar|solicitudes-insumos.gestionar|insumos.gestionar')->name('aprobar');
        Route::post('/{id}/rechazar', 'rechazarSolicitud')->middleware('permission:solicitudes-insumos.rechazar|solicitudes-insumos.gestionar|insumos.gestionar')->name('rechazar');
        Route::post('/{id}/consumir', 'consumirSolicitud')->middleware('permission:solicitudes-insumos.consumir|solicitudes-insumos.gestionar|insumos.gestionar')->name('consumir');
        Route::middleware('permission:solicitudes-insumos.entregar|solicitudes-insumos.gestionar|insumos.gestionar')->group(function () {
            Route::post('/{id}/entregar', 'entregarSolicitud')->name('entregar');
        });

        Route::middleware('permission:solicitudes-insumos.eliminar|solicitudes-insumos.gestionar|insumos.gestionar')->group(function () {
            Route::delete('/{id}', 'eliminarSolicitud')->name('destroy');
        });
    });
    /* ============================================================
       ESTERILIZACIÓN
       ============================================================ */
    Route::prefix('esterilizacion')->name('esterilizacion.')->controller(EsterilizacionController::class)->group(function () {
        Route::get('/{esterilizacion}/control', 'control')->middleware('permission:esterilizaciones.ver|esterilizaciones.gestionar')->name('control');
        Route::post('/{esterilizacion}/autorizar', 'autorizar')->middleware('permission:esterilizaciones.autorizar|esterilizaciones.gestionar')->name('autorizar');
        Route::post('/{esterilizacion}/movimiento', 'movimiento')->middleware('permission:esterilizaciones.gestionar')->name('movimiento');
        Route::post('/{esterilizacion}/preparar', 'preparar')->middleware('permission:esterilizaciones.gestionar')->name('preparar');
        Route::post('/{esterilizacion}/ciclos', 'registrarCiclo')->middleware('permission:esterilizaciones.gestionar')->name('ciclos.store');
        Route::post('/{esterilizacion}/ciclos/{ciclo}/revision', 'revisarCiclo')->middleware('permission:esterilizaciones.gestionar')->name('ciclos.revisar');
        Route::post('/{esterilizacion}/ciclos/{ciclo}/liberar', 'liberar')->middleware('permission:esterilizaciones.gestionar')->name('ciclos.liberar');

        Route::middleware('permission:esterilizaciones.ver|esterilizaciones.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/buscar-usuarios', 'buscarUsuarios')->name('buscar-usuarios');
            Route::get('/datos-iniciales', 'datosIniciales')->name('datos-iniciales');

            Route::get('/{esterilizacion}/datos', 'datos')->name('datos');
            Route::get('/{esterilizacion}', 'show')->name('show');
        });

        Route::middleware('permission:esterilizaciones.crear|esterilizaciones.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:esterilizaciones.editar|esterilizaciones.gestionar')->group(function () {
            Route::put('/{esterilizacion}', 'update')->name('update');
        });

        Route::middleware('permission:esterilizaciones.eliminar|esterilizaciones.gestionar')->group(function () {
            Route::delete('/{esterilizacion}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       RADIOLOGÍA
       ============================================================ */
    Route::prefix('radiologia')->name('radiologia.')->controller(RadiologiaController::class)->group(function () {
        Route::get('/{radiologia}/visor', 'visor')->middleware('permission:radiologias.ver|radiologias.gestionar')->name('visor');
        Route::get('/{radiologia}/archivos/{archivo}', 'archivo')->middleware('permission:radiologias.ver|radiologias.gestionar')->name('archivos.ver');
        Route::post('/{radiologia}/archivos', 'guardarArchivos')->middleware('permission:radiologias.editar|radiologias.gestionar')->name('archivos.store');

        Route::middleware('permission:radiologias.ver|radiologias.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/buscar-pacientes', 'buscarPacientes')->name('buscar-pacientes');
            Route::get('/buscar-cajas', 'buscarCajas')->name('buscar-cajas');
            Route::get('/datos-iniciales', 'datosIniciales')->name('datos-iniciales');

            Route::get('/{radiologia}/datos', 'datos')->name('datos');
            Route::get('/{radiologia}', 'show')->name('show');
        });

        Route::middleware('permission:radiologias.crear|radiologias.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:radiologias.editar|radiologias.gestionar')->group(function () {
            Route::put('/{radiologia}', 'update')->name('update');
        });

        Route::middleware('permission:radiologias.eliminar|radiologias.gestionar')->group(function () {
            Route::delete('/{radiologia}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       CAJAS
       ============================================================ */
    Route::prefix('cajas')->name('cajas.')->controller(CajaController::class)->group(function () {

        Route::middleware('permission:cajas.ver|cajas.crear|cajas.gestionar')->group(function () {
            Route::get('/buscar-ficha', 'buscarFicha')->name('buscar-ficha');
            Route::get('/buscar-pacientes', 'buscarPacientes')->name('buscar-pacientes');
        });

        Route::middleware('permission:cajas.ver|cajas.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/reporte', 'reporte')->name('reporte');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{caja}/datos', 'datos')->name('datos');
            Route::get('/{caja}', 'show')->name('show');
        });

        Route::middleware('permission:cajas.crear|cajas.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:cajas.editar|cajas.gestionar')->group(function () {
            Route::put('/{caja}', 'update')->name('update');
        });

        Route::middleware('permission:cajas.eliminar|cajas.gestionar')->group(function () {
            Route::delete('/{caja}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       REPORTES
       ============================================================ */
    Route::prefix('reportes')->name('reportes.')->controller(ReporteController::class)->group(function () {

        Route::middleware('permission:reportes.ver|reportes.gestionar')->group(function () {

            Route::get('/', 'index')->name('index');

            /* ---- Endpoints JSON de gráficos: {labels, valores, total} ---- */
            Route::get('/pacientes-por-periodo', 'pacientesPorPeriodo')->name('pacientes-por-periodo');
            Route::get('/pacientes-por-especialidad', 'pacientesPorEspecialidad')->name('pacientes-por-especialidad');
            Route::get('/pacientes-por-edad', 'pacientesPorEdad')->name('pacientes-por-edad');
            Route::get('/pacientes-por-sexo', 'pacientesPorSexo')->name('pacientes-por-sexo');
            Route::get('/motivos-consulta', 'motivosConsulta')->name('motivos-consulta');
            Route::get('/tratamientos-por-especialidad', 'tratamientosPorEspecialidad')->name('tratamientos-por-especialidad');
            Route::get('/atenciones-por-docente', 'atencionesPorDocente')->name('atenciones-por-docente');
            Route::get('/atenciones-por-estudiante', 'atencionesPorEstudiante')->name('atenciones-por-estudiante');
            Route::get('/insumos-utilizados', 'insumosUtilizados')->name('insumos-utilizados');
            Route::get('/radiografias-por-mes', 'radiografiasPorMes')->name('radiografias-por-mes');
            Route::get('/esterilizaciones-por-mes', 'esterilizacionesPorMes')->name('esterilizaciones-por-mes');
            Route::get('/pagos-por-tipo', 'pagosPorTipo')->name('pagos-por-tipo');
            Route::get('/ingresos-por-mes', 'ingresosPorMes')->name('ingresos-por-mes');

            /* ---- Exportaciones ----
               CSV:  GET /reportes/exportar/csv         (?reporte=<clave> para uno solo)
               PDF:  GET /reportes/exportar/imprimible  (vista con window.print())
            */
            Route::get('/exportar/csv', 'exportarCsv')->name('exportar.csv');
            Route::get('/exportar/pdf', 'exportarPdf')->name('exportar.pdf');
            Route::get('/exportar/imprimible', 'imprimible')->name('exportar.imprimible');
        });

        Route::middleware('permission:reportes.crear|reportes.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });
    });

    /* ============================================================
       KARDEX (solo lectura) — usa el permiso reportes.ver
       ============================================================ */
    Route::prefix('kardex')->name('kardex.')->controller(KardexController::class)->group(function () {
        Route::middleware('permission:reportes.ver|reportes.gestionar')->group(function () {
            // Las rutas literales van ANTES de /kardex/{id}/datos
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{id}/datos', 'datos')->name('datos');
        });
    });

    /* ============================================================
       INVESTIGACIÓN
       ============================================================ */
    Route::prefix('investigacion')->name('investigacion.')->controller(InvestigacionController::class)->group(function () {
        // Rutas literales ANTES de cualquier /investigacion/{id}
        Route::middleware('permission:investigacion.ver|investigacion.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/estadisticas', 'estadisticas')->name('estadisticas');
        });

        Route::middleware('permission:investigacion.ver|investigacion.gestionar')->group(function () {
            Route::get('/exportar', 'exportar')->name('exportar');
        });
    });

    /* ============================================================
       USUARIOS — Roles dinámicos (sin prefijo admin.)
       ============================================================ */
    Route::prefix('usuarios')->name('usuarios.')->controller(UsuarioController::class)->group(function () {

        Route::middleware('permission:usuarios.ver|usuarios.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{usuario}/datos', 'datos')->name('datos');
            Route::get('/{usuario}', 'show')->name('show');
        });

        Route::middleware('permission:usuarios.crear|usuarios.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:usuarios.editar|usuarios.gestionar')->group(function () {
            Route::put('/{usuario}', 'update')->name('update');
            Route::post('/{usuario}/habilitar', 'habilitar')->name('habilitar');
            Route::post('/{usuario}/cambiar-rol', 'cambiarRol')->name('cambiar-rol');
        });

        Route::middleware('permission:usuarios.eliminar|usuarios.gestionar')->group(function () {
            Route::delete('/{usuario}/eliminar', 'eliminarDefinitivamente')->name('eliminar-definitivamente');
            Route::delete('/{usuario}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       ROLES — Gestión dinámica de roles y permisos
       ============================================================ */
    Route::prefix('roles')->name('roles.')->controller(RolController::class)->group(function () {

        Route::middleware('permission:roles.ver|roles.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{rol}/datos', 'datos')->name('datos');
            Route::get('/{rol}', 'show')->name('show');
        });

        Route::middleware('permission:roles.crear|roles.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:roles.editar|roles.gestionar')->group(function () {
            Route::put('/{rol}', 'update')->name('update');
            Route::post('/{rol}/permisos', 'syncPermisos')->name('permisos');
        });

        Route::middleware('permission:roles.eliminar|roles.gestionar')->group(function () {
            Route::delete('/{rol}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       CLÍNICAS
       ============================================================ */
    Route::prefix('clinicas')->name('clinicas.')->controller(ClinicaController::class)->group(function () {

        Route::middleware('permission:clinicas.ver|clinicas.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{clinica}/datos', 'datos')->name('datos');
            Route::get('/{clinica}', 'show')->name('show');
        });

        Route::middleware('permission:clinicas.crear|clinicas.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:clinicas.editar|clinicas.gestionar')->group(function () {
            Route::put('/{clinica}', 'update')->name('update');
        });

        Route::middleware('permission:clinicas.eliminar|clinicas.gestionar')->group(function () {
            Route::delete('/{clinica}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       PERIODOS
       ============================================================ */
    Route::prefix('periodos')->name('periodos.')->controller(PeriodoController::class)->group(function () {

        Route::middleware('permission:periodos.ver|periodos.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{periodo}/datos', 'datos')->name('datos');
            Route::get('/{periodo}', 'show')->name('show');
        });

        Route::middleware('permission:periodos.crear|periodos.gestionar')->group(function () {
            Route::post('/', 'store')->name('store');
        });

        Route::middleware('permission:periodos.editar|periodos.gestionar')->group(function () {
            Route::put('/{periodo}', 'update')->name('update');
        });

        Route::middleware('permission:periodos.eliminar|periodos.gestionar')->group(function () {
            Route::delete('/{periodo}', 'destroy')->name('destroy');
        });
    });

    /* ============================================================
       BITÁCORAS
       ============================================================ */
    Route::prefix('bitacoras')->name('bitacoras.')->controller(BitacoraController::class)->group(function () {
        Route::middleware('permission:bitacoras.ver|bitacoras.gestionar')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tabla', 'tabla')->name('tabla');
            Route::get('/{bitacora}', 'show')->name('show');
        });
    });
});
