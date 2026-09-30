<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->integer('gestion');
            $table->integer('semestre');
            $table->integer('fecha_inicio');
            $table->integer('fecha_fin');
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
            $table->unique(['gestion', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
