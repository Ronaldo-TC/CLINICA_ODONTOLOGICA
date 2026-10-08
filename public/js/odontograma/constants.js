// ============================================================
// CONSTANTS — Colores, posiciones FDI y rutas - CORREGIDO
// ============================================================

export const COLORES = {
    caries: '#FF0000',
    obturacion: '#0066FF',
    corona: '#FFD700',
    ausente: '#808080',
    implante: '#00CED1',
    endodoncia: '#8B00FF',
    fractura: '#FF4500',
    sellante: '#32CD32',
    movilidad: '#FFA500',
    mancha: '#D2B48C',
    sano: '#FFFFFF',
};

export const PRIORIDAD_HALLAZGOS = [
    'ausente', 'implante', 'corona', 'endodoncia', 'fractura',
    'caries', 'obturacion', 'sellante', 'mancha', 'movilidad',
];

// Generador de arco elíptico anatómico
function generarArco(adulto = true) {
    const esc  = adulto ? 1 : 0.78;
    const ySup = adulto ?  0.98 :  0.75;
    const yInf = adulto ? -0.98 : -0.75;

    const angulos = { 1: 4, 2: 12, 3: 24, 4: 36, 5: 48, 6: 62, 7: 76, 8: 90 };
    const radios  = { 1: 3.6, 2: 3.5, 3: 3.35, 4: 3.1, 5: 2.75, 6: 2.35, 7: 1.9, 8: 1.4 };

    const pos = {};

    // Cuadrantes FDI:
    //   1 = superior derecho (X positivo)
    //   2 = superior izquierdo (X negativo)
    //   3 = inferior izquierdo (X negativo)
    //   4 = inferior derecho (X positivo)
    const cuadrantesSup = adulto ? [1, 2] : [5, 6];
    const cuadrantesInf = adulto ? [4, 3] : [8, 7];
    const piezasPorCuadrante = adulto ? 8 : 5;

    cuadrantesSup.forEach(quad => {
        const signoX = (quad === 1 || quad === 5) ? 1 : -1;
        for (let n = 1; n <= piezasPorCuadrante; n++) {
            const ang = angulos[n] * Math.PI / 180;
            const r   = radios[n] * esc;
            const x   = Math.sin(ang) * r * signoX;
            const z   = Math.cos(ang) * r + (adulto ? 0.2 : 0.1);
            pos[quad * 10 + n] = [x, ySup, z];
        }
    });

    cuadrantesInf.forEach(quad => {
        const signoX = (quad === 4 || quad === 8) ? 1 : -1;
        for (let n = 1; n <= piezasPorCuadrante; n++) {
            const ang = angulos[n] * Math.PI / 180;
            const r   = radios[n] * esc;
            const x   = Math.sin(ang) * r * signoX;
            const z   = Math.cos(ang) * r + (adulto ? 0.2 : 0.1);
            pos[quad * 10 + n] = [x, yInf, z];
        }
    });

    return pos;
}

// Diastema: separa los incisivos centrales de la línea media.
function aplicarDiastema(pos, offset = 0.18) {
    const out = {};
    Object.entries(pos).forEach(([fdi, [x, y, z]]) => {
        const n = parseInt(fdi, 10) % 10;
        if (n === 1) {
            let signo = Math.sign(x);
            if (signo === 0) {
                const quad = Math.floor(parseInt(fdi, 10) / 10);
                signo = (quad === 1 || quad === 4 || quad === 5 || quad === 8) ? 1 : -1;
            }
            out[fdi] = [x + signo * offset, y, z];
        } else {
            out[fdi] = [x, y, z];
        }
    });
    return out;
}

export const FDI_POS_ADULTO   = aplicarDiastema(generarArco(true));
export const FDI_POS_INFANTIL = aplicarDiastema(generarArco(false));

export const OFFSET_INFANTIL_X = -5.5;
export const OFFSET_ADULTO_X   =  5.5;

export const MODELOS_GLB = {
    incisivo: '/models/dientes/incisivo.glb',
    canino:   '/models/dientes/canino.glb',
    premolar: '/models/dientes/premolar.glb',
    molar:    '/models/dientes/molar.glb',
};

export const TEXTURAS_ENCIA = {
    color:     '/textures/gum_color.jpg',
    normal:    '/textures/gum_normal.jpg',
    roughness: '/textures/gum_rough.jpg',
};

export function getTipoPorFDI(fdi, esInfantil) {
    const ultimo = parseInt(fdi, 10) % 10;
    if (ultimo === 1 || ultimo === 2) return 'incisivo';
    if (ultimo === 3) return 'canino';
    if (ultimo === 4 || ultimo === 5) return esInfantil ? 'molar' : 'premolar';
    return 'molar';
}

// ------------------------------------------------------------
// ROTACIÓN CORREGIDA
// ------------------------------------------------------------
// Convención asumida:
//   - Los modelos GLB están exportados con la corona mirando hacia +Y
//     (hacia arriba) y la raíz hacia -Y (hacia abajo).
//   - Para el maxilar SUPERIOR hay que invertirlos 180° en X
//     para que la corona mire hacia -Y (hacia abajo).
//   - Para el maxilar INFERIOR se dejan como están (corona hacia +Y).
//
// Además se aplica un giro en Y (yaw) para orientar la cara vestibular
// radialmente hacia afuera del arco.
// ------------------------------------------------------------

/**
 * Devuelve la rotación [rotX, rotY, rotZ] en radianes para un diente FDI.
 * Los dientes superiores se invierten 180° en X (mirando hacia abajo).
 * Los dientes inferiores quedan mirando hacia arriba.
 */
export function getRotacionFDI(fdi) {
    const pos = FDI_POS_ADULTO[fdi] || FDI_POS_INFANTIL[fdi];
    if (!pos) return [0, 0, 0];

    const quad = Math.floor(parseInt(fdi, 10) / 10);
    const esSuperior = (quad === 1 || quad === 2 || quad === 5 || quad === 6);

    const [x, , z] = pos;

    // Yaw: orientar la cara vestibular hacia afuera (radialmente).
    const rotY = Math.atan2(x, z) + (esSuperior ? Math.PI : 0);

    // Pitch: los superiores se voltean 180° para mirar hacia abajo.
    const rotX = esSuperior ? Math.PI : 0;

    // Roll: sin inclinación adicional por ahora.
    const rotZ = 0;

    return [rotX, rotY, rotZ];
}

/**
 * Helper para aplicar la rotación directamente a un Object3D de Three.js.
 * Uso:
 *   const diente = gltf.scene.clone();
 *   aplicarRotacionFDI(diente, fdi);
 */
export function aplicarRotacionFDI(object3D, fdi) {
    const [rx, ry, rz] = getRotacionFDI(fdi);
    object3D.rotation.set(rx, ry, rz, 'YXZ');
}
