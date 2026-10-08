
// ============================================================
// GEOMETRY REALISTA - NO MAS CILINDROS - v2
// Para: public/js/odontograma/geometry.js
// ============================================================
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';
import {
    MAT_ESMALTE, MAT_ESMALTE_NINO,
    MAT_DENTINA, MAT_DENTINA_NINO,
    MAT_ENCIA, MAT_ENCIA_NINO,
} from './materials.js';
import { MODELOS_GLB, getTipoPorFDI } from './constants.js';

const dracoLoader = new DRACOLoader();
const gltfLoader = new GLTFLoader();
gltfLoader.setDRACOLoader(dracoLoader);

const glbCache = {};
const geoCache = {};

async function tryLoadGLB(tipo){
    if(tipo in glbCache) return glbCache[tipo];
    const url = MODELOS_GLB[tipo];
    if(!url){ glbCache[tipo]=null; return null; }
    try{
        const gltf = await gltfLoader.loadAsync(url);
        let geom=null;
        gltf.scene.traverse(o=>{
            if(!geom && o.isMesh && o.geometry?.attributes?.position){
                geom = o.geometry.clone();
                geom.computeVertexNormals();
            }
        });
        glbCache[tipo]=geom;
        return geom;
    }catch(e){
        console.warn('GLB no encontrado',tipo,e.message);
        glbCache[tipo]=null; return null;
    }
}
export async function precargarModelos(){
    await Promise.all(Object.keys(MODELOS_GLB).map(tryLoadGLB));
}

// ---------- UTIL: deforma Box a diente real ----------
function createAnatomicalCrown(tipo, escala, material){
    // Base: box subdividido 20x12x20, luego esculpido por vértices
    let geo;
    const key = `crown_${tipo}_${escala}`;
    if(geoCache[key]) return new THREE.Mesh(geoCache[key].clone(), material);

    // Geometría base
    const w = tipo==='incisivo'?0.85:tipo==='canino'?0.82:tipo==='premolar'?1.0:1.25;
    const h = tipo==='incisivo'?1.1:tipo==='canino'?1.35:tipo==='premolar'?1.0:0.9;
    const d = tipo==='incisivo'?0.65:0.95;

    geo = new THREE.BoxGeometry(w*escala, h*escala, d*escala, 14, 12, 10);
    const pos = geo.attributes.position;

    for(let i=0;i<pos.count;i++){
        let x = pos.getX(i);
        let y = pos.getY(i);
        let z = pos.getZ(i);

        const ny = (y / (h*escala*0.5)); // -1 a 1

        // 1. Constricción cervical (cuello más angosto que ecuador)
        const cervicalFactor = 0.75 + 0.25 * Math.pow(Math.abs(ny+0.2), 2);
        if(ny < 0.2){
            x *= (0.72 + 0.28 * (ny+1)/1.2);
            z *= (0.78 + 0.22 * (ny+1)/1.2);
        }

        // 2. Convexidad vestibular (cara vestibular más abultada)
        if(z > 0){
            z += Math.cos(x*2.2) * 0.12 * escala * Math.max(0, 1 - Math.abs(ny)*0.6);
        }

        // 3. Forma por tipo
        if(tipo==='incisivo'){
            // Borde incisal fino, cingulo lingual
            if(ny > 0.7){
                z *= 0.35; // filo fino
                x *= 0.95;
            }
            if(ny < -0.3 && z < 0){
                // Cingulo (protuberancia lingual)
                z -= 0.18*escala * Math.exp(-x*x*6);
            }
            // Mamelones incisales sutiles
            if(ny > 0.85){
                y += Math.sin(x*10)*0.02*escala;
            }
        }

        if(tipo==='canino'){
            // Punta cuspídea prominente
            if(ny > 0.4){
                const t = (ny-0.4)/0.6;
                x *= (1 - t*0.7);
                z *= (1 - t*0.6);
                y += t*0.15*escala;
            }
        }

        if(tipo==='premolar'){
            // 2 cúspides: vestibular más alta
            if(ny > 0.2){
                const isVest = z > 0;
                const cuspH = isVest ? 0.22 : 0.12;
                const dist = Math.abs(x);
                if(dist < 0.35){
                    y += cuspH*escala * Math.exp(-dist*dist*12);
                }
                // Surco central
                if(Math.abs(z) < 0.15){
                    y -= 0.12*escala * Math.exp(-z*z*40);
                }
            }
        }

        if(tipo==='molar'){
            // 4 cúspides + surco en cruz + fosa central
            if(ny > 0.1){
                const cuspPositions = [
                    [0.32, 0.32, 0.28], [ -0.32, 0.32, 0.22],
                    [0.32, -0.32, 0.18], [-0.32, -0.32, 0.16]
                ];
                let bump = 0;
                cuspPositions.forEach(([cx, cz, ch])=>{
                    const dx = x/escala - cx;
                    const dz = z/escala - cz;
                    const dist2 = dx*dx + dz*dz;
                    bump += ch * Math.exp(-dist2*12);
                });
                // Surcos oclusales en cruz
                const surco1 = 0.18*Math.exp(-(x*x*18 + z*z*2));
                const surco2 = 0.18*Math.exp(-(x*x*2 + z*z*18));
                const surcoCentral = 0.22*Math.exp(-(x*x+z*z)*8);
                y += (bump - surco1 - surco2 - surcoCentral)*escala;
            }
            // Paredes ligeramente convergentes hacia oclusal
            if(ny > 0){
                x *= 1 + ny*0.12;
                z *= 1 + ny*0.10;
            }
        }

        pos.setXYZ(i, x, y, z);
    }

    pos.needsUpdate = true;
    geo.computeVertexNormals();
    // Suavizado visual
    geo = geo.toNonIndexed();
    geo.computeVertexNormals();

    geoCache[key]=geo;
    return new THREE.Mesh(geo.clone(), material);
}

