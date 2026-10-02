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

            // ─── Identificación ────────────────────────────────
            $table->string('ci', 50)->unique();
            $table->string('nombre_usuario', 20)->unique();
            $table->string('nombre_completo', 200);

            // ─── Info personal ────────────────────────────────
            $table->string('descripcion', 300)->default('');
            $table->string('tipo', 20)->default('');

            // ─── Estado y permisos ────────────────────────────
            $table->boolean('habilitado')->default(true);
            $table->boolean('permitir_login')->default(true);
            $table->boolean('multilogin')->default(false);

            // ─── Autenticación ────────────────────────────────
            $table->string('contrasena', 200)->default('');

            // ─── Sesión ───────────────────────────────────────
            $table->string('ip', 300)->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->string('sesion', 100)->default('');
            $table->string('sesion_extra', 100)->default('');
            $table->timestamp('ultimo_logout')->nullable();
            $table->string('ip_permitida', 300)->nullable();

            // ─── Extras ───────────────────────────────────────
            $table->string('info', 300)->default('');
            $table->string('foto', 300)->nullable();

            // ─── Índices para queries frecuentes ──────────────
            $table->index('habilitado');
            $table->index('tipo');
            $table->index('permitir_login');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
