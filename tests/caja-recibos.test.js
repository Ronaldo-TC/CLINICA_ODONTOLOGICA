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
        this.children = [];
        this.attributes = new Map();
        this.classList = {add() {}, remove() {}, toggle() {}, contains() { return true; }};
    }
    addEventListener(name, handler) { this.listeners.set(name, handler); }
    emit(name, details = {}) { return this.listeners.get(name)?.({target: this, preventDefault() {}, ...details}); }
    replaceChildren(...options) { this.options = this.children = options; this.value = String(options[0]?.value || ''); }
    add(option) { this.options.push(option); }
    append(...children) { this.children.push(...children); }
    setAttribute(name, value) { this.attributes.set(name, value); }
    removeAttribute(name) { this.attributes.delete(name); }
    focus() {}
    scrollIntoView() {}
}

function harness(fetch) {
    const ids = ['modalCaja', 'formCajaId', 'caja-clinica', 'caja-ficha', 'caja-atencion', 'caja-paciente', 'caja-estudiante', 'caja-costo-total', 'caja-anterior', 'caja-total-cancelado', 'caja-saldo', 'formCajaErrorText', 'btnGuardarCaja', 'modalCajaTitulo', 'caja-paciente-buscar', 'caja-paciente-busqueda', 'caja-paciente-resultados', 'caja-paciente-buscar-estado'];
    const elements = Object.fromEntries(ids.map(id => [id, new Element()]));
    const form = new Element();
    elements.formCaja = form;
    form.dataset = {store: '/cajas', update: '/cajas/__ID__', ficha: '/cajas/buscar-ficha', pacientes: '/cajas/buscar-pacientes'};
    const names = {clinica_id: 'caja-clinica', ficha: 'caja-ficha', tratamiento_realizado_id: 'caja-atencion'};
    for (const [name, id] of Object.entries(names)) form[name] = elements[id];
    for (const name of ['tratamiento_id', 'cancelado', 'recibo', 'descripcion', 'tipo_pago', 'habilitada']) form[name] = new Element();
    form.reset = () => { for (const name of [...Object.keys(names), 'tratamiento_id', 'cancelado', 'recibo', 'descripcion', 'tipo_pago']) form[name].value = ''; form.habilitada.checked = true; };
    const timers = new Map();
    let timerId = 0;
    const window = {dispatchEvent() {}};
    runInNewContext(source, {
        document: {getElementById: id => elements[id], createElement: () => new Element(), addEventListener() {}, body: new Element()}, window,
        fetch, URLSearchParams, Option: class {constructor(label, value) {this.text = label; this.value = value;}},
        Event: class {constructor(type) {this.type = type;}},
        setTimeout: callback => {timers.set(++timerId, callback); return timerId;}, clearTimeout: id => timers.delete(id),
        FormData: class extends Map {constructor(form) {super(Object.entries(form).filter(([, value]) => value instanceof Element && !value.disabled).map(([name, value]) => [name, value.value]));}},
    });
    return {elements, form, window, runTimer: () => {const callback = [...timers.values()].at(-1); timers.clear(); return callback?.();}};
}

function lookupFicha(ui) {
    ui.form.ficha.emit('input');
    return ui.runTimer();
}

