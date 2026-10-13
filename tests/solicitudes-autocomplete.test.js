import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const partial = readFileSync(new URL('../resources/views/solicitudes-insumos/partials/modal.blade.php', import.meta.url), 'utf8');
const source = partial.slice(partial.indexOf('        function inicializarAutocomplete('), partial.indexOf('        const $tratWrap'));

function harness(search) {
    const timers = new Map();
    let timerId = 0;
    const element = () => {
        const classes = new Set();
        return {value: '', hidden: true, innerHTML: '', listeners: {}, attributes: {},
            classList: {add(...names) {names.forEach(name => classes.add(name));}, remove(...names) {names.forEach(name => classes.delete(name));}},
            addEventListener(name, callback) {this.listeners[name] = callback;},
            setAttribute(name, value) {this.attributes[name] = value;}, focus() {},
        };
    };
    const input = element();
    const dropdown = element();
    const clear = element();
    const wrap = {...element(), contains: () => true, querySelector: selector => ({
        '.autocomplete-input': input, '[data-autocomplete-dropdown]': dropdown,
        '[data-autocomplete-clear]': clear, '.autocomplete-loading': element(),
    })[selector]};
    const context = {AbortController, document: {addEventListener() {}}, escapar: value => String(value),
        setTimeout: callback => {timers.set(++timerId, callback); return timerId;},
        clearTimeout: id => timers.delete(id),
    };
    runInNewContext(source, context);
    let selected;
    const autocomplete = context.inicializarAutocomplete(wrap, {
        buscar: search, alLimpiar() {selected = null;}, alElegir(item) {selected = item;},
    });
    return {input, dropdown, clear, autocomplete, selected: () => selected,
        search(value) {input.value = value; input.listeners.input();},
        runSearch() {const [id, callback] = timers.entries().next().value; timers.delete(id); return callback();},
        timers,
    };
}

test('buscar tratamiento muestra opciones visibles y permite seleccionarlas', async () => {
    const treatment = {id: 5, texto: 'Tratamiento autorizado', extra: 'Pieza 16'};
    const ui = harness(async () => [treatment]);
    ui.search('Tratamiento');
    await ui.runSearch();
    assert.equal(ui.dropdown.hidden, false);
    assert.equal(ui.input.attributes['aria-expanded'], 'true');
    assert.match(ui.dropdown.innerHTML, /Tratamiento autorizado/);
    ui.dropdown.listeners.click({target: {closest: () => ({dataset: {index: '0'}})}});
    assert.equal(ui.selected().id, 5);
    assert.equal(ui.input.value, 'Tratamiento autorizado');
    assert.equal(ui.dropdown.hidden, true);
});

test('cambiar clínica invalida una búsqueda anterior y conserva los nuevos resultados', async () => {
    let resolvePrevious;
    let previousSignal;
    const ui = harness((term, signal) => term === 'Anterior'
        ? new Promise(resolve => {resolvePrevious = resolve; previousSignal = signal;})
        : Promise.resolve([{id: 2, texto: 'Nueva clínica'}]));
    ui.search('Anterior');
    const previous = ui.runSearch();
    ui.autocomplete.limpiar();
    assert.equal(previousSignal.aborted, true);
    assert.equal(ui.dropdown.hidden, true);
    ui.search('Nuevo');
    await ui.runSearch();
    resolvePrevious([{id: 1, texto: 'Clínica anterior'}]);
    await previous;
    assert.match(ui.dropdown.innerHTML, /Nueva clínica/);
    assert.doesNotMatch(ui.dropdown.innerHTML, /Clínica anterior/);
});

test('borrar la búsqueda cancela el debounce y mantiene las opciones cerradas', () => {
    const ui = harness(() => {throw new Error('No debe buscar un texto borrado');});
    ui.search('Tratamiento');
    assert.equal(ui.timers.size, 1);
    ui.search('');
    assert.equal(ui.timers.size, 0);
    assert.equal(ui.dropdown.hidden, true);
    assert.equal(ui.input.attributes['aria-expanded'], 'false');
});
