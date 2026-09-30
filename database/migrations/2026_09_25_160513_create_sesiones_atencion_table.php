<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_realizado_id')->constrained('tratamientos_realizados')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('fecha')->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('estudiante_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('observacion_docente')->nullable();
            $table->boolean('autorizado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_atencion');
    }
};
