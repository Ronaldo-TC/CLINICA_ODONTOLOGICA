import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../public/js/insumo-registro.js', import.meta.url), 'utf8');

class Element {
    constructor() {
        this.value = '';
        this.textContent = '';
        this.disabled = false;
        this.required = false;
        this.dataset = {};
        this.style = {};
        this.options = [];
        this.listeners = new Map();
        const classes = new Set();
        this.classList = {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)};
    }
    addEventListener(name, handler) { this.listeners.set(name, handler); }
    emit(name) { return this.listeners.get(name)?.({target: this, preventDefault() {}}); }
    replaceChildren(...options) { this.options = options; this.value = String(options[0]?.value || ''); }
    add(option) { this.options.push(option); }
    closest() { return null; }
}

function harness(fetch) {
    const ids = ['formInsumo', 'modalInsumo', 'modalInsumoTitulo', 'formInsumoId', 'formMethod', 'clinica_id', 'insumo-tratamiento', 'insumo-tratamientos-actuales', 'btnGuardarInsumo', 'btnGuardarInsumoText', 'formInsumoError', 'formInsumoErrorText', 'btnNuevoInsumo'];
    const elements = Object.fromEntries(ids.map(id => [id, new Element()]));
    const fields = Object.fromEntries(['nombre', 'cantidad_numerica', 'cantidad', 'stock_minimo', 'costo_unitario', '_token'].map(name => [name, new Element()]));
    Object.assign(fields, {clinica_id: elements.clinica_id, tratamiento_id: elements['insumo-tratamiento'], _method: elements.formMethod});
    fields._token.value = 'csrf';
    const form = elements.formInsumo;
    form.dataset = {store: '/insumos', update: '/insumos/__INSUMO__', tratamientos: '/insumos/tratamientos-por-clinica/__CLINICA__'};
    form.elements = {namedItem: name => fields[name]};
    form.querySelectorAll = () => [];
    form.querySelector = selector => fields[selector.match(/name="([^"]+)"/)?.[1]];
    form.reset = () => { Object.entries(fields).forEach(([name, element]) => {if (name !== '_token') element.value = '';}); };
    form.reportValidity = () => !elements['insumo-tratamiento'].required || Boolean(elements['insumo-tratamiento'].value);
    const window = {dispatchEvent() {}, InsumosLista: {toast() {}}};
    runInNewContext(source, {
        document: {getElementById: id => elements[id], addEventListener() {}, body: new Element()},
        window, fetch,
        Option: class {constructor(label, value) {this.text = label; this.value = String(value);}},
        Event: class {constructor(type) {this.type = type;}},
        FormData: class extends Map {constructor() {super(Object.entries(fields).filter(([, field]) => !field.disabled).map(([name, field]) => [name, field.value]));}},
    });
    return {elements, form, fields, window};
}

function response(id = 11, nombre = 'Tratamiento') {
    return {ok: true, json: async () => [{id, nombre}]};
}

test('crear carga tratamientos de la clínica y envía los siete campos del registro', async () => {
    let requestedUrl;
    let submitted;
    const ui = harness(async (url, options) => {
        if (options.method) {
            submitted = options.body;
            return {ok: true, json: async () => ({success: true})};
        }
        requestedUrl = url;
        return response();
    });
    ui.elements.btnNuevoInsumo.emit('click');
    assert.equal(ui.elements.btnGuardarInsumo.disabled, true);
    ui.fields.clinica_id.value = '3';
    await ui.fields.clinica_id.emit('change');
    assert.equal(requestedUrl, '/insumos/tratamientos-por-clinica/3');
    assert.equal(ui.fields.tratamiento_id.options[1].value, '11');
    ui.fields.tratamiento_id.value = '11';
    for (const [name, value] of Object.entries({nombre: 'Resina', cantidad_numerica: '25', cantidad: 'ml', stock_minimo: '3', costo_unitario: '8.5'})) ui.fields[name].value = value;
    await ui.form.emit('submit');
    for (const name of ['clinica_id', 'tratamiento_id', 'nombre', 'cantidad_numerica', 'cantidad', 'stock_minimo', 'costo_unitario']) assert.equal(submitted.get(name), ui.fields[name].value);
    assert.equal(submitted.has('insumo_tratamiento_id'), false);
    assert.equal(ui.elements.modalInsumo.classList.contains('open'), false);
});