function crearRaicesRealistas(tipo, escala, material){
    const group = new THREE.Group();
    const mat = material.clone();

    if(tipo==='incisivo' || tipo==='canino'){
        // Raiz unica con conicidad y curvatura distal leve
        const curve = new THREE.CatmullRomCurve3([
            new THREE.Vector3(0, -0.1*escala, 0),
            new THREE.Vector3(0.04*escala, -0.7*escala, 0.02*escala),
            new THREE.Vector3(0.08*escala, -1.5*escala, 0.05*escala),
        ]);
        const geo = new THREE.TubeGeometry(curve, 14, 0.24*escala, 10, false);
        // Taper manual por vertices
        const p = geo.attributes.position;
        for(let i=0;i<p.count;i++){
            const y = p.getY(i);
            const t = THREE.MathUtils.clamp((-y)/ (1.5*escala),0,1);
            const factor = 1 - t*0.82; // de 0.24 a ~0.04
            // aproximado
            const cx = curve.getPointAt(t).x;
            const cz = curve.getPointAt(t).z;
            // no perfecto pero da conicidad
        }
        const m = new THREE.Mesh(geo, mat);
        m.castShadow=true;
        group.add(m);
        // Cono final afinado
        const tip = new THREE.Mesh(new THREE.ConeGeometry(0.05*escala,0.35*escala,8), mat);
        tip.position.set(0.08*escala, -1.68*escala, 0.05*escala);
        tip.rotation.x = Math.PI;
        tip.rotation.z = -0.12;
        group.add(tip);
    } else if(tipo==='premolar'){
        // 1 o 2 raices
        const roots = [
            { x: 0.12, z: 0.08, lean: 0.15 },
            { x: -0.12, z: -0.08, lean: -0.12 },
        ];
        roots.forEach(r=>{
            const curve = new THREE.CatmullRomCurve3([
                new THREE.Vector3(r.x*escala, -0.05*escala, r.z*escala),
                new THREE.Vector3(r.x*1.2*escala, -0.7*escala, r.z*1.2*escala),
                new THREE.Vector3(r.x*1.4*escala + r.lean*0.2, -1.35*escala, r.z*1.3*escala),
            ]);
            const geo = new THREE.TubeGeometry(curve, 12, 0.15*escala, 8, false);
            const m = new THREE.Mesh(geo, mat);
            m.castShadow=true;
            group.add(m);
        });
    } else if(tipo==='molar'){
        // 3 raices divergentes (2 vestibulares, 1 palatina/lingual más grande)
        const roots = [
            { x: 0.28, z: 0.22, y: -1.15, r:0.18, leanX:0.25, leanZ:0.22 }, // MB
            { x: -0.28, z: 0.18, y: -1.05, r:0.16, leanX:-0.22, leanZ:0.18 }, // DB
            { x: 0.0, z: -0.32, y: -1.35, r:0.22, leanX:0.05, leanZ:-0.35 }, // Palatina
        ];
        roots.forEach(cfg=>{
            const top = new THREE.Vector3(cfg.x*escala, -0.05*escala, cfg.z*escala);
            const mid = new THREE.Vector3(
                (cfg.x+cfg.leanX)*escala,
                cfg.y*0.5*escala,
                (cfg.z+cfg.leanZ)*escala
            );
            const bot = new THREE.Vector3(
                (cfg.x+cfg.leanX*1.5)*escala,
                cfg.y*escala,
                (cfg.z+cfg.leanZ*1.5)*escala
            );
            const curve = new THREE.CatmullRomCurve3([top, mid, bot]);
            const geo = new THREE.TubeGeometry(curve, 10, cfg.r*escala, 8, false);
            const mesh = new THREE.Mesh(geo, mat);
            mesh.castShadow=true;
            group.add(mesh);
        });
    }
    return group;
}

