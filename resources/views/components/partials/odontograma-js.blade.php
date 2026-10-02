{{--
    ============================================================
    ODONTOGRAMA · lógica compartida (Vanilla JS)
    ============================================================
    Se incluye una sola vez. Inicializa todos los [data-odontograma]
    presentes en la página y expone window.Odontograma con:
      · Odontograma.init(raiz)          → inicializar un contenedor
      · Odontograma.get(raizOSel)       → datos { pieza: { cara: estado } }
      · Odontograma.set(raizOSel, datos)→ cargar datos
      · Odontograma.limpiar(raizOSel)   → volver todo a "sano"
    El estado se serializa como JSON en el input oculto del componente.
--}}
@once
    @push('scripts')
        <script>
            (() => {
                'use strict';

                const CARAS = ['o', 'm', 'd', 'v', 'l'];
                const ORDEN_ESTADOS = ['sano', 'caries', 'obturado', 'ausente', 'corona', 'endodoncia', 'sellante', 'fractura'];

                const raizDe = (objetivo) => {
                    if (!objetivo) return null;
                    if (objetivo instanceof Element && objetivo.matches('[data-odontograma]')) return objetivo;
                    return objetivo?.closest?.('[data-odontograma]') || document.querySelector(objetivo);
                };

                function init(raiz) {
                    if (!raiz || raiz.dataset.odontogramaListo === '1') return;

                    raiz.dataset.odontogramaListo = '1';

                    const lectura = raiz.dataset.lectura === '1';
                    const input = raiz.querySelector('[data-odontograma-input]');

                    let estados = {};
                    try {
                        estados = JSON.parse(raiz.dataset.estados || '{}');
                    } catch {
                        estados = {};
                    }

                    // ---------- Pintado ----------
                    const colorDe = (estado) => (estados[estado] || estados.sano || {}).color || '#ffffff';
                    const bordeDe = (estado) => (estados[estado] || estados.sano || {}).borde || '#cbd5e1';

                    function datosActuales() {
                        try {
                            const crudo = input?.value || '{}';
                            const parsed = JSON.parse(crudo);
                            return parsed && typeof parsed === 'object' ? parsed : {};
                        } catch {
                            return {};
                        }
                    }

                    function guardar(datos) {
                        const limpio = {};

                        Object.entries(datos).forEach(([pieza, caras]) => {
                            const activas = {};
                            CARAS.forEach((cara) => {
                                const estado = caras[cara];
                                if (estado && estado !== 'sano') activas[cara] = estado;
                            });
                            if (Object.keys(activas).length > 0) limpio[pieza] = activas;
                        });

                        if (input) input.value = JSON.stringify(limpio);
                        pintarTodo(limpio);
                        raiz.dispatchEvent(new CustomEvent('odontograma:cambio', {
                            bubbles: true,
                            detail: { datos: limpio },
                        }));

                        return limpio;
                    }

                    function pintarPieza(pieza, caras) {
                        const contenedor = raiz.querySelector(`[data-pieza="${pieza}"]`);
                        if (!contenedor) return;

                        let algunEstado = false;

                        CARAS.forEach((cara) => {
                            const poligono = contenedor.querySelector(`[data-cara="${cara}"]`);
                            if (!poligono) return;

                            const estado = caras?.[cara] || 'sano';
                            poligono.setAttribute('fill', colorDe(estado));
                            poligono.setAttribute('stroke', bordeDe(estado));
                            poligono.setAttribute('stroke-width', estado === 'sano' ? '0.6' : '1');
                            poligono.dataset.estado = estado;

                            if (estado !== 'sano') algunEstado = true;
                        });

                        // La pieza ausente se marca tachada
                        const ausente = CARAS.some((cara) => caras?.[cara] === 'ausente');
                        contenedor.classList.toggle('es-ausente', ausente);
                        contenedor.classList.toggle('es-tratada', algunEstado && !ausente);
                    }

                    function pintarTodo(datos) {
                        raiz.querySelectorAll('[data-pieza]').forEach((contenedor) => {
                            const pieza = contenedor.dataset.pieza;
                            pintarPieza(pieza, datos[pieza]);
                        });
                    }

                    // ---------- Interacción ----------
                    if (!lectura) {
                        raiz.addEventListener('click', (e) => {
                            const poligono = e.target.closest('[data-cara]');
                            if (!poligono) return;
                            e.preventDefault();

                            const diente = poligono.closest('[data-pieza]');
                            const pieza = diente?.dataset.pieza;
                            const cara = poligono.dataset.cara;
                            if (!pieza || !cara) return;

                            const datos = datosActuales();
                            const actual = datos[pieza]?.[cara] || 'sano';
                            const siguiente = ORDEN_ESTADOS[(ORDEN_ESTADOS.indexOf(actual) + 1) % ORDEN_ESTADOS.length];

                            datos[pieza] = datos[pieza] || {};
                            datos[pieza][cara] = siguiente;

                            guardar(datos);
                        });

                        // Clic derecho: la pieza vuelve a sano
                        raiz.addEventListener('contextmenu', (e) => {
                            const diente = e.target.closest('[data-pieza]');
                            if (!diente) return;
                            e.preventDefault();

                            const pieza = diente.dataset.pieza;
                            const datos = datosActuales();
                            delete datos[pieza];
                            guardar(datos);
                        });
                    }

                    // Estado inicial
                    pintarTodo(datosActuales());
                }

                function initTodo(ambito = document) {
                    ambito.querySelectorAll?.('[data-odontograma]').forEach(init);
                }

                window.Odontograma = {
                    estados: ORDEN_ESTADOS,

                    init: init,
                    initTodo: initTodo,

                    get(objetivo) {
                        const raiz = raizDe(objetivo);
                        const input = raiz?.querySelector('[data-odontograma-input]');
                        try {
                            return JSON.parse(input?.value || '{}');
                        } catch {
                            return {};
                        }
                    },

                    set(objetivo, datos) {
                        const raiz = raizDe(objetivo);
                        if (!raiz) return {};
                        const input = raiz.querySelector('[data-odontograma-input]');
                        if (input) input.value = JSON.stringify(datos && typeof datos === 'object' ? datos : {});
                        // Repintar forzando la relectura
                        raiz.dataset.odontogramaListo = '0';
                        init(raiz);
                        return window.Odontograma.get(raiz);
                    },

                    limpiar(objetivo) {
                        return window.Odontograma.set(objetivo, {});
                    },

                    /** Resumen legible: [{pieza, cara, estado}] de las caras tratadas */
                    resumen(objetivo) {
                        const datos = window.Odontograma.get(objetivo);
                        const filas = [];

                        Object.entries(datos).forEach(([pieza, caras]) => {
                            Object.entries(caras || {}).forEach(([cara, estado]) => {
                                filas.push({ pieza: Number(pieza), cara, estado });
                            });
                        });

                        return filas.sort((a, b) => a.pieza - b.pieza);
                    },
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => initTodo());
                } else {
                    initTodo();
                }
            })();
        </script>
    @endpush
@endonce
