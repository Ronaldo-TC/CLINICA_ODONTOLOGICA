@extends('layouts.app')

@section('title', 'Historias clínicas')
@section('page-title', 'Historias clínicas')
@section('page-subtitle', 'Historias asignadas a tu supervisión clínica')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Historias bajo supervisión</h2><span class="card-meta">{{ $historias->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.historias-clinicas') }}" class="card-body form-grid">
        <div class="form-group"><label for="buscar">Paciente</label><input class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o apellido"></div>
        <div class="form-group"><label for="autorizado">Autorización</label><select class="form-control" id="autorizado" name="autorizado">
                <option value="">Todas</option>
                <option value="1" @selected(request('autorizado')==='1' )>Autorizadas</option>
                <option value="0" @selected(request('autorizado')==='0' )>Pendientes</option>
            </select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.historias-clinicas') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Clínica</th>
                    <th>Estudiante</th>
                    <th>Estado</th>
                    <th>Revisión</th>
                    <th>Inicio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($historias as $historia)
                <tr>
                    <td>{{ $historia->paciente?->nombre_completo ?? '—' }}</td>
                    <td>{{ $historia->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $historia->estudiante?->nombre_completo ?? '—' }}</td>
                    <td>{{ $historia->estado ?? '—' }}</td>
                    <td>{{ $historia->autorizado ? 'Autorizada' : 'Pendiente' }}</td>
                    <td>{{ $historia->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                    <td><a class="btn btn-secondary btn-sm" href="{{ route('docente.ver-historia', $historia->id) }}">Revisar</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">No hay historias clínicas para mostrar.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $historias->withQueryString()->links() }}</div>
</section>
@endsection