import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const template = readFileSync(new URL('../resources/views/bitacoras/index.blade.php', import.meta.url), 'utf8');
const source = template.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("@json(route('bitacoras.tabla'))", JSON.stringify('/bitacoras/tabla'));

function harness(fetch) {
    const ids = ['tbodyBitacoras', 'paginacionWrap', 'crud-status', 'crud-status-text', 'crud-total-label',
        'filtro-buscar', 'filtro-tipo', 'filtro-estado', 'filtro-desde', 'filtro-hasta', 'btnLimpiarFiltros'];
    const elements = Object.fromEntries(ids.map(id => [id, {
        value: '', innerHTML: '', textContent: '', listeners: new Map(),
        classList: { add() {}, remove() {} },
        addEventListener(name, handler) { this.listeners.set(name, handler); },
        emit(name) { return this.listeners.get(name)?.(); },
    }]));
    runInNewContext(source, {
        document: { getElementById: id => elements[id], addEventListener() {} },
        window: { location: { origin: 'http://localhost' } },
        console: { error() {} }, fetch, URLSearchParams, URL, AbortController, setTimeout, clearTimeout,
    });
    return elements;
}

test('al cambiar filtros se cancela la consulta anterior y se conservan los resultados más recientes', async () => {
    const requests = [];
    const elements = harness((url, options) => new Promise(resolve => requests.push({ url, options, resolve })));
    elements['filtro-tipo'].value = 'crear';
    const older = elements['filtro-tipo'].emit('change');
    elements['filtro-tipo'].value = 'editar';
    const newer = elements['filtro-tipo'].emit('change');
    requests[1].resolve({ ok: true, json: async () => ({ filas: 'Resultado actual', paginacion: 'Página actual', total: 2 }) });
    await newer;
    requests[0].resolve({ ok: true, json: async () => ({ filas: 'Resultado antiguo', paginacion: '', total: 9 }) });
    await older;

    assert.equal(requests[0].options.signal.aborted, true);
    assert.match(requests[1].url, /tipo=editar/);
    assert.equal(elements.tbodyBitacoras.innerHTML, 'Resultado actual');
    assert.equal(elements.paginacionWrap.innerHTML, 'Página actual');
    assert.equal(elements['crud-total-label'].textContent, '2 eventos registrados');
});

test('un rango de fechas inválido muestra el mensaje de validación del servidor', async () => {
    const message = 'La fecha Hasta debe ser igual o posterior a Desde.';
    const elements = harness(async () => ({ ok: false, status: 422, json: async () => ({ errors: { hasta: [message] } }) }));
    elements['filtro-hasta'].value = '2026-10-10';

    await elements['filtro-hasta'].emit('change');

    assert.equal(elements['crud-status-text'].textContent, message);
    assert.match(elements.tbodyBitacoras.innerHTML, /Error al cargar bitácoras/);
});
