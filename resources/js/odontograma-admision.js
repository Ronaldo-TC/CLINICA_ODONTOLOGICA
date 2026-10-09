import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { configurarPantallaCompleta } from './visor-pantalla-completa.js';
import {
    CARAS_ADMISION, actualizarPieza, aplicarEstadoModelo, esVisibleEnEscena,
    identificarPieza, limitesDenticion, limpiarDenticion, piezasDeDenticion, prepararModeloAdmision,
    pintarMaterial, posicionEtiquetaEncia,
} from '../../public/js/odontograma/admision.js';

function iniciarEditor() {
    const editor = document.getElementById('adm-odontograma-3d');
    const odontograma = document.getElementById('adm-odontograma');
    if (!editor || !odontograma || !window.Odontograma) return;
    window.Odontograma.init(odontograma);
    const control = nombre => document.getElementById(`adm-3d-${nombre}`);
    const catalogo = JSON.parse(odontograma.dataset.estados);
    const estado = () => window.Odontograma.get(odontograma);
    const denticion = () => window.Odontograma.denticion(odontograma);
    const canvas = control('canvas');
    const status = control('status');
    const selectorPieza = control('pieza');
    const selectorCara = control('cara');
    const selectorModo = control('modo');
    const selectorCondicion = control('condicion');
    const selectorColor = control('color');
    const selectorDenticion = control('denticion');
    const resumen = control('resumen');
    const botonesModelo = ['aplicar', 'extraer', 'restaurar', 'enfocar'].map(control);
    const herramientas = [...editor.querySelectorAll('[data-3d-herramienta]')];
    const caras = [...editor.querySelectorAll('[data-3d-cara]')];
    const historial = [{ datos: structuredClone(estado()), denticion: denticion() }];
    let posicionHistorial = 0;
    let restaurandoHistorial = false;
    let guardando = false;
    let indice = null;
    let renderer = null;
    let camera = null;
    let controls = null;
    let scene = null;
    let labels = null;
    let ultimaDenticion = null;
    let destruido = false;
    let renderPendiente = true;
    const accesorios = new Map();

    const actualizarHistorial = () => {
        const actual = { datos: structuredClone(estado()), denticion: denticion() };
        if (!restaurandoHistorial && JSON.stringify(actual) !== JSON.stringify(historial[posicionHistorial])) {
            historial.splice(posicionHistorial + 1);
            historial.push(actual);
            if (historial.length > 50) historial.shift();
            posicionHistorial = historial.length - 1;
        }
        control('deshacer').disabled = posicionHistorial === 0;
        control('rehacer').disabled = posicionHistorial === historial.length - 1;
    };

    const guardar = (datos, tipo = denticion()) => {
        let limpio;
        guardando = true;
        try {
            limpio = window.Odontograma.set(odontograma, datos, tipo);
        } finally {
            guardando = false;
        }
        odontograma.dispatchEvent(new CustomEvent('odontograma:cambio', {
            bubbles: true, detail: { datos: limpio, denticion: tipo },
        }));
    };

    const seleccionar = (pieza, cara = null) => {
        selectorPieza.value = pieza;
        if (cara && selectorModo.value === 'cara') {
            selectorCara.value = cara;
        }
        sincronizar();
    };

    const aplicarActual = (alternar = false) => guardar(actualizarPieza(estado(), {
        pieza: selectorPieza.value, cara: selectorCara.value, estado: selectorCondicion.value,
        modo: selectorModo.value, color: selectorColor.value, alternar,
    }));

    const crearBotonResumen = (texto, accion) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'btn btn-sm btn-secondary';
        boton.textContent = texto;
        boton.addEventListener('click', accion);
        resumen.append(boton);
    };

    const renderResumen = datos => {
        resumen.replaceChildren();
        const titulo = document.createElement('p');
        titulo.textContent = `Pieza ${selectorPieza.value} · ${catalogo[selectorCondicion.value]?.etiqueta || ''}.`;
        resumen.append(titulo);
        const diente = datos[selectorPieza.value] || {};
        CARAS_ADMISION.forEach(cara => {
            if (!diente[cara]) return;
            crearBotonResumen(`${cara.toUpperCase()}: ${catalogo[diente[cara]]?.etiqueta || diente[cara]} · borrar`, () => {
                guardar(actualizarPieza(estado(), { pieza: selectorPieza.value, cara, estado: 'sano' }));
            });
        });
        (diente.marcas || []).forEach(marca => {
            crearBotonResumen(`${catalogo[marca.estado]?.etiqueta || marca.estado} (${marca.color}) · quitar`, () => {
                guardar(actualizarPieza(estado(), { pieza: selectorPieza.value, estado: marca.estado, modo: 'marca', quitar: true }));
            });
        });
        if (!Object.keys(diente).length) {
            const sano = document.createElement('span');
            sano.textContent = 'Sin condiciones registradas en esta pieza.';
            resumen.append(sano);
        }
    };

    const renderEtiquetas = datos => {
        if (!labels) return;
        labels.children.slice().forEach(etiqueta => {
            etiqueta.material.map.dispose();
            etiqueta.material.dispose();
            labels.remove(etiqueta);
        });
        piezasDeDenticion(denticion()).forEach(pieza => {
            const diente = indice.dientes.get(pieza);
            if (!diente) return;
            const marcas = datos[pieza]?.marcas || [];
            const imagen = document.createElement('canvas');
            imagen.width = marcas.length ? 256 : 128;
            imagen.height = marcas.length ? 96 : 64;
            const contexto = imagen.getContext('2d');
            contexto.fillStyle = selectorPieza.value === pieza ? '#075985' : '#182331';
            contexto.fillRect(0, 0, imagen.width, imagen.height);
            contexto.font = 'bold 32px sans-serif';
            contexto.textAlign = 'center';
            contexto.textBaseline = 'middle';
            contexto.fillStyle = '#f8fafc';
            contexto.fillText(pieza, imagen.width / 2, 32);
            if (marcas.length) {
                contexto.font = '20px sans-serif';
                const visibles = marcas.slice(0, 3);
                const anchoMarca = 256 / (visibles.length + (marcas.length > 3 ? 1 : 0));
                visibles.forEach((marca, index) => {
                    contexto.fillStyle = marca.color === 'azul' ? '#60a5fa' : '#f87171';
                    contexto.fillText(catalogo[marca.estado]?.simbolo || catalogo[marca.estado]?.etiqueta || marca.estado, anchoMarca * (index + 0.5), 75, anchoMarca - 4);
                });
                if (marcas.length > 3) {
                    contexto.fillStyle = '#f8fafc';
                    contexto.fillText(`+${marcas.length - 3}`, 256 - anchoMarca / 2, 75);
                }
            }
            const textura = new THREE.CanvasTexture(imagen);
            textura.colorSpace = THREE.SRGBColorSpace;
            const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: textura, depthTest: false, depthWrite: false, toneMapped: false }));
            sprite.position.copy(posicionEtiquetaEncia(indice, pieza));
            sprite.scale.set(marcas.length ? 0.014 : 0.007, marcas.length ? 0.00525 : 0.0035, 1);
            sprite.userData.fdi = pieza;
            sprite.renderOrder = 10;
            labels.add(sprite);
        });
        labels.visible = control('numeros').checked;
    };

    const renderAccesorios = datos => {
        const tipos = { corona: 'crown', corona_temporal: 'crown', implante: 'implant', perno: 'post', poste: 'post', ortodoncia_fija: 'bracket', supernumerario: 'supernumerary' };
        accesorios.forEach(objeto => objeto.visible = false);
        piezasDeDenticion(denticion()).forEach(pieza => {
            const diente = indice.dientes.get(pieza);
            (datos[pieza]?.marcas || []).forEach(marca => {
                const tipo = tipos[marca.estado];
                const plantilla = indice.accesorios.get(tipo);
                const anchor = diente?.children.find(objeto => objeto.userData.anchor === tipo);
                if (!plantilla || !anchor) return;
                const clave = `${pieza}:${marca.estado}`;
                let accesorio = accesorios.get(clave);
                if (!accesorio) {
                    accesorio = new THREE.Group();
                    accesorio.position.copy(diente.position);
                    accesorio.quaternion.copy(diente.quaternion);
                    accesorio.scale.copy(diente.scale);
                    const copia = plantilla.clone(true);
                    copia.visible = true;
                    copia.traverse(objeto => {
                        if (!objeto.isMesh) return;
                        const materiales = Array.isArray(objeto.material) ? objeto.material : [objeto.material];
                        const independientes = materiales.map(material => material.clone());
                        objeto.material = Array.isArray(objeto.material) ? independientes : independientes[0];
                    });
                    const centro = new THREE.Box3().setFromObject(copia).getCenter(new THREE.Vector3());
                    copia.position.sub(centro).add(anchor.position);
                    accesorio.add(copia);
                    diente.parent.add(accesorio);
                    accesorios.set(clave, accesorio);
                }
                accesorio.visible = diente.visible || tipo === 'implant';
                accesorio.traverse(objeto => {
                    if (!objeto.isMesh) return;
                    const materiales = Array.isArray(objeto.material) ? objeto.material : [objeto.material];
                    materiales.forEach(material => {
                        pintarMaterial(material, marca.color === 'azul' ? '#2563eb' : '#dc2626');
                    });
                });
            });
        });
    };

    const orientar = (vista = 'perspectiva', pieza = null) => {
        if (!indice) return;
        const limites = limitesDenticion(indice, denticion());
        if (pieza) {
            limites.makeEmpty();
            indice.superficies.filter(objeto => String(objeto.userData.fdi) === pieza).forEach(objeto => limites.union(new THREE.Box3().setFromObject(objeto)));
        }
        if (limites.isEmpty()) return;
        const centro = limites.getCenter(new THREE.Vector3());
        const size = limites.getSize(new THREE.Vector3());
        const distancia = Math.max(size.y, size.x / camera.aspect, size.z) / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2))) * 1.6;
        controls.target.copy(centro);
        const direcciones = {
            perspectiva: [0.25, 0.3, 1], frontal: [0, 0, 1], superior: [0, 1, 0.001],
            inferior: [0, -1, 0.001], derecha: [-1, 0, 0], izquierda: [1, 0, 0], posterior: [0, 0, -1],
        };
        camera.position.copy(centro).add(new THREE.Vector3(...(direcciones[vista] || direcciones.perspectiva)).normalize().multiplyScalar(distancia));
        controls.minDistance = 0.003;
        controls.maxDistance = 1;
        controls.update();
    };
    const centrar = () => orientar(control('vista').value);

    const zoom = factor => {
        if (!controls) return;
        const direccion = camera.position.clone().sub(controls.target);
        direccion.setLength(THREE.MathUtils.clamp(direccion.length() * factor, controls.minDistance, controls.maxDistance));
        camera.position.copy(controls.target).add(direccion);
        controls.update();
    };

    const limpiarPantalla = configurarPantallaCompleta(editor, control('pantalla'), 'odontograma-3d-fullscreen', 'Volver al formulario');

    function sincronizar() {
        renderPendiente = true;
        const tipo = denticion();
        const datos = estado();
        selectorDenticion.value = tipo;
        const piezas = piezasDeDenticion(tipo);
        if (selectorPieza.dataset.denticion !== tipo) {
            const seleccionada = selectorPieza.value;
            selectorPieza.replaceChildren(...piezas.map(pieza => new Option(pieza, pieza)));
            selectorPieza.value = piezas.includes(seleccionada) ? seleccionada : piezas[0];
            selectorPieza.dataset.denticion = tipo;
        }
        selectorCara.disabled = selectorModo.value === 'marca';
        selectorColor.disabled = selectorModo.value !== 'marca';
        herramientas.forEach(boton => boton.setAttribute('aria-pressed', String(boton.dataset['3dHerramienta'] === selectorCondicion.value)));
        caras.forEach(boton => {
            const cara = boton.dataset['3dCara'];
            const condicion = datos[selectorPieza.value]?.[cara];
            boton.style.backgroundColor = catalogo[condicion]?.color || (condicion ? '#dc2626' : '#fff');
            boton.style.color = condicion ? '#fff' : '#334155';
            boton.setAttribute('aria-pressed', String(selectorModo.value === 'cara' && selectorCara.value === cara));
            boton.disabled = !indice;
        });
        actualizarHistorial();
        renderResumen(datos);
        if (!indice) return;
        aplicarEstadoModelo(indice, datos, catalogo, {
            denticion: tipo, raices: control('raices').checked,
            encias: control('encias').checked, huesos: control('huesos').checked,
            seleccion: { pieza: selectorPieza.value, cara: selectorModo.value === 'cara' ? selectorCara.value : null },
        });
        renderAccesorios(datos);
        renderEtiquetas(datos);
        if (ultimaDenticion !== tipo) centrar();
        ultimaDenticion = tipo;
    }

    odontograma.addEventListener('odontograma:cambio', sincronizar);
    odontograma.addEventListener('odontograma:actualizado', () => {
        if (!guardando) {
            historial.splice(0, historial.length, { datos: structuredClone(estado()), denticion: denticion() });
            posicionHistorial = 0;
        }
        sincronizar();
    });
    selectorDenticion.addEventListener('change', () => guardar(estado(), selectorDenticion.value));
    [selectorPieza, selectorCara, selectorModo, selectorCondicion, selectorColor, control('encias'), control('raices'), control('huesos'), control('numeros')].forEach(elemento => elemento.addEventListener('change', sincronizar));
    selectorCondicion.value = 'caries';
    herramientas.forEach(boton => boton.addEventListener('click', () => {
        selectorCondicion.value = boton.dataset['3dHerramienta'];
        if (!catalogo[selectorCondicion.value]?.color) selectorModo.value = 'marca';
        sincronizar();
    }));
    caras.forEach(boton => boton.addEventListener('click', () => {
        selectorModo.value = 'cara';
        selectorCara.value = boton.dataset['3dCara'];
        aplicarActual();
    }));
    control('aplicar').addEventListener('click', () => aplicarActual());
    control('extraer').addEventListener('click', () => guardar(actualizarPieza(estado(), {
        pieza: selectorPieza.value, estado: 'ausente', modo: 'marca', color: selectorColor.value,
    })));
    control('restaurar').addEventListener('click', () => guardar(actualizarPieza(estado(), { pieza: selectorPieza.value, modo: 'borrar' })));
    control('limpiar').addEventListener('click', () => {
        if (window.confirm('¿Borrar las marcas de la dentición visible?')) guardar(limpiarDenticion(estado(), denticion()));
    });
    control('centrar').addEventListener('click', centrar);
    control('vista').addEventListener('change', centrar);
    control('enfocar').addEventListener('click', () => orientar(control('vista').value, selectorPieza.value));
    control('acercar').addEventListener('click', () => zoom(0.8));
    control('alejar').addEventListener('click', () => zoom(1.25));
    control('girar').addEventListener('change', () => {
        if (controls) controls.autoRotate = control('girar').checked;
    });
    control('suelo').addEventListener('change', () => canvas.parentElement.classList.toggle('sin-suelo', !control('suelo').checked));
    ['deshacer', 'rehacer'].forEach((nombre, index) => control(nombre).addEventListener('click', () => {
        const siguiente = posicionHistorial + (index === 0 ? -1 : 1);
        if (siguiente < 0 || siguiente >= historial.length) return;
        posicionHistorial = siguiente;
        restaurandoHistorial = true;
        try {
            guardar(historial[siguiente].datos, historial[siguiente].denticion);
        } finally {
            restaurandoHistorial = false;
        }
    }));
    sincronizar();

    const mostrarError = error => {
        console.error('No se pudo iniciar el odontograma 3D.', error);
        status.textContent = 'No se pudo cargar el editor 3D. Puede completar el odontograma en la vista 2D.';
        document.getElementById('adm-odontograma-alternativo').open = true;
        botonesModelo.forEach(boton => boton.disabled = true);
    };

    try {
        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(38, 1, 0.0005, 10);
        renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        controls = new OrbitControls(camera, canvas);
        controls.addEventListener('change', () => renderPendiente = true);
        controls.enableDamping = true;
        controls.enablePan = true;
        controls.autoRotateSpeed = 0.8;
        const ambiente = new THREE.HemisphereLight(0xffffff, 0x64748b, 1.4);
        scene.add(ambiente);
        const luz = new THREE.DirectionalLight(0xffffff, 1.8);
        luz.position.set(0.2, 0.4, 0.5);
        scene.add(luz);
        control('iluminacion').addEventListener('input', () => {
            const intensidad = Number(control('iluminacion').value);
            ambiente.intensity = 1.4 * intensidad;
            luz.intensity = 1.8 * intensidad;
            renderPendiente = true;
        });
        labels = new THREE.Group();
        scene.add(labels);
        const resize = new ResizeObserver(() => {
            const { clientWidth: width, clientHeight: height } = canvas;
            if (!width || !height) return;
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderPendiente = true;
        });
        resize.observe(canvas.parentElement);
        const raycaster = new THREE.Raycaster();
        const pointer = new THREE.Vector2();
        let inicio = null;
        let arrastrando = false;
        canvas.addEventListener('pointerdown', evento => {
            if (!evento.isPrimary || ![0, 2].includes(evento.button) || evento.ctrlKey || evento.shiftKey || evento.metaKey) {
                inicio = null;
                return;
            }
            inicio = { x: evento.clientX, y: evento.clientY, button: evento.button };
            arrastrando = false;
        });
        canvas.addEventListener('pointermove', evento => {
            if (inicio && Math.hypot(evento.clientX - inicio.x, evento.clientY - inicio.y) > 5) arrastrando = true;
        });
        canvas.addEventListener('pointercancel', () => inicio = null);
        canvas.addEventListener('contextmenu', evento => evento.preventDefault());
        canvas.addEventListener('pointerup', evento => {
            const seleccionarClick = inicio && !arrastrando && Math.hypot(evento.clientX - inicio.x, evento.clientY - inicio.y) <= 5;
            const borrar = inicio?.button === 2;
            inicio = null;
            if (!indice || !seleccionarClick) return;
            const rect = canvas.getBoundingClientRect();
            pointer.set(((evento.clientX - rect.left) / rect.width) * 2 - 1, -((evento.clientY - rect.top) / rect.height) * 2 + 1);
            raycaster.setFromCamera(pointer, camera);
            const objetos = [...indice.seleccionables, ...labels.children].filter(esVisibleEnEscena);
            const interseccion = raycaster.intersectObjects(objetos, false)[0];
            const diente = interseccion ? identificarPieza(interseccion.object) : null;
            if (diente) {
                seleccionar(diente.pieza, diente.cara);
                if (borrar) {
                    guardar(actualizarPieza(estado(), { pieza: diente.pieza, modo: 'borrar' }));
                } else if (control('pintar').checked && (diente.cara || selectorModo.value === 'marca')) {
                    aplicarActual(true);
                }
            }
        });
        renderer.setAnimationLoop(() => {
            if (controls.update() || renderPendiente) {
                renderer.render(scene, camera);
                renderPendiente = false;
            }
        });
        new GLTFLoader().load(editor.dataset.modelUrl, gltf => {
            if (destruido) return;
            indice = prepararModeloAdmision(gltf.scene);
            if (indice.dientes.size !== 52 || indice.superficies.length !== 260) {
                mostrarError(new Error('El modelo no contiene las 52 piezas con sus cinco superficies.'));
                return;
            }
            scene.add(gltf.scene);
            sincronizar();
            botonesModelo.forEach(boton => boton.disabled = false);
            status.textContent = 'Modelo listo. Selecciona una pieza o una cara para editarla.';
        }, progreso => {
            if (progreso.total) status.textContent = `Cargando modelo dental 3D… ${Math.round(progreso.loaded / progreso.total * 100)}%`;
        }, mostrarError);
        window.addEventListener('pagehide', evento => {
            if (evento.persisted) return;
            destruido = true;
            limpiarPantalla();
            resize.disconnect();
            renderer.setAnimationLoop(null);
            controls.dispose();
            const materiales = new Set();
            const texturas = new Set();
            const geometrias = new Set();
            scene.traverse(objeto => {
                if (objeto.geometry) geometrias.add(objeto.geometry);
                if (!objeto.material) return;
                const lista = Array.isArray(objeto.material) ? objeto.material : [objeto.material];
                lista.forEach(material => {
                    materiales.add(material);
                    Object.values(material).filter(valor => valor?.isTexture).forEach(textura => texturas.add(textura));
                });
            });
            geometrias.forEach(geometria => geometria.dispose());
            materiales.forEach(material => material.dispose());
            texturas.forEach(textura => textura.dispose());
            renderer.dispose();
        }, { once: true });
    } catch (error) {
        mostrarError(error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarEditor, { once: true });
} else {
    iniciarEditor();
}
