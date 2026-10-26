<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Insumo;
use App\Models\InsumoTratamiento;
use App\Models\Tratamiento;
use Illuminate\Database\Seeder;

class InsumosSeeder extends Seeder
{
    public function run(): void
    {
        $insumos = $this->crearInventario();
        $this->vincularTratamientos($insumos);
    }

    /** @return array<string, array<string, Insumo>> */
    private function crearInventario(): array
    {
        $catalogo = [
            'Operatoria Dental' => [
                ['tipo' => 'Resina compuesta', 'nombre' => 'Resina fotopolimerizable A2', 'unidad' => 'jeringas', 'stock' => 30, 'minimo' => 8, 'costo' => 150],
                ['tipo' => 'Resina compuesta', 'nombre' => 'Ácido fosfórico al 37%', 'unidad' => 'jeringas', 'stock' => 36, 'minimo' => 10, 'costo' => 42],
                ['tipo' => 'Resina compuesta', 'nombre' => 'Adhesivo dental universal', 'unidad' => 'frascos', 'stock' => 18, 'minimo' => 5, 'costo' => 125],
                ['tipo' => 'Resina compuesta', 'nombre' => 'Microaplicadores dentales', 'unidad' => 'cajas', 'stock' => 24, 'minimo' => 6, 'costo' => 48],
                ['tipo' => 'Aislamiento', 'nombre' => 'Dique de goma', 'unidad' => 'cajas', 'stock' => 15, 'minimo' => 4, 'costo' => 68],
                ['tipo' => 'Aislamiento', 'nombre' => 'Rollos de algodón', 'unidad' => 'paquetes', 'stock' => 40, 'minimo' => 10, 'costo' => 28],
            ],
            'Endodoncia' => [
                ['tipo' => 'Limas endodónticas', 'nombre' => 'Limas K primera serie', 'unidad' => 'cajas', 'stock' => 20, 'minimo' => 5, 'costo' => 88],
                ['tipo' => 'Hipoclorito', 'nombre' => 'Hipoclorito de sodio al 2,5%', 'unidad' => 'frascos', 'stock' => 24, 'minimo' => 6, 'costo' => 36],
                ['tipo' => 'Conos de gutapercha', 'nombre' => 'Conos de gutapercha surtidos', 'unidad' => 'cajas', 'stock' => 22, 'minimo' => 6, 'costo' => 64],
                ['tipo' => 'Conos de gutapercha', 'nombre' => 'Cemento sellador endodóntico', 'unidad' => 'kits', 'stock' => 12, 'minimo' => 3, 'costo' => 98],
            ],
            'Cirugía Bucal' => [
                ['tipo' => 'Sutura', 'nombre' => 'Sutura seda 3-0', 'unidad' => 'sobres', 'stock' => 50, 'minimo' => 12, 'costo' => 18],
                ['tipo' => 'Anestésico', 'nombre' => 'Lidocaína al 2% con epinefrina', 'unidad' => 'cajas', 'stock' => 28, 'minimo' => 8, 'costo' => 96],
                ['tipo' => 'Gasa estéril', 'nombre' => 'Gasas estériles 10 x 10 cm', 'unidad' => 'paquetes', 'stock' => 45, 'minimo' => 12, 'costo' => 42],
            ],
            'Periodoncia' => [
                ['tipo' => 'Curetas', 'nombre' => 'Cureta Gracey', 'unidad' => 'unidades', 'stock' => 18, 'minimo' => 4, 'costo' => 185],
                ['tipo' => 'Clorhexidina', 'nombre' => 'Clorhexidina al 0,12%', 'unidad' => 'frascos', 'stock' => 26, 'minimo' => 7, 'costo' => 46],
                ['tipo' => 'Anestésico', 'nombre' => 'Mepivacaína al 3%', 'unidad' => 'cajas', 'stock' => 16, 'minimo' => 5, 'costo' => 102],
            ],
            'Radiología' => [
                ['tipo' => 'Película radiográfica', 'nombre' => 'Película periapical adulto', 'unidad' => 'cajas', 'stock' => 25, 'minimo' => 6, 'costo' => 255],
                ['tipo' => 'Revelador', 'nombre' => 'Líquido revelador radiográfico', 'unidad' => 'bidones', 'stock' => 10, 'minimo' => 3, 'costo' => 122],
                ['tipo' => 'Fijador', 'nombre' => 'Líquido fijador radiográfico', 'unidad' => 'bidones', 'stock' => 10, 'minimo' => 3, 'costo' => 115],
            ],
        ];

        $insumos = [];
        foreach ($catalogo as $especialidad => $items) {
            $clinica = Clinica::query()->where('especialidad', $especialidad)->first();
            if (! $clinica) {
                continue;
            }

            foreach ($items as $item) {
                $tipo = InsumoTratamiento::query()
                    ->where('clinica_id', $clinica->id)
                    ->where('nombre', $item['tipo'])
                    ->first();
                if (! $tipo) {
                    continue;
                }

                $insumo = Insumo::query()->updateOrCreate(
                    ['clinica_id' => $clinica->id, 'nombre' => $item['nombre']],
                    [
                        'insumo_tratamiento_id' => $tipo->id,
                        'cantidad' => $item['unidad'],
                        'cantidad_numerica' => $item['stock'],
                        'stock_minimo' => $item['minimo'],
                        'costo_unitario' => $item['costo'],
                    ],
                );
                $insumos[$especialidad][$item['nombre']] = $insumo;
            }
        }

        return $insumos;
    }

