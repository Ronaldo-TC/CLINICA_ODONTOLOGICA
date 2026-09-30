<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles1')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
            $table->primary(['rol_id', 'permiso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
    }
};
