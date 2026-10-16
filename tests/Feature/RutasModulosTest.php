<?php

namespace Tests\Feature;

use App\Http\Controllers\AdmisionController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\OdontogramaController;
use App\Http\Controllers\RadiologiaController;
use App\Http\Controllers\RemisionController;
use App\Http\Controllers\SolicitudRadiologicaController;
use App\Http\Controllers\TratamientoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class RutasModulosTest extends TestCase
{
    public function test_todas_las_rutas_resuelven_controladores_y_acciones_existentes(): void
    {
        $invalidas = [];
        foreach (Route::getRoutes() as $ruta) {
            $accion = $ruta->getActionName();
            if (! str_starts_with($accion, 'App\\Http\\Controllers\\')) {
                continue;
            }
            [$controlador, $metodo] = explode('@', $accion);
            if (! class_exists($controlador) || ! method_exists($controlador, $metodo) || ! (new ReflectionMethod($controlador, $metodo))->isPublic()) {
                $invalidas[] = $ruta->uri().': '.$accion;
            }
        }

        $this->assertSame([], $invalidas);
    }

    public function test_las_rutas_de_cada_modulo_comparten_un_controlador(): void
    {
        $modulos = [
            'clinicas.' => ClinicaController::class,
            'remisiones.' => RemisionController::class,
            'pacientes-remitidos.' => RemisionController::class,
            'admisiones.' => AdmisionController::class,
            'historias.' => [HistoriaController::class, SolicitudRadiologicaController::class],
            'api.v1.historias.' => HistoriaController::class,
            'radiologia.' => [RadiologiaController::class, SolicitudRadiologicaController::class],
            'tratamientos.' => TratamientoController::class,
            'tratamientos-realizados.' => TratamientoController::class,
            'sesiones-atencion.' => TratamientoController::class,
            'insumos.' => InsumoController::class,
            'solicitudes-insumos.' => InsumoController::class,
            'api.v1.solicitudes.' => InsumoController::class,
            'odontogramas.' => OdontogramaController::class,
            'api.v1.odontogramas.' => OdontogramaController::class,
        ];

        foreach ($modulos as $prefijo => $controlador) {
            $rutas = collect(Route::getRoutes())->filter(fn ($ruta): bool => str_starts_with($ruta->getName() ?? '', $prefijo));
            $this->assertNotEmpty($rutas->all(), $prefijo);
            foreach ($rutas as $ruta) {
                $this->assertContains($ruta->getControllerClass(), (array) $controlador, $ruta->getName());
            }
        }
    }

    public function test_las_busquedas_estaticas_se_resuelven_antes_de_los_identificadores(): void
    {
        $busquedas = [
            '/cajas/buscar-ficha' => 'cajas.buscar-ficha',
            '/solicitudes-insumos/catalogo-designado' => 'solicitudes-insumos.catalogo-designado',
            '/remisiones/buscar-admisiones' => 'remisiones.buscar-admisiones',
            '/pacientes-remitidos/buscar' => 'pacientes-remitidos.buscar',
            '/admisiones/buscar-pacientes' => 'admisiones.buscar-pacientes',
        ];

        foreach ($busquedas as $url => $nombre) {
            $this->assertSame($nombre, Route::getRoutes()->match(Request::create($url, 'GET'))->getName());
        }
    }
}
