{{--
    ============================================================
    FIRMA DIGITAL DEL PACIENTE
    ============================================================
    Canvas nativo (sin librerías externas, funciona sin red).
    Guarda un PNG en base64 (data URL) en el campo indicado.

    Uso:
      <x-firma-digital nombre="firma" id="adm-firma" />

    API pública: window.FirmaDigital
      · FirmaDigital.get(objetivo)      → data URL o ''
      · FirmaDigital.set(objetivo, url) → carga una firma existente
      · FirmaDigital.limpiar(objetivo)
      · FirmaDigital.estaVacia(objetivo) → true/false
--}}
@props([
    'nombre'  => 'firma',
    'valor'   => null,
    'id'      => null,
    'alto'    => 180,
])

@php
    $idComponente = $id ?: 'firma_' . substr(md5($nombre . microtime(true)), 0, 8);
    $valorInicial = (string) ($valor ?? '');
@endphp

<div class="firma-pad"
    id="{{ $idComponente }}"
    data-firma
    data-nombre="{{ $nombre }}"
    data-alto="{{ (int) $alto }}"
    data-valor="{{ $valorInicial }}">
    <input type="hidden" name="{{ $nombre }}" value="{{ $valorInicial }}" data-firma-input>

    <div class="firma-lienzo">
        <canvas data-firma-canvas aria-label="Área de firma del paciente" role="img"></canvas>
        <img class="firma-imagen" data-firma-imagen src="{{ $valorInicial }}" alt="Firma del paciente"
            @if ($valorInicial === '') hidden @endif>
        <p class="firma-guia" data-firma-guia @if ($valorInicial !== '') hidden @endif>
            Firme aquí con el dedo o el ratón
        </p>
    </div>

    <div class="firma-acciones">
        <button type="button" class="btn btn-sm btn-secondary" data-firma-limpiar>
            Limpiar firma
        </button>
        <span class="firma-estado" data-firma-estado>{{ $valorInicial !== '' ? 'Firma registrada' : 'Sin firma' }}</span>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                'use strict';

                const raizDe = (objetivo) => {
                    if (!objetivo) return null;
                    if (objetivo instanceof Element && objetivo.matches('[data-firma]')) return objetivo;
                    return objetivo?.closest?.('[data-firma]') || document.querySelector(objetivo);
                };

                function init(raiz) {
                    if (!raiz || raiz.dataset.firmaListo === '1') return;
                    raiz.dataset.firmaListo = '1';

                    const canvas = raiz.querySelector('[data-firma-canvas]');
                    const imagen = raiz.querySelector('[data-firma-imagen]');
                    const guia = raiz.querySelector('[data-firma-guia]');
                    const input = raiz.querySelector('[data-firma-input]');
                    const estado = raiz.querySelector('[data-firma-estado]');
                    const botonLimpiar = raiz.querySelector('[data-firma-limpiar]');
                    const alto = parseInt(raiz.dataset.alto || '180', 10);

                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    let dibujando = false;
                    let huboTrazo = false;

                    /* Ajuste al tamaño real (incluye pantallas de alta densidad) */
                    function dimensionar() {
                        const anchoReal = canvas.clientWidth || canvas.parentElement.clientWidth || 600;
                        const dpr = window.devicePixelRatio || 1;

                        // Si ya había una firma dibujada, se conserva
                        const previo = huboTrazo ? canvas.toDataURL('image/png') : null;

                        canvas.width = Math.round(anchoReal * dpr);
                        canvas.height = Math.round(alto * dpr);
                        canvas.style.height = alto + 'px';

                        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                        ctx.lineWidth = 2.2;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = '#1b2e2b';

                        if (previo) {
                            const img = new Image();
                            img.onload = () => ctx.drawImage(img, 0, 0, anchoReal, alto);
                            img.src = previo;
                        }
                    }

                    const punto = (evento) => {
                        const rect = canvas.getBoundingClientRect();
                        const fuente = evento.touches?.[0] ?? evento;
                        return {
                            x: fuente.clientX - rect.left,
                            y: fuente.clientY - rect.top,
                        };
                    };

                    function empezar(evento) {
                        evento.preventDefault();
                        dibujando = true;
                        const p = punto(evento);
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        if (guia) guia.hidden = true;
                    }

                    function mover(evento) {
                        if (!dibujando) return;
                        evento.preventDefault();
                        const p = punto(evento);
                        ctx.lineTo(p.x, p.y);
                        ctx.stroke();
                        huboTrazo = true;
                    }

                    function terminar() {
                        if (!dibujando) return;
                        dibujando = false;
                        guardar();
                    }

                    function guardar() {
                        if (!huboTrazo) return;

                        const dataUrl = canvas.toDataURL('image/png');
                        if (input) input.value = dataUrl;

                        if (imagen) {
                            imagen.src = dataUrl;
                            imagen.hidden = false;
                        }
                        if (estado) estado.textContent = 'Firma registrada';

                        raiz.dispatchEvent(new CustomEvent('firma:cambio', { bubbles: true }));
                    }

                    function limpiar() {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        huboTrazo = false;

                        if (input) input.value = '';
                        if (imagen) {
                            imagen.hidden = true;
                            imagen.removeAttribute('src');
                        }
                        if (guia) guia.hidden = false;
                        if (estado) estado.textContent = 'Sin firma';

                        raiz.dispatchEvent(new CustomEvent('firma:cambio', { bubbles: true }));
                    }

                    /* Eventos de ratón y táctiles */
                    canvas.addEventListener('mousedown', empezar);
                    canvas.addEventListener('mousemove', mover);
                    window.addEventListener('mouseup', terminar);

                    canvas.addEventListener('touchstart', empezar, { passive: false });
                    canvas.addEventListener('touchmove', mover, { passive: false });
                    canvas.addEventListener('touchend', terminar);
                    canvas.addEventListener('touchcancel', terminar);

                    botonLimpiar?.addEventListener('click', limpiar);

                    raiz.addEventListener('firma:limpiar', limpiar);

                    let temporizador = null;
                    window.addEventListener('resize', () => {
                        clearTimeout(temporizador);
                        temporizador = setTimeout(dimensionar, 150);
                    });

                    dimensionar();

                    // Firma precargada: se marca como existente
                    if (input?.value) {
                        huboTrazo = true;
                        if (estado) estado.textContent = 'Firma registrada';
                    }

                    raiz.dataset.firmaInicializada = '1';
                }

                window.FirmaDigital = {
                    init,
                    initTodo: (ambito = document) => ambito.querySelectorAll?.('[data-firma]').forEach(init),

                    get(objetivo) {
                        const raiz = raizDe(objetivo);
                        return raiz?.querySelector('[data-firma-input]')?.value || '';
                    },

                    set(objetivo, dataUrl) {
                        const raiz = raizDe(objetivo);
                        if (!raiz) return;

                        const input = raiz.querySelector('[data-firma-input]');
                        const imagen = raiz.querySelector('[data-firma-imagen]');
                        const guia = raiz.querySelector('[data-firma-guia]');
                        const estado = raiz.querySelector('[data-firma-estado]');
                        const canvas = raiz.querySelector('[data-firma-canvas]');

                        if (input) input.value = dataUrl || '';

                        if (dataUrl) {
                            if (imagen) {
                                imagen.src = dataUrl;
                                imagen.hidden = false;
                            }
                            if (guia) guia.hidden = true;
                            if (estado) estado.textContent = 'Firma registrada';
                            if (canvas) canvas.dataset.firmaTieneTrazo = '1';
                        } else {
                            window.FirmaDigital.limpiar(raiz);
                        }
                    },

                    limpiar(objetivo) {
                        const raiz = raizDe(objetivo);
                        raiz?.dispatchEvent(new Event('firma:limpiar'));
                    },

                    estaVacia(objetivo) {
                        return window.FirmaDigital.get(objetivo) === '';
                    },
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => window.FirmaDigital.initTodo());
                } else {
                    window.FirmaDigital.initTodo();
                }
            })();
        </script>
    @endpush
@endonce
