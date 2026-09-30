<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admision_id')->constrained('admisiones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('estado', 50)->default('');
            $table->string('archivo_nombre', 100)->default('');
            $table->text('archivo')->nullable();
            $table->string('archivo_hash', 50)->nullable();
            $table->boolean('autorizado')->default(false);
            $table->foreignId('estudiante_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_estudiante');
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_docente');
            $table->foreign(['estudiante_id', 'especialidad_estudiante'])
                ->references(['usuario_id', 'clinica_id'])
                ->on('especialidades')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign(['docente_id', 'especialidad_docente'])
                ->references(['usuario_id', 'clinica_id'])
                ->on('especialidades')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->text('revision_docente')->nullable();
            $table->boolean('revision_cualquiera')->default(false);
            $table->boolean('estado_revision')->default(false);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};
