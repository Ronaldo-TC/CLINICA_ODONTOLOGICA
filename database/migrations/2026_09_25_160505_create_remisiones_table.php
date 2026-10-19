<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admision_id')->constrained('admisiones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('tratamiento_propuesto_id')->nullable()->index();
            $table->text('observacion_propuesta')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('estudiante_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_asignacion')->nullable();
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};
