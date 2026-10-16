<?php

namespace Tests;

use App\Models\Admision;
use App\Models\Caja;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Periodo;
use App\Models\SesionAtencion;
use App\Models\Tratamiento;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Support\Str;

trait CreatesClinicalCase
{
    private function clinicalUser(string $role): Usuario
    {
        $key = substr((string) Str::uuid(), 0, 12);
        $user = Usuario::create(['nombre_usuario' => $key, 'ci' => $key, 'nombre_completo' => 'Prueba '.$role.' '.$key, 'contrasena' => 'unused', 'habilitado' => true, 'permitir_login' => true]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{student: Usuario, teacher: Usuario, clinic: Clinica, admission: Admision, history: HistoriaClinica, treatment: Tratamiento} */
    private function clinicalCase(): array
    {
        $student = $this->clinicalUser('estudiante');
        $teacher = $this->clinicalUser('docente');
        $clinic = Clinica::create(['especialidad' => 'Clínica prueba', 'modulo' => 'Prueba', 'curso' => 1]);
        foreach ([$student, $teacher] as $person) {
            $person->especialidades()->attach($clinic->id, ['habilitado' => true]);
        }
        $patient = Paciente::create(['ci' => substr((string) Str::uuid(), 0, 12), 'nombre' => 'Paciente', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Clínica', 'genero' => 'M', 'fecha_nacimiento' => '1990-01-01']);
        $period = Periodo::query()->firstOrFail();
        $admission = Admision::create(['paciente_id' => $patient->id, 'periodo_id' => $period->id, 'estudiante_id' => $student->id, 'especialidad_estudiante' => $clinic->id, 'responsable_id' => $teacher->id, 'especialidad_responsable' => $clinic->id]);
        $history = HistoriaClinica::create(['admision_id' => $admission->id, 'paciente_id' => $patient->id, 'periodo_id' => $period->id, 'clinica_id' => $clinic->id, 'estudiante_id' => $student->id, 'especialidad_estudiante' => $clinic->id, 'docente_id' => $teacher->id, 'especialidad_docente' => $clinic->id, 'estado' => 'abierta', 'autorizado' => true]);
        $treatment = Tratamiento::create(['clinica_id' => $clinic->id, 'nombre' => 'Tratamiento prueba', 'costo' => 100]);

        return compact('student', 'teacher', 'clinic', 'admission', 'history', 'treatment');
    }

    /** @return array<string, mixed> */
    private function approvedProgress(): array
    {
        $case = $this->clinicalCase();
        $case['realized'] = TratamientoRealizado::create(['historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id, 'clinica_id' => $case['clinic']->id, 'estado' => 'en_proceso', 'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(), 'pieza_dental' => '16']);
        $case['session'] = SesionAtencion::create(['tratamiento_realizado_id' => $case['realized']->id, 'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id, 'fecha' => today(), 'descripcion' => 'Avance', 'autorizado' => true, 'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now()]);

        return $case;
    }

    /** @param array<string, mixed> $case */
    private function paidTreatment(array $case): TratamientoRealizado
    {
        $realized = $case['realized'] ?? TratamientoRealizado::query()
            ->where('historia_clinica_id', $case['history']->id)
            ->where('tratamiento_id', $case['treatment']->id)
            ->first();
        $price = (float) ($realized?->costo_cobrado ?? $case['treatment']->costo ?? 100);

        if (! $realized) {
            $realized = TratamientoRealizado::create([
                'historia_clinica_id' => $case['history']->id,
                'tratamiento_id' => $case['treatment']->id,
                'clinica_id' => $case['clinic']->id,
                'estado' => 'autorizado',
                'autorizado_por' => $case['teacher']->id,
                'fecha_autorizacion' => now(),
                'costo_cobrado' => $price,
                'pieza_dental' => '16',
            ]);
        } else {
            $realized->update([
                'autorizado_por' => $realized->autorizado_por ?? $case['teacher']->id,
                'fecha_autorizacion' => $realized->fecha_autorizacion ?? now(),
                'costo_cobrado' => $price,
            ]);
        }

        $balance = (float) $realized->fresh()->resumenPago()['saldo'];
        if ($balance > 0) {
            Caja::create([
                'tratamiento_realizado_id' => $realized->id,
                'tratamiento_id' => $case['treatment']->id,
                'paciente_id' => $case['history']->paciente_id,
                'remision_id' => $case['history']->remision_id,
                'clinica_id' => $case['clinic']->id,
                'periodo_id' => $case['history']->periodo_id,
                'usuario_id' => $case['teacher']->id,
                'recibo' => max(1000000, (int) Caja::max('recibo')) + 1,
                'monto' => $balance,
                'costo_total' => $price,
                'cancelado' => $balance,
                'habilitada' => true,
                'descripcion' => 'Pago completo para prueba clínica',
                'paciente_nombre_completo' => $case['admission']->paciente->nombre_completo,
                'tipo' => 'tratamiento',
                'tipo_pago' => 'efectivo',
                'ci' => 0,
            ]);
        }

        return $realized->fresh();
    }
}
