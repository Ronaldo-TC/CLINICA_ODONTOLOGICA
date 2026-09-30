@extends('layouts.app')

@section('title', 'Esterilizaciones')
@section('page-title', 'Esterilizaciones')
@section('page-subtitle', 'Consulta global de entregas y autorizaciones')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Registro de esterilizaciones</h2>
        <span class="card-meta">{{ $esterilizaciones->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('director.esterilizaciones.index') }}" class="card-body form-grid">
        <div class="form-group">
            <label for="clinicaId">ID de clínica</label>
            <input class="form-control" id="clinicaId" name="clinica_id" type="number" min="1" value="{{ request('clinica_id') }}">
        </div>
        <div class="form-group">
            <label for="estudianteId">ID de estudiante</label>
            <input class="form-control" id="estudianteId" name="estudiante_id" type="number" min="1" value="{{ request('estudiante_id') }}">
        </div>
        <div class="form-group">
            <label for="habilitada">Estado</label>
            <select class="form-control" id="habilitada" name="habilitada">
                <option value="">Todos</option>
                <option value="1" @selected(request('habilitada')==='1' )>Habilitadas</option>
                <option value="0" @selected(request('habilitada')==='0' )>No habilitadas</option>
            </select>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="{{ route('director.esterilizaciones.index') }}">Limpiar</a>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th>Clínica</th>
                    <th>Sobres</th>
                    <th>Paquetes</th>
                    <th>Entrega</th>
                    <th>Recojo</th>
                    <th>Estado</th>
                    <th>Autorizaciones</th>
                </tr>
            </thead>
            <tbody>@include('director.esterilizaciones.partials.filas', ['esterilizaciones' => $esterilizaciones])</tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $esterilizaciones->withQueryString()->links() }}</div>
</section>
@endsection