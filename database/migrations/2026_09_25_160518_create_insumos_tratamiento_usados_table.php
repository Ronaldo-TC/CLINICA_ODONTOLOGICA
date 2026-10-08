<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_tratamiento_usados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('insumo_solicitado_id')
                ->nullable()
                ->constrained('insumos_solicitados')
                ->nullOnDelete();

            $table->foreignId('insumo_solicitado_detalle_id')->nullable()->constrained('insumos_solicitados_detalle')->nullOnDelete();
            $table->foreignId('sesion_atencion_id')->nullable()->constrained('sesiones_atencion')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();

            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();

            $table->foreignId('tratamiento_realizado_id')
                ->nullable()
                ->constrained('tratamientos_realizados')
                ->nullOnDelete();

            $table->decimal('cantidad_solicitada', 10, 2)->default(0);
            $table->decimal('cantidad_usada', 10, 2)->default(0);
            $table->decimal('costo_al_momento', 10, 2)->default(0);
            $table->date('fecha_uso')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_tratamiento_usados');
    }
};
