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
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-border: rgba(255, 255, 255, 0.75);
            --glass-shadow: 0 20px 60px rgba(24, 37, 45, 0.12);
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
            background: #ffffff;
            display: grid;
            place-items: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* ============================================
           CÍRCULOS DECORATIVOS DE FONDO (cian y fucsia)
           ============================================ */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.55;
            z-index: 0;
            pointer-events: none;
        }

        .blob-cyan-1 {
            width: 480px;
            height: 480px;
            background: var(--cyan);
            top: -160px;
            left: -140px;
            animation: float1 14s ease-in-out infinite;
        }

        .blob-fuchsia-1 {
            width: 420px;
            height: 420px;
            background: var(--fuchsia);
            bottom: -140px;
            right: -120px;
            animation: float2 18s ease-in-out infinite;
        }

        .blob-cyan-2 {
            width: 300px;
            height: 300px;
            background: var(--cyan);
            top: 40%;
            right: -100px;
            opacity: 0.35;
            animation: float2 16s ease-in-out infinite reverse;
        }

        .blob-fuchsia-2 {
            width: 280px;
            height: 280px;
            background: var(--fuchsia);
            bottom: 20%;
            left: -80px;
            opacity: 0.35;
            animation: float1 20s ease-in-out infinite reverse;
        }

        @keyframes float1 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(30px, -40px) scale(1.1);
            }
        }

        @keyframes float2 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-30px, 40px) scale(1.08);
            }
        }

        /* ============================================
           CONTENEDOR PRINCIPAL (vidrio esmerilado)
           ============================================ */
        .glass-shell {
            position: relative;
            z-index: 1;
            width: min(960px, 100%);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--glass-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            animation: fadeUp 0.8s ease-out;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           PANEL IZQUIERDO (intro)
           ============================================ */
        .intro {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background: linear-gradient(160deg, rgba(6, 182, 212, 0.12), rgba(217, 70, 239, 0.10));
            border-right: 1px solid rgba(255, 255, 255, 0.6);
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
            box-shadow: 0 8px 24px rgba(217, 70, 239, 0.3);
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
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
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
            box-shadow: 0 0 12px #22c55e;
        }

        /* ============================================
           PANEL DERECHO (formulario)
           ============================================ */
        .form-panel {
            padding: clamp(36px, 5vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            border: 1px solid rgba(24, 37, 45, 0.12);
            border-radius: 14px;
            outline: 0;
            color: var(--ink);
            font: 16px -apple-system, sans-serif;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.25s ease;
        }

        input::placeholder {
            color: rgba(24, 37, 45, 0.4);
        }

        input:focus {
            border-color: var(--cyan);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        }

        input:-webkit-autofill {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px rgba(255, 255, 255, 0.9) inset;
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
            transition: all 0.2s ease;
        }

        .toggle-password:hover {
            background: rgba(6, 182, 212, 0.12);
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
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            cursor: pointer;
            font: 700 13px -apple-system, sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: all 0.25s ease;
            box-shadow: 0 8px 24px rgba(217, 70, 239, 0.28);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(217, 70, 239, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0) scale(0.98);
        }

        /* ============================================
           MENSAJES DE ERROR
           ============================================ */
        .error {
            margin-bottom: 16px;
            padding: 13px 16px;
            color: #991b1b;
            background: rgba(254, 226, 226, 0.85);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font: 13px/1.4 -apple-system, sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        .error::before {
            content: '⚠';
            font-size: 16px;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-6px);
            }

            75% {
                transform: translateX(6px);
            }
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 720px) {
            .glass-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .intro {
                min-height: 260px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.6);
            }

            h1 {
                font-size: 32px;
            }

            .note {
                margin-top: 24px;
            }

            .blob {
                filter: blur(50px);
                opacity: 0.4;
            }
        }

        /* Accesibilidad */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    <!-- Círculos decorativos de fondo -->
    <div class="blob blob-cyan-1"></div>
    <div class="blob blob-fuchsia-1"></div>
    <div class="blob blob-cyan-2"></div>
    <div class="blob blob-fuchsia-2"></div>

    <main class="glass-shell">
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
                        <!-- Ojo cerrado (por defecto) -->
                        <svg id="eye-closed" viewBox="0 0 24 24">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <!-- Ojo abierto (oculto por defecto) -->
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