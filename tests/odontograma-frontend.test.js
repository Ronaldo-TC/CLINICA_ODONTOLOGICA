import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';
import * as THREE from 'three';
import { FDI_POS_ADULTO, FDI_POS_INFANTIL, aplicarRotacionFDI } from '../public/js/odontograma/constants.js';
import { renderLista, renderResumen } from '../public/js/odontograma/ui.js';
import { guardarHallazgos } from '../public/js/odontograma/api.js';
import { crearInteraccion } from '../public/js/odontograma/interaction.js';

const originalDocument = globalThis.document;
const originalFetch = globalThis.fetch;

afterEach(() => {
    globalThis.document = originalDocument;
    globalThis.fetch = originalFetch;
});

class Element {
    children = [];
    style = {};
    className = '';
    content = '';

    append(...children) {
        this.children.push(...children);
    }

    replaceChildren(...children) {
        this.children = children;
        this.content = '';
    }

    set textContent(value) {
        this.replaceChildren();
        this.content = String(value);
    }

    get textContent() {
        return this.content + this.children.map(child => child.textContent).join('');
    }

    set innerHTML(value) {
        assert.fail(`Los datos clínicos deben mostrarse como texto: ${value}`);
    }
}

function crearDocumento() {
    const elements = new Map([
        ['listaHallazgos', new Element()],
        ['resumen-grid', new Element()],
    ]);
    globalThis.document = {
        getElementById: id => elements.get(id),
        createElement: () => new Element(),
        createTextNode: value => {
            const node = new Element();
            node.textContent = value;
            return node;
        },
        querySelector: () => ({ content: 'csrf-test' }),
    };
    return elements;
}

test('las 32 piezas permanentes y 20 temporales tienen identificadores FDI distintos', () => {
    const adultas = Object.keys(FDI_POS_ADULTO);
    const infantiles = Object.keys(FDI_POS_INFANTIL);
    assert.equal(adultas.length, 32);
    assert.equal(infantiles.length, 20);
    assert.equal(new Set([...adultas, ...infantiles]).size, 52);
    for (const pieza of adultas) {
        assert.match(pieza, /^[1-4][1-8]$/);
    }
    for (const pieza of infantiles) {
        assert.match(pieza, /^[5-8][1-5]$/);
    }
});

test('las coronas superiores apuntan abajo y las inferiores arriba con vestibular hacia fuera', () => {
    for (const [pieza, [x, , z]] of Object.entries({ ...FDI_POS_ADULTO, ...FDI_POS_INFANTIL })) {
        const group = new THREE.Group();
        aplicarRotacionFDI(group, pieza);
        const corona = new THREE.Vector3(0, 1, 0).applyQuaternion(group.quaternion);
        const vestibular = new THREE.Vector3(0, 0, 1).applyQuaternion(group.quaternion);
        const superior = [1, 2, 5, 6].includes(Math.floor(Number(pieza) / 10));
        assert.ok(Math.abs(corona.y - (superior ? -1 : 1)) < 1e-10, pieza);
        assert.ok(vestibular.dot(new THREE.Vector3(x, 0, z).normalize()) > 0.999, pieza);
    }
});

test('hallazgos y resumen muestran caracteres HTML como texto sin interpretar etiquetas', () => {
    const elements = crearDocumento();
    const condicion = '<img src=x onerror=alert(1)>';
    const hallazgos = {
        '11': [{ condicion, caras: 'O', color: '#ff0000' }],
        '12': [{ condicion, caras: '' }],
    };
    renderLista(hallazgos);
    renderResumen(hallazgos);
    assert.equal(elements.get('listaHallazgos').textContent, `11: ● ${condicion} (O)12: ● ${condicion}`);
    assert.equal(elements.get('resumen-grid').textContent, `2${condicion}`);
    renderLista({});
    renderResumen({});
    assert.equal(elements.get('listaHallazgos').textContent, 'Sin hallazgos registrados.');
    assert.equal(elements.get('resumen-grid').textContent, 'Sin datos');
});

test('guardar utiliza la URL de Laravel, la sesión y el token CSRF', async () => {
    crearDocumento();
    const payload = { hallazgos: [{ id: 7, pieza_dental: '51', condicion: 'caries', caras: 'O' }] };
    let request;
    globalThis.fetch = async (url, options) => {
        request = { url, options };
        return { ok: true, json: async () => ({ success: true, data: payload }) };
    };
    assert.equal((await guardarHallazgos('/clinica/odontogramas/4/hallazgos', payload)).success, true);
    assert.equal(request.url, '/clinica/odontogramas/4/hallazgos');
    assert.equal(request.options.credentials, 'same-origin');
    assert.equal(request.options.headers['X-CSRF-TOKEN'], 'csrf-test');
    assert.equal(request.options.method, 'POST');
    assert.deepEqual(JSON.parse(request.options.body), payload);
});

test('los errores de validación del servidor llegan al usuario', async () => {
    crearDocumento();
    globalThis.fetch = async () => ({
        ok: false,
        status: 422,
        json: async () => ({ errors: { 'hallazgos.0.pieza_dental': ['La pieza dental no es válida.'] } }),
    });
    await assert.rejects(guardarHallazgos('/odontogramas/4/hallazgos', {}), /La pieza dental no es válida/);
});

test('arrastrar para rotar no abre el editor y un click posterior sí selecciona', () => {
    const group = new THREE.Group();
    group.userData.fdi = '11';
    group.add(new THREE.Mesh(new THREE.BoxGeometry(1, 1, 1), new THREE.MeshBasicMaterial()));
    group.updateMatrixWorld(true);
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.z = 5;
    camera.updateMatrixWorld(true);
    const canvas = { getBoundingClientRect: () => ({ left: 0, top: 0, width: 100, height: 100 }) };
    const interaction = crearInteraccion({ canvas, camera, dientesMap: { '11': group } });
    let selected = null;
    const select = interaction.onSelect(tooth => { selected = tooth; });
    interaction.onPointerDown({ clientX: 50, clientY: 50 });
    interaction.onPointerMove({ clientX: 80, clientY: 50, buttons: 1 });
    select({ clientX: 50, clientY: 50 });
    assert.equal(selected, null);
    interaction.onPointerDown({ clientX: 50, clientY: 50 });
    select({ clientX: 50, clientY: 50 });
    assert.equal(selected, group);
});
