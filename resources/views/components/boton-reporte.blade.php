@props(['ruta'])
<a href="{{ route($ruta, array_merge(request()->query(), ['formato' => 'pdf'])) }}" data-reporte-modulo data-formato="pdf" data-base="{{ route($ruta) }}" class="btn btn-secondary">Generar PDF</a>
<a href="{{ route($ruta, request()->query()) }}" data-reporte-modulo data-base="{{ route($ruta) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">Vista previa</a>
@once
@push('scripts')
<script>
    document.querySelectorAll('[data-reporte-modulo]').forEach(enlace => {
        const campos = { buscar:'filtro-buscar', clinica_id:'filtro-clinica', periodo_id:'filtro-periodo', estado:'filtro-estado', desde:'filtro-desde', hasta:'filtro-hasta', tipo_pago:'filtro-tipo-pago', genero:'filtro-genero' };
        const actualizar = () => {
            const destino = new URL(enlace.dataset.base, window.location.origin);
            if (enlace.dataset.formato === 'pdf') destino.searchParams.set('formato', 'pdf');
            Object.entries(campos).forEach(([nombre, id]) => {
                const control = document.getElementById(id);
                if (control?.value.trim()) destino.searchParams.set(nombre, control.value.trim());
            });
            if (document.getElementById('filtro-stock-bajo')?.checked) destino.searchParams.set('stock_bajo', '1');
            enlace.href = destino.href;
        };
        Object.values(campos).concat('filtro-stock-bajo').forEach(id => {
            const control = document.getElementById(id);
            control?.addEventListener('input', actualizar);
            control?.addEventListener('change', actualizar);
        });
        enlace.addEventListener('click', actualizar);
        enlace.addEventListener('contextmenu', actualizar);
    });
</script>
@endpush
@endonce
