@extends('layouts.app')
@section('title', 'Mi Historia Clínica')
@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">Historia #{{ $registro->id }} · {{ $registro->paciente?->nombre_completo }}</div>
    <div class="toolbar-actions"><a class="btn btn-secondary" href="{{ route('estudiante.historias') }}">Mis Historias Clínicas</a></div>
</div>
<div data-estudiante-feedback role="status" aria-live="polite"></div>
<div data-estudiante-detalle data-url="{{ route('estudiante.historia', $registro->id) }}">
    @fragment('detalle-estudiante')
    <div class="card">
        <div class="card-header">
            <h2>{{ $registro->paciente?->nombre_completo }}</h2><span class="badge {{ $registro->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $cerrada ? 'Historia cerrada' : ($registro->autorizado ? 'Historia autorizada' : 'Pendiente de revisión docente') }}</span>
        </div>
        <div class="card-body">
            <p>Ficha #{{ $registro->admision_id }} · CI: {{ $registro->paciente?->ci }} · Edad: {{ $registro->paciente?->edad ?? '—' }} · {{ $registro->clinica?->especialidad }}</p>
            <p>Docente asignado: <strong>{{ $registro->docente?->nombre_completo }}</strong></p>
            <p>Motivo de consulta: {{ $registro->admision?->motivo_consulta ?: 'Sin especificar' }}</p>
            <div class="actions">
                <a class="btn btn-sm btn-secondary" href="#archivos">Documentos</a><a class="btn btn-sm btn-secondary" href="#tratamientos">Tratamientos y avances</a><a class="btn btn-sm btn-secondary" href="#pagos">Caja</a>
                @canany(['solicitudes-insumos.ver', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])<a class="btn btn-sm btn-secondary" href="#insumos">Insumos</a>@endcanany
                @canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])<a class="btn btn-sm btn-secondary" href="#esterilizacion">Esterilización</a>@endcanany
                @canany(['admisiones.ver', 'admisiones.gestionar'])<a class="btn btn-sm btn-secondary" href="{{ route('admisiones.show', $registro->admision_id) }}">Ficha de admisión y odontograma</a>@endcanany
            </div>
            <ol>
                <li>Adjunta la historia y completa la documentación del paciente.</li>
                <li>Espera la revisión de tu docente para proponer tratamientos.</li>
                <li>Envía la propuesta; el docente debe autorizarla y el paciente debe registrar su pago en Caja.</li>
                <li>Registra los avances de atención y espera su autorización docente.</li>
                <li>Solicita los insumos configurados para el tratamiento de tu clínica; el docente autoriza y el encargado aprueba o rechaza la entrega.</li>
                <li>Solicita el control de esterilización y entrega el material cuando el docente lo autorice.</li>
                <li>Finaliza el tratamiento; el docente confirma la culminación.</li>
            </ol>
            @if(!$registro->autorizado && !$cerrada)<div class="alert alert-warning">Tu docente debe revisar y autorizar esta historia antes de iniciar tratamientos. Puedes adjuntar la documentación.</div>@endif
        </div>
    </div>

    <div class="card" id="archivos" style="margin-top:16px">
        <div class="card-header">
            <h2>Historia y documentos adjuntos</h2>
        </div>
        <div class="card-body">
            @forelse($registro->archivos as $archivo)
            <p><a class="btn btn-sm btn-secondary" href="{{ route('historias.archivos.descargar', ['id' => $registro->id, 'archivo' => $archivo->id]) }}" target="_blank" rel="noopener">Ver / descargar {{ $archivo->nombre_original }}</a> · {{ $archivo->created_at?->format('d/m/Y H:i') }}</p>
            @empty
            <p>No has adjuntado documentos a esta historia.</p>
            @endforelse
            @if(!$cerrada)
            @canany(['historias.editar', 'historias.gestionar'])
            <form data-estudiante-form action="{{ route('historias.archivos.subir', $registro->id) }}" method="POST" enctype="multipart/form-data" class="form-grid">
                @csrf
                <div class="form-group"><label for="archivo-historia">Adjuntar PDF, JPG o PNG (máximo 10 MB)</label><input type="file" id="archivo-historia" name="archivo" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required></div>
                <div><button class="btn btn-primary">Subir historia / documento</button></div>
                <p data-form-error role="alert"></p>
            </form>
            @endcanany
            @endif
        </div>
    </div>

    <div class="card" id="tratamientos" style="margin-top:16px">
        <div class="card-header">
            <h2>Tratamientos y avances</h2>
        </div>
        <div class="card-body">
            @if($registro->autorizado && !$cerrada)
            @canany(['atenciones.crear', 'atenciones.gestionar'])
            <form data-estudiante-form action="{{ route('tratamientos-realizados.store') }}" method="POST" class="form-grid">
                @csrf<input type="hidden" name="historia_clinica_id" value="{{ $registro->id }}">
                <div class="form-group"><label for="tratamiento_id">Proponer tratamiento</label><select id="tratamiento_id" name="tratamiento_id" class="form-control" required>
                        <option value="">Seleccione un tratamiento de esta especialidad</option>@foreach($catalogo as $tratamiento)<option value="{{ $tratamiento->id }}">{{ $tratamiento->nombre }} · {{ $tratamiento->costo }} Bs</option>@endforeach
                    </select></div>
                <div class="form-group"><label for="pieza_dental">Pieza dental</label><input id="pieza_dental" name="pieza_dental" class="form-control" maxlength="20"></div>
                <div class="form-group"><label for="observacion">Observación</label><textarea id="observacion" name="observacion" class="form-control" maxlength="2000"></textarea></div>
                <div><button class="btn btn-primary">Enviar propuesta al docente</button></div>
                <p data-form-error role="alert"></p>
            </form>
            @endcanany
            @endif
            @forelse($registro->tratamientosRealizados as $tratamiento)
            <div style="padding:16px 0;border-top:1px solid var(--line)">
                <h3>#{{ $tratamiento->id }} · {{ $tratamiento->tratamiento?->nombre }}</h3>
                <p><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $tratamiento->estado)) }}</span> · Pieza {{ $tratamiento->pieza_dental ?: 'Sin especificar' }} · {{ $tratamiento->costo_cobrado }} Bs</p>
                @if(in_array($tratamiento->estado, ['pendiente', 'propuesta']))<p>Espera la autorización de la propuesta por tu docente. Mientras esté pendiente puedes corregirla o eliminarla.</p>@endif
                @if($tratamiento->estado === 'rechazado')
                <div class="alert alert-danger">
                    <strong>Propuesta rechazada por el docente.</strong>
                    <p>{{ $tratamiento->observacion }}</p>
                </div>
                @endif
                @if($tratamiento->estado === 'cancelado')<p>Esta solicitud fue cancelada y se conserva en el historial.</p>@endif
                @if(!$cerrada && in_array($tratamiento->estado, ['pendiente', 'propuesta', 'rechazado']))
                @canany(['atenciones.editar', 'atenciones.gestionar'])
                <details @if($tratamiento->estado === 'rechazado') open @endif>
                    <summary>{{ $tratamiento->estado === 'rechazado' ? 'Corregir y reenviar propuesta' : 'Editar propuesta' }}</summary>
                    <form data-estudiante-form action="{{ route('tratamientos-realizados.update', $tratamiento->id) }}" method="POST" class="form-grid">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="tratamiento-{{ $tratamiento->id }}">Tratamiento</label>
                            <select id="tratamiento-{{ $tratamiento->id }}" name="tratamiento_id" class="form-control" required>
                                @foreach($catalogo as $opcion)
                                <option value="{{ $opcion->id }}" @selected((int) $tratamiento->tratamiento_id === (int) $opcion->id)>{{ $opcion->nombre }} · {{ $opcion->costo }} Bs</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label for="pieza-{{ $tratamiento->id }}">Pieza dental</label><input id="pieza-{{ $tratamiento->id }}" name="pieza_dental" value="{{ $tratamiento->pieza_dental }}" class="form-control" maxlength="20"></div>
                        <div class="form-group"><label for="observacion-{{ $tratamiento->id }}">Observación</label><textarea id="observacion-{{ $tratamiento->id }}" name="observacion" class="form-control" maxlength="2000">{{ $tratamiento->observacion }}</textarea></div>
                        <div><button class="btn btn-primary">{{ $tratamiento->estado === 'rechazado' ? 'Corregir y reenviar' : 'Guardar cambios' }}</button></div>
                        <p data-form-error role="alert"></p>
                    </form>
                </details>
                <form data-estudiante-form data-confirm="¿Eliminar definitivamente esta propuesta? Esta acción no se puede deshacer." action="{{ route('tratamientos-realizados.destroy', $tratamiento->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger">Eliminar propuesta</button>
                    <p data-form-error role="alert"></p>
                </form>
                @endcanany
                @endif
                @if(in_array($tratamiento->estado, ['autorizado', 'en_proceso']) && !$tratamiento->tiene_pago)<p>Falta un pago válido registrado por Caja antes de registrar avances.</p>@endif
                @if(!$cerrada && $tratamiento->tiene_pago && in_array($tratamiento->estado, ['autorizado', 'en_proceso']))
                @canany(['historias.editar', 'historias.gestionar'])
                <form data-estudiante-form action="{{ route('sesiones-atencion.store', $tratamiento->id) }}" method="POST" class="form-grid">
                    @csrf
                    <div class="form-group"><label for="fecha-{{ $tratamiento->id }}">Fecha de atención</label><input id="fecha-{{ $tratamiento->id }}" name="fecha" type="date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
                    <div class="form-group"><label for="avance-{{ $tratamiento->id }}">Avance realizado</label><textarea id="avance-{{ $tratamiento->id }}" name="descripcion" class="form-control" required maxlength="5000"></textarea></div>
                    <div><button class="btn btn-primary">Registrar avance</button></div>
                    <p data-form-error role="alert"></p>
                </form>
                @endcanany
                @endif
                @foreach($tratamiento->sesionesAtencion as $sesion)
                <div style="padding:12px 0">
                    <strong>Avance #{{ $sesion->id }} · {{ $sesion->fecha?->format('d/m/Y') }}</strong>
                    <p>{{ $sesion->descripcion }}</p>
                    <span class="badge {{ $sesion->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $sesion->autorizado ? 'Autorizado por el docente' : 'Pendiente de revisión docente' }}</span>
                    @if($sesion->observacion_docente)<p>Observación docente: {{ $sesion->observacion_docente }}</p>@endif
                    @if(!$cerrada && $sesion->autorizado && in_array($tratamiento->estado, ['autorizado', 'en_proceso']))
                    @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
                    <button type="button" class="btn btn-sm btn-primary" data-solicitar-insumos data-clinica="{{ $registro->clinica_id }}" data-tratamiento="{{ $tratamiento->id }}" data-sesion="{{ $sesion->id }}" data-nombre="{{ $tratamiento->tratamiento?->nombre }}" data-pieza="{{ $tratamiento->pieza_dental }}">Solicitar insumos para este avance</button>
                    @endcanany
                    @endif
                </div>
                @endforeach
                @if(!$cerrada && $tratamiento->estado === 'en_proceso')
                @canany(['atenciones.editar', 'atenciones.gestionar'])
                <form data-estudiante-form action="{{ route('tratamientos-realizados.finalizar', $tratamiento->id) }}" method="POST">@csrf<button class="btn btn-secondary">Finalizar tratamiento</button>
                    <p data-form-error role="alert"></p>
                </form>
                @endcanany
                @endif
                @if($tratamiento->estado === 'finalizado')<p>Finalizado por el estudiante. Pendiente de confirmación de culminación por el docente.</p>@endif
            </div>
            @empty
            <p>Este paciente todavía no tiene tratamientos propuestos.</p>
            @endforelse
        </div>
    </div>

    <div class="card" id="pagos" style="margin-top:16px">
        <div class="card-header">
            <h2>Caja · Pagos de los tratamientos</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Recibo</th>
                        <th>Descripción</th>
                        <th>Costo total</th>
                        <th>Cancelado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $pago)<tr>
                        <td>{{ $pago->recibo ?: 'Sin recibo' }}</td>
                        <td>{{ $pago->descripcion }}</td>
                        <td>{{ $pago->costo_total }} Bs</td>
                        <td>{{ $pago->cancelado }} Bs</td>
                        <td>{{ number_format($pago->saldo, 2) }} Bs</td>
                        <td>{{ ucfirst($pago->estado_pago) }}</td>
                    </tr>@empty<tr>
                        <td colspan="6">No hay pagos registrados para los tratamientos de esta historia.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>

    @canany(['solicitudes-insumos.ver', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
    <div class="card" id="insumos" style="margin-top:16px">
        <div class="card-header">
            <h2>Insumos de este paciente</h2>
        </div>
        <div class="card-body">
            <p>Selecciona una de las clínicas asignadas para este paciente y luego el tratamiento para ver sus insumos. Puedes solicitar cuantas veces necesites, incluso si tienes solicitudes pendientes. El docente de la clínica seleccionada autoriza la solicitud y el encargado de insumos aprueba o rechaza antes de entregar.</p>
            @if(!$cerrada) @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])<button class="btn btn-primary" type="button" data-solicitar-designado data-historia="{{ $registro->id }}">Solicitar insumos por tratamiento</button>@endcanany @endif
        </div>
        @forelse($solicitudes as $solicitud)
        <div class="card-body">
            <h3>Solicitud #{{ $solicitud->id }} · {{ $solicitud->tratamiento?->nombre ?? $solicitud->tratamientoRealizado?->tratamiento?->nombre }} @if($solicitud->sesion_atencion_id) · Avance #{{ $solicitud->sesion_atencion_id }} @endif</h3>
            <p><span class="badge badge-info">{{ ucfirst($solicitud->estado) }}</span> @if($solicitud->motivo_rechazo) {{ $solicitud->motivo_rechazo }} @endif</p>
            @if(!$cerrada)
            @canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
            <button class="btn btn-sm btn-primary" type="button" data-solicitar-designado data-historia="{{ $registro->id }}" data-tratamiento="{{ $solicitud->tratamiento_id }}">Solicitar nuevamente</button>
            @endcanany
            @endif
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Solicitado</th>
                            <th>Entregado</th>
                            <th>Utilizado</th>
                            <th>Disponible para usar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($solicitud->detalles as $detalle)<tr>
                            <td>{{ $detalle->insumo?->nombre }}</td>
                            <td>{{ $detalle->cantidad_solicitada }}</td>
                            <td>{{ $detalle->cantidad_entregada }}</td>
                            <td>{{ $detalle->consumos->sum('cantidad_usada') }}</td>
                            <td>{{ number_format((float) $detalle->cantidad_entregada - $detalle->consumos->sum('cantidad_usada'), 2) }}</td>
                        </tr>@endforeach
                    </tbody>
                </table>
            </div>
            @if(!$cerrada && $solicitud->sesion_atencion_id && in_array($solicitud->estado, ['parcial', 'entregado']))
            @canany(['solicitudes-insumos.consumir', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
            <form data-estudiante-form data-consumo action="{{ route('solicitudes-insumos.consumir', $solicitud->id) }}" method="POST" class="form-grid">
                @csrf
                @foreach($solicitud->detalles as $detalle)
                <div class="form-group"><label for="consumo-{{ $detalle->id }}">Utilizado: {{ $detalle->insumo?->nombre }}</label><input id="consumo-{{ $detalle->id }}" data-detalle="{{ $detalle->id }}" type="number" class="form-control" min="0" step="0.01" max="{{ (float) $detalle->cantidad_entregada - $detalle->consumos->sum('cantidad_usada') }}" value="0"></div>
                @endforeach
                <div><button class="btn btn-primary">Guardar cantidades utilizadas</button></div>
                <p data-form-error role="alert"></p>
            </form>
            @endcanany
            @endif
        </div>
        @empty
        <div class="card-body">
            <p>Este paciente todavía no tiene solicitudes de insumos.</p>
        </div>
        @endforelse
    </div>
    @endcanany

    @canany(['esterilizaciones.ver', 'esterilizaciones.gestionar'])
    <div class="card" id="esterilizacion" style="margin-top:16px">
        <div class="card-header">
            <h2>Esterilización</h2>
        </div>
        <div class="card-body">
            <p>Controles del paciente y controles generales de tu material en esta especialidad. El docente firma la solicitud; el servicio registra la recepción, preparación, ciclo y controles. Puedes recoger el material cuando esté liberado.</p>
            @if(!$cerrada && $registro->remision_id)
            @canany(['esterilizaciones.crear', 'esterilizaciones.gestionar'])
            <form data-estudiante-form action="{{ route('esterilizacion.store') }}" method="POST" class="form-grid">
                @csrf
                <input type="hidden" name="remision_id" value="{{ $registro->remision_id }}"><input type="hidden" name="estudiante_id" value="{{ auth()->id() }}"><input type="hidden" name="docente_id" value="{{ $registro->docente_id }}"><input type="hidden" name="clinica_id" value="{{ $registro->clinica_id }}"><input type="hidden" name="usuario_id" value="{{ auth()->id() }}"><input type="hidden" name="habilitada" value="1">
                <div class="form-group"><label for="cantidad_sobres">Sobres</label><input id="cantidad_sobres" name="cantidad_sobres" type="number" min="0" step="1" value="0" class="form-control" required></div>
                <div class="form-group"><label for="cantidad_paquetes">Paquetes</label><input id="cantidad_paquetes" name="cantidad_paquetes" type="number" min="0" step="1" value="0" class="form-control" required></div>
                <div class="form-group"><label for="descripcion-material">Descripción del material</label><input id="descripcion-material" name="descripcion" maxlength="200" class="form-control"></div>
                <div><button class="btn btn-primary">Solicitar control de esterilización</button></div>
                <p data-form-error role="alert"></p>
            </form>
            @endcanany
            @endif
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Material</th>
                        <th>Asociación</th>
                        <th>Autorización</th>
                        <th>Recepción</th>
                        <th>Recojo</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($esterilizaciones as $control)<tr>
                        <td>{{ $control->id }}</td>
                        <td>{{ $control->cantidad_sobres }} sobres · {{ $control->cantidad_paquetes }} paquetes<br>{{ $control->descripcion }}</td>
                        <td>{{ $control->remision_id ? 'De este paciente' : 'Material general de la especialidad' }}</td>
                        <td>{{ $control->estado_proceso }}</td>
                        <td>{{ $control->fecha_entrega?->format('d/m/Y') ?? 'Pendiente' }} {{ $control->hora_entrega }}</td>
                        <td>{{ $control->fecha_recojo?->format('d/m/Y') ?? 'Pendiente' }} {{ $control->hora_recojo }}</td>
                        <td><a class="btn btn-sm btn-secondary" href="{{ route('esterilizacion.control', $control->id) }}">Ver ficha</a></td>
                    </tr>@empty<tr>
                        <td colspan="7">No hay controles de esterilización registrados.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endcanany
    @endfragment
</div>
@canany(['solicitudes-insumos.crear', 'solicitudes-insumos.gestionar', 'insumos.gestionar'])
@include('solicitudes-insumos.partials.modal', ['historiaOrigenId' => $registro->id])
@include('solicitudes-insumos.partials.modal-designado')
@endcanany
@endsection
@push('scripts')
<script src="{{ asset('js/estudiante-pacientes.js') }}" defer></script>
@endpush
