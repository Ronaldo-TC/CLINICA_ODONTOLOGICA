<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('habilitado')->default(true);
            $table->integer('rotacion')->default(-1);
            $table->boolean('responsable_admision')->default(false);
            $table->date('fecha_inicio')->nullable();
            $table->timestamps();
            $table->primary(['usuario_id', 'clinica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades');
    }
};
