<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esterilizaciones', function (Blueprint $table) {
            $table->unsignedInteger('sobres_recibidos')->nullable();
            $table->unsignedInteger('paquetes_recibidos')->nullable();
            $table->json('codigos_material')->nullable();
            $table->text('observacion_recepcion')->nullable();
            $table->timestamp('fecha_preparacion')->nullable();
            $table->foreignId('preparado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->text('observacion_preparacion')->nullable();
            $table->json('preparaciones')->nullable();
            $table->foreignId('recogido_por')->nullable()->constrained('usuarios')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esterilizaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preparado_por');
            $table->dropConstrainedForeignId('recogido_por');
            $table->dropColumn(['sobres_recibidos', 'paquetes_recibidos', 'codigos_material', 'observacion_recepcion', 'fecha_preparacion', 'observacion_preparacion', 'preparaciones']);
        });
    }
};
