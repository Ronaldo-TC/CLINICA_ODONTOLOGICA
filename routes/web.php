<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\AdmisionController;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\RemisionController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\EsterilizacionController;
use App\Http\Controllers\RadiologiaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\InvestigacionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\SolicitudInsumoController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\NotificacionController;

/* ============================================================
   AUTENTICACIÓN
   ============================================================ */

Route::get('/login',   [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login',  [AuthController::class, 'loginWeb'])->name('auth.login.post');
Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('auth.logout');

/* ============================================================
   RUTAS PROTEGIDAS
   ============================================================ */
Route::middleware(['auth.session'])->group(function () {

    /* ---- Dashboard ---- */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    /* ---- Perfil y configuración ---- */
    Route::get('/perfil',        [AuthController::class, 'perfil'])->name('auth.profile');
    Route::get('/configuracion', [AuthController::class, 'configuracion'])->name('auth.settings');

    /* ---- Notificaciones internas (bandeja + campana del topbar) ---- */
    Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
        Route::get('/',                   [NotificacionController::class, 'index'])->name('index');
        Route::get('/ultimas',            [NotificacionController::class, 'ultimas'])->name('ultimas');
        Route::post('/leer-todas',        [NotificacionController::class, 'leerTodas'])->name('leer-todas');
        Route::post('/{id}/leer',         [NotificacionController::class, 'leer'])->name('leer');
        Route::delete('/{id}',            [NotificacionController::class, 'destroy'])->name('destroy');
    });

    /* ============================================================
       PACIENTES
       ============================================================ */
    Route::prefix('pacientes')->name('pacientes.')->group(function () {

        Route::middleware('permission:pacientes.ver')->group(function () {
            Route::get('/',       [PacienteController::class, 'index'])->name('index');
            Route::get('/tabla',  [PacienteController::class, 'tabla'])->name('tabla');
            Route::get('/{paciente}/datos', [PacienteController::class, 'datos'])->name('datos');
            Route::get('/{paciente}', [PacienteController::class, 'show'])->name('show');
        });

        Route::middleware('permission:pacientes.crear')->group(function () {
            Route::post('/', [PacienteController::class, 'store'])->name('store');
        });

        Route::middleware('permission:pacientes.editar')->group(function () {
            Route::put('/{paciente}', [PacienteController::class, 'update'])->name('update');
            Route::post('/{paciente}/habilitar', [PacienteController::class, 'habilitar'])->name('habilitar');
        });

        Route::middleware('permission:pacientes.eliminar')->group(function () {
            Route::delete('/{paciente}', [PacienteController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       ADMISIONES
       ============================================================ */
    /* ============================================================
   ADMISIONES
   ============================================================ */
    Route::prefix('admisiones')->name('admisiones.')->group(function () {

        Route::middleware('permission:admisiones.ver')->group(function () {
            //  Rutas estáticas PRIMERO
            Route::get('/',                  [AdmisionController::class, 'index'])->name('index');
            Route::get('/tabla',             [AdmisionController::class, 'tabla'])->name('tabla');

            //  Rutas de búsqueda AJAX (también estáticas)
            Route::get('/buscar-pacientes',  [AdmisionController::class, 'buscarPacientes'])->name('buscar-pacientes');
            Route::get('/buscar-usuarios',   [AdmisionController::class, 'buscarUsuarios'])->name('buscar-usuarios');
            Route::get('/datos-iniciales',   [AdmisionController::class, 'datosIniciales'])->name('datos-iniciales');

            //  Rutas con {admision} AL FINAL
            Route::get('/{admision}/datos',  [AdmisionController::class, 'datos'])->name('datos');
            Route::get('/{admision}',        [AdmisionController::class, 'show'])->name('show');
        });

        Route::middleware('permission:admisiones.crear')->group(function () {
            Route::post('/', [AdmisionController::class, 'store'])->name('store');
        });

        Route::middleware('permission:admisiones.editar')->group(function () {
            Route::put('/{admision}', [AdmisionController::class, 'update'])->name('update');
        });

        Route::middleware('permission:admisiones.eliminar')->group(function () {
            Route::delete('/{admision}', [AdmisionController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       HISTORIAS CLÍNICAS
       ============================================================ */
    Route::prefix('historias')->name('historias.')->group(function () {

        Route::middleware('permission:historias.ver')->group(function () {
            Route::get('/',                  [HistoriaController::class, 'index'])->name('index');
            Route::get('/tabla',             [HistoriaController::class, 'tabla'])->name('tabla');

            Route::get('/buscar-admisiones', [HistoriaController::class, 'buscarAdmisiones'])->name('buscar-admisiones');
            Route::get('/buscar-usuarios',   [HistoriaController::class, 'buscarUsuarios'])->name('buscar-usuarios');
            Route::get('/datos-iniciales',   [HistoriaController::class, 'datosIniciales'])->name('datos-iniciales');

            Route::get('/{historia}/datos',  [HistoriaController::class, 'datos'])->name('datos');

            /* ---- Archivos adjuntos ---- */
            Route::get('/{id}/archivos',                    [HistoriaController::class, 'archivos'])->name('archivos');
            Route::get('/{id}/archivos/{archivo}',          [HistoriaController::class, 'descargarArchivo'])->name('archivos.descargar');
            Route::get('/{historia}',        [HistoriaController::class, 'show'])->name('show');
        });

        Route::middleware('permission:historias.crear')->group(function () {
            Route::post('/', [HistoriaController::class, 'store'])->name('store');
        });

        Route::middleware('permission:historias.editar')->group(function () {
            Route::put('/{historia}', [HistoriaController::class, 'update'])->name('update');

            /* ---- Archivos adjuntos (escritura) ---- */
            Route::post('/{id}/archivos',            [HistoriaController::class, 'subirArchivo'])->name('archivos.subir');
            Route::delete('/{id}/archivos/{archivo}', [HistoriaController::class, 'eliminarArchivo'])->name('archivos.eliminar');
        });

        Route::middleware('permission:historias.eliminar')->group(function () {
            Route::delete('/{historia}', [HistoriaController::class, 'destroy'])->name('destroy');
        });
    });
    /* ============================================================
       TRATAMIENTOS
       ============================================================ */
    Route::prefix('tratamientos')->name('tratamientos.')->group(function () {

        Route::middleware('permission:tratamientos.ver')->group(function () {
            Route::get('/',      [TratamientoController::class, 'index'])->name('index');
            Route::get('/tabla', [TratamientoController::class, 'tabla'])->name('tabla');
            Route::get('/{tratamiento}/datos', [TratamientoController::class, 'datos'])->name('datos');
            Route::get('/{tratamiento}', [TratamientoController::class, 'show'])->name('show');
        });

        Route::middleware('permission:tratamientos.crear')->group(function () {
            Route::post('/', [TratamientoController::class, 'store'])->name('store');
        });

        Route::middleware('permission:tratamientos.editar')->group(function () {
            Route::put('/{tratamiento}', [TratamientoController::class, 'update'])->name('update');
        });

        Route::middleware('permission:tratamientos.eliminar')->group(function () {
            Route::delete('/{tratamiento}', [TratamientoController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       REMISIONES
       ============================================================ */
    Route::prefix('remisiones')->name('remisiones.')->group(function () {

        Route::middleware('permission:remisiones.ver')->group(function () {
            Route::get('/',                  [RemisionController::class, 'index'])->name('index');
            Route::get('/tabla',             [RemisionController::class, 'tabla'])->name('tabla');

            Route::get('/buscar-admisiones', [RemisionController::class, 'buscarAdmisiones'])->name('buscar-admisiones');
            Route::get('/datos-iniciales',   [RemisionController::class, 'datosIniciales'])->name('datos-iniciales');

            Route::get('/{remision}/datos',  [RemisionController::class, 'datos'])->name('datos');
            Route::get('/{remision}',        [RemisionController::class, 'show'])->name('show');
        });

        Route::middleware('permission:remisiones.crear')->group(function () {
            Route::post('/', [RemisionController::class, 'store'])->name('store');
        });

        Route::middleware('permission:remisiones.editar')->group(function () {
            Route::put('/{remision}', [RemisionController::class, 'update'])->name('update');
        });

        Route::middleware('permission:remisiones.eliminar')->group(function () {
            Route::delete('/{remision}', [RemisionController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       INSUMOS
       ============================================================ */
    /* ============================================================
   INSUMOS — Catálogo
   ============================================================ */
    Route::prefix('insumos')->name('insumos.')->group(function () {

        Route::middleware('permission:insumos.ver')->group(function () {
            Route::get('/',              [InsumoController::class, 'index'])->name('index');
            Route::get('/tabla',         [InsumoController::class, 'tabla'])->name('tabla');

            Route::get('/tipos-por-clinica/{clinica}', [InsumoController::class, 'tiposPorClinica'])->name('tipos-por-clinica');
            Route::get('/buscar-insumos/{clinica}',    [InsumoController::class, 'buscarInsumos'])->name('buscar-insumos');

            Route::get('/{insumo}/datos', [InsumoController::class, 'datos'])->name('datos');
            Route::get('/{insumo}',       [InsumoController::class, 'show'])->name('show');
        });

        Route::middleware('permission:insumos.crear')->group(function () {
            Route::post('/', [InsumoController::class, 'store'])->name('store');
        });

        Route::middleware('permission:insumos.editar')->group(function () {
            Route::put('/{insumo}', [InsumoController::class, 'update'])->name('update');
            Route::post('/{insumo}/ajustar-stock', [InsumoController::class, 'ajustarStock'])->name('ajustar-stock');
        });

        Route::middleware('permission:insumos.eliminar')->group(function () {
            Route::delete('/{insumo}', [InsumoController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
   SOLICITUDES DE INSUMOS — Flujo de aprobación
   ============================================================ */
    Route::prefix('solicitudes-insumos')->name('solicitudes-insumos.')->group(function () {

        Route::middleware('permission:insumos.ver')->group(function () {
            Route::get('/',                 [SolicitudInsumoController::class, 'index'])->name('index');
            Route::get('/tabla',            [SolicitudInsumoController::class, 'tabla'])->name('tabla');
            Route::get('/buscar-tratamientos', [SolicitudInsumoController::class, 'buscarTratamientos'])->name('buscar-tratamientos');
            Route::get('/{id}/datos',       [SolicitudInsumoController::class, 'datos'])->name('datos');
            Route::get('/{id}',             [SolicitudInsumoController::class, 'show'])->name('show');
        });

        Route::middleware('permission:insumos.crear')->group(function () {
            Route::post('/', [SolicitudInsumoController::class, 'store'])->name('store');
        });

        Route::middleware('permission:insumos.editar')->group(function () {
            Route::put('/{id}',                   [SolicitudInsumoController::class, 'update'])->name('update');
            Route::post('/{id}/autorizar',        [SolicitudInsumoController::class, 'autorizar'])->name('autorizar');
            Route::post('/{id}/rechazar',         [SolicitudInsumoController::class, 'rechazar'])->name('rechazar');
        });

        Route::middleware('permission:insumos.gestionar')->group(function () {
            Route::post('/{id}/entregar', [SolicitudInsumoController::class, 'entregar'])->name('entregar');
        });

        Route::middleware('permission:insumos.eliminar')->group(function () {
            Route::delete('/{id}', [SolicitudInsumoController::class, 'destroy'])->name('destroy');
        });
    });
    /* ============================================================
       ESTERILIZACIÓN
       ============================================================ */
    Route::prefix('esterilizacion')->name('esterilizacion.')->group(function () {

        Route::middleware('permission:esterilizaciones.ver')->group(function () {
            Route::get('/',                 [EsterilizacionController::class, 'index'])->name('index');
            Route::get('/tabla',            [EsterilizacionController::class, 'tabla'])->name('tabla');
            Route::get('/buscar-usuarios',  [EsterilizacionController::class, 'buscarUsuarios'])->name('buscar-usuarios');
            Route::get('/datos-iniciales',  [EsterilizacionController::class, 'datosIniciales'])->name('datos-iniciales');

            Route::get('/{esterilizacion}/datos', [EsterilizacionController::class, 'datos'])->name('datos');
            Route::get('/{esterilizacion}',       [EsterilizacionController::class, 'show'])->name('show');
        });

        Route::middleware('permission:esterilizaciones.crear')->group(function () {
            Route::post('/', [EsterilizacionController::class, 'store'])->name('store');
        });

        Route::middleware('permission:esterilizaciones.editar')->group(function () {
            Route::put('/{esterilizacion}', [EsterilizacionController::class, 'update'])->name('update');
        });

        Route::middleware('permission:esterilizaciones.eliminar')->group(function () {
            Route::delete('/{esterilizacion}', [EsterilizacionController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       RADIOLOGÍA
       ============================================================ */
    Route::prefix('radiologia')->name('radiologia.')->group(function () {

        Route::middleware('permission:radiologias.ver')->group(function () {
            Route::get('/',                 [RadiologiaController::class, 'index'])->name('index');
            Route::get('/tabla',            [RadiologiaController::class, 'tabla'])->name('tabla');
            Route::get('/buscar-pacientes', [RadiologiaController::class, 'buscarPacientes'])->name('buscar-pacientes');
            Route::get('/buscar-cajas',     [RadiologiaController::class, 'buscarCajas'])->name('buscar-cajas');
            Route::get('/datos-iniciales',  [RadiologiaController::class, 'datosIniciales'])->name('datos-iniciales');

            Route::get('/{radiologia}/datos', [RadiologiaController::class, 'datos'])->name('datos');
            Route::get('/{radiologia}',       [RadiologiaController::class, 'show'])->name('show');
        });

        Route::middleware('permission:radiologias.crear')->group(function () {
            Route::post('/', [RadiologiaController::class, 'store'])->name('store');
        });

        Route::middleware('permission:radiologias.editar')->group(function () {
            Route::put('/{radiologia}', [RadiologiaController::class, 'update'])->name('update');
        });

        Route::middleware('permission:radiologias.eliminar')->group(function () {
            Route::delete('/{radiologia}', [RadiologiaController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       CAJAS
       ============================================================ */
    Route::prefix('cajas')->name('cajas.')->group(function () {

        Route::middleware('permission:cajas.ver')->group(function () {
            Route::get('/',              [CajaController::class, 'index'])->name('index');
            Route::get('/tabla',         [CajaController::class, 'tabla'])->name('tabla');
            Route::get('/{caja}/datos',  [CajaController::class, 'datos'])->name('datos');
            Route::get('/{caja}',        [CajaController::class, 'show'])->name('show');
        });

        Route::middleware('permission:cajas.crear')->group(function () {
            Route::post('/', [CajaController::class, 'store'])->name('store');
        });

        Route::middleware('permission:cajas.editar')->group(function () {
            Route::put('/{caja}', [CajaController::class, 'update'])->name('update');
        });

        Route::middleware('permission:cajas.eliminar')->group(function () {
            Route::delete('/{caja}', [CajaController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       REPORTES
       ============================================================ */
    Route::prefix('reportes')->name('reportes.')->group(function () {

        Route::middleware('permission:reportes.ver')->group(function () {

            Route::get('/', [ReporteController::class, 'index'])->name('index');

            /* ---- Endpoints JSON de gráficos: {labels, valores, total} ---- */
            Route::get('/pacientes-por-periodo',         [ReporteController::class, 'pacientesPorPeriodo'])->name('pacientes-por-periodo');
            Route::get('/pacientes-por-especialidad',    [ReporteController::class, 'pacientesPorEspecialidad'])->name('pacientes-por-especialidad');
            Route::get('/pacientes-por-edad',            [ReporteController::class, 'pacientesPorEdad'])->name('pacientes-por-edad');
            Route::get('/pacientes-por-sexo',            [ReporteController::class, 'pacientesPorSexo'])->name('pacientes-por-sexo');
            Route::get('/motivos-consulta',              [ReporteController::class, 'motivosConsulta'])->name('motivos-consulta');
            Route::get('/tratamientos-por-especialidad', [ReporteController::class, 'tratamientosPorEspecialidad'])->name('tratamientos-por-especialidad');
            Route::get('/atenciones-por-docente',        [ReporteController::class, 'atencionesPorDocente'])->name('atenciones-por-docente');
            Route::get('/atenciones-por-estudiante',     [ReporteController::class, 'atencionesPorEstudiante'])->name('atenciones-por-estudiante');
            Route::get('/insumos-utilizados',            [ReporteController::class, 'insumosUtilizados'])->name('insumos-utilizados');
            Route::get('/radiografias-por-mes',          [ReporteController::class, 'radiografiasPorMes'])->name('radiografias-por-mes');
            Route::get('/esterilizaciones-por-mes',      [ReporteController::class, 'esterilizacionesPorMes'])->name('esterilizaciones-por-mes');
            Route::get('/pagos-por-tipo',                [ReporteController::class, 'pagosPorTipo'])->name('pagos-por-tipo');
            Route::get('/ingresos-por-mes',              [ReporteController::class, 'ingresosPorMes'])->name('ingresos-por-mes');

            /* ---- Exportaciones ----
               CSV:  GET /reportes/exportar/csv         (?reporte=<clave> para uno solo)
               PDF:  GET /reportes/exportar/imprimible  (vista con window.print())
            */
            Route::get('/exportar/csv',        [ReporteController::class, 'exportarCsv'])->name('exportar.csv');
            Route::get('/exportar/imprimible', [ReporteController::class, 'imprimible'])->name('exportar.imprimible');
        });

        Route::middleware('permission:reportes.crear')->group(function () {
            Route::post('/', [ReporteController::class, 'store'])->name('store');
        });
    });

    /* ============================================================
       KARDEX (solo lectura) — usa el permiso reportes.ver
       ============================================================ */
    Route::prefix('kardex')->name('kardex.')->group(function () {
        Route::middleware('permission:reportes.ver')->group(function () {
            // Las rutas literales van ANTES de /kardex/{id}/datos
            Route::get('/',                [KardexController::class, 'index'])->name('index');
            Route::get('/tabla',           [KardexController::class, 'tabla'])->name('tabla');
            Route::get('/{id}/datos',      [KardexController::class, 'datos'])->name('datos');
        });
    });

    /* ============================================================
       INVESTIGACIÓN
       ============================================================ */
    Route::prefix('investigacion')->name('investigacion.')->group(function () {
        // Rutas literales ANTES de cualquier /investigacion/{id}
        Route::middleware('permission:investigacion.ver')->group(function () {
            Route::get('/', [InvestigacionController::class, 'index'])->name('index');
            Route::get('/tabla', [InvestigacionController::class, 'tabla'])->name('tabla');
            Route::get('/estadisticas', [InvestigacionController::class, 'estadisticas'])->name('estadisticas');
        });

        Route::middleware('permission:investigacion.ver|investigacion.gestionar')->group(function () {
            Route::get('/exportar', [InvestigacionController::class, 'exportar'])->name('exportar');
        });
    });

    /* ============================================================
       USUARIOS — Roles dinámicos (sin prefijo admin.)
       ============================================================ */
    Route::prefix('usuarios')->name('usuarios.')->group(function () {

        Route::middleware('permission:usuarios.ver')->group(function () {
            Route::get('/',                [UsuarioController::class, 'index'])->name('index');
            Route::get('/tabla',           [UsuarioController::class, 'tabla'])->name('tabla');
            Route::get('/{usuario}/datos', [UsuarioController::class, 'datos'])->name('datos');
            Route::get('/{usuario}',       [UsuarioController::class, 'show'])->name('show');
        });

        Route::middleware('permission:usuarios.crear')->group(function () {
            Route::post('/', [UsuarioController::class, 'store'])->name('store');
        });

        Route::middleware('permission:usuarios.editar')->group(function () {
            Route::put('/{usuario}',              [UsuarioController::class, 'update'])->name('update');
            Route::post('/{usuario}/habilitar',   [UsuarioController::class, 'habilitar'])->name('habilitar');
            Route::post('/{usuario}/cambiar-rol', [UsuarioController::class, 'cambiarRol'])->name('cambiar-rol');
        });

        Route::middleware('permission:usuarios.eliminar')->group(function () {
            Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       ROLES — Gestión dinámica de roles y permisos
       ============================================================ */
    Route::prefix('roles')->name('roles.')->group(function () {

        Route::middleware('permission:roles.ver')->group(function () {
            Route::get('/',            [RolController::class, 'index'])->name('index');
            Route::get('/tabla',       [RolController::class, 'tabla'])->name('tabla');
            Route::get('/{rol}/datos', [RolController::class, 'datos'])->name('datos');
            Route::get('/{rol}',       [RolController::class, 'show'])->name('show');
        });

        Route::middleware('permission:roles.crear')->group(function () {
            Route::post('/', [RolController::class, 'store'])->name('store');
        });

        Route::middleware('permission:roles.editar')->group(function () {
            Route::put('/{rol}',           [RolController::class, 'update'])->name('update');
            Route::post('/{rol}/permisos', [RolController::class, 'syncPermisos'])->name('permisos');
        });

        Route::middleware('permission:roles.eliminar')->group(function () {
            Route::delete('/{rol}', [RolController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       CLÍNICAS
       ============================================================ */
    Route::prefix('clinicas')->name('clinicas.')->group(function () {

        Route::middleware('permission:clinicas.ver')->group(function () {
            Route::get('/',              [ClinicaController::class, 'index'])->name('index');
            Route::get('/tabla',         [ClinicaController::class, 'tabla'])->name('tabla');
            Route::get('/{clinica}/datos', [ClinicaController::class, 'datos'])->name('datos');
            Route::get('/{clinica}',     [ClinicaController::class, 'show'])->name('show');
        });

        Route::middleware('permission:clinicas.crear')->group(function () {
            Route::post('/', [ClinicaController::class, 'store'])->name('store');
        });

        Route::middleware('permission:clinicas.editar')->group(function () {
            Route::put('/{clinica}', [ClinicaController::class, 'update'])->name('update');
        });

        Route::middleware('permission:clinicas.eliminar')->group(function () {
            Route::delete('/{clinica}', [ClinicaController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       PERIODOS
       ============================================================ */
    Route::prefix('periodos')->name('periodos.')->group(function () {

        Route::middleware('permission:periodos.ver')->group(function () {
            Route::get('/',              [PeriodoController::class, 'index'])->name('index');
            Route::get('/tabla',         [PeriodoController::class, 'tabla'])->name('tabla');
            Route::get('/{periodo}/datos', [PeriodoController::class, 'datos'])->name('datos');
            Route::get('/{periodo}',     [PeriodoController::class, 'show'])->name('show');
        });

        Route::middleware('permission:periodos.crear')->group(function () {
            Route::post('/', [PeriodoController::class, 'store'])->name('store');
        });

        Route::middleware('permission:periodos.editar')->group(function () {
            Route::put('/{periodo}', [PeriodoController::class, 'update'])->name('update');
        });

        Route::middleware('permission:periodos.eliminar')->group(function () {
            Route::delete('/{periodo}', [PeriodoController::class, 'destroy'])->name('destroy');
        });
    });

    /* ============================================================
       BITÁCORAS
       ============================================================ */
    Route::prefix('bitacoras')->name('bitacoras.')->group(function () {
        Route::middleware('permission:bitacoras.ver')->group(function () {
            Route::get('/',           [BitacoraController::class, 'index'])->name('index');
            Route::get('/tabla',      [BitacoraController::class, 'tabla'])->name('tabla');
            Route::get('/{bitacora}', [BitacoraController::class, 'show'])->name('show');
        });
    });
});
