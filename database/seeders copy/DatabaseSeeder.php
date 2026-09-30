<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserTableSeeder::class,
            ClinicalTableSeeder::class,
            SpecialtyTableSeeder::class,
            PatientTableSeeder::class,
            PatientAdmissionTableSeeder::class,
            RemissionTableSeeder::class,
            ClinicHistoryTableSeeder::class,
            TreatmentTableSeeder::class,
            SuppliesTreatmentTableSeeder::class,
            SuppliesTableSeeder::class,
            SuppliesRequestedTableSeeder::class,
            SterilizationTableSeeder::class,
            BoxTableSeeder::class,
            AuthorizationTableSeeder::class,
            RadiologyTableSeeder::class,
            LogTableSeeder::class,
        ]);

        // 🔧 Sincronizar secuencias al MAX(id) insertado
        // (usertable NO tiene secuencia porque su PK es manual)
        $sequences = [
            'clinicaltable_clinicalid_seq'                   => ['clinicaltable', 'clinicalid'],
            'patienttable_patientid_seq'                     => ['patienttable', 'patientid'],
            'patientadmissiontable_patientadmissionid_seq'   => ['patientadmissiontable', 'patientadmissionid'],
            'remissiontable_remissionid_seq'                 => ['remissiontable', 'remissionid'],
            'treatmenttable_treatmentid_seq'                 => ['treatmenttable', 'treatmentid'],
            'suppliestreatmenttable_suppliestreatmentid_seq' => ['suppliestreatmenttable', 'suppliestreatmentid'],
            'suppliestable_suppliesid_seq'                   => ['suppliestable', 'suppliesid'],
            'suppliesrequestedtable_suppliesrequestedid_seq' => ['suppliesrequestedtable', 'suppliesrequestedid'],
            'sterilizationtable_sterilizationid_seq'         => ['sterilizationtable', 'sterilizationid'],
            'boxtable_boxid_seq'                             => ['boxtable', 'boxid'],
            'radiologytable_radiologyid_seq'                 => ['radiologytable', 'radiologyid'],
            'logtable_lognumber_seq'                         => ['logtable', 'lognumber'],
        ];

        foreach ($sequences as $seq => [$table, $column]) {
            DB::statement(
                "SELECT setval('public.{$seq}', COALESCE((SELECT MAX({$column}) FROM public.{$table}), 1), true)"
            );
        }
    }
}
