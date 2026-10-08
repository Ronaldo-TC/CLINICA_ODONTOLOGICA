@extends('layouts.app')
@section('title', 'Mis Pacientes Admitidos')
@section('content')
<div class="dashboard-toolbar"><div class="toolbar-info"><span data-estudiante-total>{{ $registros->total() }}</span> admisiones propias</div>@canany(['admisiones.crear', 'admisiones.gestionar'])<a class="btn btn-primary" href="{{ route('admisiones.create') }}">Nueva admisión</a>@endcanany</div>
<div class="card">
    <div class="card-header"><h2>Mis Pacientes Admitidos</h2></div>
    <div class="card-body"><p>Pacientes cuya ficha de admisión has registrado o tienes asignada en admisión.</p>@include('historias.partials.filtros-estudiante', ['mostrarClinica' => false])</div>
    <div data-estudiante-tabla>
    @fragment('tabla-estudiante')
    <div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Paciente</th><th>Consulta</th><th>Fecha admisión</th><th>Docente responsable</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        @forelse($registros as $admision)
            <tr><td>{{ $admision->id }}</td><td>{{ $admision->paciente?->nombre_completo }}<br><small>CI: {{ $admision->paciente?->ci }}</small></td><td>{{ $admision->motivo_consulta ?: '—' }}</td><td>{{ ($admision->fecha_admision ?? $admision->created_at)?->format('d/m/Y') }}</td><td>{{ $admision->responsable?->nombre_completo }}</td><td><span class="badge badge-info">{{ ucfirst($admision->estado) }}</span></td><td><a class="btn btn-sm btn-secondary" href="{{ route('admisiones.show', $admision->id) }}">Ver ficha</a>@if(!$admision->fecha_autorizacion) @canany(['admisiones.editar', 'admisiones.gestionar']) <a class="btn btn-sm btn-primary" href="{{ route('admisiones.edit', $admision->id) }}">Completar admisión</a>@endcanany @endif</td></tr>
        @empty
            <tr><td colspan="7"><div class="empty-state"><p>No tienes admisiones registradas.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-body">{{ $registros->links() }}</div>
    @endfragment
    </div>
</div>
@endsection
