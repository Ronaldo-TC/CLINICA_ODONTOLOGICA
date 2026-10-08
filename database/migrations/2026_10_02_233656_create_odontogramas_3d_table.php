<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Catálogo de condiciones (opcional pero recomendado)
        Schema::create('condiciones_odontologicas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('color_hex', 7)->default('#FFFFFF');
            $table->string('mesh_3d', 100)->nullable()->comment('nombre mesh Three.js');
            $table->string('icono', 100)->nullable();
            $table->boolean('habilitado')->default(true);
            $table->timestamps();
        });

        // 2. Odontograma (cabecera + layout visual)
        Schema::create('odontogramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admision_id')->constrained('admisiones')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('historia_clinica_id')->nullable()
                ->constrained('historias_clinicas')->nullOnDelete();
            $table->integer('version')->default(1);
            $table->enum('tipo', ['inicial', 'evolucion', 'alta', 'control'])->default('inicial');
            $table->enum('estado', ['borrador', 'finalizado', 'anulado'])->default('borrador');
            // SOLO layout visual Three.js (cámara, zoom, luces, mesh ids)
            $table->json('layout_3d')->nullable()
                ->comment('{camera:{x,y,z}, zoom, lights, mesh_map}');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['paciente_id', 'admision_id', 'version']);
            $table->index(['paciente_id', 'created_at']);
        });

        // 3. Hallazgos por pieza dental
        Schema::create('odontograma_hallazgos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odontograma_id')->constrained('odontogramas')->cascadeOnDelete();
            // FDI 11-48, 51-85 (string para soportar combinaciones futuras)
            $table->string('pieza_dental', 10);
            // Combinaciones de caras: M, O, D, V, L, MOD, etc.
            $table->string('caras', 20)->default('')->comment('M,O,D,V,L, MOD, etc.');
            $table->enum('condicion', [
                'caries',
                'obturacion',
                'ausente',
                'corona',
                'endodoncia',
                'implante',
                'sellante',
                'fractura',
                'movilidad',
                'bolsa_periodontal',
                'mancha',
                'desgaste',
                'giroversión',
                'diastema',
                'supernumerario',
            ])->default('caries');
            $table->enum('severidad', ['leve', 'moderada', 'severa'])->default('leve');
            $table->string('color', 7)->nullable()->comment('#FF0000 para Three.js');
            $table->tinyInteger('movilidad')->nullable()->comment('0-3');
            $table->decimal('sondaje_mm', 4, 1)->nullable();
            $table->text('notas')->nullable();
            // Vínculo con tratamiento (evolución clínica)
            $table->foreignId('tratamiento_realizado_id')->nullable()
                ->constrained('tratamientos_realizados')->nullOnDelete();
            // Auditoría
            $table->foreignId('created_by')->nullable()
                ->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['odontograma_id', 'pieza_dental']);
            $table->index(['pieza_dental', 'condicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontograma_hallazgos');
        Schema::dropIfExists('odontogramas');
        Schema::dropIfExists('condiciones_odontologicas');
    }
};
