<section id="adm-odontograma-3d" data-model-url="{{ asset('models/odontograma.glb') }}" aria-label="Editor de odontograma 3D">
    <p>Arrastra para girar y usa la rueda para acercar. Selecciona un diente o una cara y aplica la condición. Los cambios se guardan al registrar la admisión.</p>
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
    <div class="odontograma-3d-actions">
        <button type="button" class="btn btn-primary" id="adm-3d-aplicar" disabled>Aplicar condición</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-extraer" disabled>Marcar ausente / extraído</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-restaurar" disabled>Restaurar pieza / borrar</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-limpiar">Limpiar dentición visible</button>
        <button type="button" class="btn btn-secondary" id="adm-3d-centrar">Centrar vista</button>
    </div>
    <div class="odontograma-3d-actions">
        <label><input type="checkbox" id="adm-3d-encias" checked> Encías</label>
        <label><input type="checkbox" id="adm-3d-raices"> Raíces</label>
        <label><input type="checkbox" id="adm-3d-huesos"> Huesos</label>
    </div>
    <div class="odontograma-3d-stage">
        <canvas id="adm-3d-canvas" aria-label="Modelo dental 3D; también puede seleccionar la pieza y la cara con los controles del formulario"></canvas>
    </div>
    <p id="adm-3d-status" role="status" aria-live="polite">Cargando modelo dental 3D…</p>
    <div id="adm-3d-resumen" aria-live="polite"></div>
    <span class="form-error" data-error-for="odontograma_pintado"></span>
</section>

@push('styles')
<style>
    .odontograma-3d-stage { height: 480px; background: #eef2f7; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    #adm-3d-canvas { display: block; width: 100%; height: 100%; touch-action: none; cursor: grab; }
    #adm-3d-canvas:active { cursor: grabbing; }
    .odontograma-3d-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 12px 0; }
    #adm-3d-resumen { margin-bottom: 16px; }
    #adm-3d-resumen button { margin: 4px; }
    #adm-odontograma-alternativo summary { cursor: pointer; margin: 12px 0; }
    @media (max-width: 640px) { .odontograma-3d-stage { height: 360px; } }
</style>
@endpush

@push('scripts')
    @vite('resources/js/odontograma-admision.js')
@endpush
