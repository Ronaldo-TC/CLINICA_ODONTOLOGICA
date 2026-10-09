<?php

use Database\Seeders\PacientesContactoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable();
            $table->string('estado_civil', 200)->nullable();
            $table->string('escuela', 200)->nullable();
            $table->string('ocupacion', 200)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('emergencia', 200)->nullable();
            $table->string('telefono_emergencia', 30)->nullable();
        });

        app(PacientesContactoSeeder::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'estado_civil', 'escuela', 'ocupacion', 'direccion', 'emergencia', 'telefono_emergencia']);
        });
    }
};
