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

        $pdf = Pdf::view($vista, $datos)
            ->margins(7, 7, 10, 7)
            ->footerView('reportes.footer', ['codigo' => $datos['codigo']])
            ->dontCache()
            ->waitUntilReady('window.pdfReady === true', 20000)
            ->withBrowsershot(function (Browsershot $browser): void {
                $browser->timeout(60)
                    ->setNodeModulePath(base_path('node_modules'))
                    ->blockUrls(['http://', 'https://']);

                if ($chromePath = $this->chromePath()) {
                    $browser->setChromePath($chromePath);
                }
            });

        if (($datos['tamanoPapel'] ?? 'a4') === 'oficio') {
            $pdf->paperSize(216, 330, 'mm');
        } else {
            $pdf->format('a4');
        }

        if ($datos['horizontal'] ?? false) {
            $pdf->landscape();
        }

        return $pdf->name($nombre)->download()->headers($cabeceras)->toResponse($request);
    }

    private function chromePath(): ?string
    {
        $configuredPath = config('laravel-pdf.browsershot.chrome_path');
        if (is_string($configuredPath) && is_file($configuredPath)) {
            return $configuredPath;
        }

        foreach ([
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
