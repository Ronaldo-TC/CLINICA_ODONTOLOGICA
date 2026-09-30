<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('costo', 10, 2)->default(0);
            $table->string('porcentaje_pg', 100)->nullable();
            $table->string('descripcion', 100)->default('');
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
