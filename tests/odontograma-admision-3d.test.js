import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import {
    actualizarPieza, aplicarEstadoModelo, esVisibleEnEscena, identificarPieza,
    limpiarDenticion, piezaAusente, piezasDeDenticion, prepararModeloAdmision, pintarMaterial, posicionEtiquetaEncia,
} from '../public/js/odontograma/admision.js';

const catalogo = { caries: { color: '#dc2626' }, obturado: { color: '#2563eb' } };

test('el clic en una marca alterna igual que el 2D y cambiar de color conserva las otras condiciones', () => {
    const datos = { '16': { o: 'caries', marcas: [{ estado: 'rotacion', color: 'rojo' }, { estado: 'corona', color: 'azul' }] } };
    const quitado = actualizarPieza(datos, { pieza: '16', modo: 'marca', estado: 'rotacion', color: 'rojo', alternar: true });
    assert.deepEqual(quitado['16'], { o: 'caries', marcas: [{ estado: 'corona', color: 'azul' }] });
    const cambiado = actualizarPieza(datos, { pieza: '16', modo: 'marca', estado: 'rotacion', color: 'azul', alternar: true });
    assert.deepEqual(cambiado['16'].marcas, [{ estado: 'corona', color: 'azul' }, { estado: 'rotacion', color: 'azul' }]);
});

test('los hallazgos se pintan opacos aunque el material original tenga textura, transparencia y transmisión', () => {
    const material = new THREE.MeshPhysicalMaterial({ transmission: 0.8, transparent: true, opacity: 0.2, vertexColors: true, map: new THREE.Texture(), alphaMap: new THREE.Texture(), metalness: 0.8 });
    pintarMaterial(material, '#dc2626');
    assert.equal(material.color.getHexString(), 'dc2626');
    assert.equal(material.opacity, 1);
    assert.equal(material.transparent, false);
    assert.equal(material.transmission, 0);
    assert.equal(material.map, null);
    assert.equal(material.alphaMap, null);
    assert.equal(material.vertexColors, false);
    assert.equal(material.depthWrite, true);
});

test('pintar una cara conserva otras caras, piezas y múltiples marcas sin mutar los datos de origen', () => {
    const datos = { '16': { m: 'obturado', marcas: [{ estado: 'rotacion', color: 'azul' }] }, '55': { v: 'sellante' } };
    const resultado = actualizarPieza(datos, { pieza: '16', cara: 'o', estado: 'caries' });
    assert.deepEqual(resultado, { '16': { m: 'obturado', o: 'caries', marcas: [{ estado: 'rotacion', color: 'azul' }] }, '55': { v: 'sellante' } });
    assert.equal(datos['16'].o, undefined);
    const conCorona = actualizarPieza(resultado, { pieza: '16', modo: 'marca', estado: 'corona_temporal', color: 'rojo' });
    assert.deepEqual(conCorona['16'].marcas, [{ estado: 'rotacion', color: 'azul' }, { estado: 'corona_temporal', color: 'rojo' }]);
    const cambiado = actualizarPieza(conCorona, { pieza: '16', modo: 'marca', estado: 'corona_temporal', color: 'azul' });
    assert.deepEqual(cambiado['16'].marcas, [{ estado: 'rotacion', color: 'azul' }, { estado: 'corona_temporal', color: 'azul' }]);
    assert.deepEqual(actualizarPieza(cambiado, { pieza: '16', modo: 'marca', estado: 'corona_temporal', quitar: true }), resultado);
});

test('extraer y restaurar una pieza utiliza el mismo estado ausente del 2D', () => {
    const extraido = actualizarPieza({ '16': { o: 'caries' }, '24': { m: 'obturado' } }, { pieza: '16', modo: 'marca', estado: 'ausente', color: 'rojo' });
    assert.equal(piezaAusente(extraido['16']), true);
    assert.equal(piezaAusente({ o: 'ausente' }), true);
    const restaurado = actualizarPieza(extraido, { pieza: '16', modo: 'borrar' });
    assert.equal(piezaAusente(restaurado['16']), false);
    assert.deepEqual(restaurado, { '24': { m: 'obturado' } });
});

