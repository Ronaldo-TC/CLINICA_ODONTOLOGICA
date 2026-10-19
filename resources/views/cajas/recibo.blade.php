<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Recibo #{{ $caja->recibo }} · Clínica Odontológica UPEA</title>
    <style>
        :root { color-scheme:light; --ink:#173d39; --muted:#61736f; --line:#d7e4e0; --soft:#f2f7f5; --brand:#087f79; --danger:#b42318; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e8efec; color:var(--ink); font:14px/1.45 Arial, sans-serif; }
        .receipt-actions { width:min(100% - 24px, 148mm); margin:20px auto 12px; display:flex; justify-content:space-between; gap:10px; }
        .receipt-actions button { border:1px solid var(--line); border-radius:8px; padding:10px 16px; background:#fff; color:var(--ink); font:700 14px Arial, sans-serif; cursor:pointer; }
        .receipt-actions .primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .receipt { position:relative; width:min(100% - 24px, 148mm); min-height:190mm; margin:0 auto 24px; padding:14mm 12mm 11mm; overflow:hidden; background:#fff; box-shadow:0 12px 36px rgb(22 59 55 / 12%); }
        .receipt-header { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; padding-bottom:14px; border-bottom:2px solid var(--brand); }
        .brand { display:flex; align-items:center; gap:10px; }
        .brand-mark { display:grid; width:46px; height:46px; place-items:center; border-radius:10px; background:var(--brand); color:#fff; font-weight:800; letter-spacing:.5px; }
        .brand p, .reference p { margin:0; }
        .brand-name { font-size:16px; font-weight:800; }
        .muted { color:var(--muted); }
        .reference { text-align:right; }
        .reference strong { display:block; font-size:20px; color:var(--brand); }
        .status { display:inline-block; margin-top:5px; padding:3px 8px; border-radius:999px; background:#e6f5f2; color:#08746f; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; }
        .status.void { background:#feeceb; color:var(--danger); }
        h1 { margin:22px 0 4px; font-size:22px; text-align:center; text-transform:uppercase; letter-spacing:1.2px; }
        .subtitle { margin:0 0 20px; text-align:center; color:var(--muted); }
        .section { margin-top:18px; }
        .section-title { margin:0 0 8px; padding-bottom:5px; border-bottom:1px solid var(--line); color:var(--muted); font-size:11px; font-weight:800; letter-spacing:1px; text-transform:uppercase; }
        .grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px 18px; }
        .field { min-width:0; }
        .field span { display:block; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.45px; }
        .field strong { display:block; margin-top:2px; overflow-wrap:anywhere; font-size:14px; }
        .concept { margin-top:8px; padding:12px; border:1px solid var(--line); border-radius:8px; background:var(--soft); }
        .concept span { display:block; margin-bottom:3px; color:var(--muted); font-size:11px; text-transform:uppercase; }
        .concept strong { overflow-wrap:anywhere; }
        .amount { margin:20px 0; padding:15px; border:2px solid var(--brand); border-radius:10px; text-align:center; }
        .amount span { display:block; color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; }
        .amount strong { display:block; margin-top:2px; color:var(--brand); font-size:30px; }
        .totals { width:100%; border-collapse:collapse; }
        .totals th, .totals td { padding:8px 10px; border-bottom:1px solid var(--line); }
        .totals th { text-align:left; color:var(--muted); font-weight:600; }
        .totals td { text-align:right; font-weight:800; font-variant-numeric:tabular-nums; }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:28px; margin-top:42px; text-align:center; font-size:12px; }
        .signature { padding-top:7px; border-top:1px solid var(--ink); }
        .footer { margin-top:24px; padding-top:10px; border-top:1px dashed var(--line); text-align:center; color:var(--muted); font-size:10px; }
        .void-watermark { position:absolute; inset:42% auto auto 50%; z-index:2; transform:translate(-50%, -50%) rotate(-18deg); border:5px solid rgb(180 35 24 / 24%); padding:8px 20px; color:rgb(180 35 24 / 24%); font-size:48px; font-weight:900; letter-spacing:5px; pointer-events:none; }
        @media (max-width:560px) { .receipt { min-height:0; padding:24px 20px; } .receipt-header { flex-direction:column; } .reference { text-align:left; } .grid { grid-template-columns:1fr; } }
        @page { size:A5 portrait; margin:8mm; }
        @media print {
            body { background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .receipt-actions { display:none; }
            .receipt { width:100%; min-height:0; margin:0; padding:0; box-shadow:none; }
        }
    </style>
</head>
<body>
    <nav class="receipt-actions" aria-label="Acciones del recibo">
        <button type="button" onclick="history.back()">Volver</button>
        <button type="button" class="primary" onclick="window.print()">Imprimir recibo</button>
    </nav>

    <main class="receipt">
        @unless($caja->habilitada)
            <div class="void-watermark" aria-hidden="true">ANULADO</div>
        @endunless

        <header class="receipt-header">
            <div class="brand">
                <div class="brand-mark">UPEA</div>
                <div>
                    <p class="muted">Universidad Pública de El Alto</p>
                    <p class="brand-name">Clínica Odontológica</p>
                    <p class="muted">Comprobante de caja</p>
                </div>
            </div>
            <div class="reference">
                <p class="muted">N.º de recibo</p>
                <strong>#{{ $caja->recibo }}</strong>
                <span class="status {{ $caja->habilitada ? '' : 'void' }}">{{ $caja->habilitada ? 'Vigente' : 'Anulado' }}</span>
            </div>
        </header>

        <h1>Recibo de pago</h1>
        <p class="subtitle">Emitido el {{ $caja->created_at?->format('d/m/Y') ?? '—' }} a las {{ $caja->created_at?->format('H:i') ?? '—' }}</p>

        <section class="section" aria-labelledby="datos-paciente">
            <h2 class="section-title" id="datos-paciente">Datos del paciente</h2>
            <div class="grid">
                <div class="field"><span>Paciente</span><strong>{{ $paciente?->nombre_completo ?: ($caja->paciente_nombre_completo ?: '—') }}</strong></div>
                <div class="field"><span>CI</span><strong>{{ $paciente?->ci ?: ($caja->ci ?: '—') }}</strong></div>
                <div class="field"><span>Ficha de admisión</span><strong>{{ $ficha ? '#'.$ficha : '—' }}</strong></div>
                <div class="field"><span>Estudiante responsable</span><strong>{{ $estudiante?->nombre_completo ?: '—' }}</strong></div>
            </div>
        </section>

        <section class="section" aria-labelledby="datos-servicio">
            <h2 class="section-title" id="datos-servicio">Detalle del servicio</h2>
            <div class="grid">
                <div class="field"><span>Clínica / especialidad</span><strong>{{ $caja->clinica?->especialidad ?: '—' }}</strong></div>
                <div class="field"><span>Forma de pago</span><strong>{{ $caja->tipo_pago ?: 'No especificada' }}</strong></div>
                <div class="field"><span>Tipo</span><strong>{{ $caja->tipo === 'radiologia' ? 'Estudio radiológico' : 'Tratamiento clínico' }}</strong></div>
                <div class="field"><span>Responsable de caja</span><strong>{{ $caja->usuario?->nombre_completo ?: '—' }}</strong></div>
            </div>
            <div class="concept"><span>Concepto</span><strong>{{ $concepto }}</strong></div>
        </section>

        <div class="amount"><span>Importe recibido</span><strong>Bs {{ number_format((float) $caja->cancelado, 2) }}</strong></div>

        <table class="totals" aria-label="Resumen del pago">
            <tbody>
                <tr><th>Costo autorizado</th><td>Bs {{ number_format((float) $caja->costo_total, 2) }}</td></tr>
                <tr><th>Total abonado vigente</th><td>Bs {{ number_format($totalPagado, 2) }}</td></tr>
                <tr><th>Saldo vigente</th><td>Bs {{ number_format($saldo, 2) }}</td></tr>
            </tbody>
        </table>

        @if($caja->descripcion)
            <section class="section"><h2 class="section-title">Observación</h2><p>{{ $caja->descripcion }}</p></section>
        @endif

        <div class="signatures">
            <div class="signature">Responsable de caja</div>
            <div class="signature">Paciente / responsable</div>
        </div>

        <footer class="footer">Recibo generado por el Sistema de la Clínica Odontológica UPEA · Conserve este comprobante.</footer>
    </main>
</body>
</html>
