<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('historia_clinica_id')->nullable()->constrained('historias_clinicas')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('remision_id')->nullable()->constrained('remisiones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('radiologia_id')->nullable()->constrained('radiologias')->nullOnDelete()->cascadeOnUpdate();
            $table->string('nombre_original', 255)->nullable();
            $table->string('nombre_guardado', 255)->nullable();
            $table->string('ruta', 500)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano_bytes')->default(0);
            $table->string('tipo', 50)->nullable();
            $table->string('hash', 128)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
