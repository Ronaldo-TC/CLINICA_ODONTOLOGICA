import assert from 'node:assert/strict';
import { test } from 'node:test';
import { crearAutocompletadoAdmision, inicializarFormularioAdmision, normalizarValor } from '../public/js/admision-autocompletado.js';

function pendiente() {
    let resolver;
    let rechazar;
    const promise = new Promise((resolve, reject) => {
        resolver = resolve;
        rechazar = reject;
    });
    return { promise, resolver, rechazar };
}

function formulario({ obtenerPaciente, valoresIniciales = {}, ...opciones } = {}) {
    const valores = new Map(Object.entries(valoresIniciales));
    const escritos = [];
    const pacientes = [];
    const estados = [];
    const peticiones = [];
    let limpiezas = 0;
    const autocompletado = crearAutocompletadoAdmision({
        campos: ['telefono', 'antecedentes', 'gpe', 'pulso', 'asimetria_facial', 'odontograma_pintado', 'odontograma_denticion'],
        ...opciones,
        obtenerPaciente: (id, options) => {
            peticiones.push({ id, ...options });
            return obtenerPaciente(id, options);
        },
        escribirCampo: (campo, valor) => {
            escritos.push([campo, valor]);
            valores.set(campo, valor);
        },
        limpiarFicha: () => {
            limpiezas += 1;
            valores.clear();
        },
        alPaciente: paciente => pacientes.push(paciente),
        alEstado: estado => estados.push(estado),
    });
    return { autocompletado, valores, escritos, pacientes, estados, peticiones, limpiezas: () => limpiezas };
}

class Elemento {
    constructor(name = '', tagName = 'INPUT') {
        Object.assign(this, { name, tagName, value: '', checked: false, disabled: false, hidden: false, style: {}, dataset: {}, options: [], listeners: new Map(), children: [] });
        const clases = new Set();
        this.classList = {
            add: clase => clases.add(clase), remove: clase => clases.delete(clase),
            toggle: (clase, activa) => activa ? clases.add(clase) : clases.delete(clase),
        };
    }

    addEventListener(tipo, callback) { this.listeners.set(tipo, callback); }
    emitir(tipo, target = this) { return this.listeners.get(tipo)?.({ target, preventDefault() {} }); }
    append(...hijos) { this.children.push(...hijos); }
    replaceChildren(...hijos) { this.children = hijos; }
    contains(target) { return target === this || this.children.includes(target); }
    focus() {}
    add(opcion) {
        opcion.remove = () => { this.options = this.options.filter(actual => actual !== opcion); };
        this.options.push(opcion);
    }
    set innerHTML(valor) { assert.fail('La ficha debe presentar texto sin interpretar HTML: ' + valor); }
}

function formularioDOM({ solicitar, pacienteInicialId = '', ...configuracion } = {}) {
    const ids = [
        'adm-paciente-buscar', 'adm-paciente-id', 'wrapPaciente', 'dropdownPaciente', 'datosPacienteResumen',
        'datosPacienteNombre', 'datosPacienteMeta', 'datosPacienteOrigen', 'datosPacienteFicha', 'datosPacienteReporte',
        'cargaPacienteEstado', 'cargaPacienteMensaje', 'btnReintentarPaciente', 'btnGuardarAdmision', 'formAdmisionError', 'formAdmisionErrorText',
    ];
    const elementos = new Map(ids.map(id => [id, new Elemento()]));
    elementos.get('adm-paciente-id').value = pacienteInicialId;
    const botonLimpiar = new Elemento();
    elementos.get('wrapPaciente').querySelector = () => botonLimpiar;
    const nombres = ['telefono', 'gpe', 'pulso', 'asimetria_facial', 'motivo_consulta_id', 'odontograma_pintado', 'odontograma_denticion', 'periodo_id', 'odontograma_pintado_anterior', '_token'];
    const controles = new Map(nombres.map(nombre => [nombre, new Elemento(nombre, ['gpe', 'asimetria_facial', 'motivo_consulta_id'].includes(nombre) ? 'SELECT' : 'INPUT')]));
    controles.get('motivo_consulta_id').add(Object.assign(new Elemento('', 'OPTION'), { value: '', textContent: 'Seleccione' }));
    const consentimientos = ['consentimiento_datos', 'consentimiento_fotografias', 'consentimiento_investigacion', 'clinica_ids[]'].map(nombre => new Elemento(nombre));
    const documento = new Elemento();
    documento.getElementById = id => elementos.get(id);
    documento.createElement = tagName => new Elemento('', tagName.toUpperCase());
    documento.defaultView = { location: { href: '' } };
    const form = new Elemento();
    form.ownerDocument = documento;
    form.elements = { namedItem: nombre => controles.get(nombre) || consentimientos.find(control => control.name === nombre) };
    form.querySelectorAll = selector => selector === '[data-error-for]' ? [] : consentimientos;
    form.reportValidity = () => true;
    const odontograma = {
        datos: {}, denticion: 'adulto', limpiezas: 0,
        get() { return this.datos; },
        set(objetivo, datos, denticion) { this.datos = typeof datos === 'string' ? JSON.parse(datos) : datos; this.denticion = denticion; },
        limpiar() { this.datos = {}; this.denticion = 'adulto'; this.limpiezas += 1; },
    };
    const firma = { limpiezas: 0, limpiar() { this.limpiezas += 1; } };
    const peticiones = [];
    const autocompletado = inicializarFormularioAdmision({
        form, odontograma, firma,
        config: {
            modo: 'crear', preservarInicial: false, campos: nombres.filter(nombre => !['periodo_id', 'odontograma_pintado_anterior', '_token'].includes(nombre)),
            urls: { buscarPacientes: '/admisiones/buscar-pacientes', datosPaciente: '/admisiones/buscar-pacientes/__ID__/datos', ficha: '/admisiones/__ID__', reporte: '/admisiones/__ID__/reporte-paciente' },
            ...configuracion,
        },
        solicitar: (url, opciones) => { peticiones.push({ url, opciones }); return solicitar(url, opciones); },
    });
    return { autocompletado, form, elementos, controles, consentimientos, odontograma, firma, peticiones, botonLimpiar };
}

