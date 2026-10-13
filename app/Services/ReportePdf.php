<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;

class ReportePdf
{
    /** @param array<string, mixed> $datos */
    public function responder(string $vista, array $datos, Request $request, string $nombre): Response
    {
        $request->validate(['formato' => ['nullable', Rule::in(['html', 'pdf'])]]);
        $esPdf = $request->input('formato') === 'pdf';
        $datos['esPdf'] = $esPdf;
        $datos['descargarPdf'] = route($request->route()->getName(), array_merge(
            $request->route()->parameters(), $request->query(), ['formato' => 'pdf']
        ));
        $cabeceras = ['Cache-Control' => 'private, no-store', 'Pragma' => 'no-cache', 'X-Content-Type-Options' => 'nosniff'];
        if (! $esPdf) {
            return response()->view($vista, $datos, 200, $cabeceras);
        }

        $pdf = Pdf::view($vista, $datos)->format('a4')->margins(7, 7, 10, 7)
            ->footerView('reportes.footer', ['codigo' => $datos['codigo']])
            ->dontCache()->waitUntilReady('window.pdfReady === true', 20000)
            ->withBrowsershot(function (Browsershot $browser): void {
                $browser->timeout(60)->blockUrls(['http://', 'https://']);
            });
        if ($datos['horizontal'] ?? false) {
            $pdf->landscape();
        }

        return $pdf->name($nombre)->download()->headers($cabeceras)->toResponse($request);
    }
}
