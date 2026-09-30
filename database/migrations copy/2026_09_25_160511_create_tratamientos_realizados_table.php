<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos_realizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('tratamiento_id')->constrained('tratamientos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('pieza_dental', 20)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->decimal('costo_cobrado', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->integer('fecha_inicio')->default(0);
            $table->integer('fecha_fin')->default(-1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos_realizados');
    }
};