test('limpiar una dentición conserva los hallazgos de la otra', () => {
    const datos = { '16': { o: 'caries' }, '55': { v: 'sellante' } };
    assert.equal(piezasDeDenticion('adulto').length, 32);
    assert.equal(piezasDeDenticion('infantil').length, 20);
    assert.equal(piezasDeDenticion('mixto').length, 52);
    assert.deepEqual(limpiarDenticion(datos, 'adulto'), { '55': { v: 'sellante' } });
    assert.deepEqual(limpiarDenticion(datos, 'infantil'), { '16': { o: 'caries' } });
    assert.deepEqual(limpiarDenticion(datos, 'mixto'), {});
});

test('el GLB exportado permite pintar cada cara, seleccionar ausentes y cambiar de dentición', async () => {
    const archivo = readFileSync(new URL('../public/models/odontograma.glb', import.meta.url));
    const buffer = archivo.buffer.slice(archivo.byteOffset, archivo.byteOffset + archivo.byteLength);
    const loader = new GLTFLoader();
    // Node does not decode browser images; keep the exported geometry and metadata intact.
    loader.register(() => ({ name: 'TestTextures', loadTexture: () => Promise.resolve(new THREE.Texture()) }));
    const gltf = await loader.parseAsync(buffer, '');
    const indice = prepararModeloAdmision(gltf.scene);
    assert.equal(indice.dientes.size, 52);
    assert.equal(indice.superficies.length, 260);
    assert.equal(indice.alveolos.size, 52);
    const o16 = indice.superficies.find(objeto => objeto.userData.fdi === '16' && objeto.userData.surface === 'O');
    const m16 = indice.superficies.find(objeto => objeto.userData.fdi === '16' && objeto.userData.surface === 'M');
    const o17 = indice.superficies.find(objeto => objeto.userData.fdi === '17' && objeto.userData.surface === 'O');
    const original17 = o17.material.color.getHexString();
    const original16 = o16.material.color.getHexString();
    const encias16 = posicionEtiquetaEncia(indice, '16');
    const origen16 = indice.dientes.get('16').getWorldPosition(new THREE.Vector3());
    assert.ok(Math.abs(encias16.y - origen16.y - 0.002) < 1e-8);
    const encias46 = posicionEtiquetaEncia(indice, '46');
    const origen46 = indice.dientes.get('46').getWorldPosition(new THREE.Vector3());
    assert.ok(Math.abs(encias46.y - origen46.y + 0.002) < 1e-8);
    assert.deepEqual(identificarPieza(o16), { pieza: '16', cara: 'o' });
    aplicarEstadoModelo(indice, { '16': { o: 'caries', m: 'obturado' } }, catalogo);
    assert.equal(o16.material.color.getHexString(), 'dc2626');
    assert.equal(m16.material.color.getHexString(), '2563eb');
    assert.equal(o16.material.opacity, 1);
    assert.equal(o16.material.transparent, false);
    assert.equal(o16.material.transmission, 0);
    assert.equal(o17.material.color.getHexString(), original17);
    assert.notEqual(o16.material, o17.material);
    assert.equal(esVisibleEnEscena(indice.dientes.get('55')), false);
    assert.equal(esVisibleEnEscena(indice.dientes.get('16')), true);
    aplicarEstadoModelo(indice, { '16': { marcas: [{ estado: 'ausente', color: 'rojo' }] } }, catalogo);
    assert.equal(esVisibleEnEscena(o16), false);
    assert.equal(esVisibleEnEscena(indice.alveolos.get('16')), true);
    assert.deepEqual(identificarPieza(indice.alveolos.get('16')), { pieza: '16', cara: null });
    aplicarEstadoModelo(indice, {}, catalogo, { denticion: 'infantil' });
    assert.equal(indice.dientes.get('16').visible, true);
    assert.equal(esVisibleEnEscena(o16), false);
    assert.equal(esVisibleEnEscena(indice.dientes.get('55')), true);
    aplicarEstadoModelo(indice, {}, catalogo, { denticion: 'mixto', raices: true });
    assert.equal(esVisibleEnEscena(o16), true);
    assert.equal(esVisibleEnEscena(indice.alveolos.get('16')), false);
    assert.equal(o16.material.color.getHexString(), original16);
    assert.equal(esVisibleEnEscena(indice.dientes.get('55')), true);
    assert.ok(indice.raices.every(objeto => objeto.visible));
    assert.ok(indice.accesorios.size > 0);
});
