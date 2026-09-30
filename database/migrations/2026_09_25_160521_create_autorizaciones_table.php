<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorizaciones', function (Blueprint $table) {
            $table->foreignId('esterilizacion_id')->constrained('esterilizaciones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('remision_id')->constrained('remisiones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('estado')->default(false);
            $table->string('descripcion', 200)->default('');
            $table->string('docente', 200)->default('');
            $table->integer('sobre');
            $table->timestamps();
            $table->primary(['esterilizacion_id', 'remision_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizaciones');
    }
};
