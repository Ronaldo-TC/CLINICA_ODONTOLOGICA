<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso · Clínica Odontológica UPEA</title>
    <style>
        :root {
            --teal: #087f79;
            --teal-dark: #075d5a;
            --ink: #18252d;
            --muted: #718087;
            --cyan: #06b6d4;
            --fuchsia: #d946ef;
            --line: #e2e8e6;
            --surface: #ffffff;
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100vh;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
            color: var(--ink);
            background: #f4f7f5;
            display: grid;
            place-items: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* ============================================
           CÍRCULOS DECORATIVOS DE FONDO (ESTÁTICOS)
           ============================================
           Sin `filter: blur()` ni `animation`.
           Se logra el efecto difuminado con radial-gradient
           que se pinta en CPU, no se compone en GPU.
        */
        .blob {
            position: absolute;
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .blob-cyan-1 {
            width: 620px;
            height: 620px;
            top: -200px;
            left: -180px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.35) 0%, rgba(6, 182, 212, 0.18) 40%, transparent 70%);
        }

        .blob-fuchsia-1 {
            width: 560px;
            height: 560px;
            bottom: -180px;
            right: -160px;
            background: radial-gradient(circle, rgba(217, 70, 239, 0.32) 0%, rgba(217, 70, 239, 0.16) 40%, transparent 70%);
        }

        .blob-cyan-2 {
            width: 380px;
            height: 380px;
            top: 42%;
            right: -140px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.22) 0%, transparent 65%);
        }

        .blob-fuchsia-2 {
            width: 360px;
            height: 360px;
            bottom: 22%;
            left: -120px;
            background: radial-gradient(circle, rgba(217, 70, 239, 0.22) 0%, transparent 65%);
        }

        /* ============================================
           CONTENEDOR PRINCIPAL (sólido, sin blur)
           ============================================ */
        .shell {
            position: relative;
            z-index: 1;
            width: min(960px, 100%);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: 0 4px 16px rgba(24, 37, 45, 0.06);
            overflow: hidden;
        }

        /* ============================================
           PANEL IZQUIERDO (intro)
           ============================================ */
        .intro {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(160deg, #e6f5f7 0%, #f5e8f4 100%);
            border-right: 1px solid var(--line);
        }

        .mark {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            display: grid;
            place-items: center;
            font: 700 26px -apple-system, sans-serif;
            color: #fff;
        }

        .eyebrow {
            margin-bottom: 20px;
            color: var(--teal);
            font: 700 11px -apple-system, sans-serif;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 340px;
            font-size: clamp(34px, 4.5vw, 52px);
            line-height: 1.02;
            font-weight: 300;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        h1 strong {
            font-weight: 600;
            color: var(--teal);
        }

        .intro p {
            max-width: 320px;
            margin-top: 22px;
            color: var(--muted);
            font: 15px/1.6 -apple-system, sans-serif;
        }

        .note {
            color: var(--muted);
            font: 12px/1.5 -apple-system, sans-serif;
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
        }

        /* ============================================
           PANEL DERECHO (formulario)
           ============================================ */
        .form-panel {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface);
        }

        .form-panel h2 {
            font-size: 30px;
            font-weight: 500;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .subhead {
            margin-bottom: 32px;
            color: var(--muted);
            font: 14px/1.5 -apple-system, sans-serif;
        }

        label {
            display: block;
            margin: 22px 0 8px;
            font: 700 11px -apple-system, sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink);
        }

        input {
            width: 100%;
            padding: 15px 18px;
            border: 1px solid var(--line);
            border-radius: 14px;
            outline: 0;
            color: var(--ink);
            font: 16px -apple-system, sans-serif;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input::placeholder {
            color: rgba(24, 37, 45, 0.4);
        }

        input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
        }

        input:-webkit-autofill {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* ============================================
           CAMPO DE CONTRASEÑA CON OJO
           ============================================ */
        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 52px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
        }

        .toggle-password:hover {
            background: #eef3f2;
            color: var(--teal);
        }

        .toggle-password:focus-visible {
            outline: 2px solid var(--cyan);
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
           BOTÓN PRINCIPAL
           ============================================ */
        .btn-submit {
            width: 100%;
            margin-top: 32px;
            padding: 16px 18px;
            border: 0;
            border-radius: 14px;
            color: #fff;
            background: var(--teal);
            cursor: pointer;
            font: 700 13px -apple-system, sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: background-color 0.15s ease;
        }

        .btn-submit:hover {
            background: var(--teal-dark);
        }

        .btn-submit:active {
            transform: scale(0.99);
        }

        /* ============================================
           MENSAJES DE ERROR
           ============================================ */
        .error {
            margin-bottom: 16px;
            padding: 13px 16px;
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 12px;
            font: 13px/1.4 -apple-system, sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error::before {
            content: '⚠';
            font-size: 16px;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 720px) {
            .shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .intro {
                min-height: 260px;
                border-right: none;
                border-bottom: 1px solid var(--line);
            }

            h1 {
                font-size: 32px;
            }

            .note {
                margin-top: 24px;
            }

            /* Círculos más discretos en móvil */
            .blob-cyan-1,
            .blob-fuchsia-1 {
                opacity: 0.6;
            }

            .blob-cyan-2,
            .blob-fuchsia-2 {
                display: none;
            }
        }

        /* Accesibilidad */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    <!-- Círculos decorativos de fondo (estáticos, sin GPU) -->
    <div class="blob blob-cyan-1"></div>
    <div class="blob blob-fuchsia-1"></div>
    <div class="blob blob-cyan-2"></div>
    <div class="blob blob-fuchsia-2"></div>

    <main class="shell">
        <section class="intro">
            <div class="mark">O</div>
            <div>
                <p class="eyebrow">Odontología · UPEA · UTIC</p>
                <h1>Un espacio <strong>claro</strong> para administrar la clínica.</h1>
                <p>Ingresa al panel administrativo para consultar y organizar la operación académica y clínica.</p>
            </div>
            <div class="note">Acceso protegido para personal autorizado</div>
        </section>

        <section class="form-panel">
            <h2>Iniciar sesión</h2>
            <p class="subhead">Usa tus credenciales institucionales.</p>

            @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.login.submit') }}">
                @csrf
                <label for="nombre_usuario">Usuario</label>
                <input
                    id="nombre_usuario"
                    name="nombre_usuario"
                    type="text"
                    value="{{ old('nombre_usuario') }}"
                    placeholder="Ingresa tu usuario"
                    autocomplete="username"
                    required
                    autofocus>

                <label for="contrasena">Contraseña</label>
                <div class="password-wrap">
                    <input
                        id="contrasena"
                        name="contrasena"
                        type="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required>
                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword()"
                        aria-label="Mostrar u ocultar contraseña"
                        tabindex="0">
                        <svg id="eye-closed" viewBox="0 0 24 24">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <svg id="eye-open" viewBox="0 0 24 24" style="display:none;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>

                <button type="submit" class="btn-submit">Entrar al panel</button>
            </form>
        </section>
    </main>

    <script>
        function togglePassword() {
            const input = document.getElementById('contrasena');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            }
        }
    </script>
</body>

</html>