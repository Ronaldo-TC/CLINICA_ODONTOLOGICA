@extends('layouts.app')

@section('title', 'Mis inscripciones')
@section('page-title', 'Mis inscripciones')
@section('page-subtitle', 'Registro de especialidades asignadas')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span>{{ $inscripciones->count() }} registro(s)</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Especialidades</h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Completo</th>
                    <th>Especialidad</th>
                    <th>Fecha de Inscripción</th>
                    <th>Fecha de Baja</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inscripciones as $index => $registro)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $registro['nombre_completo'] }}</td>
                        <td>{{ $registro['especialidad'] }}</td>
                        <td>{{ $registro['fecha_inscripcion'] }}</td>
                        <td>{{ $registro['fecha_baja'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <p>No tiene inscripciones registradas.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