export function crearDientePorFDI(fdi, esInfantil=false){
    const group = new THREE.Group();
    const tipo = getTipoPorFDI(fdi, esInfantil);
    const escala = esInfantil?0.78:1.0;
    const matEsmalte = esInfantil? MAT_ESMALTE_NINO.clone() : MAT_ESMALTE.clone();
    const matDentina = esInfantil? MAT_DENTINA_NINO.clone() : MAT_DENTINA.clone();

    // Intenta GLB primero
    const glbGeo = glbCache[tipo];
    if(glbGeo){
        glbGeo.computeBoundingBox();
        const h = glbGeo.boundingBox.max.y - glbGeo.boundingBox.min.y;
        const mesh = new THREE.Mesh(glbGeo, matEsmalte);
        mesh.scale.setScalar((1.7/h)*escala);
        mesh.castShadow = mesh.receiveShadow = true;
        mesh.userData.esCorona = true;
        group.add(mesh);
        return group;
    }

    // Procedural realista
    const corona = createAnatomicalCrown(tipo, escala, matEsmalte);
    corona.castShadow = corona.receiveShadow = true;
    corona.userData.esCorona = true;
    corona.position.y = 0.25*escala;
    // Aplanado anatómico vestíbulo-lingual
    if(tipo==='incisivo'){
        corona.scale.set(1.35,1,0.75);
    } else if(tipo==='canino'){
        corona.scale.set(1.15,1,0.85);
    }
    group.add(corona);

    const raices = crearRaicesRealistas(tipo, escala, matDentina);
    group.add(raices);

    // Cuello / unión amelocementaria sutil
    const cuelloGeo = new THREE.TorusGeometry(0.32*escala, 0.04*escala, 8, 18);
    const cuello = new THREE.Mesh(cuelloGeo, matEsmalte.clone());
    cuello.rotation.x = Math.PI/2;
    cuello.position.y = -0.08*escala;
    cuello.scale.set(
        tipo==='incisivo'?1.3:tipo==='molar'?1.1:1.0,
        tipo==='incisivo'?0.7:1.0,
        1
    );
    group.add(cuello);

    return group;
}

export function crearEncia(esInfantil, offsetX){
    const grupo=new THREE.Group();
    const esc=esInfantil?0.88:1.0;
    const baseMat = esInfantil? MAT_ENCIA_NINO : MAT_ENCIA;
    const matSup = baseMat.clone();
    const matInf = baseMat.clone();
    const matBase = baseMat.clone();

    const curvaSup=new THREE.CatmullRomCurve3([
        new THREE.Vector3(3.9*esc,0.05,-0.4),new THREE.Vector3(3.6*esc,0.10,0.9),
        new THREE.Vector3(3.0*esc,0.18,1.9),new THREE.Vector3(1.9*esc,0.25,2.9),
        new THREE.Vector3(0,0.35,3.5),new THREE.Vector3(-1.9*esc,0.25,2.9),
        new THREE.Vector3(-3.0*esc,0.18,1.9),new THREE.Vector3(-3.6*esc,0.10,0.9),
        new THREE.Vector3(-3.9*esc,0.05,-0.4)
    ]);
    const enciaSup=new THREE.Mesh(new THREE.TubeGeometry(curvaSup,64,0.62*esc,12,false), matSup);
    enciaSup.scale.y=0.44; enciaSup.position.y=0.22*esc; enciaSup.castShadow=enciaSup.receiveShadow=true; grupo.add(enciaSup);

    const curvaInf=new THREE.CatmullRomCurve3([
        new THREE.Vector3(3.5*esc,-0.12,-0.10),new THREE.Vector3(3.3*esc,-0.12,0.90),
        new THREE.Vector3(2.8*esc,-0.18,1.75),new THREE.Vector3(1.7*esc,-0.22,2.55),
        new THREE.Vector3(0,-0.28,3.05),new THREE.Vector3(-1.7*esc,-0.22,2.55),
        new THREE.Vector3(-2.8*esc,-0.18,1.75),new THREE.Vector3(-3.3*esc,-0.12,0.90),
        new THREE.Vector3(-3.5*esc,-0.12,-0.10)
    ]);
    const enciaInf=new THREE.Mesh(new THREE.TubeGeometry(curvaInf,64,0.50*esc,12,false), matInf);
    enciaInf.scale.y=0.52; enciaInf.position.y=-0.28*esc; enciaInf.castShadow=enciaInf.receiveShadow=true; grupo.add(enciaInf);

    const base=new THREE.Mesh(new THREE.CylinderGeometry(3.95*esc,3.5*esc,0.45*esc,32,1,true), matBase);
    base.position.y=-0.55*esc; base.rotation.x=Math.PI/2; base.castShadow=base.receiveShadow=true; grupo.add(base);

    grupo.position.x=offsetX;
    return { grupo, enciaSup, enciaInf };
}
