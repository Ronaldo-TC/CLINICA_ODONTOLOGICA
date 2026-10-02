<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificacionController
 *
 * Campana del topbar y bandeja de notificaciones internas.
 * Cada usuario sólo ve y modifica SUS notificaciones.
 */
class NotificacionController extends Controller
{
    private const POR_PAGINA = 20;

    /**
     * GET /notificaciones
     * Bandeja completa del usuario autenticado.
     */
    public function index(Request $request)
    {
        $usuario = $this->usuarioActual();

        abort_unless($usuario !== null, 403);

        $filtro = $request->input('filtro', 'todas');

        $notificaciones = Notificacion::query()
            ->delUsuario($usuario->id)
            ->when($filtro === 'no_leidas', fn ($q) => $q->noLeidas())
            ->when($filtro === 'leidas', fn ($q) => $q->leidas())
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('notificaciones.index', [
            'registros'      => $notificaciones,
            'items'          => $notificaciones,
            'filtro'         => $filtro,
            'totalNoLeidas'  => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
        ]);
    }

    /**
     * GET /notificaciones/ultimas
     * Últimas notificaciones para el dropdown del topbar.
     */
    public function ultimas(Request $request): JsonResponse
    {
        $usuario = $this->usuarioActual();

        if (! $usuario) {
            return response()->json(['success' => false, 'message' => 'Sesión no válida.'], 401);
        }

        $limite = (int) $request->input('limite', 10);

        $noLeidas = Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count();

        $items = Notificacion::query()
            ->delUsuario($usuario->id)
            ->orderByDesc('created_at')
            ->limit(max(1, min($limite, 20)))
            ->get()
            ->map(fn (Notificacion $n) => [
                'id'       => $n->id,
                'tipo'     => $n->tipo,
                'titulo'   => $n->titulo,
                'mensaje'  => $n->mensaje,
                'url'      => $n->url,
                'leida'    => $n->leida,
                'tono'     => $n->tono,
                'icono'    => $n->icono,
                'fecha'    => $n->created_at?->format('d/m/Y H:i'),
                'hace'     => $n->tiempo_relativo,
            ]);

        return response()->json([
            'success'  => true,
            'no_leidas' => $noLeidas,
            'items'    => $items,
        ]);
    }

    /**
     * POST /notificaciones/{id}/leer
     * Marca una notificación como leída y devuelve su URL de destino.
     */
    public function leer($id): JsonResponse
    {
        $usuario = $this->usuarioActual();

        if (! $usuario) {
            return response()->json(['success' => false, 'message' => 'Sesión no válida.'], 401);
        }

        $notificacion = Notificacion::query()
            ->delUsuario($usuario->id)
            ->find($id);

        if (! $notificacion) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada.'], 404);
        }

        $notificacion->marcarLeida();

        return response()->json([
            'success'   => true,
            'url'       => $notificacion->url,
            'no_leidas' => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
        ]);
    }

    /**
     * POST /notificaciones/leer-todas
     */
    public function leerTodas(): JsonResponse
    {
        $usuario = $this->usuarioActual();

        if (! $usuario) {
            return response()->json(['success' => false, 'message' => 'Sesión no válida.'], 401);
        }

        $actualizadas = Notificacion::query()
            ->delUsuario($usuario->id)
            ->noLeidas()
            ->update([
                'leida'         => true,
                'fecha_lectura' => now(),
            ]);

        return response()->json([
            'success'   => true,
            'message'   => $actualizadas > 0
                ? "Se marcaron {$actualizadas} notificación(es) como leídas."
                : 'No había notificaciones pendientes.',
            'no_leidas' => 0,
        ]);
    }

    /**
     * DELETE /notificaciones/{id}
     */
    public function destroy($id): JsonResponse
    {
        $usuario = $this->usuarioActual();

        if (! $usuario) {
            return response()->json(['success' => false, 'message' => 'Sesión no válida.'], 401);
        }

        $notificacion = Notificacion::query()
            ->delUsuario($usuario->id)
            ->find($id);

        if (! $notificacion) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada.'], 404);
        }

        $notificacion->delete();

        return response()->json([
            'success'   => true,
            'message'   => 'Notificación eliminada.',
            'no_leidas' => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
        ]);
    }

    private function usuarioActual(): ?Usuario
    {
        return auth()->user() ?? Usuario::find(session('user_id'));
    }
}
