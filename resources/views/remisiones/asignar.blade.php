@extends('layouts.app')
@section('title', 'Asignar atención')
@section('content')
<div class="card"><div class="card-header"><h2>Remisión #{{ $registro->id }} · {{ $registro->clinica?->especialidad }}</h2></div><div class="card-body">
<p>Paciente: {{ $registro->paciente?->nombre_completo }}. Se creará la historia de esta especialidad para revisión docente.</p>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('remisiones.asignar', $registro->id) }}" class="form-grid">@csrf
@foreach(['estudiante' => ['estudiante','interno'], 'docente' => ['docente','jefe-catedra']] as $tipo => $roles)
<div class="form-group"><label for="asignacion-{{ $tipo }}">{{ ucfirst($tipo) }}</label><select id="asignacion-{{ $tipo }}" name="{{ $tipo }}_id" class="form-control" required><option value="">Seleccione...</option>
@foreach($personas as $persona) @if($persona->hasAnyRole($roles))<option value="{{ $persona->id }}" @selected((string) old($tipo.'_id') === (string) $persona->id)>{{ $persona->nombre_completo }}</option>@endif @endforeach
</select></div>
@endforeach
<div><button class="btn btn-primary">Asignar y crear historia</button><a class="btn btn-secondary" href="{{ route('remisiones.index') }}">Volver</a></div>
</form>
</div></div>
@endsection