test('cambiar de clínica descarta la respuesta anterior y su tratamiento seleccionado', async () => {
    let resolvePrevious;
    const ui = harness(url => url.endsWith('/3') ? new Promise(resolve => {resolvePrevious = resolve;}) : Promise.resolve(response(22, 'Tratamiento correcto')));
    ui.elements.btnNuevoInsumo.emit('click');
    ui.fields.clinica_id.value = '3';
    const previous = ui.fields.clinica_id.emit('change');
    ui.fields.clinica_id.value = '4';
    await ui.fields.clinica_id.emit('change');
    resolvePrevious(response(11, 'Tratamiento anterior'));
    await previous;
    assert.equal(ui.fields.tratamiento_id.options[1].value, '22');
    assert.equal(ui.fields.tratamiento_id.value, '');
});

test('editar recibe registro y tratamientos asignados y conserva las medidas del insumo', async () => {
    let submitted;
    const ui = harness(async (_url, options) => {
        if (!options.method) return response();
        submitted = options.body;
        return {ok: true, json: async () => ({success: true})};
    });
    await ui.window.InsumosModal.abrirEdicion({registro: {id: 7, clinica_id: 3, nombre: 'Resina', cantidad_numerica: '25.50', cantidad: 'ml', stock_minimo: '3.00', costo_unitario: '8.50'}, tratamientosAsignados: [{id: 11, nombre: 'Restauración'}]});
    assert.equal(ui.fields.nombre.value, 'Resina');
    assert.equal(ui.fields.cantidad.value, 'ml');
    assert.equal(ui.fields.tratamiento_id.value, '11');
    await ui.form.emit('submit');
    assert.equal(submitted.get('_method'), 'PUT');
    assert.equal(submitted.get('tratamiento_id'), '11');
    assert.equal(submitted.get('cantidad_numerica'), '25.50');
});

test('editar un insumo compartido permite mantener todas sus relaciones', async () => {
    const ui = harness(async () => response());
    await ui.window.InsumosModal.abrirEdicion({registro: {id: 7, clinica_id: 3}, tratamientosAsignados: [{id: 11, nombre: 'Tratamiento A'}, {id: 12, nombre: 'Tratamiento B'}]});
    assert.equal(ui.fields.tratamiento_id.required, false);
    assert.equal(ui.fields.tratamiento_id.options[0].text, 'Mantener tratamientos actuales');
    assert.match(ui.elements['insumo-tratamientos-actuales'].textContent, /Tratamiento A, Tratamiento B/);
    assert.equal(ui.elements.btnGuardarInsumo.disabled, false);
});

test('una clínica sin tratamientos no permite registrar un insumo sin relación', async () => {
    const ui = harness(async () => ({ok: true, json: async () => []}));
    ui.elements.btnNuevoInsumo.emit('click');
    ui.fields.clinica_id.value = '3';
    await ui.fields.clinica_id.emit('change');
    assert.equal(ui.fields.tratamiento_id.disabled, true);
    assert.equal(ui.elements.btnGuardarInsumo.disabled, true);
    assert.equal(ui.fields.tratamiento_id.options[0].text, 'Esta clínica no tiene tratamientos registrados');
});

test('cerrar y volver a crear limpia la selección e invalida las cargas pendientes', async () => {
    let resolvePending;
    const ui = harness(() => new Promise(resolve => {resolvePending = resolve;}));
    ui.elements.btnNuevoInsumo.emit('click');
    ui.fields.clinica_id.value = '3';
    const pending = ui.fields.clinica_id.emit('change');
    await ui.elements.modalInsumo.emit('click');
    ui.elements.btnNuevoInsumo.emit('click');
    resolvePending(response());
    await pending;
    assert.equal(ui.fields.clinica_id.value, '');
    assert.equal(ui.fields.tratamiento_id.options.length, 1);
    assert.equal(ui.fields.tratamiento_id.disabled, true);
    assert.equal(ui.elements.btnGuardarInsumo.disabled, true);
});
