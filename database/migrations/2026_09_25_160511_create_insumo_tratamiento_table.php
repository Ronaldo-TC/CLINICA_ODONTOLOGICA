<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumo_tratamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_id')->constrained('tratamientos')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['tratamiento_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumo_tratamiento');
    }
};
