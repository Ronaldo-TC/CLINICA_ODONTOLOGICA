
// ============================================================
// MATERIALS REALISTA v2 - PBR mejorado
// public/js/odontograma/materials.js
// ============================================================
import * as THREE from 'three';
import { TEXTURAS_ENCIA } from './constants.js';

const texLoader = new THREE.TextureLoader();
texLoader.setCrossOrigin('anonymous');

function tryLoadTexture(path, colorSpace = THREE.SRGBColorSpace){
    return new Promise((resolve)=>{
        texLoader.load(path, (tex)=>{
            tex.colorSpace = colorSpace;
            tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
            tex.repeat.set(2,2);
            resolve(tex);
        }, undefined, ()=>resolve(null));
    });
}

// Esmalte adulto - translúcido, con micro-aspereza
export const MAT_ESMALTE = new THREE.MeshPhysicalMaterial({
    color: 0xFFFEF8,
    roughness: 0.18,          // más pulido
    metalness: 0.0,
    clearcoat: 1.0,           // capa brillante
    clearcoatRoughness: 0.08, // muy pulido
    sheen: 0.7,
    sheenColor: new THREE.Color(0xFFF4E0),
    sheenRoughness: 0.45,
    transmission: 0.12,       // translucidez real
    thickness: 0.6,
    ior: 1.56,                // IOR esmalte real 1.62, bajamos un poco para WebGL
    attenuationColor: new THREE.Color(0xFFF8E8),
    attenuationDistance: 0.8,
});

// Infantil - más blanco azulado, menos translúcido
export const MAT_ESMALTE_NINO = new THREE.MeshPhysicalMaterial({
    color: 0xFFFFFF,
    roughness: 0.22,
    metalness: 0.0,
    clearcoat: 0.85,
    clearcoatRoughness: 0.14,
    sheen: 0.5,
    sheenColor: new THREE.Color(0xE8F6FF),
    transmission: 0.08,
    thickness: 0.4,
    ior: 1.50,
});

export const MAT_DENTINA = new THREE.MeshStandardMaterial({
    color: 0xE8D5A8,
    roughness: 0.72,
    metalness: 0.0,
    bumpScale: 0.02,
});

export const MAT_DENTINA_NINO = new THREE.MeshStandardMaterial({
    color: 0xF2E2B8,
    roughness: 0.78,
});

export const MAT_ENCIA = new THREE.MeshPhysicalMaterial({
    color: 0xC85D7A,
    roughness: 0.48,
    metalness: 0.0,
    clearcoat: 0.35,
    clearcoatRoughness: 0.38,
    sheen: 0.8,
    sheenColor: new THREE.Color(0xFFB8C8),
    transmission: 0.08,
    thickness: 0.5,
});

export const MAT_ENCIA_NINO = new THREE.MeshPhysicalMaterial({
    color: 0xE78AA3,
    roughness: 0.52,
    clearcoat: 0.25,
    sheen: 0.6,
});

const MATERIALES_HALLAZGO = {};
export function getMaterialHallazgo(condicion, colorHex){
    const key = `${condicion}_${colorHex}`;
    if(!MATERIALES_HALLAZGO[key]){
        const color = new THREE.Color(colorHex);
        MATERIALES_HALLAZGO[key] = new THREE.MeshPhysicalMaterial({
            color: color.clone().lerp(new THREE.Color(0xffffff), 0.35),
            roughness: condicion==='corona'?0.12:0.32,
            metalness: condicion==='corona'?0.65:0.0,
            clearcoat: 0.7,
            clearcoatRoughness: 0.18,
            emissive: color,
            emissiveIntensity: 0.08,
        });
    }
    return MATERIALES_HALLAZGO[key];
}

export async function cargarTexturasEncia(){
    const [color, normal, rough] = await Promise.all([
        tryLoadTexture(TEXTURAS_ENCIA.color),
        tryLoadTexture(TEXTURAS_ENCIA.normal, THREE.NoColorSpace),
        tryLoadTexture(TEXTURAS_ENCIA.roughness, THREE.NoColorSpace),
    ]);
    if(color){ MAT_ENCIA.map=color; MAT_ENCIA_NINO.map=color; }
    if(normal){
        MAT_ENCIA.normalMap=normal; MAT_ENCIA_NINO.normalMap=normal;
        MAT_ENCIA.normalScale.set(0.7,0.7);
        MAT_ENCIA_NINO.normalScale.set(0.55,0.55);
    }
    if(rough){ MAT_ENCIA.roughnessMap=rough; MAT_ENCIA_NINO.roughnessMap=rough; }
    if(color||normal||rough){
        MAT_ENCIA.needsUpdate=true;
        MAT_ENCIA_NINO.needsUpdate=true;
    }
}