test('la ficha conserva respuestas negativas, cero, teléfonos y el odontograma completo', async () => {
    const odontograma = { '55': { o: 'caries', marcas: [{ estado: 'corona', color: 'azul' }] } };
    const datos = {
        telefono: '007000001', antecedentes: '<script>antecedente</script>', gpe: 'Bueno',
        pulso: 0, asimetria_facial: false, odontograma_pintado: odontograma, odontograma_denticion: 'mixto',
    };
    const pagina = formulario({ obtenerPaciente: async () => ({ id: 1, admision_anterior: { datos } }) });

    await pagina.autocompletado.seleccionar(1);

    assert.deepEqual(Object.fromEntries(pagina.valores), {
        telefono: '007000001', antecedentes: '<script>antecedente</script>', gpe: 'Bueno',
        pulso: '0', asimetria_facial: '0', odontograma_pintado: odontograma, odontograma_denticion: 'mixto',
    });
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
});

test('sólo completa campos permitidos y toma primero los datos de la admisión anterior', async () => {
    const paciente = {
        id: 1, telefono: '11111111', estado: 'autorizada',
        admision_anterior: { datos: {
            telefono: '00070000', firma: 'firma anterior', consentimiento_datos: true,
            periodo_id: 4, estudiante_id: 12, responsable_id: 20, clinica_ids: [3], estado: 'autorizada',
        } },
    };
    const pagina = formulario({ obtenerPaciente: async () => paciente });

    await pagina.autocompletado.seleccionar(1);

    assert.deepEqual(pagina.escritos, [['telefono', '00070000']]);
});

test('una respuesta lenta de otro paciente no reemplaza la selección más reciente', async () => {
    const primero = pendiente();
    const segundo = pendiente();
    const pagina = formulario({ obtenerPaciente: id => id === '1' ? primero.promise : segundo.promise });
    const cargaPrimero = pagina.autocompletado.seleccionar(1);
    const cargaSegundo = pagina.autocompletado.seleccionar(2);
    assert.equal(pagina.peticiones[0].signal.aborted, true);

    segundo.resolver({ id: 2, admision_anterior: { datos: { telefono: '22222222' } } });
    await cargaSegundo;
    primero.resolver({ id: 1, admision_anterior: { datos: { telefono: '11111111' } } });
    await cargaPrimero;

    assert.deepEqual(pagina.escritos, [['telefono', '22222222']]);
    assert.deepEqual(pagina.autocompletado.estado(), { estado: 'listo', pacienteId: '2', error: null });
    assert.equal(pagina.pacientes.at(-1).id, 2);
});

