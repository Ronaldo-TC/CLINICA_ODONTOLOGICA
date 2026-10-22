import assert from 'node:assert/strict';
import { test } from 'node:test';
import { describirPieza, leerJson } from '../resources/js/odontograma-ficha-3d.js';

test('el visor interpreta únicamente objetos JSON válidos', () => {
    assert.deepEqual(leerJson('{"16":{"o":"caries"}}'), { 16: { o: 'caries' } });
    assert.deepEqual(leerJson('texto inválido'), {});
    assert.deepEqual(leerJson('["dato"]'), {});
});

test('el detalle 3D describe caras y marcas con sus etiquetas clínicas', () => {
    const datos = {
        16: {
            o: 'caries',
            marcas: [{ estado: 'corona', color: 'azul' }],
        },
    };
    const catalogo = {
        caries: { etiqueta: 'Caries' },
        corona: { etiqueta: 'Corona' },
    };

    assert.equal(describirPieza('16', datos, catalogo), 'Pieza 16 · Oclusal / incisal: Caries · Corona (azul)');
    assert.equal(describirPieza('17', datos, catalogo), 'Pieza 17 · Sin condiciones registradas.');
});
