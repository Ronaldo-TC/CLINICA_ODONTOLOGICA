<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión · Clínica Odontológica UPEA</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #087f79, #078fa0);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
            width: 100%;
            max-width: 420px;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 6px;
            color: #075d5a;
        }

        p.subtitle {
            font-size: 13px;
            color: #60736f;
            margin-bottom: 28px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        label {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #60736f;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #dce6e2;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color .15s ease;
        }

        input:focus {
            border-color: #078fa0;
        }

        input.is-invalid {
            border-color: #dc2626;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 10px;
            background: #087f79;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s ease, opacity .15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button[type="submit"]:hover:not(:disabled) {
            background: #075d5a;
        }

        button[type="submit"]:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }

        .alert-danger {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #7f1d1d;
        }

        .alert-success {
            background: #dcfce7;
            border-color: #86efac;
            color: #14532d;
        }

        .field-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 2px;
            min-height: 16px;
        }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .8s linear infinite;
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

        .hint {
            text-align: center;
            font-size: 11.5px;
            color: #94a3b8;
            margin-top: 24px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h1>Clínica Odontológica</h1>
        <p class="subtitle">UPEA · UTIC — Inicia sesión para continuar</p>

        <div id="loginAlert" style="display:none;"></div>

        <form id="loginForm" method="POST" action="{{ route('auth.login.post') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="nombre_usuario">Usuario</label>
                <input type="text" name="nombre_usuario" id="nombre_usuario"
                    value="{{ old('nombre_usuario') }}" autofocus
                    autocomplete="username" required>
                <span class="field-error" data-error="nombre_usuario"></span>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" name="contrasena" id="contrasena"
                    autocomplete="current-password" required>
                <span class="field-error" data-error="contrasena"></span>
            </div>

            <button type="submit" id="btnLogin">
                <span class="btn-text">Entrar</span>
                <span class="spinner"></span>
            </button>
        </form>

        <p class="hint">Usuario de prueba: admin / 123</p>
    </div>

    <script>
        (function() {
            const form = document.getElementById('loginForm');
            const alertEl = document.getElementById('loginAlert');
            const btn = document.getElementById('btnLogin');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            function mostrarAlerta(tipo, mensaje) {
                alertEl.style.display = 'block';
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