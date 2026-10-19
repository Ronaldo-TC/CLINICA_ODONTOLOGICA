<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 50)->nullable();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100)->nullable();
            $table->string('apellido_materno', 100)->nullable();
            $table->string('genero', 50)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->timestamps();
            $table->string('telefono', 30)->nullable();
            $table->string('estado_civil', 200)->nullable();
            $table->string('escuela', 200)->nullable();
            $table->string('ocupacion', 200)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('emergencia', 200)->nullable();
            $table->string('telefono_emergencia', 30)->nullable();
            $table->index(['apellido_paterno', 'apellido_materno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
