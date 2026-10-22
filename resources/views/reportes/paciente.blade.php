@extends('layouts.reporte')

@push('styles')
<style>
    .patient-report { --patient-navy:#173f4f; --patient-teal:#087f79; --patient-aqua:#eaf6f3; --patient-line:#c8ddd8; color:#183b38; }
    .patient-lead { margin:0 0 8px; color:#5c706c; font-size:10.5px; }
    .patient-summary { margin:8px 0 10px; padding:11px 13px; border:1px solid var(--patient-line); border-left:5px solid var(--patient-teal); border-radius:7px; background:linear-gradient(135deg,#f8fcfb 0%,#eaf6f3 100%); }
    .patient-summary-main { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .patient-summary-name { margin:2px 0 0; color:var(--patient-navy); font-size:16px; line-height:1.15; }
    .patient-summary-code { padding:5px 8px; border:1px solid var(--patient-line); border-radius:4px; background:#fff; color:var(--patient-teal); font-size:10px; font-weight:700; white-space:nowrap; }
    .patient-kicker { color:var(--patient-teal); font-size:8.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; }
    .patient-facts { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px 10px; margin:9px 0 0; }
    .patient-fact span { display:block; color:#5c706c; font-size:8px; font-weight:700; letter-spacing:.35px; text-transform:uppercase; }
    .patient-fact strong { display:block; margin-top:1px; color:#173f4f; font-size:10px; font-weight:600; overflow-wrap:anywhere; }
    .patient-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:6px; margin:8px 0 10px; }
    .patient-metric { padding:6px 8px; border:1px solid var(--patient-line); border-radius:5px; background:#fff; }
    .patient-metric span { display:block; color:#5c706c; font-size:8px; text-transform:uppercase; }
    .patient-metric strong { color:var(--patient-navy); font-size:13px; }
    .patient-section { margin-top:10px; }
    .patient-section-title { margin:10px 0 5px; padding:4px 7px; border:0; border-left:4px solid var(--patient-teal); background:var(--patient-aqua); color:var(--patient-navy); font-size:11px; }
    .patient-empty { margin:4px 0 7px; padding:5px 7px; border:1px dashed var(--patient-line); color:#5c706c; font-size:9px; }
    .patient-history { margin:6px 0 8px; padding:7px 8px; border:1px solid var(--patient-line); border-radius:5px; }
    .patient-history-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .patient-history-heading h3 { margin:0; color:var(--patient-navy); }
    .patient-history-heading p { margin:0; color:#5c706c; font-size:8.5px; text-align:right; }
    .patient-history-meta { margin:3px 0 5px; color:#5c706c; font-size:8.5px; }
    .patient-report table { margin:4px 0 6px; }
    .patient-report .report-note { margin:4px 0; padding:4px 6px; }
    .patient-payment-summary { margin:4px 0 6px; }
    .patient-report .report-admission { margin-top:8px; padding-top:0; border:1px solid var(--patient-line); border-radius:6px; overflow:hidden; }
    .patient-report .report-admission + .report-admission { margin-top:10px; padding-top:0; border-top:1px solid var(--patient-line); }
    .admission-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:6px 9px; background:var(--patient-navy); color:#fff; }
    .admission-heading h2 { margin:0; padding:0; border:0; color:#fff; font-size:11px; }
    .admission-heading p { margin:0; color:#d9eeea; font-size:8.5px; text-align:right; }
    .admission-body { padding:6px 8px 7px; }
    .admission-block { margin:0 0 5px; }
    .admission-block-title { margin:0 0 3px; padding:0 0 2px; border-bottom:1px solid var(--patient-line); color:var(--patient-teal); font-size:9px; text-transform:uppercase; letter-spacing:.35px; }
    .clinical-columns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:5px; margin:4px 0 5px; }
    .clinical-card { padding:5px 6px; border:1px solid var(--patient-line); border-radius:4px; background:#fbfdfc; }
    .clinical-card h3 { margin:0 0 3px; color:var(--patient-navy); font-size:9px; }
    .clinical-card p { margin:2px 0; }
    .clinical-label { color:#5c706c; font-size:8px; font-weight:700; text-transform:uppercase; }
    .compact-facts { margin:3px 0; }
    .odontogram-panel { margin:4px 0; padding:4px 5px; border:1px solid var(--patient-line); border-radius:4px; }
    .odontogram-panel .admission-block-title { margin-bottom:2px; }
    .consent-line { margin:2px 0; color:#4e625f; font-size:8.5px; }

    @media(max-width:720px) {
        .patient-facts { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .patient-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .patient-history-heading { display:block; }
        .patient-history-heading p { margin-top:2px; text-align:left; }
    }

    @media print {
        .patient-lead { margin-bottom:4px; font-size:8.5px; }
        .patient-summary { margin:4px 0 5px; padding:6px 8px; border-radius:3px; }
        .patient-summary-name { font-size:13px; }
        .patient-facts { grid-template-columns:repeat(5,minmax(0,1fr)); gap:3px 7px; margin-top:5px; }
        .patient-fact span { font-size:7px; }
        .patient-fact strong { font-size:8px; }
        .patient-metrics { grid-template-columns:repeat(4,minmax(0,1fr)); gap:4px; margin:4px 0 5px; }
        .patient-metric { padding:3px 5px; }
        .patient-metric span { font-size:7px; }
        .patient-metric strong { font-size:10px; }
        .patient-section { margin-top:5px; }
        .patient-section-title { margin:5px 0 3px; padding:2px 5px; font-size:9px; }
        .patient-empty { margin:2px 0 4px; padding:3px 5px; font-size:8px; }
        .patient-history { margin:3px 0 4px; padding:4px 5px; }
        .clinical-columns { grid-template-columns:repeat(2,minmax(0,1fr)); gap:3px; }
    }

    @if($esPdf)
    .patient-lead { margin-bottom:3px; font-size:8px; }
    .patient-summary { height:72px; margin:3px 0 5px; padding:5px 7px; overflow:visible; border-radius:2px; background:#eef8f5; }
    .patient-summary-main { display:table; width:100%; }
    .patient-summary-main > div { display:table-cell; vertical-align:middle; }
    .patient-summary-main > div:last-child { width:25%; text-align:right; }
    .patient-summary-name { font-size:12px; }
    .patient-kicker { font-size:7px; }
    .patient-summary-code { display:inline-block; padding:3px 5px; font-size:7.5px; }
    .patient-facts { display:table; width:100%; margin-top:4px; table-layout:fixed; }
    .patient-fact { display:table-cell; width:20%; padding-right:4px; vertical-align:top; }
    .patient-fact span { font-size:7px; }
    .patient-fact strong { font-size:8.4px; }
    .patient-metrics { display:table; width:100%; margin:4px 0 5px; table-layout:fixed; }
    .patient-metric { display:table-cell; padding:3px 5px; border-radius:2px; vertical-align:top; }
    .patient-metric + .patient-metric { border-left-width:0; }
    .patient-metric span { font-size:7px; }
    .patient-metric strong { font-size:10px; }
    .patient-section { margin-top:4px; }
    .patient-section-title { margin:4px 0 2px; padding:2px 5px; font-size:8.5px; }
    .patient-empty { margin:2px 0 3px; padding:2px 4px; font-size:7.5px; }
    .patient-history { margin:2px 0 3px; padding:3px 4px; border-radius:2px; page-break-inside:auto; }
    .patient-history-heading { display:table; width:100%; }
    .patient-history-heading > * { display:table-cell; vertical-align:top; }
    .patient-history-heading p { width:45%; font-size:7.5px; text-align:right; }
    .patient-history-meta { margin:2px 0 3px; font-size:7.5px; }
    .patient-report table { margin:2px 0 3px; font-size:8px; line-height:1.1; }
    .patient-report th, .patient-report td { padding:2px 3px; }
    .patient-report .report-note { margin:2px 0; padding:2px 4px; font-size:8px; }
    .patient-report .report-admission { margin-top:4px; overflow:visible; border-radius:2px; }
    .patient-report .report-admission + .report-admission { margin-top:5px; }
    .admission-heading { display:table; width:100%; padding:3px 5px; }
    .admission-heading > * { display:table-cell; vertical-align:middle; }
    .admission-heading h2 { font-size:9.2px; }
    .admission-heading p { width:65%; font-size:7.2px; text-align:right; }
    .admission-body { padding:3px 4px 4px; }
    .admission-block { margin-bottom:2px; }
    .admission-block-title { margin-bottom:1px; padding-bottom:1px; font-size:7.6px; }
    .clinical-columns { display:table; width:100%; margin:2px 0; table-layout:fixed; }
    .clinical-card { display:table-cell; width:50%; padding:2px 3px; border-radius:2px; font-size:8px; vertical-align:top; }
    .clinical-card + .clinical-card { border-left-width:0; }
    .clinical-card h3 { margin-bottom:1px; font-size:8px; }
    .clinical-card p { margin:1px 0; }
    .clinical-label { font-size:6.8px; }
    .compact-facts { margin:1px 0; }
    .odontogram-panel { margin:2px 0; padding:2px 3px; border-radius:2px; }
    .odontogram-panel .odontograma { padding:0; }
    .odontogram-panel .odontograma-denticion[hidden] { display:none !important; }
    .odontogram-panel .odontograma-denticion-titulo { margin:0 0 1px; font-size:7px; }
    .odontogram-panel .odontograma .odontograma-cuadrante { width:48%; margin:0 .5% 2px 0; padding:1px 2px; border-radius:1px; }
    .odontogram-panel .odontograma .odontograma-titulo { margin-bottom:1px; font-size:6.5px; }
    .odontogram-panel .odontograma .diente-ilustracion { position:relative; display:block !important; height:50px; }
    .odontogram-panel .odontograma .diente-imagen { display:block; width:28px; height:50px; margin:0 auto; object-fit:contain; }
    .odontogram-panel .odontograma .diente svg { width:13px; height:13px; }
    .odontogram-panel .odontograma .diente svg.diente-overlay { position:absolute; inset:0; width:28px; height:50px; background:transparent; }
    .odontogram-panel .odontograma .diente-numero { font-size:6.5px; }
    .odontogram-panel .odontograma .diente-marcas { display:none; }
    .odontogram-panel table { margin-top:2px; }
    .consent-line { margin:1px 0; font-size:7.3px; }
    .patient-report .report-signatures { display:table; width:100%; margin-top:3px; table-layout:fixed; }
    .patient-report .report-signatures > div { display:table-cell; width:50%; margin:0; padding-top:2px; font-size:7.5px; }
    .patient-report .report-signatures img { height:34px !important; }
    .patient-report .report-signatures { page-break-inside:auto; }
    .patient-report .report-metric,
    .patient-report .report-field,
    .patient-report .odontograma-cuadrante { page-break-inside:auto; }
    .patient-report h2,
    .patient-report h3 { page-break-after:auto; }
    .patient-report--historial .patient-lead { display:none; }
    .patient-report--historial .patient-summary { height:60px; margin:2px 0 3px; padding:3px 6px; }
    .patient-report--historial .patient-summary-name { font-size:10.5px; }
    .patient-report--historial .patient-kicker,
    .patient-report--historial .patient-fact span { font-size:6.3px; }
    .patient-report--historial .patient-fact strong { font-size:7.5px; }
    .patient-report--historial .patient-facts { margin-top:2px; }
    .patient-report--historial .patient-metrics { margin:2px 0 3px; }
    .patient-report--historial .patient-metric { padding:2px 4px; }
    .patient-report--historial .patient-metric span { font-size:6.3px; }
    .patient-report--historial .patient-metric strong { font-size:8.5px; }
    .patient-report--historial .report-admission { margin-top:2px; }
    .patient-report--historial .admission-heading { padding:2px 4px; }
    .patient-report--historial .admission-heading h2 { font-size:8.3px; }
    .patient-report--historial .admission-heading p { font-size:6.3px; }
    .patient-report--historial .admission-body { padding:2px 3px 3px; }
    .patient-report--historial .admission-block { margin-bottom:1px; }
    .patient-report--historial .admission-block-title { font-size:6.9px; }
    .patient-report--historial .report-grid { gap:1px 6px; margin:1px 0; }
    .patient-report--historial .report-field { font-size:7.6px; }
    .patient-report--historial .report-field dt { margin-bottom:0; font-size:6.4px; }
    .patient-report--historial .clinical-columns { margin:1px 0; }
    .patient-report--historial .clinical-card,
    .patient-report--historial .clinical-card h3 { font-size:7.2px; }
    .patient-report--historial .clinical-card { padding:1px 3px; }
    .patient-report--historial .clinical-label { font-size:6px; }
    .patient-report--historial .odontogram-panel { margin:1px 0; padding:1px 3px; }
    .patient-report--historial .odontogram-panel .odontograma .odontograma-cuadrante { margin-bottom:1px; }
    .patient-report--historial .odontogram-panel .odontograma .diente-ilustracion { height:42px; }
    .patient-report--historial .odontogram-panel .odontograma .diente-imagen { width:24px; height:42px; }
    .patient-report--historial .odontogram-panel .odontograma .diente svg { width:11px; height:11px; }
    .patient-report--historial .odontogram-panel .odontograma .diente svg.diente-overlay { width:24px; height:42px; }
    .patient-report--historial .odontogram-panel .odontograma .diente-numero { font-size:5.8px; }
    .patient-report--historial .patient-section { margin-top:2px; }
    .patient-report--historial .patient-section-title { margin:2px 0 1px; padding:1px 4px; font-size:7.8px; }
    .patient-report--historial .patient-history { margin:1px 0 2px; padding:2px 3px; }
    @endif
</style>
@endpush

@section('reporte')
<div class="patient-report {{ $soloFicha ? 'patient-report--ficha' : 'patient-report--historial' }}">
    <p class="patient-lead">{{ $soloFicha ? 'Evaluación inicial, examen odontológico y derivación clínica.' : 'Resumen consolidado de admisión, evolución clínica, estudios y pagos.' }}</p>

    <section class="patient-summary" aria-labelledby="patient-name">
        <div class="patient-summary-main">
            <div>
                <span class="patient-kicker">Identificación del paciente</span>
                <h2 class="patient-summary-name" id="patient-name">{{ $paciente->nombre_completo }}</h2>
            </div>
            <div><span class="patient-summary-code">Paciente #{{ str_pad((string) $paciente->id, 6, '0', STR_PAD_LEFT) }}</span></div>
        </div>
        <div class="patient-facts">
            <div class="patient-fact"><span>Documento / CI</span><strong>{{ $paciente->ci }}</strong></div>
            <div class="patient-fact"><span>Nacimiento</span><strong>{{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? 'Sin registrar' }}</strong></div>
            <div class="patient-fact"><span>Edad</span><strong>{{ $paciente->edad !== null ? $paciente->edad.' años' : 'Sin registrar' }}</strong></div>
            <div class="patient-fact"><span>Sexo registrado</span><strong>{{ match(mb_strtolower((string) $paciente->genero)) { 'm', 'masculino' => 'Masculino', 'f', 'femenino' => 'Femenino', default => $paciente->genero ?: 'Sin registrar' } }}</strong></div>
            <div class="patient-fact"><span>Ficha consultada</span><strong>#{{ $admisiones->first()?->id ?? '—' }}</strong></div>
        </div>
    </section>

    @unless($soloFicha)
    <div class="patient-metrics" aria-label="Resumen del historial">
        <div class="patient-metric"><span>Admisiones</span><strong>{{ $admisiones->count() }}</strong></div>
        @if($permisos['historias'])<div class="patient-metric"><span>Historias clínicas</span><strong>{{ $historias->count() }}</strong></div>@endif
        @if($permisos['remisiones'])<div class="patient-metric"><span>Derivaciones</span><strong>{{ $remisiones->count() }}</strong></div>@endif
        @if($permisos['radiologias'])<div class="patient-metric"><span>Estudios radiológicos</span><strong>{{ $radiologias->count() }}</strong></div>@endif
    </div>
    @endunless

    @foreach($admisiones as $ficha)
        @include('reportes.partials.ficha-admision', ['ficha' => $ficha, 'hallazgosFicha' => $hallazgos[$ficha->id]])
    @endforeach

    @if($permisos['remisiones'] && $remisiones->isNotEmpty())
    <section class="patient-section">
        <h2 class="patient-section-title">Derivaciones a especialidades</h2>
        <table>
            <thead><tr><th>Derivación / ficha</th><th>Especialidad</th><th>Periodo</th><th>Asignación</th><th>Estado</th></tr></thead>
            <tbody>@foreach($remisiones as $remision)
                <tr><td>#{{ $remision->id }} / #{{ $remision->admision_id }}</td><td>{{ $remision->clinica?->especialidad }}</td><td>{{ $remision->periodo?->nombre }}</td><td>{{ $remision->fecha_asignacion?->format('d/m/Y') ?? 'Pendiente' }}</td><td>{{ ucfirst(str_replace('_', ' ', (string) $remision->estado)) }}{{ $remision->habilitado ? '' : ' · Inhabilitada' }}</td></tr>
            @endforeach</tbody>
        </table>
    </section>
    @endif

    @if($permisos['historias'])
    <section class="patient-section">
        <h2 class="patient-section-title">Historias clínicas y evolución de tratamientos</h2>
        @forelse($historias as $historia)
            <article class="patient-history">
                <header class="patient-history-heading">
                    <h3>Historia #{{ $historia->id }} · {{ $historia->clinica?->especialidad }}</h3>
                    <p>{{ $historia->periodo?->nombre }} · {{ ucfirst((string) $historia->estado) }} · {{ $historia->autorizado ? 'Autorizada' : 'Pendiente de autorización' }}</p>
                </header>
                <p class="patient-history-meta">Estudiante: {{ $historia->estudiante?->nombre_completo }} · Docente: {{ $historia->docente?->nombre_completo }}</p>
                @forelse($historia->tratamientosRealizados as $tratamiento)
                    <div class="report-note"><strong>{{ $tratamiento->tratamiento?->nombre }}</strong> · Pieza: {{ $tratamiento->pieza_dental ?: 'Sin especificar' }} · Estado: {{ ucfirst(str_replace('_', ' ', (string) $tratamiento->estado)) }} · Costo autorizado: {{ number_format((float) $tratamiento->costo_cobrado, 2) }} Bs
                        @if($tratamiento->observacion)<p>{{ $tratamiento->observacion }}</p>@endif
                        @if($tratamiento->fecha_culminacion)<p>Culminación docente: {{ $tratamiento->fecha_culminacion->format('d/m/Y H:i') }}</p>@endif
                    </div>
                    @if($tratamiento->sesionesAtencion->isNotEmpty())
                    <table><thead><tr><th>Fecha</th><th>Avance clínico</th><th>Revisión docente</th><th>Observación docente</th></tr></thead>
                        <tbody>@foreach($tratamiento->sesionesAtencion->sortBy('fecha') as $sesion)
                        <tr><td>{{ $sesion->fecha?->format('d/m/Y') }}</td><td>{{ $sesion->descripcion }}</td><td>{{ $sesion->autorizado ? 'Autorizado' : 'Pendiente' }} · {{ $sesion->docente?->nombre_completo }}</td><td>{{ $sesion->observacion_docente ?: 'Sin observación' }}</td></tr>
                        @endforeach</tbody>
                    </table>
                    @else
                        <p class="patient-empty">Sin sesiones registradas para este tratamiento.</p>
                    @endif
                @empty
                    <p class="patient-empty">Sin tratamientos registrados en esta historia.</p>
                @endforelse
            </article>
        @empty
            <p class="patient-empty">Sin historias clínicas registradas.</p>
        @endforelse
    </section>
    @endif

    @if($permisos['radiologias'] && $radiologias->isNotEmpty())
    <section class="patient-section">
        <h2 class="patient-section-title">Estudios radiológicos</h2>
        <table><thead><tr><th>Estudio</th><th>Fecha de toma</th><th>Estado</th><th>Observación</th></tr></thead>
            <tbody>@foreach($radiologias as $estudio)
            <tr><td>#{{ $estudio->id }} · {{ $estudio->descripcion }}</td><td>{{ $estudio->fecha_toma?->format('d/m/Y H:i') ?? 'Pendiente' }}</td><td>{{ ucfirst((string) $estudio->estado) }}</td><td>{{ $estudio->observacion ?: 'Sin observación' }}</td></tr>
            @endforeach</tbody>
        </table>
    </section>
    @endif

    @if($permisos['cajas'] && $pagos->isNotEmpty())
    <section class="patient-section">
        <h2 class="patient-section-title">Pagos del paciente</h2>
        <div class="patient-metrics patient-payment-summary">
            <div class="patient-metric"><span>Costos en comprobantes Bs</span><strong>{{ number_format((float) $pagos->sum('costo_total'), 2) }}</strong></div>
            <div class="patient-metric"><span>Pagado Bs</span><strong>{{ number_format((float) $pagos->sum('cancelado'), 2) }}</strong></div>
            <div class="patient-metric"><span>Saldo Bs</span><strong>{{ number_format((float) $pagos->sum('costo_total') - (float) $pagos->sum('cancelado'), 2) }}</strong></div>
        </div>
        <table><thead><tr><th>Recibo</th><th>Fecha</th><th>Concepto</th><th>Costo Bs</th><th>Pagado Bs</th><th>Saldo Bs</th></tr></thead>
            <tbody>@foreach($pagos as $pago)
            <tr><td>{{ $pago->recibo }}</td><td>{{ $pago->created_at?->format('d/m/Y') }}</td><td>{{ $pago->descripcion }}</td><td>{{ number_format((float) $pago->costo_total, 2) }}</td><td>{{ number_format((float) $pago->cancelado, 2) }}</td><td>{{ number_format($pago->saldo, 2) }}</td></tr>
            @endforeach</tbody>
        </table>
    </section>
    @endif
</div>
@endsection
