<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use App\Models\Usuario;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegistrarAcciones
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $usuario = $request->user();
        if (! $usuario && $request->hasSession() && $request->session()->has('user_id')) {
            $usuario = Usuario::find($request->session()->get('user_id'));
        }

        $response = $next($request);

        if ($request->route()?->getName()) {
            try {
                $usuario = $request->attributes->get('bitacora.usuario') ?? $request->user() ?? $usuario;
                $this->registrar($request, $response, $usuario instanceof Usuario ? $usuario : null);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    private function registrar(Request $request, Response $response, ?Usuario $usuario): void
    {
        $routeName = $request->route()->getName();
        $route = Str::after($routeName, 'api.v1.');
        $action = $this->accion($route, $request->method());
        $module = Str::before($route, '.');
        $responseData = $response instanceof JsonResponse ? $response->getData(true) : [];
        $failed = $response->getStatusCode() >= 400 || data_get($responseData, 'success') === false;

        if ($request->hasSession()) {
            $failed = $failed || array_intersect($request->session()->get('_flash.new', []), ['errors', 'error']) !== [];
        }

        $records = [];
        foreach ($request->route()->parameters() as $name => $value) {
            if ($value instanceof Model) {
                $records[$name] = $value->getKey();
            } elseif (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $records[$name] = (int) $value;
            }
        }
        $createdId = data_get($responseData, 'id') ?? data_get($responseData, 'registro.id') ?? data_get($responseData, 'data.id');
        if (is_int($createdId) || (is_string($createdId) && ctype_digit($createdId))) {
            $records['registro'] = (int) $createdId;
        }

        $labels = [
            'crear' => 'Creación', 'editar' => 'Edición', 'eliminar' => 'Eliminación',
            'habilitar' => 'Habilitación', 'deshabilitar' => 'Deshabilitación',
            'autorizar' => 'Autorización', 'aprobar' => 'Aprobación', 'rechazar' => 'Rechazo',
            'entregar' => 'Entrega', 'asignar' => 'Asignación', 'ajustar' => 'Ajuste de stock',
            'login' => 'Inicio de sesión', 'logout' => 'Cierre de sesión',
        ];
        $description = $labels[$action] ?? Str::headline($action);
        if (! in_array($action, ['login', 'logout'], true)) {
            $description .= ' · '.Str::headline($module === 'auth' ? 'cuenta de usuario' : $module);
        }

        Bitacora::create([
            'usuario_id' => $usuario?->id,
            'ip' => $request->ip() ?? '0.0.0.0',
            'fecha' => now(),
            'tipo' => $action,
            'estado' => $failed ? 'error' : 'exito',
            'datos' => json_encode([
                'descripcion' => $description,
                'usuario' => $usuario ? ['id' => $usuario->id, 'nombre' => $usuario->nombre_completo, 'cuenta' => $usuario->nombre_usuario] : null,
                'ruta' => $routeName,
                'metodo' => $request->method(),
                'registros' => $records,
                'codigo_http' => $response->getStatusCode(),
                'resultado' => $failed ? match ($response->getStatusCode()) {
                    401 => 'La sesión no está autenticada.',
                    403 => 'El usuario no tiene permiso para realizar esta acción.',
                    404 => 'El registro solicitado no existe.',
                    422 => 'La operación fue rechazada por una validación o una regla del sistema.',
                    default => 'La operación no se completó.',
                } : 'La operación se completó correctamente.',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    private function accion(string $route, string $method): string
    {
        if (in_array($route, ['auth.login.post', 'login'], true)) {
            return 'login';
        }
        if (str_contains($route, 'logout')) {
            return 'logout';
        }
        if ($route === 'usuarios.destroy') {
            return 'deshabilitar';
        }

        $operation = Str::afterLast($route, '.');
        foreach (['deshabilitar', 'habilitar', 'autorizar', 'aprobar', 'rechazar', 'entregar', 'asignar', 'ajustar'] as $action) {
            if (str_contains($operation, $action)) {
                return $action;
            }
        }

        return match ($operation) {
            'store' => 'crear',
            'update' => 'editar',
            'destroy', 'eliminar', 'eliminar-definitivamente' => 'eliminar',
            default => match ($method) {
                'PUT', 'PATCH' => 'editar',
                'DELETE' => 'eliminar',
                default => 'actualizar',
            },
        };
    }
}
