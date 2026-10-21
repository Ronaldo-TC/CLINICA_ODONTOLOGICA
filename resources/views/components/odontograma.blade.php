@props([
    'nombre'    => 'odontograma_pintado',
    'valor'     => null,          // JSON previo (string) o array
    'lectura'   => false,         // true = sólo visualización
    'id'        => null,
    'mostrarLeyenda' => true,
    'denticion' => 'adulto',
    'imagenes' => [],
])

@php
    /**
     * Odontograma interactivo de dentición permanente y temporal, notación FDI.
     *
     * Cada pieza tiene 5 caras: O (oclusal/incisal), M (mesial), D (distal),
     * V (vestibular) y L (lingual/palatino).
     *
     * Estados por cara y su color:
     *   sano (blanco), caries (rojo), obturado (azul), ausente (gris),
     *   corona (dorado), endodoncia (morado), sellante (verde), fractura (naranja).
     */
    // Cuadrantes FDI: 18-11 (superior derecho), 21-28 (superior izquierdo),
    // 38-31 (inferior izquierdo), 41-48 (inferior derecho)
    $denticiones = [
        'adulto' => [
            'titulo' => 'Adulto · 32 piezas permanentes',
            'cuadrantes' => [
                ['titulo' => 'Superior derecho', 'piezas' => range(18, 11)],
                ['titulo' => 'Superior izquierdo', 'piezas' => range(21, 28)],
                ['titulo' => 'Inferior derecho', 'piezas' => range(48, 41)],
                ['titulo' => 'Inferior izquierdo', 'piezas' => range(31, 38)],
            ],
        ],
        'infantil' => [
            'titulo' => 'Infantil · 20 piezas temporales',
            'cuadrantes' => [
                ['titulo' => 'Superior derecho', 'piezas' => range(55, 51)],
                ['titulo' => 'Superior izquierdo', 'piezas' => range(61, 65)],
                ['titulo' => 'Inferior derecho', 'piezas' => range(85, 81)],
                ['titulo' => 'Inferior izquierdo', 'piezas' => range(71, 75)],
            ],
        ],
    ];
    $denticionInicial = in_array($denticion, ['adulto', 'infantil', 'mixto'], true) ? $denticion : 'adulto';

    $estados = config('odontograma.condiciones');
    $estadosJson = json_encode($estados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

    $valorInicial = is_array($valor) ? json_encode($valor) : (string) ($valor ?? '');
    $idComponente = $id ?: 'odontograma_' . substr(md5($nombre . microtime(true)), 0, 8);
@endphp

<div class="odontograma"
    id="{{ $idComponente }}"
    data-odontograma
    data-nombre="{{ $nombre }}"
    data-lectura="{{ $lectura ? '1' : '0' }}"
    data-denticion="{{ $denticionInicial }}"
    data-estados="{{ $estadosJson }}"
    data-valor="{{ $valorInicial }}">
    <input type="hidden"
        name="{{ $nombre }}"
        value="{{ $valorInicial }}"
        data-odontograma-input
        @if ($lectura) disabled @endif>
    <input type="hidden" name="odontograma_denticion" value="{{ $denticionInicial }}"
        data-odontograma-denticion-input @disabled($lectura)>

    <div class="odontograma-controles">
        @unless($lectura)
        <label>Color de marcas<select data-odontograma-color><option value="rojo">Rojo</option><option value="azul">Azul</option></select></label>
        <button type="button" class="btn btn-sm btn-secondary" data-odontograma-limpiar>Limpiar dentición visible</button>
        @endunless
        <label for="{{ $idComponente }}-denticion">Dentición
            <select id="{{ $idComponente }}-denticion" data-odontograma-denticion>
                <option value="adulto" @selected($denticionInicial === 'adulto')>Adulto</option>
                <option value="infantil" @selected($denticionInicial === 'infantil')>Infantil</option>
                <option value="mixto" @selected($denticionInicial === 'mixto')>Mixto</option>
            </select>
        </label>
        <label for="{{ $idComponente }}-vista">Vista de las piezas
            <select id="{{ $idComponente }}-vista" data-odontograma-vista>
                <option value="anatomica">Diente completo</option>
                <option value="frontal">Superficie</option>
            </select>
        </label>
    </div>

    @unless($lectura)
    <div class="odontograma-herramientas" aria-label="Condición dental">
        @foreach($estados as $clave => $estado)
        <button type="button" data-herramienta="{{ $clave }}" aria-pressed="false">{{ $estado['etiqueta'] }}</button>
        @endforeach
    </div>
    @endunless

    <p class="odontograma-hint">
        @if ($lectura)
            Vista de sólo lectura.
        @else
            Seleccione una condición. Clic en la imagen: marcar el diente; clic en el esquema: marcar una cara. Clic derecho: borrar la pieza. Las marcas se muestran también debajo de cada diente.
        @endif
    </p>

    @foreach ($denticiones as $tipoDenticion => $datosDenticion)
        <section class="odontograma-denticion" data-denticion-arcada="{{ $tipoDenticion }}"
            @if ($denticionInicial !== 'mixto' && $denticionInicial !== $tipoDenticion) hidden @endif>
            <h4 class="odontograma-denticion-titulo">{{ $datosDenticion['titulo'] }}</h4>
            <div class="odontograma-desplazable">
                <div class="odontograma-arcada odontograma-arcada-{{ $tipoDenticion }}">
        @foreach ($datosDenticion['cuadrantes'] as $cuadrante)
            <div class="odontograma-cuadrante">
                <span class="odontograma-titulo">{{ $cuadrante['titulo'] }}</span>
                <div class="odontograma-piezas">
                    @foreach ($cuadrante['piezas'] as $pieza)
                        <div class="diente" data-pieza="{{ $pieza }}" title="Pieza {{ $pieza }}">
                            <span class="diente-ilustracion">
                            <img class="diente-imagen"
                                src="{{ $imagenes[$tipoDenticion][$pieza]['frontal'] ?? route('odontograma.imagen', ['denticion' => $tipoDenticion, 'pieza' => $pieza, 'vista' => 'frontal']) }}"
                                data-imagen-frontal="{{ $imagenes[$tipoDenticion][$pieza]['frontal'] ?? route('odontograma.imagen', ['denticion' => $tipoDenticion, 'pieza' => $pieza, 'vista' => 'frontal']) }}"
                                data-imagen-anatomica="{{ $imagenes[$tipoDenticion][$pieza]['anatomica'] ?? route('odontograma.imagen', ['denticion' => $tipoDenticion, 'pieza' => $pieza, 'vista' => 'anatomica']) }}"
                                alt="Pieza dental {{ $pieza }}" loading="{{ $imagenes ? 'eager' : 'lazy' }}" decoding="async">
                            <svg class="diente-overlay" viewBox="0 0 40 70" data-overlay aria-hidden="true"></svg>
                            </span>
                            <svg viewBox="0 0 40 40" role="img"
                                aria-label="Caras de la pieza dental {{ $pieza }}">
                                {{-- El JS asigna data-cara y el color de relleno --}}
                                <polygon data-cara="o" points="15,15 25,15 25,25 15,25" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Oclusal o incisal"><title>Oclusal / incisal</title></polygon>
                                <polygon data-cara="m" points="1,1 15,15 15,25 1,39" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Mesial"><title>Mesial</title></polygon>
                                <polygon data-cara="d" points="39,1 39,39 25,25 25,15" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Distal"><title>Distal</title></polygon>
                                <polygon data-cara="v" points="1,1 39,1 25,15 15,15" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Vestibular"><title>Vestibular</title></polygon>
                                <polygon data-cara="l" points="1,39 15,25 25,25 39,39" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Lingual o palatino"><title>Lingual / palatino</title></polygon>
                                <rect class="diente-borde" x="1" y="1" width="38" height="38" rx="4" />
                                <line class="diente-ausente" x1="4" y1="4" x2="36" y2="36" />
                                <line class="diente-ausente" x1="36" y1="4" x2="4" y2="36" />
                            </svg>
                            <span class="diente-numero">{{ $pieza }}</span>
                            <span class="diente-marcas" data-marcas aria-live="polite"></span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @if ($mostrarLeyenda)
        <div class="odontograma-leyenda">
            @foreach ($estados as $clave => $estado)
                <span class="odontograma-leyenda-item">
                    <span class="odontograma-swatch" data-estado="{{ $clave }}"></span>
                    {{ $estado['etiqueta'] }}
                </span>
            @endforeach
        </div>
    @endif
</div>

@once
    <style>
        .odontograma .odontograma-controles { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
        .odontograma-herramientas { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:3px; margin-bottom:12px; }
        .odontograma-herramientas button { padding:6px; border:1px solid var(--line,#cbd5e1); border-radius:4px; background:var(--surface,#fff); color:var(--ink,#334155); font-size:11px; cursor:pointer; }
        .odontograma-herramientas button[aria-pressed="true"] { background:#2563eb; color:white; }
        .odontograma .diente-marcas { display:flex; flex-wrap:wrap; justify-content:center; font-size:10px; max-width:45px; gap:2px; }
        .odontograma .diente-marcas span { border:1px solid currentColor; padding:1px 2px; border-radius:3px; }
        .odontograma-controles label { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--muted, #64748b); }
        .odontograma-controles select { padding: 5px 8px; border: 1px solid var(--line, #cbd5e1); border-radius: 6px; color: var(--text, #334155); background: var(--surface, #fff); }
        .odontograma-denticion[hidden] { display: none; }
        .odontograma-denticion + .odontograma-denticion { margin-top: 16px; }
        .odontograma-denticion-titulo { margin: 0 0 10px; font-size: 13px; color: var(--text, #334155); }
        .odontograma-desplazable { overflow-x: auto; padding-bottom: 6px; }
        .odontograma .odontograma-arcada { grid-template-columns: repeat(2, minmax(0, 1fr)); min-width: 680px; gap: 18px; }
        .odontograma .odontograma-arcada-infantil { min-width: 460px; }
        .odontograma .odontograma-piezas { flex-wrap: nowrap; gap: 4px; justify-content: space-between; }
        .odontograma .diente { width: 40px; flex: 0 0 40px; gap: 4px; }
        .odontograma .diente-imagen { width: 40px; height: 70px; object-fit: contain; display: block; }
        .odontograma .diente-ilustracion { display:block; position:relative; height:70px; }
        .odontograma .diente svg.diente-overlay { position:absolute; inset:0; width:40px; height:70px; pointer-events:none; background:transparent; border-radius:0; }
        .odontograma .diente svg.diente-overlay:hover { transform:none; box-shadow:none; }
        .odontograma .diente svg { width: 40px; height: 40px; }
        .odontograma .diente [data-cara]:focus-visible { outline: none; stroke: var(--teal-dark, #0f766e); stroke-width: 2; }
        .odontograma .diente-numero { font-size: 11px; }
        .odontograma .diente.es-ausente .diente-imagen { opacity: 0.3; }
    </style>
@endonce

@unless($esPdf ?? false)
    @include('components.partials.odontograma-js')
@endunless
