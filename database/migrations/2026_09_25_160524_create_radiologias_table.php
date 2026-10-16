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

            // ── NUEVA: relación con solicitudes_radiologicas ──
            $table->foreignId('solicitud_radiologica_id')
                ->nullable()
                ->unique()
                ->constrained('solicitudes_radiologicas')
                ->restrictOnDelete();

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
