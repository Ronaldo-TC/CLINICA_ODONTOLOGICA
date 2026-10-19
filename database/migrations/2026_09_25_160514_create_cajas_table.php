<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->integer('recibo');
            $table->integer('monto');
            $table->decimal('costo_total', 10, 2)->default(0);
            $table->decimal('cancelado', 10, 2)->default(0);
            $table->boolean('habilitada')->default(true);
            $table->string('descripcion', 100)->nullable();
            $table->integer('ci')->default(0);
            $table->string('tipo', 100)->nullable();
            $table->string('tipo_pago', 100)->nullable();
            $table->string('paciente_nombre_completo', 100)->nullable();
            $table->string('grado', 100)->nullable();

            // ── Relaciones ──
            $table->foreignId('tratamiento_realizado_id')
                ->nullable()
                ->constrained('tratamientos_realizados')
                ->restrictOnDelete();

            $table->foreignId('tratamiento_id')
                ->nullable()
                ->constrained('tratamientos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('remision_id')
                ->nullable()
                ->constrained('remisiones')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('paciente_id')
                ->nullable()
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('periodo_id')
                ->nullable()
                ->constrained('periodos')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique('recibo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
