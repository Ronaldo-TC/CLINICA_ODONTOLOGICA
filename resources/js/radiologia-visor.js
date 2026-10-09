import { RenderingEngine, Enums, init as coreInit } from '@cornerstonejs/core';
import { init as loaderInit, wadouri } from '@cornerstonejs/dicom-image-loader';
import { init as toolsInit, addTool, ToolGroupManager, WindowLevelTool, ZoomTool, PanTool, StackScrollTool, LengthTool, AngleTool, RectangleROITool, EllipticalROITool, ProbeTool, EraserTool, annotation, Enums as ToolEnums } from '@cornerstonejs/tools';
import dicomParser from 'dicom-parser';
import { configurarPantallaCompleta } from './visor-pantalla-completa.js';

const root = document.getElementById('visor-radiologia');
if (root) iniciarVisor();

function iniciarVisor() {
    const control = nombre => document.getElementById(`dicom-${nombre}`);
    const status = control('estado'), element = control('viewport'), slider = control('corte'), selector = control('serie');
    const raster = document.getElementById('radiografia-viewport'), image = document.getElementById('radiografia-imagen');
    const brillo = document.getElementById('radiografia-brillo'), contraste = document.getElementById('radiografia-contraste');
    const herramientas = [WindowLevelTool, ZoomTool, PanTool, StackScrollTool, LengthTool, AngleTool, RectangleROITool, EllipticalROITool, ProbeTool, EraserTool];
    const series = new Map(), fileIds = [];
    let engine, group, viewport, actual;
    let active = WindowLevelTool.toolName;
    let transformacion = estadoImagen(), reproduciendo = false, timer = null, cambiando = Promise.resolve(), destruido = false;
    const limpiarPantalla = configurarPantallaCompleta(root, control('pantalla'), 'radiologia-fullscreen');

    function estadoImagen() { return { zoom: 1, x: 0, y: 0, rotation: 0, horizontal: 1, vertical: 1, invert: false }; }
    function renderImagen() {
        image.style.transform = `translate(${transformacion.x}px, ${transformacion.y}px) rotate(${transformacion.rotation}deg) scale(${transformacion.zoom * transformacion.horizontal}, ${transformacion.zoom * transformacion.vertical})`;
        image.style.filter = `brightness(${brillo.value}%) contrast(${contraste.value}%) invert(${transformacion.invert ? 1 : 0})`;
    }
    function actualizarControles() {
        const dicom = actual?.tipo === 'dicom';
        root.querySelectorAll('[data-dicom-only]').forEach(button => button.disabled = !dicom);
        root.querySelectorAll('[data-image-only]').forEach(button => button.disabled = !actual || dicom);
        root.querySelectorAll('[data-view-action], [data-tool]').forEach(button => {
            button.disabled = !actual || (button.hasAttribute('data-dicom-only') && !dicom);
        });
        root.querySelectorAll('[data-tool]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.tool === active)));
        const indice = dicom ? Number(slider.value) : selector.selectedIndex;
        const total = dicom ? actual.entries.length : selector.options.length;
        control('contador').textContent = actual ? `${dicom ? 'Corte' : 'Imagen'} ${indice + 1} / ${total}` : 'Sin imagen';
        control('anterior').disabled = !actual || indice <= 0;
        control('siguiente').disabled = !actual || indice >= total - 1;
        slider.disabled = !dicom || total < 2;
        control('reproducir').disabled = !dicom || total < 2;
        control('invertir').setAttribute('aria-pressed', String(dicom ? viewport?.getProperties().invert || false : transformacion.invert));
    }
    function detener() {
        reproduciendo = false; clearTimeout(timer);
        control('reproducir').textContent = 'Reproducir';
        control('reproducir').setAttribute('aria-pressed', 'false');
    }
    function sincronizarVentana() {
        const rango = viewport?.getProperties().voiRange;
        if (!rango) return;
        control('ww').value = Math.round(rango.upper - rango.lower);
        control('wl').value = Math.round((rango.upper + rango.lower) / 2);
    }
    function elegirHerramienta(nombre) {
        if (group && actual?.tipo === 'dicom') {
            herramientas.filter(tool => tool !== StackScrollTool).forEach(tool => group.setToolPassive(tool.toolName, { removeAllBindings: true }));
            group.setToolActive(nombre, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Primary }] });
            group.setToolActive(PanTool.toolName, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Secondary }] });
        }
        active = nombre; actualizarControles();
    }
    function mostrarSerie() {
        detener();
        const key = selector.value;
        cambiando = cambiando.catch(() => {}).then(async () => {
            if (key !== selector.value || destruido) return;
            const siguiente = series.get(key);
            if (!siguiente) return;
            status.textContent = 'Abriendo estudio…'; actual = null; actualizarControles();
            if (siguiente.tipo === 'imagen') {
                const precarga = new Image();
                await new Promise((resolve, reject) => {
                    precarga.onload = resolve; precarga.onerror = () => reject(new Error('No se pudo abrir la radiografía.'));
                    precarga.src = siguiente.file.url;
                });
                if (key !== selector.value || destruido) return;
                image.src = siguiente.file.url; image.alt = siguiente.file.nombre;
                transformacion = estadoImagen(); brillo.value = contraste.value = 100; renderImagen();
                element.hidden = true; raster.hidden = false; slider.max = slider.value = 0;
            } else {
                raster.hidden = true; element.hidden = false; engine.resize();
                await viewport.setStack(siguiente.entries.map(entry => entry.imageId));
                if (key !== selector.value || destruido) return;
                viewport.resetCamera(); viewport.resetProperties(); viewport.render();
                slider.max = Math.max(0, siguiente.entries.length - 1); slider.value = 0;
                control('ventana').value = 'original'; sincronizarVentana();
            }
            actual = siguiente; elegirHerramienta(WindowLevelTool.toolName);
            status.textContent = siguiente.nombre; actualizarControles();
        });
        return cambiando;
    }
    async function mover(paso, circular = false) {
        if (!actual) return;
        if (actual.tipo === 'dicom') {
            const total = actual.entries.length;
            const indice = circular ? (Number(slider.value) + paso + total) % total : Math.min(total - 1, Math.max(0, Number(slider.value) + paso));
            await viewport.setImageIdIndex(indice); viewport.render(); slider.value = indice; actualizarControles();
        } else {
            selector.selectedIndex = Math.min(selector.options.length - 1, Math.max(0, selector.selectedIndex + paso));
            await mostrarSerie();
        }
    }
    async function reproducir() {
        if (!reproduciendo || actual?.tipo !== 'dicom') return;
        try {
            await mover(1, true);
            if (reproduciendo) timer = setTimeout(reproducir, 1000 / Number(control('fps').value));
        } catch (failure) { detener(); status.textContent = failure.message; }
    }
    function zoom(factor) {
        if (actual?.tipo === 'dicom') {
            viewport.setZoom(Math.max(0.1, Math.min(20, viewport.getZoom() * factor))); viewport.render();
        } else if (actual) {
            transformacion.zoom = Math.max(0.1, Math.min(20, transformacion.zoom * factor)); renderImagen();
        }
    }
    function girar(angulo) {
        if (actual?.tipo === 'dicom') {
            viewport.setViewPresentation({ rotation: (viewport.getRotation() + angulo + 360) % 360 }); viewport.render();
        } else if (actual) { transformacion.rotation = (transformacion.rotation + angulo + 360) % 360; renderImagen(); }
    }
    function espejo(eje) {
        if (actual?.tipo === 'dicom') {
            const propiedad = eje === 'horizontal' ? 'flipHorizontal' : 'flipVertical';
            viewport.setCamera({ [propiedad]: !viewport.getCamera()[propiedad] }); viewport.render();
        } else if (actual) { transformacion[eje] *= -1; renderImagen(); }
    }
    const mostrarError = failure => status.textContent = failure.message;
    root.querySelectorAll('[data-tool]').forEach(button => button.addEventListener('click', () => elegirHerramienta(button.dataset.tool)));
    selector.addEventListener('change', () => mostrarSerie().catch(mostrarError));
    slider.addEventListener('input', async () => {
        detener(); if (actual?.tipo !== 'dicom') return;
        try { await viewport.setImageIdIndex(Number(slider.value)); viewport.render(); actualizarControles(); }
        catch (failure) { mostrarError(failure); }
    });
    element.addEventListener(Enums.Events.STACK_NEW_IMAGE, event => { slider.value = event.detail.imageIdIndex; actualizarControles(); });
    element.addEventListener(Enums.Events.VOI_MODIFIED, sincronizarVentana);
    control('anterior').addEventListener('click', () => { detener(); mover(-1).catch(mostrarError); });
    control('siguiente').addEventListener('click', () => { detener(); mover(1).catch(mostrarError); });
    control('reproducir').addEventListener('click', () => {
        if (reproduciendo) return detener();
        reproduciendo = true; control('reproducir').textContent = 'Pausar';
        control('reproducir').setAttribute('aria-pressed', 'true'); reproducir();
    });
    control('acercar').addEventListener('click', () => zoom(1.25));
    control('alejar').addEventListener('click', () => zoom(0.8));
    control('girar-izquierda').addEventListener('click', () => girar(-90));
    control('girar-derecha').addEventListener('click', () => girar(90));
    control('espejo-horizontal').addEventListener('click', () => espejo('horizontal'));
    control('espejo-vertical').addEventListener('click', () => espejo('vertical'));
    control('ajustar').addEventListener('click', () => {
        if (actual?.tipo === 'dicom') { viewport.resetCamera(); viewport.render(); }
        else { transformacion.zoom = 1; transformacion.x = transformacion.y = 0; renderImagen(); }
    });
    control('invertir').addEventListener('click', () => {
        if (actual?.tipo === 'dicom') { viewport.setProperties({ invert: !viewport.getProperties().invert }); viewport.render(); }
        else { transformacion.invert = !transformacion.invert; renderImagen(); }
        actualizarControles();
    });
    control('reset').addEventListener('click', () => {
        detener();
        if (actual?.tipo === 'dicom') {
            viewport.resetCamera(); viewport.resetProperties(); viewport.render(); sincronizarVentana(); control('ventana').value = 'original';
        } else { transformacion = estadoImagen(); brillo.value = contraste.value = 100; renderImagen(); }
        actualizarControles();
    });
    control('ventana').addEventListener('change', () => {
        if (actual?.tipo !== 'dicom') return;
        const valor = control('ventana').value;
        if (valor === 'original') viewport.resetProperties();
        else if (valor !== 'personalizada') {
            const [ancho, nivel] = valor === 'hueso' ? [2000, 500] : [400, 40];
            viewport.setProperties({ voiRange: { lower: nivel - ancho / 2, upper: nivel + ancho / 2 } });
        }
        viewport.render(); sincronizarVentana();
    });
    ['ww', 'wl'].forEach(nombre => control(nombre).addEventListener('change', () => {
        const ancho = Number(control('ww').value), nivel = Number(control('wl').value);
        if (actual?.tipo !== 'dicom' || ancho < 1 || !Number.isFinite(ancho) || !Number.isFinite(nivel)) return;
        control('ventana').value = 'personalizada';
        viewport.setProperties({ voiRange: { lower: nivel - ancho / 2, upper: nivel + ancho / 2 } }); viewport.render();
    }));
    control('limpiar-medidas').addEventListener('click', () => {
        if (actual?.tipo !== 'dicom' || !window.confirm('¿Borrar las medidas del corte actual?')) return;
        herramientas.forEach(tool => annotation.state.getAnnotations(tool.toolName, element)?.forEach(marca => {
            if (marca.metadata.referencedImageId === viewport.getCurrentImageId()) annotation.state.removeAnnotation(marca.annotationUID);
        }));
        viewport.render();
    });
    [brillo, contraste].forEach(input => input.addEventListener('input', renderImagen));
    [element, raster].forEach(target => target.addEventListener('contextmenu', event => event.preventDefault()));
    raster.addEventListener('wheel', event => { event.preventDefault(); zoom(event.deltaY < 0 ? 1.1 : 1 / 1.1); }, { passive: false });
    let inicio = null;
    raster.addEventListener('pointerdown', event => {
        if (![0, 2].includes(event.button)) return;
        raster.setPointerCapture(event.pointerId);
        inicio = { x: event.clientX, y: event.clientY, estado: { ...transformacion }, brillo: Number(brillo.value), contraste: Number(contraste.value), modo: event.button === 2 ? 'Pan' : active };
    });
    raster.addEventListener('pointermove', event => {
        if (!inicio) return;
        const x = event.clientX - inicio.x, y = event.clientY - inicio.y;
        if (inicio.modo === 'Pan') { transformacion.x = inicio.estado.x + x; transformacion.y = inicio.estado.y + y; }
        else if (inicio.modo === 'Zoom') transformacion.zoom = Math.max(0.1, Math.min(20, inicio.estado.zoom * Math.exp(-y / 150)));
        else { brillo.value = Math.max(25, Math.min(250, inicio.brillo - y)); contraste.value = Math.max(25, Math.min(300, inicio.contraste + x)); }
        renderImagen();
    });
    ['pointerup', 'pointercancel'].forEach(nombre => raster.addEventListener(nombre, () => inicio = null));
    const resize = new ResizeObserver(() => engine?.resize(true, true));
    resize.observe(root.querySelector('.radiologia-visor-stage'));
    document.addEventListener('visibilitychange', () => { if (document.hidden) detener(); });
    actualizarControles();

    async function cargar() {
        const files = JSON.parse(root.dataset.imagenes);
        files.filter(file => !file.dicom).forEach(file => {
            const key = `imagen-${file.id}`;
            series.set(key, { tipo: 'imagen', file, nombre: file.nombre }); selector.add(new Option(file.nombre, key));
        });
        if (selector.options.length) await mostrarSerie().catch(mostrarError);
        const dicomFiles = files.filter(file => file.dicom);
        if (dicomFiles.length) {
            await coreInit(); loaderInit({ maxWebWorkers: 2 }); await toolsInit();
            engine = new RenderingEngine('radiologia-engine');
            engine.enableElement({ viewportId: 'radiologia-stack', type: Enums.ViewportType.STACK, element });
            viewport = engine.getViewport('radiologia-stack'); group = ToolGroupManager.createToolGroup('radiologia-tools');
            herramientas.forEach(tool => { addTool(tool); group.addTool(tool.toolName); }); group.addViewport('radiologia-stack', engine.id);
            group.setToolActive(StackScrollTool.toolName, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Wheel }] });
            const dicoms = new Map();
            for (const file of dicomFiles) {
                try {
                    const response = await fetch(file.url, { credentials: 'same-origin' });
                    if (!response.ok) throw new Error(`No se pudo descargar el estudio (${response.status}).`);
                    const buffer = await response.arrayBuffer(); if (destruido) return;
                    const data = dicomParser.parseDicom(new Uint8Array(buffer));
                    const key = data.string('x0020000e') || 'serie-sin-identificador';
                    const imageId = wadouri.fileManager.add(new Blob([buffer], { type: 'application/dicom' })); fileIds.push(imageId);
                    const count = Math.max(1, parseInt(data.string('x00280008') || '1', 10)), entries = dicoms.get(key) || [];
                    for (let frame = 0; frame < count; frame++) entries.push({ imageId: count > 1 ? `${imageId}?frame=${frame + 1}` : imageId, instance: parseInt(data.string('x00200013') || '0', 10), frame });
                    dicoms.set(key, entries);
                } catch (failure) { status.textContent = `${file.nombre}: ${failure.message}`; }
            }
            [...dicoms].forEach(([key, entries], index) => {
                entries.sort((a, b) => a.instance - b.instance || a.frame - b.frame);
                const nombre = `Serie ${index + 1} · ${entries.length} cortes`;
                series.set(key, { tipo: 'dicom', entries, nombre }); selector.add(new Option(nombre, key));
            });
            if (!actual && dicoms.size) {
                selector.value = dicoms.keys().next().value;
                await mostrarSerie();
            }
        }
        if (!files.length) status.textContent = 'El estudio todavía no tiene imágenes. Puede agregar archivos desde el formulario.';
    }
    cargar().catch(failure => status.textContent = `No se pudo abrir el estudio: ${failure.message}`);
    window.addEventListener('pagehide', event => {
        detener(); if (event.persisted) return;
        destruido = true; resize.disconnect(); limpiarPantalla();
        ToolGroupManager.destroyToolGroup('radiologia-tools'); engine?.destroy();
        fileIds.forEach(id => wadouri.fileManager.remove(Number(id.split(':')[1])));
    });
}
