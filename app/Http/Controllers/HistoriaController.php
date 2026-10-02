<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Archivo;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Periodo;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class HistoriaController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = HistoriaClinica::class;
    protected string $viewPrefix       = 'historias';
    protected string $resourceName     = 'Historia Clínica';
    protected string $pluralName       = 'Historias Clínicas';
    protected string $permissionPrefix = 'historias';
    protected int    $perPage          = 15;

    protected function buildIndexQuery(Request $request)
    {
        return HistoriaClinica::with([
            'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
            'clinica:id,especialidad,modulo',
            'periodo:id,nombre,gestion,semestre',
            'estudiante:id,nombre_completo',
            'docente:id,nombre_completo',
        ])->orderByDesc('id');
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
            'admision_id'             => ['required', 'integer', 'exists:admisiones,id'],
            'paciente_id'             => ['required', 'integer', 'exists:pacientes,id'],
            'clinica_id'              => ['required', 'integer', 'exists:clinicas,id'],
            'periodo_id'              => ['required', 'integer', 'exists:periodos,id'],
            'estudiante_id'           => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_estudiante' => ['required', 'integer', 'exists:clinicas,id'],
            'docente_id'              => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_docente'    => ['required', 'integer', 'exists:clinicas,id'],
            'estado'                  => ['nullable', 'string', 'max:50'],
            'archivo_nombre'          => ['nullable', 'string', 'max:100'],
            'autorizado'              => ['nullable', 'boolean'],
            'fecha_inicio'            => ['nullable', 'date'],
            'fecha_fin'               => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    protected function beforeStore(array $data): array
    {
        foreach (['estado', 'archivo_nombre'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        $data['autorizado']          = request()->boolean('autorizado') ? 1 : 0;
        $data['estado_revision']     = 0;
        $data['revision_cualquiera'] = 0;
        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        foreach (['estado', 'archivo_nombre'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }
        $data['autorizado'] = request()->boolean('autorizado') ? 1 : 0;
        return $data;
    }

    /* ============================================================
       ENDPOINTS AJAX (opcionales)
       ============================================================ */

    public function buscarAdmisiones(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $admisiones = Admision::with('paciente:id,ci,nombre,apellido_paterno,apellido_materno')
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
            ->map(fn($a) => [
                'id'          => $a->id,
                'paciente_id' => $a->paciente_id,
                'texto'       => $a->paciente?->nombre_completo ?? "Admisión #{$a->id}",
                'extra'       => 'CI: ' . ($a->paciente?->ci ?? '—'),
            ]);

        return response()->json($admisiones);
    }

    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');
        $q   = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'docente'    => ['docente', 'jefe-catedra'],
            default      => [],
        };

        if (empty($roles)) return response()->json([]);

        $usuarios = Usuario::where('habilitado', 1)
            ->whereHas('roles', fn($r) => $r->whereIn('name', $roles))
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre_completo', 'like', "%{$q}%");
            })
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'ci', 'nombre_completo'])
            ->map(fn($u) => [
                'id'    => $u->id,
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
                ->map(fn($c) => ['id' => $c->id, 'texto' => $c->especialidad]),
            'periodos' => Periodo::orderByDesc('gestion')->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre'])
                ->map(fn($p) => ['id' => $p->id, 'texto' => "{$p->nombre} ({$p->gestion}-{$p->semestre})"]),
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

        $historia = HistoriaClinica::findOrFail($id);

        return response()->json([
            'success'  => true,
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

        $historia = HistoriaClinica::with('paciente')->findOrFail($id);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'archivo.required' => 'Debe seleccionar un archivo.',
            'archivo.mimes'    => 'Sólo se permiten archivos PDF, JPG o PNG.',
            'archivo.max'      => 'El archivo no puede superar los 10 MB.',
        ]);

        $file = $request->file('archivo');

        $contenido = file_get_contents($file->getRealPath());
        $hash      = hash('sha256', $contenido);
        $bytes     = strlen($contenido);
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
                Storage::disk('public')->put($ruta, $contenido);
            } catch (\Throwable $e) {
                report($e);
            }

            $archivo = Archivo::create([
                'paciente_id'        => $historia->paciente_id,
                'historia_clinica_id' => $historia->id,
                'nombre_original'    => $file->getClientOriginalName(),
                'nombre_guardado'    => $nombreGuardado,
                'ruta'               => $ruta,
                'extension'          => $extension,
                'tamano_bytes'       => $bytes,
                'tipo'               => $file->getMimeType(),
                'hash'               => $hash,
                'usuario_id'         => auth()->id() ?? session('user_id'),
                'clinica_id'         => $historia->clinica_id,
            ]);

            // El binario también queda en la historia (respaldo en la BD)
            $historia->update([
                'archivo'        => base64_encode($contenido),
                'archivo_nombre' => mb_substr((string) $file->getClientOriginalName(), 0, 100),
                'archivo_hash'   => substr($hash, 0, 50),
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
                    : 'Error: ' . $e->getMessage(),
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

        $registro = Archivo::where('historia_clinica_id', $id)->findOrFail($archivo);

        $contenido = null;

        if ($registro->ruta && Storage::disk('public')->exists($registro->ruta)) {
            $contenido = Storage::disk('public')->get($registro->ruta);
        }

        if ($contenido === null) {
            $historia = HistoriaClinica::find($id);
            $contenido = $historia?->archivo ? base64_decode($historia->archivo) : null;
        }

        abort_if($contenido === null, 404, 'El archivo no está disponible.');

        return response($contenido)
            ->header('Content-Type', $registro->tipo ?: 'application/octet-stream')
            ->header('Content-Disposition', 'inline; filename="' . ($registro->nombre_original ?: 'archivo') . '"');
    }

    /**
     * DELETE /historias/{id}/archivos/{archivo}
     */
    public function eliminarArchivo($id, $archivo): JsonResponse
    {
        $this->authorizeAction('editar');

        $registro = Archivo::where('historia_clinica_id', $id)->findOrFail($archivo);

        if ($registro->ruta) {
            try {
                Storage::disk('public')->delete($registro->ruta);
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
                'id'      => $archivo->historia_clinica_id,
                'archivo' => $archivo->id,
            ])
            : null;

        return [
            'id'              => $archivo->id,
            'nombre_original' => $archivo->nombre_original,
            'extension'       => $archivo->extension,
            'tipo'            => $archivo->tipo,
            'tamano_bytes'    => (int) $archivo->tamano_bytes,
            'tamano_legible'  => $this->tamanoLegible((int) $archivo->tamano_bytes),
            'es_imagen'       => in_array($archivo->extension, ['jpg', 'jpeg', 'png'], true),
            'es_pdf'          => $archivo->extension === 'pdf',
            'url'             => $url,
            'fecha'           => $archivo->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function tamanoLegible(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
