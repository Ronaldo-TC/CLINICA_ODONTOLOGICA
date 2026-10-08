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
        if (Schema::hasTable('insumos_solicitados_detalle')) {
            return;
        }

        Schema::create('insumos_solicitados_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_solicitado_id')->constrained('insumos_solicitados')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_entregada', 10, 2)->default(0);
            $table->date('fecha')->nullable();
            $table->timestamps();
            $table->unique(['insumo_solicitado_id', 'insumo_id'], 'solicitud_insumo_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos_solicitados_detalle');
    }
};
