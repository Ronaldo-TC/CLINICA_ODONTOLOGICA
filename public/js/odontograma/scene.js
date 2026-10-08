
// SCENE MEJORADO - Iluminación clínica realista
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

export function crearEscena(canvas){
    const W = canvas.clientWidth || 800;
    const H = canvas.clientHeight || 600;
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0E121C); // azul muy oscuro, más clínico
    scene.fog = new THREE.Fog(0x0E121C, 28, 65);

    const camera = new THREE.PerspectiveCamera(32, W/H, 0.1, 500);
    camera.position.set(0, 10, 22);
    camera.lookAt(0,0,1.2);

    const renderer = new THREE.WebGLRenderer({
        canvas, antialias:true, alpha:false,
        powerPreference:'high-performance',
    });
    renderer.setSize(W,H,false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.25; // un poco más brillante para esmalte
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.03).texture;

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.06;
    controls.minDistance = 5;
    controls.maxDistance = 50;
    controls.maxPolarAngle = Math.PI*0.88;
    controls.target.set(0,0,1.2);
    controls.update();
    controls.saveState();

    return { scene, camera, renderer, controls };
}

export function configurarLuces(scene){
    scene.add(new THREE.AmbientLight(0xDDE8FF, 0.45));

    const key = new THREE.DirectionalLight(0xFFFBF0, 3.0);
    key.position.set(9, 20, 10);
    key.castShadow=true;
    key.shadow.mapSize.set(2048,2048);
    key.shadow.camera.near=1; key.shadow.camera.far=70;
    key.shadow.camera.left=-20; key.shadow.camera.right=20;
    key.shadow.camera.top=20; key.shadow.camera.bottom=-20;
    key.shadow.bias=-0.0009;
    key.shadow.normalBias=0.02;
    scene.add(key);

    const fill = new THREE.DirectionalLight(0xA8C8FF, 1.2);
    fill.position.set(-14, 8, -8);
    scene.add(fill);

    const rim = new THREE.DirectionalLight(0xFFDDBB, 1.6);
    rim.position.set(-6, 5, -18);
    scene.add(rim);

    // Luz cenital suave para resaltar cúspides
    const top = new THREE.SpotLight(0xFFFFFF, 1.4, 38, Math.PI/3.5, 0.25, 1.2);
    top.position.set(0,16,6);
    top.target.position.set(0,0,2);
    top.castShadow=true;
    scene.add(top, top.target);

    const front = new THREE.PointLight(0xFFF2D0, 1.0, 30, 2);
    front.position.set(0,5,14);
    scene.add(front);
}

export function crearSuelo(scene){
    const geo = new THREE.CircleGeometry(32, 64);
    const mat = new THREE.MeshStandardMaterial({ color:0x0B1220, roughness:0.92, metalness:0.08 });
    const suelo = new THREE.Mesh(geo, mat);
    suelo.rotation.x=-Math.PI/2;
    suelo.position.y=-3.8;
    suelo.receiveShadow=true;
    scene.add(suelo);
}
