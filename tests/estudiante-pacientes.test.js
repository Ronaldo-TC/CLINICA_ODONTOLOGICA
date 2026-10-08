import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../public/js/estudiante-pacientes.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));

class Element {
    constructor() {
        this.listeners = new Map();
        this.dataset = {};
        this.attributes = new Map();
        this.textContent = '';
        this.innerHTML = 'contenido anterior';
    }
    addEventListener(type, handler) { this.listeners.set(type, handler); }
    setAttribute(key, value) { this.attributes.set(key, value); }
    removeAttribute(key) { this.attributes.delete(key); }
    querySelector() { return null; }
    querySelectorAll() { return []; }
    emit(type, target = this) {
        const event = {target, prevented: false, preventDefault() { this.prevented = true; }};
        const result = this.listeners.get(type)?.(event);
        return {event, result};
    }
}

function harness({listing = true, fetch} = {}) {
    const document = new Element();
    const filters = new Element();
    const table = new Element();
    const status = new Element();
    const feedback = new Element();
    const detail = new Element();
    const search = new Element();
    const totals = [];
    const history = [];
    filters.action = 'https://clinica.test/estudiante/historias';
    filters.values = [['buscar', 'Paciente'], ['periodo_id', '2']];
    filters.querySelector = () => search;
    detail.dataset.url = 'https://clinica.test/estudiante/historias/9';
    const selectors = new Map([
        ['[data-estudiante-filtros]', listing ? filters : null],
        ['[data-estudiante-tabla]', listing ? table : null],
        ['[data-estudiante-status]', status],
        ['[data-estudiante-feedback]', feedback],
        ['[data-estudiante-detalle]', listing ? null : detail],
        ['[data-estudiante-total]', {replaceChildren: value => totals.push(value)}],
        ['meta[name="csrf-token"]', {content: 'csrf-prueba'}],
    ]);
    document.querySelector = selector => selectors.get(selector);
    document.createTextNode = value => value;
    const window = new Element();
    runInNewContext(source, {
        document, window, fetch, AbortController, URL, setTimeout, clearTimeout,
        location: {origin: 'https://clinica.test'},
        history: {replaceState: (_state, _title, url) => history.push(url.toString())},
        FormData: class { constructor(form) { this.values = form.values || []; } [Symbol.iterator]() { return this.values[Symbol.iterator](); } },
    });
    return {document, filters, table, status, feedback, detail, window, totals, history};
}

function jsonResponse(data, status = 200) {
    return {ok: status < 400, redirected: false, headers: {get: () => 'application/json'}, json: async () => data};
}

test('filtra por AJAX sin recargar y actualiza tabla, total y dirección', async () => {
    const calls = [];
    const ui = harness({fetch: async (url, options) => {
        calls.push({url: url.toString(), options});
        return jsonResponse({success: true, total: 3, tabla: '<tr>Paciente filtrado</tr>'});
    }});
    const {event} = ui.filters.emit('submit');
    await settle();

    assert.equal(event.prevented, true);
    assert.equal(calls[0].options.headers.Accept, 'application/json');
    assert.equal(new URL(calls[0].url).searchParams.get('buscar'), 'Paciente');
    assert.equal(new URL(calls[0].url).searchParams.get('page'), null);
    assert.equal(ui.table.innerHTML, '<tr>Paciente filtrado</tr>');
    assert.deepEqual(ui.totals, ['3']);
    assert.equal(ui.history.length, 1);
    assert.equal(ui.table.attributes.has('aria-busy'), false);
});

test('una respuesta vieja no reemplaza el resultado de la búsqueda más reciente', async () => {
    const pending = [];
    const ui = harness({fetch: (_url, options) => new Promise(resolve => pending.push({options, resolve}))});
    ui.filters.emit('submit');
    ui.filters.values = [['buscar', 'Segundo']];
    ui.filters.emit('submit');

    assert.equal(pending[0].options.signal.aborted, true);
    pending[1].resolve(jsonResponse({success: true, total: 1, tabla: 'Segundo resultado'}));
    await settle();
    pending[0].resolve(jsonResponse({success: true, total: 1, tabla: 'Primer resultado'}));
    await settle();

    assert.equal(ui.table.innerHTML, 'Segundo resultado');
    assert.equal(ui.history.length, 1);
});

test('un error AJAX conserva la tabla y muestra el mensaje de validación', async () => {
    const ui = harness({fetch: async () => jsonResponse({errors: {clinica_id: ['Seleccione una especialidad válida.']}}, 422)});
    ui.filters.emit('submit');
    await settle();

    assert.equal(ui.table.innerHTML, 'contenido anterior');
    assert.equal(ui.status.textContent, 'Seleccione una especialidad válida.');
    assert.equal(ui.history.length, 0);
});

test('un formulario guarda por AJAX, previene envíos duplicados y actualiza la ficha', async () => {
    const calls = [];
    let resolveSave;
    const ui = harness({listing: false, fetch: (url, options) => {
        calls.push({url, options});
        if (options.method === 'POST') return new Promise(resolve => { resolveSave = resolve; });
        return Promise.resolve(jsonResponse({success: true, detalle: 'Ficha actualizada'}));
    }});
    const form = new Element();
    const button = new Element();
    const error = new Element();
    button.textContent = 'Guardar';
    form.action = 'https://clinica.test/tratamientos-realizados';
    form.reportValidity = () => true;
    form.hasAttribute = () => false;
    form.closest = () => form;
    form.querySelector = selector => selector === '[data-form-error]' ? error : button;

    const first = ui.document.emit('submit', form);
    const duplicate = ui.document.emit('submit', form);
    assert.equal(first.event.prevented, true);
    assert.equal(duplicate.event.prevented, true);
    assert.equal(calls.length, 1);
    assert.equal(button.disabled, true);
    resolveSave(jsonResponse({success: true, message: 'Propuesta enviada.'}, 201));
    await first.result;

    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'csrf-prueba');
    assert.equal(ui.detail.innerHTML, 'Ficha actualizada');
    assert.equal(ui.feedback.textContent, 'Propuesta enviada.');
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Guardar');
});

test('solicitar insumos preselecciona el tratamiento y avance del paciente', () => {
    const ui = harness({listing: false, fetch: async () => jsonResponse({})});
    let context;
    ui.window.SolicitudModal = {abrirCrear: value => { context = value; }};
    const button = {dataset: {clinica: '2', tratamiento: '7', sesion: '8', nombre: 'Restauración', pieza: '16'}, closest: () => button};

    ui.document.emit('click', button);

    assert.equal(context.clinica_id, '2');
    assert.equal(context.tratamiento_realizado_id, '7');
    assert.equal(context.sesion_atencion_id, '8');
    assert.equal(context.tratamiento_nombre, 'Restauración');
});
