import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const componente = readFileSync(new URL('../resources/views/components/boton-reporte.blade.php', import.meta.url), 'utf8');
const script = componente.match(/<script>([\s\S]*?)<\/script>/)[1];

function control(value = '') {
    return {
        value,
        checked: false,
        eventos: new Map(),
        addEventListener(evento, callback) { this.eventos.set(evento, callback); },
    };
}

function pagina(campos = {}, formato = '') {
    const enlace = {
        ...control(),
        dataset: { base: 'https://clinica.test/admisiones/reporte', formato },
        href: 'https://clinica.test/admisiones/reporte',
    };
    const controles = new Map(Object.entries(campos));
    runInNewContext(script, {
        URL,
        window: { location: { origin: 'https://clinica.test' } },
        document: {
            querySelectorAll: () => [enlace],
            getElementById: id => controles.get(id),
        },
    });
    return enlace;
}

test('el reporte recoge los filtros vigentes y conserva el estado cero', () => {
    const enlace = pagina({
        'filtro-buscar': control('  Mamani & López  '),
        'filtro-clinica': control('3'),
        'filtro-periodo': control('2'),
        'filtro-estado': control('0'),
        'filtro-desde': control('2026-10-01'),
        'filtro-hasta': control('2026-10-05'),
        'filtro-tipo-pago': control('efectivo'),
        'filtro-stock-bajo': { ...control(), checked: true },
    });

    enlace.eventos.get('click')();

    const destino = new URL(enlace.href);
    assert.equal(destino.origin, 'https://clinica.test');
    assert.deepEqual(Object.fromEntries(destino.searchParams), {
        buscar: 'Mamani & López', clinica_id: '3', periodo_id: '2', estado: '0',
        desde: '2026-10-01', hasta: '2026-10-05', tipo_pago: 'efectivo', stock_bajo: '1',
    });
});

test('limpiar los filtros elimina los parámetros al volver a generar el reporte', () => {
    const buscar = control('Paciente');
    const stock = { ...control(), checked: true };
    const enlace = pagina({ 'filtro-buscar': buscar, 'filtro-stock-bajo': stock });
    enlace.eventos.get('click')();
    buscar.value = '';
    stock.checked = false;

    enlace.eventos.get('click')();

    assert.equal(enlace.href, 'https://clinica.test/admisiones/reporte');
});

test('editar un filtro actualiza el enlace antes de abrirlo en otra pestaña', () => {
    const buscar = control('Ana');
    const enlace = pagina({ 'filtro-buscar': buscar });

    buscar.eventos.get('input')();

    assert.equal(new URL(enlace.href).searchParams.get('buscar'), 'Ana');
    buscar.value = 'Sofía';
    enlace.eventos.get('contextmenu')();
    assert.equal(new URL(enlace.href).searchParams.get('buscar'), 'Sofía');
});

test('la descarga mantiene el formato PDF al cambiar y limpiar los filtros', () => {
    const buscar = control('Paciente');
    const enlace = pagina({ 'filtro-buscar': buscar }, 'pdf');
    enlace.eventos.get('click')();
    assert.deepEqual(Object.fromEntries(new URL(enlace.href).searchParams), { formato: 'pdf', buscar: 'Paciente' });

    buscar.value = '';
    buscar.eventos.get('input')();

    assert.deepEqual(Object.fromEntries(new URL(enlace.href).searchParams), { formato: 'pdf' });
});

test('el reporte de pacientes conserva el filtro de sexo seleccionado', () => {
    const enlace = pagina({ 'filtro-genero': control('F') }, 'pdf');
    enlace.eventos.get('click')();

    assert.equal(new URL(enlace.href).searchParams.get('genero'), 'F');
});
