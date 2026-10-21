<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('periodos', 'semestre')) {
            return;
        }

        $referenceTables = [
            'admisiones',
            'remisiones',
            'historias_clinicas',
            'esterilizaciones',
            'cajas',
            'radiologias',
        ];

        DB::transaction(function () use ($referenceTables): void {
            $periodosPorGestion = DB::table('periodos')
                ->orderBy('id')
                ->get()
                ->groupBy('gestion');

            foreach ($periodosPorGestion as $gestion => $periodos) {
                $periodoConUso = $periodos
                    ->map(function (object $periodo) use ($referenceTables): object {
                        $periodo->referencias = collect($referenceTables)
                            ->filter(fn (string $table): bool => Schema::hasTable($table) && Schema::hasColumn($table, 'periodo_id'))
                            ->sum(fn (string $table): int => DB::table($table)->where('periodo_id', $periodo->id)->count());

                        return $periodo;
                    })
                    ->sortBy([
                        ['referencias', 'desc'],
                        ['id', 'asc'],
                    ])
                    ->first();

                $idsDuplicados = $periodos->pluck('id')->reject(
                    fn (int $id): bool => $id === $periodoConUso->id
                );

                foreach ($referenceTables as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'periodo_id')) {
                        DB::table($table)
                            ->whereIn('periodo_id', $idsDuplicados)
                            ->update(['periodo_id' => $periodoConUso->id]);
                    }
                }

                DB::table('periodos')->where('id', $periodoConUso->id)->update([
                    'nombre' => $this->nombreAnual($periodoConUso->nombre, (int) $gestion),
                    'fecha_inicio' => $periodos->min('fecha_inicio'),
                    'fecha_fin' => $periodos->max('fecha_fin'),
                    'habilitado' => $periodos->contains(fn (object $periodo): bool => (bool) $periodo->habilitado),
                    'updated_at' => now(),
                ]);

                DB::table('periodos')->whereIn('id', $idsDuplicados)->delete();
            }
        });

        Schema::table('periodos', function (Blueprint $table) {
            $table->dropUnique('periodos_gestion_semestre_unique');
            $table->dropColumn('semestre');
            $table->unique('gestion');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('periodos', 'semestre')) {
            return;
        }

        Schema::table('periodos', function (Blueprint $table) {
            $table->dropUnique('periodos_gestion_unique');
            $table->unsignedTinyInteger('semestre')->default(1)->after('gestion');
            $table->unique(['gestion', 'semestre']);
        });
    }

    private function nombreAnual(string $nombre, int $gestion): string
    {
        $nombreAnual = preg_replace('/\s*-?\s*semestre\s+\d+\s*$/iu', '', $nombre);

        return filled($nombreAnual) ? trim($nombreAnual) : "Gestión {$gestion}";
    }
};
