<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('cantidad_numerica', 10, 2)->default(0);
            $table->string('cantidad', 100)->default('');
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->foreignId('insumo_tratamiento_id')->constrained('insumos_tratamiento')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
