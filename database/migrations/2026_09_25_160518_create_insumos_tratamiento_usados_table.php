<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_tratamiento_usados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('tratamiento_realizado_id')->constrained('tratamientos_realizados')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('cantidad_usada', 10, 2)->default(1);
            $table->decimal('costo_al_momento', 10, 2)->default(0);
            $table->date('fecha')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_tratamiento_usados');
    }
};
