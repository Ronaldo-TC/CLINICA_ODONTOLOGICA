<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $titulo }} · {{ $codigo }} · Clínica Odontológica UPEA</title>
    <style>
        :root { --ink:#183b38; --muted:#5c706c; --teal:#087f79; --line:#d7e3df; --soft:#f0f6f3; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e9efec; color:var(--ink); font:11.5px/1.35 Arial, sans-serif; }
        .report-actions { max-width:{{ ($horizontal ?? false) ? '1120' : '880' }}px; margin:20px auto; display:flex; gap:10px; align-items:center; padding:0 12px; flex-wrap:wrap; }
        .report-actions a, .report-actions button { border:1px solid var(--line); border-radius:6px; background:white; color:var(--ink); padding:10px 16px; text-decoration:none; font:600 13px Arial,sans-serif; cursor:pointer; }
        .report-actions button { background:var(--teal); color:white; border-color:var(--teal); }
        .report-actions span { color:var(--muted); }
        .report-paper { max-width:{{ ($horizontal ?? false) ? '1120' : '880' }}px; margin:0 auto 24px; padding:24px; background:white; box-shadow:0 8px 32px #183b3810; }
        .report-header { display:flex; justify-content:space-between; gap:14px; padding-bottom:10px; border-bottom:2px solid var(--teal); }
        .brand { display:flex; gap:9px; align-items:center; }
        .brand-mark { background:var(--teal); color:white; padding:9px; font-weight:700; border-radius:4px; }
        .brand p, .report-header p { margin:2px 0; }
        .eyebrow { text-transform:uppercase; font-size:10px; letter-spacing:1.2px; color:var(--muted); }
        .report-reference { text-align:right; font-size:11px; color:var(--muted); }
        h1 { font-size:19px; margin:14px 0 5px; line-height:1.2; }
        h2 { font-size:13px; margin:14px 0 8px; padding-bottom:4px; border-bottom:1px solid var(--line); }
        h3 { font-size:11.5px; margin:10px 0 6px; }
        p { margin:4px 0; }
        .muted { color:var(--muted); }
        .report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px 12px; margin:8px 0; }
        .report-field { min-width:0; }
        .report-field dt { font-size:10px; text-transform:uppercase; color:var(--muted); margin-bottom:4px; }
        .report-field dd { margin:0; overflow-wrap:anywhere; white-space:pre-wrap; }
        .report-metrics { display:flex; flex-wrap:wrap; gap:7px; margin:12px 0; }
        .report-metric { background:var(--soft); padding:7px 10px; border:1px solid var(--line); border-radius:4px; flex:1; min-width:120px; }
        .report-metric span { display:block; color:var(--muted); font-size:11px; }
        .report-metric strong { font-size:16px; }
        table { border-collapse:collapse; width:100%; margin:8px 0; font-size:9.5px; }
        th { background:var(--soft); text-align:left; color:var(--ink); font-size:10px; }
        th, td { border:1px solid var(--line); padding:5px 6px; vertical-align:top; overflow-wrap:anywhere; }
        td { white-space:pre-wrap; }
        tr:nth-child(even) td { background:#f9fbfa; }
        .report-table { overflow-x:auto; }
        .report-note { background:var(--soft); border-left:3px solid var(--teal); padding:7px 10px; margin:9px 0; }
        .report-warning { background:#fff8e5; border-left:3px solid #ca8a04; padding:10px 14px; }
        .report-footer { margin-top:18px; padding-top:8px; border-top:1px solid var(--line); display:flex; justify-content:space-between; gap:12px; font-size:9px; color:var(--muted); }
        .report-admission + .report-admission { border-top:2px solid var(--line); margin-top:18px; padding-top:8px; }
        .report-admission p { white-space:pre-wrap; overflow-wrap:anywhere; }
        .report-signatures { display:flex; gap:24px; margin-top:16px; font-size:10px; }
        .report-signatures div { flex:1; border-top:1px solid var(--line); padding-top:8px; }
        .odontograma { padding:12px 0; }
        .odontograma-arcada { display:grid; }
        .odontograma-cuadrante { padding:8px; border:1px solid var(--line); border-radius:6px; }
        .odontograma-titulo { display:block; font-size:10px; margin-bottom:8px; color:var(--muted); }
        .odontograma-piezas { display:flex; }
        .diente { display:flex; flex-direction:column; align-items:center; position:relative; }
        .diente svg { background:#f8faf9; }
        .diente-borde { fill:none; stroke:#64748b; stroke-width:1; }
        .diente-ausente { display:none; }
        .diente.es-ausente .diente-ausente { display:block; }
        .odontograma-hint { display:none; }
        @media(max-width:720px) { .report-paper { padding:20px; } .report-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .report-header { flex-wrap:wrap; } .report-reference { text-align:left; } }
        @page { size:A4 {{ ($horizontal ?? false) ? 'landscape' : 'portrait' }}; margin:9mm 9mm 14mm; }
        @media print {
            body { background:white; font-size:10px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .report-actions, .odontograma-controles { display:none !important; }
            .report-paper { margin:0; padding:0; max-width:none; box-shadow:none; }
            .report-table, .odontograma-desplazable { overflow:visible !important; }
            thead { display:table-header-group; }
            tr, .report-metric, .report-field, .report-signatures, .odontograma-cuadrante { break-inside:avoid; }
            h2, h3 { break-after:avoid; }
            .report-admission + .report-admission { break-before:auto; }
            .report-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .report-header { flex-wrap:nowrap; }
            .report-reference { text-align:right; }
            .report-footer { break-inside:avoid; }
            .report-paper .odontograma .odontograma-piezas { gap:2px; }
            .report-paper .odontograma .diente { width:34px; flex-basis:34px; }
            .report-paper .odontograma .diente-imagen { width:34px; height:62px; }
            .report-paper .odontograma .diente-ilustracion { height:62px; }
            .report-paper .odontograma .diente svg { width:34px; }
            .report-paper .odontograma .diente svg.diente-overlay { width:34px; height:62px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @unless($esPdf)
    <nav class="report-actions" aria-label="Acciones del reporte">
        <a href="{{ $volver }}">Volver al módulo</a>
        <a href="{{ $descargarPdf }}">Descargar PDF</a>
        <button type="button" id="imprimir-reporte">Imprimir / Guardar PDF</button>
    </nav>
    @endunless
    <main class="report-paper">
        <header class="report-header">
            <div class="brand">
                <div class="brand-mark">UPEA</div>
                <div><p class="eyebrow">Universidad Pública de El Alto</p><strong>Clínica Odontológica</strong><p class="muted">Atención y seguimiento clínico</p></div>
            </div>
            <div class="report-reference"><strong>{{ $codigo }}</strong><p>Emitido: {{ $emitidoEn->format('d/m/Y H:i') }}</p><p>Por: {{ $emitidoPor }}</p></div>
        </header>
        <h1>{{ $titulo }}</h1>
        @yield('reporte')
        @unless($esPdf)<footer class="report-footer"><span>Clínica Odontológica UPEA · {{ $codigo }}</span><span>Documento de uso interno · Información confidencial</span></footer>@endunless
    </main>
    @stack('scripts')
    <script>
        document.getElementById('imprimir-reporte')?.addEventListener('click', () => window.print());
        const prepararPdf = async () => {
            await document.fonts.ready;
            await Promise.all(Array.from(document.images, imagen => imagen.complete ? Promise.resolve() : new Promise(resolve => {
                imagen.addEventListener('load', resolve, {once:true});
                imagen.addEventListener('error', resolve, {once:true});
            })));
            window.pdfReady = true;
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', prepararPdf);
        else prepararPdf();
    </script>
</body>
</html>
