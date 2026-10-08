// ============================================================
// INDEX — Orquestador principal del odontograma 3D
// ============================================================
import * as THREE from 'three';
import {
    FDI_POS_ADULTO, FDI_POS_INFANTIL,
    OFFSET_ADULTO_X, OFFSET_INFANTIL_X,
    COLORES, PRIORIDAD_HALLAZGOS, aplicarRotacionFDI,
} from './constants.js';
import { cargarTexturasEncia } from './materials.js';
import { precargarModelos, crearDientePorFDI, crearEncia } from './geometry.js';
import { crearEscena, configurarLuces, crearSuelo } from './scene.js';
import { crearInteraccion, crearEtiquetaFDI } from './interaction.js';
import { renderLista, renderResumen } from './ui.js';
import { guardarHallazgos } from './api.js';

// ---------------- Datos del DOM ----------------
const root = document.getElementById('odontograma-root');
const puedeEditar = root?.dataset.canEdit === '1';
let hallazgosState = {};
let layoutState = {};

let dientesMap = {};
let ctx = null;
let guardando = false;

function normalizarHallazgos(hallazgos = {}) {
    if (!Array.isArray(hallazgos)) {
        return Object.fromEntries(Object.entries(hallazgos).map(([pieza, lista]) => [
            pieza, lista.map(hallazgo => ({ ...hallazgo, pieza_dental: pieza })),
        ]));
    }

    const map = {};
    hallazgos.forEach(hallazgo => {
        const pieza = String(hallazgo.pieza_dental);
        if (!map[pieza]) map[pieza] = [];
        map[pieza].push({ ...hallazgo, pieza_dental: pieza });
    });
    return map;
}

// ---------------- Aplicar color a un diente ----------------
function aplicarColor(group, colorHex, esAusente, condicion) {
    const color = new THREE.Color(colorHex);

    group.traverse((child) => {
        if (!child.isMesh || !child.userData.esCorona) return;

        if (esAusente) {
            child.material.color.set(0x555555);
            child.material.transparent = true;
            child.material.opacity = 0.18;
            child.material.emissive = new THREE.Color(0x000000);
            child.material.emissiveIntensity = 0;
            child.material.roughness = 0.9;
            child.material.metalness = 0;
        } else if (condicion === 'sano' || !condicion) {
            child.material.color.set(0xf5f0e6);
            child.material.emissive = new THREE.Color(0x000000);
            child.material.emissiveIntensity = 0;
            child.material.transparent = false;
            child.material.opacity = 1;
            child.material.roughness = 0.28;
            child.material.metalness = 0;
        } else {
            child.material.color.copy(color).lerp(new THREE.Color(0xffffff), 0.4);
            child.material.emissive = color.clone();
            child.material.emissiveIntensity = 0.12;
            child.material.transparent = false;
            child.material.opacity = 1;
            child.material.roughness = condicion === 'corona' ? 0.15 : 0.38;
            child.material.metalness = condicion === 'corona' ? 0.55 : 0.0;
        }
    });
}

function aplicarColoresGuardados() {
    Object.entries(dientesMap).forEach(([pieza, group]) => {
        const lista = hallazgosState[pieza] || [];
        if (!lista.length) {
            aplicarColor(group, COLORES.sano, false, 'sano');
            return;
        }

        let elegido = lista[lista.length - 1];
        for (const cond of PRIORIDAD_HALLAZGOS) {
            const found = lista.find(h => h.condicion === cond);
            if (found) { elegido = found; break; }
        }
        const colorHex = /^#[0-9a-f]{6}$/i.test(elegido.color || '')
            ? elegido.color : COLORES[elegido.condicion] || '#FFFFFF';
        aplicarColor(group, colorHex, elegido.condicion === 'ausente', elegido.condicion);
    });
}

