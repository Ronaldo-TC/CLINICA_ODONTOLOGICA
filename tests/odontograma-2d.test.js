import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { test } from 'node:test';

const template = readFileSync(new URL('../resources/views/components/partials/odontograma-js.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];
const states = ['sano', 'caries', 'obturado', 'ausente', 'corona', 'endodoncia', 'sellante', 'fractura', 'perno', 'diastema'];

class Element {
    constructor(dataset = {}) {
        this.dataset = dataset;
        this.children = [];
        this.listeners = new Map();
        this.attributes = new Map();
        this.classes = new Set();
        this.classList = { toggle: (name, active) => active ? this.classes.add(name) : this.classes.delete(name), add: name => this.classes.add(name) };
        this.style = {};
        this.value = '';
        this.hidden = false;
        this.events = [];
    }

    append(child) {
        child.parent = this;
        this.children.push(child);
        return child;
    }

    replaceChildren(...children) {
        this.children = [];
        children.forEach(child => this.append(child));
    }

    matches(selector) {
        if (selector.startsWith('#')) return this.id === selector.slice(1);
        if (selector === '.diente-imagen') return this.className === 'diente-imagen';
        const match = selector.match(/^\[data-([a-z-]+)(?:="([^"]+)")?\]$/);
        if (!match) return false;
        const key = match[1].replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
        return key in this.dataset && (match[2] === undefined || this.dataset[key] === match[2]);
    }

    closest(selector) {
        return this.matches(selector) ? this : this.parent?.closest(selector);
    }

    querySelectorAll(selector) {
        return this.children.flatMap(child => [
            ...(child.matches(selector) ? [child] : []),
            ...child.querySelectorAll(selector),
        ]);
    }

    querySelector(selector) {
        return this.querySelectorAll(selector)[0];
    }

    setAttribute(name, value) {
        this.attributes.set(name, value);
    }

    addEventListener(type, handler) {
        this.listeners.set(type, [...(this.listeners.get(type) || []), handler]);
    }

    dispatchEvent(event) {
        this.events.push(event);
        for (const handler of this.listeners.get(event.type) || []) handler(event);
        return true;
    }

    emit(type, target = this, properties = {}) {
        this.dispatchEvent({ type, target, preventDefault() {}, ...properties });
    }
}

function createRoot({ id = 'odontograma', lectura = false } = {}) {
    const root = new Element({
        odontograma: '', lectura: lectura ? '1' : '0', denticion: 'adulto',
        estados: JSON.stringify(Object.fromEntries(states.map(state => [state, { color: '#ffffff', etiqueta: state }]))),
    });
    root.id = id;
    root.append(new Element({ odontogramaInput: '' })).value = '{}';
    root.append(new Element({ odontogramaDenticionInput: '' })).value = 'adulto';
    root.append(new Element({ odontogramaDenticion: '' })).value = 'adulto';
    root.append(new Element({ odontogramaVista: '' })).value = 'frontal';
    root.append(new Element({ odontogramaColor: '' })).value = 'rojo';
    root.append(new Element({ odontogramaLimpiar: '' }));
    for (const state of states) root.append(new Element({ herramienta: state }));
    for (const [denticion, piezas] of [['adulto', ['16', '21']], ['infantil', ['51', '75']]]) {
        const arcada = root.append(new Element({ denticionArcada: denticion }));
        for (const pieza of piezas) {
            const tooth = arcada.append(new Element({ pieza }));
            const image = tooth.append(new Element({ imagenFrontal: `/${pieza}/frontal`, imagenAnatomica: `/${pieza}/anatomica` }));
            image.className = 'diente-imagen';
            image.src = image.dataset.imagenFrontal;
            tooth.append(new Element({ marcas: '' }));
            tooth.append(new Element({ overlay: '' }));
            for (const cara of ['o', 'm', 'd', 'v', 'l']) tooth.append(new Element({ cara }));
        }
    }
    return root;
}

function run(...roots) {
    const document = {
        readyState: 'complete',
        createElement: () => new Element(),
        createElementNS: () => new Element(),
        querySelectorAll: selector => roots.filter(root => root.matches(selector)),
        querySelector: selector => roots.find(root => root.matches(selector)),
    };
    const window = { confirm: () => true };
    vm.runInNewContext(script, {
        document, window, Element,
        CustomEvent: class {
            constructor(type, options) { Object.assign(this, { type }, options); }
        },
    });
    return window.Odontograma;
}

const data = (api, root) => JSON.parse(JSON.stringify(api.get(root)));

test('set carga JSON guardado adulto e infantil y una sola interacción tras cargar varias veces', () => {
    const root = createRoot();
    const api = run(root);
    const previous = { '16': { o: 'caries' }, '51': { v: 'obturado' } };
    for (let index = 0; index < 5; index++) api.set(root, JSON.stringify(previous), 'mixto');
    assert.deepEqual(data(api, root), previous);
    root.emit('click', root.querySelector('[data-pieza="16"]').querySelector('[data-cara="o"]'));
    assert.deepEqual(data(api, root), { '16': { o: 'obturado' }, '51': { v: 'obturado' } });
    assert.equal(root.events.filter(event => event.type === 'odontograma:cambio').length, 1);
});

test('la paleta guarda varias marcas y colores y alterna una marca sin borrar caras ni otros dientes', () => {
    const root = createRoot();
    const api = run(root);
    api.set(root, { '16': { o: 'caries' }, '51': { marcas: [{ estado: 'perno', color: 'azul' }] } }, 'mixto');
    const tooth = root.querySelector('[data-pieza="16"]');
    root.querySelector('[data-herramienta="ausente"]').emit('click');
    root.emit('click', tooth.querySelector('.diente-imagen'));
    assert.ok(tooth.classes.has('es-ausente'));
    assert.equal(tooth.querySelector('[data-overlay]').children.length, 1);
    root.querySelector('[data-odontograma-color]').value = 'azul';
    root.querySelector('[data-herramienta="diastema"]').emit('click');
    root.emit('click', tooth.querySelector('.diente-imagen'));
    assert.deepEqual(data(api, root), { '16': { o: 'caries', marcas: [{ estado: 'ausente', color: 'rojo' }, { estado: 'diastema', color: 'azul' }] }, '51': { marcas: [{ estado: 'perno', color: 'azul' }] } });
    root.emit('click', tooth.querySelector('.diente-imagen'));
    assert.deepEqual(data(api, root)['16'], { o: 'caries', marcas: [{ estado: 'ausente', color: 'rojo' }] });
    const dentition = root.querySelector('[data-odontograma-denticion]');
    dentition.value = 'adulto';
    dentition.emit('change');
    root.querySelector('[data-odontograma-limpiar]').emit('click');
    assert.deepEqual(data(api, root), { '51': { marcas: [{ estado: 'perno', color: 'azul' }] } });
});

test('cargar marcas descarta colores y condiciones desconocidos y conserva datos de denticion mixta', () => {
    const root = createRoot();
    const api = run(root);
    api.set(root, { '16': { marcas: [{ estado: 'fractura', color: 'azul' }, { estado: 'bad', color: 'rojo' }, { estado: 'perno', color: 'bad' }] }, '75': { v: 'corona', marcas: [{ estado: 'diastema', color: 'rojo' }] } });
    assert.equal(api.denticion(root), 'mixto');
    assert.deepEqual(data(api, root), { '16': { marcas: [{ estado: 'fractura', color: 'azul' }] }, '75': { v: 'corona', marcas: [{ estado: 'diastema', color: 'rojo' }] } });
});

test('cambiar dentición preserva hallazgos ocultos y serializa adulto, infantil o mixto', () => {
    const root = createRoot();
    const api = run(root);
    const previous = { '16': { o: 'caries' }, '75': { m: 'sellante' } };
    api.set(root, previous, 'mixto');
    const selector = root.querySelector('[data-odontograma-denticion]');
    for (const denticion of ['infantil', 'adulto', 'mixto']) {
        selector.value = denticion;
        selector.emit('change');
        assert.equal(api.denticion(root), denticion);
        assert.deepEqual(data(api, root), previous);
        for (const arcada of root.querySelectorAll('[data-denticion-arcada]')) {
            assert.equal(arcada.hidden, denticion !== 'mixto' && denticion !== arcada.dataset.denticionArcada);
        }
    }
});

test('vista anatómica cambia las imágenes sin alterar condiciones clínicas', () => {
    const root = createRoot();
    const api = run(root);
    api.set(root, { '51': { o: 'corona' } }, 'infantil');
    const selector = root.querySelector('[data-odontograma-vista]');
    selector.value = 'anatomica';
    selector.emit('change');
    for (const image of root.querySelectorAll('.diente-imagen')) assert.equal(image.src, image.dataset.imagenAnatomica);
    assert.deepEqual(data(api, root), { '51': { o: 'corona' } });
    assert.equal(api.denticion(root), 'infantil');
});

test('solo lectura permite cambiar vista pero rechaza clic, teclado y limpieza por clic derecho', () => {
    const root = createRoot({ lectura: true });
    const api = run(root);
    const previous = { '51': { o: 'caries' } };
    api.set(root, previous, 'infantil');
    const face = root.querySelector('[data-pieza="51"]').querySelector('[data-cara="o"]');
    root.emit('click', face);
    root.emit('keydown', face, { key: 'Enter' });
    root.emit('contextmenu', face);
    assert.deepEqual(data(api, root), previous);
    assert.equal(root.events.filter(event => event.type === 'odontograma:cambio').length, 0);
});

test('teclado edita una cara temporal y clic derecho limpia esa pieza conservando las demás', () => {
    const root = createRoot();
    const api = run(root);
    api.set(root, { '16': { v: 'corona' } }, 'mixto');
    const face = root.querySelector('[data-pieza="51"]').querySelector('[data-cara="m"]');
    root.emit('keydown', face, { key: 'Enter' });
    assert.deepEqual(data(api, root), { '16': { v: 'corona' }, '51': { m: 'caries' } });
    root.emit('contextmenu', face);
    assert.deepEqual(data(api, root), { '16': { v: 'corona' } });
});

test('varios componentes conservan estados independientes y datos inválidos se descartan', () => {
    const first = createRoot({ id: 'first' });
    const second = createRoot({ id: 'second' });
    const api = run(first, second);
    api.set('#first', '{"51":{"o":"caries"},"58":{"o":"caries"},"99":{"v":"corona"},"16":{"z":"caries","o":"<script>"}}');
    api.set('#second', { '21': { l: 'endodoncia' } });
    assert.deepEqual(data(api, first), { '51': { o: 'caries' } });
    assert.equal(api.denticion(first), 'infantil');
    api.limpiar(first);
    assert.deepEqual(data(api, first), {});
    assert.equal(api.denticion(first), 'adulto');
    assert.deepEqual(data(api, second), { '21': { l: 'endodoncia' } });
    api.set(first, 'JSON roto');
    assert.deepEqual(data(api, first), {});
});

test('precargar y limpiar notifica a la vista 3D sin marcar los datos como edición manual', () => {
    const root = createRoot();
    const api = run(root);
    const previous = { '51': { o: 'caries' } };
    api.set(root, previous, 'infantil');
    const actualizado = root.events.find(event => event.type === 'odontograma:actualizado');
    assert.deepEqual(JSON.parse(JSON.stringify(actualizado.detail)), { datos: previous, denticion: 'infantil' });
    api.limpiar(root);
    const ultimo = root.events.filter(event => event.type === 'odontograma:actualizado').at(-1);
    assert.deepEqual(JSON.parse(JSON.stringify(ultimo.detail)), { datos: {}, denticion: 'adulto' });
    assert.equal(root.events.filter(event => event.type === 'odontograma:cambio').length, 0);
});
