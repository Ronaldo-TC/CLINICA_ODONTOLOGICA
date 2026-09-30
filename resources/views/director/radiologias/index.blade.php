@extends('layouts.app')

@section('title', 'Radiologías')
@section('page-title', 'Radiologías')
@section('page-subtitle', 'Consulta global de estudios radiológicos')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Registro de radiologías</h2>
        <span class="card-meta">{{ $radiologias->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('director.radiologias.index') }}" class="card-body form-grid">
        <div class="form-group">
            <label for="pacienteId">ID de paciente</label>
            <input class="form-control" id="pacienteId" name="paciente_id" type="number" min="1" value="{{ request('paciente_id') }}">
        </div>
        <div class="form-group">
            <label for="estado">Estado</label>
            <input class="form-control" id="estado" name="estado" type="text" value="{{ request('estado') }}">
        </div>
        <div class="form-group">
            <label for="fechaInicio">Desde</label>
            <input class="form-control" id="fechaInicio" name="fecha_inicio" type="date" value="{{ request('fecha_inicio') }}">
        </div>
        <div class="form-group">
            <label for="fechaFin">Hasta</label>
            <input class="form-control" id="fechaFin" name="fecha_fin" type="date" value="{{ request('fecha_fin') }}">
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="{{ route('director.radiologias.index') }}">Limpiar</a>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Registrado por</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>@include('director.radiologias.partials.filas', ['radiologias' => $radiologias])</tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $radiologias->withQueryString()->links() }}</div>
</section>
@endsection