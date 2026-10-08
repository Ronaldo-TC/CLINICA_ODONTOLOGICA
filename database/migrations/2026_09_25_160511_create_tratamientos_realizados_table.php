<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos_realizados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('tratamiento_id')->constrained('tratamientos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('pieza_dental', 20)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->decimal('costo_cobrado', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_autorizacion')->nullable();
            $table->foreignId('finalizado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_finalizacion')->nullable();
            $table->foreignId('culminado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_culminacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos_realizados');
    }
};
