<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use DOMDocument;
use DOMXPath;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class PaginationDesignTest extends TestCase
{
    use UsesAuthDatabase;

    /** @return array<string, array{int, int, int, string, int, int}> */
    public static function pageBoundaries(): array
    {
        return [
            'first page' => [63, 1, 15, '1–15', 0, 1],
            'middle page' => [63, 2, 15, '16–30', 1, 1],
            'last page' => [63, 5, 3, '61–63', 1, 0],
            'single page' => [3, 1, 3, '1–3', 0, 0],
        ];
    }

    #[DataProvider('pageBoundaries')]
    public function test_pagination_shows_record_counts_current_page_and_only_available_directions(int $total, int $page, int $count, string $range, int $previousLinks, int $nextLinks): void
    {
        $paginator = new LengthAwarePaginator(array_fill(0, $count, 'Registro'), $total, 15, $page, ['path' => '/listado']);
        $html = $paginator->links()->toHtml();
        $xpath = $this->paginationDocument($html);

        $this->assertStringContainsString('<strong>'.$range.'</strong>', $html);
        $this->assertStringContainsString('de <strong>'.$total.'</strong> registros', $html);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Paginación de la tabla"]')->length);
        $this->assertSame((string) $page, $xpath->query('//*[@aria-current="page"]')->item(0)->textContent);
        $this->assertSame($previousLinks, $xpath->query('//a[@rel="prev"]')->length);
        $this->assertSame($nextLinks, $xpath->query('//a[@rel="next"]')->length);
        $this->assertSame(2 - $previousLinks - $nextLinks, $xpath->query('//*[@aria-disabled="true"]')->length);
        $this->assertSame(0, $xpath->query('//a[@href="#"]')->length);
    }

    public function test_an_empty_table_has_no_pagination_controls(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->assertSame('', trim($paginator->links()->toHtml()));
    }

    public function test_page_numbers_are_limited_for_large_lists_and_keep_first_last_and_current_pages(): void
    {
        $paginator = new LengthAwarePaginator(array_fill(0, 15, 'Registro'), 1500, 15, 50, ['path' => '/listado']);
        $xpath = $this->paginationDocument($paginator->links()->toHtml());

        $this->assertSame('50', $xpath->query('//*[@aria-current="page"]')->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//a[@aria-label="Ir a la página 1"]')->length);
        $this->assertSame(1, $xpath->query('//a[@aria-label="Ir a la página 100"]')->length);
        $this->assertSame(2, $xpath->query('//span[@class="table-pagination__ellipsis"]')->length);
        $this->assertSame(3, $xpath->query('//ul/li[contains(@class, "is-nearby")]')->length);
        $this->assertLessThan(20, $xpath->query('//ul/li')->length);
    }

    public function test_page_links_preserve_filters_and_escape_query_strings(): void
    {
        $paginator = new LengthAwarePaginator(array_fill(0, 15, 'Registro'), 63, 15, 2, ['path' => '/listado']);
        $paginator->appends(['buscar' => 'Ana & María', 'estado' => '1']);
        $html = $paginator->links()->toHtml();
        $xpath = $this->paginationDocument($html);

        foreach ($xpath->query('//a') as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertSame('Ana & María', $query['buscar']);
            $this->assertSame('1', $query['estado']);
            $this->assertArrayHasKey('page', $query);
        }
        $this->assertStringContainsString('&amp;estado=1&amp;page=', $html);
    }

    public function test_ajax_table_uses_shared_pagination_and_preserves_filters_on_the_last_page(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::create(['name' => 'bitacoras.ver', 'guard_name' => 'web']));
        foreach (range(1, 26) as $number) {
            Bitacora::create([
                'usuario_id' => $user->id, 'ip' => '127.0.0.1', 'fecha' => '2026-10-10 10:00:00',
                'tipo' => 'editar', 'estado' => 'exito', 'datos' => 'Paginación '.$number,
            ]);
        }
        Bitacora::create([
            'usuario_id' => $user->id, 'ip' => '127.0.0.1', 'fecha' => '2026-10-10 10:00:00',
            'tipo' => 'editar', 'estado' => 'exito', 'datos' => 'Evento fuera del filtro',
        ]);

        $response = $this->actingAs($user)->getJson(route('bitacoras.tabla', ['buscar' => 'Paginación', 'page' => 2]))
            ->assertOk()->assertJsonPath('total', 26);
        $html = $response->json('paginacion');
        $xpath = $this->paginationDocument($html);

        $this->assertStringContainsString('<strong>26–26</strong>', $html);
        $this->assertStringContainsString('Página 2 de 2', $html);
        $this->assertSame(0, $xpath->query('//a[@rel="next"]')->length);
        parse_str(parse_url($xpath->query('//a[@rel="prev"]')->item(0)->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame(['buscar' => 'Paginación', 'page' => '1'], $query);
    }

    public function test_initial_table_page_loads_shared_pagination_styles_and_controls(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::create(['name' => 'bitacoras.ver', 'guard_name' => 'web']));
        Bitacora::create([
            'usuario_id' => $user->id, 'ip' => '127.0.0.1', 'fecha' => '2026-10-10 10:00:00',
            'tipo' => 'editar', 'estado' => 'exito', 'datos' => 'Evento visible',
        ]);

        $this->actingAs($user)->get(route('bitacoras.index'))->assertOk()
            ->assertSee('class="table-pagination"', false)
            ->assertSee('.table-pagination__controls', false)
            ->assertSee('Página 1 de 1')->assertSee('Anterior')->assertSee('Siguiente');
    }

    private function paginationDocument(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