function lookupPaciente(ui) {
    ui.elements['caja-paciente-buscar'].emit('input');
    return ui.runTimer();
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
    await lookupFicha(ui);
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
    const first = lookupFicha(ui);
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

test('escribir la ficha varias veces realiza una sola búsqueda automática', async () => {
    const calls = [];
    const ui = harness(async url => {calls.push(url); return response();});
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    ui.form.ficha.emit('input');

    ui.form.ficha.emit('input');
    await ui.runTimer();

    assert.equal(calls.length, 1);
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
});

function patientResponse(overrides = {}) {
    return {ok: true, json: async () => ({fichas: [
        {ficha: 11, paciente: 'Ana Montes', ci: '739125', fecha: '08/10/2026'},
        {ficha: 10, paciente: 'Ana Montes', ci: '739125', fecha: '01/10/2026'},
    ], hay_mas: false, ...overrides})};
}

test('buscar por CI o nombre muestra las fichas y elegir una completa el recibo por AJAX', async () => {
    const calls = [];
    const ui = harness(async url => {
        calls.push(url);
        return url.includes('buscar-pacientes') ? patientResponse() : response('Ana Montes');
    });
    ui.window.CajaModal.abrirCrear();
    ui.elements['caja-paciente-buscar'].value = '7391';
    ui.elements['caja-paciente-buscar'].emit('input');
    assert.equal(calls.length, 0);
    await ui.runTimer();
    assert.deepEqual(calls, ['/cajas/buscar-pacientes?buscar=7391']);
    assert.equal(ui.elements['caja-paciente-resultados'].hidden, false);
    assert.equal(ui.elements['caja-paciente-resultados'].children.length, 2);
    assert.equal(ui.elements['caja-paciente-resultados'].children[0].children[0].children[0].textContent, 'Ana Montes');
    assert.match(ui.elements['caja-paciente-resultados'].children[0].children[0].children[1].textContent, /739125.*Ficha #11.*08\/10\/2026/);
    assert.equal(ui.form.ficha.value, '');
    assert.equal(ui.elements['caja-paciente'].value, '');
    assert.equal(ui.elements['caja-estudiante'].value, '');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);

    await ui.elements['caja-paciente-resultados'].children[1].emit('click');
    assert.equal(ui.form.ficha.value, '10');
    assert.equal(calls[1], '/cajas/buscar-ficha?ficha=10');
    assert.equal(ui.elements['caja-paciente'].value, 'Ana Montes');
    assert.equal(ui.elements['caja-estudiante'].value, 'Estudiante asignado');
    assert.equal(ui.form.tratamiento_realizado_id.value, '9');
    assert.equal(ui.elements.btnGuardarCaja.disabled, false);
    assert.equal(ui.elements['caja-paciente-buscar'].value, 'Ana Montes');
    assert.equal(ui.elements['caja-paciente-resultados'].hidden, true);

    ui.elements['caja-paciente-buscar'].value = 'Otro paciente';
    ui.elements['caja-paciente-buscar'].emit('input');
    assert.equal(ui.form.ficha.value, '');
    assert.equal(ui.elements['caja-paciente'].value, '');
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);
});

test('la búsqueda por nombre descarta fichas anteriores y la búsqueda directa descarta pacientes pendientes', async () => {
    let resolvePatient;
    let resolveFicha;
    const ui = harness(url => {
        if (url.includes('buscar-pacientes')) return new Promise(resolve => {resolvePatient = resolve;});
        if (url.includes('ficha=10')) return new Promise(resolve => {resolveFicha = resolve;});
        return Promise.resolve(response('Paciente correcto', 12));
    });
    ui.window.CajaModal.abrirCrear();
    ui.form.ficha.value = '10';
    const oldFicha = lookupFicha(ui);
    ui.elements['caja-paciente-buscar'].value = 'Ana';
    ui.elements['caja-paciente-buscar'].emit('input');
    const oldPatient = ui.runTimer();
    resolveFicha(response('Paciente anterior'));
    await oldFicha;
    assert.equal(ui.elements['caja-paciente'].value, '');

    ui.form.ficha.value = '11';
    ui.form.ficha.emit('input');
    await ui.runTimer();
    resolvePatient(patientResponse());
    await oldPatient;
    assert.equal(ui.elements['caja-paciente'].value, 'Paciente correcto');
    assert.equal(ui.elements['caja-paciente-resultados'].hidden, true);
    assert.equal(ui.elements['caja-paciente-buscar'].value, '');
});

