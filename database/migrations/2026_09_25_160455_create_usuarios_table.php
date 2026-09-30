<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 50)->unique();
            $table->string('nombre_usuario', 20)->unique();
            $table->string('nombre_completo', 200);
            $table->string('descripcion', 300)->nullable();
            $table->string('tipo', 20)->nullable();
            $table->boolean('habilitado')->default(true);
            $table->boolean('permitir_login')->default(true);
            $table->boolean('multilogin')->default(false);
            $table->string('contrasena', 200)->default('');
            $table->string('ip', 300)->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->string('sesion', 50)->default('');
            $table->string('sesion_extra', 50)->default('');
            $table->timestamp('ultimo_logout')->nullable();
            $table->string('ip_permitida', 300)->nullable();
            $table->string('info', 300)->default('');
            $table->string('foto', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
