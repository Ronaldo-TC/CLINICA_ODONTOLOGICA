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
            $table->integer('pieza');
            $table->text('insumos')->nullable();
            $table->integer('docente_autoriza')->default(0);
            $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('autorizado')->default(false);
            $table->foreignId('insumo_tratamiento_id')->constrained('insumos_tratamiento')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('remision_id')->constrained('remisiones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('tratamiento_realizado_id')->nullable()->constrained('tratamientos_realizados')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('fecha_autorizacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_solicitados');
    }
};