test('un error de una selección cancelada no bloquea al paciente actual', async () => {
    const anterior = pendiente();
    const pagina = formulario({ obtenerPaciente: id => id === '1' ? anterior.promise : Promise.resolve({ id: 2 }) });
    const primeraCarga = pagina.autocompletado.seleccionar(1);
    await pagina.autocompletado.seleccionar(2);

    anterior.rechazar(new Error('Error del paciente anterior'));
    await primeraCarga;

    assert.equal(pagina.autocompletado.estado().estado, 'listo');
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
    assert.equal(pagina.estados.some(estado => estado.estado === 'error'), false);
});

test('limpiar una selección durante la carga cancela la respuesta y bloquea el guardado', async () => {
    const solicitud = pendiente();
    const pagina = formulario({ obtenerPaciente: () => solicitud.promise });
    const carga = pagina.autocompletado.seleccionar(1);

    pagina.autocompletado.limpiar();
    solicitud.resolver({ id: 1, admision_anterior: { datos: { telefono: '11111111' } } });
    await carga;

    assert.equal(pagina.peticiones[0].signal.aborted, true);
    assert.deepEqual(pagina.escritos, []);
    assert.deepEqual(pagina.autocompletado.estado(), { estado: 'sin-paciente', pacienteId: '', error: null });
    assert.equal(pagina.autocompletado.puedeGuardar(), false);
});

test('los campos escritos durante la carga se conservan y los demás se completan', async () => {
    const solicitud = pendiente();
    const pagina = formulario({ obtenerPaciente: () => solicitud.promise });
    const carga = pagina.autocompletado.seleccionar(1);
    pagina.valores.set('telefono', '07000000 corregido');
    pagina.autocompletado.marcarEditado('telefono');

    solicitud.resolver({ id: 1, admision_anterior: { datos: { telefono: '11111111', gpe: 'Regular' } } });
    await carga;

    assert.equal(pagina.valores.get('telefono'), '07000000 corregido');
    assert.equal(pagina.valores.get('gpe'), 'Regular');
    assert.deepEqual(pagina.escritos, [['gpe', 'Regular']]);
});

test('seleccionar el mismo paciente no repite peticiones ni reemplaza cambios manuales', async () => {
    const solicitud = pendiente();
    const pagina = formulario({ obtenerPaciente: () => solicitud.promise });
    const primeraCarga = pagina.autocompletado.seleccionar(1);
    const segundaCarga = pagina.autocompletado.seleccionar(1);
    assert.equal(primeraCarga, segundaCarga);
    solicitud.resolver({ id: 1, admision_anterior: { datos: { telefono: '11111111' } } });
    await primeraCarga;
    pagina.valores.set('telefono', '22222222');
    pagina.autocompletado.marcarEditado('telefono');

    await pagina.autocompletado.seleccionar(1);

    assert.equal(pagina.peticiones.length, 1);
    assert.equal(pagina.valores.get('telefono'), '22222222');
    assert.equal(pagina.limpiezas(), 0);
});

test('cambiar a un paciente sin ficha anterior elimina datos y cambios del paciente previo', async () => {
    const pagina = formulario({ obtenerPaciente: async id => id === '1'
        ? { id: 1, admision_anterior: { datos: { telefono: '11111111', gpe: 'Bueno' } } }
        : { id: 2, admision_anterior: null } });
    await pagina.autocompletado.seleccionar(1);
    pagina.autocompletado.marcarEditado('telefono');

    await pagina.autocompletado.seleccionar(2);

    assert.equal(pagina.limpiezas(), 1);
    assert.deepEqual(Object.fromEntries(pagina.valores), {});
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
    await pagina.autocompletado.seleccionar(1);
    assert.equal(pagina.valores.get('telefono'), '11111111');
});

test('una carga fallida bloquea el guardado y permite reintentar la misma selección', async () => {
    let intentos = 0;
    const pagina = formulario({ obtenerPaciente: async () => {
        intentos += 1;
        if (intentos === 1) throw new Error('No se pudieron cargar los datos');
        return { id: 1, admision_anterior: { datos: { telefono: '00070000' } } };
    } });
    assert.equal(pagina.autocompletado.puedeGuardar(), false);
    const carga = pagina.autocompletado.seleccionar(1);
    assert.equal(pagina.autocompletado.estado().estado, 'cargando');
    assert.equal(pagina.autocompletado.puedeGuardar(), false);
    await carga;
    assert.equal(pagina.autocompletado.estado().error.message, 'No se pudieron cargar los datos');
    assert.equal(pagina.autocompletado.puedeGuardar(), false);

    await pagina.autocompletado.reintentar();

    assert.equal(intentos, 2);
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
    assert.equal(pagina.valores.get('telefono'), '00070000');
});

