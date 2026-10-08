@extends('layouts.app')
@section('title', 'Ficha de admisión')
@section('page-title', 'Ficha de admisión')
@section('content')
<div class="dashboard-toolbar">
    <a class="btn btn-secondary" href="{{ route('admisiones.index') }}">Volver</a>
    <a class="btn btn-secondary" href="{{ route('admisiones.ficha-pdf', ['admision' => $registro->id, 'formato' => 'pdf']) }}">PDF de la ficha</a>
    <a class="btn btn-secondary" href="{{ route('admisiones.reporte-paciente', ['admision' => $registro->id, 'formato' => 'pdf']) }}">Descargar PDF del paciente</a>
    <a class="btn btn-secondary" href="{{ route('admisiones.reporte-paciente', $registro->id) }}" target="_blank" rel="noopener">Vista previa del reporte</a>
    @can('admisiones.editar')<a class="btn btn-primary" href="{{ route('admisiones.edit', $registro->id) }}">Editar ficha</a>@endcan
</div>
<div class="card" id="ficha-detalle">
    <div class="card-header"><h2>{{ $registro->paciente?->nombre_completo }}</h2><span>CI: {{ $registro->paciente?->ci }}</span></div>
    <div class="card-body">
        <div class="report-grid">
            <p><strong>Nacimiento:</strong> {{ $registro->paciente?->fecha_nacimiento?->format('d/m/Y') ?? 'Sin registrar' }}</p>
            <p><strong>Edad:</strong> {{ $registro->paciente?->edad !== null ? $registro->paciente->edad.' años' : 'Sin registrar' }}</p>
            <p><strong>Sexo registrado:</strong> {{ $registro->paciente?->genero ?: 'Sin registrar' }}</p>
        </div>
        @include('reportes.partials.ficha-admision', ['ficha' => $registro])
        @canany(['admisiones.autorizar','admisiones.gestionar'])
            @if(auth()->user()->hasAnyRole(['admin', 'subadmin']) || $registro->responsable_id === auth()->id())
                <div class="form-actions"><button class="btn btn-primary" id="autorizar-ficha">Autorizar ficha</button><span id="ficha-resultado" role="status"></span></div>
            @endif
        @endcanany
    </div>
</div>
@endsection
@push('styles')
<style>
    #ficha-detalle .report-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin:12px 0;}
    #ficha-detalle .report-field dt {font-weight:600;color:var(--muted);}
    #ficha-detalle .report-field dd {margin:5px 0 0;white-space:pre-wrap;overflow-wrap:anywhere;}
    #ficha-detalle h3 {margin:20px 0 10px;}
    #ficha-detalle .report-signatures {display:flex;flex-wrap:wrap;gap:24px;margin:20px 0;}
    #ficha-detalle .report-signatures>div {flex:1;min-width:220px;}
    #ficha-detalle p {white-space:pre-wrap;overflow-wrap:anywhere;}
</style>
@endpush
@push('scripts')
<script>
    document.getElementById('autorizar-ficha')?.addEventListener('click', async () => {
        const resultado = document.getElementById('ficha-resultado');
        try {
            const response = await fetch(@json(route('admisiones.autorizar', $registro->id)), {
                method:'POST', headers:{Accept:'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}
            });
            const data = await response.json();
            resultado.textContent = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
            if (response.ok) location.reload();
        } catch { resultado.textContent = 'No se pudo autorizar la ficha. Intente nuevamente.'; }
    });
</script>
@endpush