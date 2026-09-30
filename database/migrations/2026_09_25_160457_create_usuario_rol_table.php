<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('rol_id')->constrained('roles1')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamps();
            $table->primary(['usuario_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
};
