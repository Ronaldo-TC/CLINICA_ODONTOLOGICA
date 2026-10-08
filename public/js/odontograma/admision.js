import * as THREE from 'three';

export const CARAS_ADMISION = ['o', 'm', 'd', 'v', 'l'];
export const PIEZAS_ADMISION = Array.from({ length: 8 }, (_, index) => index + 1)
    .flatMap(cuadrante => Array.from({ length: cuadrante <= 4 ? 8 : 5 }, (_, index) => String(cuadrante * 10 + index + 1)));

export function piezasDeDenticion(denticion) {
    return PIEZAS_ADMISION.filter(pieza => denticion === 'mixto' || (Number(pieza) < 50 ? 'adulto' : 'infantil') === denticion);
}

export function actualizarPieza(datos, { pieza, cara, estado, modo = 'cara', color = 'rojo', quitar = false }) {
    const actualizado = structuredClone(datos);
    if (!PIEZAS_ADMISION.includes(String(pieza))) return actualizado;
    if (modo === 'borrar' || (modo === 'marca' && estado === 'sano')) {
        delete actualizado[pieza];
        return actualizado;
    }
    const diente = actualizado[pieza] || {};
    if (modo === 'marca') {
        const marcas = (diente.marcas || []).filter(marca => marca.estado !== estado);
        if (!quitar) marcas.push({ estado, color });
        if (marcas.length) diente.marcas = marcas;
        else delete diente.marcas;
    } else if (CARAS_ADMISION.includes(cara)) {
        if (estado === 'sano') delete diente[cara];
        else diente[cara] = estado;
    }
    if (Object.keys(diente).length) actualizado[pieza] = diente;
    else delete actualizado[pieza];
    return actualizado;
}

export function limpiarDenticion(datos, denticion) {
    return Object.fromEntries(Object.entries(datos).filter(([pieza]) => !piezasDeDenticion(denticion).includes(pieza)));
}

export function piezaAusente(diente = {}) {
    return CARAS_ADMISION.some(cara => diente[cara] === 'ausente') || (diente.marcas || []).some(marca => marca.estado === 'ausente');
}

export function identificarPieza(objeto) {
    let pieza = null;
    let cara = null;
    for (let actual = objeto; actual; actual = actual.parent) {
        pieza ||= actual.userData.fdi ? String(actual.userData.fdi) : null;
        cara ||= actual.userData.surface?.toLowerCase();
        if (pieza && cara) break;
    }
    return PIEZAS_ADMISION.includes(pieza) ? { pieza, cara: CARAS_ADMISION.includes(cara) ? cara : null } : null;
}

export function prepararModeloAdmision(modelo) {
    const indice = { modelo, dientes: new Map(), superficies: [], raices: [], alveolos: new Map(), denticiones: [], encias: [], huesos: [], seleccionables: [], accesorios: new Map() };
    modelo.traverse(objeto => {
        const datos = objeto.userData;
        if (datos.default_visible === false) objeto.visible = false;
        if (datos.part === 'tooth') indice.dientes.set(String(datos.fdi), objeto);
        if (datos.part === 'dentition') indice.denticiones.push(objeto);
        if (datos.part === 'gingiva') indice.encias.push(objeto);
        if (datos.part === 'skull') indice.huesos.push(objeto);
        if (datos.part === 'root') indice.raices.push(objeto);
        if (datos.part === 'socket') indice.alveolos.set(String(datos.fdi), objeto);
        if (datos.part === 'accessory_template') indice.accesorios.set(datos.accessory, objeto);
        if (objeto.isMesh) {
            const originales = (Array.isArray(objeto.material) ? objeto.material : [objeto.material]);
            objeto.userData.materialesOriginales = originales;
            const independientes = originales.map(material => material.clone());
            objeto.material = Array.isArray(objeto.material) ? independientes : independientes[0];
            if (datos.surface) indice.superficies.push(objeto);
            if (datos.selectable) indice.seleccionables.push(objeto);
        }
    });
    return indice;
}

export function aplicarEstadoModelo(indice, datos, catalogo, { denticion = 'adulto', raices = false, encias = true, huesos = false, seleccion = null } = {}) {
    indice.denticiones.forEach(objeto => objeto.visible = denticion === 'mixto' || objeto.userData.dentition === denticion);
    indice.encias.forEach(objeto => objeto.visible = encias);
    indice.huesos.forEach(objeto => objeto.visible = huesos);
    indice.raices.forEach(objeto => objeto.visible = raices);
    indice.dientes.forEach((objeto, pieza) => objeto.visible = !piezaAusente(datos[pieza]));
    indice.alveolos.forEach((objeto, pieza) => objeto.visible = piezaAusente(datos[pieza]));
    indice.superficies.forEach(objeto => {
        const pieza = String(objeto.userData.fdi);
        const cara = objeto.userData.surface.toLowerCase();
        const diente = datos[pieza] || {};
        const estado = diente[cara] || 'sano';
        const marca = (diente.marcas || []).find(item => ['corona', 'corona_temporal', 'endodoncia', 'discromia'].includes(item.estado));
        const color = estado !== 'sano' ? catalogo[estado]?.color || '#dc2626' : marca ? (marca.color === 'azul' ? '#2563eb' : '#dc2626') : null;
        const materiales = Array.isArray(objeto.material) ? objeto.material : [objeto.material];
        materiales.forEach((material, index) => {
            const teniaColoresVertices = material.vertexColors;
            material.copy(objeto.userData.materialesOriginales[index]);
            if (color) {
                material.color.set(color);
                material.vertexColors = false;
                material.transparent = false;
                material.opacity = 1;
            }
            if (seleccion?.pieza === pieza && (!seleccion.cara || seleccion.cara === cara) && material.emissive) {
                material.emissive.set('#0e7490');
                material.emissiveIntensity = 0.25;
            }
            if (teniaColoresVertices !== material.vertexColors) material.needsUpdate = true;
        });
    });
    indice.modelo.updateMatrixWorld(true);
}

export function esVisibleEnEscena(objeto) {
    for (let actual = objeto; actual; actual = actual.parent) {
        if (!actual.visible) return false;
    }
    return true;
}

export function limitesDenticion(indice, denticion) {
    const limites = new THREE.Box3();
    const piezas = piezasDeDenticion(denticion);
    [...indice.superficies, ...indice.alveolos.values()].forEach(objeto => {
        if (piezas.includes(String(objeto.userData.fdi))) limites.union(new THREE.Box3().setFromObject(objeto));
    });
    return limites;
}
