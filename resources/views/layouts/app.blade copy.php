<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Clínica Odontológica UPEA</title>
    <script>
        (() => {
            const root = document.documentElement;
            try {
                const savedTheme = localStorage.getItem('upea-theme');
                root.dataset.theme = ['light', 'dark'].includes(savedTheme) ?
                    savedTheme :
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            } catch {
                root.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
        })();
    </script>
    <style>
        /* ============================================================
           RESET Y VARIABLES GLOBALES
           ============================================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            color-scheme: light;
            --teal: #087f79;
            --teal-dark: #075d5a;
            --cyan: #078fa0;
            --fuchsia: #b3449e;
            --ink: #1b2e2b;
            --muted: #60736f;
            --line: #dce6e2;
            --bg: #f4f7f5;
            --surface: #ffffff;
            --surface-raised: #ffffff;
            --surface-soft: #edf3f1;
            --surface-border: #dce6e2;
            --sidebar-bg: rgba(255, 255, 255, 0.94);
            --topbar-bg: rgba(255, 255, 255, 0.88);
            --control-bg: #ffffff;
            /* --grid-line: rgba(8, 127, 121, 0.045); */
            --particle-color: rgba(8, 127, 121, 0.35);
            --particle-color-2: rgba(7, 143, 160, 0.28);
            --particle-color-3: rgba(179, 68, 158, 0.18);

            --sidebar-w: 260px;
            --sidebar-w-collapsed: 0px;

            --success: #16a34a;
            --success-bg: #dcfce7;
            --success-border: #86efac;
            --danger: #dc2626;
            --danger-bg: #fee2e2;
            --danger-border: #fca5a5;
            --warning: #d97706;
            --warning-bg: #fef3c7;
            --warning-border: #fcd34d;
            --info: #0284c7;
            --info-bg: #dbeafe;
            --info-border: #93c5fd;

            --glass-bg: rgba(255, 255, 255, 0.65);
            --glass-border: rgba(255, 255, 255, 0.85);
            --glass-shadow: 0 8px 32px rgba(24, 37, 45, 0.08);

            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.4s cubic-bezier(0.4, 0, 0.2, 1);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 999px;
        }

        html,
        body {
            min-height: 100vh;
            min-height: 100dvh;
        }

        html {
            color-scheme: light;
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--ink);
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.5;
        }

        /* Capa de partículas detrás de todo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image:
                radial-gradient(circle, var(--particle-color) 1.4px, transparent 1.6px),
                radial-gradient(circle, var(--particle-color-2) 1px, transparent 1.2px),
                radial-gradient(circle, var(--particle-color-3) 0.8px, transparent 1px);
            background-size: 180px 180px, 240px 240px, 320px 320px;
            background-position: 0 0, 60px 80px, 120px 40px;
            opacity: 0.7;
            mask-image: radial-gradient(ellipse at top, #000 30%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at top, #000 30%, transparent 80%);
        }

        /* Un segundo layer un poco más animado (opcional, muy barato) */
        body::after {
            content: '';
            position: fixed;
            inset: -20%;
            z-index: 0;
            pointer-events: none;
            background-image:
                radial-gradient(circle, var(--particle-color-2) 1px, transparent 1.2px),
                radial-gradient(circle, var(--particle-color) 0.8px, transparent 1px);
            background-size: 280px 280px, 360px 360px;
            background-position: 30px 60px, 200px 120px;
            opacity: 0.35;
            animation: particleDrift 60s linear infinite;
            will-change: transform;
        }

        @keyframes particleDrift {
            from {
                transform: translate3d(0, 0, 0);
            }

            to {
                transform: translate3d(-280px, -280px, 0);
            }
        }

        body.no-scroll {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }

        img,
        svg,
        video {
            max-width: 100%;
            height: auto;
            display: block;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
            color: inherit;
        }

        /* ============================================================
           LAYOUT PRINCIPAL
           ============================================================ */
        .app {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
            min-height: 100dvh;
            transition: grid-template-columns var(--transition-base);
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        /* ============================================================
           SIDEBAR
           ============================================================ */
        .sidebar {
            background: var(--sidebar-bg);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-right: 1px solid rgba(255, 255, 255, 0.85);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            transition: transform var(--transition-base), box-shadow var(--transition-base);
            -webkit-overflow-scrolling: touch;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 20px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
        }

        .sidebar-brand .logo {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            display: grid;
            place-items: center;
            color: #fff;
            font: 700 18px -apple-system, sans-serif;
            box-shadow: 0 6px 18px rgba(217, 70, 239, 0.28);
            transition: transform var(--transition-base);
            flex-shrink: 0;
        }

        .sidebar-brand .logo:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .sidebar-brand .brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sidebar-brand .brand-title {
            font: 700 14px -apple-system, sans-serif;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-brand .brand-sub {
            font: 11px -apple-system, sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sidebar-user {
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            display: grid;
            place-items: center;
            color: #fff;
            font: 700 14px -apple-system, sans-serif;
            flex-shrink: 0;
            transition: transform var(--transition-base);
        }

        .avatar:hover {
            transform: scale(1.08);
        }

        .user-info {
            overflow: hidden;
            min-width: 0;
        }

        .user-name {
            font: 600 13px -apple-system, sans-serif;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font: 11px -apple-system, sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .nav-label {
            padding: 12px 10px 6px;
            font: 700 10px -apple-system, sans-serif;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            border-radius: 10px;
            color: var(--ink);
            text-decoration: none;
            font: 500 13.5px -apple-system, sans-serif;
            transition: all var(--transition-fast);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(217, 70, 239, 0.06));
            opacity: 0;
            transition: opacity var(--transition-fast);
            border-radius: inherit;
            pointer-events: none;
        }

        .nav-item:hover::after {
            opacity: 1;
        }

        .nav-item:hover {
            color: var(--teal);
            transform: translateX(2px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.14), rgba(217, 70, 239, 0.10));
            color: var(--teal-dark);
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 22px;
            border-radius: 0 3px 3px 0;
            background: linear-gradient(180deg, var(--cyan), var(--fuchsia));
            animation: slideInLeft var(--transition-base) ease;
        }

        @keyframes slideInLeft {
            from {
                height: 0;
                opacity: 0;
            }

            to {
                height: 22px;
                opacity: 1;
            }
        }

        .nav-item svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
            transition: transform var(--transition-fast);
        }

        .nav-item:hover svg {
            transform: scale(1.1);
        }

        .nav-item.disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }

        .nav-empty {
            padding: 12px 14px;
            font: 400 13px -apple-system, sans-serif;
            color: var(--muted);
            font-style: italic;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--line);
            flex-shrink: 0;
        }

        .logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            font: 600 13px -apple-system, sans-serif;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .logout-btn:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
            transform: translateY(-1px);
        }

        /* Overlay para sidebar móvil */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(24, 37, 45, 0.45);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-base), visibility var(--transition-base);
        }

        .sidebar-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        /* ============================================================
           TOPBAR
           ============================================================ */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 28px;
            background: var(--topbar-bg);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.85);
            transition: box-shadow var(--transition-base);
            min-height: 68px;
        }

        .topbar.scrolled {
            box-shadow: 0 4px 20px rgba(24, 37, 45, 0.08);
        }

        .topbar-mobile-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            cursor: pointer;
            place-items: center;
            transition: all var(--transition-fast);
            flex-shrink: 0;
        }

        .topbar-mobile-toggle:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .topbar-mobile-toggle svg {
            width: 20px;
            height: 20px;
        }

        .topbar-title {
            flex: 1;
            min-width: 0;
        }

        .topbar-heading {
            font: 600 18px -apple-system, sans-serif;
            letter-spacing: -0.02em;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-sub {
            font: 12px -apple-system, sans-serif;
            color: var(--muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .topbar-search {
            position: relative;
            display: flex;
            align-items: center;
            width: 240px;
            padding: 9px 14px;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--line);
            transition: all var(--transition-base);
        }

        .topbar-search:focus-within {
            border-color: var(--cyan);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.12);
            background: #fff;
            transform: translateY(-1px);
        }

        .topbar-search svg {
            width: 16px;
            height: 16px;
            color: var(--muted);
            flex-shrink: 0;
        }

        .topbar-search input {
            flex: 1;
            margin-left: 8px;
            border: 0;
            outline: 0;
            background: transparent;
            font: 13px -apple-system, sans-serif;
            color: var(--ink);
            min-width: 0;
        }

        .topbar-search input::placeholder {
            color: var(--muted);
        }

        .topbar-icon-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            color: var(--ink);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all var(--transition-fast);
            flex-shrink: 0;
        }

        .topbar-icon-btn:hover {
            background: #fff;
            border-color: var(--cyan);
            color: var(--teal);
            transform: translateY(-1px);
        }

        .topbar-icon-btn:active {
            transform: translateY(0) scale(0.96);
        }

        .topbar-icon-btn svg {
            width: 18px;
            height: 18px;
        }

        .theme-icon--sun {
            display: none;
        }

        :root[data-theme="dark"] .theme-icon--moon {
            display: none;
        }

        :root[data-theme="dark"] .theme-icon--sun {
            display: block;
        }

        .badge-dot {
            position: absolute;
            top: 9px;
            right: 9px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--fuchsia);
            box-shadow: 0 0 8px var(--fuchsia);
            animation: pulse 2s ease-in-out infinite;
        }

        /* ============================================================
           DROPDOWN DE USUARIO
           ============================================================ */
        .topbar-user {
            position: relative;
        }

        .topbar-user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .topbar-user-btn:hover {
            background: #fff;
            border-color: var(--cyan);
            transform: translateY(-1px);
        }

        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            display: grid;
            place-items: center;
            color: #fff;
            font: 700 13px -apple-system, sans-serif;
            flex-shrink: 0;
        }

        .topbar-avatar.lg {
            width: 44px;
            height: 44px;
            font-size: 15px;
        }

        .topbar-user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.2;
        }

        .topbar-user-name {
            font: 600 13px -apple-system, sans-serif;
            color: var(--ink);
        }

        .topbar-user-role {
            font: 11px -apple-system, sans-serif;
            color: var(--muted);
        }

        .topbar-user-btn .chevron {
            width: 14px;
            height: 14px;
            color: var(--muted);
            transition: transform var(--transition-fast);
        }

        .topbar-user.open .chevron {
            transform: rotate(180deg);
        }

        .topbar-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: min(260px, calc(100vw - 24px));
            padding: 8px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 16px 48px rgba(24, 37, 45, 0.16);
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
            pointer-events: none;
            transition: all var(--transition-base);
            z-index: 100;
        }

        .topbar-user.open .topbar-dropdown {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
        }

        .dropdown-name {
            font: 600 14px -apple-system, sans-serif;
            color: var(--ink);
        }

        .dropdown-email {
            font: 12px -apple-system, sans-serif;
            color: var(--muted);
            margin-top: 2px;
        }

        .dropdown-divider {
            height: 1px;
            background: var(--line);
            margin: 6px 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--ink);
            font: 500 13.5px -apple-system, sans-serif;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-align: left;
        }

        .dropdown-item:hover {
            background: rgba(6, 182, 212, 0.08);
            color: var(--teal);
            transform: translateX(2px);
        }

        .dropdown-item.danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .dropdown-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* ============================================================
           CONTENIDO
           ============================================================ */
        .content {
            position: relative;
            z-index: 1;
            padding: 28px 32px;
            flex: 1;
            overflow-x: hidden;
            width: 100%;
        }

        /* ============================================================
           TARJETAS DE MÉTRICAS
           ============================================================ */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .metric-card {
            position: relative;
            padding: 24px;
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 32px rgba(24, 37, 45, 0.06);
            overflow: hidden;
            transition: all var(--transition-base);
            animation: fadeUp 0.6s ease-out backwards;
        }

        .metric-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .metric-card:nth-child(2) {
            animation-delay: 0.10s;
        }

        .metric-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .metric-card:nth-child(4) {
            animation-delay: 0.20s;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .metric-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.04), rgba(217, 70, 239, 0.04));
            opacity: 0;
            transition: opacity var(--transition-base);
            pointer-events: none;
        }

        .metric-card:hover::before {
            opacity: 1;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(24, 37, 45, 0.10);
            border-color: rgba(6, 182, 212, 0.35);
        }

        .metric-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            color: #fff;
            transition: transform var(--transition-base);
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .metric-card:nth-child(odd) .metric-icon {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .metric-card:nth-child(even) .metric-icon {
            background: linear-gradient(135deg, #d946ef, #a21caf);
        }

        .metric-icon svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .metric-value {
            font: 600 34px -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.03em;
            color: #18252d;
            margin-bottom: 6px;
            transition: color var(--transition-base);
            word-break: break-word;
        }

        .metric-card.updating .metric-value {
            color: #06b6d4;
            opacity: 0.6;
        }

        .metric-label {
            color: #718087;
            font: 600 11px -apple-system, sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* ============================================================
           TARJETA DE BIENVENIDA
           ============================================================ */
        .welcome-card {
            position: relative;
            padding: 32px;
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 32px rgba(24, 37, 45, 0.06);
            margin-bottom: 32px;
            animation: fadeUp 0.6s ease-out;
            overflow: hidden;
        }

        .welcome-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .welcome-eyebrow {
            margin: 0 0 12px;
            color: #087f79;
            font: 700 11px -apple-system, sans-serif;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .welcome-title {
            margin: 0;
            font: 300 clamp(24px, 4vw, 36px) -apple-system, sans-serif;
            letter-spacing: -0.02em;
            color: #18252d;
            line-height: 1.2;
        }

        .welcome-title strong {
            font-weight: 600;
            background: linear-gradient(135deg, #06b6d4, #d946ef);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-text {
            max-width: 560px;
            margin: 12px 0 0;
            color: #718087;
            font: 15px/1.6 -apple-system, sans-serif;
        }

        /* ============================================================
           ESTADO DE SESIÓN
           ============================================================ */
        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #15803d;
            font: 600 12px -apple-system, sans-serif;
            margin-top: 20px;
        }

        .session-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 12px #22c55e;
            animation: pulse 2s ease-in-out infinite;
            flex-shrink: 0;
        }

        /* ============================================================
           TOOLBAR DEL DASHBOARD
           ============================================================ */
        .dashboard-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font: 500 12.5px -apple-system, sans-serif;
            color: #718087;
        }

        .toolbar-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            transition: background var(--transition-base);
            flex-shrink: 0;
        }

        .toolbar-dot.live {
            background: #22c55e;
            box-shadow: 0 0 10px #22c55e;
            animation: pulse 2s ease-in-out infinite;
        }

        .toolbar-dot.syncing {
            background: #06b6d4;
            box-shadow: 0 0 10px #06b6d4;
            animation: pulse 0.8s ease-in-out infinite;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.75);
            color: #18252d;
            font: 600 12.5px -apple-system, sans-serif;
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
        }

        .btn-icon:hover {
            background: #fff;
            border-color: #06b6d4;
            color: #087f79;
            transform: translateY(-1px);
        }

        .btn-icon:active {
            transform: translateY(0) scale(0.97);
        }

        .btn-icon svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .btn-icon.spinning svg {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ============================================================
           SWITCH AUTO-REFRESH
           ============================================================ */
        .auto-refresh-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            font: 500 12.5px -apple-system, sans-serif;
            color: #718087;
        }

        .auto-refresh-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .switch-track {
            position: relative;
            width: 38px;
            height: 20px;
            border-radius: var(--radius-full);
            background: #cbd5e1;
            transition: background var(--transition-base);
            flex-shrink: 0;
        }

        .switch-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            transition: transform var(--transition-base);
        }

        .auto-refresh-toggle input:checked+.switch-track {
            background: linear-gradient(135deg, #06b6d4, #087f79);
        }

        .auto-refresh-toggle input:checked+.switch-track .switch-thumb {
            transform: translateX(18px);
        }

        /* ============================================================
           ALERTAS
           ============================================================ */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            border-radius: var(--radius-md);
            font: 500 13.5px -apple-system, sans-serif;
            margin-bottom: 18px;
            border: 1px solid transparent;
            animation: slideIn 0.25s ease;
            word-break: break-word;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-icon {
            display: grid;
            place-items: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .alert-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .alert-success {
            background: var(--success-bg);
            color: #14532d;
            border-color: var(--success-border);
        }

        .alert-danger {
            background: var(--danger-bg);
            color: #7f1d1d;
            border-color: var(--danger-border);
        }

        .alert-warning {
            background: var(--warning-bg);
            color: #78350f;
            border-color: var(--warning-border);
        }

        .alert-info {
            background: var(--info-bg);
            color: #1e40af;
            border-color: var(--info-border);
        }

        /* ============================================================
           CARDS GENERALES
           ============================================================ */
        .card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 20px rgba(24, 37, 45, 0.06);
            overflow: hidden;
            margin-bottom: 22px;
            transition: box-shadow var(--transition-base);
        }

        .card:hover {
            box-shadow: 0 8px 28px rgba(24, 37, 45, 0.09);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 16px 22px;
            border-bottom: 1px solid var(--line);
        }

        .card-header h2 {
            font: 600 14.5px -apple-system, sans-serif;
            color: var(--ink);
            letter-spacing: -0.01em;
        }

        .card-header .card-meta {
            font: 400 12.5px -apple-system, sans-serif;
            color: var(--muted);
        }

        .card-header .card-meta strong {
            color: var(--ink);
            font-weight: 600;
        }

        .card-body {
            padding: 22px;
        }

        /* ============================================================
           FORMULARIOS
           ============================================================ */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
            gap: 14px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font: 600 11.5px -apple-system, sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font: 13.5px -apple-system, sans-serif;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.7);
            transition: all var(--transition-base);
            outline: none;
            min-height: 42px;
        }

        .form-control:hover {
            border-color: #cbd5e1;
        }

        .form-control:focus {
            border-color: var(--cyan);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.12);
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: var(--muted);
            opacity: 0.7;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23718087' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 38px;
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 90px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-error {
            font: 500 12px -apple-system, sans-serif;
            color: var(--danger);
            margin-top: 2px;
        }

        /* ============================================================
           BOTONES
           ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: 600 13px -apple-system, sans-serif;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
            line-height: 1;
            position: relative;
            overflow: hidden;
            min-height: 40px;
        }

        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.2);
            opacity: 0;
            transition: opacity var(--transition-fast);
            pointer-events: none;
        }

        .btn:hover::after {
            opacity: 1;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--cyan), var(--teal));
            color: #fff;
            box-shadow: 0 4px 14px rgba(6, 182, 212, 0.28);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0891b2, var(--teal-dark));
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.38);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.75);
            color: var(--ink);
            border-color: var(--line);
        }

        .btn-secondary:hover {
            background: #fff;
            border-color: var(--cyan);
            color: var(--teal);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.22);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.32);
            transform: translateY(-1px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 7px 12px;
            font-size: 12px;
            border-radius: var(--radius-sm);
            min-height: 34px;
        }

        .btn-sm svg {
            width: 13px;
            height: 13px;
        }

        .btn:disabled,
        .btn[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
            transform: none;
        }

        /* ============================================================
           TABLA RESPONSIVE
           ============================================================ */
        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font: 13.5px -apple-system, sans-serif;
            min-width: 600px;
        }

        thead th {
            text-align: left;
            padding: 12px 16px;
            background: rgba(241, 245, 249, 0.6);
            color: var(--muted);
            font: 700 10.5px -apple-system, sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
            color: var(--ink);
            transition: background var(--transition-fast);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: background var(--transition-fast);
        }

        tbody tr:hover {
            background: rgba(6, 182, 212, 0.04);
        }

        tbody tr:hover td {
            color: var(--ink);
        }

        tbody code {
            padding: 2px 7px;
            border-radius: 6px;
            background: rgba(6, 182, 212, 0.10);
            color: var(--teal-dark);
            font: 500 12px 'SF Mono', Menlo, monospace;
            word-break: break-all;
        }

        /* ============================================================
           BADGES
           ============================================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font: 600 11px -apple-system, sans-serif;
            text-transform: capitalize;
            white-space: nowrap;
            transition: transform var(--transition-fast);
        }

        .badge:hover {
            transform: scale(1.05);
        }

        .badge-success {
            background: var(--success-bg);
            color: #14532d;
        }

        .badge-danger {
            background: var(--danger-bg);
            color: #7f1d1d;
        }

        .badge-warning {
            background: var(--warning-bg);
            color: #78350f;
        }

        .badge-info {
            background: var(--info-bg);
            color: #1e40af;
        }

        .badge-muted {
            background: rgba(241, 245, 249, 0.9);
            color: #475569;
        }

        /* ============================================================
           ACCIONES EN TABLA
           ============================================================ */
        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* ============================================================
           EMPTY STATE
           ============================================================ */
        .empty-state {
            padding: 48px 20px;
            text-align: center;
            color: var(--muted);
            font: 400 13.5px -apple-system, sans-serif;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .empty-state svg {
            width: 44px;
            height: 44px;
            margin: 0 auto 12px;
            display: block;
            stroke: var(--muted);
            opacity: 0.5;
            fill: none;
            stroke-width: 1.5;
        }

        /* ============================================================
           PAGINACIÓN
           ============================================================ */
        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 14px 22px;
            border-top: 1px solid var(--line);
            background: rgba(248, 250, 252, 0.5);
        }

        .pagination-wrap .info {
            font: 400 12.5px -apple-system, sans-serif;
            color: var(--muted);
        }

        .pagination-wrap .info strong {
            color: var(--ink);
            font-weight: 600;
        }

        .pagination-wrap nav[role="navigation"]>div:first-child {
            display: none;
        }

        .pagination-wrap nav[role="navigation"]>div:last-child {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .pagination-wrap nav[role="navigation"] a,
        .pagination-wrap nav[role="navigation"] span[aria-current="page"]>span,
        .pagination-wrap nav[role="navigation"] span[aria-disabled="true"]>span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            color: var(--ink);
            font: 500 13px -apple-system, sans-serif;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .pagination-wrap nav[role="navigation"] a:hover {
            border-color: var(--cyan);
            color: var(--teal);
            background: #fff;
            transform: translateY(-1px);
        }

        .pagination-wrap nav[role="navigation"] span[aria-current="page"]>span {
            background: linear-gradient(135deg, var(--cyan), var(--teal));
            border-color: transparent;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
        }

        .pagination-wrap nav[role="navigation"] span[aria-disabled="true"]>span {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination-wrap nav[role="navigation"] svg {
            width: 16px;
            height: 16px;
        }

        /* ============================================================
           STATS CARDS
           ============================================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(24, 37, 45, 0.06);
            transition: all var(--transition-base);
            animation: fadeUp 0.5s ease-out backwards;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(24, 37, 45, 0.1);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #fff;
            transition: transform var(--transition-base);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .stat-icon.cyan {
            background: linear-gradient(135deg, var(--cyan), var(--teal));
        }

        .stat-icon.fuchsia {
            background: linear-gradient(135deg, var(--fuchsia), #a21caf);
        }

        .stat-icon.teal {
            background: linear-gradient(135deg, var(--teal), var(--teal-dark));
        }

        .stat-icon.amber {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .stat-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .stat-value {
            font: 700 22px -apple-system, sans-serif;
            color: var(--ink);
            line-height: 1.1;
            letter-spacing: -0.02em;
            word-break: break-word;
        }

        .stat-label {
            font: 500 12px -apple-system, sans-serif;
            color: var(--muted);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ============================================================
           MODALES OPTIMIZADOS Y RESPONSIVE
           ============================================================ */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(24, 37, 45, 0.5);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-base), visibility var(--transition-base);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-backdrop.open {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            width: 100%;
            max-width: 640px;
            max-height: calc(100vh - 40px);
            max-height: calc(100dvh - 40px);
            background: #fff;
            border-radius: var(--radius-xl);
            box-shadow: 0 24px 64px rgba(24, 37, 45, 0.28);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(16px) scale(0.98);
            transition: transform var(--transition-base);
            margin: auto;
        }

        .modal-backdrop.open .modal {
            transform: translateY(0) scale(1);
        }

        .modal>form {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
        }

        .modal-header h3 {
            font: 600 16px -apple-system, sans-serif;
            color: var(--ink);
            letter-spacing: -0.01em;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all var(--transition-fast);
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
            transform: rotate(90deg);
        }

        .modal-close svg {
            width: 18px;
            height: 18px;
        }

        .modal-body {
            padding: 22px 24px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            flex: 1;
            min-height: 0;
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover {
            background: var(--surface-soft);
            color: var(--teal-dark);
        }

        .password-toggle:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 2px;
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .password-toggle .eye-off,
        .password-toggle.visible .eye-on {
            display: none;
        }

        .password-toggle.visible .eye-off {
            display: block;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 24px;
            border-top: 1px solid var(--line);
            background: rgba(248, 250, 252, 0.6);
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .modal-footer .btn {
            flex: 0 1 auto;
        }

        /* Modales más anchos */
        .modal.modal-lg {
            max-width: 900px;
        }

        .modal.modal-sm {
            max-width: 440px;
        }

        /* ============================================================
           UTILIDADES DE MODAL
           ============================================================ */
        .req {
            color: var(--danger);
            font-weight: 700;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 150px), 1fr));
            gap: 8px;
            padding: 12px;
            background: rgba(248, 250, 252, 0.6);
            border: 1px solid var(--line);
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font: 500 13px -apple-system, sans-serif;
            color: var(--ink);
            transition: background var(--transition-fast);
            user-select: none;
        }

        .checkbox-item:hover {
            background: rgba(6, 182, 212, 0.06);
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--cyan);
            cursor: pointer;
            flex-shrink: 0;
        }

        /* ============================================================
           SWITCH ITEM
           ============================================================ */
        .switch-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            cursor: pointer;
            user-select: none;
        }

        .switch-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .switch-item .switch-track {
            width: 40px;
            height: 22px;
        }

        .switch-item .switch-thumb {
            width: 18px;
            height: 18px;
        }

        .switch-item input[type="checkbox"]:checked+.switch-track {
            background: linear-gradient(135deg, var(--cyan), var(--teal));
        }

        .switch-item input[type="checkbox"]:checked+.switch-track .switch-thumb {
            transform: translateX(18px);
        }

        .switch-label {
            font: 500 13.5px -apple-system, sans-serif;
            color: var(--ink);
        }

        body.modal-open {
            overflow: hidden;
        }

        /* ============================================================
           TOASTS RESPONSIVE
           ============================================================ */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 13px 20px;
            border-radius: var(--radius-md);
            font: 600 13.5px -apple-system, sans-serif;
            color: #fff;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateY(20px);
            transition: all var(--transition-base);
            z-index: 3000;
            max-width: min(360px, calc(100vw - 32px));
            pointer-events: none;
            word-break: break-word;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .toast-success {
            background: linear-gradient(135deg, #16a34a, #15803d);
        }

        .toast-error {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .toast-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .toast-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        /* ============================================================
           FEEDBACK DE BÚSQUEDA DE CI
           ============================================================ */
        #ciFeedback.ok {
            color: #16a34a;
        }

        #ciFeedback.error {
            color: #dc2626;
        }

        /* ============================================================
           INPUT READONLY
           ============================================================ */
        input.form-control[readonly] {
            background: rgba(248, 250, 252, 0.9) !important;
            color: var(--muted);
            cursor: not-allowed;
        }

        input.form-control[readonly]:focus {
            border-color: var(--line);
            box-shadow: none;
            background: rgba(248, 250, 252, 0.9) !important;
            transform: none;
        }

        /* ============================================================
           ANIMACIÓN PULSE
           ============================================================ */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(0.85);
            }
        }

        /* ============================================================
           ANIMACIÓN DE ENTRADA EN FILAS
           ============================================================ */
        #tbodyUsuarios tr {
            animation: filaEntra 0.35s ease-out backwards;
        }

        @keyframes filaEntra {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ============================================================
           BARRA DE PROGRESO SUPERIOR
           ============================================================ */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: linear-gradient(90deg, #06b6d4, #d946ef);
            box-shadow: 0 0 12px rgba(6, 182, 212, 0.5);
            transition: width 0.3s ease, opacity 0.3s ease;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
        }

        .loading-bar.active {
            opacity: 1;
            width: 70%;
        }

        .loading-bar.complete {
            width: 100%;
            opacity: 0;
        }

        /* ============================================================
           INDICADOR EN EL HEADER DE LA TABLA
           ============================================================ */
        .card-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tabla-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font: 500 12px -apple-system, sans-serif;
            color: #718087;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .tabla-status.visible {
            opacity: 1;
        }

        .tabla-status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #06b6d4;
            animation: pulse 1s ease-in-out infinite;
        }

        /* ============================================================
           FADE AL CAMBIAR EL TBODY
           ============================================================ */
        #tbodyUsuarios.loading {
            opacity: 0.4;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        /* ============================================================
           BOTÓN ACTUALIZAR CON SPINNER
           ============================================================ */
        .btn-actualizar.spinning svg {
            animation: spin 0.8s linear infinite;
        }

        /* ============================================================
           DARK MODE
           ============================================================ */
        :root[data-theme="dark"] {
            color-scheme: dark;
            --teal: #55c4b2;
            --teal-dark: #83d8c9;
            --cyan: #42b7c2;
            --fuchsia: #c47bb9;
            --ink: #e6f0ed;
            --muted: #a6bbb5;
            --line: #344944;
            --bg: #111a18;
            --surface: #192421;
            --surface-raised: #202d29;
            --surface-soft: #24342f;
            --surface-border: #354a44;
            --sidebar-bg: rgba(25, 36, 33, 0.97);
            --topbar-bg: rgba(25, 36, 33, 0.94);
            --control-bg: #16211e;
            --grid-line: rgba(105, 195, 176, 0.055);
            --success-bg: #153a2c;
            --success-border: #286447;
            --danger-bg: #402627;
            --danger-border: #744044;
            --warning-bg: #40351e;
            --warning-border: #765d2a;
            --info-bg: #203747;
            --info-border: #365e73;
            --glass-bg: rgba(25, 36, 33, 0.94);
            --glass-border: rgba(75, 102, 93, 0.55);
            --glass-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        }

        :root[data-theme="dark"] .sidebar,
        :root[data-theme="dark"] .topbar,
        :root[data-theme="dark"] .topbar-search,
        :root[data-theme="dark"] .topbar-icon-btn,
        :root[data-theme="dark"] .topbar-user-btn,
        :root[data-theme="dark"] .topbar-dropdown,
        :root[data-theme="dark"] .metric-card,
        :root[data-theme="dark"] .welcome-card,
        :root[data-theme="dark"] .card,
        :root[data-theme="dark"] .stat-card,
        :root[data-theme="dark"] .modal,
        :root[data-theme="dark"] .modal-close {
            background-color: var(--surface);
            border-color: var(--surface-border);
            box-shadow: var(--glass-shadow);
        }

        :root[data-theme="dark"] .topbar-dropdown,
        :root[data-theme="dark"] .modal,
        :root[data-theme="dark"] .topbar-search:focus-within,
        :root[data-theme="dark"] .form-control:focus {
            background-color: var(--surface-raised);
        }

        :root[data-theme="dark"] .topbar-mobile-toggle,
        :root[data-theme="dark"] .form-control,
        :root[data-theme="dark"] .btn-secondary,
        :root[data-theme="dark"] .btn-icon,
        :root[data-theme="dark"] .pagination-wrap nav[role="navigation"] a {
            background-color: var(--control-bg);
            border-color: var(--surface-border);
            color: var(--ink);
        }

        :root[data-theme="dark"] .card-header,
        :root[data-theme="dark"] .modal-header,
        :root[data-theme="dark"] .modal-footer,
        :root[data-theme="dark"] .pagination-wrap,
        :root[data-theme="dark"] .checkbox-grid,
        :root[data-theme="dark"] thead th {
            background-color: var(--surface-soft);
        }

        :root[data-theme="dark"] .metric-value,
        :root[data-theme="dark"] .welcome-title {
            color: var(--ink);
        }

        :root[data-theme="dark"] .metric-label,
        :root[data-theme="dark"] .welcome-text,
        :root[data-theme="dark"] .toolbar-info,
        :root[data-theme="dark"] .auto-refresh-toggle,
        :root[data-theme="dark"] .tabla-status {
            color: var(--muted);
        }

        :root[data-theme="dark"] .nav-item.active {
            background: rgba(66, 183, 194, 0.16);
            color: var(--teal-dark);
        }

        :root[data-theme="dark"] tbody tr:hover,
        :root[data-theme="dark"] .checkbox-item:hover,
        :root[data-theme="dark"] .dropdown-item:hover {
            background-color: rgba(66, 183, 194, 0.1);
        }

        :root[data-theme="dark"] .badge-success {
            color: #a7e6c4;
        }

        :root[data-theme="dark"] .badge-danger {
            color: #ffc0c0;
        }

        :root[data-theme="dark"] .badge-warning {
            color: #f4d38d;
        }

        :root[data-theme="dark"] .badge-info {
            color: #addcf0;
        }

        :root[data-theme="dark"] .badge-muted {
            background: var(--surface-soft);
            color: var(--muted);
        }

        :root[data-theme="dark"] .alert-success {
            color: #b5ebc9;
        }

        :root[data-theme="dark"] .alert-danger {
            color: #ffc3c3;
        }

        :root[data-theme="dark"] .alert-warning {
            color: #f2d695;
        }

        :root[data-theme="dark"] .alert-info {
            color: #b6dff1;
        }

        :root[data-theme="dark"] .dropdown-item.danger:hover,
        :root[data-theme="dark"] .logout-btn:hover,
        :root[data-theme="dark"] .modal-close:hover {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: #ffc3c3;
        }

        :root[data-theme="dark"] .form-control::placeholder {
            color: #91a59f;
        }

        :root[data-theme="dark"] .sidebar-user,
        :root[data-theme="dark"] .sidebar-brand,
        :root[data-theme="dark"] .sidebar-footer {
            border-color: var(--line);
        }

        :root[data-theme="dark"] .dropdown-divider {
            background-color: var(--line);
        }

        :root[data-theme="dark"] .btn-secondary:hover,
        :root[data-theme="dark"] .pagination-wrap nav[role="navigation"] a:hover,
        :root[data-theme="dark"] .topbar-icon-btn:hover,
        :root[data-theme="dark"] .topbar-mobile-toggle:hover {
            border-color: var(--cyan);
            color: var(--teal-dark);
        }

        .theme-toggle:focus-visible,
        .topbar-mobile-toggle:focus-visible,
        .topbar-user-btn:focus-visible {
            outline: 3px solid var(--cyan);
            outline-offset: 2px;
        }

        /* ============================================================
           AUTOCOMPLETE DE PACIENTES
           ============================================================ */
        .autocomplete-wrap {
            position: relative;
        }

        .autocomplete-input {
            padding-right: 40px;
        }

        .autocomplete-loading {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid rgba(6, 182, 212, 0.2);
            border-top-color: #06b6d4;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .autocomplete-wrap.loading .autocomplete-loading {
            display: block;
        }

        .autocomplete-clear {
            position: absolute;
            right: 36px;
            top: 50%;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            border: 0;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            display: none;
            place-items: center;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .autocomplete-wrap.has-value .autocomplete-clear {
            display: grid;
        }

        .autocomplete-clear:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .autocomplete-clear svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
        }

        .autocomplete-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            max-height: min(320px, 50vh);
            overflow-y: auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            box-shadow: 0 12px 32px rgba(24, 37, 45, 0.14);
            z-index: 1500;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: all 0.18s ease;
            padding: 6px;
            -webkit-overflow-scrolling: touch;
        }

        .autocomplete-dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .autocomplete-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background 0.12s ease;
        }

        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: rgba(6, 182, 212, 0.08);
        }

        .autocomplete-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4, #d946ef);
            display: grid;
            place-items: center;
            color: #fff;
            font: 700 13px -apple-system, sans-serif;
            flex-shrink: 0;
        }

        .autocomplete-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .autocomplete-name {
            font: 600 13.5px -apple-system, sans-serif;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .autocomplete-meta {
            font: 400 11.5px -apple-system, sans-serif;
            color: var(--muted);
            margin-top: 2px;
        }

        .autocomplete-empty {
            padding: 20px;
            text-align: center;
            color: var(--muted);
            font: 400 13px -apple-system, sans-serif;
        }

        .autocomplete-empty svg {
            width: 32px;
            height: 32px;
            display: block;
            margin: 0 auto 8px;
            stroke: var(--muted);
            opacity: 0.5;
        }

        .paciente-seleccionado {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.25);
            border-radius: 10px;
            margin-top: 8px;
            animation: fadeIn 0.2s ease;
            flex-wrap: wrap;
        }

        .paciente-seleccionado .avatar {
            width: 40px;
            height: 40px;
            font-size: 14px;
        }

        .paciente-seleccionado .info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .paciente-seleccionado .nombre {
            font: 600 13.5px -apple-system, sans-serif;
            color: var(--ink);
        }

        .paciente-seleccionado .detalles {
            font: 400 12px -apple-system, sans-serif;
            color: var(--muted);
            margin-top: 2px;
        }

        .paciente-seleccionado .quitar {
            width: 28px;
            height: 28px;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all 0.15s ease;
        }

        .paciente-seleccionado .quitar:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .paciente-seleccionado .quitar svg {
            width: 14px;
            height: 14px;
        }

        /* ============================================================
           RESPONSIVE MEJORADO
           ============================================================ */

        /* Tablets grandes y pantallas medianas */
        @media (max-width: 1200px) {
            .content {
                padding: 24px 24px;
            }

            .topbar {
                padding: 12px 24px;
            }
        }

        /* Tablets */
        @media (max-width: 1024px) {
            .app {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 200;
                transform: translateX(-100%);
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
                width: 280px;
                max-width: 85vw;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .topbar-mobile-toggle {
                display: grid;
            }

            .topbar {
                padding: 12px 20px;
            }

            .content {
                padding: 22px 20px;
            }
        }

        /* Móviles grandes */
        @media (max-width: 768px) {
            .topbar {
                padding: 10px 16px;
                gap: 10px;
                min-height: 60px;
            }

            .topbar-search {
                display: none;
            }

            .topbar-user-info {
                display: none;
            }

            .topbar-user-btn {
                padding: 6px;
                gap: 0;
            }

            .topbar-user-btn .chevron {
                display: none;
            }

            .content {
                padding: 18px 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .metrics-grid,
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr));
                gap: 12px;
            }

            .metric-card {
                padding: 18px;
            }

            .metric-value {
                font-size: 26px;
            }

            .welcome-card {
                padding: 22px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .welcome-text {
                font-size: 14px;
            }

            .card-header,
            .card-body {
                padding: 14px 16px;
            }

            .pagination-wrap {
                padding: 12px 16px;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .pagination-wrap nav[role="navigation"]>div:last-child {
                justify-content: center;
            }

            /* Modales a pantalla completa en móviles */
            .modal-backdrop {
                padding: 0;
                align-items: flex-end;
            }

            .modal {
                max-width: 100%;
                max-height: 95vh;
                max-height: 95dvh;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                transform: translateY(100%);
            }

            .modal-backdrop.open .modal {
                transform: translateY(0);
            }

            .modal-header {
                padding: 16px 18px;
            }

            .modal-body {
                padding: 18px;
            }

            .modal-footer {
                padding: 14px 18px;
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .modal-footer .btn {
                width: 100%;
            }

            /* Toasts en móvil */
            .toast {
                bottom: 16px;
                right: 16px;
                left: 16px;
                max-width: none;
                text-align: center;
            }

            /* Tablas */
            .table-wrap {
                margin: 0 -16px;
                padding: 0 16px;
                border-radius: 0;
            }

            /* Botones de acción en tabla */
            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .actions .btn {
                width: 100%;
            }

            /* Dropdown de usuario */
            .topbar-dropdown {
                right: -8px;
                min-width: 220px;
            }
        }

        /* Móviles pequeños */
        @media (max-width: 480px) {
            .topbar-heading {
                font-size: 15px;
            }

            .topbar-sub {
                display: none;
            }

            .content {
                padding: 14px 12px;
            }

            .metric-card {
                padding: 16px;
            }

            .metric-value {
                font-size: 22px;
            }

            .metric-label {
                font-size: 10px;
            }

            .welcome-card {
                padding: 18px;
            }

            .welcome-title {
                font-size: 20px;
            }

            .welcome-eyebrow {
                font-size: 10px;
            }

            .stat-card {
                padding: 14px;
                gap: 12px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
            }

            .stat-value {
                font-size: 18px;
            }

            .stat-label {
                font-size: 10px;
            }

            .btn {
                padding: 9px 14px;
                font-size: 12.5px;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 11.5px;
            }

            .card-header h2 {
                font-size: 13.5px;
            }

            .card-header {
                padding: 12px 14px;
            }

            .card-body {
                padding: 14px;
            }

            .form-control {
                padding: 9px 12px;
                font-size: 13px;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            /* Paginación más compacta */
            .pagination-wrap nav[role="navigation"] a,
            .pagination-wrap nav[role="navigation"] span[aria-current="page"]>span,
            .pagination-wrap nav[role="navigation"] span[aria-disabled="true"]>span {
                min-width: 30px;
                height: 30px;
                font-size: 12px;
                padding: 0 8px;
            }
        }

        /* Móviles muy pequeños */
        @media (max-width: 360px) {
            .topbar-heading {
                font-size: 14px;
            }

            .metric-value {
                font-size: 20px;
            }

            .welcome-title {
                font-size: 18px;
            }

            .stat-value {
                font-size: 16px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        /* Landscape en móviles */
        @media (max-height: 500px) and (orientation: landscape) {
            .modal {
                max-height: 100vh;
                max-height: 100dvh;
                border-radius: 0;
            }

            .modal-backdrop {
                padding: 0;
                align-items: stretch;
            }

            .modal-body {
                padding: 12px 18px;
            }

            .modal-header,
            .modal-footer {
                padding: 10px 18px;
            }
        }

        /* ============================================================
           ACCESIBILIDAD
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }

        /* ============================================================
           SCROLLBAR PERSONALIZADA (WEBKIT)
           ============================================================ */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.4), rgba(217, 70, 239, 0.4));
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.7), rgba(217, 70, 239, 0.7));
            background-clip: content-box;
        }

        /* ============================================================
           SELECCIÓN DE TEXTO
           ============================================================ */
        ::selection {
            background: rgba(6, 182, 212, 0.25);
            color: var(--teal-dark);
        }

        /* ============================================================
           UTILIDADES
           ============================================================ */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .text-center {
            text-align: center;
        }

        .w-full {
            width: 100%;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="app">
        @include('layouts.partials.sidebar')

        <div class="main-wrapper">
            @include('layouts.partials.topbar')

            <main class="content">
                {{-- Mensajes flash --}}
                @if (session('success'))
                <div class="alert alert-success" role="alert">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </span>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if (session('warning'))
                <div class="alert alert-warning" role="alert">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </span>
                    <span>{{ session('warning') }}</span>
                </div>
                @endif

                @if (session('info'))
                <div class="alert alert-info" role="alert">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                    </span>
                    <span>{{ session('info') }}</span>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('layouts.partials.scripts')
    @stack('scripts')
</body>

</html>