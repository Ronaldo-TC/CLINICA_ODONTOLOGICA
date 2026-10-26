<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tablasRelacionadas() as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'periodo_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('periodo_id');
            });
        }

        Schema::dropIfExists('periodos');
    }

    public function down(): void
    {
        if (! Schema::hasTable('periodos')) {
            Schema::create('periodos', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 100);
                $table->integer('gestion')->unique();
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->boolean('habilitado')->default(true);
                $table->timestamps();
            });
        }

        foreach ($this->tablasRelacionadas() as $tabla) {
            if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'periodo_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreignId('periodo_id')
                    ->nullable()
                    ->constrained('periodos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    /** @return array<int, string> */
    private function tablasRelacionadas(): array
    {
        return [
            'admisiones',
            'remisiones',
            'historias_clinicas',
            'cajas',
            'esterilizaciones',
            'radiologias',
        ];
    }
};
