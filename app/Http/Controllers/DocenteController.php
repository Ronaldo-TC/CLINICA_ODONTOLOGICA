<?php

namespace App\Http\Controllers;

use App\Models\Admision;
use App\Models\Autorizacion;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Especialidad;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\InsumoSolicitadoDetalle;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DocenteController extends Controller
{
    /**
     * Ya NO se define middleware en el constructor.
     * Se aplica en las rutas: ->middleware('role:docente,jefe-catedra,jefatura')
     */

    /**
     * Helper: ID del usuario en sesión (compatible con CheckRole)
     */
    private function userId(): int
    {
        return (int) session('user_id');
    }

    /**
     * Helper: parsear booleanos de query string
     */
    private function toBool($value, ?bool $default = null): ?bool
    {
        if ($value === null) return $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Helper: clínicas habilitadas del docente
     */
    private function clinicasDelDocente(): array
    {
        return Especialidad::where('usuario_id', $this->userId())
            ->where('habilitado', true)
            ->pluck('clinica_id')
            ->all();
    }

    /**
     * Helper: registrar bitácora (tipo <= 20 chars)
     */
    private function bitacora(string $tipo, array $datos, Request $request): void
    {
        Bitacora::create([
            'usuario_id' => $this->userId(),
            'ip'         => $request->ip(),
            'tipo'       => substr($tipo, 0, 20),
            'datos'      => json_encode($datos),
            'estado'     => 'exitoso',
        ]);
    }

    // ============================================================
    // 1. DASHBOARD
    // ============================================================
    public function dashboard(Request $request)
    {
        $docenteId = $this->userId();
        $periodoActivo = Periodo::where('habilitado', true)->first();
        $clinicasIds = $this->clinicasDelDocente();

        $stats = [
            'total_pacientes' => HistoriaClinica::whereIn('clinica_id', $clinicasIds)
                ->where('docente_id', $docenteId)
                ->where('periodo_id', $periodoActivo?->id)
                ->count(),
            'historias_pendientes' => HistoriaClinica::whereIn('clinica_id', $clinicasIds)
                ->where('docente_id', $docenteId)
                ->where('autorizado', false)
                ->count(),
            'historias_autorizadas' => HistoriaClinica::whereIn('clinica_id', $clinicasIds)
                ->where('docente_id', $docenteId)
                ->where('autorizado', true)
                ->count(),
            'tratamientos_pendientes' => TratamientoRealizado::whereIn('clinica_id', $clinicasIds)
                ->where('estado', 'pendiente')
                ->whereHas('historiaClinica', fn($q) => $q->where('docente_id', $docenteId))
                ->count(),
            'insumos_pendientes' => InsumoSolicitado::whereIn('clinica_id', $clinicasIds)
                ->where('autorizado', false)
                ->count(),
            'esterilizaciones_pendientes' => Esterilizacion::whereIn('clinica_id', $clinicasIds)
                ->where('habilitada', true)
                ->whereDoesntHave('autorizaciones')
                ->count(),
        ];

        $especialidades = Especialidad::where('usuario_id', $docenteId)
            ->where('habilitado', true)
            ->with('clinica')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'stats'          => $stats,
                'especialidades' => $especialidades,
                'periodo'        => $periodoActivo,
            ]);
        }

        return view('docente.dashboard', compact('stats', 'especialidades', 'periodoActivo'));
    }

    // ============================================================
    // 2. PACIENTES DERIVADOS
    // ============================================================
    public function pacientesDerivados(Request $request)
    {
        $periodoActivo = Periodo::where('habilitado', true)->first();
        $clinicasIds = $this->clinicasDelDocente();

        $query = Remision::whereIn('clinica_id', $clinicasIds)
            ->where('periodo_id', $periodoActivo?->id)
            ->where('habilitado', true)
            ->with(['paciente', 'admision', 'clinica', 'admision.estudiante', 'admision.motivoConsulta']);

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->clinica_id);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'asignado') {
                $query->whereHas('historiasClinicas');
            } elseif ($request->estado === 'pendiente') {
                $query->whereDoesntHave('historiasClinicas');
            }
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('paciente', function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'ILIKE', "%{$buscar}%")
                        ->orWhere('ci', 'ILIKE', "%{$buscar}%");
                });
            });
        }

        $remisiones = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'data'     => $remisiones,
                'clinicas' => Clinica::whereIn('id', $clinicasIds)->get(),
            ]);
        }

        $clinicas = Clinica::whereIn('id', $clinicasIds)->get();
        return view('docente.pacientes-derivados', compact('remisiones', 'clinicas'));
    }

    // ============================================================
    // 3. DESIGNAR PACIENTE
    // ============================================================
    public function designarPaciente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'remision_id'   => 'required|exists:remisiones,id',
            'estudiante_id' => 'required|exists:usuarios,id',
            'clinica_id'    => 'required|exists:clinicas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $remision = Remision::with('admision', 'paciente')->findOrFail($request->remision_id);

            $especialidadDocente = Especialidad::where('usuario_id', $docenteId)
                ->where('clinica_id', $request->clinica_id)
                ->where('habilitado', true)
                ->exists();

            if (! $especialidadDocente) {
                return response()->json(['success' => false, 'message' => 'No tiene especialidad habilitada en esta clínica'], 403);
            }

            $especialidadEstudiante = Especialidad::where('usuario_id', $request->estudiante_id)
                ->where('clinica_id', $request->clinica_id)
                ->where('habilitado', true)
                ->exists();

            if (! $especialidadEstudiante) {
                return response()->json(['success' => false, 'message' => 'El estudiante no está habilitado en esta clínica'], 422);
            }

            $historiaExistente = HistoriaClinica::where('admision_id', $remision->admision_id)
                ->where('clinica_id', $request->clinica_id)
                ->exists();

            if ($historiaExistente) {
                return response()->json(['success' => false, 'message' => 'Ya existe una historia clínica para esta remisión en esta clínica'], 422);
            }

            $historia = HistoriaClinica::create([
                'admision_id'            => $remision->admision_id,
                'paciente_id'            => $remision->paciente_id,
                'clinica_id'             => $request->clinica_id,
                'periodo_id'             => $remision->periodo_id,
                'estado'                 => 'en_proceso',
                'estudiante_id'          => $request->estudiante_id,
                'especialidad_estudiante' => $request->clinica_id,
                'docente_id'             => $docenteId,
                'especialidad_docente'   => $request->clinica_id,
                'fecha_inicio'           => now()->toDateString(),
                'autorizado'             => false,
                'estado_revision'        => false,
            ]);

            $this->bitacora('designacion', [
                'historia_id'    => $historia->id,
                'estudiante_id'  => $request->estudiante_id,
                'paciente_id'    => $remision->paciente_id,
                'clinica_id'     => $request->clinica_id,
            ], $request);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Paciente designado exitosamente',
                'historia' => $historia->load('paciente', 'estudiante'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 4. HISTORIAS CLÍNICAS
    // ============================================================
    public function historiasClinicas(Request $request)
    {
        $docenteId = $this->userId();

        $query = HistoriaClinica::where('docente_id', $docenteId)
            ->with([
                'paciente',
                'admision',
                'clinica',
                'estudiante',
                'tratamientosRealizados.tratamiento',
                'tratamientosRealizados.sesionesAtencion',
            ]);

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->clinica_id);
        }

        if ($request->filled('autorizado')) {
            $query->where('autorizado', $this->toBool($request->autorizado, false));
        }

        if ($request->filled('estado_revision')) {
            $query->where('estado_revision', $this->toBool($request->estado_revision, false));
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('paciente', function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'ILIKE', "%{$buscar}%");
                });
            });
        }

        $historias = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $historias]);
        }

        return view('docente.historias-clinicas', compact('historias'));
    }

    // ============================================================
    // 5. VER HISTORIA
    // ============================================================
    public function verHistoria(Request $request, $id)
    {
        $docenteId = $this->userId();

        $historia = HistoriaClinica::where('docente_id', $docenteId)
            ->with([
                'paciente',
                'admision.motivoConsulta',
                'admision.consentimientos',
                'clinica',
                'estudiante',
                'tratamientosRealizados.tratamiento',
                'tratamientosRealizados.sesionesAtencion.estudiante',
                'tratamientosRealizados.insumosUsados.insumo',
                'tratamientosRealizados.cajaDetalles',
            ])
            ->findOrFail($id);

        $remisiones = Remision::where('admision_id', $historia->admision_id)
            ->with('clinica')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'historia'   => $historia,
                'remisiones' => $remisiones,
            ]);
        }

        return view('docente.ver-historia', compact('historia', 'remisiones'));
    }

    // ============================================================
    // 6. EVALUAR ESTUDIANTE
    // ============================================================
    public function evaluarEstudiante(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'revision_docente' => 'required|string|min:10',
            'estado_revision'  => 'required|boolean',
            'autorizado'       => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $historia = HistoriaClinica::where('docente_id', $docenteId)->findOrFail($id);

            $historia->update([
                'revision_docente'    => $request->revision_docente,
                'estado_revision'     => $request->estado_revision,
                'autorizado'          => $request->autorizado,
                'revision_cualquiera' => $request->boolean('revision_cualquiera'),
            ]);

            $this->bitacora('evaluacion', [
                'historia_id'    => $historia->id,
                'estudiante_id'  => $historia->estudiante_id,
                'autorizado'     => $request->autorizado,
            ], $request);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Evaluación registrada exitosamente',
                'historia' => $historia->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 7. ESTERILIZACIÓN POR ESTUDIANTE
    // ============================================================
    public function verEsterilizacionEstudiante(Request $request, $estudianteId)
    {
        $docenteId = $this->userId();

        $tieneRelacion = HistoriaClinica::where('docente_id', $docenteId)
            ->where('estudiante_id', $estudianteId)
            ->exists();

        if (! $tieneRelacion) {
            return response()->json(['success' => false, 'message' => 'No tiene relación con este estudiante'], 403);
        }

        $esterilizaciones = Esterilizacion::where('estudiante_id', $estudianteId)
            ->with(['clinica', 'periodo', 'autorizaciones'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $esterilizaciones]);
        }

        return view('docente.esterilizacion-estudiante', compact('esterilizaciones', 'estudianteId'));
    }

    // ============================================================
    // 8. AUTORIZAR ESTERILIZACIÓN
    // ============================================================
    public function autorizarEsterilizacion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esterilizacion_id' => 'required|exists:esterilizaciones,id',
            'remision_id'       => 'required|exists:remisiones,id',
            'estado'            => 'required|boolean',
            'descripcion'       => 'nullable|string|max:200',
            'sobre'             => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docente = session('user_id');
            $docenteNombre = \App\Models\Usuario::find($docente)?->nombre_completo ?? '';

            $autorizacion = Autorizacion::updateOrCreate(
                [
                    'esterilizacion_id' => $request->esterilizacion_id,
                    'remision_id'       => $request->remision_id,
                ],
                [
                    'estado'      => $request->estado,
                    'descripcion' => $request->descripcion ?? '',
                    'docente'     => $docenteNombre,
                    'sobre'       => $request->sobre,
                ]
            );

            $this->bitacora('autoriz_esteriliza', $request->all(), $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Autorización registrada exitosamente',
                'data'    => $autorizacion,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 9. VER CAJAS
    // ============================================================
    public function verCajas(Request $request)
    {
        $clinicasIds = $this->clinicasDelDocente();

        $query = Caja::whereIn('clinica_id', $clinicasIds)
            ->with(['tratamiento', 'clinica', 'usuario'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->clinica_id);
        }

        if ($request->filled('habilitada')) {
            $query->where('habilitada', $this->toBool($request->habilitada, true));
        }

        $cajas = $query->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $cajas]);
        }

        return view('docente.cajas', compact('cajas'));
    }

    // ============================================================
    // 10. AUTORIZAR CAJA
    // ============================================================
    public function autorizarCaja(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'habilitada'  => 'required|boolean',
            'observacion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $caja = Caja::findOrFail($id);

            $tieneAcceso = Especialidad::where('usuario_id', $docenteId)
                ->where('clinica_id', $caja->clinica_id)
                ->where('habilitado', true)
                ->exists();

            if (! $tieneAcceso) {
                return response()->json(['success' => false, 'message' => 'No tiene acceso a esta clínica'], 403);
            }

            $caja->update([
                'habilitada'  => $request->habilitada,
                'descripcion' => $request->observacion ?? $caja->descripcion,
            ]);

            $this->bitacora('autoriz_caja', [
                'caja_id'    => $caja->id,
                'habilitada' => $request->habilitada,
            ], $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Caja actualizada exitosamente',
                'data'    => $caja->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 11. VER INSUMOS SOLICITADOS
    // ============================================================
    public function verInsumosSolicitados(Request $request)
    {
        $clinicasIds = $this->clinicasDelDocente();

        $query = InsumoSolicitado::whereIn('clinica_id', $clinicasIds)
            ->with([
                'insumoTratamiento',
                'clinica',
                'remision.paciente',
                'tratamientoRealizado.tratamiento',
                'usuario',
                'detalles.insumo',
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('autorizado')) {
            $query->where('autorizado', $this->toBool($request->autorizado, false));
        }

        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->clinica_id);
        }

        $insumos = $query->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $insumos]);
        }

        return view('docente.insumos-solicitados', compact('insumos'));
    }

    // ============================================================
    // 12. AUTORIZAR INSUMOS
    // ============================================================
    public function autorizarInsumos(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'autorizado'                      => 'required|boolean',
            'docente_autoriza'                => 'required|integer',
            'observacion'                     => 'nullable|string|max:500',
            'detalles'                        => 'nullable|array',
            'detalles.*.id'                   => 'required_with:detalles|exists:insumos_solicitados_detalle,id',
            'detalles.*.cantidad_entregada'   => 'required_with:detalles|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $solicitud = InsumoSolicitado::with('detalles')->findOrFail($id);

            $tieneAcceso = Especialidad::where('usuario_id', $docenteId)
                ->where('clinica_id', $solicitud->clinica_id)
                ->where('habilitado', true)
                ->exists();

            if (! $tieneAcceso) {
                return response()->json(['success' => false, 'message' => 'No tiene acceso a esta clínica'], 403);
            }

            $solicitud->update([
                'autorizado'          => $request->autorizado,
                'docente_id'          => $docenteId,
                'docente_autoriza'    => $request->docente_autoriza,
                'estado'              => $request->autorizado ? 'autorizado' : 'rechazado',
                'fecha_autorizacion'  => now(),
            ]);

            if ($request->filled('detalles') && is_array($request->detalles)) {
                foreach ($request->detalles as $detalleData) {
                    $detalle = InsumoSolicitadoDetalle::find($detalleData['id']);
                    if ($detalle && (int) $detalle->insumo_solicitado_id === (int) $solicitud->id) {
                        $detalle->update([
                            'cantidad_entregada' => $detalleData['cantidad_entregada'] ?? 0,
                            'fecha'              => now()->toDateString(),
                        ]);
                    }
                }
            }

            $this->bitacora('autoriz_insumos', [
                'solicitud_id' => $solicitud->id,
                'autorizado'   => $request->autorizado,
            ], $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->autorizado ? 'Insumos autorizados exitosamente' : 'Solicitud rechazada',
                'data'    => $solicitud->fresh(['detalles.insumo']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 13. REGISTRAR AVANCE
    // ============================================================
    public function registrarAvance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tratamiento_realizado_id' => 'required|exists:tratamientos_realizados,id',
            'fecha'                    => 'required|date',
            'descripcion'              => 'required|string|min:5',
            'estudiante_id'            => 'required|exists:usuarios,id',
            'observacion_docente'      => 'nullable|string',
            'autorizado'               => 'boolean',
            'estado_tratamiento'       => 'nullable|in:pendiente,en_proceso,completado,cancelado',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $tratamiento = TratamientoRealizado::with('historiaClinica')->findOrFail($request->tratamiento_realizado_id);

            if ((int) $tratamiento->historiaClinica->docente_id !== $docenteId) {
                return response()->json(['success' => false, 'message' => 'No es el docente responsable de este tratamiento'], 403);
            }

            $sesion = SesionAtencion::create([
                'tratamiento_realizado_id' => $request->tratamiento_realizado_id,
                'fecha'                    => $request->fecha,
                'descripcion'              => $request->descripcion,
                'estudiante_id'            => $request->estudiante_id,
                'docente_id'               => $docenteId,
                'observacion_docente'      => $request->observacion_docente,
                'autorizado'               => $request->boolean('autorizado'),
            ]);

            if ($request->filled('estado_tratamiento')) {
                $tratamiento->update([
                    'estado'    => $request->estado_tratamiento,
                    'fecha_fin' => $request->estado_tratamiento === 'completado' ? now()->toDateString() : null,
                ]);
            }

            $this->bitacora('avance_tratam', [
                'sesion_id'                => $sesion->id,
                'tratamiento_realizado_id' => $tratamiento->id,
            ], $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Avance registrado exitosamente',
                'data'    => $sesion->load('estudiante', 'docente'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 14. FINALIZAR TRATAMIENTO
    // ============================================================
    public function finalizarTratamiento(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'observacion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $docenteId = $this->userId();
            $tratamiento = TratamientoRealizado::with('historiaClinica')->findOrFail($id);

            if ((int) $tratamiento->historiaClinica->docente_id !== $docenteId) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            $tratamiento->update([
                'estado'      => 'completado',
                'fecha_fin'   => now()->toDateString(),
                'observacion' => $request->observacion ?? $tratamiento->observacion,
            ]);

            $historia = $tratamiento->historiaClinica;
            $todosCompletados = TratamientoRealizado::where('historia_clinica_id', $historia->id)
                ->where('estado', '!=', 'completado')
                ->count() === 0;

            if ($todosCompletados) {
                $historia->update([
                    'estado'    => 'completado',
                    'fecha_fin' => now()->toDateString(),
                ]);
            }

            $this->bitacora('fin_tratamiento', [
                'tratamiento_id' => $tratamiento->id,
            ], $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tratamiento finalizado exitosamente',
                'data'    => $tratamiento->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // 15. REPORTES
    // ============================================================
    public function reportes(Request $request)
    {
        $docenteId = $this->userId();
        $clinicasIds = $this->clinicasDelDocente();

        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        $pacientesPorPeriodo = HistoriaClinica::whereIn('clinica_id', $clinicasIds)
            ->where('docente_id', $docenteId)
            ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
            ->selectRaw('clinica_id, COUNT(*) as total')
            ->groupBy('clinica_id')
            ->with('clinica')
            ->get();

        $tratamientosPorEspecialidad = TratamientoRealizado::whereIn('clinica_id', $clinicasIds)
            ->whereHas('historiaClinica', fn($q) => $q->where('docente_id', $docenteId))
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->selectRaw('clinica_id, COUNT(*) as total, SUM(costo_cobrado) as monto_total')
            ->groupBy('clinica_id')
            ->with('clinica')
            ->get();

        $atencionesPorEstudiante = SesionAtencion::where('docente_id', $docenteId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->selectRaw('estudiante_id, COUNT(*) as total_sesiones')
            ->groupBy('estudiante_id')
            ->with('estudiante')
            ->get();

        $tratamientosPorEstado = TratamientoRealizado::whereIn('clinica_id', $clinicasIds)
            ->whereHas('historiaClinica', fn($q) => $q->where('docente_id', $docenteId))
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        $data = [
            'pacientes_por_periodo'        => $pacientesPorPeriodo,
            'tratamientos_por_especialidad' => $tratamientosPorEspecialidad,
            'atenciones_por_estudiante'    => $atencionesPorEstudiante,
            'tratamientos_por_estado'      => $tratamientosPorEstado,
            'fecha_inicio'                 => $fechaInicio,
            'fecha_fin'                    => $fechaFin,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('docente.reportes', $data);
    }

    // ============================================================
    // 16. ESTUDIANTES POR CLÍNICA (AJAX) - CORREGIDO
    // ============================================================
    public function getEstudiantesPorClinica(Request $request, $clinicaId)
    {
        $estudiantes = Usuario::where(function ($q) {
            $q->where('tipo', 'estudiante')->orWhere('tipo', 'interno');
        })
            ->whereHas('especialidades', function ($q) use ($clinicaId) {
                $q->where('clinica_id', $clinicaId)
                    ->where('habilitado', true);
            })
            ->select('id', 'nombre_completo', 'ci', 'tipo')
            ->orderBy('nombre_completo')
            ->get();

        return response()->json(['success' => true, 'data' => $estudiantes]);
    }

    // ============================================================
    // 17. TRATAMIENTOS POR CLÍNICA (AJAX) - CORREGIDO
    // ============================================================
    public function getTratamientosPorClinica($clinicaId)
    {
        $tratamientos = Tratamiento::where('clinica_id', $clinicaId)
            ->orderBy('nombre')
            ->get();

        return response()->json(['success' => true, 'data' => $tratamientos]);
    }

    // ============================================================
    // 18. DETALLE DE HISTORIA (AJAX)
    // ============================================================
    public function getDetalleHistoria(Request $request, $id)
    {
        $docenteId = $this->userId();

        $historia = HistoriaClinica::where('docente_id', $docenteId)
            ->with([
                'paciente',
                'admision',
                'clinica',
                'estudiante',
                'tratamientosRealizados.tratamiento',
                'tratamientosRealizados.sesionesAtencion',
                'tratamientosRealizados.insumosUsados.insumo',
            ])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $historia]);
    }

    // ============================================================
    // 19. INSUMOS DISPONIBLES (AJAX)
    // ============================================================
    public function getInsumosDisponibles($clinicaId)
    {
        $insumos = Insumo::where('clinica_id', $clinicaId)
            ->where('cantidad_numerica', '>', 0)
            ->with('insumoTratamiento')
            ->orderBy('nombre')
            ->get();

        return response()->json(['success' => true, 'data' => $insumos]);
    }

    // ============================================================
    // 20. VER ESTERILIZACIONES
    // ============================================================
    public function verEsterilizaciones(Request $request)
    {
        $clinicasIds = $this->clinicasDelDocente();

        $query = Esterilizacion::whereIn('clinica_id', $clinicasIds)
            ->with(['estudiante', 'clinica', 'periodo', 'autorizaciones'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->filled('habilitada')) {
            $query->where('habilitada', $this->toBool($request->habilitada, true));
        }

        $esterilizaciones = $query->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $esterilizaciones]);
        }

        return view('docente.esterilizaciones', compact('esterilizaciones'));
    }

    // ============================================================
    // 21. VER RADIOLOGÍAS
    // ============================================================
    public function verRadiologias(Request $request)
    {
        $docenteId = $this->userId();

        $pacientesIds = HistoriaClinica::where('docente_id', $docenteId)
            ->pluck('paciente_id')
            ->unique()
            ->all();

        $query = Radiologia::whereIn('paciente_id', $pacientesIds)
            ->with(['paciente', 'usuario', 'remision', 'caja'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('paciente_id')) {
            $query->where('paciente_id', $request->paciente_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $radiologias = $query->paginate(15);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $radiologias]);
        }

        return view('docente.radiologias', compact('radiologias'));
    }

    // ============================================================
    // 22. BUSCAR PACIENTES (AJAX) - AGRUPADO
    // ============================================================
    public function buscarPacientes(Request $request)
    {
        $buscar = $request->input('q', '');

        if (strlen($buscar) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $pacientes = Paciente::where(function ($q) use ($buscar) {
            $q->where('nombre', 'ILIKE', "%{$buscar}%")
                ->orWhere('apellido_paterno', 'ILIKE', "%{$buscar}%")
                ->orWhere('apellido_materno', 'ILIKE', "%{$buscar}%")
                ->orWhere('ci', 'ILIKE', "%{$buscar}%");
        })
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'data' => $pacientes]);
    }

    // ============================================================
    // 23. RESUMEN CAJA POR PACIENTE (AJAX)
    // ============================================================
    public function getResumenCajaPaciente($pacienteId)
    {
        $clinicasIds = $this->clinicasDelDocente();

        $cajas = Caja::whereIn('clinica_id', $clinicasIds)
            ->whereHas('remision', fn($q) => $q->where('paciente_id', $pacienteId))
            ->with(['tratamiento', 'clinica', 'cajaDetalles'])
            ->get();

        $resumen = [
            'total_cancelado' => $cajas->sum('cancelado'),
            'total_costo'     => $cajas->sum('costo_total'),
            'saldo_pendiente' => $cajas->sum('costo_total') - $cajas->sum('cancelado'),
            'cajas'           => $cajas,
        ];

        return response()->json(['success' => true, 'data' => $resumen]);
    }

    // ============================================================
    // 24. NOTIFICACIONES (AJAX)
    // ============================================================
    public function getNotificaciones(Request $request)
    {
        $docenteId = $this->userId();
        $clinicasIds = $this->clinicasDelDocente();

        $notificaciones = [];

        $historiasPendientes = HistoriaClinica::whereIn('clinica_id', $clinicasIds)
            ->where('docente_id', $docenteId)
            ->where('autorizado', false)
            ->where('estado', '!=', 'completado')
            ->count();

        if ($historiasPendientes > 0) {
            $notificaciones[] = [
                'tipo'    => 'historia',
                'mensaje' => "Tiene {$historiasPendientes} historia(s) clínica(s) pendiente(s) de revisión",
                'url'     => route('docente.historias-clinicas'),
                'count'   => $historiasPendientes,
            ];
        }

        $insumosPendientes = InsumoSolicitado::whereIn('clinica_id', $clinicasIds)
            ->where('autorizado', false)
            ->where('estado', 'pendiente')
            ->count();

        if ($insumosPendientes > 0) {
            $notificaciones[] = [
                'tipo'    => 'insumos',
                'mensaje' => "Tiene {$insumosPendientes} solicitud(es) de insumos pendiente(s)",
                'url'     => route('docente.insumos-solicitados'),
                'count'   => $insumosPendientes,
            ];
        }

        $remisionesNuevas = Remision::whereIn('clinica_id', $clinicasIds)
            ->where('habilitado', true)
            ->whereDoesntHave('historiasClinicas')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($remisionesNuevas > 0) {
            $notificaciones[] = [
                'tipo'    => 'remision',
                'mensaje' => "Hay {$remisionesNuevas} paciente(s) derivado(s) pendiente(s) de asignación",
                'url'     => route('docente.pacientes-derivados'),
                'count'   => $remisionesNuevas,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $notificaciones,
            'total'   => collect($notificaciones)->sum('count'),
        ]);
    }
}
