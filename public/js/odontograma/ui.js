// ============================================================
// UI — Lista y resumen de hallazgos
// ============================================================
import { COLORES } from './constants.js';

export function renderLista(hallazgosState) {
    const cont = document.getElementById('listaHallazgos');
    if (!cont) return;

    const entries = Object.entries(hallazgosState).filter(([, lista]) => lista.length);
    cont.replaceChildren();
    if (!entries.length) {
        const empty = document.createElement('div');
        empty.style.color = '#666';
        empty.textContent = 'Sin hallazgos registrados.';
        cont.append(empty);
        return;
    }

    entries.forEach(([pieza, lista]) => {
        const row = document.createElement('div');
        const label = document.createElement('strong');
        label.textContent = pieza;
        row.append(label, document.createTextNode(': '));

        lista.forEach((hallazgo, index) => {
            if (index > 0) {
                row.append(document.createTextNode(' · '));
            }
            const marker = document.createElement('span');
            marker.style.color = hallazgo.color || COLORES[hallazgo.condicion] || '#fff';
            marker.textContent = '●';
            const caras = hallazgo.caras ? ` (${hallazgo.caras})` : '';
            row.append(marker, document.createTextNode(` ${hallazgo.condicion}${caras}`));
        });
        cont.append(row);
    });
}

export function renderResumen(hallazgosState) {
    const cont = document.getElementById('resumen-grid');
    if (!cont) return;

    const counts = new Map();
    Object.values(hallazgosState).flat().forEach(h => {
        counts.set(h.condicion, (counts.get(h.condicion) || 0) + 1);
    });

    cont.replaceChildren();
    if (!counts.size) {
        const empty = document.createElement('div');
        empty.style.color = '#666';
        empty.style.fontSize = '12px';
        empty.textContent = 'Sin datos';
        cont.append(empty);
        return;
    }

    counts.forEach((count, condicion) => {
        const item = document.createElement('div');
        item.className = 'resumen-item';
        const countElement = document.createElement('div');
        countElement.className = 'count';
        countElement.style.color = COLORES[condicion] || '#888';
        countElement.textContent = String(count);
        const label = document.createElement('div');
        label.className = 'label';
        label.textContent = condicion;
        item.append(countElement, label);
        cont.append(item);
    });
}
