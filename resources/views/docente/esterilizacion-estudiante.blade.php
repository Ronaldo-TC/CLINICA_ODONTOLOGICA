@extends('layouts.app')

@section('title', 'Esterilización del estudiante')
@section('page-title', 'Esterilización del estudiante')
@section('page-subtitle', 'Historial de entregas y autorizaciones')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Registros del estudiante #{{ $estudianteId }}</h2><a class="btn btn-secondary btn-sm" href="{{ route('docente.esterilizaciones') }}">Volver</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Clínica</th>
                    <th>Sobres</th>
                    <th>Paquetes</th>
                    <th>Estado</th>
                    <th>Autorizaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($esterilizaciones as $esterilizacion)
                <tr>
                    <td>{{ $esterilizacion->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $esterilizacion->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $esterilizacion->cantidad_sobres }}</td>
                    <td>{{ $esterilizacion->cantidad_paquetes }}</td>
                    <td>{{ $esterilizacion->habilitada ? 'Activa' : 'Inactiva' }}</td>
                    <td>@forelse ($esterilizacion->autorizaciones as $autorizacion)<div>Sobre {{ $autorizacion->sobre }}: {{ $autorizacion->estado ? 'Aprobado' : 'Rechazado' }} · {{ $autorizacion->docente }}</div>@empty<span>Sin autorización</span>@endforelse</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">No hay registros para este estudiante.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection