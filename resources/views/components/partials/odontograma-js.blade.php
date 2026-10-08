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
                const DENTICIONES = ['adulto', 'infantil', 'mixto'];
                const INSTANCIAS = new WeakMap();
                let CONDICIONES = ORDEN_ESTADOS;

                function normalizarDatos(datos) {
                    if (typeof datos === 'string') {
                        try {
                            datos = JSON.parse(datos);
                        } catch {
                            return {};
                        }
                    }
                    if (!datos || typeof datos !== 'object' || Array.isArray(datos)) return {};
                    const limpio = {};
                    Object.entries(datos).forEach(([pieza, caras]) => {
                        if (!/^(?:[1-4][1-8]|[5-8][1-5])$/.test(pieza)
                            || !caras || typeof caras !== 'object' || Array.isArray(caras)) return;
                        const activas = {};
                        CARAS.forEach(cara => {
                            if (CONDICIONES.includes(caras[cara]) && caras[cara] !== 'sano') {
                                activas[cara] = caras[cara];
                            }
                        });
                        const marcas = Array.isArray(caras.marcas) ? caras.marcas.filter(marca => marca && CONDICIONES.includes(marca.estado) && marca.estado !== 'sano' && ['rojo','azul'].includes(marca.color)).slice(0, 35).map(marca => ({estado:marca.estado,color:marca.color})) : [];
                        if (marcas.length) activas.marcas = marcas;
                        if (Object.keys(activas).length) limpio[pieza] = activas;
                    });
                    return limpio;
                }

                function inferirDenticion(datos) {
                    const piezas = Object.keys(datos).map(Number);
                    const adultas = piezas.some(pieza => pieza < 50);
                    const infantiles = piezas.some(pieza => pieza > 50);
                    return adultas && infantiles ? 'mixto' : infantiles ? 'infantil' : 'adulto';
                }

                const raizDe = (objetivo) => {
                    if (!objetivo) return null;
                    if (objetivo instanceof Element && objetivo.matches('[data-odontograma]')) return objetivo;
                    return objetivo?.closest?.('[data-odontograma]') || document.querySelector(objetivo);
                };

                function init(raiz) {
                    if (!raiz || INSTANCIAS.has(raiz)) return;

                    raiz.dataset.odontogramaListo = '1';

                    const lectura = raiz.dataset.lectura === '1';
                    const input = raiz.querySelector('[data-odontograma-input]');
                    const inputDenticion = raiz.querySelector('[data-odontograma-denticion-input]');
                    const selectorDenticion = raiz.querySelector('[data-odontograma-denticion]');
                    const selectorVista = raiz.querySelector('[data-odontograma-vista]');
                    const selectorColor = raiz.querySelector('[data-odontograma-color]');
                    let herramienta = null;

                    let estados = {};
                    try {
                        estados = JSON.parse(raiz.dataset.estados || '{}');
                    } catch {
                        estados = {};
                    }
                    CONDICIONES = [...new Set([...ORDEN_ESTADOS, ...Object.keys(estados)])];

                    // ---------- Pintado ----------
                    const colorDe = (estado) => estados[estado]?.color || (estado === 'sano' ? '#ffffff' : '#dc2626');
                    const bordeDe = (estado) => (estados[estado] || estados.sano || {}).borde || '#cbd5e1';

                    function datosActuales() {
                        try {
                            const crudo = input?.value || '{}';
                            return normalizarDatos(crudo);
                        } catch {
                            return {};
                        }
                    }

                    function guardar(datos) {
                        const limpio = normalizarDatos(datos);

                        if (input) input.value = JSON.stringify(limpio);
                        pintarTodo(limpio);
                        raiz.dispatchEvent(new CustomEvent('odontograma:cambio', {
                            bubbles: true,
                            detail: { datos: limpio, denticion: inputDenticion?.value || 'adulto' },
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
                            poligono.setAttribute('aria-label', `Pieza ${pieza}, cara ${cara.toUpperCase()}, ${estados[estado]?.etiqueta || estado}`);

                            if (estado !== 'sano') algunEstado = true;
                        });

                        // La pieza ausente se marca tachada
                        const ausente = CARAS.some((cara) => caras?.[cara] === 'ausente');
                        contenedor.classList.toggle('es-ausente', ausente);
                        contenedor.classList.toggle('es-tratada', algunEstado && !ausente);
                        const marcas = contenedor.querySelector('[data-marcas]');
                        if (marcas) {
                            marcas.replaceChildren();
                            (caras?.marcas || []).forEach(marca => {
                                const etiqueta = document.createElement('span');
                                etiqueta.textContent = estados[marca.estado]?.simbolo || estados[marca.estado]?.etiqueta || marca.estado;
                                etiqueta.title = estados[marca.estado]?.etiqueta || marca.estado;
                                etiqueta.style.color = marca.color === 'azul' ? '#2563eb' : '#dc2626';
                                marcas.append(etiqueta);
                            });
                        }
                        if ((caras?.marcas || []).some(marca => marca.estado === 'ausente')) contenedor.classList.add('es-ausente');
                        const overlay = contenedor.querySelector('[data-overlay]');
                        if (overlay) {
                            overlay.replaceChildren();
                            (caras?.marcas || []).forEach((marca, index) => {
                                const color = marca.color === 'azul' ? '#2563eb' : '#dc2626';
                                const linea = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                                const inferior = /^[3478]/.test(String(pieza));
                                const corona = inferior ? 'M3 2H37V23H3Z' : 'M3 48H37V68H3Z';
                                const trazos = {
                                    ausente: 'M2 2L38 68M38 2L2 68', fractura: 'M24 5L12 23L28 32L12 46L24 65',
                                    corona, corona_temporal: corona, protesis_fija: corona,
                                    implante: 'M20 4V66M10 12H30M10 22H30M10 32H30M10 42H30M10 52H30',
                                    perno: 'M20 12V56', poste: 'M20 12V56',
                                };
                                if (trazos[marca.estado]) {
                                    linea.setAttribute('d', trazos[marca.estado]); linea.setAttribute('fill', 'none');
                                    linea.setAttribute('stroke', color); linea.setAttribute('stroke-width', '2'); overlay.append(linea);
                                } else {
                                    const texto = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                                    texto.setAttribute('x', '20'); texto.setAttribute('y', String(12 + (index % 4) * 15));
                                    texto.setAttribute('text-anchor', 'middle'); texto.setAttribute('font-size', '12'); texto.setAttribute('fill', color);
                                    texto.textContent = estados[marca.estado]?.simbolo || marca.estado.slice(0, 2).toUpperCase(); overlay.append(texto);
                                }
                            });
                        }
                    }

                    function cambiarDenticion(denticion) {
                        const tipo = DENTICIONES.includes(denticion) ? denticion : 'adulto';
                        if (selectorDenticion) selectorDenticion.value = tipo;
                        if (inputDenticion) inputDenticion.value = tipo;
                        raiz.dataset.denticion = tipo;
                        raiz.querySelectorAll('[data-denticion-arcada]').forEach(arcada => {
                            arcada.hidden = tipo !== 'mixto' && arcada.dataset.denticionArcada !== tipo;
                        });
                    }

                    function cambiarVista() {
                        const vista = selectorVista?.value === 'anatomica' ? 'anatomica' : 'frontal';
                        raiz.querySelectorAll('.diente-imagen').forEach(imagen => {
                            imagen.src = vista === 'anatomica' ? imagen.dataset.imagenAnatomica : imagen.dataset.imagenFrontal;
                        });
                    }

                    selectorDenticion?.addEventListener('change', () => {
                        cambiarDenticion(selectorDenticion.value);
                        if (!lectura) guardar(datosActuales());
                    });
                    selectorVista?.addEventListener('change', cambiarVista);

                    function cambiarCara(poligono) {
                        const diente = poligono.closest('[data-pieza]');
                        const pieza = diente?.dataset.pieza;
                        const cara = poligono.dataset.cara;
                        if (!pieza || !CARAS.includes(cara)) return;

                        const datos = datosActuales();
                        const actual = datos[pieza]?.[cara] || 'sano';
                        const siguiente = herramienta || ORDEN_ESTADOS[(ORDEN_ESTADOS.indexOf(actual) + 1) % ORDEN_ESTADOS.length];
                        datos[pieza] = datos[pieza] || {};
                        datos[pieza][cara] = siguiente;
                        guardar(datos);
                    }

                    function pintarTodo(datos) {
                        raiz.querySelectorAll('[data-pieza]').forEach((contenedor) => {
                            const pieza = contenedor.dataset.pieza;
                            pintarPieza(pieza, datos[pieza]);
                        });
                    }

                    // ---------- Interacción ----------
                    if (!lectura) {
                        raiz.querySelectorAll('[data-herramienta]').forEach(button => button.addEventListener('click', () => {
                            herramienta = button.dataset.herramienta;
                            raiz.querySelectorAll('[data-herramienta]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
                        }));
                        raiz.querySelector('[data-odontograma-limpiar]')?.addEventListener('click', () => {
                            if (!window.confirm('¿Borrar las marcas de la dentición visible?')) return;
                            const datos = datosActuales();
                            const tipo = inputDenticion?.value || 'adulto';
                            Object.keys(datos).forEach(pieza => { if (tipo === 'mixto' || (Number(pieza) < 50 ? 'adulto' : 'infantil') === tipo) delete datos[pieza]; });
                            guardar(datos);
                        });
                        raiz.addEventListener('click', (e) => {
                            const imagen = e.target.closest?.('.diente-imagen');
                            if (imagen && herramienta) {
                                const pieza = imagen.closest('[data-pieza]').dataset.pieza;
                                const datos = datosActuales();
                                if (herramienta === 'sano') delete datos[pieza];
                                else {
                                    const caras = datos[pieza] || {};
                                    const marcas = (caras.marcas || []).filter(marca => marca.estado !== herramienta);
                                    if (!(caras.marcas || []).some(marca => marca.estado === herramienta && marca.color === (selectorColor?.value || 'rojo'))) marcas.push({estado:herramienta, color:selectorColor?.value || 'rojo'});
                                    datos[pieza] = {...caras, marcas};
                                }
                                guardar(datos); return;
                            }
                            const poligono = e.target.closest?.('[data-cara]');
                            if (!poligono) return;
                            e.preventDefault();

                            cambiarCara(poligono);
                        });

                        raiz.addEventListener('keydown', e => {
                            if (e.key !== 'Enter' && e.key !== ' ') return;
                            const poligono = e.target.closest?.('[data-cara]');
                            if (!poligono) return;
                            e.preventDefault();
                            cambiarCara(poligono);
                        });

                        // Clic derecho: la pieza vuelve a sano
                        raiz.addEventListener('contextmenu', (e) => {
                            const diente = e.target.closest?.('[data-pieza]');
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
                    cambiarVista();
                    cambiarDenticion(inputDenticion?.value || raiz.dataset.denticion || inferirDenticion(datosActuales()));
                    INSTANCIAS.set(raiz, {
                        set(datos, denticion) {
                            const limpio = normalizarDatos(datos);
                            if (input) input.value = JSON.stringify(limpio);
                            cambiarDenticion(denticion || inferirDenticion(limpio));
                            pintarTodo(limpio);
                            raiz.dispatchEvent(new CustomEvent('odontograma:actualizado', {
                                bubbles: true,
                                detail: { datos: limpio, denticion: inputDenticion?.value || 'adulto' },
                            }));
                            return limpio;
                        },
                    });
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
                            return normalizarDatos(input?.value || '{}');
                        } catch {
                            return {};
                        }
                    },

                    set(objetivo, datos, denticion = null) {
                        const raiz = raizDe(objetivo);
                        if (!raiz) return {};
                        init(raiz);
                        return INSTANCIAS.get(raiz).set(datos, denticion);
                    },

                    limpiar(objetivo) {
                        return window.Odontograma.set(objetivo, {}, 'adulto');
                    },

                    denticion(objetivo) {
                        const raiz = raizDe(objetivo);
                        return raiz?.querySelector('[data-odontograma-denticion-input]')?.value || 'adulto';
                    },

                    /** Resumen legible: [{pieza, cara, estado}] de las caras tratadas */
                    resumen(objetivo) {
                        const datos = window.Odontograma.get(objetivo);
                        const filas = [];

                        Object.entries(datos).forEach(([pieza, caras]) => {
                            Object.entries(caras || {}).forEach(([cara, estado]) => {
                                if (!CARAS.includes(cara)) return;
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
