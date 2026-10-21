@extends('layouts.app')
@section('title', $titulo)
@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span data-estudiante-total>{{ $registros->total() }}</span>
        {{ $soloActivas ? 'pacientes en atención' : 'historias clínicas' }}
    </div>
    <div class="toolbar-actions">
        <a class="btn btn-secondary" href="{{ route('estudiante.derivados') }}">Pacientes derivados</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $titulo }}</h2>
    </div>
    <div class="card-body">
        <p>Abre la historia del paciente para adjuntar documentos, proponer tratamientos, registrar avances y consultar sus insumos y pagos.</p>
        @include('historias.partials.filtros-estudiante')
    </div>

    <div data-estudiante-tabla>
        @fragment('tabla-estudiante')
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Paciente</th>
                        <th>Edad</th>
                        <th>Consulta</th>
                        <th>Especialidad</th>
                        <th>Docente asignado</th>
                        <th>Estado</th>
                        <th>Fecha inicio</th>
                        <th>Fecha culminación</th>
                        <th>Historial</th>
                        <th>Esterilización</th>
                        <th>Caja</th>
                        <th>Insumos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $historia)
                    <tr>
                        <td>{{ $historia->id }}<br><small>Ficha #{{ $historia->admision_id }}</small></td>
                        <td>
                            <a href="{{ route('estudiante.historia', $historia->id) }}">{{ $historia->paciente?->nombre_completo }}</a>
                            <br><small>CI: {{ $historia->paciente?->ci }}</small>
                        </td>
                        <td>{{ $historia->paciente?->edad ?? '—' }}</td>
                        <td>{{ $historia->admision?->motivo_consulta ?: '—' }}</td>
                        <td>{{ $historia->clinica?->especialidad }}</td>
                        <td>{{ $historia->docente?->nombre_completo ?? 'Sin docente' }}</td>
                        <td>
                            <span class="badge {{ $historia->autorizado ? 'badge-success' : 'badge-warning' }}">
                                {{ $historia->autorizado ? ucfirst($historia->estado) : 'Pendiente de revisión docente' }}
                            </span>
                        </td>
                        <td>{{ ($historia->fecha_inicio ?? $historia->remision?->fecha_asignacion ?? $historia->created_at)?->format('d/m/Y') }}</td>
                        <td>{{ $historia->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <button type="button"
                                    class="btn btn-sm btn-secondary estudiante-modal-action"
                                    data-historia-seguimiento="{{ $historia->id }}"
                                    data-seguimiento-seccion="tratamientos"
                                    data-seguimiento-titulo="Tratamientos"
                                    aria-haspopup="dialog">
                                    <x-icono nombre="tratamientos" /> Gestionar tratamientos
                                </button>
                                <button type="button"
                                    class="btn btn-sm btn-secondary estudiante-modal-action"
                                    data-historia-seguimiento="{{ $historia->id }}"
                                    data-seguimiento-seccion="archivos"
                                    data-seguimiento-titulo="Archivos adjuntos"
                                    aria-haspopup="dialog">
                                    <x-icono nombre="archivos" /> Gestionar archivos
                                </button>
                            </div>
                            <small>{{ $historia->archivos_count }} archivos · {{ $historia->tratamientos_realizados_count }} tratamientos</small>
                            <br><a href="{{ route('estudiante.historia', $historia->id) }}">Abrir historia completa</a>
                        </td>
                        <td>
                            @canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
                            <button type="button"
                                class="btn btn-sm btn-secondary estudiante-modal-action"
                                data-historia-seguimiento="{{ $historia->id }}"
                                data-seguimiento-seccion="controles"
                                data-seguimiento-titulo="Controles de esterilización"
                                aria-haspopup="dialog">
                                <x-icono nombre="control" /> Ver control
                            </button>
                            @endcanany
                        </td>
                        <td>
                            <button type="button"
                                class="btn btn-sm btn-secondary estudiante-modal-action"
                                data-historia-seguimiento="{{ $historia->id }}"
                                data-seguimiento-seccion="recibos"
                                data-seguimiento-titulo="Recibos de Caja"
                                aria-haspopup="dialog">
                                <x-icono nombre="recibo" /> Ver pagos
                            </button>
                        </td>
                        <td>
                            @canany(['solicitudes-insumos.ver', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                            <div class="actions">
                                <button type="button"
                                    class="btn btn-sm btn-secondary estudiante-modal-action"
                                    data-historia-seguimiento="{{ $historia->id }}"
                                    data-seguimiento-seccion="solicitudes"
                                    data-seguimiento-titulo="Solicitudes de insumos"
                                    aria-haspopup="dialog">
                                    <x-icono nombre="insumos" /> Ver solicitudes
                                </button>
                                @if(!$historia->fecha_fin && !in_array($historia->estado, $estadosCerrados, true))
                                <button class="btn btn-sm btn-primary estudiante-modal-action" type="button"
                                    data-solicitar-designado data-historia="{{ $historia->id }}" aria-haspopup="dialog">
                                    <x-icono nombre="insumos" /> Solicitar insumos
                                </button>
                                @endif
                            </div>
                            @endcanany
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13">
                            <div class="empty-state">
                                <p>No hay pacientes que coincidan con los filtros. Los pacientes asignados por tu docente aparecerán aquí.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $registros->links() }}</div>
        @endfragment
    </div>
</div>

@include('historias.partials.modal-seguimiento')
@canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
    @include('solicitudes-insumos.partials.modal-designado')
@endcanany
@endsection

@push('styles')
<style>
    .estudiante-modal-action {
        justify-content: flex-start;
        white-space: nowrap;
    }

    .estudiante-modal-action svg {
        flex: 0 0 auto;
    }

    [data-estudiante-tabla] td .actions {
        align-items: stretch;
        flex-direction: column;
    }

    [data-estudiante-tabla] td .actions .estudiante-modal-action {
        width: 100%;
    }
</style>
@endpush
