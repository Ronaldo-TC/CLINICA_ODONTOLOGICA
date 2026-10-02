<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HistoriaResource;
use App\Http\Resources\InsumoResource;
use App\Http\Resources\NotificacionResource;
use App\Http\Resources\PacienteResource;
use App\Http\Resources\SolicitudResource;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ApiController
 *
 * Endpoints REST para la app móvil (Sanctum). Cada acción valida el permiso
 * `modulo.accion` del usuario autenticado y responde con Recursos Eloquent.
 */
class ApiController extends Controller
{
    /* ============================================================
       UTILIDADES
       ============================================================ */

    private function usuario(Request $request): ?Usuario
    {
        return $request->user() ?? Usuario::find(session('user_id'));
    }

    private function autorizar(Request $request, string $permiso): void
    {
        $usuario = $this->usuario($request);

        abort_unless(
            $usuario && ($usuario->hasRole('admin') || $usuario->can($permiso)),
            403,
            "No tienes permiso {$permiso}"
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function paginacion($paginador): array
    {
        return [
            'total'        => $paginador->total(),
            'por_pagina'   => $paginador->perPage(),
            'pagina'       => $paginador->currentPage(),
            'ultima_pagina' => $paginador->lastPage(),
        ];
    }

    /* ============================================================
       SESIÓN
       ============================================================ */

    /**
     * POST /api/v1/login
     * Emite un token Sanctum. Cuerpo: { nombre_usuario, contrasena, device_name? }
     */
    public function login(Request $request): JsonResponse
    {
        $validador = Validator::make($request->all(), [
            'nombre_usuario' => ['required', 'string', 'max:50'],
            'contrasena'     => ['required', 'string'],
            'device_name'    => ['nullable', 'string', 'max:100'],
        ]);

        if ($validador->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los campos enviados.',
                'errors'  => $validador->errors(),
            ], 422);
        }

        $datos = $validador->validated();

        $usuario = Usuario::query()->where('nombre_usuario', $datos['nombre_usuario'])->first();

        if (! $usuario || ! \Illuminate\Support\Facades\Hash::check($datos['contrasena'], $usuario->contrasena)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if (! $usuario->habilitado || ! $usuario->permitir_login) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene permitido iniciar sesión.',
            ], 403);
        }

        // Si no permite múltiples sesiones, se revocan los tokens anteriores
        if (! $usuario->multilogin) {
            $usuario->tokens()->delete();
        }

        $usuario->forceFill([
            'ultimo_login' => now(),
            'ip'           => $request->ip(),
        ])->save();

        $dispositivo = $datos['device_name'] ?? 'movil';
        $token = $usuario->createToken($dispositivo)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada.',
            'data'    => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'              => $usuario->id,
                    'ci'              => $usuario->ci,
                    'nombre_usuario'  => $usuario->nombre_usuario,
                    'nombre_completo' => $usuario->nombre_completo,
                    'foto'            => $usuario->foto,
                    'roles'           => $usuario->getRoleNames(),
                    'permisos'        => $usuario->getAllPermissions()->pluck('name'),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $this->usuario($request);
        abort_unless($usuario !== null, 401, 'No autenticado.');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $usuario->id,
                'nombre_usuario'  => $usuario->nombre_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'ci'              => $usuario->ci,
                'roles'           => $usuario->getRoleNames(),
                'permisos'        => $usuario->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * POST /api/v1/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    /**
     * POST /api/v1/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Se cerraron todas las sesiones.']);
    }

    /* ============================================================
       DASHBOARD
       ============================================================ */

    /**
     * GET /api/v1/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $usuario = $this->usuario($request);
        abort_unless($usuario !== null, 401, 'No autenticado.');

        return response()->json([
            'success' => true,
            'data'    => [
                'tarjetas'          => \App\Services\DashboardMetrics::tarjetas($usuario),
                'pacientes_por_mes' => \App\Services\DashboardMetrics::pacientesPorMes(),
                'especialidades'    => \App\Services\DashboardMetrics::topEspecialidades(),
                'no_leidas'         => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
            ],
        ]);
    }

    /* ============================================================
       PACIENTES
       ============================================================ */

    /**
     * GET /api/v1/pacientes
     */
    public function pacientes(Request $request): JsonResponse
    {
        $this->autorizar($request, 'pacientes.ver');

        $buscar = trim((string) $request->input('buscar'));

        $paginador = Paciente::query()
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($x) use ($buscar) {
                    $x->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombre')
            ->paginate((int) $request->input('por_pagina', 20));

        return response()->json([
            'success'    => true,
            'data'       => PacienteResource::collection($paginador->items()),
            'paginacion' => $this->paginacion($paginador),
        ]);
    }

    /**
     * GET /api/v1/pacientes/{id}
     */
    public function paciente(Request $request, $id): JsonResponse
    {
        $this->autorizar($request, 'pacientes.ver');

        $paciente = Paciente::query()
            ->withCount(['admisiones', 'historiasClinicas'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                (new PacienteResource($paciente))->toArray($request),
                [
                    'total_admisiones' => $paciente->admisiones_count,
                    'total_historias'  => $paciente->historias_clinicas_count,
                ]
            ),
        ]);
    }

    /* ============================================================
       HISTORIAS CLÍNICAS
       ============================================================ */

    /**
     * GET /api/v1/historias
     */
    public function historias(Request $request): JsonResponse
    {
        $this->autorizar($request, 'historias.ver');

        $paginador = HistoriaClinica::query()
            ->with(['paciente:id,nombre,apellido_paterno,apellido_materno,ci', 'clinica:id,especialidad', 'periodo:id,nombre', 'estudiante:id,nombre_completo', 'docente:id,nombre_completo'])
            ->when($request->filled('paciente_id'), fn ($q) => $q->where('paciente_id', $request->input('paciente_id')))
            ->when($request->filled('clinica_id'), fn ($q) => $q->where('clinica_id', $request->input('clinica_id')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->when($request->filled('autorizado'), fn ($q) => $q->where('autorizado', $request->boolean('autorizado')))
            ->orderByDesc('id')
            ->paginate((int) $request->input('por_pagina', 20));

        return response()->json([
            'success'    => true,
            'data'       => HistoriaResource::collection($paginador->items()),
            'paginacion' => $this->paginacion($paginador),
        ]);
    }

    /**
     * GET /api/v1/historias/{id}
     */
    public function historia(Request $request, $id): JsonResponse
    {
        $this->autorizar($request, 'historias.ver');

        $historia = HistoriaClinica::query()
            ->with(['paciente', 'clinica', 'periodo', 'estudiante', 'docente', 'tratamientosRealizados.tratamiento'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                (new HistoriaResource($historia))->toArray($request),
                [
                    'odontograma'  => $historia->admision?->odontograma_pintado,
                    'tratamientos' => $historia->tratamientosRealizados->map(fn ($t) => [
                        'id'            => $t->id,
                        'tratamiento'   => $t->tratamiento?->nombre,
                        'pieza_dental'  => $t->pieza_dental,
                        'estado'        => $t->estado,
                        'costo_cobrado' => (float) $t->costo_cobrado,
                        'fecha_inicio'  => $t->fecha_inicio?->format('Y-m-d'),
                    ]),
                ]
            ),
        ]);
    }

    /**
     * POST /api/v1/historias
     * Registra el avance de una historia existente (estado y revisión).
     */
    public function crearHistoria(Request $request): JsonResponse
    {
        $this->autorizar($request, 'historias.editar');

        $validador = Validator::make($request->all(), [
            'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
            'estado'              => ['nullable', 'string', 'max:50'],
            'revision_docente'    => ['nullable', 'string'],
        ]);

        if ($validador->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los campos enviados.',
                'errors'  => $validador->errors(),
            ], 422);
        }

        $datos = $validador->validated();

        $historia = HistoriaClinica::findOrFail($datos['historia_clinica_id']);
        $historia->update(array_filter([
            'estado'           => $datos['estado'] ?? null,
            'revision_docente' => $datos['revision_docente'] ?? null,
        ], fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Historia clínica actualizada.',
            'data'    => new HistoriaResource($historia->fresh()->load(['paciente', 'clinica'])),
        ]);
    }

    /* ============================================================
       INSUMOS
       ============================================================ */

    /**
     * GET /api/v1/insumos
     */
    public function insumos(Request $request): JsonResponse
    {
        $this->autorizar($request, 'insumos.ver');

        $buscar = trim((string) $request->input('buscar'));

        $paginador = Insumo::query()
            ->with(['clinica:id,especialidad', 'insumoTratamiento:id,nombre'])
            ->when($buscar !== '', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->when($request->filled('clinica_id'), fn ($q) => $q->where('clinica_id', $request->input('clinica_id')))
            ->when($request->boolean('stock_bajo'), fn ($q) => $q->whereColumn('cantidad_numerica', '<=', 'stock_minimo'))
            ->orderBy('nombre')
            ->paginate((int) $request->input('por_pagina', 20));

        return response()->json([
            'success'    => true,
            'data'       => InsumoResource::collection($paginador->items()),
            'paginacion' => $this->paginacion($paginador),
        ]);
    }

    /* ============================================================
       SOLICITUDES DE INSUMOS
       ============================================================ */

    /**
     * GET /api/v1/solicitudes-insumos
     */
    public function solicitudes(Request $request): JsonResponse
    {
        $this->autorizar($request, 'insumos.ver');

        $usuario = $this->usuario($request);

        $paginador = InsumoSolicitado::query()
            ->with(['clinica:id,especialidad', 'estudiante:id,nombre_completo', 'docente:id,nombre_completo', 'detalles.insumo:id,nombre,cantidad'])
            ->when(
                $usuario?->hasRole('estudiante') || $usuario?->hasRole('interno'),
                fn ($q) => $q->where('usuario_id', $usuario->id)
            )
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->orderByDesc('id')
            ->paginate((int) $request->input('por_pagina', 20));

        return response()->json([
            'success'    => true,
            'data'       => SolicitudResource::collection($paginador->items()),
            'paginacion' => $this->paginacion($paginador),
        ]);
    }

    /**
     * POST /api/v1/solicitudes-insumos
     */
    public function crearSolicitud(Request $request): JsonResponse
    {
        $this->autorizar($request, 'insumos.crear');

        $validador = Validator::make($request->all(), [
            'clinica_id'              => ['required', 'integer', 'exists:clinicas,id'],
            'insumo_tratamiento_id'   => ['required', 'integer', 'exists:insumos_tratamiento,id'],
            'pieza'                   => ['nullable', 'integer', 'min:0', 'max:99'],
            'tratamiento_realizado_id' => ['nullable', 'integer', 'exists:tratamientos_realizados,id'],
            'detalles'                => ['required', 'array', 'min:1'],
            'detalles.*.insumo_id'    => ['required', 'integer', 'exists:insumos,id'],
            'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'min:0.01'],
        ], [
            'detalles.required' => 'Debe agregar al menos un insumo.',
            'detalles.min'      => 'Debe agregar al menos un insumo.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los campos enviados.',
                'errors'  => $validador->errors(),
            ], 422);
        }

        $datos   = $validador->validated();
        $usuario = $this->usuario($request);

        DB::beginTransaction();

        try {
            $solicitud = InsumoSolicitado::create([
                'pieza'                   => $datos['pieza'] ?? 0,
                'insumo_tratamiento_id'   => $datos['insumo_tratamiento_id'],
                'clinica_id'              => $datos['clinica_id'],
                'tratamiento_realizado_id' => $datos['tratamiento_realizado_id'] ?? null,
                'usuario_id'              => $usuario->id,
                'estado'                  => InsumoSolicitado::ESTADO_PENDIENTE,
                'autorizado'              => 0,
                'docente_autoriza'        => 0,
            ]);

            foreach ($datos['detalles'] as $detalle) {
                InsumoSolicitadoDetalle::create([
                    'insumo_solicitado_id' => $solicitud->id,
                    'insumo_id'            => $detalle['insumo_id'],
                    'cantidad_solicitada'  => $detalle['cantidad_solicitada'],
                    'cantidad_entregada'   => 0,
                    'fecha'                => now(),
                ]);
            }

            DB::commit();

            \App\Services\NotificacionService::aRol(
                ['docente', 'jefe-catedra'],
                Notificacion::TIPO_SOLICITUD_CREADA,
                'Nueva solicitud de insumos #' . $solicitud->id,
                'Creada desde la app móvil.',
                \Illuminate\Support\Facades\Route::has('solicitudes-insumos.index')
                    ? route('solicitudes-insumos.index')
                    : null,
                $usuario->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada correctamente.',
                'data'    => new SolicitudResource($solicitud->load(['clinica', 'detalles.insumo'])),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->environment('production')
                    ? 'No se pudo crear la solicitud.'
                    : 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ============================================================
       NOTIFICACIONES
       ============================================================ */

    /**
     * GET /api/v1/notificaciones
     */
    public function notificaciones(Request $request): JsonResponse
    {
        $usuario = $this->usuario($request);
        abort_unless($usuario !== null, 401, 'No autenticado.');

        $paginador = Notificacion::query()
            ->delUsuario($usuario->id)
            ->when($request->boolean('no_leidas'), fn ($q) => $q->noLeidas())
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('por_pagina', 20));

        return response()->json([
            'success'    => true,
            'no_leidas'  => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
            'data'       => NotificacionResource::collection($paginador->items()),
            'paginacion' => $this->paginacion($paginador),
        ]);
    }

    /**
     * POST /api/v1/notificaciones/{id}/leer
     */
    public function leerNotificacion(Request $request, $id): JsonResponse
    {
        $usuario = $this->usuario($request);
        abort_unless($usuario !== null, 401, 'No autenticado.');

        $notificacion = Notificacion::query()->delUsuario($usuario->id)->find($id);

        if (! $notificacion) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada.'], 404);
        }

        $notificacion->marcarLeida();

        return response()->json([
            'success'   => true,
            'message'   => 'Notificación marcada como leída.',
            'no_leidas' => Notificacion::query()->delUsuario($usuario->id)->noLeidas()->count(),
        ]);
    }
}
