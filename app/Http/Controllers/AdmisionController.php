<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Bitacora;
use App\Models\Clinica;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\Usuario;
use App\Services\AccesoClinico;
use App\Services\ReportePaciente;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdmisionController extends Controller
{
    use HasCrudAjax;

    private const CAMPOS_AUTOCOMPLETAR = [
        'telefono', 'estado_civil', 'escuela', 'ocupacion', 'direccion', 'emergencia', 'telefono_emergencia',
        'motivo_consulta_id', 'motivo_consulta', 'historia_enfermedad_actual', 'antecedentes', 'gmh',
        'gpe', 'peso_kg', 'talla_mts', 'temperatura_c', 'pulso', 'presion_arterial', 'frecuencia_respiratoria', 'signos_vitales',
        'asimetria_facial', 'cambio_color', 'abultamiento', 'ganglios_palpables', 'ruidos_articulares',
        'bm_estado', 'gm_estado', 'ee', 'cn', 'atm', 'bm', 'gm', 'calculo', 'oclusion',
        'odontograma', 'odontograma_pintado', 'odontograma_denticion', 'pieza_dental', 'diagnostico', 'examen',
    ];

    public function create(Request $request): View
    {
        $this->authorizeAction('crear');

        return view('admisiones.admision', [
            'odontograma3d' => $request->routeIs('admisiones.create-3d'),
        ] + $this->catalogosFormulario());
    }

    public function edit(Request $request, int $admision): View
    {
        $this->authorizeAction('editar');

        return view('admisiones.admision', [
            'admision' => $this->findOrFail($admision)->load(['paciente', 'remisiones.clinica']),
        ] + $this->catalogosFormulario());
    }

    private function catalogosFormulario(): array
    {
        return [
            'clinicas' => Clinica::orderBy('especialidad')->get(),
            'tratamientosPropuestos' => Tratamiento::orderBy('nombre')->get(['id', 'nombre', 'clinica_id']),
            'periodos' => Periodo::orderByDesc('gestion')->get(),
            'motivos' => MotivoConsulta::where('habilitado', true)->orderBy('nombre')->get(),
            'camposAutocompletar' => self::CAMPOS_AUTOCOMPLETAR,
        ];
    }

    public function especialidadesUsuario(Request $request, int $usuario): JsonResponse
    {
        $this->authorizeAction('ver');
        $tipo = $request->validate(['tipo' => ['required', Rule::in(['estudiante', 'responsable'])]])['tipo'];
        $persona = Usuario::where('habilitado', true)->findOrFail($usuario);
        abort_unless($persona->hasAnyRole($tipo === 'estudiante' ? ['estudiante', 'interno'] : ['docente', 'jefe-catedra']), 422);
        if ($tipo === 'estudiante' && AccesoClinico::esEstudiante($request->user())) {
            abort_unless($persona->id === $request->user()->id, 403);
        }

        return response()->json($persona->especialidades()->wherePivot('habilitado', true)->orderBy('especialidad')->get()->map(fn (Clinica $clinica): array => ['id' => $clinica->id, 'texto' => $clinica->especialidad.' ('.$clinica->modulo.')']));
    }

    protected string $modelClass = Admision::class;

    protected string $viewPrefix = 'admisiones';

    protected string $resourceName = 'Admisión';

    protected string $pluralName = 'Admisiones';

    protected string $permissionPrefix = 'admisiones';

    protected int $perPage = 15;

    public function reportePaciente(Request $request, int $admision, ReportePaciente $reporte): Response
    {
        $this->authorizeAction('ver');

        return $reporte->generar($this->findOrFail($admision), $request->user());
    }

    public function fichaPdf(Request $request, int $admision, ReportePaciente $reporte): Response
    {
        $this->authorizeAction('ver');

        return $reporte->generar($this->findOrFail($admision), $request->user(), true);
    }

    protected function extraShowData(Model $model): array
    {
        $model->load(['paciente', 'periodo', 'motivoConsulta', 'registradoPor', 'estudiante', 'responsable', 'clinicaEstudiante', 'clinicaResponsable', 'remisiones.clinica']);

        return [];
    }

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return AccesoClinico::admisiones(Admision::query(), $request->user())
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'periodo:id,nombre,gestion',
                'motivoConsulta:id,nombre',
                'estudiante:id,nombre_completo',
                'responsable:id,nombre_completo',
                'registradoPor:id,nombre_completo',
            ])
            ->orderByDesc('id');
    }

    protected function buildTablaQuery(Request $request)
    {
        $q = $this->buildIndexQuery($request);

        if ($buscar = trim((string) $request->input('buscar'))) {
            $q->where(function ($x) use ($buscar) {
                $x->whereHas('paciente', function ($p) use ($buscar) {
                    $p->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                        ->orWhere('ci', 'like', "%{$buscar}%");
                })->orWhere('motivo_consulta', 'like', "%{$buscar}%");
            });
        }

        if ($periodoId = $request->input('periodo_id')) {
            $q->where('periodo_id', $periodoId);
        }

        if ($desde = $request->input('desde')) {
            $q->whereDate('created_at', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $q->whereDate('created_at', '<=', $hasta);
        }

        return $q;
    }

    /* ============================================================
       DATOS EXTRA
       ============================================================ */

    protected function extraIndexData(Request $request): array
    {
        return [
            'periodos' => Periodo::orderByDesc('gestion')
                ->get(['id', 'nombre', 'gestion']),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'pacientes' => Paciente::orderBy('nombre')->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno']),
            'periodos' => Periodo::orderByDesc('gestion')->get(['id', 'nombre', 'gestion']),
            'motivos' => MotivoConsulta::where('habilitado', 1)->orderBy('nombre')->get(['id', 'nombre']),
            'estudiantes' => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['estudiante', 'interno']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
            'responsables' => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'jefe-catedra']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
            'clinicas' => Clinica::orderBy('especialidad')->get(['id', 'especialidad']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'periodo_id' => ['nullable', 'integer', 'exists:periodos,id'],
            'motivo_consulta_id' => ['nullable', 'integer', 'exists:motivos_consulta,id'],
            'estudiante_id' => ['nullable', 'required_with:especialidad_estudiante,responsable_id,especialidad_responsable', 'integer', 'exists:usuarios,id'],
            'especialidad_estudiante' => ['nullable', 'required_with:estudiante_id,responsable_id,especialidad_responsable', 'integer', 'exists:clinicas,id'],
            'responsable_id' => ['nullable', 'required_with:estudiante_id,especialidad_estudiante,especialidad_responsable', 'integer', 'exists:usuarios,id'],
            'especialidad_responsable' => ['nullable', 'required_with:estudiante_id,especialidad_estudiante,responsable_id', 'integer', 'exists:clinicas,id'],
            'clinica_ids' => ['nullable', 'array'],
            'clinica_ids.*' => ['required', 'integer', 'distinct', 'exists:clinicas,id'],
            'tratamientos_propuestos' => ['nullable', 'array'],
            'tratamientos_propuestos.*' => ['nullable', 'integer', 'exists:tratamientos,id'],
            'observaciones_propuestas' => ['nullable', 'array'],
            'observaciones_propuestas.*' => ['nullable', 'string', 'max:2000'],
            'actualizar_derivaciones' => ['nullable', 'boolean'],

            'estado_civil' => ['nullable', 'string', 'max:200'],
            'escuela' => ['nullable', 'string', 'max:200'],
            'ocupacion' => ['nullable', 'string', 'max:200'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'emergencia' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9() .\-]+$/'],
            'telefono_emergencia' => ['nullable', 'string', 'max:30'],
            'historia_enfermedad_actual' => ['nullable', 'string', 'max:10000'],
            'peso_kg' => ['nullable', 'numeric', 'between:0,9999.99'],
            'talla_mts' => ['nullable', 'numeric', 'between:0,9.99'],
            'temperatura_c' => ['nullable', 'numeric', 'between:0,100'],
            'pulso' => ['nullable', 'integer', 'between:0,1000'],
            'presion_arterial' => ['nullable', 'string', 'max:20'],
            'frecuencia_respiratoria' => ['nullable', 'integer', 'between:0,1000'],

            'motivo_consulta' => ['nullable', 'string', 'max:10000'],
            'antecedentes' => ['nullable', 'string', 'max:10000'],
            'gmh' => ['nullable', 'string', 'max:10000'],
            'gpe' => [$id === null ? 'required' : 'sometimes', 'required', Rule::in(['Bueno', 'Regular', 'Malo'])],
            'asimetria_facial' => ['nullable', 'boolean'],
            'cambio_color' => ['nullable', 'boolean'],
            'abultamiento' => ['nullable', 'boolean'],
            'ganglios_palpables' => ['nullable', 'boolean'],
            'ruidos_articulares' => ['nullable', 'boolean'],
            'bm_estado' => ['nullable', Rule::in(['Sin alteraciones aparentes', 'Con alteraciones'])],
            'gm_estado' => ['nullable', Rule::in(['Sin alteraciones aparentes', 'Con alteraciones'])],
            'signos_vitales' => ['nullable', 'string', 'max:10000'],
            'ee' => ['nullable', 'string', 'max:10000'],
            'cn' => ['nullable', 'string', 'max:10000'],
            'odontograma' => ['nullable', 'string'],
            'odontograma_pintado' => ['nullable', 'string'],
            'odontograma_denticion' => ['nullable', Rule::in(['adulto', 'infantil', 'mixto'])],
            'fecha_admision' => ['nullable', 'date'],
            'atm' => ['nullable', 'string', 'max:10000'],
            'bm' => ['nullable', 'string', 'max:10000'],
            'gm' => ['nullable', 'string', 'max:10000'],
            'calculo' => ['nullable', 'string', 'max:50'],
            'oclusion' => ['nullable', 'string', 'max:50'],
            'pieza_dental' => ['nullable', 'string', 'max:200'],
            'diagnostico' => ['nullable', 'string', 'max:10000'],
            'area_derivada' => ['nullable', 'string', 'max:10000'],
            'examen' => ['nullable', 'string', 'max:10000'],
            'firma' => ['nullable', 'string', 'max:2000000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],

            'consentimiento_datos' => ['nullable', 'boolean'],
            'consentimiento_fotografias' => ['nullable', 'boolean'],
            'consentimiento_investigacion' => ['nullable', 'boolean'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'paciente_id.required' => 'Debe seleccionar un paciente.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'gpe.required' => 'Seleccione el estado físico general.',
            'gpe.in' => 'Seleccione Bueno, Regular o Malo para el estado físico general.',
            'clinica_ids.*.exists' => 'Una de las clínicas seleccionadas no existe.',
            'clinica_ids.*.distinct' => 'No repita una clínica en las áreas derivadas.',
            'firma.regex' => 'La firma debe ser una imagen PNG generada en el formulario.',
            'estudiante_id.required' => 'Debe seleccionar un estudiante.',
            'especialidad_estudiante.required' => 'Debe seleccionar la especialidad del estudiante.',
            'responsable_id.required' => 'Debe seleccionar un responsable.',
            'especialidad_responsable.required' => 'Debe seleccionar la especialidad del responsable.',
        ];
    }

    /* ============================================================
       HOOKS
       ============================================================ */

    private function normalizarStrings(array $data): array
    {
        $cols = [
            'estado_civil',
            'escuela',
            'ocupacion',
            'direccion',
            'emergencia',
            'motivo_consulta',
            'antecedentes',
            'gmh',
            'gpe',
            'signos_vitales',
            'ee',
            'cn',
            'odontograma',
            'odontograma_pintado',
            'atm',
            'bm',
            'gm',
            'calculo',
            'oclusion',
            'pieza_dental',
            'diagnostico',
            'area_derivada',
            'examen',
            'firma',
        ];

        foreach ($cols as $col) {
            if (array_key_exists($col, $data) && $data[$col] === null) {
                $data[$col] = '';
            }
        }

        // El odontograma se guarda como JSON válido (o cadena vacía)
        if (array_key_exists('odontograma_pintado', $data)) {
            $data['odontograma_pintado'] = $this->normalizarOdontograma($data['odontograma_pintado']);
        }

        return $data;
    }

    /**
     * Deja el odontograma como JSON compacto { "16": {"o":"caries"} }.
     * Si viene vacío o inválido, devuelve cadena vacía.
     */
    private function normalizarOdontograma($valor): string
    {
        $datos = is_array($valor) ? $valor : json_decode(trim((string) $valor), true);

        if (! is_array($datos) || $datos === []) {
            return '';
        }

        $limite = ['o', 'm', 'd', 'v', 'l'];
        $limpio = [];

        foreach ($datos as $pieza => $caras) {
            $numero = (int) $pieza;

            $cuadrante = intdiv($numero, 10);
            $posicion = $numero % 10;
            $piezaValida = ($cuadrante >= 1 && $cuadrante <= 4 && $posicion >= 1 && $posicion <= 8)
                || ($cuadrante >= 5 && $cuadrante <= 8 && $posicion >= 1 && $posicion <= 5);
            if (! $piezaValida || ! is_array($caras)) {
                continue;
            }

            $carasLimpias = [];
            foreach ($limite as $cara) {
                if (isset($caras[$cara]) && is_string($caras[$cara]) && array_key_exists($caras[$cara], config('odontograma.condiciones'))) {
                    $carasLimpias[$cara] = $caras[$cara];
                }
            }
            $marcas = [];
            foreach (is_array($caras['marcas'] ?? null) ? array_slice($caras['marcas'], 0, 35) : [] as $marca) {
                if (is_array($marca) && is_string($marca['estado'] ?? null) && $marca['estado'] !== 'sano' && array_key_exists($marca['estado'], config('odontograma.condiciones')) && in_array($marca['color'] ?? null, ['rojo', 'azul'], true)) {
                    $marcas[$marca['estado']] = ['estado' => $marca['estado'], 'color' => $marca['color']];
                }
            }
            if ($marcas !== []) {
                $carasLimpias['marcas'] = array_values($marcas);
            }

            if ($carasLimpias !== []) {
                $limpio[(string) $numero] = $carasLimpias;
            }
        }

        return $limpio === [] ? '' : json_encode($limpio, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Registra en bitácoras los cambios del odontograma.
     */
    private function registrarCambioOdontograma(Admision $admision, ?string $anterior): void
    {
        $nuevo = (string) $admision->odontograma_pintado;

        if ($nuevo === (string) $anterior) {
            return;
        }

        try {
            Bitacora::create([
                'usuario_id' => auth()->id() ?? session('user_id'),
                'ip' => request()->ip() ?? '0.0.0.0',
                'fecha' => now(),
                'tipo' => 'odontograma',
                'datos' => json_encode([
                    'admision_id' => $admision->id,
                    'paciente_id' => $admision->paciente_id,
                    'caras_antes' => $this->contarCaras($anterior),
                    'caras_despues' => $this->contarCaras($nuevo),
                ], JSON_UNESCAPED_UNICODE),
                'estado' => 'ok',
            ]);
        } catch (\Throwable $e) {
            // La bitácora no debe interrumpir el guardado de la admisión.
        }
    }

    private function contarCaras(?string $json): int
    {
        $datos = json_decode((string) $json, true);

        if (! is_array($datos)) {
            return 0;
        }

        return array_sum(array_map(
            static fn ($caras) => is_array($caras) ? count($caras) : 0,
            $datos
        ));
    }

    protected function beforeStore(array $data): array
    {
        $this->validarResponsables($data);
        $data += Paciente::findOrFail($data['paciente_id'])->only(Paciente::CAMPOS_CONTACTO);
        $data = $this->normalizarStrings($data);
        $data['consentimiento_datos'] = request()->boolean('consentimiento_datos') ? 1 : 0;
        $data['consentimiento_fotografias'] = request()->boolean('consentimiento_fotografias');
        $data['consentimiento_investigacion'] = request()->boolean('consentimiento_investigacion') ? 1 : 0;
        $data['fecha_admision'] = $data['fecha_admision'] ?? today()->toDateString();
        $data['odontograma_denticion'] = $data['odontograma_denticion'] ?? 'adulto';
        $data['estado'] = 'revision';
        $data['registrado_por'] = request()->user()->id;
        unset($data['clinica_ids'], $data['actualizar_derivaciones'], $data['tratamientos_propuestos'], $data['observaciones_propuestas']);

        return $data;
    }

    protected function afterStore($model): void
    {
        $this->registrarCambioOdontograma($model, null);
        $this->sincronizarDerivaciones($model);
    }

    protected function beforeUpdate($model, array $data): array
    {
        $this->validarResponsables($data);
        if ($model->remisiones()->exists() || $model->historiasClinicas()->exists()) {
            $campos = ['paciente_id', 'estudiante_id', 'responsable_id', 'especialidad_estudiante', 'especialidad_responsable'];
            if ($model->historiasClinicas()->exists() || $model->remisiones()->where('estado', '!=', 'pendiente')->exists()) {
                $campos[] = 'periodo_id';
            }
            foreach ($campos as $campo) {
                if (array_key_exists($campo, $data) && (string) $data[$campo] !== (string) $model->$campo) {
                    throw ValidationException::withMessages([$campo => 'La admisión ya tiene derivaciones; no se puede cambiar su asignación.']);
                }
            }
        }
        $data = $this->normalizarStrings($data);
        $data['consentimiento_datos'] = request()->boolean('consentimiento_datos') ? 1 : 0;
        $data['consentimiento_fotografias'] = request()->boolean('consentimiento_fotografias');
        $data['consentimiento_investigacion'] = request()->boolean('consentimiento_investigacion') ? 1 : 0;
        unset($data['clinica_ids'], $data['actualizar_derivaciones'], $data['tratamientos_propuestos'], $data['observaciones_propuestas']);

        return $data;
    }

    protected function findOrFail($id): Model
    {
        return AccesoClinico::admisiones(Admision::query(), request()->user())->findOrFail($id);
    }

    private function validarResponsables(array $data): void
    {
        if (empty($data['estudiante_id']) && empty($data['responsable_id'])) {
            return;
        }
        foreach (['estudiante' => ['estudiante', 'interno'], 'responsable' => ['docente', 'jefe-catedra']] as $tipo => $roles) {
            $usuario = Usuario::find($data[$tipo.'_id']);
            $clinica = $data['especialidad_'.$tipo];
            if (! $usuario?->habilitado || ! $usuario->hasAnyRole($roles)
                || ! DB::table('especialidades')->where('usuario_id', $usuario->id)->where('clinica_id', $clinica)->where('habilitado', true)->exists()) {
                throw ValidationException::withMessages([$tipo.'_id' => 'Seleccione un usuario habilitado y asignado a esa clínica.']);
            }
        }
        if (AccesoClinico::esEstudiante(request()->user()) && (int) $data['estudiante_id'] !== request()->user()->id) {
            abort(403, 'Solo puedes registrar tu propia ficha de admisión.');
        }
    }

    public function autorizar(Request $request, int $admision): JsonResponse
    {
        $this->authorizeAction('autorizar');
        $registro = $this->findOrFail($admision);
        abort_unless($request->user()->hasAnyRole(['admin', 'subadmin', 'director']) || (! $request->user()->hasRole('jefe-catedra') && $registro->responsable_id === $request->user()->id)
            || AccesoClinico::supervisaAdmision($request->user(), $registro), 403, 'La ficha debe ser revisada por su docente responsable o el jefe de cátedra de la clínica.');
        if (! $registro->consentimiento_datos || ! $registro->firma) {
            throw ValidationException::withMessages(['consentimiento_datos' => 'Complete el consentimiento de datos y la firma del paciente antes de autorizar.']);
        }
        $registro->update(['estado' => $registro->remisiones()->exists() ? 'derivada' : 'autorizada', 'autorizado_por' => $request->user()->id, 'fecha_autorizacion' => now()]);

        return response()->json(['success' => true, 'message' => 'Ficha de admisión autorizada.', 'registro' => $registro->fresh()]);
    }

    protected function afterUpdate($model): void
    {
        $this->registrarCambioOdontograma($model, request()->input('odontograma_pintado_anterior'));
        $this->sincronizarDerivaciones($model);
    }

    private function sincronizarDerivaciones(Admision $admision): void
    {
        if (! request()->has('clinica_ids') && ! request()->boolean('actualizar_derivaciones')) {
            if ($admision->wasChanged('periodo_id')) {
                $admision->remisiones()->where('estado', 'pendiente')->update(['periodo_id' => $admision->periodo_id]);
            }

            return;
        }
        $seleccionadas = array_map('intval', request()->input('clinica_ids') ?? []);
        $propuestas = request()->input('tratamientos_propuestos', []);
        foreach ($propuestas as $clinicaId => $tratamientoId) {
            if ($tratamientoId && (! in_array((int) $clinicaId, $seleccionadas, true)
                || ! Tratamiento::whereKey($tratamientoId)->where('clinica_id', $clinicaId)->exists())) {
                throw ValidationException::withMessages(['tratamientos_propuestos.'.$clinicaId => 'Seleccione un tratamiento de la clínica derivada.']);
            }
        }
        Admision::whereKey($admision->id)->lockForUpdate()->firstOrFail();
        $existentes = $admision->remisiones()->lockForUpdate()->get();
        foreach ($existentes as $remision) {
            if (! in_array($remision->clinica_id, $seleccionadas, true) && $remision->habilitado) {
                if ($remision->estado !== 'pendiente' || $remision->historiasClinicas()->exists() || $remision->radiologias()->exists() || $remision->insumosSolicitados()->exists()) {
                    throw ValidationException::withMessages(['clinica_ids' => 'No se puede retirar una derivación que ya tiene asignación o atención registrada.']);
                }
                $remision->update(['habilitado' => false]);
            }
        }
        foreach ($seleccionadas as $clinicaId) {
            $remision = $existentes->firstWhere('clinica_id', $clinicaId);
            $propuesta = array_key_exists($clinicaId, $propuestas)
                ? ['tratamiento_propuesto_id' => $propuestas[$clinicaId] ?: null,
                    'observacion_propuesta' => request()->input('observaciones_propuestas.'.$clinicaId)]
                : [];
            if (! $remision) {
                Remision::create($propuesta + ['admision_id' => $admision->id, 'paciente_id' => $admision->paciente_id, 'periodo_id' => $admision->periodo_id, 'clinica_id' => $clinicaId, 'estado' => 'pendiente', 'habilitado' => true]);
            } elseif ($remision->estado === 'pendiente') {
                $remision->update($propuesta + ['habilitado' => true, 'periodo_id' => $admision->periodo_id]);
            } elseif ($propuesta && (int) $remision->tratamiento_propuesto_id !== (int) $propuesta['tratamiento_propuesto_id']) {
                throw ValidationException::withMessages(['tratamientos_propuestos.'.$clinicaId => 'La propuesta ya se transfirió a la historia; revísela con el docente.']);
            } elseif (! $remision->habilitado) {
                throw ValidationException::withMessages(['clinica_ids' => 'La derivación ya fue asignada y está inhabilitada. Revísela desde el módulo de remisiones.']);
            }
        }
        $areas = Clinica::whereIn('id', $seleccionadas)->orderBy('especialidad')->pluck('especialidad')->implode(', ');
        $admision->update(['area_derivada' => $areas]);
    }

    protected function canDestroy($model): array
    {
        // No eliminar si tiene historias clínicas o remisiones
        if ($model->historiasClinicas()->count() > 0) {
            return [false, 'No se puede eliminar: la admisión tiene historias clínicas.'];
        }
        if ($model->remisiones()->count() > 0) {
            return [false, 'No se puede eliminar: la admisión tiene remisiones.'];
        }

        return [true, ''];
    }

    /* ============================================================
   BÚSQUEDA AJAX PARA AUTOCOMPLETE
   ============================================================ */

    /**
     * GET /admisiones/buscar-pacientes?q=juan
     * Busca pacientes por nombre o CI.
     */
    public function buscarPacientes(Request $request)
    {
        $this->authorizeAction('ver');

        $q = trim((string) $request->input('q'));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $pacientes = AccesoClinico::pacientes(Paciente::query(), $request->user())
            ->where(function ($x) use ($q) {
                $x->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('apellido_materno', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%");
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'texto' => $p->nombre_completo,
                'extra' => $p->ci ? "CI: {$p->ci}" : '',
                'iniciales' => $p->iniciales,
            ]);

        return response()->json($pacientes);
    }

    /**
     * GET /admisiones/buscar-pacientes/{paciente}/datos
     * Recupera los datos del paciente y su última ficha de admisión accesible.
     */
    public function datosPaciente(Request $request, int $paciente): JsonResponse
    {
        $this->authorizeAction('ver');

        $paciente = AccesoClinico::pacientes(Paciente::query(), $request->user())->findOrFail($paciente);
        $admisionAnterior = AccesoClinico::admisiones(Admision::query(), $request->user())
            ->where('paciente_id', $paciente->id)
            ->select(['id', 'fecha_admision', 'created_at', ...self::CAMPOS_AUTOCOMPLETAR])
            ->with('motivoConsulta:id,nombre')
            ->orderByRaw('COALESCE(fecha_admision, DATE(created_at)) DESC')
            ->orderByDesc('created_at')->orderByDesc('id')->first();

        return response()->json([
            'id' => $paciente->id,
            'ci' => $paciente->ci,
            'nombre' => $paciente->nombre,
            'apellido_paterno' => $paciente->apellido_paterno,
            'apellido_materno' => $paciente->apellido_materno,
            'nombre_completo' => $paciente->nombre_completo,
            'genero' => $paciente->genero,
            'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
            'edad' => $paciente->edad,
            'admision_anterior' => $admisionAnterior ? [
                'id' => $admisionAnterior->id,
                'fecha_admision' => $admisionAnterior->fecha_admision?->format('Y-m-d') ?? $admisionAnterior->created_at?->format('Y-m-d'),
                'motivo_consulta_nombre' => $admisionAnterior->motivoConsulta?->nombre,
                'datos' => $admisionAnterior->only(self::CAMPOS_AUTOCOMPLETAR),
            ] : null,
        ]);
    }

    /**
     * GET /admisiones/buscar-usuarios?q=juan&rol=estudiante
     * Busca usuarios por rol.
     */
    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');

        $q = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        // Roles según el tipo de búsqueda
        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'responsable' => ['docente', 'jefe-catedra'],
            default => ['estudiante', 'interno', 'docente', 'jefe-catedra'],
        };

        $query = Usuario::query()
            ->where('habilitado', 1)
            ->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('nombre_completo', 'like', "%{$q}%")
                    ->orWhere('nombre_usuario', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%");
            });
        }

        $usuarios = $query
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'ci', 'nombre_usuario', 'nombre_completo'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'texto' => $u->nombre_completo,
                'extra' => $u->ci ? "CI: {$u->ci}" : '',
            ]);

        return response()->json($usuarios);
    }

    /**
     * GET /admisiones/datos-iniciales
     * Devuelve periodos, motivos y clínicas para llenar los selects.
     */
    public function datosIniciales()
    {
        $this->authorizeAction('ver');

        return response()->json([
            'periodos' => Periodo::orderByDesc('gestion')
                ->get(['id', 'nombre', 'gestion'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'texto' => "{$p->nombre} ({$p->gestion})",
                ]),

            'motivos' => MotivoConsulta::where('habilitado', 1)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'texto' => $m->nombre,
                ]),

            'clinicas' => Clinica::orderBy('especialidad')
                ->get(['id', 'especialidad', 'modulo'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'texto' => "{$c->especialidad} ({$c->modulo})",
                ]),
        ]);
    }

    public function admitidosEstudiante(Request $request): View|JsonResponse
    {
        $query = Admision::query()->where(function (Builder $query) use ($request): void {
            $query->where('estudiante_id', $request->user()->id)->orWhere('registrado_por', $request->user()->id);
        })->with(['paciente', 'responsable', 'periodo']);

        return $this->respuestaListadoEstudiante($request, 'admisiones.estudiante', $this->datosListadoEstudiante($request, $query));
    }
}
