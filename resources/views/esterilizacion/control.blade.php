@extends('layouts.app')
@section('title', 'Control de esterilización')
@section('content')
<div class="card"><div class="card-header"><h2>Control de esterilización #{{ $registro->id }}</h2><a class="btn btn-secondary" href="{{ route('esterilizacion.index') }}">Volver</a></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p>Estudiante: {{ $registro->estudiante?->nombre_completo }} · {{ $registro->clinica?->especialidad }}</p>
<p>Sobres: {{ $registro->cantidad_sobres }} · Paquetes: {{ $registro->cantidad_paquetes }}</p>
<p>Autorización docente: {{ $registro->autorizado ? 'Firmada' : 'Pendiente' }}</p>
@if(!$registro->autorizado && auth()->id() === $registro->docente_id)
<form method="POST" action="{{ route('esterilizacion.autorizar', $registro->id) }}">@csrf
<x-firma-digital nombre="firma_docente" />
<button class="btn btn-primary">Firmar y autorizar</button>
</form>
@elseif($registro->firma_docente)
<img src="{{ $registro->firma_docente }}" alt="Firma del docente" style="max-width:300px;background:white">
@endif
<p>Entrega al servicio: {{ $registro->fecha_entrega?->format('d/m/Y') ?? 'Pendiente' }} {{ $registro->hora_entrega }}</p>
<p>Recojo por estudiante: {{ $registro->fecha_recojo?->format('d/m/Y') ?? 'Pendiente' }} {{ $registro->hora_recojo }}</p>
@if(auth()->user()->hasAnyRole(['admin','subadmin','esterilizacion']) && $registro->autorizado && !$registro->fecha_recojo)
<form method="POST" action="{{ route('esterilizacion.movimiento', $registro->id) }}">@csrf
<input type="hidden" name="accion" value="{{ $registro->fecha_entrega ? 'entregar' : 'recibir' }}">
<button class="btn btn-primary">{{ $registro->fecha_entrega ? 'Registrar recojo' : 'Recibir material' }}</button>
</form>
@endif
</div></div>
@endsection
