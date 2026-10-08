<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class OdontogramaImagenesTest extends TestCase
{
    #[TestWith(['adulto', '11', 'frontal', 'posterior/frontal/11_incisivo_central_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '12', 'frontal', 'posterior/frontal/12_incisivo_lateral_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '13', 'frontal', 'posterior/frontal/13_canino_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '14', 'frontal', 'posterior/frontal/14_primer_premolar_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '15', 'frontal', 'posterior/frontal/15_segundo_premolar_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '16', 'frontal', 'posterior/frontal/16_primer_molar_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '17', 'frontal', 'posterior/frontal/17_segundo_molar_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '18', 'frontal', 'posterior/frontal/18_tercer_molar_superior_derecho_frontal.png'])]
    #[TestWith(['adulto', '22', 'frontal', 'posterior/frontal/22_incisivo_lateral_superior_izquierdo_frontal.png'])]
    #[TestWith(['adulto', '31', 'anatomica', 'inferior/31_incisivo_central_inferior_izquierdo.png'])]
    #[TestWith(['adulto', '41', 'anatomica', 'inferior/41_incisivo_central_inferior_derecho.png'])]
    #[TestWith(['infantil', '51', 'frontal', 'posterior/frontal/51_incisivo_central_temporal_superior_derecho_frontal.png'])]
    #[TestWith(['infantil', '52', 'frontal', 'posterior/frontal/52_incisivo_lateral_temporal_superior_derecho_frontal.png'])]
    #[TestWith(['infantil', '53', 'frontal', 'posterior/frontal/53_canino_temporal_superior_derecho_frontal.png'])]
    #[TestWith(['infantil', '54', 'frontal', 'posterior/frontal/54_primer_molar_temporal_superior_derecho_frontal.png'])]
    #[TestWith(['infantil', '55', 'frontal', 'posterior/frontal/55_segundo_molar_temporal_superior_derecho_frontal.png'])]
    #[TestWith(['infantil', '61', 'frontal', 'posterior/frontal/61_incisivo_central_temporal_superior_izquierdo_frontal.png'])]
    #[TestWith(['infantil', '71', 'anatomica', 'inferior/71_incisivo_central_temporal_inferior_izquierdo.png'])]
    #[TestWith(['infantil', '81', 'anatomica', 'inferior/81_incisivo_central_temporal_inferior_derecho.png'])]
    public function test_sirve_la_ilustracion_exacta_segun_la_pieza_y_la_vista(string $denticion, string $pieza, string $vista, string $archivo): void
    {
        Storage::fake('public');
        $ruta = "odontograma/{$denticion}/{$archivo}";
        Storage::disk('public')->put($ruta, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII='));
        if ($pieza === '22') {
            Storage::disk('public')->put(str_replace('_frontal.png', '_frontal_variante_2.png', $ruta), 'variante sin FDI confirmado');
        }

        $response = $this->get(route('odontograma.imagen', compact('denticion', 'pieza', 'vista')));

        $response->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(Storage::disk('public')->path($ruta), $response->baseResponse->getFile()->getPathname());
    }

    #[TestWith(['adulto', '51', 'frontal'])]
    #[TestWith(['infantil', '11', 'frontal'])]
    #[TestWith(['infantil', '58', 'frontal'])]
    #[TestWith(['adulto', '99', 'frontal'])]
    #[TestWith(['otro', '11', 'frontal'])]
    #[TestWith(['adulto', '11', 'archivo'])]
    #[TestWith(['adulto', '..', 'frontal'])]
    public function test_rechaza_identificadores_y_vistas_fuera_del_catalogo(string $denticion, string $pieza, string $vista): void
    {
        $this->get(route('odontograma.imagen', compact('denticion', 'pieza', 'vista')))->assertNotFound();
    }

    public function test_una_ilustracion_faltante_devuelve_404(): void
    {
        Storage::fake('public');

        $this->get(route('odontograma.imagen', ['denticion' => 'adulto', 'pieza' => '11', 'vista' => 'frontal']))->assertNotFound();
    }

    public function test_el_componente_infantil_expone_sus_imagenes_y_denticion_sin_mezclar_el_json(): void
    {
        $html = view('components.odontograma', [
            'id' => 'odonto-infantil',
            'denticion' => 'infantil',
            'valor' => ['51' => ['o' => 'caries']],
        ])->render();

        $this->assertStringContainsString('data-denticion="infantil"', $html);
        $this->assertStringContainsString('name="odontograma_denticion" value="infantil"', $html);
        $this->assertStringContainsString('data-odontograma-vista', $html);
        $this->assertStringContainsString(route('odontograma.imagen', ['denticion' => 'infantil', 'pieza' => '51', 'vista' => 'frontal']), $html);
        $this->assertStringNotContainsString('variante_2', $html);
        $this->assertStringContainsString('&quot;51&quot;:{&quot;o&quot;:&quot;caries&quot;}', $html);
    }

    public function test_las_caras_en_lectura_no_participan_en_la_navegacion_de_edicion(): void
    {
        $html = view('components.odontograma', ['lectura' => true])->render();

        $this->assertSame(260, substr_count($html, 'tabindex="-1"'));
        $this->assertSame(2, substr_count($html, ' disabled'));
    }
}
