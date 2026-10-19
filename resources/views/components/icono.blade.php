@props(['nombre' => 'ver'])

<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    @switch($nombre)
        @case('editar')
            <path d="m16 3 5 5-12 12H4v-5L16 3Z M13 6l5 5" />
            @break
        @case('eliminar')
            <path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v7M14 10v7" />
            @break
        @case('ficha')
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <rect x="9" y="2" width="6" height="4" rx="1" />
            <path d="M9 11h6M9 15h6" />
            @break
        @case('historia')
            <path d="M12 5v16M12 5C9 3 5 3 2 4v15c3-1 7-1 10 2 3-3 7-3 10-2V4c-3-1-7-1-10 1Z" />
            @break
        @case('precio')
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <circle cx="12" cy="12" r="3" />
            <path d="M6 12h.01M18 12h.01" />
            @break
        @case('insumos')
            <path d="m12 3 9 5v8l-9 5-9-5V8l9-5ZM3 8l9 5 9-5M12 13v8M7.5 5.5l9 5" />
            @break
        @case('tratamientos')
            <path d="M8 3H5v5a5 5 0 0 0 10 0V3h-3M10 13v3a5 5 0 0 0 10 0v-3" />
            <circle cx="20" cy="10" r="3" />
            @break
        @case('recibo')
            <path d="M5 3v18l3-2 4 2 4-2 3 2V3l-3 2-4-2-4 2-3-2ZM9 9h6M9 13h6" />
            @break
        @case('imprimir')
            <path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <rect x="6" y="14" width="12" height="7" rx="1" />
            <path d="M18 12h.01" />
            @break
        @case('control')
            <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z M8 12l3 3 5-6" />
            @break
        @case('permisos')
            <path d="M9 16H5v4H1v-5l7-7" />
            <circle cx="15" cy="7" r="5" />
            <path d="M16 6h.01" />
            @break
        @case('autorizar')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 3 3 5-6" />
            @break
        @case('rechazar')
            <circle cx="12" cy="12" r="9" />
            <path d="m9 9 6 6m0-6-6 6" />
            @break
        @case('deshabilitar')
            <circle cx="12" cy="12" r="9" />
            <path d="m6 6 12 12" />
            @break
        @case('archivos')
            <path d="m8 12 7-7a3 3 0 0 1 4 4L9 19a5 5 0 0 1-7-7L12 2M6 14l8-8" />
            @break
        @case('entregar')
            <path d="M3 5h12v12H3V5ZM15 9h4l3 4v4h-7" />
            <circle cx="7" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
            @break
        @case('stock')
            <path d="M8 3v18m-4-4 4 4 4-4M16 21V3m-4 4 4-4 4 4" />
            @break
        @case('asignar')
            <circle cx="9" cy="7" r="4" />
            <path d="M2 21v-2a7 7 0 0 1 14 0v2M20 8v6M17 11h6" />
            @break
        @case('abrir')
            <path d="M14 3h7v7M10 14 21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
            @break
        @case('pdf')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6ZM14 2v6h6M12 11v7m-3-3 3 3 3-3" />
            @break
        @case('reporte')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6ZM14 2v6h6M8 17v-3M12 17v-5M16 17v-2" />
            @break
        @case('enviar')
            <path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" />
            @break
        @case('avance')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6ZM14 2v6h6M12 12v6M9 15h6" />
            @break
        @case('finalizar')
            <path d="m2 12 5 5L17 7M13 17l9-10" />
            @break
        @case('leer')
            <path d="M4 4h16v16H4V4ZM8 9h8M8 13h4m0 4 2 2 5-5" />
            @break
        @default
            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
