<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

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

        $opciones = new Options;
        $opciones->setChroot([public_path(), storage_path()]);
        $opciones->setDefaultFont('DejaVu Sans');
        $opciones->setIsHtml5ParserEnabled(true);
        $opciones->setIsPhpEnabled(false);
        $opciones->setIsJavascriptEnabled(false);
        $opciones->setIsRemoteEnabled(false);

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml(view($vista, $datos)->render(), 'UTF-8');
        $pdf->setPaper('a4', ($datos['horizontal'] ?? false) ? 'landscape' : 'portrait');
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $fuente = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(
            20,
            $canvas->get_height() - 20,
            'Clínica Odontológica UPEA · '.$datos['codigo'].' · Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente,
            8,
            [0.36, 0.44, 0.42],
        );

        $nombreSeguro = str_replace(["\r", "\n", '"'], '', $nombre);

        return response($pdf->output(), 200, $cabeceras + [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombreSeguro.'"',
        ]);
    }
}
