@extends('layouts.app')
@section('title', 'Control de esterilización')
@section('content')
@php
    $activo = $registro->habilitada && !$registro->fecha_recojo;
    $preparaciones = $registro->preparaciones ?? [];
    $necesitaPreparacion = !$registro->fecha_preparacion || ($cicloActual?->estado === 'rechazado' && count($preparaciones) <= $cicloActual->version_preparacion);
    $puedeRegistrarCiclo = $registro->fecha_preparacion && (!$cicloActual || ($cicloActual->estado === 'rechazado' && !$necesitaPreparacion));
    $estadosCiclo = ['registrado' => 'Pendiente de revisión', 'pendiente_resultados' => 'Pendiente de resultados', 'revisado' => 'Controles conformes', 'rechazado' => 'Rechazado', 'liberado' => 'Liberado'];
    $resultadosBiologicos = ['pendiente' => 'Pendiente', 'conforme' => 'Conforme', 'no_conforme' => 'No conforme', 'no_aplica' => 'No aplica según protocolo'];
@endphp

<div class="card">
    <div class="card-header"><h2>Control de esterilización #{{ $registro->id }}</h2><a class="btn btn-secondary" href="{{ route('esterilizacion.index') }}">Volver al listado</a></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p><span class="badge badge-info">{{ $registro->estado_proceso }}</span></p>
        <div class="form-grid">
            <div><strong>Estudiante:</strong> {{ $registro->estudiante?->nombre_completo ?? 'Sin estudiante' }}</div>
            <div><strong>Clínica:</strong> {{ $registro->clinica?->especialidad }}</div>
            <div><strong>Periodo:</strong> {{ $registro->periodo?->nombre }}</div>
            <div><strong>Registrado por:</strong> {{ $registro->usuario?->nombre_completo }}</div>
            <div><strong>Solicitado:</strong> {{ $registro->cantidad_sobres }} sobres y {{ $registro->cantidad_paquetes }} paquetes</div>
            <div><strong>Recibido:</strong> {{ $registro->sobres_recibidos ?? 'Sin confirmar' }} sobres y {{ $registro->paquetes_recibidos ?? 'Sin confirmar' }} paquetes</div>
        </div>
        @if($registro->descripcion)<p><strong>Descripción:</strong> {{ $registro->descripcion }}</p>@endif
        @if($registro->info)<p><strong>Información:</strong> {{ $registro->info }}</p>@endif
        <p>Solicitud → Firma docente → Recepción → Preparación → Ciclo → Revisión → Liberación → Recojo</p>
        @if(!$registro->habilitada)<div class="alert alert-warning">Este control está deshabilitado y no admite movimientos.</div>@endif
        @if($registro->fecha_recojo && !$cicloActual)<div class="alert alert-info">Registro histórico de recojo. No tiene un ciclo documentado en este módulo.</div>@endif
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>1. Autorización docente</h2></div>
    <div class="card-body">
        <p>{{ $registro->autorizado ? 'Solicitud autorizada' : 'Pendiente de firma del docente o jefe de cátedra' }} {{ $registro->fecha_autorizacion?->format('d/m/Y H:i') }}</p>
        @if($activo && !$registro->autorizado && $puedeAutorizar)
            @canany(['esterilizaciones.autorizar', 'esterilizaciones.gestionar'])
            <form method="POST" action="{{ route('esterilizacion.autorizar', $registro->id) }}">@csrf
                <x-firma-digital nombre="firma_docente" /><button class="btn btn-primary">Firmar y autorizar</button>
            </form>
            @endcanany
        @elseif($registro->firma_docente)
            <img src="{{ $registro->firma_docente }}" alt="Firma del docente" style="max-width:300px;background:white">
        @endif
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>2. Recepción e identificación del material</h2></div>
    <div class="card-body">
        <p>Recepción: {{ $registro->fecha_entrega?->format('d/m/Y') ?? 'Pendiente' }} {{ $registro->hora_entrega }} · {{ $registro->receptor?->nombre_completo }}</p>
        @if($registro->observacion_recepcion)<p>{{ $registro->observacion_recepcion }}</p>@endif
        @if($registro->codigos_material)
            <p>Identifique cada sobre o paquete con su código; vincule su etiqueta al equipo, carga y fecha del ciclo.</p>
            <div class="actions">@foreach($registro->codigos_material as $codigo)<code>{{ $codigo }}</code>@endforeach</div>
        @endif
        @if($puedeGestionar && $activo && $registro->autorizado && !$registro->fecha_entrega)
            <form method="POST" action="{{ route('esterilizacion.movimiento', $registro->id) }}" class="form-grid">@csrf
                <input type="hidden" name="accion" value="recibir">
                <div class="form-group"><label for="sobres-recibidos">Sobres recibidos</label><input id="sobres-recibidos" class="form-control" type="number" name="sobres_recibidos" min="0" max="{{ $registro->cantidad_sobres }}" value="{{ old('sobres_recibidos', $registro->cantidad_sobres) }}" required></div>
                <div class="form-group"><label for="paquetes-recibidos">Paquetes recibidos</label><input id="paquetes-recibidos" class="form-control" type="number" name="paquetes_recibidos" min="0" max="{{ $registro->cantidad_paquetes }}" value="{{ old('paquetes_recibidos', $registro->cantidad_paquetes) }}" required></div>
                <div class="form-group" style="grid-column:1/-1"><label for="observacion-recepcion">Observaciones de recepción</label><textarea id="observacion-recepcion" class="form-control" name="observacion_recepcion" maxlength="1000">{{ old('observacion_recepcion') }}</textarea></div>
                <div><button class="btn btn-primary">Recibir material</button></div>
            </form>
        @endif
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>3. Limpieza, inspección y empaque</h2></div>
    <div class="card-body">
        <p>Última preparación: {{ $registro->fecha_preparacion?->format('d/m/Y H:i') ?? 'Pendiente' }} · {{ $registro->preparador?->nombre_completo }}</p>
        @if($puedeGestionar && $activo && $registro->fecha_entrega && $necesitaPreparacion)
            @if($cicloActual?->estado === 'rechazado')<div class="alert alert-warning">El último ciclo fue rechazado. Registre nuevamente la preparación antes de un nuevo ciclo.</div>@endif
            <form method="POST" action="{{ route('esterilizacion.preparar', $registro->id) }}" class="form-grid">@csrf
                @if(!$registro->codigos_material)
                    <p style="grid-column:1/-1">Confirme las cantidades de esta recepción antigua para identificar el material.</p>
                    <div class="form-group"><label for="sobres-historicos">Sobres recibidos</label><input id="sobres-historicos" class="form-control" type="number" name="sobres_recibidos" min="0" max="{{ $registro->cantidad_sobres }}" value="{{ old('sobres_recibidos', $registro->cantidad_sobres) }}" required></div>
                    <div class="form-group"><label for="paquetes-historicos">Paquetes recibidos</label><input id="paquetes-historicos" class="form-control" type="number" name="paquetes_recibidos" min="0" max="{{ $registro->cantidad_paquetes }}" value="{{ old('paquetes_recibidos', $registro->cantidad_paquetes) }}" required></div>
                @endif
                <label><input type="checkbox" name="limpieza_confirmada" value="1" required> Limpieza realizada según protocolo</label>
                <label><input type="checkbox" name="inspeccion_confirmada" value="1" required> Instrumental inspeccionado</label>
                <label><input type="checkbox" name="empaque_confirmado" value="1" required> Material empacado e identificado</label>
                <div class="form-group" style="grid-column:1/-1"><label for="observacion-preparacion">Observaciones de preparación</label><textarea id="observacion-preparacion" class="form-control" name="observacion_preparacion" maxlength="1000">{{ old('observacion_preparacion') }}</textarea></div>
                <div><button class="btn btn-primary">Registrar preparación</button></div>
            </form>
        @endif
        @if($preparaciones)<ul>@foreach($preparaciones as $preparacion)<li>{{ $preparacion['fecha'] }} · {{ $preparacion['responsable'] }} @if($preparacion['observacion'])· {{ $preparacion['observacion'] }}@endif</li>@endforeach</ul>@endif
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>4. Ciclo de esterilización</h2></div>
    <div class="card-body">
        @if($puedeGestionar && $activo && $puedeRegistrarCiclo)
            <p>Registre los valores reales según el protocolo y las instrucciones del fabricante. El ciclo incluye todo el material recibido de este control.</p>
            <form method="POST" action="{{ route('esterilizacion.ciclos.store', $registro->id) }}" class="form-grid">@csrf
                <div class="form-group"><label for="equipo">Equipo / autoclave</label><input id="equipo" class="form-control" name="equipo" maxlength="100" value="{{ old('equipo') }}" required></div>
                <div class="form-group"><label for="numero-carga">Número de carga</label><input id="numero-carga" class="form-control" name="numero_carga" maxlength="100" value="{{ old('numero_carga') }}" required></div>
                <div class="form-group" style="grid-column:1/-1"><label for="protocolo">Protocolo aplicado / referencia del fabricante</label><input id="protocolo" class="form-control" name="protocolo" maxlength="200" value="{{ old('protocolo') }}" required></div>
                <div class="form-group"><label for="inicio">Inicio del ciclo</label><input id="inicio" class="form-control" type="datetime-local" step="1" name="inicio" min="{{ $registro->fecha_preparacion?->format('Y-m-d\TH:i:s') }}" value="{{ old('inicio') }}" required></div>
                <div class="form-group"><label for="fin">Fin del ciclo</label><input id="fin" class="form-control" type="datetime-local" step="1" name="fin" value="{{ old('fin') }}" required></div>
                <div class="form-group"><label for="temperatura">Temperatura registrada (°C)</label><input id="temperatura" class="form-control" type="number" step="0.01" min="0.01" max="500" name="temperatura" value="{{ old('temperatura') }}" required></div>
                <div class="form-group"><label for="presion">Presión registrada (bar)</label><input id="presion" class="form-control" type="number" step="0.001" min="0" max="1000" name="presion" value="{{ old('presion') }}" required></div>
                <div class="form-group"><label for="exposicion">Tiempo de exposición (minutos)</label><input id="exposicion" class="form-control" type="number" step="0.01" min="0.01" max="10080" name="minutos_exposicion" value="{{ old('minutos_exposicion') }}" required></div>
                <div><button class="btn btn-primary">Registrar ciclo realizado</button></div>
            </form>
        @elseif(!$cicloActual)<p>Registre la recepción y preparación para documentar el ciclo.</p>@endif
        @foreach($registro->ciclos->sortByDesc('id') as $ciclo)
            <div style="margin-top:16px">
                <h3>Ciclo #{{ $ciclo->id }} · {{ $estadosCiclo[$ciclo->estado] ?? $ciclo->estado }}</h3>
                <p><strong>Equipo:</strong> {{ $ciclo->equipo }} · <strong>Carga:</strong> {{ $ciclo->numero_carga }} · <strong>Operador:</strong> {{ $ciclo->operador?->nombre_completo }}</p>
                <p><strong>Protocolo:</strong> {{ $ciclo->protocolo }}</p>
                <p>{{ $ciclo->inicio->format('d/m/Y H:i:s') }} → {{ $ciclo->fin->format('d/m/Y H:i:s') }} · {{ $ciclo->temperatura }} °C · {{ $ciclo->presion }} bar · {{ $ciclo->minutos_exposicion }} minutos de exposición</p>
                <p><strong>Material incluido:</strong> {{ implode(', ', $ciclo->codigos_material) }}</p>
                <p>Control físico: {{ $ciclo->control_fisico === null ? 'Pendiente' : ($ciclo->control_fisico ? 'Conforme' : 'No conforme') }} · Control químico: {{ $ciclo->control_quimico === null ? 'Pendiente' : ($ciclo->control_quimico ? 'Conforme' : 'No conforme') }} · Control biológico: {{ $resultadosBiologicos[$ciclo->control_biologico] ?? $ciclo->control_biologico }}</p>
                @if($ciclo->justificacion_biologico)<p><strong>Justificación del control biológico:</strong> {{ $ciclo->justificacion_biologico }}</p>@endif
                @if($ciclo->observaciones)<p><strong>Observaciones:</strong> {{ $ciclo->observaciones }}</p>@endif
                @if($ciclo->fecha_revision)<p>Revisado: {{ $ciclo->fecha_revision->format('d/m/Y H:i') }} · {{ $ciclo->revisor?->nombre_completo }}</p>@endif
                @if($ciclo->fecha_liberacion)<p>Liberado: {{ $ciclo->fecha_liberacion->format('d/m/Y H:i') }} · {{ $ciclo->responsableLiberacion?->nombre_completo }}</p>@endif
                @if($ciclo->revisiones)
                    <details><summary>Historial de revisiones</summary><ul>@foreach($ciclo->revisiones as $revision)
                        <li>{{ $revision['fecha'] }} · {{ $revision['responsable'] }} · {{ $estadosCiclo[$revision['estado']] ?? $revision['estado'] }} · Físico: {{ $revision['control_fisico'] ? 'Conforme' : 'No conforme' }} · Químico: {{ $revision['control_quimico'] ? 'Conforme' : 'No conforme' }} · Biológico: {{ $resultadosBiologicos[$revision['control_biologico']] }} @if($revision['observaciones'] ?? null)· {{ $revision['observaciones'] }}@endif @if($revision['justificacion_biologico'] ?? null)· {{ $revision['justificacion_biologico'] }}@endif</li>
                    @endforeach</ul></details>
                @endif
            </div>
        @endforeach
    </div>