test('una respuesta de paciente antigua no reemplaza los resultados de un nombre más reciente', async () => {
    let resolveOld;
    const ui = harness(url => url.includes('buscar=Ana') ? new Promise(resolve => {resolveOld = resolve;})
        : Promise.resolve(patientResponse({fichas: [{ficha: 20, paciente: 'Luis Rojas', ci: '888', fecha: null}]})));
    ui.window.CajaModal.abrirCrear();
    ui.elements['caja-paciente-buscar'].value = 'Ana';
    ui.elements['caja-paciente-buscar'].emit('input');
    const first = ui.runTimer();
    ui.elements['caja-paciente-buscar'].value = 'Luis';
    ui.elements['caja-paciente-buscar'].emit('input');
    await ui.runTimer();
    resolveOld(patientResponse());
    await first;
    assert.equal(ui.elements['caja-paciente-resultados'].children.length, 1);
    assert.equal(ui.elements['caja-paciente-resultados'].children[0].children[0].children[0].textContent, 'Luis Rojas');
});

test('buscar paciente automáticamente usa la clínica seleccionada y cancelar impide reabrir resultados pendientes', async () => {
    const calls = [];
    let resolveSearch;
    const ui = harness(url => {calls.push(url); return new Promise(resolve => {resolveSearch = resolve;});});
    ui.window.CajaModal.abrirCrear();
    ui.form.clinica_id.value = '2';
    ui.elements['caja-paciente-buscar'].value = 'Ana Montes';
    ui.elements['caja-paciente-buscar'].emit('input');
    const pending = ui.runTimer();
    await ui.runTimer();
    assert.deepEqual(calls, ['/cajas/buscar-pacientes?buscar=Ana+Montes&clinica_id=2']);
    ui.window.CajaModal.cerrar();
    resolveSearch(patientResponse());
    await pending;
    assert.equal(ui.elements['caja-paciente-resultados'].hidden, true);
    assert.equal(ui.elements['caja-paciente-resultados'].children.length, 0);
});

test('buscar paciente muestra errores y resultados vacíos sin habilitar el guardado', async () => {
    const ui = harness(async url => url.includes('buscar=Error')
        ? {ok: false, json: async () => ({errors: {buscar: ['Búsqueda inválida.']}})}
        : patientResponse({fichas: []}));
    ui.window.CajaModal.abrirCrear();
    ui.elements['caja-paciente-buscar'].value = 'A';
    await lookupPaciente(ui);
    assert.equal(ui.elements['caja-paciente-resultados'].children.length, 0);
    ui.elements['caja-paciente-buscar'].value = 'Error';
    await lookupPaciente(ui);
    assert.equal(ui.elements['caja-paciente-buscar-estado'].textContent, 'Búsqueda inválida.');
    ui.elements['caja-paciente-buscar'].value = 'Nadie';
    await lookupPaciente(ui);
    assert.match(ui.elements['caja-paciente-buscar-estado'].textContent, /No se encontraron/);
    assert.equal(ui.elements['caja-paciente-resultados'].hidden, true);
    assert.equal(ui.elements.btnGuardarCaja.disabled, true);
});

test('Enter sólo completa el paciente con una opción elegida y cambiar clínica actualiza las coincidencias', async () => {
    const calls = [];
    const ui = harness(async url => {calls.push(url); return url.includes('buscar-pacientes') ? patientResponse({hay_mas: true}) : response('Ana Montes');});
    ui.window.CajaModal.abrirCrear();
    ui.elements['caja-paciente-buscar'].value = 'Ana';
    await lookupPaciente(ui);
    let prevented = false;
    ui.elements['caja-paciente-buscar'].emit('keydown', {key: 'Enter', preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(calls.length, 1);
    assert.equal(ui.form.ficha.value, '');
    assert.match(ui.elements['caja-paciente-buscar-estado'].textContent, /primeras 20 fichas/);
    ui.form.clinica_id.value = '3';
    ui.form.clinica_id.emit('change');
    await ui.runTimer();
    assert.equal(calls[1], '/cajas/buscar-pacientes?buscar=Ana&clinica_id=3');
    ui.elements['caja-paciente-buscar'].emit('keydown', {key: 'ArrowDown'});
    assert.equal(ui.elements['caja-paciente-buscar'].attributes.get('aria-activedescendant'), 'caja-paciente-opcion-0');
    await ui.elements['caja-paciente-buscar'].emit('keydown', {key: 'Enter'});
    assert.equal(ui.form.ficha.value, '11');
    assert.equal(ui.elements['caja-paciente'].value, 'Ana Montes');
});
