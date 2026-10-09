<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso · Clínica Odontológica UPEA</title>

    <style>
        /* ============================================
           VARIABLES
           ============================================ */
        :root {
            --teal: #0a7d76;
            --teal-dark: #075e59;
            --teal-soft: #e6f4f3;
            --ink: #14232b;
            --ink-soft: #2c3e47;
            --muted: #6b7c85;
            --line: #e3eae8;
            --line-strong: #cdd8d5;
            --surface: #ffffff;
            --surface-soft: #f7faf9;
            --cyan: #06b6d4;
            --fuchsia: #c026d3;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --danger-line: #fecaca;
            --success: #15803d;
            --success-bg: #f0fdf4;
            --radius-lg: 24px;
            --radius-md: 14px;
            --radius-sm: 10px;
            --shadow-sm: 0 1px 2px rgba(20, 35, 43, 0.04);
            --shadow-md: 0 4px 16px rgba(20, 35, 43, 0.06);
            --shadow-lg: 0 24px 48px -12px rgba(20, 35, 43, 0.12);
            --font: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Segoe UI', Roboto, sans-serif;
        }

        /* ============================================
           RESET
           ============================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100vh;
        }

        body {
            font-family: var(--font);
            color: var(--ink);
            background: #eef3f2;
            display: grid;
            place-items: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ============================================
           FONDO DECORATIVO (sin GPU, sin blur)
           ============================================ */
        .blob {
            position: absolute;
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
            will-change: auto;
        }

        .blob-cyan-1 {
            width: 680px;
            height: 680px;
            top: -240px;
            left: -200px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.28) 0%, rgba(6, 182, 212, 0.12) 40%, transparent 70%);
        }

        .blob-fuchsia-1 {
            width: 600px;
            height: 600px;
            bottom: -220px;
            right: -180px;
            background: radial-gradient(circle, rgba(192, 38, 211, 0.22) 0%, rgba(192, 38, 211, 0.10) 40%, transparent 70%);
        }

        .blob-teal-1 {
            width: 420px;
            height: 420px;
            top: 38%;
            right: -160px;
            background: radial-gradient(circle, rgba(10, 125, 118, 0.18) 0%, transparent 65%);
        }

        .blob-teal-2 {
            width: 380px;
            height: 380px;
            bottom: 18%;
            left: -140px;
            background: radial-gradient(circle, rgba(10, 125, 118, 0.16) 0%, transparent 65%);
        }

        /* ============================================
           CONTENEDOR PRINCIPAL
           ============================================ */
        .shell {
            position: relative;
            z-index: 1;
            width: min(1000px, 100%);
            min-height: 640px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow:
                var(--shadow-sm),
                var(--shadow-md),
                var(--shadow-lg);
            overflow: hidden;
            animation: shellIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes shellIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ============================================
           PANEL IZQUIERDO (INTRO)
           ============================================ */
        .intro {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(160deg, #eaf6f5 0%, #f5ecf5 100%);
            border-right: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }

        /* Patrón sutil de puntos */
        .intro::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(10, 125, 118, 0.08) 1px, transparent 1px);
            background-size: 22px 22px;
            pointer-events: none;
            opacity: 0.6;
        }

        .intro>* {
            position: relative;
            z-index: 1;
        }

        .mark {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--teal), var(--cyan));
            display: grid;
            place-items: center;
            box-shadow:
                0 8px 20px rgba(10, 125, 118, 0.25),
                inset 0 1px 1px rgba(255, 255, 255, 0.25);
        }

        .mark svg {
            width: 32px;
            height: 32px;
            fill: #fff;
        }

        .eyebrow {
            margin-bottom: 18px;
            color: var(--teal);
            font: 700 11px var(--font);
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 360px;
            font-size: clamp(32px, 4.2vw, 48px);
            line-height: 1.05;
            font-weight: 300;
            letter-spacing: -0.025em;
            color: var(--ink);
        }

        h1 strong {
            font-weight: 600;
            color: var(--teal);
        }

        .intro p.lead {
            max-width: 340px;
            margin-top: 20px;
            color: var(--ink-soft);
            font: 15px/1.6 var(--font);
        }

        .intro p.lead strong {
            color: var(--teal);
            font-weight: 600;
        }

        .note {
            color: var(--muted);
            font: 12px/1.5 var(--font);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .note::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
        }

        /* ============================================
           PANEL DERECHO (FORMULARIO)
           ============================================ */
        .form-panel {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface);
        }

        .form-panel h2 {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
            color: var(--ink);
        }

        .subhead {
            margin-bottom: 28px;
            color: var(--muted);
            font: 14px/1.5 var(--font);
        }

        /* ============================================
           ALERTA GLOBAL
           ============================================ */
        .alert {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            font: 13px/1.45 var(--font);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: alertIn 0.3s ease;
        }

        @keyframes alertIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert::before {
            font-size: 15px;
            line-height: 1.2;
            flex-shrink: 0;
        }

        .alert-danger {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
        }

        .alert-danger::before {
            content: '⚠';
        }

        .alert-success {
            color: var(--success);
            background: var(--success-bg);
            border: 1px solid #bbf7d0;
        }

        .alert-success::before {
            content: '✓';
            font-weight: 700;
        }

        /* ============================================
           CAMPOS
           ============================================ */
        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font: 700 11px var(--font);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink);
        }

        .field input {
            width: 100%;
            padding: 15px 18px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius-md);
            outline: 0;
            color: var(--ink);
            font: 16px var(--font);
            background: #fff;
            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease,
                background-color 0.15s ease;
        }

        .field input::placeholder {
            color: rgba(20, 35, 43, 0.35);
        }

        .field input:hover {
            border-color: #b8c5c1;
        }

        .field input:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 4px rgba(10, 125, 118, 0.12);
        }

        .field input:-webkit-autofill {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        .field input[aria-invalid="true"] {
            border-color: var(--danger);
            box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.10);
        }

        .field-error {
            display: block;
            min-height: 16px;
            margin-top: 6px;
            padding-left: 4px;
            font: 500 12px/1.4 var(--font);
            color: var(--danger);
            opacity: 0;
            transform: translateY(-2px);
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .field-error.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ============================================
           CAMPO CONTRASEÑA
           ============================================ */
        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 54px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .toggle-password:hover {
            background: var(--teal-soft);
            color: var(--teal);
        }

        .toggle-password:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 2px;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ============================================
           BOTÓN
           ============================================ */
        .btn-submit {
            width: 100%;
            margin-top: 10px;
            padding: 16px 18px;
            border: 0;
            border-radius: var(--radius-md);
            color: #fff;
            background: var(--teal);
            cursor: pointer;
            font: 700 13px var(--font);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition:
                background-color 0.15s ease,
                transform 0.1s ease,
                box-shadow 0.15s ease;
            box-shadow: 0 4px 14px rgba(10, 125, 118, 0.25);
        }

        .btn-submit:hover:not(:disabled) {
            background: var(--teal-dark);
            box-shadow: 0 6px 20px rgba(10, 125, 118, 0.35);
            transform: translateY(-1px);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0) scale(0.99);
            box-shadow: 0 2px 8px rgba(10, 125, 118, 0.25);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-submit:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 3px;
        }

        /* Spinner */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        .btn-submit.loading .spinner {
            display: block;
        }

        .btn-submit.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ============================================
           FOOTER DEL FORM
           ============================================ */
        .form-footer {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            text-align: center;
            font: 12px/1.5 var(--font);
            color: var(--muted);
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 820px) {
            .shell {
                grid-template-columns: 1fr;
                min-height: auto;
                max-width: 480px;
            }

            .intro {
                min-height: 220px;
                padding: 36px 32px;
                border-right: none;
                border-bottom: 1px solid var(--line);
            }

            .intro h1 {
                font-size: 30px;
            }

            .intro p.lead {
                display: none;
            }

            .form-panel {
                padding: 36px 32px;
            }

            .blob-teal-1,
            .blob-teal-2 {
                display: none;
            }

            .blob-cyan-1,
            .blob-fuchsia-1 {
                opacity: 0.6;
            }
        }

        @media (max-width: 400px) {
            body {
                padding: 12px;
            }

            .shell {
                border-radius: 18px;
            }

            .intro,
            .form-panel {
                padding: 28px 22px;
            }

            .form-panel h2 {
                font-size: 24px;
            }
        }

        /* ============================================
           ACCESIBILIDAD
           ============================================ */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Soporte para navegadores sin :focus-visible */
        .toggle-password:focus {
            outline: 2px solid var(--teal);
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <!-- Fondo decorativo -->
    <div class="blob blob-cyan-1"></div>
    <div class="blob blob-fuchsia-1"></div>
    <div class="blob blob-teal-1"></div>
    <div class="blob blob-teal-2"></div>

    <main class="shell">
        <!-- ============ PANEL IZQUIERDO ============ -->
        <section class="intro" aria-hidden="true">
            <div class="mark">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.5 2 6 4.5 6 8c0 2.5 1 4.5 1.5 6.5.3 1.2.5 2.5.5 3.5 0 1.5 1 3 2.5 3s2-1 2-2.5c0-1 .5-2 1.5-2s1.5 1 1.5 2c0 1.5.5 2.5 2 2.5s2.5-1.5 2.5-3c0-1 .2-2.3.5-3.5.5-2 1.5-4 1.5-6.5 0-3.5-2.5-6-6-6-1.5 0-2.5.5-3 .5S13.5 2 12 2z" />
                </svg>
            </div>

            <div>
                <p class="eyebrow">Odontología · UPEA · U-TIC</p>
                <h1>Un espacio <strong>claro</strong> para administrar la clínica.</h1>
                <p class="lead">
                    Ingresa al panel administrativo para consultar y organizar la operación
                    <strong>académica y clínica</strong> de la Clínica Odontológica.
                </p>
            </div>

            <div class="note">Acceso protegido para personal autorizado</div>
        </section>

        <!-- ============ PANEL DERECHO ============ -->
        <section class="form-panel">
            <h2>Iniciar sesión</h2>
            <p class="subhead">Usa tus credenciales institucionales.</p>

            <!-- Alerta global -->
            <div id="globalAlert" class="alert" style="display:none;" role="alert" aria-live="polite"></div>

            <form id="loginForm" method="POST" action="{{ route('auth.login.post') }}" novalidate>
                @csrf

                <!-- Usuario -->
                <div class="field">
                    <label for="nombre_usuario">Usuario</label>
                    <input
                        id="nombre_usuario"
                        name="nombre_usuario"
                        type="text"
                        value="{{ old('nombre_usuario') }}"
                        placeholder="Ingresa tu usuario"
                        autocomplete="username"
                        spellcheck="false"
                        autocapitalize="none"
                        required
                        autofocus
                        aria-describedby="error-nombre_usuario">
                    <span class="field-error" id="error-nombre_usuario" data-error="nombre_usuario"></span>
                </div>

                <!-- Contraseña -->
                <div class="field">
                    <label for="contrasena">Contraseña</label>
                    <div class="password-wrap">
                        <input
                            id="contrasena"
                            name="contrasena"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            aria-describedby="error-contrasena">
                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Mostrar u ocultar contraseña"
                            aria-pressed="false"
                            tabindex="0">
                            <svg id="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                            <svg id="eye-open" viewBox="0 0 24 24" style="display:none;" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <span class="field-error" id="error-contrasena" data-error="contrasena"></span>
                </div>

                <button type="submit" id="btnLogin" class="btn-submit">
                    <span class="btn-text">Entrar al panel</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>

            <p class="form-footer">Sistema institucional · UPEA © 2026</p>
        </section>
    </main>

    <script>
        /* ============================================
           TOGGLE CONTRASEÑA
           ============================================ */
        function togglePassword() {
            const input = document.getElementById('contrasena');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');
            const btn = document.querySelector('.toggle-password');

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            eyeOpen.style.display = isPassword ? 'block' : 'none';
            eyeClosed.style.display = isPassword ? 'none' : 'block';
            btn.setAttribute('aria-pressed', String(isPassword));
            input.focus();
        }

        /* ============================================
           LOGIN AJAX CON VALIDACIÓN
           ============================================ */
        (function() {
            const form = document.getElementById('loginForm');
            const alertEl = document.getElementById('globalAlert');
            const btn = document.getElementById('btnLogin');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            /* ---------- Helpers ---------- */
            function mostrarAlerta(tipo, mensaje) {
                alertEl.style.display = 'flex';
                alertEl.className = 'alert alert-' + (tipo === 'error' ? 'danger' : 'success');
                alertEl.textContent = mensaje;
            }

            function limpiarAlerta() {
                alertEl.style.display = 'none';
                alertEl.textContent = '';
            }

            function limpiarErrores() {
                form.querySelectorAll('[data-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.remove('visible');
                });
                form.querySelectorAll('input').forEach(el => {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            function pintarErrores(errors) {
                Object.entries(errors || {}).forEach(([campo, mensajes]) => {
                    const span = form.querySelector(`[data-error="${campo}"]`);
                    const input = form.querySelector(`[name="${campo}"]`);
                    const texto = Array.isArray(mensajes) ? mensajes[0] : mensajes;

                    if (span) {
                        span.textContent = texto;
                        span.classList.add('visible');
                    }
                    if (input) {
                        input.classList.add('is-invalid');
                        input.setAttribute('aria-invalid', 'true');
                    }
                });
            }

            /* ---------- Validación en vivo ---------- */
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', () => {
                    const span = form.querySelector(`[data-error="${input.name}"]`);
                    if (span && span.textContent) {
                        span.textContent = '';
                        span.classList.remove('visible');
                    }
                    input.classList.remove('is-invalid');
                    input.removeAttribute('aria-invalid');
                });
            });

            /* ---------- Submit ---------- */
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                limpiarAlerta();
                limpiarErrores();

                btn.disabled = true;
                btn.classList.add('loading');

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    const contentType = res.headers.get('content-type') || '';
                    const data = contentType.includes('application/json') ?
                        await res.json().catch(() => ({})) : {
                            success: false,
                            message: 'Respuesta no válida del servidor.'
                        };

                    if (res.ok && data.success) {
                        mostrarAlerta('success', data.message || 'Bienvenido');
                        setTimeout(() => {
                            window.location.href = data.redirect || '/';
                        }, 500);
                        return;
                    }

                    if (res.status === 422 && data.errors) {
                        pintarErrores(data.errors);
                        mostrarAlerta('error', data.message || 'Revisa los campos marcados.');
                        return;
                    }

                    mostrarAlerta('error', data.message || 'Error al iniciar sesión.');
                } catch (err) {
                    mostrarAlerta('error', 'Error de red. Verifica tu conexión.');
                    console.error(err);
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                }
            });
        })();
    </script>
</body>

</html>