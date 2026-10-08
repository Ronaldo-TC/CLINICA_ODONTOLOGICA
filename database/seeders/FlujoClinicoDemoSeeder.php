<?php

namespace Database\Seeders;

use App\Models\Admision;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\Esterilizacion;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use App\Services\FlujoTratamiento;
use App\Services\SolicitudInsumoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FlujoClinicoDemoSeeder extends Seeder
{
    /** @var array<int, array{especialidad: string, tratamiento: string, costo: int, pieza: string, denticion: string}> */
    private const CASOS = [
        ['especialidad' => 'Operatoria Dental', 'tratamiento' => 'Resina simple', 'costo' => 120, 'pieza' => '16', 'denticion' => 'adulto'],
        ['especialidad' => 'Endodoncia', 'tratamiento' => 'Endodoncia unirradicular', 'costo' => 250, 'pieza' => '36', 'denticion' => 'adulto'],
        ['especialidad' => 'Periodoncia', 'tratamiento' => 'Detartraje', 'costo' => 80, 'pieza' => '31', 'denticion' => 'adulto'],
        ['especialidad' => 'Odontopediatría', 'tratamiento' => 'Sellante preventivo infantil', 'costo' => 60, 'pieza' => '55', 'denticion' => 'infantil'],
        ['especialidad' => 'Integral Niños', 'tratamiento' => 'Restauración en dentición mixta', 'costo' => 100, 'pieza' => '75', 'denticion' => 'mixto'],
    ];

    public function run(string $prefijo = 'DEMO-CLINICA-'): void
    {
        $creados = DB::transaction(function () use ($prefijo): int {
            $usuarios = $this->usuarios();
            $periodo = $this->periodo();
            $this->callWith(PacientesDemoSeeder::class, ['prefijo' => $prefijo]);
            $creados = 0;

            foreach (self::CASOS as $indice => $caso) {
                $paciente = Paciente::where('ci', $prefijo.str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT))->firstOrFail();
                if ($paciente->admisiones()->exists()) {
                    continue;
                }
                $this->crearCaso($paciente, $caso, $indice, $periodo, $usuarios, $prefijo);
                $creados++;
            }

            return $creados;
        });

        $this->command?->info("Cinco pacientes demo disponibles. Casos clínicos nuevos: {$creados}; los casos existentes se conservaron.");
    }

    /** @return array<string, Usuario> */
    private function usuarios(): array
    {
        $usuarios = [];
        foreach (['estudiante', 'docente', 'admision', 'cajas', 'insumos', 'esterilizacion', 'radiologia'] as $rol) {
            $usuario = Usuario::where('nombre_usuario', $rol)->where('habilitado', true)
                ->whereHas('roles', fn ($query) => $query->where('name', $rol))->lockForUpdate()->first();
            if (! $usuario) {
                throw new RuntimeException("Falta el usuario habilitado '{$rol}' con su rol. Ejecute primero los seeders base de DatabaseSeeder.");
            }
            $usuarios[$rol] = $usuario;
        }

        return $usuarios;
    }

    private function periodo(): Periodo
    {
        $periodo = Periodo::where('habilitado', true)->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())->orderByDesc('id')->first();
        if ($periodo) {
            return $periodo;
        }
        $semestre = now()->month <= 6 ? 1 : 2;
        $inicio = now()->startOfYear()->addMonths($semestre === 1 ? 0 : 6);
        $periodo = Periodo::firstOrCreate(['gestion' => now()->year, 'semestre' => $semestre], [
            'nombre' => 'Gestión '.now()->year.' - Semestre '.$semestre,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $inicio->copy()->addMonths(6)->subDay()->toDateString(),
            'habilitado' => true,
        ]);
        if (! $periodo->habilitado) {
            throw new RuntimeException('El periodo actual está deshabilitado. Seleccione o habilite un periodo antes de crear los casos demo.');
        }

        return $periodo;
    }

    /**
     * @param  array{especialidad: string, tratamiento: string, costo: int, pieza: string, denticion: string}  $caso
     * @param  array<string, Usuario>  $usuarios
     */
    private function crearCaso(Paciente $paciente, array $caso, int $indice, Periodo $periodo, array $usuarios, string $prefijo): void
    {
        $clinica = Clinica::where('especialidad', $caso['especialidad'])->firstOrFail();
        $estudiante = $usuarios['estudiante'];
        $docente = $usuarios['docente'];
        foreach ([$estudiante, $docente] as $persona) {
            $asignacion = $persona->especialidades()->where('clinicas.id', $clinica->id)->first();
            if (! $asignacion) {
                $persona->especialidades()->attach($clinica->id, ['habilitado' => true, 'rotacion' => 1, 'fecha_inicio' => today()]);
            } elseif (! $asignacion->pivot->habilitado) {
                throw new RuntimeException("La especialidad {$clinica->especialidad} está deshabilitada para {$persona->nombre_usuario}. Habilítela antes de generar el caso demo.");
            }
        }
        $firma = $this->firmaDemo();
        $motivo = MotivoConsulta::firstOrCreate(['nombre' => $indice === 2 ? 'Enfermedad periodontal' : 'Caries'], ['descripcion' => 'Evaluación odontológica', 'habilitado' => true]);
        $odontograma = [$caso['pieza'] => ['o' => 'caries', 'marcas' => [['estado' => 'caries', 'color' => 'rojo']]]];
        if ($caso['denticion'] === 'mixto') {
            $odontograma['46'] = ['o' => 'sellante', 'marcas' => [['estado' => 'sellante', 'color' => 'azul']]];
        }
        $admision = Admision::create([
            'paciente_id' => $paciente->id,
            'periodo_id' => $periodo->id,
            'motivo_consulta_id' => $motivo->id,
            'estudiante_id' => $estudiante->id,
            'especialidad_estudiante' => $clinica->id,
            'responsable_id' => $docente->id,
            'especialidad_responsable' => $clinica->id,
            'fecha_admision' => today(),
            'estado' => 'derivada',
            'estado_civil' => 'soltero',
            'ocupacion' => $indice < 3 ? 'Trabajador independiente' : 'Escolar',
            'escuela' => $indice < 3 ? '' : 'Unidad educativa de demostración',
            'direccion' => 'El Alto — domicilio ficticio',
            'emergencia' => ' Familiar de contacto',
            'telefono' => 70000001 + $indice,
            'telefono_emergencia' => '70000010',
            'motivo_consulta' => ' Evaluación para '.$caso['tratamiento'],
            'historia_enfermedad_actual' => 'Caso ficticio para probar el flujo de atención de la clínica.',
            'antecedentes' => ' Datos sintéticos, sin validez clínica.',
            'diagnostico' => ' Evaluación odontológica de ejemplo',
            'peso_kg' => $indice < 3 ? 65 : 28,
            'temperatura_c' => 36.5,
            'pulso' => 75,
            'presion_arterial' => $indice < 3 ? '120/80' : '100/65',
            'frecuencia_respiratoria' => 18,
            'pieza_dental' => $caso['pieza'],
            'odontograma_denticion' => $caso['denticion'],
            'odontograma_pintado' => json_encode($odontograma, JSON_THROW_ON_ERROR),
            'consentimiento_datos' => true,
            'consentimiento_fotografias' => false,
            'consentimiento_investigacion' => true,
            'firma' => $firma,
            'autorizado_por' => $docente->id,
            'fecha_autorizacion' => now(),
        ]);
        $remision = Remision::create([
            'admision_id' => $admision->id,
            'paciente_id' => $paciente->id,
            'clinica_id' => $clinica->id,
            'periodo_id' => $periodo->id,
            'estado' => 'asignada',
            'habilitado' => true,
            'estudiante_id' => $estudiante->id,
            'docente_id' => $docente->id,
            'asignado_por' => $usuarios['admision']->id,
            'fecha_asignacion' => now(),
        ]);
        $historia = HistoriaClinica::create([
            'admision_id' => $admision->id,
            'remision_id' => $remision->id,
            'paciente_id' => $paciente->id,
            'clinica_id' => $clinica->id,
            'periodo_id' => $periodo->id,
            'estudiante_id' => $estudiante->id,
            'especialidad_estudiante' => $clinica->id,
            'docente_id' => $docente->id,
            'especialidad_docente' => $clinica->id,
            'estado' => 'abierta',
            'autorizado' => true,
            'estado_revision' => true,
            'fecha_inicio' => today(),
            'revision_docente' => ' Historia revisada para la demostración.',
        ]);
        $catalogo = Tratamiento::firstOrCreate(['clinica_id' => $clinica->id, 'nombre' => ' '.$caso['tratamiento']], [
            'costo' => $caso['costo'],
            'descripcion' => 'Tratamiento ficticio para demostración del sistema.',
        ]);
        $flujo = app(FlujoTratamiento::class);
        $tratamiento = $flujo->proponer($estudiante, ['historia_clinica_id' => $historia->id, 'tratamiento_id' => $catalogo->id, 'pieza_dental' => $caso['pieza'], 'observacion' => ' Tratamiento de ejemplo']);
        $flujo->autorizar($tratamiento, $docente);
        $caja = $this->crearCaja($tratamiento->fresh(), $usuarios['cajas'], $indice);
        $sesion = $flujo->registrarSesion($tratamiento, $estudiante, ['fecha' => today()->toDateString(), 'descripcion' => ' Evaluación y primera atención.']);
        $flujo->autorizarSesion($sesion, $docente, ' Avance revisado.');
        $segundaSesion = $flujo->registrarSesion($tratamiento, $estudiante, ['fecha' => today()->toDateString(), 'descripcion' => ' Segundo avance del tratamiento.']);
        if ($indice >= 2) {
            $flujo->autorizarSesion($segundaSesion, $docente, ' Avance revisado.');
        }

        $this->crearInsumos($tratamiento, $sesion->id, $indice, $usuarios, $prefijo);
        $this->crearEsterilizacion($remision, $indice, $usuarios, $firma);
        $clinicaRadiologia = Clinica::where('especialidad', 'Radiología')->firstOrFail();
        Radiologia::create([
            'paciente_id' => $paciente->id,
            'remision_id' => $remision->id,
            'periodo_id' => $periodo->id,
            'clinica_id' => $clinicaRadiologia->id,
            'usuario_id' => $usuarios['radiologia']->id,
            'caja_id' => $caja->id,
            'descripcion' => $indice === 1 ? ' Tomografía CBCT' : ' Radiografía periapical',
            'observacion' => 'Registro de demostración, pendiente de cargar una imagen.',
            'estado' => 'pendiente',
        ]);
        if ($indice >= 3) {
            $flujo->finalizar($tratamiento, $estudiante);
        }
        if ($indice === 4) {
            $flujo->culminar($tratamiento, $docente);
        }
    }

    private function crearCaja(TratamientoRealizado $tratamiento, Usuario $cajero, int $indice): Caja
    {
        $historia = $tratamiento->historiaClinica;
        $pago = $indice < 2 ? (float) $tratamiento->costo_cobrado / 2 : (float) $tratamiento->costo_cobrado;
        $caja = Caja::create([
            'tratamiento_realizado_id' => $tratamiento->id,
            'tratamiento_id' => $tratamiento->tratamiento_id,
            'paciente_id' => $historia->paciente_id,
            'remision_id' => $historia->remision_id,
            'clinica_id' => $historia->clinica_id,
            'periodo_id' => $historia->periodo_id,
            'usuario_id' => $cajero->id,
            'recibo' => max(900000, (int) Caja::max('recibo')) + 1,
            'monto' => (int) round($pago),
            'costo_total' => $tratamiento->costo_cobrado,
            'cancelado' => $pago,
            'habilitada' => true,
            'descripcion' => ' Pago de tratamiento',
            'paciente_nombre_completo' => $historia->paciente->nombre_completo,
            'tipo' => 'tratamiento',
            'tipo_pago' => 'efectivo',
            'ci' => 0,
        ]);
        $caja->detalles()->create([
            'tratamiento_realizado_id' => $tratamiento->id,
            'concepto' => $tratamiento->tratamiento->nombre,
            'cantidad' => 1,
            'precio_unitario' => $tratamiento->costo_cobrado,
            'monto' => $tratamiento->costo_cobrado,
            'fecha' => today(),
        ]);

        return $caja;
    }

    /** @param array<string, Usuario> $usuarios */
    private function crearInsumos(TratamientoRealizado $tratamiento, int $sesion, int $indice, array $usuarios, string $prefijo): void
    {
        $tipo = InsumoTratamiento::firstOrCreate(['clinica_id' => $tratamiento->clinica_id, 'nombre' => ' Material de atención'], ['descripcion' => 'Tipo de material ficticio']);
        $insumo = Insumo::firstOrCreate(['clinica_id' => $tratamiento->clinica_id, 'insumo_tratamiento_id' => $tipo->id, 'nombre' => ' Material clínico '.rtrim($prefijo, '-')], [
            'cantidad' => 'unidades',
            'cantidad_numerica' => 100,
            'stock_minimo' => 10,
            'costo_unitario' => 5,
        ]);
        $servicio = app(SolicitudInsumoService::class);
        $solicitud = $servicio->guardar($usuarios['estudiante'], [
            'clinica_id' => $tratamiento->clinica_id,
            'insumo_tratamiento_id' => $tipo->id,
            'tratamiento_realizado_id' => $tratamiento->id,
            'sesion_atencion_id' => $sesion,
            'remision_id' => $tratamiento->historiaClinica->remision_id,
            'insumos' => ' Solicitud para probar autorización, entrega y consumo.',
            'detalles' => [['insumo_id' => $insumo->id, 'cantidad_solicitada' => 4]],
        ]);
        if ($indice === 0) {
            return;
        }
        $servicio->autorizar($usuarios['docente'], $solicitud);
        if ($indice === 1) {
            return;
        }
        $detalle = $solicitud->detalles()->firstOrFail();
        $servicio->entregar($usuarios['insumos'], $solicitud, ['detalles' => [['detalle_id' => $detalle->id, 'cantidad_entregada' => $indice === 2 ? 2 : 4]]]);
        $servicio->consumir($usuarios['estudiante'], $solicitud, ['detalles' => [['detalle_id' => $detalle->id, 'cantidad_usada' => $indice === 2 ? 1 : ($indice === 3 ? 3 : 4)]]]);
    }

    /** @param array<string, Usuario> $usuarios */
    private function crearEsterilizacion(Remision $remision, int $indice, array $usuarios, string $firma): void
    {
        Esterilizacion::create([
            'remision_id' => $remision->id,
            'periodo_id' => $remision->periodo_id,
            'clinica_id' => $remision->clinica_id,
            'estudiante_id' => $usuarios['estudiante']->id,
            'docente_id' => $usuarios['docente']->id,
            'usuario_id' => $usuarios['esterilizacion']->id,
            'cantidad_sobres' => 2,
            'cantidad_paquetes' => 1,
            'habilitada' => true,
            'descripcion' => ' Instrumental de práctica',
            'info' => 'Datos ficticios para probar recepción y recojo.',
            'autorizado' => $indice > 0,
            'firma_docente' => $indice > 0 ? $firma : null,
            'fecha_autorizacion' => $indice > 0 ? now() : null,
            'fecha_entrega' => $indice >= 2 ? today() : null,
            'hora_entrega' => $indice >= 2 ? '09:00:00' : null,
            'recibido_por' => $indice >= 2 ? $usuarios['esterilizacion']->id : null,
            'fecha_recojo' => $indice >= 3 ? today() : null,
            'hora_recojo' => $indice >= 3 ? '11:00:00' : null,
            'entregado_por' => $indice >= 3 ? $usuarios['esterilizacion']->id : null,
        ]);
    }

    /** Firma sintética rotulada DEMO, sin valor como consentimiento real. */
    private function firmaDemo(): string
    {
        $imagen = imagecreatetruecolor(220, 40);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
        imagestring($imagen, 4, 10, 12, 'DEMO - SIN VALIDEZ', imagecolorallocate($imagen, 60, 60, 60));
        ob_start();
        imagepng($imagen);
        $contenido = ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($contenido);
    }
}
