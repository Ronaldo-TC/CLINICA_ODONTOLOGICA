<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('esterilizacion_ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esterilizacion_id')->constrained('esterilizaciones')->restrictOnDelete();
            $table->string('equipo', 100);
            $table->string('numero_carga', 100);
            $table->string('protocolo', 200);
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->decimal('temperatura', 6, 2);
            $table->decimal('presion', 7, 3);
            $table->decimal('minutos_exposicion', 7, 2);
            $table->json('codigos_material');
            $table->unsignedInteger('version_preparacion');
            $table->json('preparacion');
            $table->foreignId('registrado_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('estado', 30)->default('registrado');
            $table->boolean('control_fisico')->nullable();
            $table->boolean('control_quimico')->nullable();
            $table->string('control_biologico', 20)->default('pendiente');
            $table->text('justificacion_biologico')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('revisiones')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_revision')->nullable();
            $table->foreignId('liberado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_liberacion')->nullable();
            $table->timestamps();
            $table->index(['equipo', 'numero_carga']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esterilizacion_ciclos');
    }
};