// ---------------- Init ----------------
async function init() {
    if (!root) throw new Error('No se encontraron los datos del odontograma.');
    const datos = JSON.parse(root.dataset.odontoData);
    hallazgosState = normalizarHallazgos(datos?.hallazgos);
    layoutState = datos?.layout_3d ?? {};

    // 1. Precarga async (GLB + texturas). Si no existen, no pasa nada.
    await Promise.all([precargarModelos(), cargarTexturasEncia()]);

    // 2. Escena
    const canvas = document.getElementById('canvas3d');
    ctx = crearEscena(canvas);
    const cameraPosition = Array.isArray(layoutState.camera) ? layoutState.camera : [
        layoutState.camera?.position?.x,
        layoutState.camera?.position?.y,
        layoutState.camera?.position?.z,
    ];
    const cameraTarget = Array.isArray(layoutState.target) ? layoutState.target : [
        layoutState.camera?.lookAt?.x,
        layoutState.camera?.lookAt?.y,
        layoutState.camera?.lookAt?.z,
    ];
    const esVectorValido = vector => Array.isArray(vector)
        && vector.length === 3 && vector.every(Number.isFinite);
    if (esVectorValido(cameraPosition) && esVectorValido(cameraTarget)) {
        ctx.camera.position.fromArray(cameraPosition);
        ctx.controls.target.fromArray(cameraTarget);
        ctx.controls.update();
    }
    configurarLuces(ctx.scene);
    crearSuelo(ctx.scene);

    // 3. Encías
    const enciaInf = crearEncia(true,  OFFSET_INFANTIL_X);
    const enciaAdu = crearEncia(false, OFFSET_ADULTO_X);
    ctx.scene.add(enciaInf.grupo, enciaAdu.grupo);

    // 4. Dientes
    const colocarDiente = (fdi, pos, esInfantil, offsetX) => {
        const [x, y, z] = pos;
        const worldX = x + offsetX;
        const group = crearDientePorFDI(fdi, esInfantil);
        group.position.set(worldX, y, z);

        aplicarRotacionFDI(group, fdi);

        group.userData.fdi = String(fdi);
        group.userData.tipo = esInfantil ? 'infantil' : 'adulto';

        const sprite = crearEtiquetaFDI(fdi);
        sprite.position.set(0, -2.1, 0);
        group.add(sprite);

        ctx.scene.add(group);
        dientesMap[fdi] = group;
    };

    Object.entries(FDI_POS_INFANTIL).forEach(([fdi, pos]) =>
        colocarDiente(fdi, pos, true, OFFSET_INFANTIL_X));
    Object.entries(FDI_POS_ADULTO).forEach(([fdi, pos]) =>
        colocarDiente(fdi, pos, false, OFFSET_ADULTO_X));

    // 5. Interacción
    const interaccion = crearInteraccion({ canvas, camera: ctx.camera, dientesMap });
    canvas.addEventListener('mousemove', interaccion.onMouseMove);
    canvas.addEventListener('mouseleave', interaccion.onMouseLeave);
    canvas.addEventListener('pointerdown', interaccion.onPointerDown);
    canvas.addEventListener('pointermove', interaccion.onPointerMove);
    if (puedeEditar) {
        canvas.addEventListener('click', interaccion.onSelect((rootGroup) => {
            const modal = document.getElementById('modalDiente');
            document.getElementById('piezaLabel').innerText = rootGroup.userData.fdi;
            modal.classList.remove('hidden');
            modal.dataset.fdi = rootGroup.userData.fdi;
            document.getElementById('condicion').value = 'caries';
            document.getElementById('caras').value = '';
        }));
    }

    // 6. Estado inicial UI
    aplicarColoresGuardados();
    renderLista(hallazgosState);
    renderResumen(hallazgosState);
    document.getElementById('loading')?.classList.add('hidden');
    document.getElementById('btnGuardar').disabled = !puedeEditar;

    // 7. Resize + loop
    window.addEventListener('resize', onResize);
    animate();
}

function onResize() {
    if (!ctx) return;
    const canvas = ctx.renderer.domElement;
    const w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    ctx.camera.aspect = w / h;
    ctx.camera.updateProjectionMatrix();
    ctx.renderer.setSize(w, h, false);
}

function animate() {
    requestAnimationFrame(animate);
    if (!ctx) return;
    ctx.controls.update();
    ctx.renderer.render(ctx.scene, ctx.camera);
}

// ---------------- Modal ----------------
document.getElementById('btnAplicar')?.addEventListener('click', () => {
    if (!puedeEditar || guardando) return;
    const modal = document.getElementById('modalDiente');
    const fdi       = modal.dataset.fdi;
    const condicion = document.getElementById('condicion').value;
    const caras     = document.getElementById('caras').value;
    const color     = COLORES[condicion] || '#FFFFFF';

    const group = dientesMap[fdi];
    if (!group) return;

    if (!hallazgosState[fdi]) hallazgosState[fdi] = [];
    const nuevoHallazgo = {
        pieza_dental: fdi, caras, condicion, color, severidad: 'leve',
    };
    const indice = hallazgosState[fdi].findIndex(hallazgo =>
        hallazgo.condicion === condicion && (hallazgo.caras || '') === caras);
    if (indice === -1) {
        hallazgosState[fdi].push(nuevoHallazgo);
    } else {
        hallazgosState[fdi][indice] = { ...hallazgosState[fdi][indice], color };
    }

    aplicarColoresGuardados();
    renderLista(hallazgosState);
    renderResumen(hallazgosState);
    modal.classList.add('hidden');
});

// ---------------- Guardar ----------------
document.getElementById('btnGuardar')?.addEventListener('click', async () => {
    if (!ctx || !puedeEditar || guardando) return;
    const button = document.getElementById('btnGuardar');
    guardando = true;
    button.disabled = true;
    document.getElementById('btnAplicar').disabled = true;
    const payload = {
        layout_3d: {
            ...layoutState,
            camera: ctx.camera.position.toArray(),
            target: ctx.controls.target.toArray(),
        },
        hallazgos: Object.entries(hallazgosState).flatMap(([pieza, lista]) =>
            lista.map(hallazgo => ({ ...hallazgo, pieza_dental: pieza }))),
    };
    try {
        const json = await guardarHallazgos(root.dataset.saveUrl, payload);
        if (json.success && json.data?.hallazgos) {
            hallazgosState = normalizarHallazgos(json.data.hallazgos);
            layoutState = json.data.layout_3d ?? payload.layout_3d;
            aplicarColoresGuardados();
            renderLista(hallazgosState);
            renderResumen(hallazgosState);
        }
        alert(json.success ? 'Guardado correctamente' : 'Error al guardar');
    } catch (err) {
        console.error('Error:', err);
        alert(`No se pudieron guardar los cambios: ${err.message}`);
    } finally {
        guardando = false;
        button.disabled = false;
        document.getElementById('btnAplicar').disabled = false;
    }
});

// ---------------- Reset ----------------
document.getElementById('btnReset')?.addEventListener('click', () => {
    if (!ctx) return;
    ctx.controls.reset();
});

// ---------------- Arrancar ----------------
init().catch(err => {
    console.error('Error en init:', err);
    const loading = document.getElementById('loading');
    if (loading) {
        loading.classList.remove('hidden');
        loading.textContent = `No se pudo cargar el odontograma 3D: ${err.message}`;
    }
    const button = document.getElementById('btnGuardar');
    if (button) button.disabled = true;
});
