@extends('layouts.app')

@section('title', 'Reportes de cátedra')
@section('page-title', 'Reportes de cátedra')
@section('page-subtitle', 'Actividad clínica de tus pacientes y estudiantes')

@section('content')
<form method="GET" action="{{ route('docente.reportes') }}" class="card">
    <div class="card-body form-grid">
        <div class="form-group"><label for="fechaInicio">Desde</label><input class="form-control" id="fechaInicio" type="date" name="fecha_inicio" value="{{ $fecha_inicio }}"></div>
        <div class="form-group"><label for="fechaFin">Hasta</label><input class="form-control" id="fechaFin" type="date" name="fecha_fin" value="{{ $fecha_fin }}"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Aplicar periodo</button></div>
    </div>
</form>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Pacientes por clínica</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Clínica</th>
                    <th>Historias</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pacientes_por_periodo as $fila)<tr>
                    <td>{{ $fila->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $fila->total }}</td>
                </tr>@empty<tr>
                    <td colspan="2">Sin actividad en el periodo.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Tratamientos por clínica</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Clínica</th>
                    <th>Tratamientos</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tratamientos_por_especialidad as $fila)<tr>
                    <td>{{ $fila->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $fila->total }}</td>
                    <td>Bs {{ number_format((float)$fila->monto_total, 2) }}</td>
                </tr>@empty<tr>
                    <td colspan="3">Sin tratamientos en el periodo.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Atenciones por estudiante</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Sesiones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($atenciones_por_estudiante as $fila)<tr>
                    <td>{{ $fila->estudiante?->nombre_completo ?? '—' }}</td>
                    <td>{{ $fila->total_sesiones }}</td>
                </tr>@empty<tr>
                    <td colspan="2">Sin sesiones en el periodo.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Tratamientos por estado</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tratamientos_por_estado as $fila)<tr>
                    <td>{{ ucfirst($fila->estado) }}</td>
                    <td>{{ $fila->total }}</td>
                </tr>@empty<tr>
                    <td colspan="2">Sin tratamientos.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection