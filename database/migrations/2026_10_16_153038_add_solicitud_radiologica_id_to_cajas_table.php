<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->foreignId('solicitud_radiologica_id')
                ->nullable()
                ->after('periodo_id')
                ->constrained('solicitudes_radiologicas')
                ->restrictOnDelete();

            $table->index(['solicitud_radiologica_id', 'habilitada'], 'cajas_solicitud_radiologica_habilitada_index');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropIndex('cajas_solicitud_radiologica_habilitada_index');
            $table->dropForeign(['solicitud_radiologica_id']);
            $table->dropColumn('solicitud_radiologica_id');
        });
    }
};
