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
        <section id="visor-radiologia" data-imagenes="{{ $imagenes->toJson() }}" aria-label="Visor de radiología">
            <div class="radiologia-visor-heading">
                <h3>Visor del estudio #{{ $registro->id }}</h3>
                <button type="button" class="btn btn-secondary" id="dicom-pantalla" aria-pressed="false">Pantalla completa</button>
            </div>
            <div class="radiologia-visor-layout">
                <div class="radiologia-visor-panel">
                    <label for="dicom-serie">Imagen o serie DICOM</label>
                    <select id="dicom-serie" class="form-control"></select>
                    <p>Herramienta del ratón</p>
                    <div class="radiologia-visor-tools">
                        @foreach(['WindowLevel' => 'Contraste', 'Zoom' => 'Zoom', 'Pan' => 'Mover', 'Length' => 'Distancia', 'Angle' => 'Ángulo', 'RectangleROI' => 'Área rectangular', 'EllipticalROI' => 'Área elíptica', 'Probe' => 'Valor de píxel', 'Eraser' => 'Borrar medida'] as $herramienta => $etiqueta)
                            <button type="button" class="btn btn-sm btn-secondary" data-tool="{{ $herramienta }}" aria-pressed="{{ $herramienta === 'WindowLevel' ? 'true' : 'false' }}" @if(!in_array($herramienta, ['WindowLevel', 'Zoom', 'Pan'])) data-dicom-only @endif>{{ $etiqueta }}</button>
                        @endforeach
                    </div>
                    <p>Vista de la imagen</p>
                    <div class="radiologia-visor-tools">
                        @foreach(['acercar' => 'Zoom +', 'alejar' => 'Zoom −', 'ajustar' => 'Ajustar imagen', 'girar-izquierda' => 'Girar −90°', 'girar-derecha' => 'Girar +90°', 'espejo-horizontal' => 'Espejo horizontal', 'espejo-vertical' => 'Espejo vertical', 'invertir' => 'Invertir colores', 'reset' => 'Restablecer'] as $accion => $etiqueta)
                            <button type="button" class="btn btn-sm btn-secondary" id="dicom-{{ $accion }}" data-view-action>{{ $etiqueta }}</button>
                        @endforeach
                        <button type="button" class="btn btn-sm btn-secondary" id="dicom-limpiar-medidas" data-dicom-only>Borrar medidas del corte</button>
                    </div>
                    <div class="radiologia-visor-campos">
                        <label for="dicom-ventana">Ventana DICOM</label>
                        <select id="dicom-ventana" class="form-control" data-dicom-only>
                            <option value="original">Original del archivo</option>
                            <option value="hueso">Hueso · WW 2000 / WL 500</option>
                            <option value="tejidos">Tejidos blandos · WW 400 / WL 40</option>
                            <option value="personalizada">Personalizada</option>
                        </select>
                        <label for="dicom-ww">Ancho de ventana (WW)</label>
                        <input type="number" id="dicom-ww" class="form-control" min="1" step="1" data-dicom-only>
                        <label for="dicom-wl">Nivel de ventana (WL)</label>
                        <input type="number" id="dicom-wl" class="form-control" step="1" data-dicom-only>
                        <label for="radiografia-brillo">Brillo JPG/PNG</label>
                        <input type="range" id="radiografia-brillo" min="25" max="250" value="100" data-image-only>
                        <label for="radiografia-contraste">Contraste JPG/PNG</label>
                        <input type="range" id="radiografia-contraste" min="25" max="300" value="100" data-image-only>
                    </div>
                    <p class="radiologia-visor-help">DICOM: rueda para recorrer cortes; botón derecho para mover. Distancias y áreas usan la escala del archivo. JPG/PNG: rueda para acercar. Las medidas se mantienen durante esta sesión del visor.</p>
                </div>
                <div class="radiologia-visor-main">
                    <p id="dicom-estado" role="status" aria-live="polite">Cargando estudio…</p>
                    <div class="radiologia-visor-stage">
                        <div id="dicom-viewport" tabindex="0" aria-label="Visor DICOM" hidden></div>
                        <div id="radiografia-viewport" tabindex="0" aria-label="Visor de radiografías JPG/PNG" hidden><img id="radiografia-imagen" alt="Radiografía del estudio" draggable="false"></div>
                    </div>
                    <div class="radiologia-visor-navigation">
                        <button type="button" class="btn btn-sm btn-secondary" id="dicom-anterior">Anterior</button>
                        <output id="dicom-contador" aria-live="polite">Sin imagen</output>
                        <button type="button" class="btn btn-sm btn-secondary" id="dicom-siguiente">Siguiente</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="dicom-reproducir" data-dicom-only aria-pressed="false">Reproducir</button>
                        <label for="dicom-fps">Velocidad</label>
                        <select id="dicom-fps" class="form-control" data-dicom-only><option value="2">2 cortes/s</option><option value="5" selected>5 cortes/s</option><option value="10">10 cortes/s</option></select>
                    </div>
                    <label for="dicom-corte">Corte</label>
                    <input id="dicom-corte" type="range" min="0" max="0" value="0" disabled>
                </div>
            </div>
        </section>
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
@push('styles')
<style>
    #visor-radiologia { margin-top: 20px; }
    .radiologia-visor-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 14px; }
    .radiologia-visor-heading h3 { margin: 0; }
    .radiologia-visor-layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 18px; }
    .radiologia-visor-panel { max-height: 700px; overflow: auto; padding-right: 8px; }
    .radiologia-visor-tools { display: flex; flex-wrap: wrap; gap: 7px; }
    .radiologia-visor-tools [aria-pressed="true"] { background: #0e7490; color: #fff; border-color: #0e7490; }
    .radiologia-visor-campos { display: grid; gap: 8px; margin-top: 16px; }
    .radiologia-visor-help { color: var(--muted); font-size: 12px; line-height: 1.5; }
    .radiologia-visor-main { min-width: 0; display: flex; flex-direction: column; }
    .radiologia-visor-stage { position: relative; height: 550px; min-height: 240px; background: #0b1018; border-radius: 12px; overflow: hidden; }
    #dicom-viewport, #radiografia-viewport { position: absolute; inset: 0; touch-action: none; }
    #dicom-viewport[hidden], #radiografia-viewport[hidden] { display: none; }
    #radiografia-viewport { overflow: hidden; cursor: grab; }
    #radiografia-viewport:active { cursor: grabbing; }
    #radiografia-imagen { display: block; width: 100%; height: 100%; object-fit: contain; user-select: none; }
    .radiologia-visor-navigation { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; margin: 12px 0; }
    #dicom-fps { width: auto; }
    #visor-radiologia.is-fullscreen, #visor-radiologia:fullscreen { position: fixed; inset: 0; z-index: 100000; margin: 0; padding: 20px; width: 100%; height: 100dvh; box-sizing: border-box; display: flex; flex-direction: column; background: var(--bg, #f1f5f9); overflow: auto; }
    #visor-radiologia.is-fullscreen .radiologia-visor-layout { flex: 1; min-height: 0; }
    #visor-radiologia.is-fullscreen .radiologia-visor-panel { max-height: none; }
    #visor-radiologia.is-fullscreen .radiologia-visor-stage { flex: 1; height: auto; }
    body.radiologia-fullscreen { overflow: hidden; }
    body.radiologia-fullscreen .sidebar, body.radiologia-fullscreen .topbar, body.radiologia-fullscreen #sidebarBackdrop { display: none !important; }
    @media(max-width: 700px) {
        .radiologia-visor-layout { display: flex; flex-direction: column-reverse; }
        .radiologia-visor-stage { height: 360px; }
        #visor-radiologia.is-fullscreen .radiologia-visor-main { min-height: 60dvh; }
        #visor-radiologia.is-fullscreen .radiologia-visor-panel { overflow: visible; }
    }
</style>
@endpush
@push('scripts')
@vite('resources/js/radiologia-visor.js')
@endpush
