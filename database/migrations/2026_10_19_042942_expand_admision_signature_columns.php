<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admisiones', function (Blueprint $table): void {
            $table->longText('odontograma_pintado')->nullable()->change();
            $table->longText('firma')->nullable()->change();
        });
    }

    public function down(): void
    {
        /* No se reduce el tamaño para evitar truncar datos clínicos existentes. */
    }
};
