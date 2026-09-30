<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shico_sincronizacion', function (Blueprint $table) {
            $table->id();
            $table->string('entidad', 50);
            $table->integer('id_local');
            $table->string('id_shico', 100)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->integer('ultimo_intento')->default(-1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shico_sincronizacion');
    }
};
