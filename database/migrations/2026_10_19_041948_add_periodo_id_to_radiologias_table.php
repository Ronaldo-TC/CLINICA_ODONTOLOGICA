<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('radiologias', 'periodo_id')) {
            Schema::table('radiologias', function (Blueprint $table): void {
                $table->foreignId('periodo_id')
                    ->nullable()
                    ->after('paciente_id')
                    ->constrained('periodos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('radiologias', 'periodo_id')) {
            Schema::table('radiologias', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('periodo_id');
            });
        }
    }
};
