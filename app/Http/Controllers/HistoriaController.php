<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Http\Resources\HistoriaResource;
use App\Models\Admision;
use App\Models\Archivo;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Especialidad;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\InsumoSolicitado;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\Usuario;
use App\Services\AccesoClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HistoriaController extends Controller
{
    use HasCrudAjax;

    private const ESTADOS_CERRADOS = ['cerrada', 'finalizada', 'culminada', 'completada', 'cancelada', 'completado', 'culminado', 'cancelado'];

    protected string $modelClass = HistoriaClinica::class;

    protected string $viewPrefix = 'historias';

    protected string $resourceName = 'Historia Clínica';

    protected string $pluralName = 'Historias Clínicas';

    protected string $permissionPrefix = 'historias';

    protected int $perPage = 15;

    protected function buildIndexQuery(Request $request): Builder
    {
        return AccesoClinico::historias(HistoriaClinica::query(), $request->user())->with([
            'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
            'clinica:id,especialidad,modulo',
            'periodo:id,nombre,gestion,semestre',
            'estudiante:id,nombre_completo',
            'docente:id,nombre_completo',
        ])->withCount('tratamientosRealizados')->orderByDesc('id');
    }

    public function show(Request $request, int $historia): View|JsonResponse
    {
        $this->authorizeAction('ver');
        $registro = $this->buildIndexQuery($request)->findOrFail($historia);
        if ($this->esAjax()) {
            return response()->json(['success' => true, 'registro' => $registro]);
        }

        $usuario = $request->user();
        $registro->load(['tratamientosRealizados.tratamiento', 'tratamientosRealizados.autorizador']);
        $tratamientoIds = $registro->tratamientosRealizados->modelKeys();
        $cerrada = in_array($registro->estado, self::ESTADOS_CERRADOS, true) || $registro->fecha_fin !== null;
        $puedeAutorizarTratamientos = $registro->autorizado && ! $cerrada
            && $usuario->canAny(['atenciones.autorizar', 'atenciones.gestionar'])
            && (AccesoClinico::esAdministrador($usuario)
                || ($usuario->hasAnyRole(['docente', 'jefe-catedra']) && (int) $registro->docente_id === $usuario->id
                    && Especialidad::query()->where('usuario_id', $usuario->id)->where('clinica_id', $registro->clinica_id)->where('habilitado', true)->exists()));
        $solicitudes = $usuario->canAny(['insumos.ver', 'insumos.gestionar'])
            ? AccesoClinico::solicitudes(InsumoSolicitado::query(), $usuario)
                ->where(function (Builder $query) use ($registro, $tratamientoIds): void {
                    $query->where('historia_clinica_id', $registro->id)
                        ->orWhere(fn (Builder $legacy) => $legacy->whereNull('historia_clinica_id')->whereIn('tratamiento_realizado_id', $tratamientoIds));
                })
                ->with(['estudiante', 'historiaClinica', 'tratamiento', 'tratamientoRealizado.tratamiento', 'tratamientoRealizado.historiaClinica',
                    'sesionAtencion', 'detalles.insumo', 'docente', 'encargado'])
                ->withCount('entregas')->orderByDesc('id')->get()
            : collect();
        $pagos = $usuario->canAny(['cajas.ver', 'cajas.gestionar'])
            ? Caja::query()->where('habilitada', true)
                ->where(fn (Builder $query) => $query->whereIn('tratamiento_realizado_id', $tratamientoIds)
                    ->orWhereHas('detalles', fn (Builder $detalle) => $detalle->whereIn('tratamiento_realizado_id', $tratamientoIds)))
                ->with(['tratamiento', 'detalles' => fn (HasMany $detalles) => $detalles->whereIn('tratamiento_realizado_id', $tratamientoIds),
                    'detalles.tratamientoRealizado.tratamiento'])
                ->orderByDesc('id')->get()
            : collect();
        $esterilizaciones = $usuario->canAny(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
            ? Esterilizacion::query()->where('estudiante_id', $registro->estudiante_id)->where('clinica_id', $registro->clinica_id)
                ->where('periodo_id', $registro->periodo_id)->where('habilitada', true)
                ->when(! AccesoClinico::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra']), fn (Builder $query) => $query->where('docente_id', $usuario->id))
                ->where(function (Builder $query) use ($registro): void {
                    $query->whereNull('remision_id');
                    if ($registro->remision_id) {
                        $query->orWhere('remision_id', $registro->remision_id);
                    }
                })->orderByDesc('id')->get()
            : collect();

        return view('historias.show', compact('registro', 'solicitudes', 'pagos', 'esterilizaciones', 'puedeAutorizarTratamientos'));
    }

    protected function buildTablaQuery(Request $request): Builder
    {
        $query = $this->buildIndexQuery($request);
        if ($buscar = trim((string) $request->input('buscar'))) {
            $query->whereHas('paciente', function ($paciente) use ($buscar): void {
                $paciente->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%");
            });
        }
        foreach (['clinica_id', 'periodo_id'] as $campo) {
            if ($request->filled($campo)) {
                $query->where($campo, $request->integer($campo));
            }
        }

        return $query;
    }

    protected function extraIndexData(Request $request): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    protected function validationRules(?int $id): array
    {
        return [
            'admision_id' => ['required', 'integer', 'exists:admisiones,id'],
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'estudiante_id' => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_estudiante' => ['required', 'integer', 'exists:clinicas,id'],
            'docente_id' => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_docente' => ['required', 'integer', 'exists:clinicas,id'],
            'estado' => ['nullable', 'string', 'max:50'],
            'archivo_nombre' => ['nullable', 'string', 'max:100'],
            'autorizado' => ['nullable', 'boolean'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    protected function beforeStore(array $data): array
    {
        $this->validarAsignacion($data);
        foreach (['estado', 'archivo_nombre'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        $data['autorizado'] = false;
        $data['estado_revision'] = 0;
        $data['revision_cualquiera'] = 0;

        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        $this->validarAsignacion($data);
        foreach (['admision_id', 'paciente_id', 'clinica_id', 'periodo_id', 'estudiante_id', 'especialidad_estudiante', 'docente_id', 'especialidad_docente'] as $campo) {
            if ((string) $model->$campo !== (string) $data[$campo]) {
                throw ValidationException::withMessages([$campo => 'La asignación clínica se administra desde la remisión.']);
            }
        }
        foreach (['estado', 'archivo_nombre'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        unset($data['autorizado']);

        return $data;
    }

    protected function findOrFail($id): Model
    {
        return AccesoClinico::historias(HistoriaClinica::query(), request()->user())->findOrFail($id);
    }

    protected function canDestroy($model): array
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'subadmin']), 403);

        return $model->autorizado || $model->archivos()->exists() || $model->tratamientosRealizados()->exists()
            ? [false, 'La historia tiene registros clínicos y debe conservarse.'] : [true, ''];
    }

    private function validarAsignacion(array $data): void
    {
        $admision = AccesoClinico::admisiones(Admision::query(), request()->user())->findOrFail($data['admision_id']);
        if ((int) $admision->paciente_id !== (int) $data['paciente_id']) {
            throw ValidationException::withMessages(['paciente_id' => 'El paciente debe corresponder a la admisión.']);
        }
        if (AccesoClinico::esEstudiante(request()->user())) {
            abort_unless((int) $data['estudiante_id'] === request()->user()->id, 403);
        }
        foreach (['estudiante' => ['estudiante', 'interno'], 'docente' => ['docente', 'jefe-catedra']] as $tipo => $roles) {
            $persona = Usuario::findOrFail($data[$tipo.'_id']);
            if (! $persona->habilitado || ! $persona->hasAnyRole($roles) || (int) $data['especialidad_'.$tipo] !== (int) $data['clinica_id'] || ! $persona->especialidades()->where('clinicas.id', $data['clinica_id'])->wherePivot('habilitado', true)->exists()) {
                throw ValidationException::withMessages([$tipo.'_id' => 'El estudiante y docente deben estar asignados a la especialidad de la historia.']);
            }
        }
        abort_unless(Remision::where('admision_id', $admision->id)->where('clinica_id', $data['clinica_id'])->where('estudiante_id', $data['estudiante_id'])->where('docente_id', $data['docente_id'])->where('habilitado', true)->exists(), 422, 'Asigne primero al estudiante y docente desde la remisión.');
    }

    public function autorizar(Request $request, int $historia): RedirectResponse
    {
        $registro = $this->findOrFail($historia);
        abort_unless($request->user()->hasAnyRole(['admin', 'subadmin']) || ($request->user()->hasAnyRole(['docente', 'jefe-catedra']) && (int) $registro->docente_id === $request->user()->id), 403);
        $registro->update(['autorizado' => true, 'estado_revision' => true, 'estado' => 'abierta']);

        return back()->with('success', 'Historia autorizada para proponer tratamientos.');
    }

    /* ============================================================
       ENDPOINTS AJAX (opcionales)
       ============================================================ */

    public function buscarAdmisiones(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $admisiones = AccesoClinico::admisiones(Admision::query(), $request->user())->with('paciente:id,ci,nombre,apellido_paterno,apellido_materno')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('paciente', function ($p) use ($q) {
                    $p->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'paciente_id' => $a->paciente_id,
                'texto' => $a->paciente?->nombre_completo ?? "Admisión #{$a->id}",
                'extra' => 'CI: '.($a->paciente?->ci ?? '—'),
            ]);

        return response()->json($admisiones);
    }

    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'docente' => ['docente', 'jefe-catedra'],
            default => [],
        };

        if (empty($roles)) {
            return response()->json([]);
        }

        $usuarios = Usuario::where('habilitado', 1)
            ->whereHas('roles', fn ($r) => $r->whereIn('name', $roles))
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre_completo', 'like', "%{$q}%");
            })
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'ci', 'nombre_completo'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'texto' => $u->nombre_completo,
                'extra' => $u->ci ? "CI: {$u->ci}" : '',
            ]);

        return response()->json($usuarios);
    }

    public function datosIniciales()
    {
        $this->authorizeAction('ver');

        return response()->json([
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad'])
                ->map(fn ($c) => ['id' => $c->id, 'texto' => $c->especialidad]),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre'])
                ->map(fn ($p) => ['id' => $p->id, 'texto' => "{$p->nombre} ({$p->gestion}-{$p->semestre})"]),
        ]);
    }

    /* ============================================================
       ARCHIVOS ADJUNTOS DE LA HISTORIA CLÍNICA
       ============================================================ */

    /**
     * GET /historias/{id}/archivos
     * Lista los adjuntos registrados (JSON).
     */
    public function archivos($id): JsonResponse
    {
        $this->authorizeAction('ver');

        $historia = $this->findOrFail($id);

        return response()->json([
            'success' => true,
            'archivos' => $historia->archivos()
                ->orderByDesc('id')
                ->get()
                ->map(fn (Archivo $a) => $this->formatearArchivo($a)),
        ]);
    }

    /**
     * POST /historias/{id}/archivos
     * Sube un PDF/JPG/PNG (máx. 10 MB). Guarda el binario en la base (columna
     * `archivo`) para no depender del enlace simbólico de storage, y registra
     * la fila en `archivos` con hash SHA-256 para evitar duplicados.
     */
    public function subirArchivo(Request $request, $id): JsonResponse
    {
        $this->authorizeAction('editar');

        $historia = $this->findOrFail($id)->load('paciente');

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'archivo.required' => 'Debe seleccionar un archivo.',
            'archivo.mimes' => 'Sólo se permiten archivos PDF, JPG o PNG.',
            'archivo.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        $file = $request->file('archivo');

        $contenido = file_get_contents($file->getRealPath());
        $hash = hash('sha256', $contenido);
        $bytes = strlen($contenido);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        // ¿Ya existe este mismo archivo en la historia?
        $existente = $historia->archivos()->where('hash', $hash)->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Este archivo ya fue cargado previamente.',
                'archivo' => $this->formatearArchivo($existente),
            ], 422);
        }

        $pacienteId = $historia->paciente_id ?: 0;
        $nombreGuardado = "historia_{$historia->id}_{$hash}.{$extension}";
        $ruta = "patients/{$pacienteId}/{$nombreGuardado}";

        DB::beginTransaction();

        try {
            // Copia en disco (si el almacenamiento está disponible)
            try {
                Storage::disk('local')->put($ruta, $contenido);
            } catch (\Throwable $e) {
                report($e);
            }

            $archivo = Archivo::create([
                'paciente_id' => $historia->paciente_id,
                'historia_clinica_id' => $historia->id,
                'nombre_original' => $file->getClientOriginalName(),
                'nombre_guardado' => $nombreGuardado,
                'ruta' => $ruta,
                'extension' => $extension,
                'tamano_bytes' => $bytes,
                'tipo' => $file->getMimeType(),
                'hash' => $hash,
                'usuario_id' => auth()->id() ?? session('user_id'),
                'clinica_id' => $historia->clinica_id,
            ]);

            // El binario también queda en la historia (respaldo en la BD)
            $historia->update([
                'archivo' => base64_encode($contenido),
                'archivo_nombre' => mb_substr((string) $file->getClientOriginalName(), 0, 100),
                'archivo_hash' => substr($hash, 0, 50),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Archivo cargado correctamente.',
                'archivo' => $this->formatearArchivo($archivo),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->environment('production')
                    ? 'No se pudo guardar el archivo.'
                    : 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /historias/{id}/archivos/{archivo}
     * Descarga o previsualiza el adjunto.
     */
    public function descargarArchivo($id, $archivo)
    {
        $this->authorizeAction('ver');
        $this->findOrFail($id);

        $registro = Archivo::where('historia_clinica_id', $id)->findOrFail($archivo);

        $contenido = null;

        if ($registro->ruta && Storage::disk('local')->exists($registro->ruta)) {
            $contenido = Storage::disk('local')->get($registro->ruta);
        } elseif ($registro->ruta && Storage::disk('public')->exists($registro->ruta)) {
            $contenido = Storage::disk('public')->get($registro->ruta);
        }

        if ($contenido === null) {
            $historia = HistoriaClinica::find($id);
            $contenido = $historia?->archivo ? base64_decode($historia->archivo) : null;
        }

        abort_if($contenido === null, 404, 'El archivo no está disponible.');

        return response($contenido)
            ->header('Content-Type', $registro->tipo ?: 'application/octet-stream')
            ->header('Content-Disposition', 'inline; filename="'.($registro->nombre_original ?: 'archivo').'"');
    }

    /**
     * DELETE /historias/{id}/archivos/{archivo}
     */
    public function eliminarArchivo($id, $archivo): JsonResponse
    {
        $this->authorizeAction('editar');
        $this->findOrFail($id);

        $registro = Archivo::where('historia_clinica_id', $id)->findOrFail($archivo);

        if ($registro->ruta) {
            try {
                Storage::disk('local')->delete($registro->ruta);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $registro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado.',
        ]);
    }

    /**
     * Datos de un adjunto para la galería del frontend.
     *
     * @return array<string, mixed>
     */
    private function formatearArchivo(Archivo $archivo): array
    {
        $url = Route::has('historias.archivos.descargar')
            ? route('historias.archivos.descargar', [
                'id' => $archivo->historia_clinica_id,
                'archivo' => $archivo->id,
            ])
            : null;

        return [
            'id' => $archivo->id,
            'nombre_original' => $archivo->nombre_original,
            'extension' => $archivo->extension,
            'tipo' => $archivo->tipo,
            'tamano_bytes' => (int) $archivo->tamano_bytes,
            'tamano_legible' => $this->tamanoLegible((int) $archivo->tamano_bytes),
            'es_imagen' => in_array($archivo->extension, ['jpg', 'jpeg', 'png'], true),
            'es_pdf' => $archivo->extension === 'pdf',
            'url' => $url,
            'fecha' => $archivo->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function tamanoLegible(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function designados(Request $request): View|JsonResponse
    {
        return $this->listadoHistorias($request, true);
    }

    public function historiasEstudiante(Request $request): View|JsonResponse
    {
        return $this->listadoHistorias($request, false);
    }

    public function historiaEstudiante(Request $request, int $historia): View|JsonResponse
    {
        $registro = HistoriaClinica::query()->where('estudiante_id', $request->user()->id)
            ->with(['paciente', 'admision', 'clinica', 'periodo', 'docente', 'remision', 'archivos',
                'tratamientosRealizados' => fn (HasMany $query) => $query->withExists([
                    'cajaDetalles as tiene_pago' => fn (Builder $detalle) => $detalle->where('monto', '>', 0)
                        ->whereHas('caja', fn (Builder $caja) => $caja->where('habilitada', true)->where('cancelado', '>', 0)->whereNotNull('recibo')->where('recibo', '!=', '')),
                ]),
                'tratamientosRealizados.tratamiento', 'tratamientosRealizados.sesionesAtencion'])
            ->findOrFail($historia);
        $tratamientoIds = $registro->tratamientosRealizados->modelKeys();
        $cerrada = in_array($registro->estado, self::ESTADOS_CERRADOS, true) || $registro->fecha_fin !== null;

        $vista = view('historias.detalle-estudiante', [
            'registro' => $registro,
            'cerrada' => $cerrada,
            'clinicas' => collect([$registro->clinica]),
            'catalogo' => Tratamiento::where('clinica_id', $registro->clinica_id)->orderBy('nombre')->get(),
            'solicitudes' => $request->user()->canAny(['insumos.ver', 'insumos.gestionar'])
                ? InsumoSolicitado::where('usuario_id', $request->user()->id)
                    ->where(fn (Builder $query) => $query->where('historia_clinica_id', $registro->id)->orWhereIn('tratamiento_realizado_id', $tratamientoIds))
                    ->with(['detalles.insumo', 'detalles.consumos', 'tratamientoRealizado.tratamiento', 'tratamiento'])->orderByDesc('id')->get()
                : collect(),
            'pagos' => Caja::where('habilitada', true)->where(function (Builder $query) use ($tratamientoIds): void {
                $query->whereIn('tratamiento_realizado_id', $tratamientoIds)
                    ->orWhereHas('detalles', fn (Builder $detalle) => $detalle->whereIn('tratamiento_realizado_id', $tratamientoIds));
            })->orderByDesc('id')->get(),
            'esterilizaciones' => $request->user()->canAny(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
                ? Esterilizacion::where('estudiante_id', $request->user()->id)->where('clinica_id', $registro->clinica_id)
                    ->where('periodo_id', $registro->periodo_id)->where('habilitada', true)
                    ->where(function (Builder $query) use ($registro): void {
                        $query->whereNull('remision_id');
                        if ($registro->remision_id) {
                            $query->orWhere('remision_id', $registro->remision_id);
                        }
                    })->orderByDesc('id')->get()
                : collect(),
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'detalle' => $vista->fragment('detalle-estudiante')])
            : $vista;
    }

    private function listadoHistorias(Request $request, bool $soloActivas): View|JsonResponse
    {
        $query = HistoriaClinica::query()->select(['id', 'remision_id', 'admision_id', 'paciente_id', 'clinica_id', 'periodo_id',
            'docente_id', 'estudiante_id', 'estado', 'autorizado', 'fecha_inicio', 'fecha_fin', 'created_at'])
            ->where('estudiante_id', $request->user()->id)
            ->when($soloActivas, fn (Builder $query) => $query->whereNotIn('estado', self::ESTADOS_CERRADOS)->whereNull('fecha_fin'))
            ->with(['paciente', 'clinica', 'docente', 'admision:id,motivo_consulta', 'remision:id,fecha_asignacion'])
            ->withCount(['archivos', 'tratamientosRealizados']);

        return $this->respuestaListadoEstudiante($request, 'historias.estudiante', $this->datosListadoEstudiante($request, $query) + [
            'titulo' => $soloActivas ? 'Mis Pacientes Designados' : 'Mis Historias Clínicas',
            'soloActivas' => $soloActivas,
            'estadosCerrados' => self::ESTADOS_CERRADOS,
        ]);
    }

    public function indexApi(Request $request): JsonResponse
    {
        $this->autorizarApi($request, 'historias.ver');

        $paginador = AccesoClinico::historias(HistoriaClinica::query(), $request->user())
            ->with(['paciente:id,nombre,apellido_paterno,apellido_materno,ci', 'clinica:id,especialidad', 'periodo:id,nombre', 'estudiante:id,nombre_completo', 'docente:id,nombre_completo'])
            ->when($request->filled('paciente_id'), fn ($q) => $q->where('paciente_id', $request->input('paciente_id')))
            ->when($request->filled('clinica_id'), fn ($q) => $q->where('clinica_id', $request->input('clinica_id')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->when($request->filled('autorizado'), fn ($q) => $q->where('autorizado', $request->boolean('autorizado')))
            ->orderByDesc('id')
            ->paginate($this->porPaginaApi($request));

        return response()->json([
            'success' => true,
            'data' => HistoriaResource::collection($paginador->items()),
            'paginacion' => $this->paginacionApi($paginador),
        ]);
    }

    public function showApi(Request $request, int $id): JsonResponse
    {
        $this->autorizarApi($request, 'historias.ver');

        $historia = AccesoClinico::historias(HistoriaClinica::query(), $request->user())
            ->with(['paciente', 'clinica', 'periodo', 'estudiante', 'docente', 'tratamientosRealizados.tratamiento', 'admision'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                (new HistoriaResource($historia))->toArray($request),
                [
                    'odontograma' => $historia->admision?->odontograma_pintado,
                    'tratamientos' => $historia->tratamientosRealizados->map(fn ($t) => [
                        'id' => $t->id,
                        'tratamiento' => $t->tratamiento?->nombre,
                        'pieza_dental' => $t->pieza_dental,
                        'estado' => $t->estado,
                        'costo_cobrado' => (float) $t->costo_cobrado,
                        'fecha_inicio' => $t->fecha_inicio?->format('Y-m-d'),
                    ]),
                ]
            ),
        ]);
    }

    public function actualizarAvanceApi(Request $request): JsonResponse
    {
        $this->autorizarApi($request, 'historias.editar');
        try {
            $data = $request->validate([
                'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
                'estado' => ['nullable', 'string', 'max:50'],
                'revision_docente' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $exception) {
            return $this->errorValidacion($exception);
        }
        $historia = AccesoClinico::historias(HistoriaClinica::query(), $request->user())->findOrFail($data['historia_clinica_id']);
        $usuario = $request->user();
        if ($request->exists('revision_docente')) {
            abort_unless($usuario->hasAnyRole(['admin', 'subadmin']) || ($usuario->hasAnyRole(['docente', 'jefe-catedra']) && (int) $historia->docente_id === $usuario->id), 403);
        }
        $historia->update(array_filter([
            'estado' => $data['estado'] ?? null,
            'revision_docente' => $data['revision_docente'] ?? null,
        ], fn (mixed $valor): bool => $valor !== null));

        return response()->json(['success' => true, 'message' => 'Historia clínica actualizada.', 'data' => new HistoriaResource($historia->fresh()->load(['paciente', 'clinica']))]);
    }
}
