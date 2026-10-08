<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Usuario;
use App\Services\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /
     * Panel principal con métricas según el rol del usuario.
     */
    public function index()
    {
        $user = $this->usuarioActual();

        return view('dashboard', [
            'user' => $user,
            'tarjetas' => $user ? DashboardMetrics::tarjetas($user) : [],
            'pacientesPorMes' => DashboardMetrics::pacientesPorMes(),
            'topEspecialidades' => DashboardMetrics::topEspecialidades(),
            'actividadReciente' => DashboardMetrics::actividadReciente(),
            'proximosVencimientos' => DashboardMetrics::proximosVencimientos(),
        ]);
    }

    /**
     * GET /dashboard/stats
     * Datos de los gráficos (para refresco sin recargar la página).
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'por_mes' => DashboardMetrics::pacientesPorMes(),
            'especialidades' => DashboardMetrics::topEspecialidades(),
            'generado' => now()->toIso8601String(),
        ]);
    }

    private function usuarioActual(): ?Usuario
    {
        return auth()->user() ?? Usuario::find(session('user_id'));
    }

    public function datosApi(Request $request): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario !== null, 401, 'No autenticado.');

        return response()->json([
            'success' => true,
            'data' => [
                'tarjetas' => DashboardMetrics::tarjetas($usuario),
                'pacientes_por_mes' => DashboardMetrics::pacientesPorMes(),
                'especialidades' => DashboardMetrics::topEspecialidades(),
                'no_leidas' => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
            ],
        ]);
    }
}
