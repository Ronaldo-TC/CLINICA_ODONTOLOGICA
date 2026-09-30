@extends('layouts.app')

@section('title', 'Radiologías')
@section('page-title', 'Radiologías de mis pacientes')
@section('page-subtitle', 'Consulta de estudios asociados a historias bajo tu supervisión')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Estudios radiológicos</h2><span class="card-meta">{{ $radiologias->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.radiologias') }}" class="card-body form-grid">
        <div class="form-group"><label for="pacienteId">ID de paciente</label><input class="form-control" id="pacienteId" type="number" min="1" name="paciente_id" value="{{ request('paciente_id') }}"></div>
        <div class="form-group"><label for="estado">Estado</label><input class="form-control" id="estado" name="estado" value="{{ request('estado') }}"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.radiologias') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Observación</th>
                    <th>Archivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($radiologias as $radiologia)
                <tr>
                    <td>{{ $radiologia->fecha_toma?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $radiologia->paciente?->nombre_completo ?? '—' }}</td>
                    <td>{{ $radiologia->estado ?? '—' }}</td>
                    <td>{{ $radiologia->descripcion ?? '—' }}</td>
                    <td>{{ $radiologia->observacion ?? '—' }}</td>
                    <td>@if($radiologia->archivo_ruta)<a href="{{ \Illuminate\Support\Facades\Storage::url($radiologia->archivo_ruta) }}" target="_blank" rel="noopener">Ver imagen</a>@else—@endif</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">No hay radiologías asociadas a tus pacientes.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $radiologias->withQueryString()->links() }}</div>
</section>
@endsection