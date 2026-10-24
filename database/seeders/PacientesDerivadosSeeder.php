<?php

namespace Database\Seeders;

use App\Models\Admision;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\MotivoConsulta;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\Radiologia;
use App\Models\Remision;
use App\Models\SolicitudRadiologica;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PacientesDerivadosSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = Periodo::query()->where('gestion', 2026)->firstOrFail();
        $motivo = MotivoConsulta::query()->where('nombre', 'Dolor dental')->firstOrFail();
        $estudiante = Usuario::query()->where('nombre_usuario', 'estudiante')->firstOrFail();
        $docente = Usuario::query()->where('nombre_usuario', 'docente')->firstOrFail();
        $admisionista = Usuario::query()->where('nombre_usuario', 'admision')->firstOrFail();
        $asignador = Usuario::query()->where('nombre_usuario', 'jefatura-clinicas')->firstOrFail();
        $cajero = Usuario::query()->where('nombre_usuario', 'cajas')->firstOrFail();
        $radiologo = Usuario::query()->where('nombre_usuario', 'radiologia')->firstOrFail();

        foreach ($this->casos() as $caso) {
            DB::transaction(function () use ($caso, $periodo, $motivo, $estudiante, $docente, $admisionista, $asignador, $cajero, $radiologo): void {
                $this->crearCaso(
                    $caso,
                    $periodo,
                    $motivo,
                    $estudiante,
                    $docente,
                    $admisionista,
                    $asignador,
                    $cajero,
                    $radiologo,
                );
            });
        }
    }

    /**
     * @return array<int, array{paciente: array<string, string>, especialidad: string, tratamiento: string, pieza: string, fecha: string, diagnostico: string, radiografia: string, costo_radiografia: int}>
     */
    private function casos(): array
    {
        return [
            [
                'paciente' => ['ci' => '6743210', 'nombre' => 'Ana Lucía', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'genero' => 'Femenino', 'fecha_nacimiento' => '1994-05-18', 'telefono' => '72031456', 'estado_civil' => 'Soltera', 'ocupacion' => 'Contadora', 'direccion' => 'Zona Miraflores', 'emergencia' => 'Rosa Mamani', 'telefono_emergencia' => '71204563'],
                'especialidad' => 'Operatoria Dental',
                'tratamiento' => 'Resina compuesta',
                'pieza' => '16',
                'fecha' => '2026-09-08',
                'diagnostico' => 'Caries oclusal profunda en pieza 16.',
                'radiografia' => 'Radiografía periapical de pieza 16',
                'costo_radiografia' => 30,
            ],
            [
                'paciente' => ['ci' => '8124567', 'nombre' => 'Carlos Andrés', 'apellido_paterno' => 'Choque', 'apellido_materno' => 'Flores', 'genero' => 'Masculino', 'fecha_nacimiento' => '1988-11-02', 'telefono' => '76520981', 'estado_civil' => 'Casado', 'ocupacion' => 'Técnico electricista', 'direccion' => 'Zona Villa Fátima', 'emergencia' => 'Elena Flores', 'telefono_emergencia' => '73019842'],
                'especialidad' => 'Endodoncia',
                'tratamiento' => 'Endodoncia unirradicular',
                'pieza' => '21',
                'fecha' => '2026-09-15',
                'diagnostico' => 'Pulpitis irreversible sintomática en pieza 21.',
                'radiografia' => 'Radiografía periapical de pieza 21',
                'costo_radiografia' => 30,
            ],
            [
                'paciente' => ['ci' => '5987342', 'nombre' => 'María Elena', 'apellido_paterno' => 'Condori', 'apellido_materno' => 'Rojas', 'genero' => 'Femenino', 'fecha_nacimiento' => '1979-03-27', 'telefono' => '70547812', 'estado_civil' => 'Casada', 'ocupacion' => 'Comerciante', 'direccion' => 'Zona San Pedro', 'emergencia' => 'Jorge Condori', 'telefono_emergencia' => '72569014'],
                'especialidad' => 'Operatoria Dental',
                'tratamiento' => 'Resina simple',
                'pieza' => '24',
                'fecha' => '2026-09-22',
                'diagnostico' => 'Restauración filtrada con caries recurrente en pieza 24.',
                'radiografia' => 'Radiografía periapical de pieza 24',
                'costo_radiografia' => 30,
            ],
        ];
    }

    /**
     * @param  array{paciente: array<string, string>, especialidad: string, tratamiento: string, pieza: string, fecha: string, diagnostico: string, radiografia: string, costo_radiografia: int}  $caso
     */
    private function crearCaso(
        array $caso,
        Periodo $periodo,
        MotivoConsulta $motivo,
        Usuario $estudiante,
        Usuario $docente,
        Usuario $admisionista,
        Usuario $asignador,
        Usuario $cajero,
        Usuario $radiologo,
    ): void {
        $clinica = Clinica::query()->where('especialidad', $caso['especialidad'])->firstOrFail();
        $tratamiento = Tratamiento::query()
            ->where('clinica_id', $clinica->id)
            ->where('nombre', $caso['tratamiento'])
            ->firstOrFail();
        $paciente = Paciente::query()->updateOrCreate(
            ['ci' => $caso['paciente']['ci']],
            $caso['paciente'],
        );
        $admision = Admision::query()->updateOrCreate(
            ['paciente_id' => $paciente->id, 'fecha_admision' => $caso['fecha']],
            [
                'periodo_id' => $periodo->id,
                'motivo_consulta_id' => $motivo->id,
                'registrado_por' => $admisionista->id,
                'estudiante_id' => $estudiante->id,
                'especialidad_estudiante' => $clinica->id,
                'responsable_id' => $docente->id,
                'especialidad_responsable' => $clinica->id,
                'telefono' => $paciente->telefono,
                'estado_civil' => $paciente->estado_civil,
                'ocupacion' => $paciente->ocupacion,
                'direccion' => $paciente->direccion,
                'emergencia' => $paciente->emergencia,
                'telefono_emergencia' => $paciente->telefono_emergencia,
                'motivo_consulta' => 'Dolor localizado y sensibilidad al masticar.',
                'historia_enfermedad_actual' => 'Molestia de evolución progresiva durante la última semana.',
                'antecedentes' => 'Sin antecedentes sistémicos de relevancia referidos.',
                'gpe' => 'Bueno',
                'presion_arterial' => '120/80',
                'pulso' => 72,
                'frecuencia_respiratoria' => 18,
                'diagnostico' => $caso['diagnostico'],
                'pieza_dental' => $caso['pieza'],
                'area_derivada' => $caso['especialidad'],
                'estado' => 'autorizada',
                'autorizado_por' => $docente->id,
                'fecha_autorizacion' => $caso['fecha'].' 10:00:00',
                'consentimiento_datos' => true,
            ],
        );
        $remision = Remision::query()->updateOrCreate(
            ['admision_id' => $admision->id, 'clinica_id' => $clinica->id],
            [
                'paciente_id' => $paciente->id,
                'periodo_id' => $periodo->id,
                'tratamiento_propuesto_id' => $tratamiento->id,
                'observacion_propuesta' => 'Evaluar la pieza '.$caso['pieza'].' y confirmar el plan terapéutico.',
                'estado' => 'asignada',
                'estudiante_id' => $estudiante->id,
                'docente_id' => $docente->id,
                'asignado_por' => $asignador->id,
                'fecha_asignacion' => $caso['fecha'].' 11:00:00',
                'habilitado' => true,
            ],
        );
        $historia = HistoriaClinica::query()->updateOrCreate(
            ['remision_id' => $remision->id],
            [
                'admision_id' => $admision->id,
                'paciente_id' => $paciente->id,
                'clinica_id' => $clinica->id,
                'periodo_id' => $periodo->id,
                'estado' => 'abierta',
                'autorizado' => true,
                'estudiante_id' => $estudiante->id,
                'especialidad_estudiante' => $clinica->id,
                'docente_id' => $docente->id,
                'especialidad_docente' => $clinica->id,
                'revision_docente' => 'Historia revisada y autorizada para iniciar atención.',
                'estado_revision' => true,
                'fecha_inicio' => $caso['fecha'],
            ],
        );
        TratamientoRealizado::query()->updateOrCreate(
            ['historia_clinica_id' => $historia->id, 'tratamiento_id' => $tratamiento->id],
            [
                'clinica_id' => $clinica->id,
                'pieza_dental' => $caso['pieza'],
                'estado' => 'autorizado',
                'costo_cobrado' => $tratamiento->costo,
                'observacion' => 'Tratamiento indicado durante la evaluación clínica.',
                'fecha_inicio' => $caso['fecha'],
                'autorizado_por' => $docente->id,
                'fecha_autorizacion' => $caso['fecha'].' 12:00:00',
            ],
        );
        $solicitud = SolicitudRadiologica::query()->updateOrCreate(
            ['historia_clinica_id' => $historia->id],
            [
                'paciente_id' => $paciente->id,
                'clinica_id' => $clinica->id,
                'solicitante_id' => $estudiante->id,
                'docente_id' => $docente->id,
                'revisado_por' => $docente->id,
                'estado' => SolicitudRadiologica::AUTORIZADA,
                'descripcion' => $caso['radiografia'],
                'observacion' => 'Estudio indicado para complementar el diagnóstico.',
                'costo_autorizado' => $caso['costo_radiografia'],
                'fecha_revision' => $caso['fecha'].' 13:00:00',
            ],
        );
        $recibo = Caja::query()->where('solicitud_radiologica_id', $solicitud->id)->first();
        if (! $recibo) {
            $recibo = new Caja;
            $recibo->recibo = Caja::siguienteNumeroRecibo();
        }
        $recibo->fill([
            'monto' => 0,
            'costo_total' => $caso['costo_radiografia'],
            'cancelado' => $caso['costo_radiografia'],
            'habilitada' => true,
            'descripcion' => $caso['radiografia'],
            'ci' => (int) $paciente->ci,
            'tipo' => 'radiologia',
            'tipo_pago' => 'Efectivo',
            'paciente_nombre_completo' => $paciente->nombre_completo,
            'clinica_id' => $clinica->id,
            'remision_id' => $remision->id,
            'usuario_id' => $cajero->id,
            'paciente_id' => $paciente->id,
            'periodo_id' => $periodo->id,
            'solicitud_radiologica_id' => $solicitud->id,
        ]);
        $recibo->save();
        $recibo->detalles()->updateOrCreate(
            ['concepto' => 'Estudio radiológico: '.$caso['radiografia']],
            [
                'tratamiento_realizado_id' => null,
                'cantidad' => 1,
                'precio_unitario' => $caso['costo_radiografia'],
                'monto' => $caso['costo_radiografia'],
                'fecha' => $caso['fecha'],
            ],
        );
        Radiologia::query()->updateOrCreate(
            ['solicitud_radiologica_id' => $solicitud->id],
            [
                'descripcion' => $caso['radiografia'],
                'observacion' => 'Imagen diagnóstica disponible para revisión clínica.',
                'estado' => 'realizado',
                'usuario_id' => $radiologo->id,
                'caja_id' => $recibo->id,
                'clinica_id' => $clinica->id,
                'paciente_id' => $paciente->id,
                'periodo_id' => $periodo->id,
                'remision_id' => $remision->id,
                'archivo_ruta' => '',
                'fecha_toma' => $caso['fecha'].' 15:00:00',
            ],
        );
    }
}
