<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_solicitados_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_solicitado_id')->constrained('insumos_solicitados')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_entregada', 10, 2)->default(0);
            $table->integer('fecha')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_solicitados_detalle');
    }
};
