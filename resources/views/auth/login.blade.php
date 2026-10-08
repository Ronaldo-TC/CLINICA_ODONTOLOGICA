<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión · Clínica Odontológica UPEA</title>

    <style>
        /* ===== RESET ===== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', 'Segoe UI', Roboto, sans-serif;
            background: #f2f2f7;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            color: #1c1c1e;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== FONDO DEGRADADO SUAVE (estilo iOS) ===== */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(0, 122, 255, 0.10), transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(88, 86, 214, 0.10), transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.6), transparent 70%);
            z-index: 0;
            pointer-events: none;
        }

        /* ===== TARJETA PRINCIPAL ===== */
        .login-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: saturate(180%) blur(30px);
            -webkit-backdrop-filter: saturate(180%) blur(30px);
            padding: 48px 36px 36px;
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow:
                0 2px 4px rgba(0, 0, 0, 0.02),
                0 12px 32px rgba(0, 0, 0, 0.08),
                0 32px 64px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 400px;
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ===== ENCABEZADO ===== */
        .logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(145deg, #007aff, #5856d6);
            display: grid;
            place-items: center;
            margin: 0 auto 22px;
            box-shadow:
                0 8px 20px rgba(0, 122, 255, 0.25),
                inset 0 1px 1px rgba(255, 255, 255, 0.3);
        }

        .logo svg {
            width: 34px;
            height: 34px;
            fill: #fff;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1c1c1e;
            letter-spacing: -0.03em;
            text-align: center;
        }

        h1 span {
            background: linear-gradient(145deg, #007aff, #5856d6);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            font-size: 14px;
            color: #8e8e93;
            margin-bottom: 32px;
            line-height: 1.4;
            text-align: center;
            font-weight: 400;
        }

        /* ===== CAMPOS ===== */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #3a3a3c;
            padding-left: 4px;
            letter-spacing: -0.01em;
        }

        input {
            width: 100%;
            padding: 15px 16px;
            border: 1px solid #d1d1d6;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.7);
            color: #1c1c1e;
            -webkit-appearance: none;
        }

        input::placeholder {
            color: #c7c7cc;
            font-weight: 400;
        }

        input:focus {
            border-color: #007aff;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.12);
        }

        input.is-invalid {
            border-color: #ff3b30;
            box-shadow: 0 0 0 4px rgba(255, 59, 48, 0.10);
        }

        /* ===== INPUT CONTRASEÑA ===== */
        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 52px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #8e8e93;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all 0.2s ease;
        }

        .toggle-password:hover {
            background: rgba(0, 122, 255, 0.08);
            color: #007aff;
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

        /* ===== BOTÓN ===== */
        button[type="submit"] {
            width: 100%;
            padding: 16px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(145deg, #007aff, #5856d6);
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            font-family: inherit;
            letter-spacing: -0.01em;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            box-shadow:
                0 4px 12px rgba(0, 122, 255, 0.3),
                inset 0 1px 1px rgba(255, 255, 255, 0.2);
            -webkit-appearance: none;
        }

        button[type="submit"]:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow:
                0 6px 18px rgba(0, 122, 255, 0.4),
                inset 0 1px 1px rgba(255, 255, 255, 0.2);
        }

        button[type="submit"]:active:not(:disabled) {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
        }

        button[type="submit"]:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* ===== ALERTAS ===== */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: alertIn 0.3s ease;
        }

        @keyframes alertIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: rgba(255, 59, 48, 0.10);
            border-color: rgba(255, 59, 48, 0.2);
            color: #d70015;
        }

        .alert-success {
            background: rgba(52, 199, 89, 0.10);
            border-color: rgba(52, 199, 89, 0.2);
            color: #248a3d;
        }

        .field-error {
            font-size: 12px;
            color: #ff3b30;
            margin-top: 4px;
            min-height: 16px;
            padding-left: 4px;
            font-weight: 500;
        }

        /* ===== SPINNER ===== */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        button.loading .spinner {
            display: block;
        }

        button.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ===== FOOTER ===== */
        .hint {
            text-align: center;
            font-size: 12px;
            color: #c7c7cc;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            font-weight: 400;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 420px) {
            .login-card {
                padding: 36px 24px 28px;
                border-radius: 18px;
            }

            h1 {
                font-size: 22px;
            }

            .logo {
                width: 56px;
                height: 56px;
                border-radius: 16px;
            }

            .logo svg {
                width: 30px;
                height: 30px;
            }
        }

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
    <div class="login-card">
        <!-- Logo con ícono de diente -->
        <div class="logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C8.5 2 6 4.5 6 8c0 2.5 1 4.5 1.5 6.5.3 1.2.5 2.5.5 3.5 0 1.5 1 3 2.5 3s2-1 2-2.5c0-1 .5-2 1.5-2s1.5 1 1.5 2c0 1.5.5 2.5 2 2.5s2.5-1.5 2.5-3c0-1 .2-2.3.5-3.5.5-2 1.5-4 1.5-6.5 0-3.5-2.5-6-6-6-1.5 0-2.5.5-3 .5S13.5 2 12 2z" />
            </svg>
        </div>

        <h1>Clínica <span>Odontológica</span></h1>
        <p class="subtitle">Universidad Pública de El Alto · U-TIC</p>

        <div id="loginAlert" style="display:none;"></div>

        <form id="loginForm" method="POST" action="{{ route('auth.login.post') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="nombre_usuario">Usuario</label>
                <input type="text" name="nombre_usuario" id="nombre_usuario"
                    value="{{ old('nombre_usuario') }}" autofocus
                    autocomplete="username" placeholder="Ingresa tu usuario" required>
                <span class="field-error" data-error="nombre_usuario"></span>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <div class="password-wrap">
                    <input type="password" name="contrasena" id="contrasena"
                        autocomplete="current-password" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Mostrar u ocultar contraseña">
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
                <span class="field-error" data-error="contrasena"></span>
            </div>

            <button type="submit" id="btnLogin">
                <span class="btn-text">Iniciar sesión</span>
                <span class="spinner"></span>
            </button>
        </form>

        <p class="hint">Sistema institucional · UPEA © 2026</p>
    </div>

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

        (function() {
            const form = document.getElementById('loginForm');
            const alertEl = document.getElementById('loginAlert');
            const btn = document.getElementById('btnLogin');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
                form.querySelectorAll('[data-error]').forEach(el => el.textContent = '');
                form.querySelectorAll('input').forEach(el => el.classList.remove('is-invalid'));
            }

            function pintarErrores(errors) {
                Object.entries(errors || {}).forEach(([campo, mensajes]) => {
                    const span = form.querySelector(`[data-error="${campo}"]`);
                    if (span) span.textContent = Array.isArray(mensajes) ? mensajes[0] : mensajes;

                    const input = form.querySelector(`[name="${campo}"]`);
                    if (input) input.classList.add('is-invalid');
                });
            }

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
                        mostrarAlerta('error', data.message || 'Revisa los campos.');
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