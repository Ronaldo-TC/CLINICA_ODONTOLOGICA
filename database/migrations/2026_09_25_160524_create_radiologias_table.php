<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiologias', function (Blueprint $table) {
            $table->id();

            $table->string('descripcion', 100);
            $table->string('observacion', 100);
            $table->string('estado', 50);

            // ── Relaciones principales ──
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('caja_id')
                ->constrained('cajas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            //  NUEVA: clínica a la que pertenece la radiografía
            // El orden de declaración ya la coloca después de caja_id.
            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('paciente_id')
                ->nullable()
                ->constrained('pacientes')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('remision_id')
                ->nullable()
                ->constrained('remisiones')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // ── Datos de la radiografía ──
            $table->string('archivo_ruta', 500)->default('');
            $table->dateTime('fecha_toma')->nullable();

            $table->timestamps();

            // ── Índices para reportes ──
            $table->index('clinica_id', 'radiologias_clinica_id_index');
            $table->index('estado', 'radiologias_estado_index');
            $table->index('fecha_toma', 'radiologias_fecha_toma_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiologias');
    }
};
