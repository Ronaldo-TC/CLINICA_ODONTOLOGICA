<section id="adm-odontograma-3d" data-model-url="{{ asset('models/odontograma.glb') }}" aria-label="Editor de odontograma 3D">
    <div class="odontograma-3d-heading">
        <p>Selecciona una condición y haz clic para marcar. Arrastra para girar, usa la rueda para acercar y el botón derecho para desplazar; un clic derecho borra la pieza. Los cambios se guardan al registrar la admisión.</p>
        <button type="button" class="btn btn-secondary" id="adm-3d-pantalla" aria-pressed="false">Pantalla completa</button>
    </div>
    <div class="odontograma-3d-layout">
    <div class="odontograma-3d-panel" aria-label="Herramientas de marcado y vista">
    <div class="form-grid">
        <div class="form-group">
            <label for="adm-3d-denticion">Dentición</label>
            <select id="adm-3d-denticion" class="form-control">
                <option value="adulto">Adulto · 32 piezas</option>
                <option value="infantil">Infantil · 20 piezas</option>
                <option value="mixto">Mixto · ambas denticiones</option>
            </select>
        </div>
        <div class="form-group">
            <label for="adm-3d-pieza">Pieza dental (FDI)</label>
            <select id="adm-3d-pieza" class="form-control"></select>
        </div>
        <div class="form-group">
            <label for="adm-3d-modo">Aplicar a</label>
            <select id="adm-3d-modo" class="form-control">
                <option value="cara">Una cara</option>
                <option value="marca">Diente completo · marca</option>
            </select>
        </div>
        <div class="form-group">
            <label for="adm-3d-cara">Cara</label>
            <select id="adm-3d-cara" class="form-control">
                <option value="o">O · Oclusal / incisal</option>
                <option value="m">M · Mesial</option>
                <option value="d">D · Distal</option>
                <option value="v">V · Vestibular</option>
                <option value="l">L · Lingual / palatina</option>
            </select>
        </div>
        <div class="form-group">
            <label for="adm-3d-condicion">Condición</label>
            <select id="adm-3d-condicion" class="form-control">
                @foreach(config('odontograma.condiciones') as $codigo => $condicion)
                    <option value="{{ $codigo }}">{{ $condicion['etiqueta'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="adm-3d-color">Color de la marca</label>
            <select id="adm-3d-color" class="form-control">
                <option value="rojo">Rojo</option>
                <option value="azul">Azul</option>
            </select>
        </div>
    </div>
    <div class="odontograma-3d-tools" aria-label="Condiciones dentales">
        @foreach(config('odontograma.condiciones') as $codigo => $condicion)
            <button type="button" data-3d-herramienta="{{ $codigo }}" aria-pressed="false" style="--tool-color:{{ $condicion['color'] ?? '#dc2626' }}">{{ $condicion['etiqueta'] }}</button>
        @endforeach
    </div>
    <div class="odontograma-3d-caras" aria-label="Caras de la pieza seleccionada">
        @foreach(['v' => 'Vestibular', 'm' => 'Mesial', 'o' => 'Oclusal / incisal', 'd' => 'Distal', 'l' => 'Lingual / palatina'] as $cara => $etiqueta)
            <button type="button" data-3d-cara="{{ $cara }}" title="{{ $etiqueta }}" aria-label="Marcar cara {{ $etiqueta }}">{{ strtoupper($cara) }}</button>
        @endforeach
    </div>
    <div class="odontograma-3d-actions">
        <button type="button" class="btn btn-primary" id="adm-3d-aplicar" disabled>Aplicar condición</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-extraer" disabled>Marcar ausente / extraído</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-restaurar" disabled>Restaurar pieza / borrar</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-limpiar">Limpiar dentición visible</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-centrar">Centrar vista</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-enfocar" disabled>Acercar a la pieza</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-deshacer" disabled>Deshacer</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-rehacer" disabled>Rehacer</button>
    </div>
    <div class="odontograma-3d-actions">
        <label><input type="checkbox" id="adm-3d-encias" checked> Encías</label>
        <label><input type="checkbox" id="adm-3d-raices"> Raíces</label>
        <label><input type="checkbox" id="adm-3d-huesos"> Huesos</label>
        <label><input type="checkbox" id="adm-3d-numeros" checked> Números FDI</label>
        <label><input type="checkbox" id="adm-3d-pintar" checked> Pintar con clic</label>
        <label><input type="checkbox" id="adm-3d-girar"> Giro automático</label>
        <label><input type="checkbox" id="adm-3d-suelo" checked> Fondo con suelo</label>
    </div>
    <div class="form-group">
        <label for="adm-3d-vista">Vista del modelo</label>
        <select id="adm-3d-vista" class="form-control">
            <option value="perspectiva">Perspectiva</option>
            <option value="frontal">Frontal</option>
            <option value="superior">Desde arriba</option>
            <option value="inferior">Desde abajo</option>
            <option value="derecha">Lateral derecha</option>
            <option value="izquierda">Lateral izquierda</option>
            <option value="posterior">Posterior</option>
        </select>
    </div>
    <div class="odontograma-3d-actions">
        <button type="button" class="btn btn-secondary" id="adm-3d-acercar" aria-label="Acercar modelo">Zoom +</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-alejar" aria-label="Alejar modelo">Zoom −</button>
    </div>
    <div class="form-group">
        <label for="adm-3d-iluminacion">Iluminación</label>
        <input type="range" id="adm-3d-iluminacion" min="0.5" max="1.5" step="0.1" value="1">
    </div>
    <div id="adm-3d-resumen" aria-live="polite"></div>
    </div>
    <div class="odontograma-3d-viewport">
    <div class="odontograma-3d-stage">
        <canvas id="adm-3d-canvas" aria-label="Modelo dental 3D; también puede seleccionar la pieza y la cara con los controles del formulario"></canvas>
    </div>
    <p id="adm-3d-status" role="status" aria-live="polite">Cargando modelo dental 3D…</p>
    </div>
    </div>
    <span class="form-error" data-error-for="odontograma_pintado"></span>
</section>

@push('styles')
<style>
    .odontograma-3d-heading { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
    .odontograma-3d-heading p { flex: 1; margin: 0; }
    .odontograma-3d-layout { display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 18px; }
    .odontograma-3d-panel { max-height: 720px; overflow: auto; padding-right: 8px; }
    .odontograma-3d-panel .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .odontograma-3d-tools { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .odontograma-3d-tools button { padding: 7px 9px; border: 1px solid var(--border); border-left: 4px solid var(--tool-color); border-radius: 6px; background: var(--surface, #fff); color: var(--text); font-size: 12px; cursor: pointer; }
    .odontograma-3d-tools button[aria-pressed="true"] { outline: 2px solid #0891b2; background: #e0f2fe; color: #0c4a6e; }
    .odontograma-3d-caras { display: grid; grid-template-columns: repeat(3, 42px); grid-template-areas: '. v .' 'm o d' '. l .'; gap: 4px; justify-content: center; margin: 14px 0; }
    .odontograma-3d-caras button { height: 36px; border: 2px solid #94a3b8; border-radius: 6px; cursor: pointer; font-weight: 700; }
    @foreach(['v', 'm', 'o', 'd', 'l'] as $cara)
        [data-3d-cara="{{ $cara }}"] { grid-area: {{ $cara }}; }
    @endforeach
    .odontograma-3d-caras button[aria-pressed="true"] { outline: 2px solid #0891b2; outline-offset: 2px; }
    .odontograma-3d-viewport { min-width: 0; display: flex; flex-direction: column; }
    .odontograma-3d-stage { position: relative; height: 680px; background: #19232f; border: 1px solid #475569; border-radius: 12px; overflow: hidden; }
    .odontograma-3d-stage::before { content: ''; position: absolute; inset: -12px; background: url("{{ asset('models/piso-odontograma.svg') }}") center / cover; filter: blur(4px) brightness(.85); pointer-events: none; }
    .odontograma-3d-stage.sin-suelo::before { display: none; }
    #adm-3d-canvas { position: relative; display: block; width: 100%; height: 100%; touch-action: none; cursor: grab; }
    #adm-3d-canvas:active { cursor: grabbing; }
    .odontograma-3d-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 12px 0; }
    #adm-3d-resumen { margin-bottom: 16px; }
    #adm-3d-resumen button { margin: 4px; }
    #adm-odontograma-3d.is-fullscreen, #adm-odontograma-3d:fullscreen { position: fixed; inset: 0; z-index: 100000; width: 100%; height: 100dvh; box-sizing: border-box; display: flex; flex-direction: column; padding: 20px; background: var(--bg, #f1f5f9); overflow: auto; }
    #adm-odontograma-3d.is-fullscreen .odontograma-3d-layout { flex: 1; min-height: 0; }
    #adm-odontograma-3d.is-fullscreen .odontograma-3d-panel { max-height: none; }
    #adm-odontograma-3d.is-fullscreen .odontograma-3d-stage { flex: 1; height: auto; min-height: 250px; }
    body.odontograma-3d-fullscreen { overflow: hidden; }
    body.odontograma-3d-fullscreen .sidebar, body.odontograma-3d-fullscreen .topbar, body.odontograma-3d-fullscreen #sidebarBackdrop { display: none !important; }
    @media (max-width: 900px) {
        .odontograma-3d-layout { grid-template-columns: 240px minmax(0, 1fr); }
        .odontograma-3d-panel .form-grid { grid-template-columns: 1fr; }
        .odontograma-3d-stage { height: 560px; }
    }
    @media (max-width: 640px) {
        .odontograma-3d-heading { flex-wrap: wrap; }
        .odontograma-3d-layout { display: flex; flex-direction: column-reverse; }
        .odontograma-3d-stage { height: 360px; }
        .odontograma-3d-panel .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        #adm-odontograma-3d.is-fullscreen .odontograma-3d-viewport { min-height: 45dvh; }
        #adm-odontograma-3d.is-fullscreen .odontograma-3d-panel { overflow: visible; }
    }
</style>
@endpush

@push('scripts')
    @vite('resources/js/odontograma-admision.js')
@endpush
