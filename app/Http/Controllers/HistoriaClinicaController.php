<?php

namespace App\Http\Controllers;

use App\Models\Admision;
use App\Models\Archivo;
use App\Models\Bitacora;
use App\Models\Clinica;
use App\Models\Especialidad;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoriaClinicaController extends Controller
{
    // ============================================================
    // LISTADO PRINCIPAL
    // ============================================================

    public function index(Request $request): View
    {
        $historias = $this->queryHistorias($request)->paginate(20);

        $clinicas = Clinica::orderBy('especialidad')->get();
        $periodos = Periodo::orderByDesc('gestion')->orderByDesc('semestre')->get();
        $docentes = Usuario::whereIn('tipo', ['docente', 'jefatura'])
            ->orderBy('nombre_completo')
            ->get();
        $estudiantes = Usuario::whereIn('tipo', ['estudiante', 'interno'])
            ->orderBy('nombre_completo')
            ->get();

        return view('admin.historias.index', compact(
            'historias',
            'clinicas',
            'periodos',
            'docentes',
            'estudiantes'
        ));
    }

    public function tabla(Request $request): JsonResponse
    {
        $historias = $this->queryHistorias($request)->paginate(20);

        return $this->responderTabla(
            view('admin.historias.partials.filas', compact('historias'))->render(),
            $historias
        );
    }

    public function json(int $id): JsonResponse
    {
        $historia = HistoriaClinica::with([
            'paciente',
            'clinica',
            'periodo',
            'estudiante',
            'docente',
            'tratamientosRealizados.tratamiento',
        ])->findOrFail($id);

        return response()->json([
            'historia' => array_merge($historia->toArray(), [
                'paciente_nombre_completo' => $historia->paciente?->nombre_completo,
                'clinica_nombre'           => $historia->clinica?->especialidad,
                'periodo_nombre'           => $historia->periodo?->nombre,
                'estudiante_nombre'        => $historia->estudiante?->nombre_completo,
                'docente_nombre'           => $historia->docente?->nombre_completo,
                'tratamientos'             => $historia->tratamientosRealizados->map(fn($tr) => [
                    'id'            => $tr->id,
                    'tratamiento'   => $tr->tratamiento?->nombre,
                    'pieza_dental'  => $tr->pieza_dental,
                    'estado'        => $tr->estado,
                    'costo_cobrado' => $tr->costo_cobrado,
                    'fecha_inicio'  => $tr->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin'     => $tr->fecha_fin?->format('Y-m-d'),
                ])->values(),
            ]),
        ]);
    }

    // ============================================================
    // CREAR
    // ============================================================

    public function guardar(Request $request)
    {
        $validator = Validator::make($request->all(), $this->reglasCrear());
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $admision = Admision::where('id', $request->admision_id)
                ->where('paciente_id', $request->paciente_id)
                ->first();

            if (! $admision) {
                return $this->responderError('La admision no corresponde al paciente indicado.');
            }

            $existe = HistoriaClinica::where('paciente_id', $request->paciente_id)
                ->where('clinica_id', $request->clinica_id)
                ->where('periodo_id', $request->periodo_id)
                ->whereIn('estado', ['abierta', 'en_revision'])
                ->exists();

            if ($existe) {
                return $this->responderError(
                    'Ya existe una historia clinica abierta para este paciente en esta especialidad y periodo.'
                );
            }

            $this->validarEspecialidadUsuario($request->estudiante_id, $request->clinica_id);
            $this->validarEspecialidadUsuario($request->docente_id, $request->clinica_id);

            $historia = HistoriaClinica::create([
                'admision_id'             => $admision->id,
                'paciente_id'             => $request->paciente_id,
                'clinica_id'              => $request->clinica_id,
                'periodo_id'              => $request->periodo_id,
                'estado'                  => 'abierta',
                'estudiante_id'           => $request->estudiante_id,
                'especialidad_estudiante' => $request->clinica_id,
                'docente_id'              => $request->docente_id,
                'especialidad_docente'    => $request->clinica_id,
                'autorizado'              => false,
                'estado_revision'         => false,
                'revision_cualquiera'     => false,
                'fecha_inicio'            => $request->fecha_inicio
                    ? Carbon::parse($request->fecha_inicio)
                    : now()->toDateString(),
                'revision_docente'        => '',
            ]);

            if ($request->filled('tratamientos') && is_array($request->tratamientos)) {
                foreach ($request->tratamientos as $tr) {
                    if (empty($tr['tratamiento_id'])) {
                        continue;
                    }

                    TratamientoRealizado::create([
                        'historia_clinica_id' => $historia->id,
                        'tratamiento_id'      => $tr['tratamiento_id'],
                        'clinica_id'          => $request->clinica_id,
                        'pieza_dental'        => $tr['pieza_dental'] ?? null,
                        'estado'              => 'pendiente',
                        'costo_cobrado'       => $tr['costo_cobrado'] ?? 0,
                        'observacion'         => $tr['observacion'] ?? '',
                        'fecha_inicio'        => now()->toDateString(),
                    ]);
                }
            }

            $this->registrarBitacora(
                'CREAR_HISTORIA',
                "HC {$historia->id} creada para paciente {$request->paciente_id} en clinica {$request->clinica_id}"
            );

            DB::commit();

            return $this->responderExito('Historia clinica creada correctamente.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError('Error al crear historia clinica: ' . $e->getMessage());
        }
    }

    // ============================================================
    // ACTUALIZAR
    // ============================================================

    public function actualizar(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        if ($historia->autorizado) {
            return $this->responderError('No se puede editar una historia clinica ya autorizada.');
        }

        $validator = Validator::make($request->all(), $this->reglasActualizar($id));
        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $data = [];

            if ($request->filled('estudiante_id')) {
                $this->validarEspecialidadUsuario($request->estudiante_id, $historia->clinica_id);
                $data['estudiante_id'] = $request->estudiante_id;
            }

            if ($request->filled('docente_id')) {
                $this->validarEspecialidadUsuario($request->docente_id, $historia->clinica_id);
                $data['docente_id'] = $request->docente_id;
            }

            if ($request->filled('fecha_inicio')) {
                $data['fecha_inicio'] = Carbon::parse($request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $data['fecha_fin'] = Carbon::parse($request->fecha_fin);
            }

            if ($request->filled('revision_docente')) {
                $data['revision_docente'] = $request->revision_docente;
            }

            if (! empty($data)) {
                $historia->update($data);
            }

            if ($request->filled('tratamientos') && is_array($request->tratamientos)) {
                $idsRecibidos = collect($request->tratamientos)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                TratamientoRealizado::where('historia_clinica_id', $historia->id)
                    ->whereNotIn('id', $idsRecibidos)
                    ->where('estado', 'pendiente')
                    ->delete();

                foreach ($request->tratamientos as $tr) {
                    if (empty($tr['tratamiento_id'])) {
                        continue;
                    }

                    if (! empty($tr['id'])) {
                        TratamientoRealizado::where('id', $tr['id'])
                            ->where('historia_clinica_id', $historia->id)
                            ->update([
                                'tratamiento_id' => $tr['tratamiento_id'],
                                'pieza_dental'   => $tr['pieza_dental'] ?? null,
                                'costo_cobrado'  => $tr['costo_cobrado'] ?? 0,
                                'observacion'    => $tr['observacion'] ?? '',
                            ]);
                    } else {
                        TratamientoRealizado::create([
                            'historia_clinica_id' => $historia->id,
                            'tratamiento_id'      => $tr['tratamiento_id'],
                            'clinica_id'          => $historia->clinica_id,
                            'pieza_dental'        => $tr['pieza_dental'] ?? null,
                            'estado'              => 'pendiente',
                            'costo_cobrado'       => $tr['costo_cobrado'] ?? 0,
                            'observacion'         => $tr['observacion'] ?? '',
                            'fecha_inicio'        => now()->toDateString(),
                        ]);
                    }
                }
            }

            $this->registrarBitacora('ACTUALIZAR_HISTORIA', "HC {$id} actualizada");
            DB::commit();

            return $this->responderExito('Historia clinica actualizada correctamente.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError('Error al actualizar: ' . $e->getMessage());
        }
    }

    // ============================================================
    // ARCHIVOS ADJUNTOS
    // ============================================================

    public function subirArchivo(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $file = $request->file('archivo');
            $hash = hash_file('sha256', $file->getRealPath());
            $ext = $file->getClientOriginalExtension();
            $nombreGuardado = "hc_{$historia->id}_" . now()->format('YmdHis') . ".{$ext}";
            $ruta = $file->storeAs("historias/{$historia->paciente_id}", $nombreGuardado, 'local');

            Archivo::where('historia_clinica_id', $historia->id)
                ->where('tipo', 'historia_clinica')
                ->update(['tipo' => 'historia_clinica_reemplazada']);

            Archivo::create([
                'paciente_id'         => $historia->paciente_id,
                'historia_clinica_id' => $historia->id,
                'nombre_original'     => $file->getClientOriginalName(),
                'nombre_guardado'     => $nombreGuardado,
                'ruta'                => $ruta,
                'extension'           => $ext,
                'tamano_bytes'        => $file->getSize(),
                'tipo'                => 'historia_clinica',
                'hash'                => $hash,
                'usuario_id'          => session('user_id'),
                'clinica_id'          => $historia->clinica_id,
            ]);

            $historia->update([
                'archivo_nombre' => $file->getClientOriginalName(),
                'archivo'        => $ruta,
                'archivo_hash'   => substr($hash, 0, 50),
            ]);

            $this->registrarBitacora('SUBIR_ARCHIVO_HC', "Archivo subido a HC {$id}: {$nombreGuardado}");
            DB::commit();

            return $this->responderExito('Archivo adjuntado correctamente.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError('Error al subir archivo: ' . $e->getMessage());
        }
    }

    public function descargarArchivo(int $id): StreamedResponse|JsonResponse
    {
        $historia = HistoriaClinica::findOrFail($id);

        if (! $historia->archivo || ! Storage::disk('local')->exists($historia->archivo)) {
            return $this->responderError('El archivo no existe.');
        }

        $this->registrarBitacora('DESCARGAR_ARCHIVO_HC', "Descarga de archivo HC {$id}");

        return Storage::disk('local')->download(
            $historia->archivo,
            $historia->archivo_nombre ?: "historia_{$id}.pdf"
        );
    }

    // ============================================================
    // AUTORIZACION Y REVISION
    // ============================================================

    public function autorizar(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        if ($historia->autorizado) {
            return $this->responderError('La historia ya esta autorizada.');
        }

        $validator = Validator::make($request->all(), [
            'revision_docente' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        $userId = session('user_id');
        if ($userId !== $historia->docente_id && ! $this->esJefatura($userId)) {
            return $this->responderError('Solo el docente asignado o una jefatura puede autorizar esta historia.');
        }

        DB::beginTransaction();
        try {
            $historia->update([
                'autorizado'       => true,
                'estado_revision'  => true,
                'estado'           => 'autorizada',
                'revision_docente' => $request->revision_docente ?? $historia->revision_docente,
            ]);

            $this->registrarBitacora('AUTORIZAR_HISTORIA', "HC {$id} autorizada por usuario {$userId}");
            DB::commit();

            return $this->responderExito('Historia clinica autorizada.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError($e->getMessage());
        }
    }

    public function revisar(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'revision_docente'    => 'required|string|max:2000',
            'estado_revision'     => 'required|boolean',
            'revision_cualquiera' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        try {
            $historia->update([
                'revision_docente'    => $request->revision_docente,
                'estado_revision'     => $request->boolean('estado_revision'),
                'revision_cualquiera' => $request->boolean('revision_cualquiera'),
            ]);

            $this->registrarBitacora('REVISAR_HISTORIA', "HC {$id} revisada");

            return $this->responderExito('Revision registrada.', 'historias');
        } catch (\Throwable $e) {
            return $this->responderError($e->getMessage());
        }
    }

    public function cerrar(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        if (! $historia->autorizado) {
            return $this->responderError('No se puede cerrar una historia no autorizada.');
        }

        $pendientes = TratamientoRealizado::where('historia_clinica_id', $id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->count();

        if ($pendientes > 0) {
            return $this->responderError(
                "No se puede cerrar: hay {$pendientes} tratamiento(s) pendiente(s) o en proceso."
            );
        }

        DB::beginTransaction();
        try {
            $historia->update([
                'estado'    => 'cerrada',
                'fecha_fin' => now()->toDateString(),
            ]);

            $total = TratamientoRealizado::where('historia_clinica_id', $id)->sum('costo_cobrado');

            $this->registrarBitacora(
                'CERRAR_HISTORIA',
                "HC {$id} cerrada. Total cobrado: Bs {$total}"
            );
            DB::commit();

            return $this->responderExito('Historia clinica cerrada.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError($e->getMessage());
        }
    }

    // ============================================================
    // REASIGNACION
    // ============================================================

    public function reasignar(Request $request, int $id)
    {
        $historia = HistoriaClinica::findOrFail($id);

        if ($historia->autorizado) {
            return $this->responderError('No se puede reasignar una historia autorizada.');
        }

        $validator = Validator::make($request->all(), [
            'estudiante_id' => 'nullable|exists:usuarios,id',
            'docente_id'    => 'nullable|exists:usuarios,id',
        ]);

        if ($validator->fails()) {
            return $this->responderErrorValidacion($validator);
        }

        DB::beginTransaction();
        try {
            $data = [];

            if ($request->filled('estudiante_id')) {
                $this->validarEspecialidadUsuario($request->estudiante_id, $historia->clinica_id);
                $data['estudiante_id'] = $request->estudiante_id;
                $data['especialidad_estudiante'] = $historia->clinica_id;
            }

            if ($request->filled('docente_id')) {
                $this->validarEspecialidadUsuario($request->docente_id, $historia->clinica_id);
                $data['docente_id'] = $request->docente_id;
                $data['especialidad_docente'] = $historia->clinica_id;
            }

            if (empty($data)) {
                return $this->responderError('Debe indicar al menos un estudiante o docente.');
            }

            $historia->update($data);

            $this->registrarBitacora(
                'REASIGNAR_HISTORIA',
                "HC {$id} reasignada: " . json_encode($data)
            );
            DB::commit();

            return $this->responderExito('Historia reasignada correctamente.', 'historias');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->responderError($e->getMessage());
        }
    }

    // ============================================================
    // HISTORIAL COMPLETO DE UN PACIENTE
    // ============================================================

    public function historialPaciente(int $pacienteId): JsonResponse
    {
        $paciente = Paciente::findOrFail($pacienteId);

        $historias = HistoriaClinica::with([
            'clinica',
            'periodo',
            'estudiante',
            'docente',
            'tratamientosRealizados.tratamiento',
        ])
            ->where('paciente_id', $pacienteId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'clinica'      => $h->clinica?->especialidad,
                'periodo'      => $h->periodo?->nombre,
                'estado'       => $h->estado,
                'autorizado'   => (bool) $h->autorizado,
                'fecha_inicio' => $h->fecha_inicio?->format('Y-m-d'),
                'fecha_fin'    => $h->fecha_fin?->format('Y-m-d'),
                'estudiante'   => $h->estudiante?->nombre_completo,
                'docente'      => $h->docente?->nombre_completo,
                'tratamientos' => $h->tratamientosRealizados->map(fn($tr) => [
                    'nombre'       => $tr->tratamiento?->nombre,
                    'pieza_dental' => $tr->pieza_dental,
                    'estado'       => $tr->estado,
                    'costo'        => $tr->costo_cobrado,
                ])->values(),
            ]);

        return response()->json([
            'success'  => true,
            'paciente' => [
                'id'               => $paciente->id,
                'ci'               => $paciente->ci,
                'nombre_completo'  => $paciente->nombre_completo,
                'genero'           => $paciente->genero,
                'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
            ],
            'historias' => $historias,
        ]);
    }

    // ============================================================
    // VALIDACIONES AUXILIARES
    // ============================================================

    private function reglasCrear(): array
    {
        return [
            'admision_id'                   => 'required|exists:admisiones,id',
            'paciente_id'                   => 'required|exists:pacientes,id',
            'clinica_id'                    => 'required|exists:clinicas,id',
            'periodo_id'                    => 'required|exists:periodos,id',
            'estudiante_id'                 => 'required|exists:usuarios,id',
            'docente_id'                    => 'required|exists:usuarios,id',
            'fecha_inicio'                  => 'nullable|date',
            'tratamientos'                  => 'nullable|array',
            'tratamientos.*.tratamiento_id' => 'required_with:tratamientos|exists:tratamientos,id',
            'tratamientos.*.pieza_dental'   => 'nullable|string|max:20',
            'tratamientos.*.costo_cobrado'  => 'nullable|numeric|min:0',
            'tratamientos.*.observacion'    => 'nullable|string|max:500',
        ];
    }

    private function reglasActualizar(int $id): array
    {
        return [
            'estudiante_id'                 => 'nullable|exists:usuarios,id',
            'docente_id'                    => 'nullable|exists:usuarios,id',
            'fecha_inicio'                  => 'nullable|date',
            'fecha_fin'                     => 'nullable|date|after_or_equal:fecha_inicio',
            'revision_docente'              => 'nullable|string|max:2000',
            'tratamientos'                  => 'nullable|array',
            'tratamientos.*.id'             => 'nullable|exists:tratamientos_realizados,id',
            'tratamientos.*.tratamiento_id' => 'required_with:tratamientos|exists:tratamientos,id',
        ];
    }

    private function validarEspecialidadUsuario(int $usuarioId, int $clinicaId): void
    {
        $existe = Especialidad::where('usuario_id', $usuarioId)
            ->where('clinica_id', $clinicaId)
            ->where('habilitado', true)
            ->exists();

        if (! $existe) {
            throw new \RuntimeException(
                "El usuario {$usuarioId} no esta habilitado en la especialidad {$clinicaId}."
            );
        }
    }

    private function esJefatura(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->where('ur.usuario_id', $userId)
            ->whereIn('r.nombre', ['jefatura', 'director', 'admin', 'subadmin'])
            ->where('r.habilitado', true)
            ->exists();
    }

    // ============================================================
    // QUERY CON FILTROS
    // ============================================================

    private function queryHistorias(Request $request)
    {
        $query = HistoriaClinica::with(['paciente', 'clinica', 'periodo', 'estudiante', 'docente']);

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->whereHas('paciente', function ($qq) use ($b) {
                    $qq->where('ci', 'LIKE', "%{$b}%")
                        ->orWhere('nombre', 'LIKE', "%{$b}%")
                        ->orWhere('apellido_paterno', 'LIKE', "%{$b}%")
                        ->orWhere('apellido_materno', 'LIKE', "%{$b}%");
                });
            });
        }

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->input('clinica_id'));
        }

        if ($request->filled('periodo_id')) {
            $query->where('periodo_id', $request->input('periodo_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('autorizado')) {
            $query->where('autorizado', $request->boolean('autorizado'));
        }

        if ($request->filled('estado_revision')) {
            $query->where('estado_revision', $request->boolean('estado_revision'));
        }

        if ($request->filled('docente_id')) {
            $query->where('docente_id', $request->input('docente_id'));
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->input('estudiante_id'));
        }

        return $query->orderByDesc('created_at');
    }

    // ============================================================
    // RESPUESTAS REUTILIZABLES
    // ============================================================

    private function responderTabla(string $html, $paginador): JsonResponse
    {
        return response()->json([
            'filas'      => $html,
            'paginacion' => $paginador->hasPages()
                ? '<span class="info">Mostrando ' . $paginador->firstItem() . ' - ' . $paginador->lastItem()
                . ' de ' . $paginador->total() . ' registros</span>'
                . '<div>' . $paginador->withQueryString()->links()->toHtml() . '</div>'
                : '',
            'total' => $paginador->total(),
        ]);
    }

    private function responderExito(string $mensaje, ?string $modulo = null)
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'modulo'  => $modulo,
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
            ], 422);
        }

        return back()->with('error', $mensaje)->withInput();
    }

    private function responderErrorValidacion($validator)
    {
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        return back()->withErrors($validator)->withInput();
    }

    private function registrarBitacora(string $tipo, string $datos): void
    {
        try {
            Bitacora::create([
                'usuario_id' => session('user_id'),
                'ip'         => request()->ip() ?? '0.0.0.0',
                'fecha'      => now(),
                'tipo'       => substr($tipo, 0, 50),
                'datos'      => $datos,
                'estado'     => 'OK',
            ]);
        } catch (\Throwable $e) {
            // Silencioso
        }
    }
}
