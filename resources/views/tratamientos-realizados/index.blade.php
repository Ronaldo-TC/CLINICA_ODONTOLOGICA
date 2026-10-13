@extends('layouts.app')
@section('title', 'Atención y avances')
@section('content')
<div class="dashboard-toolbar"><div class="toolbar-info">Tratamientos y evolución clínica</div><div class="toolbar-actions"><x-boton-reporte ruta="tratamientos-realizados.reporte" /></div></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-header"><h2>Proponer tratamiento</h2></div><div class="card-body">
<p>Propuesta → autorización docente → pago en Cajas → avances autorizados → insumos → finalización del estudiante → culminación docente.</p>
@canany(['atenciones.crear','atenciones.gestionar'])
<form action="{{ route('tratamientos-realizados.store') }}" method="POST" class="form-grid">@csrf
<div class="form-group"><label>Historia clínica autorizada</label><select name="historia_clinica_id" class="form-control" required><option value="">Seleccione...</option>@foreach($historias as $historia)<option value="{{ $historia->id }}">#{{ $historia->id }} · {{ $historia->paciente?->nombre_completo }}</option>@endforeach</select></div>
<div class="form-group"><label>Tratamiento</label><select name="tratamiento_id" class="form-control" required><option value="">Seleccione...</option>@foreach($catalogo as $tratamiento)<option value="{{ $tratamiento->id }}">{{ $tratamiento->nombre }} · {{ $tratamiento->costo }} Bs</option>@endforeach</select></div>
<div class="form-group"><label>Pieza dental</label><input name="pieza_dental" class="form-control" maxlength="20"></div>
<div class="form-group"><label>Observación</label><textarea name="observacion" class="form-control" maxlength="2000"></textarea></div>
<div><button class="btn btn-primary"><x-icono nombre="enviar" /> Enviar propuesta</button></div>
</form>
@endcanany
</div></div>
@foreach($registros as $registro)
<div class="card" style="margin-top:16px"><div class="card-header"><h2>#{{ $registro->id }} · {{ $registro->tratamiento?->nombre }} · {{ $registro->historiaClinica?->paciente?->nombre_completo }}</h2><span class="badge badge-info">{{ $registro->estado }}</span></div><div class="card-body">
<p>Pieza: {{ $registro->pieza_dental ?: 'Sin especificar' }} · Costo: {{ $registro->costo_cobrado }} Bs</p>
<div class="actions">
@if(in_array($registro->estado, ['pendiente','propuesta'])) @canany(['atenciones.autorizar','atenciones.gestionar'])<form action="{{ route('tratamientos-realizados.autorizar', $registro->id) }}" method="POST">@csrf<button class="btn btn-primary"><x-icono nombre="autorizar" /> Autorizar para Cajas</button></form>@endcanany @endif
@if($registro->estado === 'en_proceso') @canany(['atenciones.editar','atenciones.gestionar'])<form action="{{ route('tratamientos-realizados.finalizar', $registro->id) }}" method="POST">@csrf<button class="btn btn-primary"><x-icono nombre="finalizar" /> Finalizar como estudiante</button></form>@endcanany @endif
@if($registro->estado === 'finalizado') @canany(['atenciones.autorizar','atenciones.gestionar'])<form action="{{ route('tratamientos-realizados.culminar', $registro->id) }}" method="POST">@csrf<button class="btn btn-primary"><x-icono nombre="finalizar" /> Confirmar culminación</button></form>@endcanany @endif
</div>
@if(in_array($registro->estado, ['autorizado','en_proceso']))
@canany(['historias.editar','historias.gestionar'])
<form action="{{ route('sesiones-atencion.store', $registro->id) }}" method="POST" class="form-grid" style="margin-top:12px">@csrf
<div class="form-group"><label>Fecha de atención</label><input name="fecha" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" required></div>
<div class="form-group"><label>Avance realizado</label><textarea name="descripcion" class="form-control" required maxlength="5000"></textarea></div>
<div><button class="btn btn-secondary"><x-icono nombre="avance" /> Registrar avance</button></div>
</form>
@endcanany
@endif
@foreach($registro->sesionesAtencion as $sesion)
<div style="padding:12px 0;border-top:1px solid var(--line)">
<strong>Avance #{{ $sesion->id }} · {{ $sesion->fecha?->format('d/m/Y') }}</strong><p>{{ $sesion->descripcion }}</p>
<span class="badge {{ $sesion->autorizado ? 'badge-success' : 'badge-warning' }}">{{ $sesion->autorizado ? 'Autorizado' : 'Pendiente de revisión' }}</span>
@if(!$sesion->autorizado)
@canany(['historias.autorizar','historias.gestionar'])<form action="{{ route('sesiones-atencion.autorizar', $sesion->id) }}" method="POST">@csrf<input name="observacion_docente" class="form-control" placeholder="Observación docente" maxlength="2000"><button class="btn btn-sm btn-primary"><x-icono nombre="autorizar" /> Autorizar avance</button></form>@endcanany
@endif
</div>
@endforeach
</div></div>
@endforeach
<div style="margin-top:16px">{{ $registros->links() }}</div>
@endsection
