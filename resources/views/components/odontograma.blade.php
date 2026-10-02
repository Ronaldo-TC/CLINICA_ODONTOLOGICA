@props([
    'nombre'    => 'odontograma_pintado',
    'valor'     => null,          // JSON previo (string) o array
    'lectura'   => false,         // true = sólo visualización
    'id'        => null,
    'mostrarLeyenda' => true,
])

@php
    /**
     * Odontograma interactivo (32 piezas, notación FDI).
     *
     * Cada pieza tiene 5 caras: O (oclusal/incisal), M (mesial), D (distal),
     * V (vestibular) y L (lingual/palatino).
     *
     * Estados por cara y su color:
     *   sano (blanco), caries (rojo), obturado (azul), ausente (gris),
     *   corona (dorado), endodoncia (morado), sellante (verde), fractura (naranja).
     */
    $estados = [
        'sano'       => ['etiqueta' => 'Sano',       'color' => '#ffffff', 'borde' => '#cbd5e1'],
        'caries'     => ['etiqueta' => 'Caries',     'color' => '#dc2626', 'borde' => '#b91c1c'],
        'obturado'   => ['etiqueta' => 'Obturado',   'color' => '#2563eb', 'borde' => '#1d4ed8'],
        'ausente'    => ['etiqueta' => 'Ausente',    'color' => '#94a3b8', 'borde' => '#64748b'],
        'corona'     => ['etiqueta' => 'Corona',     'color' => '#d4a017', 'borde' => '#a16207'],
        'endodoncia' => ['etiqueta' => 'Endodoncia', 'color' => '#7c3aed', 'borde' => '#6d28d9'],
        'sellante'   => ['etiqueta' => 'Sellante',   'color' => '#16a34a', 'borde' => '#15803d'],
        'fractura'   => ['etiqueta' => 'Fractura',   'color' => '#ea580c', 'borde' => '#c2410c'],
    ];

    // Cuadrantes FDI: 18-11 (superior derecho), 21-28 (superior izquierdo),
    // 38-31 (inferior izquierdo), 41-48 (inferior derecho)
    $cuadrantes = [
        ['titulo' => 'Superior derecho', 'piezas' => range(18, 11)],
        ['titulo' => 'Superior izquierdo', 'piezas' => range(21, 28)],
        ['titulo' => 'Inferior izquierdo', 'piezas' => range(31, 38)],
        ['titulo' => 'Inferior derecho', 'piezas' => range(41, 48)],
    ];

    $estadosJson = json_encode($estados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

    $valorInicial = is_array($valor) ? json_encode($valor) : (string) ($valor ?? '');
    $idComponente = $id ?: 'odontograma_' . substr(md5($nombre . microtime(true)), 0, 8);
@endphp

<div class="odontograma"
    id="{{ $idComponente }}"
    data-odontograma
    data-nombre="{{ $nombre }}"
    data-lectura="{{ $lectura ? '1' : '0' }}"
    data-estados="{{ $estadosJson }}"
    data-valor="{{ $valorInicial }}">
    <input type="hidden"
        name="{{ $nombre }}"
        value="{{ $valorInicial }}"
        data-odontograma-input
        @if ($lectura) disabled @endif>

    <p class="odontograma-hint">
        @if ($lectura)
            Vista de sólo lectura.
        @else
            Clic en una cara para cambiar su estado · Clic derecho para volver a <strong>sano</strong>.
        @endif
    </p>

    <div class="odontograma-arcada">
        @foreach ($cuadrantes as $cuadrante)
            <div class="odontograma-cuadrante">
                <span class="odontograma-titulo">{{ $cuadrante['titulo'] }}</span>
                <div class="odontograma-piezas">
                    @foreach ($cuadrante['piezas'] as $pieza)
                        <div class="diente" data-pieza="{{ $pieza }}" title="Pieza {{ $pieza }}">
                            <svg viewBox="0 0 40 40" role="img"
                                aria-label="Pieza dental {{ $pieza }}">
                                {{-- El JS asigna data-cara y el color de relleno --}}
                                <polygon data-cara="o" points="15,15 25,15 25,25 15,25" />
                                <polygon data-cara="m" points="1,1 15,15 15,25 1,39" />
                                <polygon data-cara="d" points="39,1 39,39 25,25 25,15" />
                                <polygon data-cara="v" points="1,1 39,1 25,15 15,15" />
                                <polygon data-cara="l" points="1,39 15,25 25,25 39,39" />
                                <rect class="diente-borde" x="1" y="1" width="38" height="38" rx="4" />
                                <line class="diente-ausente" x1="4" y1="4" x2="36" y2="36" />
                                <line class="diente-ausente" x1="36" y1="4" x2="4" y2="36" />
                            </svg>
                            <span class="diente-numero">{{ $pieza }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

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

@include('components.partials.odontograma-js')
