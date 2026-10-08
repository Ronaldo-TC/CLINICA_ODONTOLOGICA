@extends('layouts.app')
@section('title', 'Radiografías y visor DICOM')
@section('content')
<div class="card">
    <div class="card-header"><h2>Estudio #{{ $registro->id }} · {{ $registro->descripcion }}</h2><a class="btn btn-secondary" href="{{ route('radiologia.index') }}">Volver</a></div>
    <div class="card-body">
        <p>{{ $registro->paciente?->nombre_completo }} · {{ $registro->clinica?->especialidad }} · {{ $registro->fecha_toma?->format('d/m/Y') }}</p>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if(auth()->user()->hasAnyRole(['admin', 'subadmin', 'radiologia']))
        <form action="{{ route('radiologia.archivos.store', $registro->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="estudio-archivos">Radiografías periapicales (JPG/PNG) o serie de tomografía (DICOM .dcm)</label>
            <input id="estudio-archivos" name="archivos[]" type="file" accept=".dcm,application/dicom,image/jpeg,image/png" multiple required class="form-control">
            <button class="btn btn-primary" style="margin-top:10px">Guardar archivos del estudio</button>
        </form>
        @endif
        <div id="visor-radiologia" data-imagenes="{{ $imagenes->toJson() }}" style="margin-top:20px">
            <div class="toolbar-actions" style="flex-wrap:wrap">
                <label>Serie DICOM<select id="dicom-serie" class="form-control"></select></label>
                <button type="button" class="btn btn-sm btn-secondary" data-tool="WindowLevel">Contraste</button>
                <button type="button" class="btn btn-sm btn-secondary" data-tool="Zoom">Zoom</button>
                <button type="button" class="btn btn-sm btn-secondary" data-tool="Pan">Mover</button>
                <button type="button" class="btn btn-sm btn-secondary" data-tool="Length">Medir</button>
                <button type="button" class="btn btn-sm btn-secondary" id="dicom-invertir">Invertir</button>
                <button type="button" class="btn btn-sm btn-secondary" id="dicom-reset">Restablecer</button>
            </div>
            <p id="dicom-estado" role="status">Cargando estudio…</p>
            <div id="dicom-viewport" style="height:550px;max-height:70vh;background:#000;position:relative" tabindex="0" aria-label="Visor DICOM"></div>
            <label>Corte <input id="dicom-corte" type="range" min="0" max="0" value="0" style="width:80%" disabled></label>
            <p>Rueda del ratón: recorrer cortes. Las medidas usan la escala que contiene el DICOM.</p>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px">
            @foreach($archivos as $archivo)
            <div>
                @if(in_array($archivo->extension, ['png','jpg','jpeg']))
                <a href="{{ route('radiologia.archivos.ver', [$registro->id, $archivo->id]) }}" target="_blank" rel="noopener"><img src="{{ route('radiologia.archivos.ver', [$registro->id, $archivo->id]) }}" alt="{{ $archivo->nombre_original }}" style="max-width:280px;max-height:300px"></a>
                @else
                <a href="{{ route('radiologia.archivos.ver', [$registro->id, $archivo->id]) }}">{{ $archivo->nombre_original }}</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
@push('scripts')
@vite('resources/js/radiologia-visor.js')
@endpush
