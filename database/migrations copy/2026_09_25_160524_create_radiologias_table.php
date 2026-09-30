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
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('remision_id')->nullable()->constrained('remisiones')->nullOnDelete()->cascadeOnUpdate();
            $table->string('archivo_ruta', 500)->default('');
            $table->integer('fecha_toma')->default(-1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiologias');
    }
};
