import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import {
    aplicarEstadoModelo,
    esVisibleEnEscena,
    identificarPieza,
    limitesDenticion,
    prepararModeloAdmision,
} from '../../public/js/odontograma/admision.js';

const CARAS = {
    o: 'Oclusal / incisal',
    m: 'Mesial',
    d: 'Distal',
    v: 'Vestibular',
    l: 'Lingual / palatina',
};

export function leerJson(valor, respaldo = {}) {
    try {
        const datos = JSON.parse(valor || '');

        return datos && typeof datos === 'object' && !Array.isArray(datos) ? datos : respaldo;
    } catch {
        return respaldo;
    }
}

export function describirPieza(pieza, datos, catalogo) {
    const registro = datos[pieza] || {};
    const descripciones = Object.entries(CARAS)
        .filter(([cara]) => registro[cara])
        .map(([cara, etiqueta]) => `${etiqueta}: ${catalogo[registro[cara]]?.etiqueta || registro[cara]}`);

    (registro.marcas || []).forEach((marca) => {
        const color = marca.color ? ` (${marca.color})` : '';
        descripciones.push(`${catalogo[marca.estado]?.etiqueta || marca.estado}${color}`);
    });

    return descripciones.length
        ? `Pieza ${pieza} · ${descripciones.join(' · ')}`
        : `Pieza ${pieza} · Sin condiciones registradas.`;
}

