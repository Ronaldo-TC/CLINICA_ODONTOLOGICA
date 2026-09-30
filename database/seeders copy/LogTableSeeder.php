<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogTableSeeder extends Seeder
{
    public function run(): void
    {
        // Dejado vacío intencionalmente según pedido del usuario
        // Si quieres logs de prueba, descomenta las líneas siguientes:
        //
        // $now = now()->timestamp;
        // DB::table('logtable')->insert([
        //     ['lognumber' => 1, 'loguser' => 1, 'logip' => '127.0.0.1', 'logdate' => $now, 'logtype' => 'LOGIN', 'logdata' => '{"user":"admin"}', 'logstatus' => 'OK'],
        //     ['lognumber' => 2, 'loguser' => 2, 'logip' => '127.0.0.1', 'logdate' => $now, 'logtype' => 'LOGIN', 'logdata' => '{"user":"docente"}', 'logstatus' => 'OK'],
        //     ['lognumber' => 3, 'loguser' => 4, 'logip' => '127.0.0.1', 'logdate' => $now, 'logtype' => 'LOGIN', 'logdata' => '{"user":"estudiante"}', 'logstatus' => 'OK'],
        // ]);
    }
}
