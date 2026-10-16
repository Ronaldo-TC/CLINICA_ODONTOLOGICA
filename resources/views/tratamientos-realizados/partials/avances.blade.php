<h3>Avances del tratamiento #{{ $registro->id }}</h3>
@if ($registro->observacion)
<p>{{ $registro->observacion }}</p>
@endif
@if (in_array($registro->estado, ['autorizado', 'en_proceso']))
@canany(['historias.editar', 'historias.gestionar'])
<form action="{{ route('sesiones-atencion.store', $registro->id) }}" method="POST" class="form-grid" data-form-atencion>
    @csrf
    <div class="form-group"><label for="avance-fecha-{{ $registro->id }}">Fecha de atención</label><input id="avance-fecha-{{ $registro->id }}" name="fecha" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" required></div>
    <div class="form-group"><label for="avance-descripcion-{{ $registro->id }}">Avance realizado</label><textarea id="avance-descripcion-{{ $registro->id }}" name="descripcion" class="form-control" required maxlength="5000"></textarea></div>
    <div><button class="btn btn-secondary"><x-icono nombre="avance" /> Registrar avance</button></div>
</form>
@endcanany
@endif
@forelse ($registro->sesionesAtencion as $sesion)
<div class="realizado-avance">
    <strong>Avance #{{ $sesion->id }} · {{ $sesion->fecha?->format('d/m/Y') }}</strong>
    <p class="text-muted">Estudiante: {{ $sesion->estudiante?->nombre_completo ?? 'Sin asignar' }} · Docente responsable: {{ $sesion->docente?->nombre_completo ?? 'Sin asignar' }}</p>
    <p>{{ $sesion->descripcion }}</p>
    <span class="badge {{ $sesion->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $sesion->autorizado ? 'Autorizado' : 'Pendiente de revisión' }}</span>
    @if ($sesion->autorizador)
    <p class="text-muted">Revisado por: {{ $sesion->autorizador->nombre_completo }} · {{ $sesion->fecha_autorizacion?->format('d/m/Y H:i') }}</p>
    @endif
    @if ($sesion->observacion_docente)
    <p>Observación docente: {{ $sesion->observacion_docente }}</p>
    @endif
    @if (!$sesion->autorizado)
    @canany(['historias.autorizar', 'historias.gestionar'])
    <form action="{{ route('sesiones-atencion.autorizar', $sesion->id) }}" method="POST" class="form-grid" data-form-atencion>
        @csrf
        <div class="form-group"><label for="avance-observacion-{{ $sesion->id }}">Observación docente</label><input id="avance-observacion-{{ $sesion->id }}" name="observacion_docente" class="form-control" maxlength="2000"></div>
        <div><button class="btn btn-sm btn-primary"><x-icono nombre="autorizar" /> Autorizar avance</button></div>
    </form>
    @endcanany
    @endif
</div>
@empty
<p class="text-muted">Este tratamiento todavía no tiene avances registrados.</p>
@endforelse
