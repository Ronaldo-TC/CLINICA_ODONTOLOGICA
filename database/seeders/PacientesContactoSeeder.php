<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PacientesContactoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campos = ['telefono', 'estado_civil', 'escuela', 'ocupacion', 'direccion', 'emergencia', 'telefono_emergencia'];

        DB::table('pacientes')->select(['id', ...$campos])->chunkById(200, function (Collection $pacientes) use ($campos): void {
            $admisiones = DB::table('admisiones')
                ->select(['paciente_id', ...$campos])
                ->whereIn('paciente_id', $pacientes->pluck('id'))
                ->orderByRaw('COALESCE(fecha_admision, DATE(created_at)) DESC')
                ->orderByDesc('id')
                ->get()
                ->groupBy('paciente_id');

            foreach ($pacientes as $paciente) {
                $datos = [];
                foreach ($campos as $campo) {
                    if (trim((string) $paciente->$campo) !== '') {
                        continue;
                    }

                    foreach ($admisiones->get($paciente->id, collect()) as $admision) {
                        if (trim((string) $admision->$campo) !== '') {
                            $datos[$campo] = $admision->$campo;
                            break;
                        }
                    }
                }

                if ($datos !== []) {
                    DB::table('pacientes')->where('id', $paciente->id)->update($datos);
                }
            }
        });
    }
}
