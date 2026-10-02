<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudAjax;
use App\Models\Admision;
use App\Models\Bitacora;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\MotivoConsulta;
use App\Models\Usuario;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmisionController extends Controller
{
    use HasCrudAjax;

    protected string $modelClass       = Admision::class;
    protected string $viewPrefix       = 'admisiones';
    protected string $resourceName     = 'Admisión';
    protected string $pluralName       = 'Admisiones';
    protected string $permissionPrefix = 'admisiones';
    protected int    $perPage          = 15;

    /* ============================================================
       QUERIES
       ============================================================ */

    protected function buildIndexQuery(Request $request)
    {
        return Admision::query()
            ->with([
                'paciente:id,ci,nombre,apellido_paterno,apellido_materno',
                'periodo:id,nombre,gestion,semestre',
                'motivoConsulta:id,nombre',
                'estudiante:id,nombre_completo',
                'responsable:id,nombre_completo',
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
                ->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre']),
        ];
    }

    protected function extraDatosData($model): array
    {
        return [
            'pacientes'       => Paciente::orderBy('nombre')->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno']),
            'periodos'        => Periodo::orderByDesc('gestion')->orderByDesc('semestre')->get(['id', 'nombre', 'gestion', 'semestre']),
            'motivos'         => MotivoConsulta::where('habilitado', 1)->orderBy('nombre')->get(['id', 'nombre']),
            'estudiantes'     => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['estudiante', 'interno']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
            'responsables'    => Usuario::where('habilitado', 1)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['docente', 'jefe-catedra']))
                ->orderBy('nombre_completo')
                ->get(['id', 'nombre_completo']),
            'clinicas'        => Clinica::orderBy('especialidad')->get(['id', 'especialidad']),
        ];
    }

    /* ============================================================
       VALIDACIÓN
       ============================================================ */

    protected function validationRules(?int $id): array
    {
        return [
            'paciente_id'          => ['required', 'integer', 'exists:pacientes,id'],
            'periodo_id'           => ['nullable', 'integer', 'exists:periodos,id'],
            'motivo_consulta_id'   => ['nullable', 'integer', 'exists:motivos_consulta,id'],
            'estudiante_id'        => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_estudiante' => ['required', 'integer', 'exists:clinicas,id'],
            'responsable_id'       => ['required', 'integer', 'exists:usuarios,id'],
            'especialidad_responsable' => ['required', 'integer', 'exists:clinicas,id'],

            'estado_civil'  => ['nullable', 'string', 'max:200'],
            'escuela'       => ['nullable', 'string', 'max:200'],
            'ocupacion'     => ['nullable', 'string', 'max:200'],
            'direccion'     => ['nullable', 'string', 'max:200'],
            'emergencia'    => ['nullable', 'string', 'max:200'],
            'telefono'      => ['nullable', 'integer'],

            'motivo_consulta' => ['nullable', 'string'],
            'antecedentes'    => ['nullable', 'string'],
            'gmh'             => ['nullable', 'string'],
            'gpe'             => ['nullable', 'string'],
            'signos_vitales'  => ['nullable', 'string'],
            'ee'              => ['nullable', 'string'],
            'cn'              => ['nullable', 'string'],
            'odontograma'     => ['nullable', 'string'],
            'odontograma_pintado' => ['nullable', 'string'],
            'atm'             => ['nullable', 'string'],
            'bm'              => ['nullable', 'string'],
            'gm'              => ['nullable', 'string'],
            'calculo'         => ['nullable', 'string', 'max:50'],
            'oclusion'        => ['nullable', 'string', 'max:50'],
            'pieza_dental'    => ['nullable', 'string', 'max:200'],
            'diagnostico'     => ['nullable', 'string'],
            'examen'          => ['nullable', 'string'],
            'firma'           => ['nullable', 'string', 'max:2000000'],

            'consentimiento_datos'         => ['nullable', 'boolean'],
            'consentimiento_investigacion' => ['nullable', 'boolean'],
        ];
    }

    protected function validationMessages(): array
    {
        return [
            'paciente_id.required'             => 'Debe seleccionar un paciente.',
            'paciente_id.exists'               => 'El paciente seleccionado no existe.',
            'estudiante_id.required'           => 'Debe seleccionar un estudiante.',
            'especialidad_estudiante.required' => 'Debe seleccionar la especialidad del estudiante.',
            'responsable_id.required'          => 'Debe seleccionar un responsable.',
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

        // teléfono: null → 0
        if (array_key_exists('telefono', $data) && $data['telefono'] === null) {
            $data['telefono'] = 0;
        }

        return $data;
    }

    /**
     * Deja el odontograma como JSON compacto { "16": {"o":"caries"} }.
     * Si viene vacío o inválido, devuelve cadena vacía.
     */
    private function normalizarOdontograma($valor): string
    {
        if (is_array($valor)) {
            return $valor === [] ? '' : json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return '';
        }

        $datos = json_decode($valor, true);

        if (! is_array($datos) || $datos === []) {
            return '';
        }

        $limite = ['o', 'm', 'd', 'v', 'l'];
        $limpio = [];

        foreach ($datos as $pieza => $caras) {
            $numero = (int) $pieza;

            // Sólo piezas FDI válidas (11-48) y caras conocidas
            if ($numero < 11 || $numero > 48 || ! is_array($caras)) {
                continue;
            }

            $carasLimpias = [];
            foreach ($limite as $cara) {
                if (isset($caras[$cara]) && is_string($caras[$cara]) && $caras[$cara] !== '') {
                    $carasLimpias[$cara] = mb_substr($caras[$cara], 0, 20);
                }
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
                'ip'         => request()->ip() ?? '0.0.0.0',
                'fecha'      => now(),
                'tipo'       => 'odontograma',
                'datos'      => json_encode([
                    'admision_id' => $admision->id,
                    'paciente_id' => $admision->paciente_id,
                    'caras_antes' => $this->contarCaras($anterior),
                    'caras_despues' => $this->contarCaras($nuevo),
                ], JSON_UNESCAPED_UNICODE),
                'estado'     => 'ok',
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
        $data = $this->normalizarStrings($data);
        $data['consentimiento_datos']         = request()->boolean('consentimiento_datos') ? 1 : 0;
        $data['consentimiento_investigacion'] = request()->boolean('consentimiento_investigacion') ? 1 : 0;
        return $data;
    }

    protected function afterStore($model): void
    {
        $this->registrarCambioOdontograma($model, null);
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data = $this->normalizarStrings($data);
        $data['consentimiento_datos']         = request()->boolean('consentimiento_datos') ? 1 : 0;
        $data['consentimiento_investigacion'] = request()->boolean('consentimiento_investigacion') ? 1 : 0;
        return $data;
    }

    protected function afterUpdate($model): void
    {
        $this->registrarCambioOdontograma($model, request()->input('odontograma_pintado_anterior'));
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

        $pacientes = Paciente::query()
            ->where(function ($x) use ($q) {
                $x->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('apellido_materno', 'like', "%{$q}%")
                    ->orWhere('ci', 'like', "%{$q}%");
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'ci', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->map(fn($p) => [
                'id'     => $p->id,
                'texto'  => $p->nombre_completo,
                'extra'  => $p->ci ? "CI: {$p->ci}" : '',
                'iniciales' => $p->iniciales,
            ]);

        return response()->json($pacientes);
    }

    /**
     * GET /admisiones/buscar-usuarios?q=juan&rol=estudiante
     * Busca usuarios por rol.
     */
    public function buscarUsuarios(Request $request)
    {
        $this->authorizeAction('ver');

        $q   = trim((string) $request->input('q'));
        $rol = $request->input('rol', 'estudiante');

        // Roles según el tipo de búsqueda
        $roles = match ($rol) {
            'estudiante' => ['estudiante', 'interno'],
            'responsable' => ['docente', 'jefe-catedra'],
            default => ['estudiante', 'interno', 'docente', 'jefe-catedra'],
        };

        $query = Usuario::query()
            ->where('habilitado', 1)
            ->whereHas('roles', fn($r) => $r->whereIn('name', $roles));

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
            ->map(fn($u) => [
                'id'    => $u->id,
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
                ->orderByDesc('semestre')
                ->get(['id', 'nombre', 'gestion', 'semestre'])
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'texto'  => "{$p->nombre} ({$p->gestion}-{$p->semestre})",
                ]),

            'motivos' => MotivoConsulta::where('habilitado', 1)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn($m) => [
                    'id'    => $m->id,
                    'texto' => $m->nombre,
                ]),

            'clinicas' => Clinica::orderBy('especialidad')
                ->get(['id', 'especialidad', 'modulo'])
                ->map(fn($c) => [
                    'id'    => $c->id,
                    'texto' => "{$c->especialidad} ({$c->modulo})",
                ]),
        ]);
    }
}
