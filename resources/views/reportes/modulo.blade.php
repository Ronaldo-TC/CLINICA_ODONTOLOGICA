@extends('layouts.reporte')
@section('reporte')
<p class="muted">Detalle de registros según los filtros seleccionados y el acceso del usuario.</p>
<div class="report-metrics">
    @foreach($resumen as $etiqueta => $valor)
    <div class="report-metric"><span>{{ $etiqueta }}</span><strong>{{ $valor }}</strong></div>
    @endforeach
</div>
@if($filtrosReporte)
<div class="report-note">
    <strong>Filtros aplicados</strong>
    @foreach($filtrosReporte as $etiqueta => $valor)<p>{{ $etiqueta }}: {{ $valor }}</p>@endforeach
</div>
@else
<p class="muted">Sin filtros adicionales.</p>
@endif
@if($limitado)
<p class="report-warning">Se incluyen los primeros {{ $limite }} registros. Refina los filtros para obtener el detalle restante. Los indicadores resumen todos los registros encontrados.</p>
@endif
<div class="report-table">
    <table>
        <thead><tr>@foreach($columnas as $columna)<th scope="col">{{ $columna }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($filas as $fila)
            <tr>@foreach($fila as $celda)<td>{{ $celda }}</td>@endforeach</tr>
            @empty
            <tr><td colspan="{{ count($columnas) }}">No hay registros para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
