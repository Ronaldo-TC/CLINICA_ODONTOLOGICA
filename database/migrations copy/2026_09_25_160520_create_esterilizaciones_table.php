<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esterilizaciones', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidad_sobres');
            $table->integer('cantidad_paquetes');
            $table->boolean('habilitada')->default(true);
            $table->string('info', 200)->default('');
            $table->string('descripcion', 200)->default('');
            $table->integer('fecha_entrega')->default(-1);
            $table->integer('fecha_recojo')->default(-1);
            $table->foreignId('estudiante_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esterilizaciones');
    }
};
