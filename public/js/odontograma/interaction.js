// ============================================================
// INTERACTION — Raycaster, hover, click, etiquetas FDI
// ============================================================
import * as THREE from 'three';

export function crearInteraccion({ canvas, camera, dientesMap }) {
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();
    let hoveredGroup = null;
    let pointerStart = null;
    let arrastrando = false;

    const onPointerDown = e => {
        pointerStart = { x: e.clientX, y: e.clientY };
        arrastrando = false;
    };

    const onPointerMove = e => {
        if (e.buttons && pointerStart
            && Math.hypot(e.clientX - pointerStart.x, e.clientY - pointerStart.y) > 5) {
            arrastrando = true;
        }
    };

    const getRootGroup = (obj) => {
        while (obj && !obj.userData.fdi) obj = obj.parent;
        return obj;
    };

    const updateMouse = (e) => {
        const rect = canvas.getBoundingClientRect();
        mouse.x = ((e.clientX - rect.left) / rect.width)  * 2 - 1;
        mouse.y = -((e.clientY - rect.top)  / rect.height) * 2 + 1;
    };

    const onMouseMove = (e) => {
        updateMouse(e);
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects(Object.values(dientesMap), true);

        let found = null;
        for (const h of hits) {
            if (!h.object.userData.esEtiqueta) {
                found = getRootGroup(h.object);
                break;
            }
        }

        if (found !== hoveredGroup) {
            if (hoveredGroup) hoveredGroup.scale.setScalar(1);
            hoveredGroup = found;
            if (hoveredGroup) {
                hoveredGroup.scale.setScalar(1.08);
                canvas.style.cursor = 'pointer';
            } else {
                canvas.style.cursor = 'default';
            }
        }
    };

    const onMouseLeave = () => {
        if (hoveredGroup) {
            hoveredGroup.scale.setScalar(1);
            hoveredGroup = null;
        }
        canvas.style.cursor = 'default';
    };

    const onSelect = (callback) => (e) => {
        if (arrastrando) return;
        updateMouse(e);
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects(Object.values(dientesMap), true);

        for (const h of hits) {
            if (h.object.userData.esEtiqueta) continue;
            const rootGroup = getRootGroup(h.object);
            if (rootGroup?.userData.fdi) {
                callback(rootGroup);
                return;
            }
        }
    };

    return { onMouseMove, onMouseLeave, onSelect, onPointerDown, onPointerMove };
}

export function crearEtiquetaFDI(texto) {
    const c = document.createElement('canvas');
    c.width = c.height = 128;
    const ctx = c.getContext('2d');

    ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
    ctx.beginPath();
    ctx.arc(64, 64, 50, 0, Math.PI * 2);
    ctx.fill();

    ctx.strokeStyle = 'rgba(120, 180, 255, 0.9)';
    ctx.lineWidth = 4;
    ctx.stroke();

    ctx.fillStyle = '#e0f2ff';
    ctx.font = 'bold 56px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(texto, 64, 68);

    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 4;

    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: tex, transparent: true, depthTest: true,
        depthWrite: false, sizeAttenuation: true,
    }));
    sprite.scale.set(0.65, 0.65, 0.65);
    sprite.userData.esEtiqueta = true;
    return sprite;
}
