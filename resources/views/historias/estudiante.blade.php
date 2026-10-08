@extends('layouts.app')
@section('title', $titulo)
@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info"><span data-estudiante-total>{{ $registros->total() }}</span> {{ $soloActivas ? 'pacientes en atención' : 'historias clínicas' }}</div>
    <div class="toolbar-actions"><a class="btn btn-secondary" href="{{ route('estudiante.derivados') }}">Pacientes derivados</a></div>
</div>
<div class="card">
    <div class="card-header"><h2>{{ $titulo }}</h2></div>
    <div class="card-body">
        <p>Abre la historia del paciente para adjuntar documentos, proponer tratamientos, registrar avances y consultar sus insumos y pagos.</p>
        @include('historias.partials.filtros-estudiante')
    </div>
    <div data-estudiante-tabla>
    @fragment('tabla-estudiante')
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Paciente</th><th>Edad</th><th>Consulta</th><th>Especialidad</th><th>Docente asignado</th><th>Estado</th><th>Fecha inicio</th><th>Fecha culminación</th><th>Historial</th><th>Esterilización</th><th>Caja</th><th>Insumos</th></tr></thead>
            <tbody>
                @forelse($registros as $historia)
                    <tr>
                        <td>{{ $historia->id }}<br><small>Ficha #{{ $historia->admision_id }}</small></td>
                        <td><a href="{{ route('estudiante.historia', $historia->id) }}">{{ $historia->paciente?->nombre_completo }}</a><br><small>CI: {{ $historia->paciente?->ci }}</small></td>
                        <td>{{ $historia->paciente?->edad ?? '—' }}</td>
                        <td>{{ $historia->admision?->motivo_consulta ?: '—' }}</td>
                        <td>{{ $historia->clinica?->especialidad }}</td>
                        <td>{{ $historia->docente?->nombre_completo ?? 'Sin docente' }}</td>
                        <td><span class="badge {{ $historia->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $historia->autorizado ? ucfirst($historia->estado) : 'Pendiente de revisión docente' }}</span></td>
                        <td>{{ ($historia->fecha_inicio ?? $historia->remision?->fecha_asignacion ?? $historia->created_at)?->format('d/m/Y') }}</td>
                        <td>{{ $historia->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('estudiante.historia', $historia->id) }}">Abrir historia</a><br><small>{{ $historia->archivos_count }} archivos · {{ $historia->tratamientos_realizados_count }} tratamientos</small></td>
                        <td>@canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])<a class="btn btn-sm btn-secondary" href="{{ route('estudiante.historia', $historia->id) }}#esterilizacion">Ver control</a>@endcanany</td>
                        <td><a class="btn btn-sm btn-secondary" href="{{ route('estudiante.historia', $historia->id) }}#pagos">Ver pagos</a></td>
                        <td>@canany(['insumos.ver', 'insumos.gestionar'])<a class="btn btn-sm btn-secondary" href="{{ route('estudiante.historia', $historia->id) }}#insumos">Ver solicitudes</a>@endcanany
                            @if($soloActivas) @canany(['insumos.crear', 'insumos.gestionar'])<button class="btn btn-sm btn-primary" type="button" data-solicitar-designado data-historia="{{ $historia->id }}">Solicitar insumos</button>@endcanany @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13"><div class="empty-state"><p>No hay pacientes que coincidan con los filtros. Los pacientes asignados por tu docente aparecerán aquí.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $registros->links() }}</div>
    @endfragment
    </div>
</div>
@canany(['insumos.crear', 'insumos.gestionar'])
    @include('solicitudes-insumos.partials.modal-designado')
@endcanany
@endsection
