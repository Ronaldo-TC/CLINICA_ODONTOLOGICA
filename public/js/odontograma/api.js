// ============================================================
// API — Persistencia backend
// ============================================================
export async function guardarHallazgos(url, payload) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (!res.ok) {
        const errores = Object.values(json.errors || {}).flat();
        throw new Error(errores.join('\n') || json.message || `HTTP ${res.status}`);
    }
    return json;
}
