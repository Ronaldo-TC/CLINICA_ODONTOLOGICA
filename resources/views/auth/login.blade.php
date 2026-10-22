<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a3f52">
    <link rel="icon" type="image/png" href="{{ asset('images/login/logo-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/login/logo-icon.png') }}">
    <title>Acceso · Clínica Odontológica UPEA</title>

    <style>
        :root {
            color-scheme: light;
            --navy-950: #082d3b;
            --navy-900: #0a3f52;
            --navy-800: #12576d;
            --teal-700: #087f79;
            --teal-600: #0c948c;
            --teal-100: #dff4f1;
            --teal-50: #f0faf8;
            --ink: #12282f;
            --ink-soft: #38515a;
            --muted: #6b7f87;
            --line: #dbe7e6;
            --line-strong: #bfd3d1;
            --surface: #ffffff;
            --surface-soft: #f5f9f8;
            --danger: #b42318;
            --danger-bg: #fff4f2;
            --danger-line: #fecdca;
            --success: #067647;
            --success-bg: #ecfdf3;
            --success-line: #abefc6;
            --shadow: 0 30px 80px rgba(14, 52, 69, .18), 0 8px 24px rgba(14, 52, 69, .08);
            --font: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            min-width: 320px;
            background: #eaf2f4;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: clamp(18px, 3vw, 40px);
            overflow-x: hidden;
            color: var(--ink);
            font-family: var(--font);
            background:
                radial-gradient(circle at 8% 12%, rgba(14, 165, 164, .13), transparent 28%),
                radial-gradient(circle at 94% 90%, rgba(23, 105, 140, .13), transparent 30%),
                linear-gradient(145deg, #f5fafb 0%, #e8f1f3 48%, #edf3f7 100%);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            z-index: 0;
            border: 1px solid rgba(8, 127, 121, .08);
            border-radius: 50%;
            pointer-events: none;
        }

        body::before {
            width: 440px;
            height: 440px;
            top: -270px;
            right: -140px;
            box-shadow: 0 0 0 70px rgba(8, 127, 121, .025), 0 0 0 140px rgba(8, 127, 121, .02);
        }

        body::after {
            width: 320px;
            height: 320px;
            bottom: -220px;
            left: -100px;
            box-shadow: 0 0 0 60px rgba(18, 87, 109, .025);
        }

        button,
        input {
            font: inherit;
        }

        .auth-shell {
            position: relative;
            z-index: 1;
            width: min(1180px, 100%);
            min-height: min(720px, calc(100dvh - 80px));
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(430px, .92fr);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .88);
            border-radius: 28px;
            background: var(--surface);
            box-shadow: var(--shadow);
            animation: reveal .55s cubic-bezier(.16, 1, .3, 1) both;
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(14px) scale(.992); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .visual-panel {
            position: relative;
            isolation: isolate;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(30px, 4vw, 50px);
            overflow: hidden;
            color: #fff;
            background-color: var(--navy-900);
            background-image:
                linear-gradient(180deg, rgba(5, 36, 51, .72) 0%, rgba(5, 45, 59, .35) 42%, rgba(4, 28, 40, .9) 100%),
                image-set(
                    url("{{ asset('images/login/fondo-pc.webp') }}") type("image/webp"),
                    url("{{ asset('images/login/fondo-pc.png') }}") type("image/png")
                );
            background-position: center;
            background-size: cover;
        }

        .visual-panel::before {
            content: '';
            position: absolute;
            z-index: -1;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(3, 39, 54, .42), transparent 65%),
                radial-gradient(circle at 86% 15%, rgba(128, 236, 225, .2), transparent 23%);
            pointer-events: none;
        }

        .visual-panel::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: 380px;
            height: 380px;
            right: -210px;
            bottom: -200px;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 50%;
            box-shadow: 0 0 0 55px rgba(255, 255, 255, .035), 0 0 0 110px rgba(255, 255, 255, .025);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            width: fit-content;
        }

        .brand-mark {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .42);
            border-radius: 17px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 10px 28px rgba(3, 25, 36, .24);
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            padding: 7px;
        }

        .brand-copy strong,
        .brand-copy span {
            display: block;
        }

        .brand-copy strong {
            font-size: 15px;
            line-height: 1.2;
            letter-spacing: -.015em;
        }

        .brand-copy span {
            margin-top: 4px;
            color: rgba(255, 255, 255, .78);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .hero-copy {
            max-width: 500px;
            padding: 42px 0 34px;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 18px;
            color: #b8fbf4;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .17em;
            text-transform: uppercase;
        }

        .hero-kicker::before {
            content: '';
            width: 24px;
            height: 1px;
            background: currentColor;
        }

        .hero-copy h1 {
            max-width: 490px;
            font-size: clamp(38px, 4.4vw, 55px);
            font-weight: 640;
            line-height: 1.04;
            letter-spacing: -.05em;
            text-wrap: balance;
        }

        .hero-copy h1 span {
            color: #9ceee7;
        }

        .hero-description {
            max-width: 430px;
            margin-top: 20px;
            color: rgba(255, 255, 255, .82);
            font-size: 14px;
            line-height: 1.65;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 30px;
        }

        .feature {
            min-width: 0;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 12px;
            background: rgba(5, 38, 52, .28);
            backdrop-filter: blur(8px);
        }

        .feature svg {
            width: 19px;
            height: 19px;
            display: block;
            margin-bottom: 8px;
            color: #9ceee7;
        }

        .feature span {
            display: block;
            color: rgba(255, 255, 255, .86);
            font-size: 10.5px;
            font-weight: 600;
            line-height: 1.35;
        }

        .visual-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            color: rgba(255, 255, 255, .72);
            font-size: 10.5px;
        }

        .secure-status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .secure-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #47d7a1;
            box-shadow: 0 0 0 4px rgba(71, 215, 161, .15);
        }

        .form-panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(38px, 5vw, 70px);
            background:
                radial-gradient(circle at 100% 0%, rgba(12, 148, 140, .055), transparent 30%),
                var(--surface);
        }

        .form-container {
            width: min(100%, 440px);
        }

        .form-heading {
            margin-bottom: 28px;
        }

        .form-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            color: var(--teal-700);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .form-kicker svg {
            width: 16px;
            height: 16px;
        }

        .form-heading h2 {
            color: var(--ink);
            font-size: clamp(30px, 3vw, 38px);
            font-weight: 680;
            line-height: 1.12;
            letter-spacing: -.045em;
        }

        .form-heading p:last-child {
            margin-top: 9px;
            color: var(--muted);
            font-size: 13.5px;
            line-height: 1.55;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid;
            border-radius: 11px;
            font-size: 12.5px;
            line-height: 1.45;
            animation: alert-in .24s ease both;
        }

        .alert[hidden] {
            display: none;
        }

        @keyframes alert-in {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            color: var(--danger);
            border-color: var(--danger-line);
            background: var(--danger-bg);
        }

        .alert-success {
            color: var(--success);
            border-color: var(--success-line);
            background: var(--success-bg);
        }

        .alert-icon {
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
        }

        .field {
            margin-bottom: 17px;
        }

        .field-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 7px;
        }

        .field label {
            color: var(--ink-soft);
            font-size: 12px;
            font-weight: 700;
        }

        .input-shell {
            position: relative;
        }

        .input-icon {
            position: absolute;
            z-index: 1;
            top: 50%;
            left: 16px;
            width: 19px;
            height: 19px;
            transform: translateY(-50%);
            color: #82969e;
            pointer-events: none;
            transition: color .18s ease;
        }

        .field input {
            width: 100%;
            height: 55px;
            padding: 0 48px;
            border: 1px solid var(--line);
            border-radius: 13px;
            outline: 0;
            color: var(--ink);
            background: var(--surface-soft);
            font-size: 15px;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .field input::placeholder {
            color: #99a9ae;
        }

        .field input:hover {
            border-color: var(--line-strong);
        }

        .input-shell:focus-within .input-icon {
            color: var(--teal-700);
        }

        .field input:focus {
            border-color: var(--teal-600);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(12, 148, 140, .1);
        }

        .field input[aria-invalid="true"] {
            border-color: var(--danger);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(180, 35, 24, .08);
        }

        .field input:-webkit-autofill {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
        }

        .password-toggle {
            position: absolute;
            z-index: 2;
            top: 50%;
            right: 9px;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 9px;
            color: #748991;
            background: transparent;
            cursor: pointer;
            transition: color .18s ease, background-color .18s ease;
        }

        .password-toggle:hover {
            color: var(--teal-700);
            background: var(--teal-100);
        }

        .password-toggle svg {
            width: 19px;
            height: 19px;
        }

        .password-toggle [data-eye="open"] {
            display: none;
        }

        .password-toggle[aria-pressed="true"] [data-eye="open"] {
            display: block;
        }

        .password-toggle[aria-pressed="true"] [data-eye="closed"] {
            display: none;
        }

        .field-error,
        .caps-warning {
            display: block;
            max-height: 0;
            overflow: hidden;
            padding-left: 3px;
            color: var(--danger);
            font-size: 11.5px;
            line-height: 1.4;
            opacity: 0;
            transform: translateY(-2px);
            transition: max-height .18s ease, margin-top .18s ease, opacity .18s ease, transform .18s ease;
        }

        .field-error.visible,
        .caps-warning.visible {
            max-height: 38px;
            margin-top: 6px;
            opacity: 1;
            transform: translateY(0);
        }

        .caps-warning {
            color: #9a6700;
        }

        .submit-button {
            width: 100%;
            min-height: 55px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 7px;
            padding: 13px 18px;
            border: 0;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, var(--teal-700), #0b718e);
            box-shadow: 0 10px 22px rgba(8, 127, 121, .21);
            cursor: pointer;
            font-size: 13px;
            font-weight: 750;
            letter-spacing: .01em;
            transition: transform .15s ease, box-shadow .18s ease, filter .18s ease;
        }

        .submit-button:hover:not(:disabled) {
            filter: brightness(1.055);
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(8, 127, 121, .28);
        }

        .submit-button:active:not(:disabled) {
            transform: translateY(0);
        }

        .submit-button:disabled {
            cursor: wait;
            opacity: .78;
        }

        .submit-arrow {
            width: 18px;
            height: 18px;
            transition: transform .18s ease;
        }

        .submit-button:hover:not(:disabled) .submit-arrow {
            transform: translateX(3px);
        }

        .spinner {
            width: 17px;
            height: 17px;
            display: none;
            border: 2px solid rgba(255, 255, 255, .34);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        .submit-button.loading .spinner {
            display: block;
        }

        .submit-button.loading .submit-arrow {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .access-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 20px;
            padding: 13px 14px;
            border: 1px solid #e2ecea;
            border-radius: 11px;
            color: var(--muted);
            background: #fbfdfc;
            font-size: 11.5px;
            line-height: 1.5;
        }

        .access-note svg {
            width: 17px;
            height: 17px;
            flex: 0 0 17px;
            margin-top: 1px;
            color: var(--teal-700);
        }

        .form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 25px;
            padding-top: 18px;
            border-top: 1px solid #e7efed;
            color: #83949a;
            font-size: 10.5px;
        }

        .form-footer strong {
            color: var(--ink-soft);
            font-weight: 700;
        }

        .mobile-brand {
            display: none;
        }

        :focus-visible {
            outline: 3px solid rgba(12, 148, 140, .35);
            outline-offset: 3px;
        }

        @media (max-width: 900px) {
            body {
                align-items: start;
                padding: 18px;
            }

            .auth-shell {
                max-width: 560px;
                min-height: 0;
                grid-template-columns: 1fr;
                border-radius: 24px;
            }

            .visual-panel {
                min-height: 245px;
                padding: 24px 26px;
                background-image:
                    linear-gradient(180deg, rgba(5, 36, 51, .68), rgba(4, 28, 40, .82)),
                    image-set(
                        url("{{ asset('images/login/fondo-movil.webp') }}") type("image/webp"),
                        url("{{ asset('images/login/fondo-movil.png') }}") type("image/png")
                    );
                background-position: center 51%;
            }

            .visual-panel .brand,
            .hero-description,
            .feature-list,
            .visual-footer {
                display: none;
            }

            .hero-copy {
                margin-top: auto;
                padding: 60px 0 0;
            }

            .hero-kicker {
                margin-bottom: 10px;
                font-size: 9px;
            }

            .hero-copy h1 {
                max-width: 420px;
                font-size: clamp(28px, 7vw, 36px);
                line-height: 1.08;
            }

            .form-panel {
                padding: 30px clamp(24px, 7vw, 40px) 28px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 11px;
                margin-bottom: 26px;
            }

            .mobile-brand .brand-mark {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
                border-color: var(--line);
                border-radius: 13px;
                box-shadow: 0 6px 16px rgba(16, 55, 69, .1);
            }

            .mobile-brand .brand-copy strong {
                color: var(--ink);
                font-size: 13px;
            }

            .mobile-brand .brand-copy span {
                color: var(--muted);
                font-size: 9px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
                background: #eaf2f4;
            }

            body::before,
            body::after {
                display: none;
            }

            .auth-shell {
                border-radius: 21px;
            }

            .visual-panel {
                min-height: 182px;
                padding: 18px 20px;
            }

            .hero-copy {
                padding-top: 34px;
            }

            .hero-copy h1 {
                font-size: 27px;
            }

            .form-panel {
                padding: 21px 22px 19px;
            }

            .mobile-brand {
                margin-bottom: 16px;
            }

            .form-heading {
                margin-bottom: 19px;
            }

            .form-heading h2 {
                font-size: 29px;
            }

            .field input,
            .submit-button {
                min-height: 53px;
                height: 53px;
            }

            .form-footer {
                margin-top: 18px;
                padding-top: 14px;
                display: block;
                text-align: center;
            }

            .access-note {
                margin-top: 15px;
            }

            .form-footer span:last-child {
                display: block;
                margin-top: 4px;
            }
        }

        @media (min-width: 901px) and (max-height: 760px) {
            body {
                padding: 18px;
            }

            .auth-shell {
                min-height: 650px;
            }

            .visual-panel {
                padding: 30px 38px;
            }

            .hero-copy {
                padding: 22px 0;
            }

            .hero-copy h1 {
                font-size: 44px;
            }

            .feature-list {
                margin-top: 20px;
            }

            .form-panel {
                padding: 34px 54px;
            }

            .form-heading {
                margin-bottom: 22px;
            }

            .access-note {
                margin-top: 15px;
            }

            .form-footer {
                margin-top: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
    <main class="auth-shell">
        <section class="visual-panel" aria-labelledby="login-hero-title">
            <div class="brand">
                <picture class="brand-mark">
                    <source srcset="{{ asset('images/login/logo-compacto.webp') }}" type="image/webp">
                    <img src="{{ asset('images/login/logo.png') }}" alt="Emblema de la Carrera de Odontología UPEA" width="62" height="62">
                </picture>
                <div class="brand-copy">
                    <strong>Clínica Odontológica</strong>
                    <span>UPEA · U-TIC</span>
                </div>
            </div>

            <div class="hero-copy">
                <p class="hero-kicker">Plataforma clínica universitaria</p>
                <h1 id="login-hero-title">Gestión clínica que acompaña <span>cada sonrisa.</span></h1>
                <p class="hero-description">Un entorno integrado para organizar la atención del paciente, el seguimiento clínico y la formación académica.</p>

                <div class="feature-list" aria-label="Áreas principales del sistema">
                    <div class="feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 6a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/></svg>
                        <span>Admisión de pacientes</span>
                    </div>
                    <div class="feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                        <span>Seguimiento clínico</span>
                    </div>
                    <div class="feature">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 9 9-5 9 5-9 5-9-5ZM6 11v6c4 3 8 3 12 0v-6M21 9v8"/></svg>
                        <span>Gestión académica</span>
                    </div>
                </div>
            </div>

            <div class="visual-footer">
                <span class="secure-status">Acceso protegido para personal autorizado</span>
                <span>Odontología acreditada</span>
            </div>
        </section>

        <section class="form-panel" aria-labelledby="login-title">
            <div class="form-container">
                <div class="mobile-brand" aria-hidden="true">
                    <picture class="brand-mark">
                        <source srcset="{{ asset('images/login/logo-compacto.webp') }}" type="image/webp">
                        <img src="{{ asset('images/login/logo.png') }}" alt="" width="48" height="48">
                    </picture>
                    <div class="brand-copy">
                        <strong>Clínica Odontológica</strong>
                        <span>UPEA · U-TIC</span>
                    </div>
                </div>

                <header class="form-heading">
                    <p class="form-kicker">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                        Acceso institucional seguro
                    </p>
                    <h2 id="login-title">Bienvenido</h2>
                    <p>Ingresa tus credenciales para continuar al panel clínico.</p>
                </header>

                <div
                    id="globalAlert"
                    class="alert {{ $errors->any() ? 'alert-danger' : '' }}"
                    role="alert"
                    aria-live="polite"
                    @if(! $errors->any()) hidden @endif
                >
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                    <span data-alert-message>{{ $errors->first() }}</span>
                </div>

                <form id="loginForm" method="POST" action="{{ route('auth.login.post') }}" novalidate>
                    @csrf

                    <div class="field">
                        <div class="field-header">
                            <label for="nombre_usuario">Usuario institucional</label>
                        </div>
                        <div class="input-shell">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                            <input
                                id="nombre_usuario"
                                name="nombre_usuario"
                                type="text"
                                value="{{ old('nombre_usuario') }}"
                                placeholder="Ej. usuario.apellido"
                                autocomplete="username"
                                spellcheck="false"
                                autocapitalize="none"
                                required
                                autofocus
                                aria-describedby="error-nombre_usuario"
                                @if($errors->has('nombre_usuario')) aria-invalid="true" @endif
                            >
                        </div>
                        <span class="field-error {{ $errors->has('nombre_usuario') ? 'visible' : '' }}" id="error-nombre_usuario" data-error="nombre_usuario">{{ $errors->first('nombre_usuario') }}</span>
                    </div>

                    <div class="field">
                        <div class="field-header">
                            <label for="contrasena">Contraseña</label>
                        </div>
                        <div class="input-shell">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <input
                                id="contrasena"
                                name="contrasena"
                                type="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                                aria-describedby="error-contrasena caps-warning"
                                @if($errors->has('contrasena')) aria-invalid="true" @endif
                            >
                            <button type="button" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false">
                                <svg data-eye="closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 3l18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.9 10.9 0 0 1 12 4c7 0 10 8 10 8a15.7 15.7 0 0 1-2 3.1M6.6 6.7A16.8 16.8 0 0 0 2 12s3 8 10 8a10.2 10.2 0 0 0 5.3-1.5"/></svg>
                                <svg data-eye="open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3-8 10-8 10 8 10 8-3 8-10 8S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <span class="field-error {{ $errors->has('contrasena') ? 'visible' : '' }}" id="error-contrasena" data-error="contrasena">{{ $errors->first('contrasena') }}</span>
                        <span class="caps-warning" id="caps-warning">Bloq Mayús está activado.</span>
                    </div>

                    <button type="submit" id="btnLogin" class="submit-button">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="submit-label">Ingresar al sistema</span>
                        <svg class="submit-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>

                <div class="access-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    <p>El acceso es exclusivo para personal autorizado. Si tienes inconvenientes con tus credenciales, comunícate con administración.</p>
                </div>

                <footer class="form-footer">
                    <span><strong>UPEA</strong> · Carrera de Odontología</span>
                    <span>U-TIC © {{ now()->year }}</span>
                </footer>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const form = document.getElementById('loginForm');
            const alertElement = document.getElementById('globalAlert');
            const alertMessage = alertElement.querySelector('[data-alert-message]');
            const submitButton = document.getElementById('btnLogin');
            const passwordInput = document.getElementById('contrasena');
            const passwordToggle = document.querySelector('.password-toggle');
            const capsWarning = document.getElementById('caps-warning');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            let redirecting = false;

            const showAlert = (type, message) => {
                alertElement.hidden = false;
                alertElement.className = `alert alert-${type === 'error' ? 'danger' : 'success'}`;
                alertMessage.textContent = message;
            };

            const clearAlert = () => {
                alertElement.hidden = true;
                alertMessage.textContent = '';
            };

            const clearErrors = () => {
                form.querySelectorAll('[data-error]').forEach((element) => {
                    element.textContent = '';
                    element.classList.remove('visible');
                });
                form.querySelectorAll('input').forEach((input) => input.removeAttribute('aria-invalid'));
            };

            const renderErrors = (errors) => {
                let firstInvalidInput = null;

                Object.entries(errors || {}).forEach(([field, messages]) => {
                    const error = form.querySelector(`[data-error="${field}"]`);
                    const input = form.querySelector(`[name="${field}"]`);
                    const message = Array.isArray(messages) ? messages[0] : messages;

                    if (error) {
                        error.textContent = message;
                        error.classList.add('visible');
                    }
                    if (input) {
                        input.setAttribute('aria-invalid', 'true');
                        firstInvalidInput ||= input;
                    }
                });

                firstInvalidInput?.focus();
            };

            passwordToggle.addEventListener('click', () => {
                const willShow = passwordInput.type === 'password';
                passwordInput.type = willShow ? 'text' : 'password';
                passwordToggle.setAttribute('aria-pressed', String(willShow));
                passwordToggle.setAttribute('aria-label', willShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
                passwordInput.focus({ preventScroll: true });
            });

            const updateCapsLock = (event) => {
                capsWarning.classList.toggle('visible', event.getModifierState?.('CapsLock') === true);
            };
            passwordInput.addEventListener('keydown', updateCapsLock);
            passwordInput.addEventListener('keyup', updateCapsLock);
            passwordInput.addEventListener('blur', () => capsWarning.classList.remove('visible'));

            form.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', () => {
                    const error = form.querySelector(`[data-error="${input.name}"]`);
                    if (error) {
                        error.textContent = '';
                        error.classList.remove('visible');
                    }
                    input.removeAttribute('aria-invalid');
                    if (!alertElement.hidden) clearAlert();
                });
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearAlert();
                clearErrors();
                redirecting = false;
                submitButton.disabled = true;
                submitButton.classList.add('loading');
                form.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });
                    const contentType = response.headers.get('content-type') || '';
                    const data = contentType.includes('application/json')
                        ? await response.json().catch(() => ({}))
                        : { success: false, message: 'El servidor devolvió una respuesta no válida.' };

                    if (response.ok && data.success) {
                        redirecting = true;
                        showAlert('success', data.message || 'Acceso correcto. Redirigiendo…');
                        window.setTimeout(() => window.location.assign(data.redirect || '/'), 350);
                        return;
                    }

                    if (response.status === 422 && data.errors) {
                        renderErrors(data.errors);
                    }
                    showAlert('error', data.message || 'No fue posible iniciar sesión. Revisa tus datos.');
                } catch (error) {
                    showAlert('error', 'No pudimos conectar con el servidor. Verifica tu conexión e intenta nuevamente.');
                    console.error(error);
                } finally {
                    if (!redirecting) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('loading');
                        form.removeAttribute('aria-busy');
                    }
                }
            });
        })();
    </script>
</body>

</html>
