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
            $table->string('estado_civil', 200)->nullable();
            $table->string('escuela', 200)->nullable();
            $table->string('ocupacion', 200)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('emergencia', 200)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('telefono_emergencia', 30)->nullable();
            $table->text('historia_enfermedad_actual')->nullable();
            $table->decimal('peso_kg', 6, 2)->nullable();
            $table->decimal('talla_mts', 4, 2)->nullable();
            $table->decimal('temperatura_c', 5, 2)->nullable();
            $table->unsignedSmallInteger('pulso')->nullable();
            $table->string('presion_arterial', 20)->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->text('motivo_consulta')->nullable();
            $table->text('antecedentes')->nullable();
            $table->text('gmh')->nullable();
            $table->text('gpe')->nullable();
            $table->boolean('asimetria_facial')->nullable();
            $table->boolean('cambio_color')->nullable();
            $table->boolean('abultamiento')->nullable();
            $table->boolean('ganglios_palpables')->nullable();
            $table->boolean('ruidos_articulares')->nullable();
            $table->string('bm_estado', 50)->nullable();
            $table->string('gm_estado', 50)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('signos_vitales')->nullable();
            $table->text('ee')->nullable();
            $table->text('cn')->nullable();
            $table->text('odontograma')->nullable();
            $table->longText('odontograma_pintado')->nullable();
            $table->string('odontograma_denticion', 20)->nullable();
            $table->text('atm')->nullable();
            $table->text('bm')->nullable();
            $table->text('gm')->nullable();
            $table->string('calculo', 50)->default('');
            $table->string('oclusion', 50)->default('');
            $table->string('pieza_dental', 200)->default('');
            $table->text('diagnostico')->nullable();
            $table->text('area_derivada')->nullable();
            $table->text('examen')->nullable();
            $table->longText('firma')->nullable();
            $table->boolean('consentimiento_datos')->default(false);
            $table->boolean('consentimiento_fotografias')->default(false);
            $table->boolean('consentimiento_investigacion')->default(false);
            $table->date('fecha_admision')->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_autorizacion')->nullable();
            $table->foreignId('estudiante_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_estudiante')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('usuarios')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('especialidad_responsable')->nullable();
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
