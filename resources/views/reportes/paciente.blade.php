@extends('layouts.reporte')
@section('reporte')
<p class="muted">{{ $soloFicha ? 'Evaluación inicial y áreas derivadas del paciente.' : 'Ficha de admisión y seguimiento de la atención registrada.' }}</p>
<h2>Identificación del paciente</h2>
<dl class="report-grid">
    <div class="report-field"><dt>Nombre completo</dt><dd>{{ $paciente->nombre_completo }}</dd></div>
    <div class="report-field"><dt>Documento / CI</dt><dd>{{ $paciente->ci }}</dd></div>
    <div class="report-field"><dt>Nacimiento</dt><dd>{{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? 'Sin registrar' }}</dd></div>
    <div class="report-field"><dt>Edad</dt><dd>{{ $paciente->edad !== null ? $paciente->edad.' años' : 'Sin registrar' }}</dd></div>
    <div class="report-field"><dt>Sexo registrado</dt><dd>{{ match(mb_strtolower((string) $paciente->genero)) { 'm', 'masculino' => 'Masculino', 'f', 'femenino' => 'Femenino', default => $paciente->genero ?: 'Sin registrar' } }}</dd></div>
    <div class="report-field"><dt>Número de paciente</dt><dd>{{ $paciente->id }}</dd></div>
</dl>
@unless($soloFicha)
<div class="report-metrics">
    <div class="report-metric"><span>Admisiones incluidas</span><strong>{{ $admisiones->count() }}</strong></div>
    @if($permisos['historias'])<div class="report-metric"><span>Historias clínicas</span><strong>{{ $historias->count() }}</strong></div>@endif
    @if($permisos['remisiones'])<div class="report-metric"><span>Derivaciones</span><strong>{{ $remisiones->count() }}</strong></div>@endif
    @if($permisos['radiologias'])<div class="report-metric"><span>Estudios radiológicos</span><strong>{{ $radiologias->count() }}</strong></div>@endif
</div>
@endunless
@foreach($admisiones as $ficha)
    @include('reportes.partials.ficha-admision', ['ficha' => $ficha, 'hallazgosFicha' => $hallazgos[$ficha->id]])
@endforeach
@if($permisos['remisiones'])
<h2>Derivaciones a especialidades</h2>
<table>
    <thead><tr><th>Derivación / ficha</th><th>Especialidad</th><th>Periodo</th><th>Asignación</th><th>Estado</th></tr></thead>
    <tbody>@forelse($remisiones as $remision)
        <tr><td>#{{ $remision->id }} / #{{ $remision->admision_id }}</td><td>{{ $remision->clinica?->especialidad }}</td><td>{{ $remision->periodo?->nombre }}</td><td>{{ $remision->fecha_asignacion?->format('d/m/Y') ?? 'Pendiente' }}</td><td>{{ ucfirst(str_replace('_', ' ', (string) $remision->estado)) }}{{ $remision->habilitado ? '' : ' · Inhabilitada' }}</td></tr>
        @empty<tr><td colspan="5">Sin derivaciones registradas.</td></tr>@endforelse
    </tbody>
</table>
@endif
@if($permisos['historias'])
<h2>Historias clínicas y evolución de tratamientos</h2>
@forelse($historias as $historia)
    <h3>Historia #{{ $historia->id }} · {{ $historia->clinica?->especialidad }}</h3>
    <p>Periodo: {{ $historia->periodo?->nombre }} · Estado: {{ ucfirst((string) $historia->estado) }} · {{ $historia->autorizado ? 'Autorizada' : 'Pendiente de autorización' }}</p>
    <p class="muted">Estudiante: {{ $historia->estudiante?->nombre_completo }} · Docente: {{ $historia->docente?->nombre_completo }}</p>
    @forelse($historia->tratamientosRealizados as $tratamiento)
    <div class="report-note"><strong>{{ $tratamiento->tratamiento?->nombre }}</strong><p>Pieza: {{ $tratamiento->pieza_dental ?: 'Sin especificar' }} · Estado: {{ ucfirst(str_replace('_', ' ', (string) $tratamiento->estado)) }} · Costo autorizado: {{ number_format((float) $tratamiento->costo_cobrado, 2) }} Bs</p>
        @if($tratamiento->observacion)<p>{{ $tratamiento->observacion }}</p>@endif
        @if($tratamiento->fecha_culminacion)<p>Culminación docente: {{ $tratamiento->fecha_culminacion->format('d/m/Y H:i') }}</p>@endif
    </div>
    <table><thead><tr><th>Fecha</th><th>Avance clínico</th><th>Revisión docente</th><th>Observación docente</th></tr></thead>
        <tbody>@forelse($tratamiento->sesionesAtencion->sortBy('fecha') as $sesion)
        <tr><td>{{ $sesion->fecha?->format('d/m/Y') }}</td><td>{{ $sesion->descripcion }}</td><td>{{ $sesion->autorizado ? 'Autorizado' : 'Pendiente' }} · {{ $sesion->docente?->nombre_completo }}</td><td>{{ $sesion->observacion_docente ?: 'Sin observación' }}</td></tr>
        @empty<tr><td colspan="4">Sin sesiones registradas.</td></tr>@endforelse</tbody>
    </table>
    @empty<p class="muted">Sin tratamientos registrados en esta historia.</p>@endforelse
@empty<p class="muted">Sin historias clínicas registradas.</p>@endforelse
@endif
@if($permisos['radiologias'])
<h2>Estudios radiológicos</h2>
<table><thead><tr><th>Estudio</th><th>Fecha de toma</th><th>Estado</th><th>Observación</th></tr></thead>
    <tbody>@forelse($radiologias as $estudio)
    <tr><td>#{{ $estudio->id }} · {{ $estudio->descripcion }}</td><td>{{ $estudio->fecha_toma?->format('d/m/Y H:i') ?? 'Pendiente' }}</td><td>{{ ucfirst((string) $estudio->estado) }}</td><td>{{ $estudio->observacion ?: 'Sin observación' }}</td></tr>
    @empty<tr><td colspan="4">Sin estudios radiológicos registrados.</td></tr>@endforelse</tbody>
</table>
@endif
@if($permisos['cajas'])
<h2>Pagos del paciente</h2>
<p class="muted">Comprobantes vigentes vinculados al paciente.</p>
<div class="report-metrics">
    <div class="report-metric"><span>Costos en comprobantes Bs</span><strong>{{ number_format((float) $pagos->sum('costo_total'), 2) }}</strong></div>
    <div class="report-metric"><span>Pagado Bs</span><strong>{{ number_format((float) $pagos->sum('cancelado'), 2) }}</strong></div>
    <div class="report-metric"><span>Saldo Bs</span><strong>{{ number_format((float) $pagos->sum('costo_total') - (float) $pagos->sum('cancelado'), 2) }}</strong></div>
</div>
<table><thead><tr><th>Recibo</th><th>Fecha</th><th>Concepto</th><th>Costo Bs</th><th>Pagado Bs</th><th>Saldo Bs</th></tr></thead>
    <tbody>@forelse($pagos as $pago)
    <tr><td>{{ $pago->recibo }}</td><td>{{ $pago->created_at?->format('d/m/Y') }}</td><td>{{ $pago->descripcion }}</td><td>{{ number_format((float) $pago->costo_total, 2) }}</td><td>{{ number_format((float) $pago->cancelado, 2) }}</td><td>{{ number_format($pago->saldo, 2) }}</td></tr>
    @empty<tr><td colspan="6">Sin pagos registrados.</td></tr>@endforelse</tbody>
</table>
@endif
@endsection
