<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImagenOdontograma
{
    public function ruta(string $denticion, string $pieza, string $vista): string
    {
        abort_unless(in_array($denticion, ['adulto', 'infantil'], true), 404);
        abort_unless(in_array($vista, ['frontal', 'anatomica'], true), 404);
        abort_unless(preg_match($denticion === 'adulto' ? '/^[1-4][1-8]$/' : '/^[5-8][1-5]$/', $pieza) === 1, 404);
        $cuadrante = intdiv((int) $pieza, 10);
        $numero = (int) $pieza % 10;
        $nombres = $denticion === 'adulto'
            ? [1 => 'incisivo_central', 2 => 'incisivo_lateral', 3 => 'canino', 4 => 'primer_premolar', 5 => 'segundo_premolar', 6 => 'primer_molar', 7 => 'segundo_molar', 8 => 'tercer_molar']
            : [1 => 'incisivo_central_temporal', 2 => 'incisivo_lateral_temporal', 3 => 'canino_temporal', 4 => 'primer_molar_temporal', 5 => 'segundo_molar_temporal'];
        $ubicacion = match ($cuadrante) {
            1, 5 => 'superior_derecho', 2, 6 => 'superior_izquierdo',
            3, 7 => 'inferior_izquierdo', 4, 8 => 'inferior_derecho',
        };
        $arcada = in_array($cuadrante, [1, 2, 5, 6], true) ? 'posterior' : 'inferior';
        $directorio = "odontograma/{$denticion}/{$arcada}".($vista === 'frontal' ? '/frontal' : '');
        $sufijo = $vista === 'frontal' ? '_frontal' : '';

        return Storage::disk('public')->path("{$directorio}/{$pieza}_{$nombres[$numero]}_{$ubicacion}{$sufijo}.png");
    }

    /** @return array<string, array<int, array<string, string>>> */
    public function incrustadas(): array
    {
        $imagenes = [];
        foreach (['adulto' => [1, 2, 3, 4], 'infantil' => [5, 6, 7, 8]] as $denticion => $cuadrantes) {
            foreach ($cuadrantes as $cuadrante) {
                foreach (range(1, $denticion === 'adulto' ? 8 : 5) as $numero) {
                    $pieza = (string) ($cuadrante * 10 + $numero);
                    foreach (['frontal', 'anatomica'] as $vista) {
                        $ruta = $this->ruta($denticion, $pieza, $vista);
                        $imagenes[$denticion][$pieza][$vista] = is_file($ruta)
                            ? 'data:image/png;base64,'.base64_encode(file_get_contents($ruta))
                            : '';
                    }
                }
            }
        }

        return $imagenes;
    }
}
