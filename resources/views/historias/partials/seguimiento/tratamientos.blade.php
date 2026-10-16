@php
    $puedeCrearTratamiento = $esEstudiante && $registro->autorizado && ! $cerrada;
@endphp

<div class="seguimiento-summary">
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Ficha</span><span class="seguimiento-summary__value">#{{ $registro->admision_id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Cl&iacute;nica</span><span class="seguimiento-summary__value">{{ $registro->clinica?->especialidad }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Tratamientos</span><span class="seguimiento-summary__value">{{ $registro->tratamientosRealizados->count() }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Historia</span><span class="seguimiento-summary__value">{{ $cerrada ? 'Cerrada' : 'Activa' }}</span></div>
</div>

@if($cerrada)
    <div class="alert alert-warning">La historia est&aacute; cerrada. Los tratamientos permanecen disponibles &uacute;nicamente para consulta.</div>
@endif

@if($puedeCrearTratamiento)
    @canany(['atenciones.crear', 'atenciones.gestionar'])
        <details class="seguimiento-compose">
            <summary>+ Proponer un nuevo tratamiento</summary>
            <form action="{{ route('tratamientos-realizados.store') }}" method="POST" class="form-grid" data-seguimiento-form>
                @csrf
                <input type="hidden" name="historia_clinica_id" value="{{ $registro->id }}">
                <div class="form-group">
                    <label for="seguimiento-tratamiento-nuevo">Tratamiento</label>
                    <select id="seguimiento-tratamiento-nuevo" name="tratamiento_id" class="form-control" required>
                        <option value="">Seleccione un tratamiento de esta especialidad</option>
                        @foreach($catalogo as $opcion)
                            <option value="{{ $opcion->id }}">{{ $opcion->nombre }} &middot; Bs {{ number_format((float) $opcion->costo, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="seguimiento-pieza-nueva">Pieza dental</label>
                    <input id="seguimiento-pieza-nueva" name="pieza_dental" class="form-control" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="seguimiento-observacion-nueva">Observaci&oacute;n</label>
                    <textarea id="seguimiento-observacion-nueva" name="observacion" class="form-control" maxlength="2000"></textarea>
                </div>
                <div><button type="submit" class="btn btn-primary">Enviar propuesta al docente</button></div>
            </form>
        </details>
    @endcanany
@endif

<div class="table-wrap seguimiento-table-wrap">
    <table class="seguimiento-table">
        <thead>
            <tr>
                <th>Tratamiento</th>
                <th>Pieza</th>
                <th>Costo</th>
                <th>Estado</th>
                <th>Autorizaci&oacute;n</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registro->tratamientosRealizados as $tratamiento)
                @php
                    $estadoClase = match ($tratamiento->estado) {
                        'culminado', 'realizado', 'completado' => 'badge-success',
                        'rechazado', 'cancelado' => 'badge-danger',
                        'pendiente', 'propuesta', 'finalizado' => 'badge-warning',
                        default => 'badge-info',
                    };
                @endphp
                <tr>
                    <td>
                        <span class="seguimiento-table__primary">#{{ $tratamiento->id }} &middot; {{ $tratamiento->tratamiento?->nombre }}</span>
                        @if($tratamiento->observacion)<span class="seguimiento-table__secondary">{{ $tratamiento->observacion }}</span>@endif
                    </td>
                    <td>{{ $tratamiento->pieza_dental ?: 'Sin especificar' }}</td>
                    <td class="table-value">Bs {{ number_format((float) $tratamiento->costo_cobrado, 2) }}</td>
                    <td><span class="badge {{ $estadoClase }}">{{ ucfirst(str_replace('_', ' ', $tratamiento->estado)) }}</span></td>
                    <td>
                        @if($tratamiento->estaAutorizado())
                            <span class="seguimiento-table__primary">{{ $tratamiento->autorizador?->nombre_completo }}</span>
                            <span class="seguimiento-table__secondary">{{ $tratamiento->fecha_autorizacion?->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="badge badge-warning">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        <div class="seguimiento-actions">
                            @if($puedeAutorizarTratamientos && in_array($tratamiento->estado, ['pendiente', 'propuesta'], true) && ! $tratamiento->estaAutorizado())
                                <form action="{{ route('tratamientos-realizados.autorizar', $tratamiento->id) }}" method="POST" data-seguimiento-form>
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Autorizar</button>
                                </form>
                            @endif

                            @if($esEstudiante && ! $cerrada && in_array($tratamiento->estado, ['pendiente', 'propuesta', 'rechazado'], true))
                                @canany(['atenciones.editar', 'atenciones.gestionar'])
                                    <form action="{{ route('tratamientos-realizados.destroy', $tratamiento->id) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Eliminar definitivamente esta propuesta? Esta acci&oacute;n no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                @endcanany
                            @endif

                            @if($esEstudiante && ! $cerrada && $tratamiento->estado === 'en_proceso')
                                @canany(['atenciones.editar', 'atenciones.gestionar'])
                                    <form action="{{ route('tratamientos-realizados.finalizar', $tratamiento->id) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Confirmas que deseas finalizar este tratamiento?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">Finalizar</button>
                                    </form>
                                @endcanany
                            @endif

                            @if($puedeAutorizarTratamientos && $tratamiento->estado === 'finalizado')
                                <form action="{{ route('tratamientos-realizados.culminar', $tratamiento->id) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Confirmas la culminaci&oacute;n cl&iacute;nica del tratamiento?">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Confirmar culminaci&oacute;n</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr class="seguimiento-detail-row">
                    <td colspan="6">
                        <details class="seguimiento-details">
                            <summary>Ver avances y opciones del tratamiento</summary>
                            <div class="seguimiento-details__body">
                                @if($puedeAutorizarTratamientos && in_array($tratamiento->estado, ['pendiente', 'propuesta'], true) && ! $tratamiento->estaAutorizado())
                                    <div class="seguimiento-form-panel">
                                        <h4>Rechazar propuesta</h4>
                                        <form action="{{ route('tratamientos-realizados.rechazar', $tratamiento->id) }}" method="POST" class="form-grid" data-seguimiento-form>
                                            @csrf
                                            <div class="form-group">
                                                <label for="seguimiento-rechazo-{{ $tratamiento->id }}">Motivo del rechazo</label>
                                                <input id="seguimiento-rechazo-{{ $tratamiento->id }}" name="motivo" class="form-control" minlength="5" maxlength="2000" required>
                                            </div>
                                            <div><button type="submit" class="btn btn-sm btn-danger">Rechazar propuesta</button></div>
                                        </form>
                                    </div>
                                @endif

                                @if($esEstudiante && ! $cerrada && in_array($tratamiento->estado, ['pendiente', 'propuesta', 'rechazado'], true))
                                    @canany(['atenciones.editar', 'atenciones.gestionar'])
                                        <div class="seguimiento-form-panel">
                                            <h4>{{ $tratamiento->estado === 'rechazado' ? 'Corregir y reenviar propuesta' : 'Editar propuesta' }}</h4>
                                            <form action="{{ route('tratamientos-realizados.update', $tratamiento->id) }}" method="POST" class="form-grid" data-seguimiento-form>
                                                @csrf
                                                @method('PUT')
                                                <div class="form-group">
                                                    <label for="seguimiento-tratamiento-{{ $tratamiento->id }}">Tratamiento</label>
                                                    <select id="seguimiento-tratamiento-{{ $tratamiento->id }}" name="tratamiento_id" class="form-control" required>
                                                        @foreach($catalogo as $opcion)
                                                            <option value="{{ $opcion->id }}" @selected((int) $tratamiento->tratamiento_id === (int) $opcion->id)>{{ $opcion->nombre }} &middot; Bs {{ number_format((float) $opcion->costo, 2) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="seguimiento-pieza-{{ $tratamiento->id }}">Pieza dental</label>
                                                    <input id="seguimiento-pieza-{{ $tratamiento->id }}" name="pieza_dental" value="{{ $tratamiento->pieza_dental }}" class="form-control" maxlength="20">
                                                </div>
                                                <div class="form-group">
                                                    <label for="seguimiento-observacion-{{ $tratamiento->id }}">Observaci&oacute;n</label>
                                                    <textarea id="seguimiento-observacion-{{ $tratamiento->id }}" name="observacion" class="form-control" maxlength="2000">{{ $tratamiento->observacion }}</textarea>
                                                </div>
                                                <div><button type="submit" class="btn btn-primary">Guardar y enviar al docente</button></div>
                                            </form>
                                        </div>
                                    @endcanany
                                @endif

                                @if($esEstudiante && in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true) && ! $tratamiento->tiene_pago)
                                    <div class="alert alert-warning">Debe existir un pago v&aacute;lido en Caja antes de registrar avances.</div>
                                @endif

                                @if($esEstudiante && ! $cerrada && $tratamiento->tiene_pago && in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true))
                                    @canany(['historias.editar', 'historias.gestionar'])
                                        <div class="seguimiento-form-panel">
                                            <h4>Registrar avance</h4>
                                            <form action="{{ route('sesiones-atencion.store', $tratamiento->id) }}" method="POST" class="form-grid" data-seguimiento-form>
                                                @csrf
                                                <div class="form-group">
                                                    <label for="seguimiento-fecha-{{ $tratamiento->id }}">Fecha de atenci&oacute;n</label>
                                                    <input id="seguimiento-fecha-{{ $tratamiento->id }}" name="fecha" type="date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="seguimiento-avance-{{ $tratamiento->id }}">Avance realizado</label>
                                                    <textarea id="seguimiento-avance-{{ $tratamiento->id }}" name="descripcion" class="form-control" maxlength="5000" required></textarea>
                                                </div>
                                                <div><button type="submit" class="btn btn-primary">Registrar avance</button></div>
                                            </form>
                                        </div>
                                    @endcanany
                                @endif

                                <div class="seguimiento-subtable">
                                    <table>
                                        <thead><tr><th>Avance</th><th>Fecha</th><th>Descripci&oacute;n</th><th>Estado</th><th>Acciones</th></tr></thead>
                                        <tbody>
                                            @forelse($tratamiento->sesionesAtencion as $sesion)
                                                <tr>
                                                    <td>#{{ $sesion->id }}</td>
                                                    <td>{{ $sesion->fecha?->format('d/m/Y') }}</td>
                                                    <td>
                                                        {{ $sesion->descripcion }}
                                                        @if($sesion->observacion_docente)<span class="seguimiento-table__secondary">Observaci&oacute;n docente: {{ $sesion->observacion_docente }}</span>@endif
                                                    </td>
                                                    <td><span class="badge {{ $sesion->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $sesion->autorizado ? 'Autorizado' : 'Pendiente' }}</span></td>
                                                    <td>
                                                        <div class="seguimiento-actions">
                                                            @if(! $sesion->autorizado && $puedeAutorizarAvances)
                                                                <form action="{{ route('sesiones-atencion.autorizar', $sesion->id) }}" method="POST" data-seguimiento-form>
                                                                    @csrf
                                                                    <input name="observacion_docente" class="form-control" maxlength="2000" placeholder="Observaci&oacute;n opcional">
                                                                    <button type="submit" class="btn btn-sm btn-primary">Autorizar avance</button>
                                                                </form>
                                                            @endif
                                                            @if($esEstudiante && ! $cerrada && $sesion->autorizado && in_array($tratamiento->estado, ['autorizado', 'en_proceso'], true))
                                                                @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                                                                    <button type="button" class="btn btn-sm btn-primary" data-solicitar-designado data-historia="{{ $registro->id }}" data-tratamiento="{{ $tratamiento->tratamiento_id }}">Solicitar insumos</button>
                                                                @endcanany
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="seguimiento-empty-cell">Todav&iacute;a no se registraron avances.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="seguimiento-empty-cell">Esta historia todav&iacute;a no tiene tratamientos propuestos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
