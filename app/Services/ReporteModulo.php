<?php

namespace App\Services;

use App\Models\Clinica;
use App\Models\Periodo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteModulo
{
    private const LIMITE = 500;

    /** @var array<string, array{titulo: string, columnas: array<int, array{0: string, 1: string, 2?: string}>, relaciones?: array<int, string>}> */
    private const MODULOS = [
        'pacientes' => ['titulo' => 'Registro de pacientes', 'columnas' => [
            ['Paciente', 'id'], ['CI', 'ci'], ['Nombre completo', 'nombre_completo'],
            ['Sexo', 'genero'], ['Nacimiento', 'fecha_nacimiento', 'fecha'], ['Edad', 'edad'],
            ['Admisiones', 'admisiones_count'], ['Historias', 'historias_clinicas_count'],
        ]],
        'tratamientos' => ['titulo' => 'Catálogo de tratamientos', 'columnas' => [
            ['Código', 'id'], ['Tratamiento', 'nombre'], ['Especialidad', 'clinica.especialidad'],
            ['Descripción', 'descripcion'], ['Costo Bs', 'costo', 'decimal'], ['Atenciones registradas', 'tratamientos_realizados_count'],
        ]],
        'admisiones' => ['titulo' => 'Admisiones de pacientes', 'columnas' => [
            ['Ficha', 'id'], ['Paciente', 'paciente.nombre_completo'], ['CI', 'paciente.ci'],
            ['Fecha', 'fecha_admision', 'fecha'], ['Periodo', 'periodo.nombre'], ['Motivo', 'motivo_consulta'],
            ['Áreas derivadas', 'area_derivada'], ['Registrado por', 'registradoPor.nombre_completo'], ['Estado', 'estado', 'estado'],
        ]],
        'historias' => ['titulo' => 'Historias clínicas', 'columnas' => [
            ['Historia', 'id'], ['Paciente', 'paciente.nombre_completo'], ['CI', 'paciente.ci'],
            ['Especialidad', 'clinica.especialidad'], ['Periodo', 'periodo.nombre'],
            ['Estudiante', 'estudiante.nombre_completo'], ['Docente', 'docente.nombre_completo'],
            ['Estado', 'estado', 'estado'], ['Autorizada', 'autorizado', 'booleano'],
        ]],
        'remisiones' => ['titulo' => 'Derivaciones a especialidades', 'columnas' => [
            ['Derivación', 'id'], ['Ficha', 'admision_id'], ['Paciente', 'paciente.nombre_completo'],
            ['CI', 'paciente.ci'], ['Especialidad', 'clinica.especialidad'], ['Periodo', 'periodo.nombre'],
            ['Asignación', 'fecha_asignacion', 'fecha'], ['Estado', 'estado', 'estado'], ['Habilitada', 'habilitado', 'booleano'],
        ]],
        'tratamientos-realizados' => ['titulo' => 'Tratamientos y seguimiento clínico', 'relaciones' => ['tratamiento', 'historiaClinica.paciente', 'clinica'], 'columnas' => [
            ['Atención', 'id'], ['Paciente', 'historiaClinica.paciente.nombre_completo'], ['CI', 'historiaClinica.paciente.ci'],
            ['Tratamiento', 'tratamiento.nombre'], ['Especialidad', 'clinica.especialidad'], ['Pieza', 'pieza_dental'],
            ['Inicio', 'fecha_inicio', 'fecha'], ['Estado', 'estado', 'estado'], ['Costo Bs', 'costo_cobrado', 'decimal'],
        ]],
        'insumos' => ['titulo' => 'Inventario y disponibilidad de insumos', 'columnas' => [
            ['Insumo', 'nombre'], ['Especialidad', 'clinica.especialidad'], ['Tipo', 'insumoTratamiento.nombre'],
            ['Unidad', 'cantidad'], ['Stock', 'cantidad_numerica', 'decimal'], ['Mínimo', 'stock_minimo', 'decimal'],
            ['Stock bajo', 'stock_bajo', 'booleano'], ['Costo unitario Bs', 'costo_unitario', 'decimal'], ['Valor Bs', 'valor_total', 'decimal'],
        ]],
        'solicitudes-insumos' => ['titulo' => 'Solicitudes y entrega de insumos', 'columnas' => [
            ['Solicitud', 'id'], ['Fecha', 'fecha_solicitud', 'fecha'], ['Estudiante', 'estudiante.nombre_completo'],
            ['Docente', 'docente.nombre_completo'], ['Especialidad', 'clinica.especialidad'], ['Pieza', 'pieza'],
            ['Estado', 'estado', 'estado'], ['Detalle: solicitado / entregado', 'detalles', 'detalles'],
        ]],
        'esterilizacion' => ['titulo' => 'Control de esterilización', 'columnas' => [
            ['Control', 'id'], ['Estudiante', 'estudiante.nombre_completo'], ['Especialidad', 'clinica.especialidad'],
            ['Periodo', 'periodo.nombre'], ['Sobres', 'cantidad_sobres'], ['Paquetes', 'cantidad_paquetes'],
            ['Autorizado', 'autorizado', 'booleano'], ['Recepción', 'fecha_entrega', 'fecha'], ['Recojo', 'fecha_recojo', 'fecha'],
            ['Habilitado', 'habilitada', 'booleano'],
        ]],
        'radiologia' => ['titulo' => 'Solicitudes y estudios radiológicos', 'columnas' => [
            ['Estudio', 'id'], ['Paciente', 'paciente.nombre_completo'], ['CI', 'paciente.ci'],
            ['Descripción', 'descripcion'], ['Especialidad', 'clinica.especialidad'],
            ['Fecha de toma', 'fecha_toma', 'fecha'], ['Estado', 'estado', 'estado'], ['Observaciones', 'observacion'],
        ]],
        'cajas' => ['titulo' => 'Pagos y saldos de Cajas', 'columnas' => [
            ['Recibo', 'recibo'], ['Fecha', 'created_at', 'fecha'], ['Paciente', 'paciente_nombre_completo'],
            ['Concepto', 'descripcion'], ['Especialidad', 'clinica.especialidad'], ['Tipo de pago', 'tipo_pago', 'estado'],
            ['Costo Bs', 'costo_total', 'decimal'], ['Pagado Bs', 'cancelado', 'decimal'], ['Saldo Bs', 'saldo', 'decimal'],
            ['Vigente', 'habilitada', 'booleano'],
        ]],
    ];

    public function validarFiltros(Request $request): void
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:200'],
            'clinica_id' => ['nullable', 'integer', 'min:1'],
            'periodo_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'estado' => ['nullable', 'string', 'max:50'],
            'tipo_pago' => ['nullable', 'string', 'max:100'],
            'stock_bajo' => ['nullable', 'boolean'],
            'genero' => ['nullable', 'string', 'max:50'],
        ]);
    }

    public function generar(Builder $query, string $modulo, Request $request): Response
    {
        abort_unless(isset(self::MODULOS[$modulo]), 404);
        $definicion = self::MODULOS[$modulo];
        $total = (clone $query)->count();
        $registros = (clone $query)->with($definicion['relaciones'] ?? [])->limit(self::LIMITE)->get();
        $filas = $registros->map(fn (Model $registro): array => array_map(
            fn (array $columna): string => $this->formatear(data_get($registro, $columna[1]), $columna[2] ?? 'texto'),
            $definicion['columnas']
        ));
        $resumen = ['Registros encontrados' => (string) $total, 'Registros incluidos' => (string) $registros->count()];
        if ($modulo === 'cajas') {
            $importes = (clone $query)->where('habilitada', true)->toBase()->reorder()->select([])
                ->selectRaw('COALESCE(SUM(costo_total), 0) AS costo, COALESCE(SUM(cancelado), 0) AS pagado')->first();
            $resumen['Costo vigente Bs'] = number_format((float) $importes->costo, 2, '.', ',');
            $resumen['Pagado Bs'] = number_format((float) $importes->pagado, 2, '.', ',');
            $resumen['Saldo Bs'] = number_format((float) $importes->costo - (float) $importes->pagado, 2, '.', ',');
        }
        if ($modulo === 'insumos') {
            $resumen['Con stock bajo'] = (string) (clone $query)->whereColumn('cantidad_numerica', '<=', 'stock_minimo')->count();
            $valor = (clone $query)->toBase()->reorder()->select([])->selectRaw('COALESCE(SUM(cantidad_numerica * costo_unitario), 0) AS valor')->first();
            $resumen['Valor del inventario Bs'] = number_format((float) $valor->valor, 2, '.', ',');
        }

        return app(ReportePdf::class)->responder('reportes.modulo', [
            'titulo' => $definicion['titulo'], 'codigo' => strtoupper($modulo).'-'.now()->format('Ymd'),
            'emitidoPor' => $request->user()->nombre_completo, 'emitidoEn' => now(),
            'volver' => route($modulo.'.index'), 'horizontal' => true,
            'columnas' => array_column($definicion['columnas'], 0), 'filas' => $filas,
            'resumen' => $resumen, 'filtrosReporte' => $this->filtros($request),
            'limitado' => $total > self::LIMITE, 'limite' => self::LIMITE,
        ], $request, $modulo.'-'.now()->format('Ymd-His').'.pdf');
    }

    private function formatear(mixed $valor, string $tipo): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin registrar';
        }

        return match ($tipo) {
            'fecha' => $valor instanceof CarbonInterface ? $valor->format('d/m/Y') : (string) $valor,
            'decimal' => number_format((float) $valor, 2, '.', ','),
            'booleano' => $valor ? 'Sí' : 'No',
            'estado' => ucfirst(str_replace('_', ' ', (string) $valor)),
            'detalles' => $valor->map(fn (Model $detalle): string => ($detalle->insumo?->nombre ?? 'Insumo').' · '.$detalle->cantidad_solicitada.' / '.$detalle->cantidad_entregada)->implode("\n"),
            default => (string) $valor,
        };
    }

    /** @return array<string, string> */
    private function filtros(Request $request): array
    {
        $filtros = [];
        foreach (['buscar' => 'Búsqueda', 'desde' => 'Desde', 'hasta' => 'Hasta', 'estado' => 'Estado', 'tipo_pago' => 'Tipo de pago', 'genero' => 'Sexo'] as $campo => $etiqueta) {
            if ($request->filled($campo)) {
                $filtros[$etiqueta] = (string) $request->input($campo);
            }
        }
        if ($request->filled('clinica_id')) {
            $filtros['Especialidad'] = Clinica::find($request->integer('clinica_id'))?->especialidad ?? 'Especialidad no encontrada';
        }
        if ($request->filled('periodo_id')) {
            $filtros['Periodo'] = Periodo::find($request->integer('periodo_id'))?->nombre ?? 'Periodo no encontrado';
        }
        if ($request->filled('stock_bajo')) {
            $filtros['Inventario'] = 'Solo stock bajo';
        }

        return $filtros;
    }
}
