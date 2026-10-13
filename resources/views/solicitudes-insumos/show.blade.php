@extends('layouts.app')
@section('title', 'Solicitud de insumos')
@section('content')
<div class="card"><div class="card-header"><h2>Solicitud #{{ $registro->id }} · {{ $registro->estado }}</h2><a class="btn btn-secondary" href="{{ route('solicitudes-insumos.index') }}">Volver</a></div><div class="card-body">
<p>Estudiante: {{ $registro->estudiante?->nombre_completo }} · Clínica: {{ $registro->clinica?->especialidad }}</p>
<p>Paciente: {{ $registro->historiaClinica?->paciente?->nombre_completo }} · Ficha #{{ $registro->historiaClinica?->admision_id }}</p>
<p>Tratamiento: {{ $registro->tratamiento?->nombre ?? $registro->tratamientoRealizado?->tratamiento?->nombre }} · Pieza: {{ $registro->pieza }} @if($registro->sesion_atencion_id) · Avance: #{{ $registro->sesion_atencion_id }} @endif</p>
<p>Autorización: {{ $registro->docente?->nombre_completo ?? 'Pendiente' }} · {{ $registro->fecha_autorizacion?->format('d/m/Y H:i') }}</p>
<p>Aprobación de Insumos: {{ $registro->encargado?->nombre_completo ?? 'Pendiente' }} · {{ $registro->fecha_aprobacion?->format('d/m/Y H:i') }}</p>
@if($registro->motivo_rechazo)<p>Motivo del rechazo: {{ $registro->motivo_rechazo }}</p>@endif
<table><thead><tr><th>Insumo</th><th>Solicitado</th><th>Entregado</th><th>Utilizado</th></tr></thead><tbody>
@foreach($registro->detalles as $detalle)<tr><td>{{ $detalle->insumo?->nombre }}</td><td>{{ $detalle->cantidad_solicitada }}</td><td>{{ $detalle->cantidad_entregada }}</td><td>{{ $detalle->consumos->sum('cantidad_usada') }}</td></tr>@endforeach
</tbody></table>
@if($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
@include('solicitudes-insumos.partials.revision', ['solicitud' => $registro])
@canany(['solicitudes-insumos.consumir','solicitudes-insumos.gestionar','insumos.gestionar'])
@if($registro->sesion_atencion_id && in_array($registro->estado, ['parcial','entregado']))
<form id="consumo-insumos" style="margin-top:20px">@csrf<h3>Registrar cantidades utilizadas</h3>
@foreach($registro->detalles as $detalle)
<div class="form-group"><label for="uso-{{ $detalle->id }}">{{ $detalle->insumo?->nombre }} (disponible: {{ $detalle->disponible_uso }})</label><input type="number" id="uso-{{ $detalle->id }}" data-detalle="{{ $detalle->id }}" min="0" max="{{ $detalle->disponible_uso }}" step="0.01" value="0" class="form-control"></div>
@endforeach
<button class="btn btn-primary">Guardar consumo</button><p id="consumo-resultado" role="status"></p>
</form>
@endif
@endcanany
</div></div>
@endsection
@push('scripts')
<script>document.getElementById('consumo-insumos')?.addEventListener('submit', async event => {
    event.preventDefault(); const form=event.target; const button=form.querySelector('button'); button.disabled=true;
    try {
        const detalles=Array.from(form.querySelectorAll('[data-detalle]')).map(input=>({detalle_id:Number(input.dataset.detalle),cantidad_usada:Number(input.value)})).filter(item=>item.cantidad_usada>0);
        const response=await fetch(@json(route('solicitudes-insumos.consumir', $registro->id)),{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':form.querySelector('[name="_token"]').value},body:JSON.stringify({detalles})});
        const data=await response.json(); document.getElementById('consumo-resultado').textContent=data.message; if(response.ok) location.reload();
    } catch(error) {document.getElementById('consumo-resultado').textContent=error.message;} finally {button.disabled=false;}
});</script>
@endpush
