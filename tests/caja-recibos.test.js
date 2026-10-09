import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../public/js/caja-recibos.js', import.meta.url), 'utf8');

class Element {
    constructor(value = '') {
        this.value = value;
        this.checked = true;
        this.disabled = false;
        this.listeners = new Map();
        this.dataset = {};
        this.classList = {add() {}, remove() {}, contains() { return true; }};
    }
    addEventListener(name, handler) { this.listeners.set(name, handler); }
    emit(name) { return this.listeners.get(name)?.({target: this, preventDefault() {}}); }
    replaceChildren(...options) { this.options = options; this.value = String(options[0]?.value || ''); }
    add(option) { this.options.push(option); }
}

function harness(fetch) {
    const ids = ['modalCaja', 'formCajaId', 'caja-clinica', 'caja-ficha', 'caja-atencion', 'caja-paciente', 'caja-estudiante', 'caja-costo-total', 'caja-anterior', 'caja-total-cancelado', 'caja-saldo', 'formCajaErrorText', 'btnGuardarCaja', 'caja-buscar-ficha', 'modalCajaTitulo'];
    const elements = Object.fromEntries(ids.map(id => [id, new Element()]));
    const form = new Element();
    elements.formCaja = form;
    form.dataset = {store: '/cajas', update: '/cajas/__ID__', ficha: '/cajas/buscar-ficha'};
    const names = {clinica_id: 'caja-clinica', ficha: 'caja-ficha', tratamiento_realizado_id: 'caja-atencion'};
    for (const [name, id] of Object.entries(names)) form[name] = elements[id];
    for (const name of ['tratamiento_id', 'cancelado', 'recibo', 'descripcion', 'tipo_pago', 'habilitada']) form[name] = new Element();
    form.reset = () => { for (const name of [...Object.keys(names), 'tratamiento_id', 'cancelado', 'recibo', 'descripcion', 'tipo_pago']) form[name].value = ''; form.habilitada.checked = true; };
    const timers = new Map();
    let timerId = 0;
    const window = {dispatchEvent() {}};
    runInNewContext(source, {
        document: {getElementById: id => elements[id], addEventListener() {}, body: new Element()}, window,
        fetch, URLSearchParams, Option: class {constructor(label, value) {this.text = label; this.value = value;}},
        Event: class {constructor(type) {this.type = type;}},
        setTimeout: callback => {timers.set(++timerId, callback); return timerId;}, clearTimeout: id => timers.delete(id),
        FormData: class extends Map {constructor(form) {super(Object.entries(form).filter(([, value]) => value instanceof Element && !value.disabled).map(([name, value]) => [name, value.value]));}},
    });
    return {elements, form, window, runTimer: () => {const callback = [...timers.values()].at(-1); timers.clear(); return callback?.();}};
}

function response(paciente = 'Paciente designado', id = 9, overrides = {}) {
    return {ok: true, json: async () => ({paciente, estudiante: 'Estudiante asignado', clinica_id: 2,
        atenciones: [{id, clinica_id: 2, tratamiento_id: 4, tratamiento: 'Restauración', estudiante: 'Estudiante asignado', paciente, costo: 100, cancelado_anterior: 30, saldo: 70}], ...overrides})};
}

test('la ficha completa el estudiante y paciente y suma el pago actual al cancelado anterior', async () => {
    const ui = harness(async () => response());
    ui.window.CajaModal.abrirCrear();
    ui.form.clinica_id.value = '2';
    ui.form.ficha.value = '10';
    await ui.elements['caja-buscar-ficha'].emit('click');
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente designado');
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    ui.form.cancelado.value = '20.10';
    ui.form.cancelado.emit('input');
    assert.equal(ui.elements['caja-total-cancelado'].value, '50.10');
    assert.equal(ui.elements['caja-saldo'].value, '49.90');
    ui.form.habilitada.checked = false;
    ui.form.habilitada.emit('change');
    assert.equal(ui.elements['caja-total-cancelado'].value, '30.00');
});

test('una respuesta antigua de ficha no reemplaza al paciente de la nueva búsqueda', async () => {
    let resolveFirst;
    const ui = harness(url => url.includes('ficha=10') ? new Promise(resolve => {resolveFirst = resolve;}) : Promise.resolve(response('Paciente correcto', 12)));
    ui.window.CajaModal.abrirCrear();
    ui.form.clinica_id.value = '2';
    ui.form.ficha.value = '10';
    const first = ui.elements['caja-buscar-ficha'].emit('click');
    ui.form.ficha.value = '11';
    ui.form.ficha.emit('input');
    await ui.runTimer();
    resolveFirst(response('Paciente de búsqueda anterior', 9));
    await first;
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente correcto');
    assert.equal(ui.form.tratamiento_realizado_id.value, '12');
});

