<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Tratamiento;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    // ============================================================
    // DASHBOARD
    // ============================================================

    public function index(): View
    {
        $stats = $this->calcularStats();

        $ultimasAdmisiones = DB::table('admisiones as a')
            ->join('pacientes as p', 'p.id', '=', 'a.paciente_id')
            ->join('periodos as pe', 'pe.id', '=', 'a.periodo_id')
            ->select(
                'a.id',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'pe.nombre as periodo',
                'a.created_at'
            )
            ->orderByDesc('a.created_at')
            ->limit(10)
            ->get();

        $afluenciaClinicas = $this->queryAfluenciaClinicas()
            ->orderByDesc('total_pacientes')
            ->limit(5)
            ->get();

        $cajasPeriodo = $this->queryCajasPeriodo()
            ->orderByDesc('periodo_id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', array_merge($stats, [
            'ultimasAdmisiones' => $ultimasAdmisiones,
            'afluenciaClinicas' => $afluenciaClinicas,
            'cajasPeriodo' => $cajasPeriodo,
        ]));
    }

    public function statsDashboard(): JsonResponse
    {
        return response()->json($this->calcularStats());
    }

    private function calcularStats(): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        return [
            'userCount' => Usuario::where('habilitado', true)->count(),
            'patientCount' => Paciente::count(),
            'clinicCount' => Clinica::count(),
            'periodCount' => Periodo::where('habilitado', true)->count(),
            'admisionCount' => DB::table('admisiones')->count(),
            'tratamientoCount' => DB::table('tratamientos_realizados')->count(),
            'ingresosMes' => (float) Caja::whereBetween('created_at', [$inicioMes, $finMes])
                ->sum('cancelado'),
            'pendientes' => HistoriaClinica::where('estado_revision', false)->count(),
        ];
    }

    // ============================================================
    // USUARIOS
    // ============================================================

    public function usuarios(Request $request): View
    {
        $usuarios = $this->queryUsuarios($request)->paginate(20);
        $roles = Rol::orderBy('nombre')->get();

        return view('admin.usuarios.index', compact('usuarios', 'roles'));
    }

    public function tablaUsuarios(Request $request): JsonResponse
    {
        $usuarios = $this->queryUsuarios($request)->paginate(20);

        return $this->responderTabla(
            view('admin.usuarios.partials.filas', compact('usuarios'))->render(),
            $usuarios
        );
    }

    public function usuarioJson(int $id): JsonResponse
    {
        $usuario = Usuario::with('roles')->findOrFail($id);

        return response()->json([
            'usuario' => array_merge($usuario->toArray(), [
                'roles_ids' => $usuario->roles->pluck('id')->map(fn($i) => (int) $i)->toArray(),
            ]),
        ]);
    }

    public function guardarUsuario(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglasUsuario());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $usuario = Usuario::create([
                'ci' => $request->ci,
                'nombre_usuario' => $request->nombre_usuario,
                'nombre_completo' => $request->nombre_completo,
                'descripcion' => $request->descripcion ?? '',
                'tipo' => $request->tipo,
                'habilitado' => $request->boolean('habilitado', true),
                'permitir_login' => $request->boolean('permitir_login', true),
                'multilogin' => $request->boolean('multilogin', false),
                'contrasena' => Hash::make($request->contrasena),
                'ip_permitida' => $request->ip_permitida ?? '',
                'info' => $request->info ?? '',
            ]);

            $usuario->roles()->sync($request->input('roles', []));

            $this->registrarBitacora('CREAR_USUARIO', "Usuario {$usuario->id} creado");
            DB::commit();

            return $this->responderExito('Usuario creado correctamente.', 'usuarios');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error al crear usuario: ' . $e->getMessage());
        }
    }

    public function actualizarUsuario(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validator = Validator::make($request->all(), $this->reglasUsuario($id));
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $data = [
                'ci' => $request->ci,
                'nombre_usuario' => $request->nombre_usuario,
                'nombre_completo' => $request->nombre_completo,
                'descripcion' => $request->descripcion ?? '',
                'tipo' => $request->tipo,
                'habilitado' => $request->boolean('habilitado'),
                'permitir_login' => $request->boolean('permitir_login'),
                'multilogin' => $request->boolean('multilogin'),
                'ip_permitida' => $request->ip_permitida ?? '',
                'info' => $request->info ?? '',
            ];

            if ($request->filled('contrasena')) {
                $data['contrasena'] = Hash::make($request->contrasena);
            }

            $usuario->update($data);
            $usuario->roles()->sync($request->input('roles', []));

            $activeSessions = DB::table('sesiones')
                ->where('usuario_id', $usuario->id)
                ->where('activa', true);

            if (! $data['habilitado'] || ! $data['permitir_login']) {
                $activeSessions->update([
                    'activa' => false,
                    'fin' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (! $data['multilogin']) {
                $sessionToKeep = (clone $activeSessions)
                    ->orderByDesc('inicio')
                    ->orderByDesc('id')
                    ->value('id');

                if ($sessionToKeep) {
                    $activeSessions->where('id', '!=', $sessionToKeep)->update([
                        'activa' => false,
                        'fin' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->registrarBitacora('ACTUALIZAR_USUARIO', "Usuario {$usuario->id} actualizado");
            DB::commit();

            return $this->responderExito('Usuario actualizado correctamente.', 'usuarios');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    public function eliminarUsuario(int $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->update([
                'habilitado' => false,
                'permitir_login' => false,
            ]);

            $this->registrarBitacora('DESHABILITAR_USUARIO', "Usuario {$id} deshabilitado");

            return $this->responderExito('Usuario deshabilitado.', 'usuarios');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function reglasUsuario(?int $id = null): array
    {
        return [
            'ci' => ['required', 'string', 'max:50', Rule::unique('usuarios', 'ci')->ignore($id)],
            'nombre_usuario' => ['required', 'string', 'max:20', Rule::unique('usuarios', 'nombre_usuario')->ignore($id)],
            'nombre_completo' => 'required|string|max:200',
            'tipo' => 'required|string|max:20',
            'contrasena' => $id ? 'nullable|string|min:6' : 'required|string|min:6',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ];
    }

    private function queryUsuarios(Request $request)
    {
        // MySQL: GROUP_CONCAT en lugar de STRING_AGG
        $subRoles = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->select('ur.usuario_id', DB::raw("GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles"))
            ->groupBy('ur.usuario_id');

        $query = DB::table('usuarios as u')
            ->leftJoinSub($subRoles, 'roles_agg', 'roles_agg.usuario_id', '=', 'u.id')
            ->select('u.*', 'roles_agg.roles');

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                // MySQL: LIKE en lugar de ILIKE
                $q->where('u.nombre_usuario', 'LIKE', "%{$b}%")
                    ->orWhere('u.nombre_completo', 'LIKE', "%{$b}%")
                    ->orWhere('u.ci', 'LIKE', "%{$b}%");
            });
        }
        if ($request->filled('tipo')) {
            $query->where('u.tipo', $request->input('tipo'));
        }
        if ($request->filled('habilitado')) {
            $query->where('u.habilitado', $request->boolean('habilitado'));
        }

        return $query->orderByDesc('u.created_at');
    }

    // ============================================================
    // PACIENTES
    // ============================================================

    public function pacientes(Request $request): View
    {
        $pacientes = $this->queryPacientes($request)->paginate(20);

        return view('admin.pacientes.index', compact('pacientes'));
    }

    public function tablaPacientes(Request $request): JsonResponse
    {
        $pacientes = $this->queryPacientes($request)->paginate(20);

        return $this->responderTabla(
            view('admin.pacientes.partials.filas', compact('pacientes'))->render(),
            $pacientes
        );
    }

    public function pacienteJson(int $id): JsonResponse
    {
        return response()->json(['paciente' => Paciente::findOrFail($id)]);
    }

    public function guardarPaciente(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglasPaciente());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $paciente = Paciente::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'genero' => $request->genero,
                'fecha_nacimiento' => Carbon::parse($request->fecha_nacimiento),
            ]);

            $this->registrarBitacora('CREAR_PACIENTE', "Paciente {$paciente->id} creado");

            return $this->responderExito('Paciente registrado correctamente.', 'pacientes');
        } catch (\Throwable $e) {
            return $this->responderError('Error al registrar paciente: ' . $e->getMessage());
        }
    }

    public function actualizarPaciente(Request $request, int $id)
    {
        $paciente = Paciente::findOrFail($id);

        $validator = Validator::make($request->all(), $this->reglasPaciente($id));
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $paciente->update([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'genero' => $request->genero,
                'fecha_nacimiento' => Carbon::parse($request->fecha_nacimiento),
            ]);

            $this->registrarBitacora('ACTUALIZAR_PACIENTE', "Paciente {$id} actualizado");

            return $this->responderExito('Paciente actualizado correctamente.', 'pacientes');
        } catch (\Throwable $e) {
            return $this->responderError('Error al actualizar paciente: ' . $e->getMessage());
        }
    }

    public function eliminarPaciente(int $id)
    {
        try {
            Paciente::findOrFail($id)->delete();
            $this->registrarBitacora('ELIMINAR_PACIENTE', "Paciente {$id} eliminado");

            return $this->responderExito('Paciente eliminado.', 'pacientes');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function reglasPaciente(?int $id = null): array
    {
        return [
            'ci' => ['required', 'string', 'max:50', Rule::unique('pacientes', 'ci')->ignore($id)],
            'nombre' => 'required|string|max:50',
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'required|string|max:50',
            'genero' => 'required|string|max:50',
            'fecha_nacimiento' => 'required|date',
        ];
    }

    private function queryPacientes(Request $request)
    {
        $query = Paciente::query();

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                // MySQL: LIKE
                $q->where('ci', 'LIKE', "%{$b}%")
                    ->orWhere('nombre', 'LIKE', "%{$b}%")
                    ->orWhere('apellido_paterno', 'LIKE', "%{$b}%")
                    ->orWhere('apellido_materno', 'LIKE', "%{$b}%");
            });
        }
        if ($request->filled('genero')) {
            $query->where('genero', $request->input('genero'));
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // CLÍNICAS
    // ============================================================

    public function clinicas(Request $request): View
    {
        $clinicas = $this->queryClinicas($request)->paginate(20);

        return view('admin.clinicas.index', compact('clinicas'));
    }

    public function tablaClinicas(Request $request): JsonResponse
    {
        $clinicas = $this->queryClinicas($request)->paginate(20);

        return $this->responderTabla(
            view('admin.clinicas.partials.filas', compact('clinicas'))->render(),
            $clinicas
        );
    }

    public function clinicaJson(int $id): JsonResponse
    {
        return response()->json(['clinica' => Clinica::findOrFail($id)]);
    }

    public function guardarClinica(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modulo' => 'required|string|max:200',
            'especialidad' => 'required|string|max:200|unique:clinicas,especialidad',
            'curso' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            Clinica::create([
                'modulo' => $request->modulo,
                'especialidad' => $request->especialidad,
                'curso' => $request->input('curso', -1),
            ]);

            $this->registrarBitacora('CREAR_CLINICA', "Clínica {$request->especialidad} creada");

            return $this->responderExito('Clínica registrada correctamente.', 'clinicas');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function actualizarClinica(Request $request, int $id)
    {
        $clinica = Clinica::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'modulo' => 'required|string|max:200',
            'especialidad' => ['required', 'string', 'max:200', Rule::unique('clinicas', 'especialidad')->ignore($id)],
            'curso' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $clinica->update([
                'modulo' => $request->modulo,
                'especialidad' => $request->especialidad,
                'curso' => $request->input('curso', -1),
            ]);

            $this->registrarBitacora('ACTUALIZAR_CLINICA', "Clínica {$id} actualizada");

            return $this->responderExito('Clínica actualizada correctamente.', 'clinicas');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function eliminarClinica(int $id)
    {
        try {
            Clinica::findOrFail($id)->delete();
            $this->registrarBitacora('ELIMINAR_CLINICA', "Clínica {$id} eliminada");

            return $this->responderExito('Clínica eliminada.', 'clinicas');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function queryClinicas(Request $request)
    {
        $query = Clinica::query();

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                // MySQL: LIKE
                $q->where('especialidad', 'LIKE', "%{$b}%")
                    ->orWhere('modulo', 'LIKE', "%{$b}%");
            });
        }

        return $query->orderBy('especialidad');
    }

    // ============================================================
    // PERIODOS
    // ============================================================

    public function periodos(Request $request): View
    {
        $periodos = $this->queryPeriodos($request)->paginate(20);

        return view('admin.periodos.index', compact('periodos'));
    }

    public function tablaPeriodos(Request $request): JsonResponse
    {
        $periodos = $this->queryPeriodos($request)->paginate(20);

        return response()->json([
            'filas' => view('admin.periodos.partials.filas', compact('periodos'))->render(),
            'paginacion' => $periodos->hasPages()
                ? '<span class="info">Mostrando ' . $periodos->firstItem() . '–' . $periodos->lastItem()
                . ' de ' . $periodos->total() . ' registros</span>'
                . '<div>' . $periodos->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $periodos->total(),
        ]);
    }

    public function periodoJson(int $id): JsonResponse
    {
        return response()->json(['periodo' => Periodo::findOrFail($id)]);
    }

    public function guardarPeriodo(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglasPeriodo());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            Periodo::create([
                'nombre' => $request->nombre,
                'gestion' => $request->gestion,
                'semestre' => $request->semestre,
                'fecha_inicio' => Carbon::parse($request->fecha_inicio),
                'fecha_fin' => Carbon::parse($request->fecha_fin),
                'habilitado' => $request->boolean('habilitado', true),
            ]);

            $this->registrarBitacora('CREAR_PERIODO', "Periodo {$request->nombre} creado");

            return $this->responderExito('Periodo registrado correctamente.', 'periodos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function actualizarPeriodo(Request $request, int $id)
    {
        $periodo = Periodo::findOrFail($id);

        $validator = Validator::make($request->all(), $this->reglasPeriodo($id));
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $periodo->update([
                'nombre' => $request->nombre,
                'gestion' => $request->gestion,
                'semestre' => $request->semestre,
                'fecha_inicio' => Carbon::parse($request->fecha_inicio),
                'fecha_fin' => Carbon::parse($request->fecha_fin),
                'habilitado' => $request->boolean('habilitado', true),
            ]);

            $this->registrarBitacora('ACTUALIZAR_PERIODO', "Periodo {$id} actualizado");

            return $this->responderExito('Periodo actualizado correctamente.', 'periodos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function togglePeriodo(int $id)
    {
        try {
            $periodo = Periodo::findOrFail($id);
            $periodo->update(['habilitado' => ! $periodo->habilitado]);

            $this->registrarBitacora(
                'TOGGLE_PERIODO',
                "Periodo {$id} " . ($periodo->habilitado ? 'habilitado' : 'deshabilitado')
            );

            return $this->responderExito('Estado del periodo actualizado.', 'periodos');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function reglasPeriodo(?int $id = null): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'gestion' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('periodos')->where(
                    fn($q) => $q->where('semestre', request()->semestre)
                )->ignore($id),
            ],
            'semestre' => 'required|integer|in:1,2',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ];
    }

    private function queryPeriodos(Request $request)
    {
        $query = Periodo::query();

        if ($request->filled('buscar')) {
            // MySQL: LIKE
            $query->where('nombre', 'LIKE', '%' . $request->input('buscar') . '%');
        }
        if ($request->filled('gestion')) {
            $query->where('gestion', $request->input('gestion'));
        }
        if ($request->filled('semestre')) {
            $query->where('semestre', $request->input('semestre'));
        }
        if ($request->filled('habilitado')) {
            $query->where('habilitado', $request->boolean('habilitado'));
        }

        return $query->orderByDesc('gestion')->orderByDesc('semestre');
    }

    // ============================================================
    // ROLES Y PERMISOS
    // ============================================================

    public function roles(): View
    {
        $roles = Rol::withCount(['usuarios', 'permisos'])
            ->orderBy('nombre')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function tablaRoles(Request $request): JsonResponse
    {
        $roles = Rol::withCount(['usuarios', 'permisos'])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'filas' => view('admin.roles.partials.filas', compact('roles'))->render(),
            'paginacion' => '',
            'total' => $roles->count(),
        ]);
    }

    public function rolJson(int $id): JsonResponse
    {
        return response()->json(['rol' => Rol::findOrFail($id)]);
    }

    public function guardarRol(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:200',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $rol = Rol::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'habilitado' => $request->boolean('habilitado', true),
            ]);

            $this->registrarBitacora('CREAR_ROL', "Rol {$rol->id} creado");
            DB::commit();

            return $this->responderExito('Rol creado correctamente.', 'roles');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function actualizarRol(Request $request, int $id)
    {
        $rol = Rol::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('roles', 'nombre')->ignore($id)],
            'descripcion' => 'nullable|string|max:200',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $rol->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'habilitado' => $request->boolean('habilitado', true),
            ]);

            $this->registrarBitacora('ACTUALIZAR_ROL', "Rol {$id} actualizado");
            DB::commit();

            return $this->responderExito('Rol actualizado correctamente.', 'roles');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function eliminarRol(int $id)
    {
        try {
            Rol::findOrFail($id)->update(['habilitado' => false]);
            $this->registrarBitacora('DESHABILITAR_ROL', "Rol {$id} deshabilitado");

            return $this->responderExito('Rol deshabilitado.', 'roles');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    public function permisosRol(int $rolId): View
    {
        $rol = Rol::findOrFail($rolId);

        $permisos = Permiso::orderBy('modulo')
            ->orderBy('codigo')
            ->get();

        $permisosAsignados = $rol->permisos()
            ->wherePivot('habilitado', true)
            ->pluck('permisos.id')
            ->map(fn($i) => (int) $i)
            ->toArray();

        return view('admin.roles.permisos', compact('rol', 'permisos', 'permisosAsignados'));
    }

    public function guardarPermisosRol(Request $request, int $rolId)
    {
        $validator = Validator::make($request->all(), [
            'permisos' => 'array',
            'permisos.*' => 'exists:permisos,id',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $rol = Rol::findOrFail($rolId);

            $syncData = collect($request->input('permisos', []))
                ->mapWithKeys(fn($permisoId) => [
                    (int) $permisoId => ['habilitado' => true],
                ])
                ->toArray();

            $rol->permisos()->sync($syncData);

            $this->registrarBitacora(
                'ACTUALIZAR_PERMISOS_ROL',
                "Permisos del rol {$rolId} actualizados (" . count($syncData) . ' permisos)'
            );

            DB::commit();

            return $this->responderExito('Permisos actualizados correctamente.', null);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    // ============================================================
    // TRATAMIENTOS
    // ============================================================

    public function tratamientos(Request $request): View
    {
        $tratamientos = $this->queryTratamientos($request)->paginate(20);
        $clinicas = Clinica::orderBy('especialidad')->get();

        return view('admin.tratamientos.index', compact('tratamientos', 'clinicas'));
    }

    public function tablaTratamientos(Request $request): JsonResponse
    {
        $tratamientos = $this->queryTratamientos($request)->paginate(20);

        return $this->responderTabla(
            view('admin.tratamientos.partials.filas', compact('tratamientos'))->render(),
            $tratamientos
        );
    }

    public function tratamientoJson(int $id): JsonResponse
    {
        return response()->json(['tratamiento' => Tratamiento::findOrFail($id)]);
    }

    public function guardarTratamiento(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglasTratamiento());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            Tratamiento::create([
                'nombre' => $request->nombre,
                'costo' => $request->costo,
                'porcentaje_pg' => $request->porcentaje_pg,
                'descripcion' => $request->descripcion ?? '',
                'clinica_id' => $request->clinica_id,
            ]);

            $this->registrarBitacora('CREAR_TRATAMIENTO', "Tratamiento {$request->nombre} creado");

            return $this->responderExito('Tratamiento registrado correctamente.', 'tratamientos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function actualizarTratamiento(Request $request, int $id)
    {
        $tratamiento = Tratamiento::findOrFail($id);

        $validator = Validator::make($request->all(), $this->reglasTratamiento());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $tratamiento->update([
                'nombre' => $request->nombre,
                'costo' => $request->costo,
                'porcentaje_pg' => $request->porcentaje_pg,
                'descripcion' => $request->descripcion ?? '',
            ]);

            $this->registrarBitacora('ACTUALIZAR_TRATAMIENTO', "Tratamiento {$id} actualizado");

            return $this->responderExito('Tratamiento actualizado correctamente.', 'tratamientos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function eliminarTratamiento(int $id)
    {
        try {
            Tratamiento::findOrFail($id)->delete();
            $this->registrarBitacora('ELIMINAR_TRATAMIENTO', "Tratamiento {$id} eliminado");

            return $this->responderExito('Tratamiento eliminado.', 'tratamientos');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function reglasTratamiento(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'costo' => 'required|numeric|min:0',
            'clinica_id' => 'required|exists:clinicas,id',
            'porcentaje_pg' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:100',
        ];
    }

    private function queryTratamientos(Request $request)
    {
        $query = Tratamiento::with('clinica');

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->input('clinica_id'));
        }
        if ($request->filled('buscar')) {
            // MySQL: LIKE
            $query->where('nombre', 'LIKE', '%' . $request->input('buscar') . '%');
        }

        return $query->orderBy('nombre');
    }

    // ============================================================
    // CAJAS
    // ============================================================

    public function cajas(Request $request): View
    {
        $cajas = $this->queryCajas($request)->paginate(20);
        $totales = $this->totalesCajas($cajas);
        $tratamientos = Tratamiento::orderBy('nombre')->get();
        $clinicas = Clinica::orderBy('especialidad')->get();

        return view('admin.cajas.index', compact('cajas', 'totales', 'tratamientos', 'clinicas'));
    }

    public function tablaCajas(Request $request): JsonResponse
    {
        $cajas = $this->queryCajas($request)->paginate(20);

        return response()->json([
            'filas' => view('admin.cajas.partials.filas', compact('cajas'))->render(),
            'paginacion' => $cajas->hasPages()
                ? '<span class="info">Mostrando ' . $cajas->firstItem() . '–' . $cajas->lastItem()
                . ' de ' . $cajas->total() . ' registros</span>'
                . '<div>' . $cajas->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $cajas->total(),
            'totales' => $this->totalesCajas($cajas),
        ]);
    }

    public function cajaJson(int $id): JsonResponse
    {
        $caja = Caja::with(['tratamiento', 'clinica'])->findOrFail($id);

        return response()->json([
            'caja' => array_merge($caja->toArray(), [
                'tratamiento' => $caja->tratamiento?->nombre,
                'clinica' => $caja->clinica?->especialidad,
            ]),
        ]);
    }

    public function buscarPacientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'pacientes' => []]);
        }

        $pacientes = Paciente::where(function ($query) use ($q) {
            // MySQL: LIKE en lugar de ILIKE
            $query->where('ci', 'LIKE', "%{$q}%")
                ->orWhere('nombre', 'LIKE', "%{$q}%")
                ->orWhere('apellido_paterno', 'LIKE', "%{$q}%")
                ->orWhere('apellido_materno', 'LIKE', "%{$q}%")
                ->orWhereRaw(
                    "CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, '')) LIKE ?",
                    ["%{$q}%"]
                );
        })
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        $resultado = $pacientes->map(fn($p) => [
            'id' => $p->id,
            'ci' => $p->ci,
            'nombre' => $p->nombre,
            'apellido_paterno' => $p->apellido_paterno,
            'apellido_materno' => $p->apellido_materno,
            'genero' => $p->genero,
            'fecha_nacimiento' => $p->fecha_nacimiento?->format('Y-m-d'),
            'nombre_completo' => $p->nombre_completo,
        ]);

        return response()->json(['success' => true, 'pacientes' => $resultado]);
    }

    public function guardarCaja(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recibo' => 'required|integer|min:1|unique:cajas,recibo',
            'monto' => 'required|integer|min:0',
            'costo_total' => 'required|numeric|min:0',
            'cancelado' => 'required|numeric|min:0|lte:costo_total',
            'tratamiento_id' => 'required|exists:tratamientos,id',
            'clinica_id' => 'required|exists:clinicas,id',
            'usuario_id' => 'required|exists:usuarios,id',
            'paciente_nombre_completo' => 'required|string|max:100',
            'tipo_pago' => 'required|in:Efectivo,QR,Transferencia,Tarjeta',
            'ci' => 'nullable|integer|min:0',
            'remision_id' => 'nullable|exists:remisiones,id',
            'grado' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $caja = Caja::create([
                'recibo' => $request->recibo,
                'monto' => $request->monto,
                'costo_total' => $request->costo_total,
                'cancelado' => $request->cancelado,
                'habilitada' => true,
                'descripcion' => $request->descripcion ?? '',
                'ci' => $request->ci ?? 0,
                'tipo' => $request->tipo ?? '',
                'tipo_pago' => $request->tipo_pago,
                'paciente_nombre_completo' => $request->paciente_nombre_completo,
                'grado' => $request->grado ?? '',
                'tratamiento_id' => $request->tratamiento_id,
                'clinica_id' => $request->clinica_id,
                'remision_id' => $request->remision_id,
                'usuario_id' => $request->usuario_id,
            ]);

            $this->registrarBitacora(
                'CREAR_CAJA',
                "Caja {$caja->id} registrada (recibo #{$request->recibo}, monto Bs {$request->cancelado})"
            );

            DB::commit();

            return $this->responderExito('Registro de caja creado correctamente.', 'cajas');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->responderError('Error al crear caja: ' . $e->getMessage());
        }
    }

    private function totalesCajas($cajas): array
    {
        return [
            'total' => (float) $cajas->sum('costo_total'),
            'cancelado' => (float) $cajas->sum('cancelado'),
            'saldo' => (float) $cajas->sum('costo_total') - (float) $cajas->sum('cancelado'),
        ];
    }

    private function queryCajas(Request $request)
    {
        $query = Caja::with(['tratamiento', 'clinica']);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay(),
            ]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->where('created_at', '>=', Carbon::parse($request->fecha_inicio)->startOfDay());
        } elseif ($request->filled('fecha_fin')) {
            $query->where('created_at', '<=', Carbon::parse($request->fecha_fin)->endOfDay());
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->input('tipo_pago'));
        }

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                // MySQL: LIKE + CAST AS CHAR en lugar de ILIKE + CAST AS TEXT
                $q->where('paciente_nombre_completo', 'LIKE', "%{$b}%")
                    ->orWhereRaw('CAST(recibo AS CHAR) LIKE ?', ["%{$b}%"])
                    ->orWhereRaw('CAST(ci AS CHAR) LIKE ?', ["%{$b}%"]);
            });
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // INSUMOS
    // ============================================================

    public function insumos(Request $request): View
    {
        $insumos = $this->queryInsumos($request)->paginate(20);
        $clinicas = Clinica::orderBy('especialidad')->get();
        $insumoTratamientos = InsumoTratamiento::orderBy('nombre')->get();

        return view('admin.insumos.index', compact('insumos', 'clinicas', 'insumoTratamientos'));
    }

    public function tablaInsumos(Request $request): JsonResponse
    {
        $insumos = $this->queryInsumos($request)->paginate(20);

        return $this->responderTabla(
            view('admin.insumos.partials.filas', compact('insumos'))->render(),
            $insumos
        );
    }

    public function insumoJson(int $id): JsonResponse
    {
        return response()->json(['insumo' => Insumo::findOrFail($id)]);
    }

    public function insumoTratamientosPorClinica(int $clinicaId): JsonResponse
    {
        $items = InsumoTratamiento::where('clinica_id', $clinicaId)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function guardarInsumo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('insumos', 'nombre')
                    ->where(fn($q) => $q->where('clinica_id', $request->clinica_id)),
            ],
            'cantidad_numerica' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'insumo_tratamiento_id' => 'required|exists:insumos_tratamiento,id',
            'clinica_id' => 'required|exists:clinicas,id',
            'cantidad' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            Insumo::create([
                'nombre' => $request->nombre,
                'cantidad_numerica' => $request->cantidad_numerica,
                'cantidad' => $request->cantidad ?? '',
                'stock_minimo' => $request->stock_minimo,
                'costo_unitario' => $request->costo_unitario,
                'insumo_tratamiento_id' => $request->insumo_tratamiento_id,
                'clinica_id' => $request->clinica_id,
            ]);

            $this->registrarBitacora('CREAR_INSUMO', "Insumo {$request->nombre} creado");

            return $this->responderExito('Insumo registrado correctamente.', 'insumos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function actualizarInsumo(Request $request, int $id)
    {
        $insumo = Insumo::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('insumos', 'nombre')
                    ->where(fn($q) => $q->where('clinica_id', $request->clinica_id))
                    ->ignore($id),
            ],
            'cantidad_numerica' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'insumo_tratamiento_id' => 'required|exists:insumos_tratamiento,id',
            'clinica_id' => 'required|exists:clinicas,id',
            'cantidad' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $insumo->update([
                'nombre' => $request->nombre,
                'cantidad_numerica' => $request->cantidad_numerica,
                'cantidad' => $request->cantidad ?? '',
                'stock_minimo' => $request->stock_minimo,
                'costo_unitario' => $request->costo_unitario,
                'insumo_tratamiento_id' => $request->insumo_tratamiento_id,
                'clinica_id' => $request->clinica_id,
            ]);

            $this->registrarBitacora('ACTUALIZAR_INSUMO', "Insumo {$id} actualizado");

            return $this->responderExito('Insumo actualizado correctamente.', 'insumos');
        } catch (\Throwable $e) {
            return $this->responderError('Error: ' . $e->getMessage());
        }
    }

    public function eliminarInsumo(int $id)
    {
        try {
            Insumo::findOrFail($id)->delete();
            $this->registrarBitacora('ELIMINAR_INSUMO', "Insumo {$id} eliminado");

            return $this->responderExito('Insumo eliminado.', 'insumos');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function queryInsumos(Request $request)
    {
        $query = DB::table('insumos as i')
            ->leftJoin('insumos_tratamiento as it', 'it.id', '=', 'i.insumo_tratamiento_id')
            ->join('clinicas as c', 'c.id', '=', 'i.clinica_id')
            ->select('i.*', 'it.nombre as tipo_insumo', 'c.especialidad as clinica');

        if ($request->filled('clinica_id')) {
            $query->where('i.clinica_id', $request->input('clinica_id'));
        }
        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                // MySQL: LIKE
                $q->where('i.nombre', 'LIKE', "%{$b}%")
                    ->orWhere('it.nombre', 'LIKE', "%{$b}%");
            });
        }
        if ($request->filled('bajo_stock')) {
            $query->whereRaw('i.cantidad_numerica <= i.stock_minimo');
        }

        return $query->orderBy('c.especialidad')->orderBy('i.nombre');
    }

    // ============================================================
    // HISTORIAS CLÍNICAS
    // ============================================================

    public function historiasClinicas(Request $request): View
    {
        $historias = $this->queryHistorias($request)->paginate(20);
        $clinicas = Clinica::orderBy('especialidad')->get();
        $periodos = Periodo::orderByDesc('gestion')->get();

        return view('admin.historias.index', compact('historias', 'clinicas', 'periodos'));
    }

    public function tablaHistorias(Request $request): JsonResponse
    {
        $historias = $this->queryHistorias($request)->paginate(20);

        return $this->responderTabla(
            view('admin.historias.partials.filas', compact('historias'))->render(),
            $historias
        );
    }

    public function autorizarHistoria(int $id)
    {
        try {
            $historia = HistoriaClinica::findOrFail($id);
            $historia->update([
                'autorizado' => true,
                'estado_revision' => true,
            ]);

            $this->registrarBitacora('AUTORIZAR_HISTORIA', "Historia clínica {$id} autorizada");

            return $this->responderExito('Historia autorizada.', 'historias');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    private function queryHistorias(Request $request)
    {
        $query = DB::table('historias_clinicas as hc')
            ->join('pacientes as p', 'p.id', '=', 'hc.paciente_id')
            ->join('clinicas as c', 'c.id', '=', 'hc.clinica_id')
            ->join('periodos as pe', 'pe.id', '=', 'hc.periodo_id')
            ->join('usuarios as est', 'est.id', '=', 'hc.estudiante_id')
            ->join('usuarios as doc', 'doc.id', '=', 'hc.docente_id')
            ->select(
                'hc.*',
                'p.nombre as paciente_nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'c.especialidad as clinica',
                'pe.nombre as periodo',
                'est.nombre_completo as estudiante',
                'doc.nombre_completo as docente'
            );

        if ($request->filled('estado_revision')) {
            $query->where('hc.estado_revision', $request->boolean('estado_revision'));
        }
        if ($request->filled('clinica_id')) {
            $query->where('hc.clinica_id', $request->input('clinica_id'));
        }
        if ($request->filled('periodo_id')) {
            $query->where('hc.periodo_id', $request->input('periodo_id'));
        }

        return $query->orderByDesc('hc.created_at');
    }

    // ============================================================
    // BITÁCORAS
    // ============================================================

    public function bitacoras(Request $request): View
    {
        $bitacoras = $this->queryBitacoras($request)->paginate(30);
        $usuarios = Usuario::select('id', 'nombre_completo')->get();
        $tipos = Bitacora::distinct()->pluck('tipo');

        return view('admin.bitacoras.index', compact('bitacoras', 'usuarios', 'tipos'));
    }

    public function tablaBitacoras(Request $request): JsonResponse
    {
        $bitacoras = $this->queryBitacoras($request)->paginate(30);

        return $this->responderTabla(
            view('admin.bitacoras.partials.filas', compact('bitacoras'))->render(),
            $bitacoras
        );
    }

    private function queryBitacoras(Request $request)
    {
        $query = Bitacora::with('usuario');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay(),
            ]);
        }

        return $query->orderByDesc('fecha');
    }

    // ============================================================
    // REPORTES (sin vistas SQL)
    // ============================================================

    public function reportes(): View
    {
        return view('admin.reportes.index');
    }

    /**
     * MySQL: TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())
     * en lugar de EXTRACT(YEAR FROM AGE(CURRENT_DATE, fecha_nacimiento)).
     */
    public function reportePacientesGrupoEtario(): JsonResponse
    {
        $caseEdad = "
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 6  THEN '0-5'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 13 THEN '6-12'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18 THEN '13-17'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 30 THEN '18-29'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 60 THEN '30-59'
                ELSE '60+'
            END
        ";

        $data = DB::table('pacientes')
            ->select(DB::raw("{$caseEdad} AS grupo_etario"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($caseEdad))
            ->orderBy('grupo_etario')
            ->get();

        return response()->json($data);
    }

    public function reporteAfluenciaClinicas(Request $request): JsonResponse
    {
        $query = $this->queryAfluenciaClinicas();

        if ($request->filled('periodo_id')) {
            $query->where('pe.id', $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('total_pacientes')->get());
    }

    public function reporteIngresosPeriodo(Request $request): JsonResponse
    {
        $query = $this->queryCajasPeriodo();

        if ($request->filled('periodo_id')) {
            $query->where('pe.id', $request->input('periodo_id'));
        }

        return response()->json($query->orderByDesc('periodo_id')->get());
    }

    private function queryAfluenciaClinicas()
    {
        return DB::table('remisiones as r')
            ->join('clinicas as c', 'c.id', '=', 'r.clinica_id')
            ->join('periodos as pe', 'pe.id', '=', 'r.periodo_id')
            ->where('r.habilitado', true)
            ->select(
                'pe.id as periodo_id',
                'pe.nombre as periodo',
                'c.id as clinica_id',
                'c.especialidad',
                DB::raw('COUNT(DISTINCT r.paciente_id) as total_pacientes')
            )
            ->groupBy('pe.id', 'pe.nombre', 'c.id', 'c.especialidad');
    }

    private function queryCajasPeriodo()
    {
        return DB::table('cajas as ca')
            ->leftJoin('remisiones as r', 'r.id', '=', 'ca.remision_id')
            ->leftJoin('periodos as pe', 'pe.id', '=', 'r.periodo_id')
            ->select(
                'pe.id as periodo_id',
                'pe.nombre as periodo',
                DB::raw('COUNT(ca.id) as total_recibos'),
                DB::raw('COALESCE(SUM(ca.costo_total), 0) as total_cobrado'),
                DB::raw('COALESCE(SUM(ca.cancelado), 0) as total_cancelado')
            )
            ->groupBy('pe.id', 'pe.nombre');
    }

    // ============================================================
    // RESPUESTAS AJAX REUTILIZABLES
    // ============================================================

    private function responderTabla(string $html, $paginador): JsonResponse
    {
        return response()->json([
            'filas' => $html,
            'paginacion' => $paginador->hasPages()
                ? '<span class="info">Mostrando ' . $paginador->firstItem() . '–' . $paginador->lastItem()
                . ' de ' . $paginador->total() . ' registros</span>'
                . '<div>' . $paginador->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $paginador->total(),
        ]);
    }

    private function responderExito(string $mensaje, ?string $modulo)
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'modulo' => $modulo,
            ]);
        }

        return redirect()->back()->with('success', $mensaje);
    }

    private function responderError(string $mensaje)
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $mensaje,
            ], 500);
        }

        return back()->with('error', $mensaje)->withInput();
    }

    private function responderErrorValidacion($validator)
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return back()->withErrors($validator)->withInput();
    }

    // ============================================================
    // AUXILIAR
    // ============================================================

    private function registrarBitacora(string $tipo, string $datos): void
    {
        try {
            Bitacora::create([
                'usuario_id' => session('user_id'),
                'ip' => request()->ip() ?? '0.0.0.0',
                'fecha' => now(),
                'tipo' => substr($tipo, 0, 20),
                'datos' => $datos,
                'estado' => 'OK',
            ]);
        } catch (\Throwable $e) {
        }
    }
}
