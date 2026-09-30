<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicHistoryTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->timestamp;

        $rows = [
            // [remissionid, admissionid, patientid, clinicalid, status, studentid, studentclinicalid, teacherid, teacherclinicalid]
            [1, 1, 1, 1, 'Abierto', 4, 1, 2, 1],
            [2, 2, 2, 2, 'En revisión', 4, 2, 3, 2],
            [3, 3, 3, 3, 'Cerrado', 5, 2, 3, 2],
            [4, 4, 4, 3, 'Abierto', 5, 3, 3, 3],
            [5, 5, 5, 4, 'En revisión', 6, 4, 2, 1],
            [6, 6, 6, 5, 'Cerrado', 6, 5, 2, 1],
            [7, 7, 7, 1, 'Abierto', 4, 1, 2, 1],
            [8, 8, 8, 2, 'En revisión', 5, 2, 3, 2],
            [9, 9, 9, 4, 'Cerrado', 6, 4, 2, 1],
            [10, 10, 10, 6, 'Abierto', 4, 1, 2, 1],
        ];

        foreach ($rows as $r) {
            DB::table('clinichistorytable')->insert([
                'remissionid'        => $r[0],
                'admissionid'        => $r[1],
                'patientid'          => $r[2],
                'clinicalid'         => $r[3],
                'status'             => $r[4],
                'inputfilename'      => 'hist_' . $r[0] . '.pdf',
                'inputfile'          => null,
                'inputfilehash'      => md5('hist' . $r[0]),
                'authorized'         => $r[4] === 'Cerrado',
                'studentid'          => $r[5],
                'studentclinicalid'  => $r[6],
                'teacherid'          => $r[7],
                'teacherclinicalid'  => $r[8],
                'reviewteacher'      => $r[4] === 'Cerrado' ? 'OK' : '',
                'reviewany'          => false,
                'reviewstatus'       => $r[4] === 'Cerrado',
                'stdatetime'         => $now,
                'endatetime'         => $r[4] === 'Cerrado' ? $now : -1,
                'updatetime'         => $now,
            ]);
        }
    }
}
