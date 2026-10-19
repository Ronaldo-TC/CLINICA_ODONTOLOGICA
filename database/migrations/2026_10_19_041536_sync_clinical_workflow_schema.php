<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('pacientes', 'escuela')) {
                $table->string('escuela', 200)->nullable();
            }
        });

        Schema::table('admisiones', function (Blueprint $table): void {
            if (! Schema::hasColumn('admisiones', 'telefono_emergencia')) {
                $table->string('telefono_emergencia', 30)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'historia_enfermedad_actual')) {
                $table->text('historia_enfermedad_actual')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'peso_kg')) {
                $table->decimal('peso_kg', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'talla_mts')) {
                $table->decimal('talla_mts', 4, 2)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'temperatura_c')) {
                $table->decimal('temperatura_c', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'pulso')) {
                $table->unsignedSmallInteger('pulso')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'presion_arterial')) {
                $table->string('presion_arterial', 20)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'frecuencia_respiratoria')) {
                $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'asimetria_facial')) {
                $table->boolean('asimetria_facial')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'cambio_color')) {
                $table->boolean('cambio_color')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'abultamiento')) {
                $table->boolean('abultamiento')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'ganglios_palpables')) {
                $table->boolean('ganglios_palpables')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'ruidos_articulares')) {
                $table->boolean('ruidos_articulares')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'bm_estado')) {
                $table->string('bm_estado', 50)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'gm_estado')) {
                $table->string('gm_estado', 50)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'registrado_por')) {
                $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('admisiones', 'odontograma_denticion')) {
                $table->string('odontograma_denticion', 20)->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'area_derivada')) {
                $table->text('area_derivada')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'consentimiento_fotografias')) {
                $table->boolean('consentimiento_fotografias')->default(false);
            }
            if (! Schema::hasColumn('admisiones', 'fecha_admision')) {
                $table->date('fecha_admision')->nullable();
            }
            if (! Schema::hasColumn('admisiones', 'estado')) {
                $table->string('estado', 30)->default('pendiente');
            }
            if (! Schema::hasColumn('admisiones', 'autorizado_por')) {
                $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('admisiones', 'fecha_autorizacion')) {
                $table->timestamp('fecha_autorizacion')->nullable();
            }
        });

        Schema::table('admisiones', function (Blueprint $table): void {
            $table->string('telefono', 30)->nullable()->change();
            $table->unsignedBigInteger('estudiante_id')->nullable()->change();
            $table->unsignedBigInteger('especialidad_estudiante')->nullable()->change();
            $table->unsignedBigInteger('responsable_id')->nullable()->change();
            $table->unsignedBigInteger('especialidad_responsable')->nullable()->change();
        });

        Schema::table('remisiones', function (Blueprint $table): void {
            if (! Schema::hasColumn('remisiones', 'tratamiento_propuesto_id')) {
                $table->unsignedBigInteger('tratamiento_propuesto_id')->nullable()->index();
            }
            if (! Schema::hasColumn('remisiones', 'observacion_propuesta')) {
                $table->text('observacion_propuesta')->nullable();
            }
            if (! Schema::hasColumn('remisiones', 'estado')) {
                $table->string('estado', 20)->default('pendiente');
            }
            if (! Schema::hasColumn('remisiones', 'estudiante_id')) {
                $table->foreignId('estudiante_id')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('remisiones', 'docente_id')) {
                $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('remisiones', 'asignado_por')) {
                $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('remisiones', 'fecha_asignacion')) {
                $table->timestamp('fecha_asignacion')->nullable();
            }
        });

        Schema::table('remisiones', function (Blueprint $table): void {
            $table->unsignedBigInteger('periodo_id')->nullable()->change();
        });

        if (! Schema::hasForeignKey('remisiones', 'remisiones_tratamiento_propuesto_id_foreign')) {
            Schema::table('remisiones', function (Blueprint $table): void {
                $table->foreign('tratamiento_propuesto_id')->references('id')->on('tratamientos')->nullOnDelete();
            });
        }

        Schema::table('historias_clinicas', function (Blueprint $table): void {
            if (! Schema::hasColumn('historias_clinicas', 'remision_id')) {
                $table->foreignId('remision_id')->nullable()->constrained('remisiones')->nullOnDelete()->cascadeOnUpdate();
            }
        });

        Schema::table('cajas', function (Blueprint $table): void {
            if (! Schema::hasColumn('cajas', 'tratamiento_realizado_id')) {
                $table->foreignId('tratamiento_realizado_id')->nullable()->constrained('tratamientos_realizados')->restrictOnDelete();
            }
        });

        Schema::table('insumos_solicitados', function (Blueprint $table): void {
            if (! Schema::hasColumn('insumos_solicitados', 'historia_clinica_id')) {
                $table->foreignId('historia_clinica_id')->nullable()->constrained('historias_clinicas')->nullOnDelete();
            }
            if (! Schema::hasColumn('insumos_solicitados', 'tratamiento_id')) {
                $table->foreignId('tratamiento_id')->nullable()->constrained('tratamientos')->nullOnDelete();
            }
            if (! Schema::hasColumn('insumos_solicitados', 'aprobado_por')) {
                $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            }
            if (! Schema::hasColumn('insumos_solicitados', 'fecha_aprobacion')) {
                $table->timestamp('fecha_aprobacion')->nullable();
            }
        });

        Schema::table('esterilizaciones', function (Blueprint $table): void {
            if (! Schema::hasColumn('esterilizaciones', 'remision_id')) {
                $table->foreignId('remision_id')->nullable()->constrained('remisiones')->nullOnDelete();
            }
            if (! Schema::hasColumn('esterilizaciones', 'docente_id')) {
                $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('esterilizaciones', 'autorizado')) {
                $table->boolean('autorizado')->default(false);
            }
            if (! Schema::hasColumn('esterilizaciones', 'fecha_autorizacion')) {
                $table->timestamp('fecha_autorizacion')->nullable();
            }
            if (! Schema::hasColumn('esterilizaciones', 'firma_docente')) {
                $table->longText('firma_docente')->nullable();
            }
            if (! Schema::hasColumn('esterilizaciones', 'hora_entrega')) {
                $table->time('hora_entrega')->nullable();
            }
            if (! Schema::hasColumn('esterilizaciones', 'hora_recojo')) {
                $table->time('hora_recojo')->nullable();
            }
            if (! Schema::hasColumn('esterilizaciones', 'recibido_por')) {
                $table->foreignId('recibido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('esterilizaciones', 'entregado_por')) {
                $table->foreignId('entregado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('insumos_entregas')) {
            Schema::create('insumos_entregas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('insumo_solicitado_id')->constrained('insumos_solicitados')->cascadeOnDelete();
                $table->foreignId('insumo_solicitado_detalle_id')->constrained('insumos_solicitados_detalle')->cascadeOnDelete();
                $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
                $table->foreignId('tratamiento_realizado_id')->nullable()->constrained('tratamientos_realizados')->nullOnDelete();
                $table->foreignId('sesion_atencion_id')->nullable()->constrained('sesiones_atencion')->nullOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->decimal('cantidad', 10, 2);
                $table->decimal('costo_unitario', 10, 2)->default(0);
                $table->timestamp('fecha_entrega')->nullable();
                $table->timestamps();
            });
        }

        DB::table('admisiones')->whereNull('fecha_admision')->update([
            'fecha_admision' => DB::raw('DATE(created_at)'),
        ]);

        DB::statement("UPDATE remisiones r INNER JOIN historias_clinicas h ON h.admision_id = r.admision_id AND h.clinica_id = r.clinica_id SET r.estudiante_id = COALESCE(r.estudiante_id, h.estudiante_id), r.docente_id = COALESCE(r.docente_id, h.docente_id), r.asignado_por = COALESCE(r.asignado_por, h.docente_id), r.fecha_asignacion = COALESCE(r.fecha_asignacion, h.created_at), r.estado = 'asignada'");
        DB::statement('UPDATE historias_clinicas h INNER JOIN remisiones r ON r.admision_id = h.admision_id AND r.clinica_id = h.clinica_id SET h.remision_id = r.id WHERE h.remision_id IS NULL');
        DB::statement("UPDATE admisiones a SET a.estado = 'derivada' WHERE EXISTS (SELECT 1 FROM remisiones r WHERE r.admision_id = a.id)");
    }

    public function down(): void
    {
        /* Esta conciliaciÃ³n no elimina columnas clÃ­nicas ni datos existentes. */
    }
};
