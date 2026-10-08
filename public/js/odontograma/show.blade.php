<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Odontograma 3D — {{ $paciente->nombre ?? 'Paciente' }}</title>

    {{-- IMPORTMAP: Three.js desde CDN --}}
    <script type="importmap">
        {
      "imports": {
        "three": "https://unpkg.com/three@0.169.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.169.0/examples/jsm/"
      }
    }
    </script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            color: #e5e5e5;
            overflow: hidden;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            height: 100vh;
        }

        .viewport {
            position: relative;
            background: #111827;
        }

        #canvas3d {
            width: 100%;
            height: 100%;
            display: block;
        }

        .hud {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(20, 20, 20, 0.85);
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 12px;
            color: #aaa;
            backdrop-filter: blur(10px);
            pointer-events: none;
            z-index: 10;
        }

        .panel {
            background: #141414;
            border-left: 1px solid #262626;
            overflow-y: auto;
            padding: 20px;
        }

        .panel h2 {
            font-size: 16px;
            margin-bottom: 4px;
            color: #fff;
        }

        .panel .subtitle {
            font-size: 12px;
            color: #888;
            margin-bottom: 20px;
        }

        .estado-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .estado-finalizado {
            background: #166534;
            color: #86efac;
        }

        .estado-borrador {
            background: #854d0e;
            color: #fde68a;
        }

        .estado-anulado {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }

        .resumen-item {
            background: #1c1c1c;
            border-radius: 6px;
            padding: 10px;
        }

        .resumen-item .count {
            font-size: 20px;
            font-weight: 700;
        }

        .resumen-item .label {
            font-size: 11px;
            color: #888;
            text-transform: capitalize;
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            margin-bottom: 8px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #262626;
            color: #e5e5e5;
        }

        .btn-secondary:hover {
            background: #333;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .modal.hidden {
            display: none;
        }

        .modal-content {
            background: #1c1c1c;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 24px;
            width: 360px;
        }

        .modal-content h3 {
            margin-bottom: 16px;
            color: #fff;
        }

        .modal-content label {
            display: block;
            font-size: 12px;
            color: #aaa;
            margin-bottom: 4px;
            margin-top: 12px;
        }

        .modal-content select {
            width: 100%;
            padding: 8px;
            margin-bottom: 4px;
            background: #0f0f0f;
            color: #e5e5e5;
            border: 1px solid #333;
            border-radius: 6px;
            font-size: 13px;
        }

        .loading {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
            font-size: 14px;
            color: #888;
        }

        .loading.hidden {
            display: none;
        }

        #listaHallazgos {
            font-size: 12px;
            color: #aaa;
            line-height: 1.7;
        }

        #listaHallazgos div {
            padding: 4px 0;
            border-bottom: 1px solid #222;
        }
    </style>
</head>

<body>

    <div class="layout">

        {{-- ─── VIEWPORT 3D ─── --}}
        <div class="viewport">
            <canvas id="canvas3d"></canvas>
            <div class="hud">
                🖱️ Click en un diente para editarlo · Arrastra para rotar · Scroll para zoom
            </div>
        </div>

        {{-- ─── PANEL LATERAL ─── --}}
        <aside class="panel">
            <h2>{{ $paciente->nombre ?? 'Paciente' }} {{ $paciente->apellido_paterno ?? '' }}</h2>
            <p class="subtitle">
                Odontograma v{{ $odontograma->version }} — {{ ucfirst($odontograma->tipo) }}
            </p>

            <span class="estado-badge estado-{{ $odontograma->estado }}">
                {{ $odontograma->estado }}
            </span>

            <h2>Resumen clínico</h2>
            <div class="resumen-grid" id="resumen-grid"></div>

            <h2 style="margin-top: 20px;">Hallazgos</h2>
            <div id="listaHallazgos"></div>

            <button class="btn btn-primary" id="btnGuardar" style="margin-top: 20px;">
                💾 Guardar cambios
            </button>
            <button class="btn btn-secondary" id="btnReset">
                ↻ Reiniciar vista
            </button>
        </aside>

    </div>

    {{-- ─── MODAL EDICIÓN ─── --}}
    <div class="modal hidden" id="modalDiente">
        <div class="modal-content">
            <h3>Pieza <span id="piezaLabel"></span></h3>

            <label>Condición</label>
            <select id="condicion">
                <option value="caries">Caries</option>
                <option value="obturacion">Obturación</option>
                <option value="corona">Corona</option>
                <option value="endodoncia">Endodoncia</option>
                <option value="implante">Implante</option>
                <option value="sellante">Sellante</option>
                <option value="fractura">Fractura</option>
                <option value="mancha">Mancha</option>
                <option value="movilidad">Movilidad</option>
                <option value="ausente">Ausente</option>
            </select>

            <label>Caras</label>
            <select id="caras">
                <option value="">(ninguna)</option>
                <option value="O">Oclusal</option>
                <option value="M">Mesial</option>
                <option value="D">Distal</option>
                <option value="V">Vestibular</option>
                <option value="L">Lingual</option>
                <option value="MOD">MOD</option>
                <option value="MO">MO</option>
                <option value="OD">OD</option>
            </select>

            <button class="btn btn-primary" id="btnAplicar" style="margin-top: 16px;">
                Aplicar
            </button>
            <button class="btn btn-secondary" onclick="document.getElementById('modalDiente').classList.add('hidden')">
                Cancelar
            </button>
        </div>
    </div>

    {{-- ─── LOADING ─── --}}
    <div class="loading" id="loading">Cargando odontograma 3D...</div>

    {{-- ─── DATOS PARA JS ─── --}}
    {{-- Usamos @json con comillas simples seguras; Laravel escapa las comillas internas --}}
    <div id="odontograma-root"
        data-odonto-id="{{ $odontograma->id }}"
        data-odonto-data="{{ json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        style="display:none;"></div>

    {{-- ─── SCRIPT PRINCIPAL (módulo ES) ─── --}}
    <script type="module"
        src="{{ asset('js/odontograma-3d.js') }}?v={{ filemtime(public_path('js/odontograma-3d.js')) }}"></script>
</body>

</html>