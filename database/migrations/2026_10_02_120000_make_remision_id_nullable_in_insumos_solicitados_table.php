<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una solicitud de insumos no siempre proviene de una remisión
 * (una solicitud directa del estudiante no tiene remisión asociada).
 * La columna era NOT NULL, lo que impedía crear solicitudes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->dropForeign(['remision_id']);
        });

        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->foreignId('remision_id')
                ->nullable()
                ->change();
        });

        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->foreign('remision_id')
                ->references('id')
                ->on('remisiones')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->dropForeign(['remision_id']);
        });

        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->foreignId('remision_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('insumos_solicitados', function (Blueprint $table) {
            $table->foreign('remision_id')
                ->references('id')
                ->on('remisiones')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
