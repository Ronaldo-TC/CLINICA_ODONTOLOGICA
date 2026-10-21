<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Clínica Odontológica UPEA</title>

    {{-- ============================================================
         PERMISOS Y ROLES DEL USUARIO → disponibles en JavaScript
         ============================================================ --}}
    @auth
    <meta name="user-permissions" content="{{ auth()->user()->getAllPermissions()->pluck('name')->implode(',') }}">
    <meta name="user-roles" content="{{ auth()->user()->getRoleNames()->implode(',') }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-name" content="{{ auth()->user()->nombre_completo }}">
    @endauth

    {{-- ============================================================
         TEMA (claro/oscuro) — se aplica antes de pintar para evitar flash
         ============================================================ --}}
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

    {{-- ============================================================
         HELPERS GLOBALES JS: window.can() / window.hasRole()
         ============================================================ --}}
    <script>
        (() => {
            const permsMeta = document.querySelector('meta[name="user-permissions"]');
            const rolesMeta = document.querySelector('meta[name="user-roles"]');

            window.userPermissions = (permsMeta?.content || '').split(',').map(s => s.trim()).filter(Boolean);
            window.userRoles = (rolesMeta?.content || '').split(',').map(s => s.trim()).filter(Boolean);

            /** ¿El usuario tiene el permiso?  ej: can('pacientes.crear') */
            window.can = (perm) => window.userPermissions.includes(perm);

            /** ¿El usuario tiene el rol?  ej: hasRole('admin') */
            window.hasRole = (role) => window.userRoles.includes(role);

            /** ¿Tiene al menos uno de estos roles?  ej: hasAnyRole(['admin','subadmin']) */
            window.hasAnyRole = (roles) => roles.some(r => window.userRoles.includes(r));

            /** ¿Tiene al menos uno de estos permisos? */
            window.canAny = (perms) => perms.some(p => window.userPermissions.includes(p));
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
            --bg: #f2f6f6;
            --surface: #ffffff;
            --surface-raised: #ffffff;
            --surface-soft: #edf3f1;
            --surface-border: #dce6e2;
            --sidebar-bg: #ffffff;
            --topbar-bg: #ffffff;
            --control-bg: #ffffff;

            --sidebar-w: 260px;

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

            --glass-bg: #ffffff;
            --glass-border: #ffffff;
            --glass-shadow: 0 4px 16px rgba(24, 37, 45, 0.06);
            --transition-fast: 0.18s cubic-bezier(0.22, 1, 0.36, 1);
            --transition-base: 0.3s cubic-bezier(0.22, 1, 0.36, 1);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 999px;

            /* Extras */
            --focus-ring: 0 0 0 3px rgba(8, 127, 121, 0.18);
            --shadow-menu: 0 18px 44px rgba(24, 37, 45, 0.16);
            --shadow-soft: 0 3px 14px rgba(24, 37, 45, 0.05);
            --shadow-card: 0 2px 4px rgba(24, 37, 45, 0.02), 0 8px 28px rgba(24, 37, 45, 0.04);
            --shadow-card-hover: 0 6px 12px rgba(24, 37, 45, 0.04), 0 16px 36px rgba(24, 37, 45, 0.08);
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

        body.no-scroll,
        body.modal-open,
        body.palette-open,
        body.drawer-open,
        body.has-modal {
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
            grid-template-columns: minmax(0, var(--sidebar-w)) minmax(0, 1fr);
            min-height: 100vh;
            min-height: 100dvh;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        /* ============================================================
           KBD
           ============================================================ */
        .kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border: 1px solid var(--line);
            border-bottom-width: 2px;
            border-radius: 5px;
            background: var(--surface);
            color: var(--muted);
            font: 600 10px/1 'SF Mono', ui-monospace, Menlo, monospace;
            letter-spacing: 0.02em;
        }

        /* ============================================================
           SIDEBAR
           ============================================================ */
        .sidebar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            overflow-x: clip;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(243, 248, 247, 0.94));
            border-right: 1px solid var(--line);
            box-shadow: inset -1px 0 0 rgba(24, 37, 45, 0.02), 4px 0 28px rgba(20, 30, 30, 0.04);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            -webkit-overflow-scrolling: touch;
        }

        .sidebar-brand {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            padding: 18px 16px;
            border-bottom: 1px solid var(--line);
        }

        .brand-logo {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--cyan), var(--teal) 55%, var(--fuchsia));
            color: #fff;
            box-shadow: 0 6px 16px rgba(8, 127, 121, 0.28);
        }

        .brand-logo svg {
            width: 23px;
            height: 23px;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.25;
        }

        .brand-title {
            font: 700 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            letter-spacing: -0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .brand-sub {
            font: 600 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }

        .sidebar-close {
            display: none;
            place-items: center;
            width: 34px;
            height: 34px;
            margin-left: auto;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: color var(--transition-fast), border-color var(--transition-fast), background var(--transition-fast);
        }

        .sidebar-close svg {
            width: 16px;
            height: 16px;
        }

        .sidebar-close:hover {
            background: var(--surface-soft);
            border-color: var(--cyan);
            color: var(--teal);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, var(--surface-soft), transparent);
        }

        .user-avatar-wrap,
        .avatar-chip {
            position: relative;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .user-avatar-wrap>*,
        .avatar-chip>* {
            grid-area: 1 / 1;
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            color: #fff;
            font: 700 14px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            letter-spacing: 0.02em;
        }

        .avatar-md {
            width: 38px;
            height: 38px;
            font-size: 13px;
        }

        img.avatar,
        img.topbar-avatar {
            object-fit: cover;
            position: relative;
            z-index: 1;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            flex: 1;
        }

        .user-name {
            font: 600 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            font: 600 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .user-role>span:last-child {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .role-dot {
            width: 7px;
            height: 7px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--teal);
            box-shadow: 0 0 0 3px rgba(8, 127, 121, 0.14);
        }

        .role-dot--cyan {
            background: var(--cyan);
            box-shadow: 0 0 0 3px rgba(7, 143, 160, 0.16);
        }

        .role-dot--fuchsia {
            background: var(--fuchsia);
            box-shadow: 0 0 0 3px rgba(179, 68, 158, 0.16);
        }

        .role-dot--amber {
            background: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.18);
        }

        .user-quick {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            border-radius: 9px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--muted);
            transition: color var(--transition-fast), border-color var(--transition-fast);
        }

        .user-quick:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .user-quick svg {
            width: 15px;
            height: 15px;
        }

        .sidebar-tools {
            flex-shrink: 0;
            padding: 12px 16px 0;
        }

        .sidebar-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: var(--surface-soft);
            transition: border-color var(--transition-fast), background var(--transition-fast), box-shadow var(--transition-fast);
        }

        .sidebar-filter:focus-within {
            border-color: var(--cyan);
            background: var(--surface);
            box-shadow: var(--focus-ring);
        }

        .sidebar-filter>svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            color: var(--muted);
        }

        .sidebar-filter input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--ink);
            font: 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .sidebar-filter input::placeholder {
            color: var(--muted);
            opacity: 0.85;
        }

        .sidebar-filter input::-webkit-search-cancel-button {
            display: none;
        }

        .sidebar-filter-clear {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }

        .sidebar-filter-clear:hover {
            background: var(--surface-soft);
            color: var(--danger);
        }

        .sidebar-filter-clear svg {
            width: 13px;
            height: 13px;
        }

        .sidebar-hint {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
            font: 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .sidebar-nav {
            display: flex;
            flex: 1;
            min-height: 0;
            flex-direction: column;
            gap: 2px;
            padding: 12px 12px 16px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar-nav [hidden],
        .sidebar-tools [hidden] {
            display: none !important;
        }

        .nav-label {
            padding: 14px 12px 6px;
            font: 700 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.13em;
            opacity: 0.85;
        }

        .nav-item {
            position: relative;
            display: flex;
            flex-shrink: 0;
            min-height: 40px;
            align-items: center;
            gap: 11px;
            padding: 9px 11px;
            border: 0;
            border-radius: 11px;
            background: transparent;
            color: var(--ink);
            font: 500 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            text-decoration: none;
            text-align: left;
            white-space: nowrap;
            cursor: pointer;
            transition: background var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
        }

        .nav-item:hover {
            background: var(--surface-soft);
            color: var(--teal);
        }

        .nav-item:focus-visible {
            outline: 0;
            box-shadow: var(--focus-ring);
        }

        .nav-icon {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            color: currentColor;
            opacity: 0.92;
        }

        .nav-icon svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nav-text {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            line-height: 1.4;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(7, 143, 160, 0.14), rgba(7, 143, 160, 0.02));
            color: var(--teal-dark);
            font-weight: 600;
        }

        .nav-item.active .nav-icon {
            opacity: 1;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 3px;
            height: 60%;
            min-height: 18px;
            transform: translateY(-50%);
            border-radius: 999px;
            background: linear-gradient(180deg, var(--cyan), var(--teal));
        }

        .nav-group.is-active>.nav-item-toggle {
            color: var(--teal-dark);
            font-weight: 600;
        }

        .nav-item.disabled {
            opacity: 0.42;
            cursor: not-allowed;
            pointer-events: none;
        }

        .nav-empty {
            padding: 12px 14px;
            font: 400 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-style: italic;
            color: var(--muted);
        }

        .nav-empty--filter {
            padding: 18px 14px;
            text-align: center;
            font-style: normal;
        }

        .nav-group {
            display: flex;
            flex-shrink: 0;
            flex-direction: column;
        }

        .nav-item-toggle {
            width: 100%;
            background: transparent;
        }

        .nav-caret {
            display: grid;
            place-items: center;
            width: 16px;
            height: 16px;
            margin-left: auto;
            flex-shrink: 0;
            color: var(--muted);
            transition: transform var(--transition-fast), color var(--transition-fast);
        }

        .nav-caret svg {
            width: 13px;
            height: 13px;
        }

        .nav-group.is-open>.nav-item-toggle .nav-caret {
            transform: rotate(180deg);
            color: currentColor;
        }

        .nav-children {
            display: none;
            flex-direction: column;
            gap: 2px;
            margin: 2px 0 4px 17px;
            padding-left: 10px;
            border-left: 1px solid var(--line);
        }

        .nav-group.is-open>.nav-children {
            display: flex;
            animation: navReveal 0.18s ease both;
        }

        @keyframes navReveal {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-item-child {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 12.5px;
            color: var(--muted);
        }

        .nav-item-child .nav-icon {
            width: 18px;
            height: 18px;
        }

        .nav-item-child .nav-icon svg {
            width: 15px;
            height: 15px;
        }

        .nav-item-child.active {
            color: var(--teal-dark);
            font-weight: 600;
        }

        .nav-item-child.active::before {
            display: none;
        }

        .nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            margin-left: auto;
            padding: 0 6px;
            border-radius: var(--radius-full);
            background: var(--fuchsia);
            color: #fff;
            font: 700 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .sidebar-footer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-shrink: 0;
            padding: 12px 16px 14px;
            border-top: 1px solid var(--line);
        }

        .sidebar-shortcut {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface-soft);
            color: var(--muted);
            font: 500 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: color var(--transition-fast), border-color var(--transition-fast);
        }

        .sidebar-shortcut:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .shortcut-icon {
            display: grid;
            place-items: center;
            width: 16px;
            height: 16px;
        }

        .shortcut-icon svg {
            width: 15px;
            height: 15px;
        }

        .shortcut-text {
            flex: 1;
            text-align: left;
        }

        .sidebar-logout {
            margin: 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            font: 600 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: background var(--transition-fast), color var(--transition-fast), border-color var(--transition-fast);
        }

        .logout-btn svg {
            width: 16px;
            height: 16px;
        }

        .logout-btn:hover {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger);
        }

        .sidebar-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font: 500 10.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .meta-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.16);
        }

        /* ============================================================
           TOPBAR
           ============================================================ */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 72px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.82);
            border-bottom: 1px solid rgba(13, 31, 31, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 28px rgba(15, 23, 23, 0.03);
        }

        .topbar-mobile-toggle {
            display: none;
            place-items: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
            transition: color var(--transition-fast), border-color var(--transition-fast), background var(--transition-fast);
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
            display: flex;
            flex-direction: column;
            gap: 1px;
            flex: 1;
            min-width: 0;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            margin-bottom: 1px;
            font: 600 10.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .topbar-breadcrumb a {
            color: var(--muted);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .topbar-breadcrumb a:hover {
            color: var(--teal);
        }

        .topbar-breadcrumb>span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .topbar-breadcrumb .crumb-sep {
            opacity: 0.5;
        }

        .topbar-breadcrumb .crumb-current {
            color: var(--teal);
        }

        .topbar-heading {
            font: 600 18px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-sub {
            font: 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .topbar-search-trigger {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 250px;
            padding: 8px 10px 8px 13px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface-soft);
            color: var(--muted);
            font: 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: border-color var(--transition-fast), color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast);
        }

        .topbar-search-trigger:hover {
            border-color: var(--cyan);
            background: var(--surface);
            box-shadow: var(--focus-ring);
        }

        .topbar-search-trigger>svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .search-trigger-text {
            flex: 1;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-trigger-keys {
            display: inline-flex;
            gap: 3px;
            flex-shrink: 0;
        }

        .topbar-icon-btn {
            position: relative;
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
            transition: color var(--transition-fast), border-color var(--transition-fast), background var(--transition-fast);
        }

        .topbar-icon-btn:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .topbar-icon-btn:focus-visible {
            outline: 0;
            box-shadow: var(--focus-ring);
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
            top: 8px;
            right: 8px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--fuchsia);
            box-shadow: 0 0 0 2px var(--surface);
            animation: badgePulse 2.4s ease-in-out infinite;
        }

        @keyframes badgePulse {

            0%,
            100% {
                box-shadow: 0 0 0 2px var(--surface);
            }

            50% {
                box-shadow: 0 0 0 4px rgba(179, 68, 158, 0.22);
            }
        }

        .topbar-menu {
            position: relative;
        }

        .topbar-user-btn {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 4px 11px 4px 5px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            cursor: pointer;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        }

        .topbar-user-btn:hover {
            border-color: var(--cyan);
            box-shadow: var(--shadow-soft);
        }

        .topbar-user-btn:focus-visible {
            outline: 0;
            box-shadow: var(--focus-ring);
        }

        .topbar-avatar {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            color: #fff;
            font: 700 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .topbar-avatar.lg {
            width: 46px;
            height: 46px;
            font-size: 15px;
        }

        .topbar-user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            max-width: 170px;
            line-height: 1.2;
        }

        .topbar-user-name {
            max-width: 100%;
            font: 600 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-user-role {
            max-width: 100%;
            font: 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-user-btn .chevron {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            color: var(--muted);
            transition: transform var(--transition-fast);
        }

        .topbar-menu [data-dropdown-trigger][aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .topbar-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 120;
            min-width: min(280px, calc(100vw - 24px));
            padding: 8px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: var(--shadow-menu);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px) scale(0.98);
            transform-origin: top right;
            pointer-events: none;
            transition: opacity var(--transition-base), transform var(--transition-base), visibility var(--transition-base);
        }

        .topbar-dropdown.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .dropdown-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 12px 4px;
        }

        .dropdown-head-title {
            font: 700 10.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.11em;
        }

        .dropdown-link {
            border: 0;
            background: transparent;
            color: var(--teal);
            font: 600 11.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            cursor: pointer;
        }

        .dropdown-link:hover {
            text-decoration: underline;
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
        }

        .dropdown-identity {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 1px;
        }

        .dropdown-name {
            font: 600 14px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-email {
            font: 12px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-role {
            margin-top: 4px;
            font: 600 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--teal);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .dropdown-divider {
            height: 1px;
            margin: 6px 0;
            background: var(--line);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--ink);
            font: 500 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
            transition: background var(--transition-fast), color var(--transition-fast);
        }

        .dropdown-item:hover {
            background: var(--surface-soft);
            color: var(--teal);
        }

        .dropdown-item.danger:hover {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .dropdown-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .dropdown-empty {
            padding: 22px 16px 18px;
            text-align: center;
        }

        .dropdown-empty-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: var(--surface-soft);
            color: var(--muted);
        }

        .dropdown-empty-icon svg {
            width: 20px;
            height: 20px;
        }

        .dropdown-empty-title {
            font: 600 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        .dropdown-empty-text {
            margin-top: 3px;
            font: 12px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .shortcut-list {
            list-style: none;
            padding: 4px 6px 6px;
        }

        .shortcut-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 7px 6px;
            font: 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        .shortcut-list li+li {
            border-top: 1px dashed var(--line);
        }

        .shortcut-keys {
            display: inline-flex;
            gap: 3px;
        }

        /* ============================================================
           BUSCADOR GLOBAL (command palette)
           ============================================================ */
        .nav-palette {
            position: fixed;
            inset: 0;
            z-index: 1200;
        }

        .nav-palette[hidden] {
            display: none !important;
        }

        .nav-palette-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 23, 0.5);
            animation: fadeIn 0.16s ease both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .nav-palette-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            width: min(620px, calc(100vw - 32px));
            max-height: min(70vh, 620px);
            margin: 8vh auto 0;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            background: var(--surface);
            box-shadow: 0 30px 80px rgba(15, 23, 23, 0.35);
            animation: paletteIn 0.2s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes paletteIn {
            from {
                opacity: 0;
                transform: translateY(-14px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .nav-palette-field {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 18px;
            border-bottom: 1px solid var(--line);
        }

        .nav-palette-field>svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            color: var(--teal);
        }

        .nav-palette-field input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--ink);
            font: 500 15.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .nav-palette-field input::placeholder {
            color: var(--muted);
        }

        .nav-palette-results {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 8px;
            -webkit-overflow-scrolling: touch;
        }

        .palette-section {
            padding: 12px 12px 6px;
            font: 700 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .palette-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 10px 12px;
            border: 0;
            border-radius: 11px;
            background: transparent;
            color: var(--ink);
            font: 500 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            text-align: left;
            cursor: pointer;
        }

        .palette-item.is-cursor {
            background: var(--surface-soft);
            color: var(--teal-dark);
        }

        .palette-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(7, 143, 160, 0.16), rgba(8, 127, 121, 0.06));
            color: var(--teal);
        }

        .palette-icon svg {
            width: 17px;
            height: 17px;
        }

        .palette-text {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .palette-crumb {
            flex-shrink: 0;
            font: 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .palette-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: var(--radius-full);
            background: var(--fuchsia);
            color: #fff;
            font: 700 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .palette-open {
            flex-shrink: 0;
            color: var(--muted);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .palette-item.is-cursor .palette-open {
            opacity: 1;
        }

        .palette-empty {
            padding: 38px 20px;
            text-align: center;
        }

        .palette-empty-title {
            font: 600 14px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        .palette-empty-text {
            max-width: 320px;
            margin: 6px auto 0;
            font: 12.5px/1.6 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .nav-palette-hint {
            display: flex;
            justify-content: center;
            gap: 22px;
            padding: 10px 16px;
            border-top: 1px solid var(--line);
            background: var(--surface-soft);
            font: 11.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .nav-palette-hint>span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ============================================================
           NOTIFICACIONES · campana del topbar
           ============================================================ */
        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: var(--radius-full);
            background: var(--fuchsia);
            color: #fff;
            font: 700 10px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-shadow: 0 0 0 2px var(--surface);
        }

        .notif-lista {
            max-height: min(380px, 55vh);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .notif-cargando {
            padding: 18px 14px;
            text-align: center;
            font: 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            text-align: left;
            cursor: pointer;
            transition: background var(--transition-fast);
        }

        .notif-item:hover {
            background: var(--surface-soft);
        }

        .notif-item.is-nueva {
            background: rgba(7, 143, 160, 0.07);
        }

        .notif-icono {
            width: 9px;
            height: 9px;
            margin-top: 6px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--info);
        }

        .notif-icono.badge-danger {
            background: var(--danger);
        }

        .notif-icono.badge-warning {
            background: var(--warning);
        }

        .notif-icono.badge-success {
            background: var(--success);
        }

        .notif-texto {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .notif-titulo {
            font: 600 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        .notif-mensaje {
            font: 11.5px/1.4 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .notif-fecha {
            font: 10.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            opacity: 0.85;
        }

        tr.notificacion-no-leida td {
            background: rgba(7, 143, 160, 0.06);
        }

        /* ============================================================
           ARCHIVOS ADJUNTOS
           ============================================================ */
        .archivos-lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 210px), 1fr));
            gap: 14px;
        }

        .archivo-item {
            margin: 0;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .archivo-preview {
            height: 170px;
            background: var(--surface-soft);
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .archivo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .archivo-preview embed {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .archivo-icono {
            font: 700 12px 'SF Mono', ui-monospace, Menlo, monospace;
            color: var(--muted);
            letter-spacing: 0.1em;
        }

        .archivo-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px 12px 12px;
            border-top: 1px solid var(--line);
        }

        .archivo-nombre {
            font: 600 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--teal);
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .archivo-nombre:hover {
            text-decoration: underline;
        }

        .archivo-detalle {
            font: 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .archivo-meta .btn {
            margin-top: 4px;
            align-self: flex-start;
        }

        /* ============================================================
           FIRMA DIGITAL
           ============================================================ */
        .firma-pad {
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface-soft);
        }

        .firma-lienzo {
            position: relative;
            width: 100%;
            border: 1px dashed var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            overflow: hidden;
        }

        .firma-lienzo canvas {
            display: block;
            width: 100%;
            height: 180px;
            touch-action: none;
            cursor: crosshair;
        }

        .firma-imagen {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
            background: var(--surface);
        }

        .firma-guia {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            margin: 0;
            font: 500 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            pointer-events: none;
        }

        .firma-acciones {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
        }

        .firma-estado {
            font: 600 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .firma-firmada .firma-estado {
            color: var(--success);
        }

        /* ============================================================
           BACKDROP DEL DRAWER (móvil)
           ============================================================ */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 150;
            background: rgba(15, 23, 23, 0.48);
            opacity: 0;
            animation: fadeIn 0.16s ease forwards;
        }

        .sidebar-backdrop[hidden] {
            display: none !important;
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

        .system-footer {
            flex-shrink: 0;
            padding: 14px 32px 18px;
            border-top: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.5);
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
            text-align: center;
            user-select: none;
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
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: 0 2px 8px rgba(24, 37, 45, 0.04);
        }

        .metric-card:hover {
            box-shadow: 0 4px 16px rgba(24, 37, 45, 0.08);
            border-color: var(--cyan);
        }

        .metric-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            color: #fff;
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
            color: var(--ink);
            margin-bottom: 6px;
            word-break: break-word;
        }

        .metric-card.updating .metric-value {
            color: #06b6d4;
            opacity: 0.6;
        }

        .metric-label {
            color: var(--muted);
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
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: 0 2px 8px rgba(24, 37, 45, 0.04);
            margin-bottom: 32px;
        }

        .welcome-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .welcome-eyebrow {
            margin: 0 0 12px;
            color: var(--teal);
            font: 700 11px -apple-system, sans-serif;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .welcome-title {
            margin: 0;
            font: 300 clamp(24px, 4vw, 36px) -apple-system, sans-serif;
            letter-spacing: -0.02em;
            color: var(--ink);
            line-height: 1.2;
        }

        .welcome-title strong {
            font-weight: 600;
            color: var(--teal);
        }

        .welcome-text {
            max-width: 560px;
            margin: 12px 0 0;
            color: var(--muted);
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
            padding: 16px 20px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font: 500 12.5px -apple-system, sans-serif;
            color: var(--muted);
        }

        .toolbar-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            flex-shrink: 0;
        }

        .toolbar-dot.live {
            background: #22c55e;
        }

        .toolbar-dot.syncing {
            background: #06b6d4;
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
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
            font: 600 12.5px -apple-system, sans-serif;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-icon:hover {
            border-color: var(--cyan);
            color: var(--teal);
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
            color: var(--muted);
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
            flex-shrink: 0;
            transition: background var(--transition-fast);
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
            transition: transform var(--transition-fast);
        }

        .auto-refresh-toggle input:checked+.switch-track {
            background: var(--teal);
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
            word-break: break-word;
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
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            box-shadow: 0 2px 8px rgba(24, 37, 45, 0.04);
            overflow: hidden;
            margin-bottom: 22px;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(24, 37, 45, 0.08);
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
            gap: 18px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font: 600 12.5px -apple-system, sans-serif;
            color: var(--ink);
            letter-spacing: 0.01em;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font: 13.5px -apple-system, sans-serif;
            color: var(--ink);
            background: var(--surface);
            outline: none;
            min-height: 44px;
        }

        .form-control:hover {
            border-color: #cbd5e1;
        }

        .form-control:focus {
            border-color: var(--cyan);
            background: var(--surface);
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
            border-radius: 12px;
            font: 600 13px -apple-system, sans-serif;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            line-height: 1;
            min-height: 40px;
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
            background: var(--teal);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--teal-dark);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--ink);
            border-color: var(--line);
        }

        .btn-secondary:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-warning {
            background: var(--warning);
            color: #fff;
        }

        .btn-warning:hover {
            background: #b45309;
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
            background: var(--surface-soft);
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
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--surface-soft);
        }

        tbody code {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 6px;
            background: var(--surface-soft);
            color: var(--teal-dark);
            font: 500 1em 'SF Mono', Menlo, monospace;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
        }

        .table-value {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            font-variant-numeric: tabular-nums;
        }

        tbody code.table-code-wrap {
            display: inline;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        /* ============================================================
           BADGES
           ============================================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font: 600 11px -apple-system, sans-serif;
            text-transform: capitalize;
            white-space: nowrap;
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
            background: var(--surface-soft);
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
            background: var(--surface-soft);
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
            background: var(--surface);
            color: var(--ink);
            font: 500 13px -apple-system, sans-serif;
            text-decoration: none;
        }

        .pagination-wrap nav[role="navigation"] a:hover {
            border-color: var(--cyan);
            color: var(--teal);
        }

        .pagination-wrap nav[role="navigation"] span[aria-current="page"]>span {
            background: var(--teal);
            border-color: var(--teal);
            color: #fff;
            font-weight: 600;
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
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(24, 37, 45, 0.04);
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(24, 37, 45, 0.08);
            border-color: var(--cyan);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #fff;
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
           MODALES (base — usa .modal-backdrop .modal)
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
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: 0 24px 64px rgba(24, 37, 45, 0.28);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(16px);
            transition: transform var(--transition-base);
            margin: auto;
        }

        .modal-backdrop.open .modal {
            transform: translateY(0);
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
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger);
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
            background: var(--surface-soft);
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .modal-footer .btn {
            flex: 0 1 auto;
        }

        .modal.modal-lg {
            max-width: 900px;
        }

        .modal.modal-sm {
            max-width: 440px;
        }

        .req {
            color: var(--danger);
            font-weight: 700;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 150px), 1fr));
            gap: 8px;
            padding: 12px;
            background: var(--surface-soft);
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
            user-select: none;
        }

        .checkbox-item:hover {
            background: var(--surface);
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--cyan);
            cursor: pointer;
            flex-shrink: 0;
        }

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
            background: var(--teal);
        }

        .switch-item input[type="checkbox"]:checked+.switch-track .switch-thumb {
            transform: translateX(18px);
        }

        .switch-label {
            font: 500 13.5px -apple-system, sans-serif;
            color: var(--ink);
        }

        /* ============================================================
           TOASTS
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
            background: var(--success);
        }

        .toast-error {
            background: var(--danger);
        }

        .toast-warning {
            background: var(--warning);
        }

        .toast-info {
            background: #0891b2;
        }

        /* ============================================================
           FEEDBACK DE BÚSQUEDA DE CI
           ============================================================ */
        #ciFeedback.ok {
            color: var(--success);
        }

        #ciFeedback.error {
            color: var(--danger);
        }

        /* ============================================================
           INPUT READONLY
           ============================================================ */
        input.form-control[readonly] {
            background: var(--surface-soft) !important;
            color: var(--muted);
            cursor: not-allowed;
        }

        input.form-control[readonly]:focus {
            border-color: var(--line);
            background: var(--surface-soft) !important;
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
            background: #06b6d4;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: width 0.3s ease, opacity 0.3s ease;
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
            color: var(--muted);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .tabla-status.visible {
            opacity: 1;
        }

        .tabla-status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #06b6d4;
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
        }

        .autocomplete-wrap.has-value .autocomplete-clear {
            display: grid;
        }

        .autocomplete-clear:hover {
            background: var(--danger-bg);
            color: var(--danger);
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
            background: var(--surface);
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
        }

        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: var(--surface-soft);
        }

        .autocomplete-item-extra {
            display: block;
            margin-top: 2px;
            font: 11.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .autocomplete-empty {
            cursor: default;
            font: 500 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            justify-content: center;
        }

        .autocomplete-empty:hover {
            background: transparent;
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

        .paciente-seleccionado {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: var(--surface-soft);
            border: 1px solid var(--line);
            border-radius: 10px;
            margin-top: 8px;
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
        }

        .paciente-seleccionado .quitar:hover {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger);
        }

        .paciente-seleccionado .quitar svg {
            width: 14px;
            height: 14px;
        }

        /* ============================================================
           SOLICITUD DE INSUMOS · filas dinámicas
           ============================================================ */
        .sol-items-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 22px 0 10px;
        }

        .sol-items-head label {
            font: 600 11.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sol-items-wrap {
            overflow: visible;
            padding-bottom: 26px;
        }

        .sol-items-table {
            min-width: 520px;
        }

        .sol-items-table tbody td {
            vertical-align: top;
            overflow: visible;
        }

        .sol-items-table .form-error {
            margin-top: 4px;
            display: block;
        }

        .sol-items-table tr.is-invalid .autocomplete-input,
        .sol-items-table tr.is-invalid .item-cantidad {
            border-color: var(--danger);
        }

        .sol-items-table .item-unidad {
            margin-top: 3px;
            font: 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .btn-item-remove {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            transition: color var(--transition-fast), border-color var(--transition-fast);
        }

        .btn-item-remove:hover {
            border-color: var(--danger);
            color: var(--danger);
        }

        .btn-item-remove svg {
            width: 16px;
            height: 16px;
        }

        .sol-resumen {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            font: 500 12.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .sol-resumen strong,
        .sol-resumen span {
            color: var(--teal-dark);
            font-weight: 600;
        }

        /* ============================================================
           ODONTOGRAMA
           ============================================================ */
        .odontograma {
            padding: 14px 16px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface-soft);
        }

        .odontograma-hint {
            margin-bottom: 10px;
            font: 11.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .odontograma-arcada {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
            gap: 14px;
        }

        .odontograma-cuadrante {
            min-width: 0;
        }

        .odontograma-titulo {
            display: block;
            margin-bottom: 6px;
            font: 700 9.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .odontograma-piezas {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .diente {
            position: relative;
            width: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .diente svg {
            width: 40px;
            height: 40px;
            display: block;
            border-radius: 5px;
            background: var(--surface);
            cursor: pointer;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .diente svg:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(24, 37, 45, 0.14);
        }

        .diente [data-cara] {
            transition: fill var(--transition-fast);
        }

        .diente-borde {
            fill: none;
            stroke: var(--line);
            stroke-width: 1.4;
            pointer-events: none;
        }

        .diente-ausente {
            stroke: #475569;
            stroke-width: 2.4;
            stroke-linecap: round;
            display: none;
            pointer-events: none;
        }

        .diente.es-ausente .diente-ausente {
            display: block;
        }

        .diente.es-tratada .diente-numero {
            color: var(--teal-dark);
            font-weight: 700;
        }

        .diente-numero {
            font: 600 9.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .odontograma[data-lectura="1"] .diente svg {
            cursor: default;
        }

        .odontograma[data-lectura="1"] .diente svg:hover {
            transform: none;
            box-shadow: none;
        }

        .odontograma-leyenda {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed var(--line);
        }

        .odontograma-leyenda-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font: 500 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
        }

        .odontograma-swatch {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            border: 1px solid var(--line);
            flex-shrink: 0;
        }

        .odontograma-swatch[data-estado="sano"] {
            background: #ffffff;
        }

        .odontograma-swatch[data-estado="caries"] {
            background: #dc2626;
            border-color: #b91c1c;
        }

        .odontograma-swatch[data-estado="obturado"] {
            background: #2563eb;
            border-color: #1d4ed8;
        }

        .odontograma-swatch[data-estado="ausente"] {
            background: #94a3b8;
            border-color: #64748b;
        }

        .odontograma-swatch[data-estado="corona"] {
            background: #d4a017;
            border-color: #a16207;
        }

        .odontograma-swatch[data-estado="endodoncia"] {
            background: #7c3aed;
            border-color: #6d28d9;
        }

        .odontograma-swatch[data-estado="sellante"] {
            background: #16a34a;
            border-color: #15803d;
        }

        .odontograma-swatch[data-estado="fractura"] {
            background: #ea580c;
            border-color: #c2410c;
        }

        :root[data-theme="dark"] .odontograma-swatch[data-estado="sano"] {
            background: #475569;
        }

        :root[data-theme="dark"] .diente svg {
            background: var(--surface);
        }

        /* ============================================================
           VENTANAS MODALES DINÁMICAS (creadas por JS)
           Compatible con: SweetAlert2, modales custom, ventanas emergentes
           ============================================================ */
        .modal-dynamic-backdrop,
        .swal2-container {
            position: fixed !important;
            inset: 0 !important;
            z-index: 10500 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            background: rgba(24, 37, 45, 0.55) !important;
            animation: fadeInModal 0.18s ease both;
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-dynamic {
            width: 100%;
            max-width: 640px;
            max-height: calc(100vh - 40px);
            max-height: calc(100dvh - 40px);
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            box-shadow: 0 30px 80px rgba(15, 23, 23, 0.35);
            overflow: hidden;
            animation: modalIn 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .modal-dynamic.modal-dynamic--sm {
            max-width: 440px;
        }

        .modal-dynamic.modal-dynamic--lg {
            max-width: 900px;
        }

        .modal-dynamic.modal-dynamic--xl {
            max-width: 1200px;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-dynamic.is-closing {
            animation: modalOut 0.18s ease both;
        }

        @keyframes modalOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            to {
                opacity: 0;
                transform: translateY(8px) scale(0.98);
            }
        }

        .modal-dynamic__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
        }

        .modal-dynamic__title {
            font: 600 16px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            letter-spacing: -0.01em;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-dynamic__body {
            flex: 1;
            min-height: 0;
            padding: 22px 24px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            color: var(--ink);
            font: 13.5px/1.55 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .modal-dynamic__footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            padding: 16px 24px;
            border-top: 1px solid var(--line);
            background: var(--surface-soft);
            flex-shrink: 0;
        }

        /* ============================================================
           SWEETALERT2 · overrides para integrar con el tema
           ============================================================ */
        .swal2-popup {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border: 1px solid var(--line) !important;
            border-radius: var(--radius-xl) !important;
            box-shadow: 0 30px 80px rgba(15, 23, 23, 0.35) !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        }

        .swal2-title {
            color: var(--ink) !important;
            font-weight: 600 !important;
        }

        .swal2-html-container,
        .swal2-content {
            color: var(--muted) !important;
        }

        .swal2-confirm {
            background: var(--teal) !important;
            color: #fff !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            padding: 10px 20px !important;
        }

        .swal2-confirm:hover {
            background: var(--teal-dark) !important;
        }

        .swal2-cancel {
            background: var(--surface-soft) !important;
            color: var(--ink) !important;
            border: 1px solid var(--line) !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
        }

        .swal2-cancel:hover {
            border-color: var(--cyan) !important;
            color: var(--teal) !important;
        }

        .swal2-input,
        .swal2-select,
        .swal2-textarea {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border: 1px solid var(--line) !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
        }

        .swal2-input:focus,
        .swal2-select:focus,
        .swal2-textarea:focus {
            border-color: var(--cyan) !important;
            box-shadow: var(--focus-ring) !important;
        }

        /* ============================================================
           TOASTIFY / notificaciones flotantes JS
           ============================================================ */
        .toastify {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 13px 20px !important;
            border-radius: var(--radius-md) !important;
            font: 600 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.18) !important;
            color: #fff !important;
            max-width: min(360px, calc(100vw - 32px)) !important;
            z-index: 10800 !important;
        }

        .toastify.on {
            opacity: 1 !important;
        }

        .toastify-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }

        /* ============================================================
           ALERTIFY / NOTY / similares
           ============================================================ */
        .ajs-dialog,
        .noty_layout {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border-radius: var(--radius-lg) !important;
            border: 1px solid var(--line) !important;
            box-shadow: 0 20px 60px rgba(15, 23, 23, 0.3) !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }

        .ajs-header {
            background: var(--surface-soft) !important;
            border-bottom: 1px solid var(--line) !important;
            font-weight: 600 !important;
        }

        .ajs-footer {
            background: var(--surface-soft) !important;
            border-top: 1px solid var(--line) !important;
        }

        .ajs-button {
            background: var(--teal) !important;
            color: #fff !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
        }

        .ajs-button.ajs-cancel {
            background: var(--surface-soft) !important;
            color: var(--ink) !important;
            border: 1px solid var(--line) !important;
        }

        /* ============================================================
           VENTANAS EMERGENTES / IFRAMES DE MODALES
           ============================================================ */
        .modal-iframe-backdrop {
            position: fixed;
            inset: 0;
            z-index: 10600;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(24, 37, 45, 0.55);
        }

        .modal-iframe {
            width: min(1100px, 96vw);
            height: min(780px, 92vh);
            height: min(780px, 92dvh);
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--line);
            box-shadow: 0 30px 80px rgba(15, 23, 23, 0.35);
            overflow: hidden;
            animation: modalIn 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .modal-iframe__bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid var(--line);
            background: var(--surface-soft);
        }

        .modal-iframe__bar-title {
            font: 600 13.5px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-iframe iframe {
            flex: 1;
            width: 100%;
            border: 0;
            background: var(--surface);
        }

        /* ============================================================
           SPINNER GLOBAL (mientras carga una ventana/modal JS)
           ============================================================ */
        .spinner-overlay {
            position: fixed;
            inset: 0;
            z-index: 11000;
            display: grid;
            place-items: center;
            background: rgba(15, 23, 23, 0.35);
            animation: fadeInModal 0.15s ease both;
        }

        .spinner-overlay[hidden] {
            display: none !important;
        }

        .spinner {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.25);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        .spinner--dark {
            border-color: rgba(8, 127, 121, 0.2);
            border-top-color: var(--teal);
        }

        .spinner-text {
            margin-top: 14px;
            color: #fff;
            font: 600 13px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            text-align: center;
        }

        /* ============================================================
           CONFIRM DIALOG (custom / estilizado)
           ============================================================ */
        .confirm-dialog {
            width: min(440px, calc(100vw - 32px));
            padding: 26px 24px 20px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            box-shadow: 0 30px 80px rgba(15, 23, 23, 0.35);
            text-align: center;
            animation: modalIn 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .confirm-dialog__icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: var(--warning-bg);
            color: var(--warning);
        }

        .confirm-dialog__icon svg {
            width: 26px;
            height: 26px;
        }

        .confirm-dialog__title {
            font: 600 17px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .confirm-dialog__text {
            font: 13.5px/1.55 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--muted);
            margin-bottom: 22px;
        }

        .confirm-dialog__actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .confirm-dialog--danger .confirm-dialog__icon {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .confirm-dialog--success .confirm-dialog__icon {
            background: var(--success-bg);
            color: var(--success);
        }

        .modal-dynamic .confirm-dialog {
            width: 100%;
            padding: 8px 0;
            border: 0;
            box-shadow: none;
            animation: none;
        }

        .modal-dynamic .confirm-dialog__icon {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            border-radius: 20px;
        }

        .confirm-dialog__record {
            margin-bottom: 16px;
            padding: 12px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface-soft);
            color: var(--ink);
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .modal-dynamic .confirm-dialog__text {
            margin-bottom: 0;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .modal-dynamic--confirm .modal-dynamic__footer .btn {
            flex: 1;
            justify-content: center;
            min-height: 42px;
        }

        /* ============================================================
           OVERLAY BLOQUEANTE (AJAX largos)
           ============================================================ */
        .ui-blocker {
            position: fixed;
            inset: 0;
            z-index: 10900;
            background: rgba(15, 23, 23, 0.25);
            cursor: wait;
            animation: fadeInModal 0.15s ease both;
        }

        .ui-blocker[hidden] {
            display: none !important;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 1200px) {
            .content {
                padding: 24px 24px;
            }

            .topbar {
                padding: 10px 20px;
            }

            .topbar-search-trigger {
                width: 210px;
            }

            .topbar-user-info {
                max-width: 130px;
            }
        }

        @media (max-width: 1024px) {
            .app {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 200;
                width: 292px;
                max-width: 86vw;
                height: 100vh;
                height: 100dvh;
                transform: translateX(-102%);
                visibility: hidden;
                border-right: 0;
                box-shadow: 18px 0 48px rgba(15, 23, 23, 0.22);
                transition: transform var(--transition-base), visibility var(--transition-base);
                overscroll-behavior: contain;
            }

            .sidebar.is-open {
                transform: translateX(0);
                visibility: visible;
            }

            .sidebar-close {
                display: grid;
            }

            .topbar-mobile-toggle {
                display: grid;
            }

            .topbar {
                padding: 10px 18px;
            }

            .content {
                padding: 22px 20px;
            }

            .nav-palette-panel {
                margin-top: 5vh;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                gap: 10px;
                min-height: 60px;
                padding: 10px 14px;
            }

            .topbar-search-trigger {
                width: 40px;
                height: 40px;
                justify-content: center;
                padding: 0;
                border-radius: var(--radius-md);
                background: var(--surface);
            }

            .search-trigger-text,
            .search-trigger-keys {
                display: none;
            }

            .topbar-user-info {
                display: none;
            }

            .topbar-user-btn {
                gap: 0;
                padding: 4px;
                border-radius: 13px;
            }

            .topbar-user-btn .chevron {
                display: none;
            }

            .topbar-help {
                display: none;
            }

            .topbar-actions {
                gap: 8px;
            }

            .nav-palette-panel {
                width: 100%;
                max-height: 82vh;
                margin: auto 0 0;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                animation: sheetIn 0.24s cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            .nav-palette {
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
            }

            @keyframes sheetIn {
                from {
                    opacity: 0;
                    transform: translateY(24px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .nav-palette-hint {
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

            .toast {
                bottom: 16px;
                right: 16px;
                left: 16px;
                max-width: none;
                text-align: center;
            }

            .table-wrap {
                margin: 0 -16px;
                padding: 0 16px;
                border-radius: 0;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .actions .btn {
                width: 100%;
            }

            .topbar-dropdown {
                right: -6px;
                min-width: min(260px, calc(100vw - 28px));
            }

            /* Modales dinámicos en móvil (bottom sheet) */
            .modal-dynamic-backdrop,
            .swal2-container {
                padding: 0 !important;
                align-items: flex-end !important;
            }

            .modal-dynamic {
                max-width: 100%;
                max-height: 92vh;
                max-height: 92dvh;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                animation: sheetIn 0.24s cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            .modal-dynamic__header,
            .modal-dynamic__body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .modal-dynamic__footer {
                padding: 14px 18px;
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .modal-dynamic__footer .btn {
                width: 100%;
            }

            .modal-iframe-backdrop {
                padding: 0;
                align-items: stretch;
            }

            .modal-iframe {
                width: 100%;
                height: 100%;
                max-height: 100dvh;
                border-radius: 0;
            }

            .confirm-dialog {
                width: 100%;
                max-width: 100%;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                padding: 24px 20px 20px;
            }

            .confirm-dialog__actions {
                flex-direction: column-reverse;
            }

            .confirm-dialog__actions .btn {
                width: 100%;
            }

            .swal2-popup {
                width: 100% !important;
                max-width: 100% !important;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0 !important;
                margin: 0 !important;
            }

            .toastify {
                bottom: 16px !important;
                right: 16px !important;
                left: 16px !important;
                max-width: none !important;
                text-align: center;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .topbar-heading {
                font-size: 15px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .topbar-sub,
            .topbar-breadcrumb {
                display: none;
            }

            .topbar-context {
                min-width: 0;
            }

            .topbar-search-trigger {
                width: 38px;
                height: 38px;
            }

            .topbar-icon-btn {
                width: 38px;
                height: 38px;
            }

            .topbar-actions {
                gap: 6px;
            }

            .nav-palette-field {
                padding: 13px 14px;
            }

            .nav-palette-field input {
                font-size: 15px;
            }

            .sidebar {
                width: 100%;
                max-width: 100vw;
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

            .pagination-wrap nav[role="navigation"] a,
            .pagination-wrap nav[role="navigation"] span[aria-current="page"]>span,
            .pagination-wrap nav[role="navigation"] span[aria-disabled="true"]>span {
                min-width: 30px;
                height: 30px;
                font-size: 12px;
                padding: 0 8px;
            }

            .modal-dynamic__body {
                padding: 16px 14px;
            }

            .modal-dynamic__header {
                padding: 14px 16px;
            }

            .confirm-dialog__icon {
                width: 48px;
                height: 48px;
            }

            .confirm-dialog__title {
                font-size: 15.5px;
            }
        }

        @media (max-width: 360px) {
            .topbar .theme-toggle {
                display: none;
            }

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
           SCROLLBAR PERSONALIZADA
           ============================================================ */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-soft);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

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
            --sidebar-bg: #192421;
            --topbar-bg: #192421;
            --control-bg: #16211e;
            --success-bg: #153a2c;
            --success-border: #286447;
            --danger-bg: #402627;
            --danger-border: #744044;
            --warning-bg: #40351e;
            --warning-border: #765d2a;
            --info-bg: #203747;
            --info-border: #365e73;
            --glass-bg: #192421;
            --glass-border: #354a44;
            --glass-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        }

        :root[data-theme="dark"] .sidebar {
            background: linear-gradient(180deg, rgba(25, 36, 33, 0.96), rgba(17, 26, 24, 0.96));
            box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.02), 4px 0 28px rgba(0, 0, 0, 0.12);
        }

        :root[data-theme="dark"] .topbar {
            background: rgba(25, 36, 33, 0.8);
            border-bottom-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.12);
        }

        :root[data-theme="dark"] .system-footer {
            background: rgba(17, 26, 24, 0.5);
        }

        :root[data-theme="dark"] .sidebar,
        :root[data-theme="dark"] .topbar,
        :root[data-theme="dark"] .topbar-search-trigger,
        :root[data-theme="dark"] .topbar-icon-btn,
        :root[data-theme="dark"] .topbar-user-btn,
        :root[data-theme="dark"] .topbar-dropdown,
        :root[data-theme="dark"] .nav-palette-panel,
        :root[data-theme="dark"] .metric-card,
        :root[data-theme="dark"] .welcome-card,
        :root[data-theme="dark"] .card,
        :root[data-theme="dark"] .stat-card,
        :root[data-theme="dark"] .modal,
        :root[data-theme="dark"] .modal-close,
        :root[data-theme="dark"] .modal-dynamic,
        :root[data-theme="dark"] .modal-iframe,
        :root[data-theme="dark"] .confirm-dialog {
            background-color: var(--surface);
            border-color: var(--surface-border);
        }

        :root[data-theme="dark"] .topbar-dropdown,
        :root[data-theme="dark"] .modal,
        :root[data-theme="dark"] .modal-dynamic,
        :root[data-theme="dark"] .form-control:focus {
            background-color: var(--surface-raised);
        }

        :root[data-theme="dark"] .topbar-mobile-toggle,
        :root[data-theme="dark"] .form-control,
        :root[data-theme="dark"] .btn-secondary,
        :root[data-theme="dark"] .btn-icon,
        :root[data-theme="dark"] .kbd,
        :root[data-theme="dark"] .sidebar-shortcut,
        :root[data-theme="dark"] .sidebar-filter,
        :root[data-theme="dark"] .user-quick,
        :root[data-theme="dark"] .pagination-wrap nav[role="navigation"] a {
            background-color: var(--control-bg);
            border-color: var(--surface-border);
            color: var(--ink);
        }

        :root[data-theme="dark"] .card-header,
        :root[data-theme="dark"] .modal-header,
        :root[data-theme="dark"] .modal-footer,
        :root[data-theme="dark"] .modal-dynamic__header,
        :root[data-theme="dark"] .modal-dynamic__footer,
        :root[data-theme="dark"] .modal-iframe__bar,
        :root[data-theme="dark"] .pagination-wrap,
        :root[data-theme="dark"] .checkbox-grid,
        :root[data-theme="dark"] .nav-palette-hint,
        :root[data-theme="dark"] thead th {
            background-color: var(--surface-soft);
        }

        :root[data-theme="dark"] .sidebar-filter:focus-within,
        :root[data-theme="dark"] .topbar-search-trigger:hover {
            background-color: var(--surface-raised);
            border-color: var(--cyan);
        }

        :root[data-theme="dark"] .sidebar-user {
            background: linear-gradient(180deg, var(--surface-soft), transparent);
        }

        :root[data-theme="dark"] .palette-icon {
            background: linear-gradient(135deg, rgba(66, 183, 194, 0.2), rgba(85, 196, 178, 0.06));
            color: var(--teal-dark);
        }

        :root[data-theme="dark"] .badge-dot {
            box-shadow: 0 0 0 2px var(--surface);
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
            background: var(--surface-soft);
            color: var(--teal-dark);
        }

        :root[data-theme="dark"] tbody tr:hover,
        :root[data-theme="dark"] .checkbox-item:hover,
        :root[data-theme="dark"] .dropdown-item:hover {
            background-color: var(--surface-soft);
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

        :root[data-theme="dark"] .swal2-popup {
            background: var(--surface-raised) !important;
            border-color: var(--surface-border) !important;
        }

        :root[data-theme="dark"] .swal2-input,
        :root[data-theme="dark"] .swal2-select,
        :root[data-theme="dark"] .swal2-textarea {
            background: var(--control-bg) !important;
            border-color: var(--surface-border) !important;
        }

        :root[data-theme="dark"] .spinner-overlay {
            background: rgba(0, 0, 0, 0.55);
        }

        :root[data-theme="dark"] .ui-blocker {
            background: rgba(0, 0, 0, 0.4);
        }

        /* ============================================================
           ACABADO VISUAL Y MOVIMIENTO
           ============================================================ */
        body {
            background-image: radial-gradient(ellipse at 100% 0%, rgba(7, 143, 160, 0.07), transparent 55%);
            transition: background-color var(--transition-base), color var(--transition-base);
        }

        @media (min-width: 1025px) {
            .sidebar {
                box-shadow: 4px 0 24px rgba(24, 37, 45, 0.025);
            }
        }

        .sidebar-brand {
            min-height: 80px;
        }

        .brand-logo {
            background: linear-gradient(145deg, var(--cyan), var(--teal));
        }

        .nav-item {
            border-radius: 12px;
            transition: background-color var(--transition-fast), color var(--transition-fast), box-shadow var(--transition-fast);
        }

        .nav-icon {
            border-radius: 7px;
            transition: background-color var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
        }

        .nav-item.active {
            box-shadow: inset 0 0 0 1px rgba(7, 143, 160, 0.12);
        }

        .nav-item.active .nav-icon {
            color: var(--teal-dark);
            background: rgba(7, 143, 160, 0.1);
        }

        .topbar {
            min-height: 80px;
            box-shadow: 0 4px 20px rgba(24, 37, 45, 0.025);
            transition: background-color var(--transition-base), border-color var(--transition-base);
        }

        .topbar-heading {
            letter-spacing: -0.035em;
        }

        .card,
        .stat-card,
        .metric-card {
            box-shadow: var(--shadow-card);
            transition: box-shadow var(--transition-base), border-color var(--transition-base), background-color var(--transition-base), transform var(--transition-base);
        }

        .card:hover,
        .stat-card:hover,
        .metric-card:hover {
            box-shadow: var(--shadow-card-hover);
        }

        .card-header {
            padding-block: 20px;
        }

        .card-header h2 {
            font-size: 15px;
        }

        .stat-value {
            font-size: clamp(24px, 2.5vw, 32px);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.045em;
        }

        .stat-label {
            text-transform: none;
            letter-spacing: 0;
            line-height: 1.5;
        }

        .stat-icon {
            box-shadow: 0 4px 10px rgba(8, 127, 121, 0.12);
            transition: transform var(--transition-base);
        }

        .topbar-dropdown {
            transform-origin: top right;
            transform: translateY(8px) scale(0.98);
        }

        .topbar-dropdown.is-open {
            transform: translateY(0) scale(1);
        }

        tbody tr:focus-within {
            background: var(--surface-soft);
        }

        .table-wrap tbody tr:hover td:first-child {
            box-shadow: inset 3px 0 var(--teal);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            padding: 10px;
            border-radius: 14px;
            background: var(--surface-soft);
            opacity: 0.8;
        }

        .btn,
        .btn-icon,
        .modal-close,
        .topbar-icon-btn,
        .topbar-user-btn,
        .topbar-search-trigger,
        .dropdown-item {
            transition: background-color var(--transition-fast), color var(--transition-fast), border-color var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--teal), #076d72);
            box-shadow: 0 4px 10px rgba(8, 127, 121, 0.16);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 16px rgba(8, 127, 121, 0.24);
        }

        .btn:disabled,
        .btn-icon:disabled {
            cursor: not-allowed;
            opacity: 0.55;
            transform: none;
            box-shadow: none;
        }

        :where(a, button, input, select, textarea):focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 3px;
        }

        .form-control {
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background-color var(--transition-fast);
        }

        .form-control:focus {
            box-shadow: var(--focus-ring);
        }

        tbody tr {
            transition: background-color var(--transition-fast);
        }

        .modal-backdrop {
            /* Sin backdrop-filter */
        }

        .modal {
            transform: translateY(18px) scale(0.98);
        }

        .modal-backdrop.open .modal {
            transform: translateY(0) scale(1);
        }

        @media (hover: hover) and (prefers-reduced-motion: no-preference) {

            .stat-card:hover,
            .metric-card:hover {
                transform: translateY(-3px);
            }

            .stat-card:hover .stat-icon {
                transform: translateY(-1px) scale(1.035);
            }

            .btn:not(:disabled):hover,
            .btn-icon:not(:disabled):hover {
                transform: translateY(-1px);
            }

            .btn:not(:disabled):active,
            .btn-icon:not(:disabled):active {
                transform: translateY(0) scale(0.98);
            }
        }

        @media (prefers-reduced-motion: no-preference) {
            @view-transition {
                navigation: auto;
            }

            .content {
                view-transition-name: app-content;
            }

            ::view-transition-group(app-content) {
                animation-duration: 0.24s;
                animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
            }

            ::view-transition-old(root) {
                animation: none;
            }

            ::view-transition-new(root) {
                animation: none;
            }

            ::view-transition-old(app-content) {
                animation: pageFadeOut 0.16s ease-out both;
            }

            ::view-transition-new(app-content) {
                animation: contentEnter 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            .stats-grid>.stat-card {
                animation: pageFadeIn 0.4s ease-out backwards;
            }

            .stats-grid>.stat-card:nth-child(2) {
                animation-delay: 0.04s;
            }

            .stats-grid>.stat-card:nth-child(3) {
                animation-delay: 0.08s;
            }

            .stats-grid>.stat-card:nth-child(4) {
                animation-delay: 0.12s;
            }

            .stats-grid>.stat-card:nth-child(n+5) {
                animation-delay: 0.16s;
            }
        }

        @keyframes pageFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes pageFadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes contentEnter {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar,
        .sidebar *,
        .sidebar::before,
        .sidebar::after,
        .sidebar *::before,
        .sidebar *::after,
        .sidebar-backdrop {
            scroll-behavior: auto;
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            ::view-transition-group(*),
            ::view-transition-old(*),
            ::view-transition-new(*) {
                animation: none !important;
            }
        }

        :root[data-theme="dark"] {
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-card-hover: 0 10px 32px rgba(0, 0, 0, 0.24);
        }

        body {
            background:
                radial-gradient(ellipse at 100% 0, color-mix(in srgb, var(--cyan) 7%, transparent), transparent 42rem),
                var(--bg);
        }

        .sidebar {
            box-shadow: 1px 0 0 var(--line);
            overflow: hidden;
        }

        .topbar {
            background: var(--topbar-bg);
        }

        .content {
            padding: clamp(20px, 3vw, 36px);
        }

        .content:has(.modal-backdrop.open) {
            z-index: 1000;
        }

        .card {
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            transition:
                transform var(--transition-base),
                background-color var(--transition-base),
                border-color var(--transition-base),
                box-shadow var(--transition-base);
        }

        .card-header {
            background: var(--surface);
        }

        .card-header :is(h2, h3) {
            font-size: 16px;
            font-weight: 650;
            letter-spacing: -0.025em;
            line-height: 1.4;
        }

        .card-meta {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .sidebar-brand {
            min-height: 80px;
            padding: 18px 16px;
            gap: 10px;
            background: var(--sidebar-bg);
        }

        .sidebar .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            box-shadow: 0 4px 12px color-mix(in srgb, var(--teal) 20%, transparent);
        }

        .sidebar .brand-title {
            font-size: 13px;
        }

        .sidebar .brand-sub {
            margin-top: 4px;
            font-size: 9px;
            letter-spacing: 0.12em;
        }

        .sidebar-user {
            margin: 12px 12px 0;
            padding: 12px 10px;
            gap: 10px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface-soft);
        }

        .sidebar .avatar-md {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            font-size: 12px;
        }

        .sidebar .user-name {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            white-space: normal;
            line-height: 1.4;
            font-size: 12px;
        }

        .sidebar .user-role {
            font-size: 9px;
            letter-spacing: 0.035em;
        }

        .sidebar-tools {
            padding: 14px 12px 4px;
        }

        .sidebar-filter {
            min-height: 40px;
            padding: 8px 10px;
            border-radius: 12px;
            background: var(--sidebar-bg);
        }

        .sidebar-filter input {
            font-size: 12px;
        }

        .sidebar-filter .kbd {
            min-width: 18px;
            height: 20px;
            font-size: 10px;
            padding: 2px 4px;
        }

        .sidebar-filter:has(input:not(:placeholder-shown)) .kbd {
            display: none;
        }

        .sidebar-nav {
            gap: 3px;
            padding: 4px 12px 16px;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: var(--line) transparent;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--line);
        }

        .nav-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 8px 7px;
            font-size: 9px;
            letter-spacing: 0.11em;
            color: var(--muted);
        }

        .nav-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .sidebar-nav>.nav-item,
        .sidebar-nav .nav-item-toggle {
            min-height: 42px;
            padding: 6px 9px;
            border: 1px solid transparent;
            border-radius: 11px;
            gap: 10px;
            font-size: 12.5px;
        }

        .sidebar-nav .nav-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--surface-soft);
            color: var(--muted);
        }

        .sidebar-nav .nav-icon svg {
            width: 16px;
            height: 16px;
        }

        .sidebar-nav .nav-item:hover .nav-icon {
            color: var(--teal-dark);
        }

        .sidebar-nav .nav-children {
            margin: 4px 0 6px 22px;
            padding-left: 9px;
            gap: 3px;
        }

        .sidebar-nav .nav-item-child {
            min-height: 36px;
            padding: 6px 8px;
            font-size: 12px;
            gap: 8px;
        }

        .sidebar-nav .nav-item-child .nav-icon {
            width: 22px;
            height: 22px;
            background: transparent;
        }

        .sidebar-footer {
            padding: 12px;
            gap: 8px;
            background: var(--sidebar-bg);
            border-top: 1px solid var(--line);
        }

        .sidebar-shortcut,
        .sidebar .logout-btn {
            min-height: 38px;
            border-radius: 11px;
            justify-content: flex-start;
            padding-inline: 12px;
            font-size: 12px;
        }

        .sidebar-meta {
            padding: 4px 3px 0;
            font-size: 9px;
            letter-spacing: 0.06em;
        }

        .nav-item.active {
            background: color-mix(in srgb, var(--teal) 10%, var(--surface));
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--teal) 18%, transparent);
        }

        .sidebar-nav .nav-item.active .nav-icon {
            background: var(--teal);
            color: var(--surface);
            opacity: 1;
        }

        :root[data-theme="dark"] .btn-primary {
            background: var(--teal);
            color: var(--bg);
        }

        .topbar-heading {
            font-size: clamp(17px, 1.6vw, 22px);
            font-weight: 650;
            line-height: 1.35;
        }

        .topbar-sub {
            line-height: 1.6;
        }

        .topbar-search-trigger {
            border-radius: 12px;
            background: var(--surface-soft);
        }

        .content :is(.card, .welcome-card) {
            margin-bottom: 24px;
        }

        .card-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .table-wrap {
            scrollbar-width: thin;
            scrollbar-color: var(--line) transparent;
        }

        thead th {
            padding-block: 14px;
            background: var(--surface-soft);
            font-size: 10.5px;
            letter-spacing: 0.075em;
        }

        tbody td {
            padding-block: 15px;
            line-height: 1.5;
        }

        tbody td small {
            font-size: 12px;
        }

        .pagination-wrap {
            padding-block: 18px;
            background: var(--surface);
            gap: 16px;
        }

        .pagination-wrap nav[role="navigation"] a {
            transition: background-color var(--transition-fast), border-color var(--transition-fast), color var(--transition-fast);
        }

        .form-group :is(small, .form-hint),
        .form-hint {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 20px;
            margin-top: 24px;
            border-top: 1px solid var(--line);
        }

        .form-control:disabled,
        .form-control[readonly] {
            background: var(--surface-soft);
            color: var(--muted);
        }

        .form-control[aria-invalid="true"] {
            border-color: var(--danger);
        }

        :is(input[type="checkbox"], input[type="radio"]) {
            accent-color: var(--teal);
        }

        .btn-outline-danger {
            color: var(--danger);
            border: 1px solid var(--danger-border);
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: var(--danger-bg);
        }

        .modal-header,
        .modal-dynamic__header {
            background: var(--surface-soft);
        }

        .modal-footer,
        .modal-dynamic__footer {
            background: var(--surface);
        }

        .section-title {
            margin: 28px 0 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
            color: var(--ink);
            font-size: 14px;
            font-weight: 650;
            letter-spacing: -0.01em;
        }

        .profile-photo {
            align-items: center;
            text-align: center;
            padding: 24px;
            margin-bottom: 24px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--surface-soft), var(--surface));
            border: 1px solid var(--line);
        }

        .profile-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .profile-avatar,
        .profile-avatar-image {
            width: 120px;
            height: 120px;
            border-radius: 36px;
            border: 4px solid var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .profile-avatar {
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--cyan), var(--fuchsia));
            color: #fff;
            font-size: 36px;
            font-weight: 700;
        }

        .profile-avatar-image {
            position: absolute;
            inset: 0;
            object-fit: cover;
        }

        .profile-avatar-image[hidden] {
            display: none;
        }

        .profile-photo .profile-avatar-edit {
            position: absolute;
            right: -4px;
            bottom: -4px;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            background: var(--teal);
            color: #fff;
            border: 3px solid var(--surface);
            border-radius: 12px;
            cursor: pointer;
            box-shadow: var(--shadow-soft);
        }

        .account-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .account-stat {
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--surface-soft);
        }

        .account-stat-value {
            color: var(--teal-dark);
            font-size: 28px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.04em;
        }

        .account-stat-value--text {
            font-size: 15px;
            letter-spacing: 0;
        }

        .account-stat .form-hint {
            display: block;
            margin-top: 6px;
        }

        .account-empty {
            color: var(--muted);
            text-align: center;
            padding: 28px 16px;
            font-size: 13px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-list>li {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
        }

        .activity-list>li:last-child {
            border-bottom: 0;
        }

        .activity-detail {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .activity-time {
            flex-shrink: 0;
            color: var(--muted);
            font-size: 11px;
        }

        @media (max-width: 600px) {

            .sidebar-nav>.nav-item,
            .sidebar-nav .nav-item-toggle {
                min-height: 44px;
            }

            .dashboard-toolbar {
                padding: 14px;
                gap: 14px;
            }

            .toolbar-actions {
                width: 100%;
                gap: 8px;
            }

            .toolbar-actions>.btn {
                flex-grow: 1;
            }

            .form-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .account-stats {
                grid-template-columns: minmax(0, 1fr);
            }

            .account-stat {
                padding: 16px;
            }

            .activity-list>li {
                flex-direction: column;
                gap: 8px;
            }

            .topbar-heading {
                font-size: 16px;
            }
        }

        .nav-item {
            transition:
                background-color var(--transition-fast),
                color var(--transition-fast),
                box-shadow var(--transition-fast),
                transform var(--transition-fast);
        }

        .nav-item.active {
            position: relative;
        }

        .nav-item.active::before {
            position: absolute;
            inset: 9px auto 9px 0;
            width: 3px;
            height: auto;
            min-height: 0;
            transform: none;
            border-radius: var(--radius-full);
            background: var(--teal);
            content: '';
        }

        .theme-toggle svg {
            transition: transform var(--transition-base);
        }

        .theme-toggle:hover svg {
            transform: rotate(-12deg);
        }

        :root.theme-transitioning,
        :root.theme-transitioning body,
        :root.theme-transitioning .app,
        :root.theme-transitioning .sidebar,
        :root.theme-transitioning .sidebar *,
        :root.theme-transitioning .topbar,
        :root.theme-transitioning .topbar *,
        :root.theme-transitioning .content,
        :root.theme-transitioning .content :is(.card, .stat-card, .metric-card, .form-control, .alert, .badge, thead th, tbody tr),
        :root.theme-transitioning .modal,
        :root.theme-transitioning .modal *,
        :root.theme-transitioning .modal-dynamic,
        :root.theme-transitioning .modal-dynamic * {
            transition-property: color, background-color, border-color, box-shadow, fill, stroke;
            transition-duration: 280ms;
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
        }

        @media (hover: hover) and (prefers-reduced-motion: no-preference) {
            .card:hover {
                border-color: color-mix(in srgb, var(--teal) 25%, var(--line));
            }
        }

        @media (prefers-reduced-motion: reduce) {

            :root.theme-transitioning,
            :root.theme-transitioning body,
            :root.theme-transitioning .sidebar *,
            :root.theme-transitioning .topbar *,
            :root.theme-transitioning .content *,
            :root.theme-transitioning .modal * {
                transition: none !important;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                min-height: 68px;
            }

            .card-header {
                padding-block: 16px;
            }

            .stat-value {
                font-size: 24px;
            }
        }

        /* ============================================================
           PRINT · ocultar overlays y modales al imprimir
           ============================================================ */
        @media print {

            .modal-dynamic-backdrop,
            .modal-backdrop,
            .swal2-container,
            .toastify,
            .spinner-overlay,
            .ui-blocker,
            .toast,
            .sidebar-backdrop {
                display: none !important;
            }
        }

        /* ============================================================
           ACABADO GENERAL · navegación, superficies e interacciones
           ============================================================ */
        .sidebar {
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--teal) 3%, var(--sidebar-bg)), var(--sidebar-bg) 28%);
            box-shadow: 1px 0 0 color-mix(in srgb, var(--line) 80%, transparent);
            view-transition-name: none;
            transition:
                background-color var(--transition-base),
                border-color var(--transition-base),
                box-shadow var(--transition-base),
                transform var(--transition-base),
                visibility var(--transition-base);
        }

        .sidebar-brand {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--sidebar-bg);
        }

        .sidebar .brand-logo {
            transition: transform var(--transition-base), box-shadow var(--transition-base);
        }

        .sidebar-user {
            transition: background-color var(--transition-base), border-color var(--transition-base), box-shadow var(--transition-base);
        }

        .sidebar .avatar-md {
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--teal) 14%, var(--surface));
        }

        .sidebar-filter {
            box-shadow: 0 2px 8px color-mix(in srgb, var(--ink) 4%, transparent);
        }

        .sidebar-nav {
            scrollbar-gutter: stable;
        }

        #appSidebar:not(.has-nav-interaction) .sidebar-nav .nav-children {
            animation: none;
            transition: none;
        }

        .sidebar-nav .nav-label {
            padding-top: 18px;
            font-size: 9px;
            font-weight: 750;
        }

        .sidebar-nav>.nav-item,
        .sidebar-nav .nav-item-toggle {
            min-height: 44px;
            border-radius: 12px;
            transition:
                background-color var(--transition-fast),
                border-color var(--transition-fast),
                color var(--transition-fast),
                box-shadow var(--transition-fast),
                transform var(--transition-fast);
        }

        .sidebar-nav .nav-item:hover {
            border-color: color-mix(in srgb, var(--teal) 14%, transparent);
        }

        .sidebar-nav .nav-item:not(.disabled):active {
            transform: scale(0.985);
        }

        .sidebar-nav .nav-item.active {
            background: linear-gradient(100deg,
                    color-mix(in srgb, var(--teal) 13%, var(--surface)),
                    color-mix(in srgb, var(--cyan) 5%, var(--surface)));
            border-color: color-mix(in srgb, var(--teal) 18%, transparent);
            box-shadow: inset 0 1px 0 color-mix(in srgb, white 24%, transparent);
        }

        .sidebar-nav .nav-item.active::before {
            inset-block: 9px;
            inset-inline-start: -1px;
            width: 3px;
            border-radius: 0 var(--radius-full) var(--radius-full) 0;
            box-shadow: 0 0 12px color-mix(in srgb, var(--teal) 35%, transparent);
        }

        .sidebar-nav .nav-item.active .nav-icon {
            background: color-mix(in srgb, var(--teal) 12%, var(--surface));
            color: var(--teal-dark);
            transform: scale(1.04);
        }

        .sidebar-nav .nav-group.is-active>.nav-item-toggle:not(.active) {
            color: var(--teal-dark);
        }

        .sidebar-nav .nav-children {
            display: flex;
            max-height: 0;
            overflow: hidden;
            visibility: hidden;
            opacity: 0;
            transform: translateY(-5px);
            transition:
                max-height 320ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 180ms ease,
                transform 220ms ease,
                visibility 0s linear 220ms;
        }

        .sidebar-nav .nav-group.is-open>.nav-children {
            max-height: 80rem;
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
            transition:
                max-height 380ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 220ms ease 40ms,
                transform 240ms ease,
                visibility 0s;
        }

        .sidebar-nav .nav-item-child {
            transition:
                background-color var(--transition-fast),
                color var(--transition-fast),
                border-color var(--transition-fast),
                transform var(--transition-fast);
        }

        .sidebar-nav .nav-item-child.active {
            background: color-mix(in srgb, var(--teal) 9%, var(--surface));
            color: var(--teal-dark);
            font-weight: 650;
        }

        .sidebar-nav .nav-item-child.active::before {
            inset-inline-start: -10px;
            width: 2px;
        }

        .sidebar-nav .nav-badge {
            border: 1px solid color-mix(in srgb, currentColor 12%, transparent);
            box-shadow: 0 2px 5px color-mix(in srgb, var(--ink) 6%, transparent);
        }

        .sidebar-footer {
            position: sticky;
            bottom: 0;
            z-index: 2;
            background: var(--sidebar-bg);
        }

        .sidebar-shortcut,
        .sidebar .logout-btn {
            transition:
                background-color var(--transition-fast),
                border-color var(--transition-fast),
                color var(--transition-fast),
                box-shadow var(--transition-fast),
                transform var(--transition-fast);
        }

        .sidebar-shortcut:hover,
        .sidebar .logout-btn:hover {
            transform: translateY(-1px);
        }

        .topbar {
            background: var(--topbar-bg);
            border-bottom-color: color-mix(in srgb, var(--line) 88%, transparent);
            box-shadow: 0 4px 20px color-mix(in srgb, var(--ink) 4%, transparent);
            view-transition-name: none;
        }

        .topbar-actions {
            gap: 8px;
        }

        .topbar-icon-btn,
        .topbar-mobile-toggle {
            border-radius: 12px;
        }

        .topbar-heading {
            text-wrap: balance;
        }

        .content {
            isolation: isolate;
        }

        .content> :is(.alert, .dashboard-toolbar, .stats-grid, .metrics-grid, .card, .welcome-card) {
            animation: contentEnter 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .content> :is(.alert, .dashboard-toolbar, .stats-grid, .metrics-grid, .card, .welcome-card):nth-child(2) {
            animation-delay: 35ms;
        }

        .content> :is(.alert, .dashboard-toolbar, .stats-grid, .metrics-grid, .card, .welcome-card):nth-child(3) {
            animation-delay: 70ms;
        }

        .card,
        .stat-card,
        .metric-card,
        .welcome-card {
            border-color: color-mix(in srgb, var(--line) 88%, transparent);
            transition:
                transform var(--transition-base),
                background-color var(--transition-base),
                border-color var(--transition-base),
                box-shadow var(--transition-base);
        }

        .card-header {
            background: linear-gradient(110deg, var(--surface), color-mix(in srgb, var(--surface-soft) 54%, var(--surface)));
        }

        .table-wrap {
            border-radius: var(--radius-md);
        }

        tbody tr:hover {
            background: color-mix(in srgb, var(--teal) 5%, var(--surface));
        }

        .form-control,
        .form-select {
            transition:
                background-color var(--transition-fast),
                border-color var(--transition-fast),
                box-shadow var(--transition-fast),
                color var(--transition-fast);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--teal);
            box-shadow: var(--focus-ring);
        }

        .alert {
            border-radius: var(--radius-md);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--ink) 5%, transparent);
        }

        @media (hover: hover) and (prefers-reduced-motion: no-preference) {
            .sidebar .brand-logo:hover {
                transform: translateY(-2px) rotate(-4deg);
                box-shadow: 0 8px 20px color-mix(in srgb, var(--teal) 30%, transparent);
            }

            .sidebar-nav .nav-item:hover .nav-icon {
                transform: translateX(2px);
            }

            .card:hover,
            .stat-card:hover,
            .metric-card:hover,
            .welcome-card:hover {
                border-color: color-mix(in srgb, var(--teal) 24%, var(--line));
                box-shadow: var(--shadow-card-hover);
            }

            .card:hover,
            .welcome-card:hover {
                transform: translateY(-2px);
            }

            .btn:not(:disabled):hover {
                box-shadow: 0 6px 18px color-mix(in srgb, var(--teal) 18%, transparent);
            }
        }

        @media (max-width: 1024px) {
            .sidebar.is-open {
                box-shadow: 20px 0 56px color-mix(in srgb, #000 24%, transparent);
            }
        }

        /* ============================================================
           SIDEBAR RESPONSIVE · rail de iconos y drawer móvil
           ============================================================ */
        .app {
            transition: grid-template-columns var(--transition-base);
        }

        .topbar-mobile-toggle {
            display: grid;
        }

        .sidebar-toggle-icon {
            transition: transform var(--transition-base);
        }

        .sidebar-toggle-icon--mobile {
            display: none;
        }

        @media (min-width: 1025px) {
            .app.sidebar-collapsed {
                grid-template-columns: 82px minmax(0, 1fr);
            }

            .app.sidebar-collapsed .sidebar {
                width: 82px;
            }

            .app.sidebar-collapsed .sidebar-brand {
                justify-content: center;
                min-height: 72px;
                padding-inline: 10px;
            }

            .app.sidebar-collapsed .brand-text,
            .app.sidebar-collapsed .sidebar-user .user-info,
            .app.sidebar-collapsed .sidebar-user .user-quick,
            .app.sidebar-collapsed .sidebar-tools,
            .app.sidebar-collapsed .sidebar-nav .nav-label,
            .app.sidebar-collapsed .sidebar-nav .nav-text,
            .app.sidebar-collapsed .sidebar-nav .nav-badge,
            .app.sidebar-collapsed .sidebar-nav .nav-caret,
            .app.sidebar-collapsed .sidebar-nav .nav-children,
            .app.sidebar-collapsed .sidebar-shortcut .shortcut-text,
            .app.sidebar-collapsed .sidebar-shortcut .kbd,
            .app.sidebar-collapsed .sidebar .logout-btn span,
            .app.sidebar-collapsed .sidebar-meta {
                display: none;
            }

            .app.sidebar-collapsed .sidebar-user {
                justify-content: center;
                margin: 10px 9px 0;
                padding: 9px;
            }

            .app.sidebar-collapsed .sidebar-nav {
                align-items: stretch;
                padding: 10px 9px 16px;
                scrollbar-width: none;
            }

            .app.sidebar-collapsed .sidebar-nav::-webkit-scrollbar {
                display: none;
            }

            .app.sidebar-collapsed .sidebar-nav>.nav-item,
            .app.sidebar-collapsed .sidebar-nav .nav-item-toggle {
                justify-content: center;
                min-height: 46px;
                padding: 6px;
            }

            .app.sidebar-collapsed .sidebar-nav .nav-icon {
                width: 32px;
                height: 32px;
            }

            .app.sidebar-collapsed .sidebar-nav .nav-item.active::before {
                inset-block: 8px;
            }

            .app.sidebar-collapsed .sidebar-footer {
                padding: 10px 9px;
            }

            .app.sidebar-collapsed .sidebar-shortcut,
            .app.sidebar-collapsed .sidebar .logout-btn {
                justify-content: center;
                min-height: 42px;
                padding: 0;
            }

            .app.sidebar-collapsed .sidebar-shortcut .shortcut-icon,
            .app.sidebar-collapsed .sidebar .logout-btn svg {
                margin: 0;
            }

            .app.sidebar-collapsed .sidebar-toggle-icon--desktop {
                transform: rotate(180deg);
            }
        }

        @media (max-width: 1024px) {
            .sidebar-toggle-icon--desktop {
                display: none;
            }

            .sidebar-toggle-icon--mobile {
                display: block;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .sidebar-nav .nav-children,
            .sidebar-nav .nav-group.is-open>.nav-children,
            .content> :is(.alert, .dashboard-toolbar, .stats-grid, .metrics-grid, .card, .welcome-card) {
                animation: none;
                transition: none;
            }
        }
    </style>

    @include('layouts.partials.pagination-styles')
    @stack('styles')
</head>

<body>
    <div class="app">
        @include('layouts.partials.sidebar')

        {{-- Mantener el fondo dentro del contexto de apilamiento del sidebar. --}}
        <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

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

            <footer class="system-footer">
                &copy; {{ date('Y') }} Universidad Pública de El Alto - UTIC | Todos los derechos reservados | Desarrollado por Ronaldo TC
            </footer>
        </div>

    </div>

    {{-- ============================================================
         HELPER GLOBAL: window.Modal
         Permite abrir/cerrar ventanas modales dinámicas desde JS
         ============================================================ --}}
    <script>
        window.Modal = (() => {
            let modalCount = 0;

            function open({
                title,
                body,
                footer,
                size = 'md',
                onClose,
                onOpen
            } = {}) {
                const previousFocus = document.activeElement;
                const titleId = `modal-dynamic-title-${++modalCount}`;
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-dynamic-backdrop';
                backdrop.innerHTML = `
                    <div class="modal-dynamic modal-dynamic--${size}" role="dialog" aria-modal="true" aria-labelledby="${titleId}">
                        <div class="modal-dynamic__header">
                            <h3 class="modal-dynamic__title" id="${titleId}"></h3>
                            <button type="button" class="modal-close" aria-label="Cerrar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="modal-dynamic__body"></div>
                        <div class="modal-dynamic__footer"></div>
                    </div>`;

                backdrop.querySelector('.modal-dynamic__title').textContent = title || '';

                const bodyEl = backdrop.querySelector('.modal-dynamic__body');
                if (typeof body === 'string') bodyEl.innerHTML = body;
                else if (body instanceof Node) bodyEl.appendChild(body);

                const footerEl = backdrop.querySelector('.modal-dynamic__footer');
                if (typeof footer === 'string') footerEl.innerHTML = footer;
                else if (footer instanceof Node) footerEl.appendChild(footer);

                let isClosing = false;

                function close() {
                    if (isClosing) return;
                    isClosing = true;
                    backdrop.querySelectorAll('button').forEach(button => button.disabled = true);
                    document.removeEventListener('keydown', handleKeydown, true);
                    backdrop.querySelector('.modal-dynamic').classList.add('is-closing');
                    setTimeout(() => {
                        backdrop.remove();
                        if (!document.querySelector('.modal-dynamic-backdrop')) {
                            document.body.classList.remove('has-modal');
                        }
                        if (previousFocus?.isConnected) previousFocus.focus();
                        onClose?.();
                    }, 180);
                }

                backdrop.querySelector('.modal-close').addEventListener('click', close);
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) close();
                });

                function handleKeydown(e) {
                    const modals = document.querySelectorAll('.modal-dynamic-backdrop');
                    if (modals[modals.length - 1] !== backdrop) return;
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        close();
                    } else if (e.key === 'Tab') {
                        const focusable = [...backdrop.querySelectorAll('button, a[href], input, select, textarea, [tabindex="0"]')]
                            .filter(element => !element.disabled && element.getClientRects().length);
                        const first = focusable[0];
                        const last = focusable[focusable.length - 1];
                        if (e.shiftKey && document.activeElement === first) {
                            e.preventDefault();
                            last?.focus();
                        } else if (!e.shiftKey && document.activeElement === last) {
                            e.preventDefault();
                            first?.focus();
                        }
                    }
                }

                document.addEventListener('keydown', handleKeydown, true);

                document.body.appendChild(backdrop);
                document.body.classList.add('has-modal');
                backdrop.querySelector('.modal-close').focus();
                onOpen?.(backdrop);
                return {
                    close,
                    root: backdrop
                };
            }

            /* Confirm dialog estilizado */
            function confirm({
                title = '¿Estás seguro?',
                text = '',
                record = '',
                confirmText = 'Confirmar',
                cancelText = 'Cancelar',
                variant = 'warning'
            } = {}) {
                return new Promise((resolve) => {
                    const m = open({
                        title,
                        size: 'sm',
                        body: `<div class="confirm-dialog">
                            <div class="confirm-dialog__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                            <div class="confirm-dialog__record" hidden></div>
                            <div class="confirm-dialog__text"></div>
                        </div>`,
                        footer: `
                            <button type="button" class="btn btn-secondary" data-cancel></button>
                            <button type="button" class="btn" data-confirm></button>
                        `,
                        onClose: () => resolve(false),
                    });
                    const dialog = m.root.querySelector('.modal-dynamic');
                    const description = m.root.querySelector('.confirm-dialog__text');
                    const recordElement = m.root.querySelector('.confirm-dialog__record');
                    const cancelButton = m.root.querySelector('[data-cancel]');
                    const confirmButton = m.root.querySelector('[data-confirm]');
                    const safeVariant = ['danger', 'success', 'warning'].includes(variant) ? variant : 'warning';
                    dialog.classList.add('modal-dynamic--confirm');
                    m.root.querySelector('.confirm-dialog').classList.add(`confirm-dialog--${safeVariant}`);
                    description.textContent = text;
                    description.id = `${dialog.getAttribute('aria-labelledby')}-description`;
                    dialog.setAttribute('aria-describedby', description.id);
                    recordElement.textContent = record;
                    recordElement.hidden = !record;
                    cancelButton.textContent = cancelText;
                    confirmButton.textContent = confirmText;
                    confirmButton.classList.add(`btn-${safeVariant === 'danger' ? 'danger' : safeVariant === 'success' ? 'primary' : 'warning'}`);
                    if (safeVariant === 'danger') {
                        m.root.querySelector('.confirm-dialog__icon svg').innerHTML = '<path d="M3 6h18M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M5 6l1 14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1l1-14M10 10v7M14 10v7"/>';
                    } else if (safeVariant === 'success') {
                        m.root.querySelector('.confirm-dialog__icon svg').innerHTML = '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>';
                    }
                    cancelButton.focus();
                    m.root.querySelector('[data-cancel]').addEventListener('click', () => m.close());
                    m.root.querySelector('[data-confirm]').addEventListener('click', () => {
                        confirmButton.disabled = true;
                        resolve(true);
                        m.close();
                    });
                });
            }

            return {
                open,
                confirm
            };
        })();
    </script>

    @include('layouts.partials.scripts')
    @stack('scripts')
</body>

</html>
