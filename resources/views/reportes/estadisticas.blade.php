@extends('layouts.reporte')

@section('reporte')
    <p class="report-note"><strong>Filtros:</strong> {{ $descripcion }}</p>
    @foreach($catalogo as $clave => $definicion)
        @php
            $resultado = $datos[$clave];
            $maximo = max([1, ...$resultado['valores']]);
        @endphp
        <h2>{{ $definicion['titulo'] }}</h2>
        <div class="report-table">
            <table>
                <thead><tr><th>{{ $definicion['etiqueta'] }}</th><th>{{ $definicion['valor'] }}</th><th style="width:35%">Distribución</th></tr></thead>
                <tbody>
                    @forelse($resultado['labels'] as $indice => $etiqueta)
                        @php($valor = $resultado['valores'][$indice] ?? 0)
                        <tr>
                            <td>{{ $etiqueta }}</td><td>{{ number_format($valor, 2, '.', ',') }}</td>
                            <td><div style="height:7px;background:#e9f0ed"><div style="height:7px;background:#157064;width:{{ round($valor / $maximo * 100, 2) }}%"></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No hay registros para los filtros seleccionados.</td></tr>
                    @endforelse
                    <tr><th>Total</th><th>{{ number_format($resultado['total'], 2, '.', ',') }}</th><td></td></tr>
                </tbody>
            </table>
        </div>
    @endforeach
    <p class="report-note">Las cifras corresponden a cada indicador y a los filtros que admite. Las cantidades y los importes monetarios conservan sus unidades.</p>
@endsection