test('una respuesta de paciente diferente produce error sin rellenar la ficha', async () => {
    const pagina = formulario({ obtenerPaciente: async () => ({ id: 99, admision_anterior: { datos: { telefono: '99999999' } } }) });

    await pagina.autocompletado.seleccionar(1);

    assert.equal(pagina.autocompletado.estado().estado, 'error');
    assert.match(pagina.autocompletado.estado().error.message, /no corresponden al paciente seleccionado/);
    assert.deepEqual(pagina.escritos, []);
    assert.equal(pagina.autocompletado.puedeGuardar(), false);
});

test('editar una ficha carga la identidad sin reemplazar su evaluación guardada', async () => {
    const pagina = formulario({
        modo: 'editar', pacienteInicialId: 1, valoresIniciales: { telefono: '07000000 edición', gpe: 'Regular' },
        obtenerPaciente: async () => ({ id: 1, admision_anterior: { datos: { telefono: '11111111', gpe: 'Bueno' } } }),
    });

    await pagina.autocompletado.seleccionar(1);

    assert.deepEqual(pagina.escritos, []);
    assert.deepEqual(Object.fromEntries(pagina.valores), { telefono: '07000000 edición', gpe: 'Regular' });
    assert.equal(pagina.limpiezas(), 0);
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
});

test('los valores recuperados tras validación se conservan hasta seleccionar otro paciente', async () => {
    const pagina = formulario({
        pacienteInicialId: 1, preservarInicial: true, valoresIniciales: { telefono: '07000000 corregido' },
        obtenerPaciente: async id => ({ id, admision_anterior: { datos: { telefono: id === '1' ? '11111111' : '22222222' } } }),
    });
    await pagina.autocompletado.seleccionar(1);
    assert.equal(pagina.valores.get('telefono'), '07000000 corregido');
    assert.deepEqual(pagina.escritos, []);

    await pagina.autocompletado.seleccionar(2);

    assert.equal(pagina.valores.get('telefono'), '22222222');
    assert.equal(pagina.limpiezas(), 1);
});

test('la normalización mantiene datos ausentes separados de cero y respuestas negativas', () => {
    assert.deepEqual([null, undefined, false, true, 0, '0000700', '1.65'].map(normalizarValor), ['', '', '0', '1', '0', '0000700', '1.65']);
    const odontograma = { '16': { o: 'caries' } };
    assert.equal(normalizarValor(odontograma), odontograma);
});

test('el formulario inicial precarga controles, motivo anterior y odontograma sin interpretar etiquetas', async () => {
    const odontograma = { '55': { o: 'caries' }, '16': { marcas: [{ estado: 'corona', color: 'azul' }] } };
    const pagina = formularioDOM({ pacienteInicialId: '1', solicitar: async () => ({ ok: true, json: async () => ({
        id: 1, nombre_completo: '<img src=x onerror=alert(1)>', ci: '000123', edad: 0,
        admision_anterior: { id: 17, fecha_admision: '2026-10-01', motivo_consulta_nombre: '<script>Motivo anterior</script>', datos: {
            telefono: '007000001', pulso: 0, asimetria_facial: false, motivo_consulta_id: 7,
            odontograma_pintado: JSON.stringify(odontograma), odontograma_denticion: 'mixto',
        } },
    }) }) });

    await pagina.autocompletado.seleccionar(1);

    assert.equal(pagina.controles.get('telefono').value, '007000001');
    assert.equal(pagina.controles.get('pulso').value, '0');
    assert.equal(pagina.controles.get('asimetria_facial').value, '0');
    const motivo = pagina.controles.get('motivo_consulta_id');
    assert.equal(motivo.value, '7');
    assert.equal(motivo.options.at(-1).textContent, '<script>Motivo anterior</script>');
    assert.equal(pagina.elementos.get('datosPacienteNombre').textContent, '<img src=x onerror=alert(1)>');
    assert.match(pagina.elementos.get('datosPacienteMeta').textContent, /Edad: 0 años/);
    assert.deepEqual(pagina.odontograma.datos, odontograma);
    assert.equal(pagina.odontograma.denticion, 'mixto');
    assert.equal(pagina.elementos.get('datosPacienteFicha').href, '/admisiones/17');
    assert.equal(pagina.elementos.get('btnGuardarAdmision').disabled, false);
    assert.equal(pagina.peticiones[0].opciones.credentials, 'same-origin');
});

