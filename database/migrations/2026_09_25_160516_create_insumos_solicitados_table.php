<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_solicitados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remision_id')
                ->nullable()
                ->constrained('remisiones')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete();
            $table->foreignId('insumo_tratamiento_id')->nullable()->constrained('insumos_tratamiento')->nullOnDelete();
            $table->foreignId('tratamiento_realizado_id')->nullable()->constrained('tratamientos_realizados')->nullOnDelete();
            $table->foreignId('sesion_atencion_id')->nullable()->constrained('sesiones_atencion')->nullOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->unsignedSmallInteger('pieza')->default(0);
            $table->text('insumos')->nullable();
            $table->boolean('docente_autoriza')->default(false);
            $table->boolean('autorizado')->default(false);
            $table->timestamp('fecha_autorizacion')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_rechazo')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->string('estado', 20)->default('pendiente');

            $table->date('fecha_solicitud')->useCurrent();
            $table->text('observaciones')->nullable();

            // ── NUEVAS: tipo de solicitud y flujo de jefatura ──
            $table->string('tipo_solicitud', 20)->default('clinica');

            $table->foreignId('jefatura_aprobado_por')
                ->nullable()
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamp('fecha_aprobacion_jefatura')->nullable();

            $table->foreignId('recibido_por')
                ->nullable()
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->timestamp('fecha_recepcion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_solicitados');
    }
};