test('editar conserva la atención y envía el importe del recibo aunque los selectores estén deshabilitados', async () => {
    let submitted;
    const ui = harness(async (_url, options) => {submitted = options.body; return {ok: true, json: async () => ({success: true, message: 'Guardado'})};});
    ui.window.CajaModal.abrirEdicion({registro: {id: 5, recibo: 500, clinica_id: 2, tratamiento_realizado_id: 9, tratamiento_id: 4, costo_total: 100, cancelado: 20, habilitada: true, paciente_nombre_completo: 'Paciente'}, cancelado_anterior: 30, atencion: {historia_clinica: {admision_id: 10, estudiante: {nombre_completo: 'Estudiante'}}}});
    assert.equal(ui.form.tratamiento_realizado_id.disabled, true);
    await ui.form.emit('submit');
    assert.equal(submitted.get('tratamiento_realizado_id'), '9');
    assert.equal(submitted.get('clinica_id'), '2');
    assert.equal(Number(submitted.get('cancelado')), 20);
    assert.equal(submitted.get('_method'), 'PUT');
});

test('escribir la ficha busca por AJAX sin elegir clínica y completa la designación automáticamente', async () => {
    const calls = [];
    const ui = harness(async url => {calls.push(url); return response();});
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '1';
    ui.form.ficha.emit('input');
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');
    assert.equal(calls.length, 0);

    await ui.runTimer();

    assert.deepEqual(calls, ['/cajas/buscar-ficha?ficha=10']);
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente designado');
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    assert.equal(ui.form.clinica_id.value, '2');
    assert.equal(ui.form.tratamiento_realizado_id.value, '9');
});

test('la ficha completa los nombres aunque no tenga tratamientos autorizados y bloquea guardar', async () => {
    const ui = harness(async () => response('Paciente sin tratamiento', 9, {atenciones: []}));
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');

    await ui.runTimer();

    assert.equal(ui.elements['caja-paciente'].value, 'Paciente sin tratamiento');
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);
    assert.match(ui.elements.formCajaErrorText.textContent, /no tiene tratamientos autorizados/);
});

test('varios tratamientos mantienen la designación y requieren elegir uno para guardar el recibo', async () => {
    const data = await response().json();
    data.atenciones.push({...data.atenciones[0], id: 12, tratamiento_id: 6, tratamiento: 'Endodoncia'});
    const ui = harness(async () => ({ok: true, json: async () => data}));
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');
    await ui.runTimer();
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente designado');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);

    ui.form.tratamiento_realizado_id.value = '12';
    ui.form.tratamiento_realizado_id.emit('change');
    assert.equal(ui.form.tratamiento_id.value, 6);
    assert.equal(ui.elements.btnGuardarCaja.disabled, false);
    ui.form.tratamiento_realizado_id.value = '';
    ui.form.tratamiento_realizado_id.emit('change');
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente designado');
    assert.equal(ui.form.tratamiento_id.value, '');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);
});

test('una ficha inexistente limpia los nombres anteriores y borrar la ficha cancela la búsqueda', async () => {
    const calls = [];
    const ui = harness(async url => {
        calls.push(url);
        return url.includes('ficha=10') ? response() : {ok: false, json: async () => ({message: 'Ficha no encontrada.'})};
    });
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');
    await ui.runTimer();
    ui.form.ficha.value = '11';
    ui.form.ficha.emit('input');
    assert.equal(ui.elements['caja-estudiante'].value, '');
    assert.equal(ui.elements['caja-paciente'].value, '');
    await ui.runTimer();
    assert.equal(ui.elements.formCajaErrorText.textContent, 'Ficha no encontrada.');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);

    ui.form.ficha.value = '12';
    ui.form.ficha.emit('input');
    ui.form.ficha.value = '';
    ui.form.ficha.emit('input');
    await ui.runTimer();
    assert.equal(calls.length, 2);
    assert.equal(ui.elements.formCajaErrorText.textContent, '');
});

test('buscar con el botón cancela la búsqueda automática pendiente', async () => {
    const calls = [];
    const ui = harness(async url => {calls.push(url); return response();});
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');

    await ui.elements['caja-buscar-ficha'].emit('click');
    await ui.runTimer();

    assert.equal(calls.length, 1);
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
});
