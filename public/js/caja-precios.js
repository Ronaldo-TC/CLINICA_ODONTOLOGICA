(() => {
    'use strict';
    const form = document.getElementById('formPrecioCaja');
    if (!form) return;
    const catalogo = JSON.parse(document.getElementById('catalogoPreciosCaja').textContent);
    const clinica = document.getElementById('tarifa-clinica');
    const tratamiento = document.getElementById('tarifa-tratamiento');
    const costo = document.getElementById('tarifa-costo');
    const resultado = document.getElementById('tarifa-resultado');
    clinica.addEventListener('change', () => {
        costo.value = '';
        tratamiento.replaceChildren(new Option('Seleccione un tratamiento', ''));
        catalogo.filter(item => String(item.clinica_id) === clinica.value).forEach(item => tratamiento.add(new Option(item.nombre, item.id)));
        tratamiento.disabled = !clinica.value;
        resultado.textContent = '';
    });
    tratamiento.addEventListener('change', () => {
        const item = catalogo.find(item => String(item.id) === tratamiento.value);
        costo.value = item?.precio_asignado ? item.costo : '';
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        if (button.disabled) return;
        button.disabled = true;
        resultado.textContent = '';
        try {
            const response = await fetch(form.dataset.precio.replace('__ID__', tratamiento.value), {
                method: 'PUT', headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({costo: costo.value}),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message);
            const item = catalogo.find(item => String(item.id) === tratamiento.value);
            item.costo = costo.value;
            item.precio_asignado = true;
            resultado.textContent = data.message;
        } catch (error) { resultado.textContent = error.message; }
        finally { button.disabled = false; }
    });
})();
