<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EspecialidadController extends Controller
{
    /* ============================================================
       LISTADO PRINCIPAL
       ============================================================ */
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $clinicaId = $request->input('clinica_id');
        $estado = $request->input('estado');

        $asignaciones = DB::table('especialidades as e')
            ->join('usuarios as u', 'u.id', '=', 'e.usuario_id')
            ->join('clinicas as c', 'c.id', '=', 'e.clinica_id')
            ->select(
                'e.usuario_id',
                'e.clinica_id',
                'e.habilitado',
                'e.rotacion',
                'e.responsable_admision',
                'e.fecha_inicio',
                'u.nombre_completo',
                'u.ci',
                'c.especialidad',
                'c.modulo',
                'c.curso'
            )
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($qq) use ($buscar) {
                    $qq->where('u.nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('u.ci', 'like', "%{$buscar}%");
                });
            })
            ->when(! empty($clinicaId), fn ($q) => $q->where('e.clinica_id', (int) $clinicaId))
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('e.habilitado', (int) $estado))
            ->orderBy('u.nombre_completo')
            ->orderBy('c.especialidad')
            ->paginate(20)
            ->withQueryString();

        $usuarios = Usuario::query()
            ->where('habilitado', true)
            ->where('permitir_login', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'estudiante',
                'interno',
                'docente',
                'jefe-catedra',
            ]))
            ->with('roles:id,name')
            ->orderBy('nombre_completo')
            ->get(['id', 'ci', 'nombre_completo']);

        $clinicas = Clinica::query()
            ->orderBy('modulo')
            ->orderBy('especialidad')
            ->get(['id', 'modulo', 'especialidad', 'curso']);

        return view('especialidades.index', compact(
            'asignaciones',
            'usuarios',
            'clinicas',
            'buscar',
            'clinicaId',
            'estado'
        ));
    }

    public function misInscripciones(Request $request): View
    {
        $usuario = $request->user();

        $inscripciones = $usuario->especialidades()
            ->withPivot(['habilitado', 'rotacion', 'fecha_inicio'])
            ->orderBy('clinicas.especialidad')
            ->get(['clinicas.id', 'clinicas.especialidad', 'clinicas.modulo'])
            ->map(function ($clinica) {
                $pivot = $clinica->pivot;

                return [
                    'id' => $clinica->id,
                    'nombre_completo' => auth()->user()->nombre_completo,
                    'especialidad' => $clinica->especialidad,
                    'fecha_inscripcion' => $pivot?->fecha_inicio
                        ? Carbon::parse($pivot->fecha_inicio)->format('d-m-Y H:i:s a')
                        : '—',
                    'fecha_baja' => $pivot?->habilitado ? '—' : 'Sin baja registrada',
                ];
            });

        return view('especialidades.mis-inscripciones', [
            'usuario' => $usuario,
            'inscripciones' => $inscripciones,
        ]);
    }

    /* ============================================================
       TABLA VÍA AJAX
       ============================================================ */
    public function tabla(Request $request): JsonResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $clinicaId = $request->input('clinica_id');
        $estado = $request->input('estado');

        $asignaciones = DB::table('especialidades as e')
            ->join('usuarios as u', 'u.id', '=', 'e.usuario_id')
            ->join('clinicas as c', 'c.id', '=', 'e.clinica_id')
            ->select(
                'e.usuario_id',
                'e.clinica_id',
                'e.habilitado',
                'e.rotacion',
                'e.responsable_admision',
                'e.fecha_inicio',
                'u.nombre_completo',
                'u.ci',
                'c.especialidad',
                'c.modulo',
                'c.curso'
            )
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($qq) use ($buscar) {
                    $qq->where('u.nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('u.ci', 'like', "%{$buscar}%");
                });
            })
            ->when(! empty($clinicaId), fn ($q) => $q->where('e.clinica_id', (int) $clinicaId))
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('e.habilitado', (int) $estado))
            ->orderBy('u.nombre_completo')
            ->orderBy('c.especialidad')
            ->paginate(20)
            ->withQueryString();

        $filas = view('especialidades.partials.filas', compact('asignaciones'))->render();
        $paginacion = $asignaciones->links()->toHtml();
        $total = $asignaciones->total();

        return response()->json([
            'html' => $filas,
            'filas' => $filas,
            'paginacion' => $paginacion,
            'total' => $total,
        ]);
    }

    /* ============================================================
       CREAR ASIGNACIONES
       ============================================================ */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'clinica_ids' => ['required', 'array', 'min:1'],
            'clinica_ids.*' => ['required', 'integer', 'distinct', 'exists:clinicas,id'],
            'rotacion' => ['required', 'integer', 'between:-1,100'],
            'fecha_inicio' => ['nullable', 'date'],
            'responsable_admision' => ['nullable', 'boolean'],
        ]);

        $usuario = Usuario::with('roles')->findOrFail($data['usuario_id']);

        abort_unless(
            $usuario->habilitado && $usuario->hasAnyRole(['estudiante', 'interno', 'docente', 'jefe-catedra']),
            422,
            'Seleccione un estudiante, interno o docente habilitado.'
        );

        $responsable = $request->boolean('responsable_admision');
        abort_if(
            $responsable && ! $usuario->hasAnyRole(['docente', 'jefe-catedra']),
            422,
            'El responsable de admisión debe ser docente o jefe de cátedra.'
        );

        DB::transaction(function () use ($usuario, $data, $responsable) {
            foreach ($data['clinica_ids'] as $clinicaId) {
                $usuario->especialidades()->syncWithoutDetaching([
                    $clinicaId => [
                        'habilitado' => true,
                        'rotacion' => $data['rotacion'],
                        'fecha_inicio' => $data['fecha_inicio'] ?? null,
                        'responsable_admision' => $responsable,
                    ],
                ]);
            }
        });

        $message = 'Especialidades asignadas correctamente.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'total' => count($data['clinica_ids']),
            ]);
        }

        return back()->with('success', $message);
    }

    /* ============================================================
       ACTUALIZAR
       ============================================================ */
    public function update(Request $request, int $usuario, int $clinica): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'habilitado' => ['required', 'boolean'],
        ]);

        $persona = Usuario::findOrFail($usuario);

        abort_unless(
            $persona->especialidades()->where('clinicas.id', $clinica)->exists(),
            404,
            'La asignación no existe.'
        );

        DB::transaction(function () use ($persona, $clinica, $data) {
            $persona->especialidades()->updateExistingPivot($clinica, [
                'habilitado' => (bool) $data['habilitado'],
            ]);
        });

        $message = 'Asignación actualizada. Las fichas anteriores se conservan.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /* ============================================================
       BULK UPDATE
       ============================================================ */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'clinica_ids' => ['required', 'array', 'min:1'],
            'clinica_ids.*' => ['required', 'integer', 'exists:clinicas,id'],
            'habilitado' => ['required', 'boolean'],
        ]);

        $usuario = Usuario::findOrFail($data['usuario_id']);
        $estado = (bool) $data['habilitado'];

        $afectadas = DB::transaction(function () use ($usuario, $data, $estado) {
            $total = 0;
            foreach ($data['clinica_ids'] as $clinicaId) {
                if ($usuario->especialidades()->where('clinicas.id', $clinicaId)->exists()) {
                    $usuario->especialidades()->updateExistingPivot($clinicaId, [
                        'habilitado' => $estado,
                    ]);
                    $total++;
                }
            }

            return $total;
        });

        return response()->json([
            'success' => true,
            'message' => "Se actualizaron {$afectadas} asignaciones.",
            'total' => $afectadas,
        ]);
    }

    /* ============================================================
       ELIMINAR ASIGNACIÓN
       ============================================================ */
    public function destroy(int $usuario, int $clinica): JsonResponse
    {
        $persona = Usuario::findOrFail($usuario);

        if (! $persona->especialidades()->where('clinicas.id', $clinica)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La asignación no existe.',
            ], 404);
        }

        $persona->especialidades()->detach($clinica);

        return response()->json([
            'success' => true,
            'message' => 'Asignación eliminada.',
        ]);
    }

    /* ============================================================
       DATOS PARA MODAL
       ============================================================ */
    public function datos(int $usuario, int $clinica): JsonResponse
    {
        $row = DB::table('especialidades')
            ->where('usuario_id', $usuario)
            ->where('clinica_id', $clinica)
            ->first();

        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'usuario_id' => $row->usuario_id,
                'clinica_id' => $row->clinica_id,
                'habilitado' => (bool) $row->habilitado,
                'rotacion' => (int) $row->rotacion,
                'responsable_admision' => (bool) $row->responsable_admision,
                'fecha_inicio' => $row->fecha_inicio,
            ],
        ]);
    }

    /* ============================================================
   BUSCAR USUARIOS (AJAX para autocompletado del modal)
   ============================================================ */
    public function buscarUsuarios(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $usuarios = Usuario::query()
            ->where('habilitado', true)
            ->where('permitir_login', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                'estudiante',
                'interno',
                'docente',
                'jefe-catedra',
            ]))
            ->where(function ($query) use ($q) {
                $query->where('nombre_completo', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%")
                    ->orWhere('nombre_usuario', 'like', "%{$q}%");
            })
            ->with('roles:id,name')
            ->orderBy('nombre_completo')
            ->limit(15)
            ->get(['id', 'ci', 'nombre_completo', 'nombre_usuario']);

        $results = $usuarios->map(function ($u) {
            return [
                'id' => $u->id,
                'ci' => $u->ci,
                'nombre_completo' => $u->nombre_completo,
                'nombre_usuario' => $u->nombre_usuario,
                'roles' => $u->roles->pluck('name')->implode(', '),
                'initials' => collect(explode(' ', trim($u->nombre_completo)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->implode(''),
            ];
        });

        return response()->json(['results' => $results]);
    }
}
