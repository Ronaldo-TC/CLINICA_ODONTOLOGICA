<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Archivo;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\SolicitudRadiologica;
use App\Services\AccesoClinico;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RadiologiaController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass = Radiologia::class;

    protected string $viewPrefix = 'radiologia';

    protected string $resourceName = 'Radiología';

    protected string $pluralName = 'Radiologías';

    protected string $permissionPrefix = 'radiologias';

    protected int $perPage = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return AccesoClinico::radiologias(Radiologia::query(), $request->user())
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'clinica:id,especialidad,modulo',
                'usuario:id,nombre_completo',
                'caja:id,recibo,costo_total',
            ])
            ->orderByDesc('fecha_toma')
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('observacion', 'like', "%{$buscar}%")
                    ->orWhereHas('paciente', function ($p) use ($buscar) {
                        $p->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('ci', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($clinicaId = $request->input('clinica_id')) {
            $q->where('clinica_id', $clinicaId);
        }

        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
        }

        if ($desde = $request->input('desde')) {
            $q->whereDate('fecha_toma', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $q->whereDate('fecha_toma', '<=', $hasta);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
            'solicitudesPagadas' => AccesoClinico::solicitudesRadiologicas(SolicitudRadiologica::query(), $request->user())
                ->where('estado', SolicitudRadiologica::AUTORIZADA)
                ->whereDoesntHave('estudio')
                ->whereRaw('COALESCE((SELECT SUM(cajas.cancelado) FROM cajas WHERE cajas.solicitud_radiologica_id = solicitudes_radiologicas.id AND cajas.habilitada = 1), 0) >= solicitudes_radiologicas.costo_autorizado')
                ->with(['historiaClinica.estudiante', 'paciente', 'recibos' => fn ($query) => $query->where('habilitada', true)])
                ->orderBy('created_at')
                ->limit(100)
                ->get(),
            'estados' => Radiologia::query()
                ->select('estado')
                ->whereNotNull('estado')
                ->distinct()
                ->orderBy('estado')
                ->pluck('estado'),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'string', 'max:50'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'caja_id' => ['required', 'integer', 'exists:cajas,id'],
            'clinica_id' => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:pacientes,id'],
            'remision_id' => ['nullable', 'integer', 'exists:remisiones,id'],
            'fecha_toma' => ['nullable', 'date'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'descripcion.required' => 'La descripción es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'usuario_id.required' => 'Debe asignar un responsable.',
            'caja_id.required' => 'Debe asignar una caja.',
            'clinica_id.required' => 'Debe seleccionar una clínica.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        foreach (['observacion', 'archivo_ruta'] as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }

        return $data;
    }

    protected function beforeStore(array $data): array
    {
        $data = $this->vincularPaciente($data);
        $data = $this->normalizarStrings($data);
        $data['usuario_id'] = request()->user()->id;
        $data['observacion'] ??= '';

        return $data;
    }

    protected function beforeUpdate($model, array $data): array
    {
        if ($model->archivos()->exists()) {
            foreach (['paciente_id', 'caja_id', 'clinica_id', 'remision_id'] as $campo) {
                if (array_key_exists($campo, $data) && (int) $data[$campo] !== (int) $model->{$campo}) {
                    throw ValidationException::withMessages([$campo => 'El estudio tiene imágenes y no puede reasignarse a otro caso.']);
                }
            }
        }
        $data = $this->vincularPaciente($data);
        unset($data['usuario_id']);

        return $this->normalizarStrings($data);
    }

    private function vincularPaciente(array $data): array
    {
        $caja = Caja::query()->lockForUpdate()->findOrFail($data['caja_id']);
        $this->exigirPagoEstudio($caja);
        if ((int) $caja->clinica_id !== (int) $data['clinica_id']) {
            throw ValidationException::withMessages(['clinica_id' => 'Seleccione la clínica del servicio radiológico pagado.']);
        }
        if ($caja->paciente_id) {
            if (! empty($data['paciente_id']) && (int) $data['paciente_id'] !== (int) $caja->paciente_id) {
                throw ValidationException::withMessages(['paciente_id' => 'El paciente debe corresponder al recibo seleccionado.']);
            }
            $data['paciente_id'] = $caja->paciente_id;
            $data['remision_id'] = $caja->remision_id;
        }
        if (empty($data['paciente_id'])) {
            throw ValidationException::withMessages(['paciente_id' => 'Seleccione el paciente del estudio.']);
        }
        if (! empty($data['remision_id'])) {
            $remision = Remision::findOrFail($data['remision_id']);
            if ((int) $remision->paciente_id !== (int) $data['paciente_id']) {
                throw ValidationException::withMessages(['remision_id' => 'La remisión debe corresponder al paciente del estudio.']);
            }
        }

        return $data;
    }

    protected function canDestroy($model): array
    {
        if ($model->archivos()->exists()) {
            return [false, 'El estudio tiene imágenes clínicas y debe conservarse.'];
        }

        return [true, ''];
    }

    protected function findOrFail($id): Model
    {
        return AccesoClinico::radiologias(Radiologia::query(), request()->user())->findOrFail($id);
    }

    /* ============================================================
       BÚSQUEDA AJAX
       ============================================================ */

    public function buscarPacientes(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $pacientes = AccesoClinico::pacientes(Paciente::query(), $request->user())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'texto' => $p->nombre_completo,
                'extra' => $p->ci ? "CI: {$p->ci}" : '',
                'iniciales' => $p->iniciales,
            ]);

        return response()->json($pacientes);
    }

    public function buscarCajas(Request $request)
    {
        $this->authorizeAction('ver');
        $q = trim((string) $request->input('q'));

        $cajas = Caja::query()
            ->where('habilitada', true)
            ->where('recibo', '>', 0)
            ->where('tipo', 'radiologia')
            ->whereNotNull('tratamiento_realizado_id')
            ->with([
                'tratamientoRealizado.tratamiento',
                'tratamientoRealizado.cajas.detalles',
                'tratamientoRealizado.cajaDetalles.caja.detalles',
            ])
            ->when(! AccesoClinico::esAdministrador($request->user()) && $request->user()->hasAnyRole(['docente', 'jefe-catedra']),
                fn (Builder $query) => $query->whereIn('paciente_id', AccesoClinico::pacientes(Paciente::query(), $request->user())->select('id')))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('recibo', 'like', "%{$q}%")
                        ->orWhere('paciente_nombre_completo', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'recibo', 'paciente_id', 'tratamiento_realizado_id', 'paciente_nombre_completo', 'costo_total'])
            ->filter(fn (Caja $caja): bool => $caja->tratamientoRealizado?->estaAutorizado()
                && $caja->tratamientoRealizado->resumenPago()['estado'] === 'pagado')
            ->take(20)
            ->map(fn ($c) => [
                'id' => $c->id,
                'texto' => 'Recibo #'.($c->recibo ?: $c->id).' - '.($c->paciente_nombre_completo ?: 'Sin paciente'),
                'extra' => 'Costo: '.number_format($c->costo_total, 2).' Bs',
            ]);

        return response()->json($cajas);
    }

    public function datosIniciales()
    {
        $this->authorizeAction('ver');

        return response()->json([
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad', 'modulo'])
                ->map(fn ($c) => ['id' => $c->id, 'texto' => "{$c->especialidad} ({$c->modulo})"]),
        ]);
    }

    public function visor(Request $request, int $radiologia): View
    {
        $registro = $this->radiologiaVisible($request, $radiologia);
        $archivos = $registro->archivos()->orderBy('id')->get();

        return view('radiologia.visor', ['registro' => $registro, 'archivos' => $archivos, 'imagenes' => $archivos->map(fn (Archivo $archivo): array => [
            'id' => $archivo->id, 'nombre' => $archivo->nombre_original, 'dicom' => $archivo->extension === 'dcm',
            'url' => route('radiologia.archivos.ver', [$registro->id, $archivo->id]),
        ])]);
    }

    public function show(int $id): RedirectResponse
    {
        $registro = $this->findOrFail($id);

        return redirect()->route('radiologia.visor', $registro);
    }

    public function guardarArchivos(Request $request, int $radiologia): RedirectResponse
    {
        $registro = $this->radiologiaVisible($request, $radiologia);
        $this->exigirPagoEstudio($registro->caja);
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'subadmin', 'director', 'radiologia'])
                && $request->user()->canAny(['radiologias.editar', 'radiologias.gestionar']),
            403,
        );
        $request->validate(['archivos' => ['required', 'array', 'min:1', 'max:200'], 'archivos.*' => ['required', 'file', 'max:51200']]);
        $preparados = [];
        foreach ($request->file('archivos') as $indice => $file) {
            $inicio = file_get_contents($file->getRealPath(), false, null, 0, 132);
            $dicom = strlen($inicio) >= 132 && substr($inicio, 128, 4) === 'DICM';
            $tipo = $file->getMimeType();
            if (! $dicom && ! in_array($tipo, ['image/jpeg', 'image/png'], true)) {
                throw ValidationException::withMessages(['archivos.'.$indice => 'Seleccione imágenes JPG/PNG o archivos DICOM Part 10 (.dcm).']);
            }
            $preparados[] = [$file, $dicom ? 'dcm' : ($tipo === 'image/png' ? 'png' : 'jpg')];
        }
        $guardados = [];
        try {
            DB::transaction(function () use ($registro, $request, $preparados, &$guardados): void {
                foreach ($preparados as [$file, $extension]) {
                    $hash = hash_file('sha256', $file->getRealPath());
                    if ($registro->archivos()->where('hash', $hash)->exists()) {
                        continue;
                    }
                    $nombre = Str::uuid().'.'.$extension;
                    $ruta = 'radiologias/'.$registro->id.'/'.$nombre;
                    $guardados[] = $ruta;
                    if (! Storage::disk('local')->putFileAs('radiologias/'.$registro->id, $file, $nombre)) {
                        throw new \RuntimeException('No se pudo guardar el estudio.');
                    }
                    $registro->archivos()->create([
                        'paciente_id' => $registro->paciente_id, 'remision_id' => $registro->remision_id,
                        'nombre_original' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
                        'nombre_guardado' => $nombre, 'ruta' => $ruta, 'extension' => $extension,
                        'tamano_bytes' => $file->getSize(), 'tipo' => $extension === 'dcm' ? 'application/dicom' : $file->getMimeType(),
                        'hash' => $hash, 'usuario_id' => $request->user()->id, 'clinica_id' => $registro->clinica_id,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($guardados);
            throw $exception;
        }

        return back()->with('success', 'Archivos del estudio guardados.');
    }

    private function exigirPagoEstudio(Caja $recibo): void
    {
        $solicitud = $recibo->solicitudRadiologica;
        if ($solicitud) {
            if (! $recibo->habilitada || (int) $recibo->recibo <= 0 || $recibo->tipo !== 'radiologia'
                || $solicitud->estado !== SolicitudRadiologica::AUTORIZADA
                || $solicitud->resumenPago()['estado'] !== 'pagado') {
                throw ValidationException::withMessages(['caja_id' => 'El estudio debe corresponder a una solicitud autorizada y completamente pagada.']);
            }

            return;
        }

        $tratamiento = $recibo->tratamientoRealizado;
        if (! $recibo->habilitada || (int) $recibo->recibo <= 0 || $recibo->tipo !== 'radiologia'
            || ! $tratamiento || ! $tratamiento->estaAutorizado() || $tratamiento->resumenPago()['estado'] !== 'pagado') {
            throw ValidationException::withMessages(['caja_id' => 'Seleccione un recibo vigente del estudio radiológico autorizado y completamente pagado.']);
        }
    }

    public function archivo(Request $request, int $radiologia, int $archivo): BinaryFileResponse
    {
        $registro = $this->radiologiaVisible($request, $radiologia);
        $documento = $registro->archivos()->findOrFail($archivo);
        abort_unless(str_starts_with($documento->ruta, 'radiologias/'.$registro->id.'/') && Storage::disk('local')->exists($documento->ruta), 404);

        return response()->file(Storage::disk('local')->path($documento->ruta), [
            'Content-Type' => $documento->tipo, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }

    private function radiologiaVisible(Request $request, int $id): Radiologia
    {
        $registro = AccesoClinico::radiologias(Radiologia::query(), $request->user())->findOrFail($id);

        return $registro->load(['paciente', 'clinica']);
    }
}