function inicializarVisor(visor) {
    const contenedor = visor.closest('.odontogram-panel');
    const panel2d = contenedor?.querySelector('[data-ficha-odontograma-panel="2d"]');
    const tabs = [...(contenedor?.querySelectorAll('[data-ficha-odontograma-tab]') || [])];
    const fuente = document.getElementById(visor.dataset.sourceId);
    const canvas = visor.querySelector('[data-ficha-3d-canvas]');
    const estado = visor.querySelector('[data-ficha-3d-status]');
    const detalle = visor.querySelector('[data-ficha-3d-detalle]');
    const selectorDenticion = visor.querySelector('[data-ficha-3d-denticion]');
    const selectorVista = visor.querySelector('[data-ficha-3d-vista]');
    const mostrarEncias = visor.querySelector('[data-ficha-3d-encias]');
    const mostrarRaices = visor.querySelector('[data-ficha-3d-raices]');
    const mostrarHuesos = visor.querySelector('[data-ficha-3d-huesos]');
    const giroAutomatico = visor.querySelector('[data-ficha-3d-giro]');
    const botonCentrar = visor.querySelector('[data-ficha-3d-centrar]');
    const botonPantalla = visor.querySelector('[data-ficha-3d-pantalla]');

    if (!contenedor || !panel2d || !fuente || !canvas || !estado) return;

    const datos = leerJson(fuente.dataset.valor);
    const catalogo = leerJson(fuente.dataset.estados);
    let cargando = null;
    let indice = null;
    let renderer = null;
    let camera = null;
    let controls = null;
    let resizeObserver = null;
    let piezaSeleccionada = null;

    const denticionActual = () => selectorDenticion.value;

    const orientar = () => {
        if (!indice || !camera || !controls) return;
        const limites = limitesDenticion(indice, denticionActual());
        if (limites.isEmpty()) return;

        const centro = limites.getCenter(new THREE.Vector3());
        const tamano = limites.getSize(new THREE.Vector3());
        const distancia = Math.max(tamano.y, tamano.x / camera.aspect, tamano.z)
            / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2))) * 1.7;
        const direcciones = {
            perspectiva: [0.28, 0.34, 1],
            frontal: [0, 0, 1],
            superior: [0, 1, 0.001],
            inferior: [0, -1, 0.001],
            derecha: [-1, 0, 0],
            izquierda: [1, 0, 0],
            posterior: [0, 0, -1],
        };
        const direccion = direcciones[selectorVista.value] || direcciones.perspectiva;

        controls.target.copy(centro);
        camera.position.copy(centro).add(new THREE.Vector3(...direccion).normalize().multiplyScalar(distancia));
        controls.minDistance = 0.003;
        controls.maxDistance = 1;
        controls.update();
    };

    const aplicarOpciones = () => {
        if (!indice) return;
        aplicarEstadoModelo(indice, datos, catalogo, {
            denticion: denticionActual(),
            encias: mostrarEncias.checked,
            raices: mostrarRaices.checked,
            huesos: mostrarHuesos.checked,
            seleccion: piezaSeleccionada ? { pieza: piezaSeleccionada, cara: null } : null,
        });
    };

    const cargar = async () => {
        if (cargando) return cargando;

        cargando = (async () => {
            try {
                estado.textContent = 'Cargando modelo dental 3D…';
                const scene = new THREE.Scene();
                camera = new THREE.PerspectiveCamera(38, 1, 0.0005, 10);
                renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.outputColorSpace = THREE.SRGBColorSpace;
                renderer.toneMapping = THREE.ACESFilmicToneMapping;
                renderer.toneMappingExposure = 1.05;

                controls = new OrbitControls(camera, canvas);
                controls.enableDamping = true;
                controls.enablePan = true;
                controls.autoRotateSpeed = 0.8;

                scene.add(new THREE.HemisphereLight(0xffffff, 0x496176, 1.5));
                const luzPrincipal = new THREE.DirectionalLight(0xffffff, 2.2);
                luzPrincipal.position.set(0.2, 0.5, 0.7);
                scene.add(luzPrincipal);
                const luzRelleno = new THREE.DirectionalLight(0xbde9ff, 1.1);
                luzRelleno.position.set(-0.5, -0.1, -0.4);
                scene.add(luzRelleno);

                resizeObserver = new ResizeObserver(() => {
                    const ancho = canvas.clientWidth;
                    const alto = canvas.clientHeight;
                    if (!ancho || !alto) return;
                    renderer.setSize(ancho, alto, false);
                    camera.aspect = ancho / alto;
                    camera.updateProjectionMatrix();
                });
                resizeObserver.observe(canvas.parentElement);

                const gltf = await new GLTFLoader().loadAsync(visor.dataset.modelUrl);
                indice = prepararModeloAdmision(gltf.scene);
                scene.add(gltf.scene);
                aplicarOpciones();
                orientar();

                const raycaster = new THREE.Raycaster();
                const pointer = new THREE.Vector2();
                canvas.addEventListener('click', (evento) => {
                    const rectangulo = canvas.getBoundingClientRect();
                    pointer.x = ((evento.clientX - rectangulo.left) / rectangulo.width) * 2 - 1;
                    pointer.y = -((evento.clientY - rectangulo.top) / rectangulo.height) * 2 + 1;
                    raycaster.setFromCamera(pointer, camera);
                    const seleccion = raycaster.intersectObjects(indice.seleccionables.filter(esVisibleEnEscena), true)
                        .map(interseccion => identificarPieza(interseccion.object))
                        .find(Boolean);
                    if (!seleccion) return;
                    piezaSeleccionada = seleccion.pieza;
                    detalle.textContent = describirPieza(piezaSeleccionada, datos, catalogo);
                    aplicarOpciones();
                });

                renderer.setAnimationLoop(() => {
                    controls.autoRotate = giroAutomatico.checked;
                    controls.update();
                    renderer.render(scene, camera);
                });
                estado.textContent = 'Arrastra para girar, usa la rueda para acercar y selecciona una pieza para ver su detalle.';
            } catch (error) {
                console.error('No se pudo cargar la visualización 3D de la ficha.', error);
                estado.textContent = 'No se pudo cargar el modelo 3D. La vista 2D continúa disponible.';
                throw error;
            }
        })();

        return cargando;
    };

    const cambiarVista = (modo) => {
        const es3d = modo === '3d';
        panel2d.hidden = es3d;
        visor.hidden = !es3d;
        tabs.forEach((tab) => {
            const activo = tab.dataset.fichaOdontogramaTab === modo;
            tab.setAttribute('aria-selected', String(activo));
            tab.tabIndex = activo ? 0 : -1;
        });
        if (es3d) {
            requestAnimationFrame(() => cargar().catch(() => {}));
        }
    };

    tabs.forEach(tab => tab.addEventListener('click', () => cambiarVista(tab.dataset.fichaOdontogramaTab)));
    [selectorDenticion, mostrarEncias, mostrarRaices, mostrarHuesos].forEach((control) => {
        control.addEventListener('change', () => {
            aplicarOpciones();
            if (control === selectorDenticion) orientar();
        });
    });
    selectorVista.addEventListener('change', orientar);
    botonCentrar.addEventListener('click', orientar);
    botonPantalla.addEventListener('click', async () => {
        if (document.fullscreenElement === visor) {
            await document.exitFullscreen();
        } else {
            await visor.requestFullscreen();
        }
    });
    document.addEventListener('fullscreenchange', () => {
        botonPantalla.textContent = document.fullscreenElement === visor ? 'Salir de pantalla completa' : 'Pantalla completa';
    });
    window.addEventListener('pagehide', () => {
        resizeObserver?.disconnect();
        renderer?.setAnimationLoop(null);
        renderer?.dispose();
    }, { once: true });
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-ficha-odontograma-visor]').forEach(inicializarVisor);
}
