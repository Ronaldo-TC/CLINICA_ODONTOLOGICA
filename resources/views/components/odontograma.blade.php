@props([
    'nombre'    => 'odontograma_pintado',
    'valor'     => null,          // JSON previo (string) o array
    'lectura'   => false,         // true = sólo visualización
    'id'        => null,
    'mostrarLeyenda' => true,
    'denticion' => 'adulto',
    'imagenes' => [],
    'esPdf' => false,
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
    $datosOdontograma = is_array($valor) ? $valor : json_decode($valorInicial, true);
    $datosOdontograma = is_array($datosOdontograma) ? $datosOdontograma : [];
    $carasSvg = [
        'o' => ['puntos' => '15,15 25,15 25,25 15,25', 'etiqueta' => 'Oclusal o incisal'],
        'm' => ['puntos' => '1,1 15,15 15,25 1,39', 'etiqueta' => 'Mesial'],
        'd' => ['puntos' => '39,1 39,39 25,25 25,15', 'etiqueta' => 'Distal'],
        'v' => ['puntos' => '1,1 39,1 25,15 15,15', 'etiqueta' => 'Vestibular'],
        'l' => ['puntos' => '1,39 15,25 25,25 39,39', 'etiqueta' => 'Lingual o palatino'],
    ];
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
                        @php
                            $detallePieza = $datosOdontograma[(string) $pieza] ?? [];
                            $detallePieza = is_array($detallePieza) ? $detallePieza : [];
                            $marcasPieza = is_array($detallePieza['marcas'] ?? null) ? $detallePieza['marcas'] : [];
                            $piezaAusente = collect($carasSvg)->keys()->contains(
                                fn (string $cara): bool => ($detallePieza[$cara] ?? null) === 'ausente'
                            ) || collect($marcasPieza)->contains(
                                fn ($marca): bool => is_array($marca) && ($marca['estado'] ?? null) === 'ausente'
                            );
                            $piezaTratada = collect($carasSvg)->keys()->contains(
                                fn (string $cara): bool => isset($detallePieza[$cara]) && $detallePieza[$cara] !== 'sano'
                            );
                            $imagenFrontal = $imagenes[$tipoDenticion][$pieza]['frontal'] ?? route('odontograma.imagen', ['denticion' => $tipoDenticion, 'pieza' => $pieza, 'vista' => 'frontal']);
                            $imagenAnatomica = $imagenes[$tipoDenticion][$pieza]['anatomica'] ?? route('odontograma.imagen', ['denticion' => $tipoDenticion, 'pieza' => $pieza, 'vista' => 'anatomica']);
                        @endphp
                        <div class="diente" data-pieza="{{ $pieza }}" title="Pieza {{ $pieza }}">
                            <span class="diente-ilustracion">
                            <img class="diente-imagen"
                                src="{{ $esPdf ? $imagenAnatomica : $imagenFrontal }}"
                                data-imagen-frontal="{{ $imagenFrontal }}"
                                data-imagen-anatomica="{{ $imagenAnatomica }}"
                                alt="Pieza dental {{ $pieza }}" loading="{{ $imagenes ? 'eager' : 'lazy' }}" decoding="async" @style(['opacity:0.3' => $piezaAusente])>
                            <svg class="diente-overlay" viewBox="0 0 40 70" data-overlay aria-hidden="true">
                                @foreach ($marcasPieza as $indiceMarca => $marca)
                                    @php
                                        $estadoMarca = is_array($marca) ? ($marca['estado'] ?? null) : null;
                                        $colorMarca = is_array($marca) && ($marca['color'] ?? null) === 'azul' ? '#2563eb' : '#dc2626';
                                        $esInferior = preg_match('/^[3478]/', (string) $pieza) === 1;
                                        $trazoCorona = $esInferior ? 'M3 2H37V23H3Z' : 'M3 48H37V68H3Z';
                                        $trazosMarca = [
                                            'ausente' => 'M2 2L38 68M38 2L2 68',
                                            'fractura' => 'M24 5L12 23L28 32L12 46L24 65',
                                            'corona' => $trazoCorona,
                                            'corona_temporal' => $trazoCorona,
                                            'protesis_fija' => $trazoCorona,
                                            'implante' => 'M20 4V66M10 12H30M10 22H30M10 32H30M10 42H30M10 52H30',
                                            'perno' => 'M20 12V56',
                                            'poste' => 'M20 12V56',
                                        ];
                                        $trazoMarca = is_string($estadoMarca) ? ($trazosMarca[$estadoMarca] ?? null) : null;
                                        $simboloMarca = is_string($estadoMarca) ? ($estados[$estadoMarca]['simbolo'] ?? mb_strtoupper(mb_substr($estadoMarca, 0, 2))) : '';
                                    @endphp
                                    @if($trazoMarca)
                                        <path d="{{ $trazoMarca }}" fill="none" stroke="{{ $colorMarca }}" stroke-width="2" />
                                    @elseif($simboloMarca !== '')
                                        <text x="20" y="{{ 12 + (((int) $indiceMarca % 4) * 15) }}" text-anchor="middle" font-size="12" fill="{{ $colorMarca }}">{{ $simboloMarca }}</text>
                                    @endif
                                @endforeach
                            </svg>
                            </span>
                            <svg viewBox="0 0 40 40" role="img"
                                aria-label="Caras de la pieza dental {{ $pieza }}">
                                @foreach($carasSvg as $cara => $configuracionCara)
                                    @php
                                        $estadoCara = $detallePieza[$cara] ?? 'sano';
                                        $estadoCara = is_string($estadoCara) && isset($estados[$estadoCara]) ? $estadoCara : 'sano';
                                        $condicionCara = $estados[$estadoCara] ?? $estados['sano'];
                                        $rellenoCara = $condicionCara['color'] ?? ($estadoCara === 'sano' ? '#ffffff' : '#dc2626');
                                        $bordeCara = $condicionCara['borde'] ?? ($estados['sano']['borde'] ?? '#cbd5e1');
                                    @endphp
                                    <polygon data-cara="{{ $cara }}" data-estado="{{ $estadoCara }}" points="{{ $configuracionCara['puntos'] }}" fill="{{ $rellenoCara }}" stroke="{{ $bordeCara }}" stroke-width="{{ $estadoCara === 'sano' ? '0.6' : '1' }}" tabindex="{{ $lectura ? -1 : 0 }}" role="button" aria-label="Pieza {{ $pieza }}, cara {{ mb_strtoupper($cara) }}, {{ $condicionCara['etiqueta'] ?? $configuracionCara['etiqueta'] }}"><title>{{ $configuracionCara['etiqueta'] }}</title></polygon>
                                @endforeach
                                <rect class="diente-borde" x="1" y="1" width="38" height="38" rx="4" />
                                <line class="diente-ausente" x1="4" y1="4" x2="36" y2="36" @style(['display:block' => $piezaAusente]) />
                                <line class="diente-ausente" x1="36" y1="4" x2="4" y2="36" @style(['display:block' => $piezaAusente]) />
                            </svg>
                            <span class="diente-numero" @style(['font-weight:700' => $piezaTratada])>{{ $pieza }}</span>
                            <span class="diente-marcas" data-marcas aria-live="polite">
                                @foreach($marcasPieza as $marca)
                                    @php
                                        $estadoMarca = is_array($marca) ? ($marca['estado'] ?? null) : null;
                                        $etiquetaMarca = is_string($estadoMarca) ? ($estados[$estadoMarca]['simbolo'] ?? $estados[$estadoMarca]['etiqueta'] ?? $estadoMarca) : '';
                                        $colorMarca = is_array($marca) && ($marca['color'] ?? null) === 'azul' ? '#2563eb' : '#dc2626';
                                    @endphp
                                    @if($etiquetaMarca !== '')<span title="{{ $estados[$estadoMarca]['etiqueta'] ?? $estadoMarca }}" style="color:{{ $colorMarca }}">{{ $etiquetaMarca }}</span>@endif
                                @endforeach
                            </span>
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

@unless($esPdf)
    @include('components.partials.odontograma-js')
@endunless
