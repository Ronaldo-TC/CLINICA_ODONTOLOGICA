@php
    $esEstudiante = auth()->user()->hasAnyRole(['estudiante', 'interno']);
@endphp

<div class="seguimiento-summary">
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Historia</span><span class="seguimiento-summary__value">#{{ $registro->id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Ficha</span><span class="seguimiento-summary__value">#{{ $registro->admision_id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Solicitudes</span><span class="seguimiento-summary__value">{{ $solicitudes->count() }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Flujo</span><span class="seguimiento-summary__value">Docente &rarr; Insumos</span></div>
</div>

@if($esEstudiante && ! $cerrada)
    @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
        <div class="seguimiento-compose">
            <div class="seguimiento-actions">
                <button class="btn btn-primary" type="button" data-solicitar-designado data-historia="{{ $registro->id }}">+ Nueva solicitud de insumos</button>
            </div>
        </div>
    @endcanany
@endif

<div class="table-wrap seguimiento-table-wrap">
    <table class="seguimiento-table">
        <thead>
            <tr>
                <th>Solicitud</th>
                <th>Tratamiento</th>
                <th>Estudiante</th>
                <th>Estado</th>
                <th>Revisi&oacute;n</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($solicitudes as $solicitud)
                @php
                    $estadoClase = match ($solicitud->estado) {
                        'aprobado', 'entregado' => 'badge-success',
                        'rechazado', 'cancelado' => 'badge-danger',
                        'pendiente', 'autorizado', 'parcial' => 'badge-warning',
                        default => 'badge-info',
                    };
                @endphp
                <tr>
                    <td>
                        <span class="seguimiento-table__primary">#{{ $solicitud->id }}</span>
                        @if($solicitud->sesion_atencion_id)<span class="seguimiento-table__secondary">Avance #{{ $solicitud->sesion_atencion_id }}</span>@endif
                    </td>
                    <td>{{ $solicitud->tratamiento?->nombre ?? $solicitud->tratamientoRealizado?->tratamiento?->nombre ?? 'Sin tratamiento' }}</td>
                    <td>{{ $solicitud->estudiante?->nombre_completo ?? 'Sin asignar' }}</td>
                    <td>
                        <span class="badge {{ $estadoClase }}">{{ ucfirst($solicitud->estado) }}</span>
                        @if($solicitud->motivo_rechazo)<span class="seguimiento-table__secondary">{{ $solicitud->motivo_rechazo }}</span>@endif
                    </td>
                    <td>
                        <span class="seguimiento-table__primary">Docente: {{ $solicitud->docente?->nombre_completo ?? 'Pendiente' }}</span>
                        <span class="seguimiento-table__secondary">Insumos: {{ $solicitud->encargado?->nombre_completo ?? 'Pendiente' }}</span>
                    </td>
                    <td>
                        <div class="seguimiento-actions">
                            @if($esEstudiante && ! $cerrada)
                                @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                                    <button class="btn btn-sm btn-primary" type="button" data-solicitar-designado data-historia="{{ $registro->id }}" data-tratamiento="{{ $solicitud->tratamiento_id ?? $solicitud->tratamientoRealizado?->tratamiento_id }}">Repetir</button>
                                @endcanany
                            @endif
                            <a class="btn btn-sm btn-secondary" href="{{ route('solicitudes-insumos.show', $solicitud->id) }}">Detalle</a>
                            @if($esEstudiante && ! $cerrada && in_array($solicitud->estado, ['pendiente', 'rechazado'], true))
                                @canany(['solicitudes-insumos.eliminar', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                                    <form action="{{ route('solicitudes-insumos.destroy', $solicitud->id) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Eliminar esta solicitud de insumos?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                @endcanany
                            @endif
                        </div>
                    </td>
                </tr>
                <tr class="seguimiento-detail-row">
                    <td colspan="6">
                        <details class="seguimiento-details">
                            <summary>Ver materiales y opciones de revisi&oacute;n</summary>
                            <div class="seguimiento-details__body">
                                <div class="seguimiento-subtable">
                                    <table>
                                        <thead><tr><th>Insumo</th><th>Medida</th><th>Solicitado</th><th>Disponible</th><th>Entregado</th><th>Utilizado</th></tr></thead>
                                        <tbody>
                                            @foreach($solicitud->detalles as $detalle)
                                                <tr>
                                                    <td><span class="seguimiento-table__primary">{{ $detalle->insumo?->nombre }}</span></td>
                                                    <td>{{ $detalle->insumo?->cantidad }}</td>
                                                    <td>{{ $detalle->cantidad_solicitada }}</td>
                                                    <td>{{ $detalle->insumo?->cantidad_numerica }}</td>
                                                    <td>{{ $detalle->cantidad_entregada }}</td>
                                                    <td>{{ $detalle->consumos->sum('cantidad_usada') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($esEstudiante && ! $cerrada && $solicitud->sesion_atencion_id && in_array($solicitud->estado, ['parcial', 'entregado'], true))
                                    @canany(['solicitudes-insumos.consumir', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                                        <div class="seguimiento-form-panel">
                                            <h4>Registrar materiales utilizados</h4>
                                            <form action="{{ route('solicitudes-insumos.consumir', $solicitud->id) }}" method="POST" class="form-grid" data-seguimiento-form>
                                                @csrf
                                                @foreach($solicitud->detalles as $indice => $detalle)
                                                    @php($disponible = max(0, (float) $detalle->cantidad_entregada - (float) $detalle->consumos->sum('cantidad_usada')))
                                                    <input type="hidden" name="detalles[{{ $indice }}][detalle_id]" value="{{ $detalle->id }}">
                                                    <div class="form-group">
                                                        <label for="seguimiento-consumo-{{ $detalle->id }}">{{ $detalle->insumo?->nombre }}</label>
                                                        <input id="seguimiento-consumo-{{ $detalle->id }}" name="detalles[{{ $indice }}][cantidad_usada]" type="number" class="form-control" min="0" step="0.01" max="{{ $disponible }}" value="0">
                                                        <small>Disponible: {{ number_format($disponible, 2) }}</small>
                                                    </div>
                                                @endforeach
                                                <div><button type="submit" class="btn btn-primary">Guardar cantidades utilizadas</button></div>
                                            </form>
                                        </div>
                                    @endcanany
                                @endif

                                <div class="seguimiento-form-panel">
                                    <h4>Gesti&oacute;n de la solicitud</h4>
                                    @include('solicitudes-insumos.partials.revision', ['solicitud' => $solicitud])
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="seguimiento-empty-cell">Este estudiante no tiene solicitudes visibles para esta historia.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
