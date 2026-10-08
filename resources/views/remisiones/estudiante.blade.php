@extends('layouts.app')
@section('title', 'Pacientes derivados')
@section('content')
<div class="dashboard-toolbar"><div class="toolbar-info"><span data-estudiante-total>{{ $registros->total() }}</span> derivaciones recibidas</div><a class="btn btn-primary" href="{{ route('estudiante.designados') }}">Mis Pacientes Designados</a></div>
<div class="card">
    <div class="card-header"><h2>Pacientes derivados</h2></div>
    <div class="card-body"><p>Pacientes que el docente te ha asignado para su atención en una especialidad.</p>@include('historias.partials.filtros-estudiante')</div>
    <div data-estudiante-tabla>
    @fragment('tabla-estudiante')
    <div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Paciente</th><th>Consulta</th><th>Especialidad</th><th>Docente asignado</th><th>Fecha de asignación</th><th>Estado</th><th>Historia clínica</th></tr></thead>
        <tbody>
        @forelse($registros as $remision)
            <tr><td>{{ $remision->id }}</td><td>{{ $remision->paciente?->nombre_completo }}<br><small>CI: {{ $remision->paciente?->ci }}</small></td><td>{{ $remision->admision?->motivo_consulta ?: '—' }}</td><td>{{ $remision->clinica?->especialidad }}</td><td>{{ $remision->docente?->nombre_completo }}</td><td>{{ $remision->fecha_asignacion?->format('d/m/Y H:i') ?? '—' }}</td><td><span class="badge badge-info">{{ ucfirst($remision->estado) }}</span></td>
            <td>@forelse($remision->historiasClinicas->where('estudiante_id', auth()->id()) as $historia)<a class="btn btn-sm btn-primary" href="{{ route('estudiante.historia', $historia->id) }}">Abrir historia #{{ $historia->id }}</a>@empty Pendiente de creación por el docente @endforelse</td></tr>
        @empty
            <tr><td colspan="8"><div class="empty-state"><p>Todavía no tienes pacientes derivados.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-body">{{ $registros->links() }}</div>
    @endfragment
    </div>
</div>
@endsection