    /** @param array<string, array<string, Insumo>> $insumos */
    private function vincularTratamientos(array $insumos): void
    {
        $asignaciones = [
            'Operatoria Dental' => [
                'Resina simple' => ['Resina fotopolimerizable A2' => 0.25, 'Ácido fosfórico al 37%' => 0.10, 'Adhesivo dental universal' => 0.10, 'Microaplicadores dentales' => 2, 'Rollos de algodón' => 4],
                'Resina compuesta' => ['Resina fotopolimerizable A2' => 0.50, 'Ácido fosfórico al 37%' => 0.15, 'Adhesivo dental universal' => 0.15, 'Microaplicadores dentales' => 3, 'Dique de goma' => 1],
                'Sellante de fosas' => ['Ácido fosfórico al 37%' => 0.10, 'Adhesivo dental universal' => 0.10, 'Microaplicadores dentales' => 2, 'Rollos de algodón' => 4],
            ],
            'Endodoncia' => [
                'Endodoncia unirradicular' => ['Limas K primera serie' => 1, 'Hipoclorito de sodio al 2,5%' => 0.20, 'Conos de gutapercha surtidos' => 2, 'Cemento sellador endodóntico' => 0.10],
                'Endodoncia birradicular' => ['Limas K primera serie' => 1, 'Hipoclorito de sodio al 2,5%' => 0.30, 'Conos de gutapercha surtidos' => 4, 'Cemento sellador endodóntico' => 0.15],
                'Endodoncia multirradicular' => ['Limas K primera serie' => 2, 'Hipoclorito de sodio al 2,5%' => 0.40, 'Conos de gutapercha surtidos' => 6, 'Cemento sellador endodóntico' => 0.20],
            ],
            'Cirugía Bucal' => [
                'Exodoncia simple' => ['Lidocaína al 2% con epinefrina' => 2, 'Gasas estériles 10 x 10 cm' => 4],
                'Exodoncia quirúrgica' => ['Lidocaína al 2% con epinefrina' => 3, 'Gasas estériles 10 x 10 cm' => 6, 'Sutura seda 3-0' => 1],
                'Cirugía de tercer molar' => ['Lidocaína al 2% con epinefrina' => 4, 'Gasas estériles 10 x 10 cm' => 8, 'Sutura seda 3-0' => 2],
            ],
            'Periodoncia' => [
                'Detartraje' => ['Cureta Gracey' => 1, 'Clorhexidina al 0,12%' => 0.10],
                'Curetaje' => ['Cureta Gracey' => 2, 'Clorhexidina al 0,12%' => 0.15, 'Mepivacaína al 3%' => 2],
            ],
            'Radiología' => [
                'Radiografía periapical' => ['Película periapical adulto' => 1, 'Líquido revelador radiográfico' => 0.05, 'Líquido fijador radiográfico' => 0.05],
                'Radiografía panorámica' => ['Película periapical adulto' => 1, 'Líquido revelador radiográfico' => 0.10, 'Líquido fijador radiográfico' => 0.10],
            ],
        ];

        foreach ($asignaciones as $especialidad => $tratamientos) {
            foreach ($tratamientos as $nombreTratamiento => $materiales) {
                $tratamiento = Tratamiento::query()
                    ->whereHas('clinica', fn ($query) => $query->where('especialidad', $especialidad))
                    ->where('nombre', $nombreTratamiento)
                    ->first();
                if (! $tratamiento) {
                    continue;
                }

                $vinculos = [];
                foreach ($materiales as $nombreInsumo => $cantidad) {
                    $insumo = $insumos[$especialidad][$nombreInsumo] ?? null;
                    if ($insumo) {
                        $vinculos[$insumo->id] = ['cantidad' => $cantidad];
                    }
                }
                $tratamiento->insumos()->syncWithoutDetaching($vinculos);
            }
        }
    }
}
