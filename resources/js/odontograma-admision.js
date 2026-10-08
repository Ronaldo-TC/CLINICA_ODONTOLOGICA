import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import {
    CARAS_ADMISION, actualizarPieza, aplicarEstadoModelo, esVisibleEnEscena,
    identificarPieza, limitesDenticion, limpiarDenticion, piezasDeDenticion, prepararModeloAdmision,
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
    const botonesModelo = ['aplicar', 'extraer', 'restaurar'].map(control);
    let indice = null;
    let renderer = null;
    let camera = null;
    let controls = null;
    let scene = null;
    let labels = null;
    let ultimaDenticion = null;
    let destruido = false;
    const accesorios = new Map();

    const guardar = (datos, tipo = denticion()) => {
        const limpio = window.Odontograma.set(odontograma, datos, tipo);
        odontograma.dispatchEvent(new CustomEvent('odontograma:cambio', {
            bubbles: true, detail: { datos: limpio, denticion: tipo },
        }));
    };

    const seleccionar = (pieza, cara = null) => {
        selectorPieza.value = pieza;
        if (cara) {
            selectorCara.value = cara;
            selectorModo.value = 'cara';
        }
        sincronizar();
    };

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
        titulo.textContent = `Pieza ${selectorPieza.value} · selecciona una condición y pulsa Aplicar.`;
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
            imagen.width = marcas.length ? 512 : 128;
            imagen.height = 64;
            const contexto = imagen.getContext('2d');
            contexto.fillStyle = 'rgba(255,255,255,0.94)';
            contexto.fillRect(0, 0, imagen.width, 64);
            contexto.font = 'bold 32px sans-serif';
            contexto.textAlign = 'center';
            contexto.textBaseline = 'middle';
            contexto.fillStyle = selectorPieza.value === pieza ? '#0e7490' : '#334155';
            contexto.fillText(pieza, imagen.width / 2, marcas.length ? 18 : 32);
            if (marcas.length) {
                contexto.font = '20px sans-serif';
                const anchoMarca = 512 / marcas.length;
                marcas.forEach((marca, index) => {
                    contexto.fillStyle = marca.color === 'azul' ? '#2563eb' : '#dc2626';
                    contexto.fillText(catalogo[marca.estado]?.simbolo || catalogo[marca.estado]?.etiqueta || marca.estado, anchoMarca * (index + 0.5), 47, anchoMarca - 4);
                });
            }
            const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(imagen), depthTest: false }));
            const superficie = indice.superficies.find(objeto => String(objeto.userData.fdi) === pieza && objeto.userData.surface === 'O');
            new THREE.Box3().setFromObject(superficie || diente).getCenter(sprite.position);
            sprite.position.y += 0.009;
            sprite.scale.set(marcas.length ? 0.036 : 0.009, 0.0045, 1);
            sprite.userData.fdi = pieza;
            sprite.renderOrder = 10;
            labels.add(sprite);
        });
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
                        material.color.set(marca.color === 'azul' ? '#2563eb' : '#dc2626');
                        material.vertexColors = false;
                        material.needsUpdate = true;
                    });
                });
            });
        });
    };

    const centrar = () => {
        if (!indice) return;
        const limites = limitesDenticion(indice, denticion());
        if (limites.isEmpty()) return;
        const centro = limites.getCenter(new THREE.Vector3());
        const size = limites.getSize(new THREE.Vector3());
        const distancia = Math.max(size.y, size.x / camera.aspect, size.z) / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2))) * 1.6;
        controls.target.copy(centro);
        camera.position.copy(centro).add(new THREE.Vector3(distancia * 0.25, distancia * 0.3, distancia));
        controls.minDistance = distancia * 0.15;
        controls.maxDistance = distancia * 5;
        controls.update();
    };

    function sincronizar() {
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
    odontograma.addEventListener('odontograma:actualizado', sincronizar);
    selectorDenticion.addEventListener('change', () => guardar(estado(), selectorDenticion.value));
    [selectorPieza, selectorCara, selectorModo, control('encias'), control('raices'), control('huesos')].forEach(elemento => elemento.addEventListener('change', sincronizar));
    selectorCondicion.value = 'caries';
    control('aplicar').addEventListener('click', () => guardar(actualizarPieza(estado(), {
        pieza: selectorPieza.value, cara: selectorCara.value, estado: selectorCondicion.value,
        modo: selectorModo.value, color: selectorColor.value,
    })));
    control('extraer').addEventListener('click', () => guardar(actualizarPieza(estado(), {
        pieza: selectorPieza.value, estado: 'ausente', modo: 'marca', color: selectorColor.value,
    })));
    control('restaurar').addEventListener('click', () => guardar(actualizarPieza(estado(), { pieza: selectorPieza.value, modo: 'borrar' })));
    control('limpiar').addEventListener('click', () => {
        if (window.confirm('¿Borrar las marcas de la dentición visible?')) guardar(limpiarDenticion(estado(), denticion()));
    });
    control('centrar').addEventListener('click', centrar);
    sincronizar();

    const mostrarError = error => {
        console.error('No se pudo iniciar el odontograma 3D.', error);
        status.textContent = 'No se pudo cargar el editor 3D. Puede completar el odontograma en la vista 2D.';
        document.getElementById('adm-odontograma-alternativo').open = true;
        botonesModelo.forEach(boton => boton.disabled = true);
    };

    try {
        scene = new THREE.Scene();
        scene.background = new THREE.Color('#eef2f7');
        camera = new THREE.PerspectiveCamera(38, 1, 0.0005, 10);
        renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        controls = new OrbitControls(camera, canvas);
        controls.enableDamping = true;
        scene.add(new THREE.HemisphereLight(0xffffff, 0x64748b, 2));
        const luz = new THREE.DirectionalLight(0xffffff, 2.5);
        luz.position.set(0.2, 0.4, 0.5);
        scene.add(luz);
        labels = new THREE.Group();
        scene.add(labels);
        const resize = new ResizeObserver(() => {
            const { clientWidth: width, clientHeight: height } = canvas;
            if (!width || !height) return;
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            centrar();
        });
        resize.observe(canvas.parentElement);
        const raycaster = new THREE.Raycaster();
        const pointer = new THREE.Vector2();
        let inicio = null;
        let arrastrando = false;
        canvas.addEventListener('pointerdown', evento => {
            if (!evento.isPrimary || evento.button !== 0) {
                inicio = null;
                return;
            }
            inicio = { x: evento.clientX, y: evento.clientY };
            arrastrando = false;
        });
        canvas.addEventListener('pointermove', evento => {
            if (inicio && Math.hypot(evento.clientX - inicio.x, evento.clientY - inicio.y) > 5) arrastrando = true;
        });
        canvas.addEventListener('pointercancel', () => inicio = null);
        canvas.addEventListener('pointerup', evento => {
            const seleccionarClick = inicio && !arrastrando && Math.hypot(evento.clientX - inicio.x, evento.clientY - inicio.y) <= 5;
            inicio = null;
            if (!indice || !seleccionarClick) return;
            const rect = canvas.getBoundingClientRect();
            pointer.set(((evento.clientX - rect.left) / rect.width) * 2 - 1, -((evento.clientY - rect.top) / rect.height) * 2 + 1);
            raycaster.setFromCamera(pointer, camera);
            const objetos = [...indice.seleccionables, ...labels.children].filter(esVisibleEnEscena);
            const interseccion = raycaster.intersectObjects(objetos, false)[0];
            const diente = interseccion ? identificarPieza(interseccion.object) : null;
            if (diente) seleccionar(diente.pieza, diente.cara);
        });
        renderer.setAnimationLoop(() => {
            controls.update();
            renderer.render(scene, camera);
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
