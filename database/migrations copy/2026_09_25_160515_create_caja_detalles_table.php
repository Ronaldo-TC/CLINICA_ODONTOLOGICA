<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('tratamiento_realizado_id')->nullable()->constrained('tratamientos_realizados')->nullOnDelete()->cascadeOnUpdate();
            $table->string('concepto', 255);
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('monto', 10, 2)->default(0);
            $table->integer('fecha')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_detalles');
    }
};
