import { RenderingEngine, Enums, init as coreInit } from '@cornerstonejs/core';
import { init as loaderInit, wadouri } from '@cornerstonejs/dicom-image-loader';
import { init as toolsInit, addTool, ToolGroupManager, WindowLevelTool, ZoomTool, PanTool, StackScrollTool, LengthTool, Enums as ToolEnums } from '@cornerstonejs/tools';
import dicomParser from 'dicom-parser';

const root = document.getElementById('visor-radiologia');
const status = document.getElementById('dicom-estado');
const element = document.getElementById('dicom-viewport');
const slider = document.getElementById('dicom-corte');
const selector = document.getElementById('dicom-serie');
let engine, group, viewport, active = WindowLevelTool.toolName;
const fileIds = [];
const series = new Map();

async function cargar() {
    const files = JSON.parse(root.dataset.imagenes).filter(file => file.dicom);
    if (!files.length) {
        status.textContent = 'Sin archivos DICOM. Las radiografías JPG/PNG se muestran debajo.';
        root.querySelectorAll('button,select,input').forEach(control => control.disabled = true);
        element.style.display = 'none';
        return;
    }
    await coreInit();
    loaderInit({ maxWebWorkers: 2 });
    await toolsInit();
    engine = new RenderingEngine('radiologia-engine');
    engine.enableElement({ viewportId: 'radiologia-stack', type: Enums.ViewportType.STACK, element });
    viewport = engine.getViewport('radiologia-stack');
    group = ToolGroupManager.createToolGroup('radiologia-tools');
    [WindowLevelTool, ZoomTool, PanTool, StackScrollTool, LengthTool].forEach(tool => { addTool(tool); group.addTool(tool.toolName); });
    group.addViewport('radiologia-stack', engine.id);
    group.setToolActive(active, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Primary }] });
    group.setToolActive(StackScrollTool.toolName, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Wheel }] });
    for (const file of files) {
        status.textContent = 'Leyendo ' + file.nombre;
        const response = await fetch(file.url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('No se pudo descargar el estudio (' + response.status + ').');
        const buffer = await response.arrayBuffer();
        const data = dicomParser.parseDicom(new Uint8Array(buffer));
        const key = data.string('x0020000e') || 'serie-sin-identificador';
        const imageId = wadouri.fileManager.add(new Blob([buffer], { type: 'application/dicom' }));
        fileIds.push(imageId);
        const count = Math.max(1, parseInt(data.string('x00280008') || '1', 10));
        const entries = series.get(key) || [];
        for (let frame = 0; frame < count; frame++) entries.push({ imageId: count > 1 ? imageId + '?frame=' + frame : imageId, instance: parseInt(data.string('x00200013') || '0', 10), frame });
        series.set(key, entries);
    }
    [...series.keys()].forEach((key, index) => selector.add(new Option('Serie ' + (index + 1) + ' · ' + series.get(key).length + ' cortes', key)));
    await mostrarSerie();
}

async function mostrarSerie() {
    const entries = series.get(selector.value) || [];
    entries.sort((a, b) => a.instance - b.instance || a.frame - b.frame);
    await viewport.setStack(entries.map(entry => entry.imageId));
    viewport.render();
    slider.max = Math.max(0, entries.length - 1); slider.value = 0; slider.disabled = entries.length < 2;
    status.textContent = entries.length + ' cortes cargados.';
}

if (root) {
    element.addEventListener('contextmenu', event => event.preventDefault());
    root.querySelectorAll('[data-tool]').forEach(button => button.addEventListener('click', () => {
        if (!group) return;
        group.setToolPassive(active); active = button.dataset.tool;
        group.setToolActive(active, { bindings: [{ mouseButton: ToolEnums.MouseBindings.Primary }] });
        root.querySelectorAll('[data-tool]').forEach(control => control.setAttribute('aria-pressed', String(control === button)));
    }));
    selector.addEventListener('change', () => mostrarSerie().catch(error => status.textContent = error.message));
    slider.addEventListener('input', async () => {
        try { await viewport.setImageIdIndex(Number(slider.value)); viewport.render(); }
        catch (error) { status.textContent = error.message; }
    });
    element.addEventListener(Enums.Events.STACK_NEW_IMAGE, event => { slider.value = event.detail.imageIdIndex; });
    document.getElementById('dicom-invertir').addEventListener('click', () => { if (viewport) { viewport.setProperties({ invert: !viewport.getProperties().invert }); viewport.render(); } });
    document.getElementById('dicom-reset').addEventListener('click', () => { if (viewport) { viewport.resetCamera(); viewport.resetProperties(); viewport.render(); } });
    const resize = new ResizeObserver(() => engine?.resize()); resize.observe(element);
    window.addEventListener('pagehide', () => { resize.disconnect(); ToolGroupManager.destroyToolGroup('radiologia-tools'); engine?.destroy(); fileIds.forEach(id => wadouri.fileManager.remove(Number(id.split(':')[1]))); });
    cargar().catch(error => { status.textContent = 'No se pudo abrir el DICOM: ' + error.message; });
}
