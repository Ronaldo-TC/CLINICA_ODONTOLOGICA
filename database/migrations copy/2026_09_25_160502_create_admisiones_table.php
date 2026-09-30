<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('motivo_consulta_id')->nullable()->constrained('motivos_consulta')->nullOnDelete()->cascadeOnUpdate();
            $table->string('estado_civil', 200)->default('');
            $table->string('escuela', 200)->default('');
            $table->string('ocupacion', 200)->default('');
            $table->string('direccion', 200)->default('');
            $table->string('emergencia', 200)->default('');
            $table->integer('telefono')->default(0);
            $table->text('motivo_consulta')->nullable();
            $table->text('antecedentes')->nullable();
            $table->text('gmh')->nullable();
            $table->text('gpe')->nullable();
            $table->text('signos_vitales')->nullable();
            $table->text('ee')->nullable();
            $table->text('cn')->nullable();
            $table->text('odontograma')->nullable();
            $table->text('odontograma_pintado')->nullable();
            $table->text('atm')->nullable();
            $table->text('bm')->nullable();
            $table->text('gm')->nullable();
            $table->string('calculo', 50)->default('');
            $table->string('oclusion', 50)->default('');
            $table->string('pieza_dental', 200)->default('');
            $table->text('diagnostico')->nullable();
            $table->text('examen')->nullable();
            $table->text('firma')->nullable();
            $table->boolean('consentimiento_datos')->default(false);
            $table->boolean('consentimiento_investigacion')->default(false);
            $table->foreignId('estudiante_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_estudiante');
            $table->foreignId('responsable_id')->constrained('usuarios')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_responsable');
            $table->foreign(['estudiante_id', 'especialidad_estudiante'])
                ->references(['usuario_id', 'clinica_id'])
                ->on('especialidades')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign(['responsable_id', 'especialidad_responsable'])
                ->references(['usuario_id', 'clinica_id'])
                ->on('especialidades')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admisiones');
    }
};
