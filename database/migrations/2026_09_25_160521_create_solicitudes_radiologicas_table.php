<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_radiologicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')
                ->constrained('historias_clinicas')
                ->cascadeOnDelete();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->restrictOnDelete();
            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->restrictOnDelete();
            $table->foreignId('solicitante_id')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->foreignId('docente_id')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->string('estado', 30)->default('pendiente');
            $table->string('descripcion', 150);
            $table->text('observacion')->nullable();
            $table->decimal('costo_autorizado', 10, 2)->nullable();
            $table->datetime('fecha_revision')->nullable();
            $table->timestamps();

            $table->index(['historia_clinica_id', 'estado'], 'sol_rad_historia_estado_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_radiologicas');
    }
};
