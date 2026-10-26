@php
    $esEstudiante = auth()->user()->hasAnyRole(['estudiante', 'interno']);
@endphp

<div class="seguimiento-summary">
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Historia</span><span class="seguimiento-summary__value">#{{ $registro->id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Cl&iacute;nica</span><span class="seguimiento-summary__value">{{ $registro->clinica?->especialidad }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Controles</span><span class="seguimiento-summary__value">{{ $esterilizaciones->count() }}</span></div>
</div>

@if($esEstudiante && ! $cerrada && $registro->remision_id)
    @canany(['esterilizaciones.crear', 'esterilizaciones.gestionar'])
        <details class="seguimiento-compose">
            <summary>+ Solicitar un control de esterilizaci&oacute;n</summary>
            <form action="{{ route('esterilizacion.store') }}" method="POST" class="form-grid" data-seguimiento-form>
                @csrf
                <input type="hidden" name="remision_id" value="{{ $registro->remision_id }}">
                <input type="hidden" name="estudiante_id" value="{{ auth()->id() }}">
                <input type="hidden" name="docente_id" value="{{ $registro->docente_id }}">
                <input type="hidden" name="clinica_id" value="{{ $registro->clinica_id }}">
                <input type="hidden" name="habilitada" value="1">
                <div class="form-group">
                    <label for="seguimiento-sobres-{{ $registro->id }}">Sobres</label>
                    <input id="seguimiento-sobres-{{ $registro->id }}" name="cantidad_sobres" type="number" min="0" max="1000" step="1" value="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="seguimiento-paquetes-{{ $registro->id }}">Paquetes</label>
                    <input id="seguimiento-paquetes-{{ $registro->id }}" name="cantidad_paquetes" type="number" min="0" max="1000" step="1" value="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="seguimiento-material-{{ $registro->id }}">Descripci&oacute;n del material</label>
                    <input id="seguimiento-material-{{ $registro->id }}" name="descripcion" maxlength="200" class="form-control">
                </div>
                <div><button type="submit" class="btn btn-primary">Enviar solicitud de control</button></div>
            </form>
        </details>
    @endcanany
@endif

<div class="table-wrap seguimiento-table-wrap">
    <table class="seguimiento-table">
        <thead>
            <tr>
                <th>Control</th>
                <th>Material</th>
                <th>Correspondencia</th>
                <th>Estado</th>
                <th>Entrega</th>
                <th>Recojo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($esterilizaciones as $control)
                <tr>
                    <td><span class="seguimiento-table__primary">#{{ $control->id }}</span></td>
                    <td>
                        <span class="seguimiento-table__primary">{{ $control->cantidad_sobres }} sobres &middot; {{ $control->cantidad_paquetes }} paquetes</span>
                        <span class="seguimiento-table__secondary">{{ $control->descripcion ?: 'Sin descripcion' }}</span>
                    </td>
                    <td>{{ $control->remision_id ? 'De este paciente' : 'Material general' }}</td>
                    <td><span class="badge {{ $control->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $control->estado_proceso }}</span></td>
                    <td>
                        <span class="seguimiento-table__primary">{{ $control->fecha_entrega?->format('d/m/Y') ?? 'Pendiente' }}</span>
                        @if($control->hora_entrega)<span class="seguimiento-table__secondary">{{ $control->hora_entrega }}</span>@endif
                    </td>
                    <td>
                        <span class="seguimiento-table__primary">{{ $control->fecha_recojo?->format('d/m/Y') ?? 'Pendiente' }}</span>
                        @if($control->hora_recojo)<span class="seguimiento-table__secondary">{{ $control->hora_recojo }}</span>@endif
                    </td>
                    <td>
                        <div class="seguimiento-actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('esterilizacion.control', $control->id) }}">Abrir control</a>
                            @if(! $control->autorizado && ! $control->fecha_entrega && ! $control->fecha_recojo)
                                @canany(['esterilizaciones.eliminar', 'esterilizaciones.gestionar'])
                                    <form action="{{ route('esterilizacion.destroy', $control->id) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Eliminar este control de esterilizaci&oacute;n?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                @endcanany
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="seguimiento-empty-cell">No hay controles de esterilizaci&oacute;n visibles para esta historia.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
