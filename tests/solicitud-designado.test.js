import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../public/js/solicitud-designado.js', import.meta.url), 'utf8');

class Element {
    constructor() {
        this.value = '';
        this.textContent = '';
        this.disabled = false;
        this.dataset = {};
        this.children = [];
        this.listeners = new Map();
        const classes = new Set();
        this.classList = {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)};
    }
    addEventListener(name, handler) { this.listeners.set(name, handler); }
    emit(name) { return this.listeners.get(name)?.({target: this, preventDefault() {}}); }
    replaceChildren(...children) { this.children = children; this.value = String(children[0]?.value || ''); }
    add(option) { this.children.push(option); }
    appendChild(element) { this.children.push(element); }
    insertRow() { const row = new Element(); this.children.push(row); return row; }
    insertCell() { return this.insertRow(); }
    setAttribute() {}
    querySelectorAll() { return this.children.flatMap(child => child.dataset?.insumo ? [child] : child.querySelectorAll?.() || []); }
    closest() { return null; }
}

function harness(fetch) {
    const ids = ['formSolicitudDesignado', 'modalSolicitudDesignado', 'designado-tratamiento', 'designado-materiales', 'designado-error', 'designado-guardar', 'designado-clinica', 'designado-paciente-select', 'designado-elegir-paciente', 'designado-paciente', 'designado-pieza'];
    const elements = Object.fromEntries(ids.map(id => [id, new Element()]));
    const form = elements.formSolicitudDesignado;
    form.dataset = {catalogo: '/solicitudes-insumos/catalogo-designado', store: '/solicitudes-insumos'};
    form.reset = () => { Object.values(elements).forEach(element => {element.value = '';}); };
    form.querySelector = () => ({value: 'csrf'});
    const window = {dispatchEvent() {}, SolicitudesLista: {toast() {}}};
    runInNewContext(source, {
        document: {getElementById: id => elements[id], addEventListener() {}, body: new Element(), createElement: () => new Element(), querySelector: () => null},
        window, fetch, URLSearchParams,
        Option: class {constructor(label, value) {this.text = label; this.value = String(value);}},
        Event: class {constructor(type) {this.type = type;}},
    });
    return {elements, form, window, inputs: () => elements['designado-materiales'].querySelectorAll()};
}

function response(clinic = 1) {
    return {ok: true, json: async () => ({
        clinicas: [{id: 1, nombre: 'Clínica A'}, {id: 2, nombre: 'Clínica B'}],
        historia: {id: clinic * 10, ficha: clinic * 100, clinica_id: clinic, paciente: 'Paciente', docente: 'Docente ' + clinic},
        tratamientos: [{id: clinic * 20, nombre: 'Tratamiento ' + clinic, insumos: [{id: clinic * 30, nombre: 'Insumo ' + clinic, unidad: 'unidades', stock: 10, cantidad: clinic + 1}]}],
    })};
}

test('cambiar clínica carga sus materiales y envía la historia de esa clínica', async () => {
    let submitted;
    const ui = harness(async (url, options) => {
        if (options.method) {
            submitted = JSON.parse(options.body);
            return {ok: true, json: async () => ({success: true})};
        }
        return response(new URLSearchParams(url.split('?')[1]).get('clinica_id') === '2' ? 2 : 1);
    });
    await ui.window.SolicitudDesignado.abrir(10);
    assert.equal(ui.elements['designado-clinica'].children.length, 3);
    assert.equal(ui.inputs()[0].dataset.insumo, '30');
    ui.elements['designado-clinica'].value = '2';
    await ui.elements['designado-clinica'].emit('change');
    assert.equal(ui.inputs()[0].dataset.insumo, '60');
    assert.match(ui.elements['designado-paciente'].textContent, /Docente 2/);
    ui.inputs()[0].value = '1.5';
    await ui.form.emit('submit');
    assert.equal(submitted.historia_clinica_id, 20);
    assert.equal(submitted.clinica_id, 2);
    assert.equal(submitted.tratamiento_id, 40);
    assert.deepEqual(submitted.detalles, [{insumo_id: 60, cantidad_solicitada: 1.5}]);
});

test('una carga anterior de clínica no reemplaza la selección más reciente', async () => {
    let resolvePrevious;
    const ui = harness(url => new URLSearchParams(url.split('?')[1]).get('clinica_id') === '2'
        ? new Promise(resolve => {resolvePrevious = resolve;}) : Promise.resolve(response()));
    await ui.window.SolicitudDesignado.abrir(10);
    ui.elements['designado-clinica'].value = '2';
    const previous = ui.elements['designado-clinica'].emit('change');
    assert.equal(ui.elements['designado-guardar'].disabled, true);
    assert.equal(ui.inputs().length, 0);
    ui.elements['designado-clinica'].value = '1';
    await ui.elements['designado-clinica'].emit('change');
    resolvePrevious(response(2));
    await previous;
    assert.equal(ui.elements['designado-clinica'].value, '1');
    assert.equal(ui.inputs()[0].dataset.insumo, '30');
});

test('nueva solicitud permite elegir paciente y limpia los materiales al quitarlo', async () => {
    let requestedUrl;
    const ui = harness(async url => {requestedUrl = url; return response();});
    await ui.window.SolicitudDesignado.abrir();
    assert.equal(ui.elements['designado-elegir-paciente'].hidden, false);
    assert.equal(ui.elements['designado-paciente-select'].required, true);
    assert.equal(ui.elements['designado-guardar'].disabled, true);
    ui.elements['designado-paciente-select'].value = '10';
    await ui.elements['designado-paciente-select'].emit('change');
    assert.match(requestedUrl, /historia_clinica_id=10/);
    assert.equal(ui.inputs().length, 1);
    ui.elements['designado-paciente-select'].value = '';
    await ui.elements['designado-paciente-select'].emit('change');
    assert.equal(ui.inputs().length, 0);
    assert.equal(ui.elements['designado-clinica'].disabled, true);
    assert.equal(ui.elements['designado-guardar'].disabled, true);
});

test('editar conserva cantidades originales solo para su atención y tratamiento', async () => {
    const ui = harness(async url => response(new URLSearchParams(url.split('?')[1]).get('clinica_id') === '2' ? 2 : 1));
    await ui.window.SolicitudDesignado.abrir(10, {registro: {id: 7, historia_clinica_id: 10, tratamiento_id: 20, pieza: 16}, detalles: [{insumo_id: 30, cantidad_solicitada: 7}]});
    assert.equal(ui.inputs()[0].value, '7');
    assert.equal(ui.elements['designado-pieza'].value, 16);
    ui.elements['designado-clinica'].value = '2';
    await ui.elements['designado-clinica'].emit('change');
    assert.equal(ui.inputs()[0].value, '3');
    assert.equal(ui.elements['designado-pieza'].value, '');
});

test('una clínica que ya no está asignada bloquea el envío y muestra el error', async () => {
    const ui = harness(async url => url.includes('clinica_id=2')
        ? {ok: false, json: async () => ({errors: {clinica_id: ['La asignación está cerrada.']}})} : response());
    await ui.window.SolicitudDesignado.abrir(10);
    ui.elements['designado-clinica'].value = '2';
    await ui.elements['designado-clinica'].emit('change');
    assert.equal(ui.elements['designado-guardar'].disabled, true);
    assert.equal(ui.inputs().length, 0);
    assert.equal(ui.elements['designado-error'].textContent, 'La asignación está cerrada.');
});