</div>

@if($cicloActual)
<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>5. Revisión y liberación del material</h2></div>
    <div class="card-body">
        @if($puedeGestionar && $activo && in_array($cicloActual->estado, ['registrado', 'pendiente_resultados'], true))
            <form method="POST" action="{{ route('esterilizacion.ciclos.revisar', ['esterilizacion' => $registro->id, 'ciclo' => $cicloActual->id]) }}" class="form-grid">@csrf
                <div class="form-group"><label for="control-fisico">Control físico: parámetros del protocolo</label><select id="control-fisico" class="form-control" name="control_fisico" required><option value="">Seleccione...</option>@foreach([1 => 'Conforme', 0 => 'No conforme'] as $valor => $texto)<option value="{{ $valor }}" @selected((string) old('control_fisico', $cicloActual->control_fisico) === (string) $valor)>{{ $texto }}</option>@endforeach</select></div>
                <div class="form-group"><label for="control-quimico">Control químico de los paquetes</label><select id="control-quimico" class="form-control" name="control_quimico" required><option value="">Seleccione...</option>@foreach([1 => 'Conforme', 0 => 'No conforme'] as $valor => $texto)<option value="{{ $valor }}" @selected((string) old('control_quimico', $cicloActual->control_quimico) === (string) $valor)>{{ $texto }}</option>@endforeach</select></div>
                <div class="form-group"><label for="control-biologico">Control biológico según protocolo</label><select id="control-biologico" class="form-control" name="control_biologico" required>@foreach($resultadosBiologicos as $valor => $texto)<option value="{{ $valor }}" @selected(old('control_biologico', $cicloActual->control_biologico) === $valor)>{{ $texto }}</option>@endforeach</select></div>
                <div class="form-group"><label for="justificacion-biologico">Justificación obligatoria si no aplica</label><textarea id="justificacion-biologico" class="form-control" name="justificacion_biologico" maxlength="1000">{{ old('justificacion_biologico', $cicloActual->justificacion_biologico) }}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label for="observaciones">Resultados / referencia de controles y motivo si no son conformes</label><textarea id="observaciones" class="form-control" name="observaciones" maxlength="1000">{{ old('observaciones', $cicloActual->observaciones) }}</textarea></div>
                <div><button class="btn btn-primary">Registrar revisión de controles</button></div>
            </form>
        @endif
        @if($cicloActual->estado === 'rechazado')<div class="alert alert-danger">Material bloqueado por controles no conformes. Debe registrar una nueva preparación y un nuevo ciclo.</div>@endif
        @if($cicloActual->estado === 'pendiente_resultados')<div class="alert alert-warning">Faltan resultados. El material permanece bloqueado para liberación y recojo.</div>@endif
        @if($puedeGestionar && $activo && $cicloActual->estado === 'revisado')
            <form method="POST" action="{{ route('esterilizacion.ciclos.liberar', ['esterilizacion' => $registro->id, 'ciclo' => $cicloActual->id]) }}" class="form-grid">@csrf
                <label><input type="checkbox" name="empaque_integro" value="1" required> Verifiqué que los empaques están secos e íntegros</label>
                <label><input type="checkbox" name="confirmar_liberacion" value="1" required> Confirmo la revisión de controles y la liberación según el protocolo aplicado</label>
                <div><button class="btn btn-primary">Liberar material para recojo</button></div>
            </form>
        @endif
        @if($cicloActual->estado === 'liberado')<div class="alert alert-success">Material liberado el {{ $cicloActual->fecha_liberacion?->format('d/m/Y H:i') }} por {{ $cicloActual->responsableLiberacion?->nombre_completo }}.</div>@endif
    </div>
</div>
@endif

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>6. Recojo del material</h2></div>
    <div class="card-body">
        <p>Recojo: {{ $registro->fecha_recojo?->format('d/m/Y') ?? 'Pendiente' }} {{ $registro->hora_recojo }}</p>
        @if($registro->fecha_recojo)
            <p>Entregado por: {{ $registro->entregador?->nombre_completo ?? 'Sin registrar' }} · Recogido por: {{ $registro->recogedor?->nombre_completo ?? 'Sin registrar' }}</p>
        @elseif($puedeGestionar && $activo && $cicloActual?->estado === 'liberado')
            <form method="POST" action="{{ route('esterilizacion.movimiento', $registro->id) }}">@csrf
                <input type="hidden" name="accion" value="entregar">
                <label><input type="checkbox" name="confirmar_recojo" value="1" required> Confirmo que {{ $registro->estudiante?->nombre_completo }} recibió todo el material liberado de este control</label>
                <button class="btn btn-primary">Registrar recojo</button>
            </form>
        @else<p>El recojo se habilita después de revisar y liberar el material.</p>@endif
    </div>
</div>
@endsection