test('cambiar de paciente reinicia la evaluación, firma, consentimientos y derivaciones de la visita', async () => {
    const pagina = formularioDOM({ solicitar: async url => ({ ok: true, json: async () => url.includes('/1/')
        ? { id: 1, admision_anterior: { id: 4, datos: { telefono: '11111111', motivo_consulta_id: 7, odontograma_pintado: '{"16":{"o":"caries"}}' } } }
        : { id: 2, admision_anterior: null } }) });
    await pagina.autocompletado.seleccionar(1);
    pagina.consentimientos.forEach(control => control.checked = true);
    pagina.controles.get('periodo_id').value = '2';
    pagina.controles.get('odontograma_pintado_anterior').value = 'registro anterior';

    await pagina.autocompletado.seleccionar(2);

    assert.equal(pagina.controles.get('telefono').value, '');
    assert.equal(pagina.controles.get('periodo_id').value, '');
    assert.equal(pagina.controles.get('odontograma_pintado_anterior').value, '');
    assert.equal(pagina.controles.get('motivo_consulta_id').options.length, 1);
    assert.equal(pagina.consentimientos.some(control => control.checked), false);
    assert.deepEqual(pagina.odontograma.datos, {});
    assert.equal(pagina.firma.limpiezas, 1);
    assert.equal(pagina.elementos.get('datosPacienteFicha').style.display, 'none');
    assert.equal(pagina.elementos.get('datosPacienteReporte').style.display, 'none');
});

test('enviar mientras se carga el paciente muestra un error y no registra una admisión incompleta', async () => {
    const solicitud = pendiente();
    const pagina = formularioDOM({ solicitar: () => solicitud.promise });
    const carga = pagina.autocompletado.seleccionar(1);

    await pagina.form.emitir('submit');

    assert.equal(pagina.peticiones.length, 1);
    assert.equal(pagina.elementos.get('btnGuardarAdmision').disabled, true);
    assert.match(pagina.elementos.get('formAdmisionErrorText').textContent, /espere a que se carguen sus datos/);
    solicitud.resolver({ ok: true, json: async () => ({ id: 1, admision_anterior: null }) });
    await carga;
    assert.equal(pagina.elementos.get('btnGuardarAdmision').disabled, false);
});

test('editar una ficha mantiene su paciente aunque se solicite otro o se intente limpiar', async () => {
    const pagina = formulario({
        modo: 'editar', pacienteInicialId: 1, valoresIniciales: { telefono: '007000001', gpe: 'Regular' },
        obtenerPaciente: async id => ({ id, admision_anterior: null }),
    });
    await pagina.autocompletado.seleccionar(1);

    await pagina.autocompletado.seleccionar(2);
    await pagina.autocompletado.seleccionar('');
    pagina.autocompletado.limpiar();

    assert.equal(pagina.peticiones.length, 1);
    assert.equal(pagina.autocompletado.estado().pacienteId, '1');
    assert.equal(pagina.autocompletado.puedeGuardar(), true);
    assert.deepEqual(Object.fromEntries(pagina.valores), { telefono: '007000001', gpe: 'Regular' });
    assert.equal(pagina.limpiezas(), 0);
});

test('los eventos de buscar y limpiar paciente en edición conservan evaluación, firma y consentimientos', async () => {
    const pagina = formularioDOM({
        modo: 'editar', pacienteInicialId: '1',
        solicitar: async () => ({ ok: true, json: async () => ({ id: 1, admision_anterior: null }) }),
    });
    pagina.controles.get('telefono').value = '007000001';
    pagina.controles.get('gpe').value = 'Regular';
    pagina.controles.get('periodo_id').value = '2';
    pagina.consentimientos.forEach(control => control.checked = true);
    pagina.odontograma.datos = { '16': { o: 'caries' } };
    await pagina.autocompletado.seleccionar(1);

    pagina.elementos.get('adm-paciente-buscar').value = 'Otro paciente';
    pagina.elementos.get('adm-paciente-buscar').emitir('input');
    pagina.botonLimpiar.emitir('click');

    assert.equal(pagina.autocompletado.estado().pacienteId, '1');
    assert.equal(pagina.elementos.get('adm-paciente-id').value, '1');
    assert.equal(pagina.peticiones.length, 1);
    assert.equal(pagina.controles.get('telefono').value, '007000001');
    assert.equal(pagina.controles.get('gpe').value, 'Regular');
    assert.equal(pagina.controles.get('periodo_id').value, '2');
    assert.equal(pagina.consentimientos.every(control => control.checked), true);
    assert.deepEqual(pagina.odontograma.datos, { '16': { o: 'caries' } });
    assert.equal(pagina.firma.limpiezas, 0);
    assert.equal(pagina.elementos.get('btnGuardarAdmision').disabled, false);
});
