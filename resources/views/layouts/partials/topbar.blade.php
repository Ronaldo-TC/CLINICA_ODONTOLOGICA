{{--
    ============================================================
    TOPBAR · Encabezado, breadcrumb, buscador global y menús
    ============================================================
    Datos inyectados por el View Composer (AppServiceProvider):
      @var \App\Models\Usuario|null $sidebarUser
      @var string|null              $sidebarRole      (descripción legible)
      @var string|null              $sidebarRoleName
      @var array                    $sidebarMenu

    Funcionalidades incluidas:
      · Breadcrumb automático (sección › página actual)
      · Buscador global estilo "command palette" (Ctrl + K o clic)
      · Panel de notificaciones con estado leído/no leído recordado
      · Menú de usuario y menú de ayuda accesibles por teclado
      · Botón de tema con sincronización de aria-pressed
--}}
@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $user = $sidebarUser ?? null;
    $roleLabel = $sidebarRole ?? null;
    $menu = is_array($sidebarMenu ?? null) ? $sidebarMenu : [];

    $name = $user->nombre_completo ?? 'Usuario';
    $username = $user->nombre_usuario ?? '';
    $userFoto = $user->foto ?? null;

    $roleDescription = $user?->roles->first()?->descripcion ?? $roleLabel ?? 'Sin rol asignado';

    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    $avatarUrl = $userFoto ? asset('storage/' . $userFoto) : null;

    $currentRoute = request()->route()?->getName() ?? '';

    // ============================================================
    // Breadcrumb automático a partir del menú del usuario
    // ============================================================
    // ============================================================
    // Breadcrumb automático a partir del menú del usuario
    // Se elige la coincidencia MÁS específica para evitar que un
    // patrón amplio (insumos.*) gane sobre uno concreto.
    // ============================================================
    $crumbSection = '';
    $crumbCurrent = '';
    $crumbSectionLabel = '';
    $mejorPuntaje = -1;

    foreach ($menu as $indice => $item) {
        if (($item['type'] ?? 'link') === 'label') {
            $crumbSectionLabel = trim((string) ($item['label'] ?? ''));

            continue;
        }

        if (! empty($item['exclude']) && request()->routeIs($item['exclude'])) {
            continue;
        }

        $pattern = $item['pattern'] ?? null;
        $route = $item['route'] ?? null;
        $coincidePatron = $pattern && request()->routeIs($pattern);
        $coincideRuta = is_string($route) && $route !== '' && $route === $currentRoute;

        if (! $coincidePatron && ! $coincideRuta) {
            continue;
        }

        // Puntaje: contar segmentos literales del patrón + prioridad por orden
        $puntaje = ($coincideRuta ? 100 : 0)
            + ($coincidePatron ? substr_count((string) $pattern, '.') : 0)
            + $indice;

        if ($puntaje <= $mejorPuntaje) {
            continue;
        }

        $mejorPuntaje = $puntaje;
        $crumbSection = $crumbSectionLabel;
        $crumbCurrent = (string) ($item['label'] ?? '');

        foreach (($item['children'] ?? []) as $child) {
            $childMatches = (($child['pattern'] ?? null) && request()->routeIs($child['pattern']))
                || (($child['route'] ?? null) === $currentRoute);

            if ($childMatches) {
                $crumbCurrent = (string) ($child['label'] ?? '');
                break;
            }
        }
    }

    $hasBitacoras = RouteFacade::has('bitacoras.index');
    $canSeeBitacoras = $hasBitacoras && (auth()->user()?->can('bitacoras.ver') ?? false);

    // Notificaciones sin leer del usuario (campana del topbar)
    $noLeidas = 0;
    $tieneNotificaciones = RouteFacade::has('notificaciones.ultimas');

    if ($tieneNotificaciones && $user) {
        try {
            $noLeidas = \App\Models\Notificacion::query()
                ->delUsuario($user->id)
                ->noLeidas()
                ->count();
        } catch (\Throwable $e) {
            $noLeidas = 0;
        }
    }
@endphp

<header class="topbar">
    {{-- ================= MÓVIL: abrir drawer ================= --}}
    <button type="button"
        class="topbar-mobile-toggle"
        data-sidebar-open
        aria-label="Abrir menú de navegación"
        aria-controls="appSidebar"
        aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="15" y2="18" />
        </svg>
    </button>

    {{-- ================= TÍTULO + BREADCRUMB ================= --}}
    <div class="topbar-title">
        <nav class="topbar-breadcrumb" aria-label="Ubicación">
            <a href="{{ RouteFacade::has('dashboard') ? route('dashboard') : url('/') }}">Inicio</a>
            @if ($crumbSection !== '' && $crumbSection !== $crumbCurrent)
                <span class="crumb-sep" aria-hidden="true">/</span>
                <span>{{ $crumbSection }}</span>
            @endif
            @if ($crumbCurrent !== '')
                <span class="crumb-sep" aria-hidden="true">/</span>
                <span class="crumb-current" aria-current="page">@yield('page-title', $crumbCurrent)</span>
            @endif
        </nav>
        <h1 class="topbar-heading">@yield('page-title', $crumbCurrent ?: 'Panel')</h1>
        <p class="topbar-sub">@yield('page-subtitle', 'Clínica Odontológica · Gestión académica y clínica')</p>
    </div>

    {{-- ================= ACCIONES ================= --}}
    <div class="topbar-actions">

        {{-- ---------- Buscador global ---------- --}}
        <button type="button"
            class="topbar-search-trigger"
            id="paletteTrigger"
            data-open-palette
            aria-haspopup="dialog"
            aria-keyshortcuts="Control+K Meta+K">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <span class="search-trigger-text">Buscar en el sistema…</span>
            <span class="search-trigger-keys" aria-hidden="true">
                <kbd class="kbd">Ctrl</kbd><kbd class="kbd">K</kbd>
            </span>
        </button>

        {{-- ---------- Notificaciones ---------- --}}
        <div class="topbar-menu" data-dropdown>
            <button type="button"
                class="topbar-icon-btn"
                data-dropdown-trigger
                aria-label="Notificaciones"
                aria-haspopup="true"
                aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <span class="badge-dot" data-notif-dot @if ($noLeidas === 0) hidden @endif aria-hidden="true"></span>
                <span class="badge-count" id="contadorNotificaciones" @if ($noLeidas === 0) hidden @endif aria-hidden="true">{{ $noLeidas > 99 ? '99+' : $noLeidas }}</span>
            </button>

            <div class="topbar-dropdown" data-dropdown-panel role="menu" aria-label="Notificaciones">
                <div class="dropdown-head">
                    <span class="dropdown-head-title">Notificaciones</span>
                    @if ($tieneNotificaciones)
                    <a href="{{ route('notificaciones.index', ['filtro' => 'no_leidas']) }}" class="dropdown-link">Ver todas</a>
                    @else
                    <button type="button" class="dropdown-link" data-notif-read>Marcar vistas</button>
                    @endif
                </div>
                <div class="dropdown-divider"></div>

                @if ($tieneNotificaciones)
                <div class="notif-lista" id="notifLista" aria-live="polite">
                    <p class="notif-cargando" id="notifCargando">Cargando…</p>
                </div>
                @else
                <div class="dropdown-empty">
                    <span class="dropdown-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                    </span>
                    <p class="dropdown-empty-title">Todo al día</p>
                    <p class="dropdown-empty-text">No tienes avisos pendientes.</p>
                </div>
                @endif

                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ $tieneNotificaciones ? route('notificaciones.index') : ($canSeeBitacoras ? route('bitacoras.index') : '#') }}"
                    role="menuitem"
                    @unless ($tieneNotificaciones || $canSeeBitacoras) onclick="event.preventDefault()" aria-disabled="true" @endunless>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                    {{ $tieneNotificaciones ? 'Abrir bandeja de notificaciones' : 'Ver bitácora de actividad' }}
                </a>
            </div>
        </div>

        {{-- ---------- Tema claro / oscuro ---------- --}}
        <button type="button"
            class="topbar-icon-btn theme-toggle"
            id="themeToggle"
            aria-label="Activar modo oscuro"
            aria-pressed="false"
            title="Activar modo oscuro">
            <svg class="theme-icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z" />
            </svg>
            <svg class="theme-icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4" />
                <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
            </svg>
        </button>

        {{-- ---------- Ayuda / atajos ---------- --}}
        <div class="topbar-menu topbar-help" data-dropdown>
            <button type="button"
                class="topbar-icon-btn"
                data-dropdown-trigger
                aria-label="Ayuda y atajos de teclado"
                aria-haspopup="true"
                aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M9.4 9.4a2.6 2.6 0 1 1 3.6 2.4c-.7.3-1 .8-1 1.6v.4" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </button>

            <div class="topbar-dropdown" data-dropdown-panel role="menu" aria-label="Ayuda">
                <div class="dropdown-head">
                    <span class="dropdown-head-title">Atajos de teclado</span>
                </div>
                <div class="dropdown-divider"></div>
                <ul class="shortcut-list">
                    <li><span>Buscar en el sistema</span> <span class="shortcut-keys"><kbd class="kbd">Ctrl</kbd><kbd class="kbd">K</kbd></span></li>
                    <li><span>Filtrar el menú lateral</span> <kbd class="kbd">/</kbd></li>
                    <li><span>Navegar el menú</span> <span class="shortcut-keys"><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd></span></li>
                    <li><span>Cerrar paneles</span> <kbd class="kbd">Esc</kbd></li>
                </ul>
            </div>
        </div>

        {{-- ---------- Menú de usuario ---------- --}}
        <div class="topbar-menu topbar-user" data-dropdown>
            <button type="button"
                class="topbar-user-btn"
                data-dropdown-trigger
                aria-label="Menú de usuario"
                aria-haspopup="true"
                aria-expanded="false">
                <span class="avatar-chip">
                    @if ($avatarUrl)
                        <img class="topbar-avatar"
                            src="{{ $avatarUrl }}"
                            alt=""
                            loading="lazy"
                            onerror="this.remove();">
                    @endif
                    <span class="topbar-avatar" aria-hidden="true">{{ $initials }}</span>
                </span>
                <span class="topbar-user-info">
                    <span class="topbar-user-name" title="{{ $name }}">{{ $name }}</span>
                    <span class="topbar-user-role" title="{{ $roleDescription }}">{{ $roleDescription }}</span>
                </span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="topbar-dropdown dropdown-user-panel" data-dropdown-panel role="menu" aria-label="Cuenta">
                <div class="dropdown-header">
                    <span class="avatar-chip">
                        @if ($avatarUrl)
                            <img class="topbar-avatar lg"
                                src="{{ $avatarUrl }}"
                                alt=""
                                loading="lazy"
                                onerror="this.remove();">
                        @endif
                        <span class="topbar-avatar lg" aria-hidden="true">{{ $initials }}</span>
                    </span>
                    <span class="dropdown-identity">
                        <span class="dropdown-name" title="{{ $name }}">{{ $name }}</span>
                        <span class="dropdown-email" title="{{ $username }}">{{ $username }}</span>
                        <span class="dropdown-role">{{ $roleDescription }}</span>
                    </span>
                </div>
                <div class="dropdown-divider"></div>

                @if (RouteFacade::has('auth.profile'))
                    <a href="{{ route('auth.profile') }}" class="dropdown-item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        Mi perfil
                    </a>
                @endif

                @if (RouteFacade::has('auth.settings'))
                    <a href="{{ route('auth.settings') }}" class="dropdown-item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        Configuración
                    </a>
                @endif

                <button type="button" class="dropdown-item" role="menuitem" data-theme-switch>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z" />
                    </svg>
                    <span data-theme-switch-label>Cambiar tema</span>
                </button>

                <div class="dropdown-divider"></div>

                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item danger" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- ==================================================================
     BUSCADOR GLOBAL (command palette)
     ================================================================== --}}
<div class="nav-palette" id="navPalette" role="dialog" aria-modal="true" aria-labelledby="navPaletteTitle" hidden>
    <div class="nav-palette-backdrop" data-palette-close></div>

    <div class="nav-palette-panel" role="document">
        <h2 class="sr-only" id="navPaletteTitle">Buscar en el sistema</h2>

        <div class="nav-palette-field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text"
                id="navPaletteInput"
                placeholder="Buscar módulos, páginas y acciones…"
                autocomplete="off"
                spellcheck="false"
                role="combobox"
                aria-expanded="true"
                aria-controls="navPaletteResults"
                aria-autocomplete="list"
                aria-label="Buscar en el sistema">
            <kbd class="kbd" data-palette-hint>Esc</kbd>
        </div>

        <div class="nav-palette-results" id="navPaletteResults" role="listbox" aria-label="Resultados"></div>

        <p class="nav-palette-hint">
            <span><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> navegar</span>
            <span><kbd class="kbd">Enter</kbd> abrir</span>
            <span><kbd class="kbd">Esc</kbd> cerrar</span>
        </p>
    </div>
</div>

@once
    @push('scripts')
        <script>
            /* ============================================================
               TOPBAR · breadcrumb, buscador global y menús desplegables
               ============================================================ */
            (function () {
                'use strict';

                /* ==================================================
                   1. Menús desplegables (notificaciones, ayuda, usuario)
                   ================================================== */
                const menus = Array.from(document.querySelectorAll('[data-dropdown]'));

                const panelOf = (menu) => menu.querySelector('[data-dropdown-panel]');

                const setMenu = (menu, open) => {
                    const panel = panelOf(menu);
                    const trigger = menu.querySelector('[data-dropdown-trigger]');
                    if (!panel) return;
                    panel.classList.toggle('is-open', open);
                    if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                const closeMenus = (except) => {
                    menus.forEach((menu) => {
                        if (menu !== except) setMenu(menu, false);
                    });
                };

                menus.forEach((menu) => {
                    const trigger = menu.querySelector('[data-dropdown-trigger]');
                    if (!trigger) return;

                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const willOpen = !panelOf(menu)?.classList.contains('is-open');
                        closeMenus(menu);
                        setMenu(menu, willOpen);
                    });

                    panelOf(menu)?.addEventListener('click', (event) => event.stopPropagation());
                });

                document.addEventListener('click', () => closeMenus());
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') closeMenus();
                });

                /* ==================================================
                   2. Notificaciones reales (campana del topbar)
                   ================================================== */
                const NOTIF_URLS = {
                    ultimas: @json(RouteFacade::has('notificaciones.ultimas') ? route('notificaciones.ultimas') : null),
                    leer: (id) => @json(RouteFacade::has('notificaciones.leer') ? url('/notificaciones') : '') + '/' + id + '/leer',
                    leerTodas: @json(RouteFacade::has('notificaciones.leer-todas') ? route('notificaciones.leer-todas') : null),
                };

                const notifDot = document.querySelector('[data-notif-dot]');
                const notifLista = document.getElementById('notifLista');
                const notifCargando = document.getElementById('notifCargando');
                const notifRead = document.querySelector('[data-notif-read]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const escaparHtml = (valor) => (valor ?? '')
                    .toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                const pintarContador = (noLeidas) => {
                    const globo = document.getElementById('contadorNotificaciones');
                    if (globo) {
                        globo.textContent = noLeidas > 99 ? '99+' : String(noLeidas);
                        globo.hidden = !(noLeidas > 0);
                    }
                    if (notifDot) notifDot.hidden = !(noLeidas > 0);
                };

                const TONOS = {
                    danger: 'badge-danger',
                    warning: 'badge-warning',
                    success: 'badge-success',
                    info: 'badge-info',
                };

                let notifCargadas = false;

                async function cargarNotificaciones() {
                    if (!NOTIF_URLS.ultimas || !notifLista) return;

                    try {
                        const res = await fetch(`${NOTIF_URLS.ultimas}?limite=10`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (!res.ok) throw new Error();
                        const data = await res.json();

                        pintarContador(data.no_leidas || 0);

                        const items = data.items || [];

                        if (items.length === 0) {
                            notifLista.innerHTML = `
                                <div class="dropdown-empty">
                                    <p class="dropdown-empty-title">Todo al día</p>
                                    <p class="dropdown-empty-text">No tienes avisos pendientes.</p>
                                </div>`;
                        } else {
                            notifLista.innerHTML = items.map((n) => `
                                <button type="button"
                                    class="notif-item ${n.leida ? '' : 'is-nueva'}"
                                    data-notif-id="${n.id}"
                                    data-notif-url="${escaparHtml(n.url || '')}">
                                    <span class="notif-icono ${TONOS[n.tono] || 'badge-info'}" aria-hidden="true"></span>
                                    <span class="notif-texto">
                                        <span class="notif-titulo">${escaparHtml(n.titulo)}</span>
                                        <span class="notif-mensaje">${escaparHtml(n.mensaje || '')}</span>
                                        <span class="notif-fecha">${escaparHtml(n.hace || n.fecha || '')}</span>
                                    </span>
                                </button>`).join('');
                        }

                        notifCargadas = true;
                    } catch (err) {
                        if (notifCargando) notifCargando.textContent = 'No se pudieron cargar los avisos.';
                    }
                }

                /* Carga diferida: sólo al abrir la campana la primera vez */
                document.querySelectorAll('[data-dropdown]').forEach((menu) => {
                    if (!menu.querySelector('[data-notif-dot]')) return;

                    menu.querySelector('[data-dropdown-trigger]')?.addEventListener('click', () => {
                        if (!notifCargadas) cargarNotificaciones();
                    });
                });

                /* Al hacer clic en un aviso: se marca leído y se navega a su URL */
                notifLista?.addEventListener('click', async (e) => {
                    const item = e.target.closest('[data-notif-id]');
                    if (!item) return;

                    const id = item.dataset.notifId;
                    const destino = item.dataset.notifUrl;

                    item.classList.remove('is-nueva');

                    try {
                        if (id && NOTIF_URLS.leer) {
                            const res = await fetch(NOTIF_URLS.leer(id), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            const data = await res.json();
                            if (data?.success) pintarContador(data.no_leidas || 0);
                        }
                    } catch { /* se navega igual */ }

                    if (destino) window.location.href = destino;
                });

                /* Botón "marcar vistas" (sólo cuando no hay módulo de notificaciones) */
                notifRead?.addEventListener('click', async () => {
                    if (NOTIF_URLS.leerTodas) {
                        try {
                            const res = await fetch(NOTIF_URLS.leerTodas, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            const data = await res.json();
                            if (data?.success) {
                                pintarContador(0);
                                window.mostrarToast?.('success', data.message);
                            }
                        } catch {
                            window.mostrarToast?.('error', 'No se pudieron marcar los avisos.');
                        }
                    }
                    closeMenus();
                });

                /* ==================================================
                   3. Buscador global (command palette)
                   ================================================== */
                const palette = document.getElementById('navPalette');
                const paletteInput = document.getElementById('navPaletteInput');
                const paletteResults = document.getElementById('navPaletteResults');
                const paletteHint = document.querySelector('[data-palette-hint]');
                const paletteOpeners = document.querySelectorAll('[data-open-palette]');

                /* --- Índice de navegación exportado por el sidebar --- */
                const readNavIndex = () => {
                    const node = document.getElementById('navMenuData');
                    if (!node) return [];
                    try {
                        const parsed = JSON.parse(node.textContent || '[]');
                        return Array.isArray(parsed) ? parsed : [];
                    } catch {
                        return [];
                    }
                };

                /* --- Acciones rápidas (siempre disponibles) --- */
                const quickActions = [
                    {
                        label: 'Cambiar tema claro / oscuro',
                        section: 'Acciones',
                        icon: 'moon',
                        keywords: 'tema oscuro claro apariencia',
                        run: () => document.getElementById('themeToggle')?.click(),
                    },
                    {
                        label: 'Abrir mi perfil',
                        section: 'Acciones',
                        icon: 'user',
                        keywords: 'perfil cuenta datos',
                        href: @json(RouteFacade::has('auth.profile') ? route('auth.profile') : null),
                    },
                    {
                        label: 'Abrir configuración',
                        section: 'Acciones',
                        icon: 'settings',
                        keywords: 'configuracion ajustes seguridad',
                        href: @json(RouteFacade::has('auth.settings') ? route('auth.settings') : null),
                    },
                    {
                        label: 'Cerrar sesión',
                        section: 'Acciones',
                        icon: 'logout',
                        keywords: 'salir logout terminar sesion',
                        run: () => document.querySelector('.dropdown-user-panel form')?.requestSubmit()
                            ?? document.querySelector('.sidebar-logout form')?.requestSubmit(),
                    },
                ];

                const normalize = (value) => (value || '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');

                const buildIndex = () => {
                    const navigation = [];
                    const actions = quickActions.map((action, index) => ({
                        id: 'action-' + index,
                        label: action.label,
                        section: 'Acciones rápidas',
                        keywords: normalize(action.keywords),
                        href: action.href || null,
                        icon: action.icon,
                        run: action.run || null,
                        badge: null,
                    }));

                    readNavIndex().forEach((item, index) => {
                        const section = item.section || 'Navegación';

                        if (Array.isArray(item.children) && item.children.length > 0) {
                            item.children.forEach((child, childIndex) => {
                                navigation.push({
                                    id: 'nav-' + index + '-' + childIndex,
                                    label: child.label,
                                    section: section,
                                    breadcrumb: item.label,
                                    keywords: normalize(`${item.label} ${child.label}`),
                                    href: child.href,
                                    icon: child.icon || item.icon || 'grid',
                                    badge: null,
                                });
                            });
                        }

                        if (item.href) {
                            navigation.push({
                                id: 'nav-' + index,
                                label: item.label,
                                section: section,
                                breadcrumb: '',
                                keywords: normalize(`${item.label} ${section}`),
                                href: item.href,
                                icon: item.icon || 'grid',
                                badge: item.badge || null,
                            });
                        }
                    });

                    return { navigation, actions };
                };

                const index = buildIndex();

                const scoreEntry = (entry, needle) => {
                    if (!needle) return 0;
                    const label = normalize(entry.label);
                    const haystack = normalize([entry.label, entry.section, entry.breadcrumb, entry.keywords].join(' '));

                    if (label === needle) return 100;
                    if (label.startsWith(needle)) return 80;
                    if (label.includes(needle)) return 60;
                    if (haystack.includes(needle)) return 30;
                    return -1;
                };

                const matches = (needle) => {
                    const pool = index.navigation
                        .map((entry) => ({ entry, score: scoreEntry(entry, needle) }))
                        .filter((item) => item.score >= 0);

                    // Agrupa por sección conservando el orden de aparición
                    const groups = new Map();
                    pool.forEach(({ entry }) => {
                        if (!groups.has(entry.section)) groups.set(entry.section, []);
                        groups.get(entry.section).push(entry);
                    });

                    return groups;
                };

                /* --- Iconos de la paleta --- */
                const PALETTE_ICONS = {
                    grid: '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />',
                    user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />',
                    settings: '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82 2 2 0 1 1-2.83 2.83 1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51 2 2 0 1 1-4 0 1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33 2 2 0 1 1-2.83-2.83 1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1 2 2 0 1 1 0-4 1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82 2 2 0 1 1 2.83-2.83 1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51 2 2 0 1 1 4 0 1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33 2 2 0 1 1 2.83 2.83 1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1 2 2 0 1 1 0 4 1.65 1.65 0 0 0-1.51 1z" />',
                    moon: '<path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z" />',
                    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />',
                };

                const entryIcon = (icon) => PALETTE_ICONS[icon] || PALETTE_ICONS.grid;

                const escapeHtml = (value) => (value || '')
                    .toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                let cursor = 0;
                let renderedEntries = [];

                const renderResults = () => {
                    if (!paletteResults) return;

                    const needle = normalize(paletteInput?.value.trim() || '');
                    const groups = matches(needle);
                    const flat = [];

                    let html = '';

                    if (groups.size === 0) {
                        html = `
                            <div class="palette-empty">
                                <p class="palette-empty-title">Sin resultados</p>
                                <p class="palette-empty-text">Prueba con el nombre de un módulo: pacientes, cajas, historias…</p>
                            </div>`;
                    } else {
                        groups.forEach((entries, section) => {
                            html += `<p class="palette-section">${escapeHtml(section)}</p>`;
                            entries.forEach((entry) => {
                                const position = flat.length;
                                flat.push(entry);
                                const badge = entry.badge
                                    ? `<span class="palette-badge">${escapeHtml(entry.badge)}</span>`
                                    : '';
                                const crumb = entry.breadcrumb
                                    ? `<span class="palette-crumb">${escapeHtml(entry.breadcrumb)}</span>`
                                    : '';

                                html += `
                                    <button type="button"
                                        class="palette-item"
                                        role="option"
                                        aria-selected="false"
                                        data-result-index="${position}">
                                        <span class="palette-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${entryIcon(entry.icon)}</svg>
                                        </span>
                                        <span class="palette-text">${escapeHtml(entry.label)}</span>
                                        ${crumb}
                                        ${badge}
                                        <span class="palette-open" aria-hidden="true">↵</span>
                                    </button>`;
                            });
                        });
                    }

                    paletteResults.innerHTML = html;
                    renderedEntries = flat;

                    if (paletteHint) paletteHint.textContent = 'Esc';
                    cursor = 0;
                    setCursor(0);
                };

                const resultItems = () => paletteResults
                    ? Array.from(paletteResults.querySelectorAll('[data-result-index]'))
                    : [];

                function setCursor(indexPosition) {
                    const items = resultItems();
                    if (items.length === 0) return;

                    cursor = (indexPosition + items.length) % items.length;

                    items.forEach((item, position) => {
                        const on = position === cursor;
                        item.classList.toggle('is-cursor', on);
                        item.setAttribute('aria-selected', on ? 'true' : 'false');
                        if (on) item.scrollIntoView({ block: 'nearest' });
                    });
                }

                const openPalette = () => {
                    if (!palette) return;
                    closeMenus();
                    palette.hidden = false;
                    document.body.classList.add('palette-open');
                    renderResults();
                    requestAnimationFrame(() => paletteInput?.focus());
                };

                const closePalette = () => {
                    if (!palette || palette.hidden) return;
                    palette.hidden = true;
                    document.body.classList.remove('palette-open');
                    if (paletteInput) paletteInput.value = '';
                    document.getElementById('paletteTrigger')?.focus();
                };

                const activate = (item) => {
                    const id = item?.dataset.resultIndex;
                    if (id === undefined) return;

                    const entry = renderedEntries[Number(id)];
                    if (!entry) return;

                    if (typeof entry.run === 'function') {
                        closePalette();
                        entry.run();
                        return;
                    }

                    if (entry.href) {
                        window.location.href = entry.href;
                    }
                };

                paletteOpeners.forEach((opener) => {
                    opener.addEventListener('click', (event) => {
                        event.preventDefault();
                        openPalette();
                    });
                });

                document.querySelectorAll('[data-palette-close]').forEach((closer) => {
                    closer.addEventListener('click', closePalette);
                });

                paletteInput?.addEventListener('input', renderResults);

                paletteInput?.addEventListener('keydown', (event) => {
                    switch (event.key) {
                        case 'ArrowDown':
                            event.preventDefault();
                            setCursor(cursor + 1);
                            break;
                        case 'ArrowUp':
                            event.preventDefault();
                            setCursor(cursor - 1);
                            break;
                        case 'Home':
                            event.preventDefault();
                            setCursor(0);
                            break;
                        case 'End':
                            event.preventDefault();
                            setCursor(resultItems().length - 1);
                            break;
                        case 'Enter':
                            event.preventDefault();
                            activate(resultItems()[cursor]);
                            break;
                        case 'Escape':
                            event.preventDefault();
                            closePalette();
                            break;
                        default:
                            break;
                    }
                });

                paletteResults?.addEventListener('click', (event) => {
                    const item = event.target.closest('[data-result-index]');
                    if (item) activate(item);
                });

                /* --- Atajo global Ctrl/Cmd + K --- */
                document.addEventListener('keydown', (event) => {
                    const isPaletteKey = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k';
                    if (isPaletteKey) {
                        event.preventDefault();
                        palette?.hidden ? openPalette() : closePalette();
                        return;
                    }

                    if (event.key === 'Escape' && palette && !palette.hidden) {
                        closePalette();
                    }
                });

                /* ==================================================
                   4. Tema claro / oscuro
                   ================================================== */
                const THEME_KEY = 'upea-theme';
                const themeToggle = document.getElementById('themeToggle');
                const themeSwitch = document.querySelector('[data-theme-switch]');
                const themeLabel = document.querySelector('[data-theme-switch-label]');
                const themeRoot = document.documentElement;
                let themeTransitionTimer;

                const isDark = () => document.documentElement.dataset.theme === 'dark';

                const syncThemeControls = () => {
                    const dark = isDark();
                    const nextLabel = dark ? 'Activar modo claro' : 'Activar modo oscuro';

                    if (themeToggle) {
                        themeToggle.setAttribute('aria-pressed', String(dark));
                        themeToggle.setAttribute('aria-label', nextLabel);
                        themeToggle.title = nextLabel;
                    }

                    if (themeLabel) {
                        themeLabel.textContent = dark ? 'Usar tema claro' : 'Usar tema oscuro';
                    }
                };

                const applyTheme = (theme) => {
                    const shouldAnimate = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    window.clearTimeout(themeTransitionTimer);
                    themeRoot.classList.toggle('theme-transitioning', shouldAnimate);
                    themeRoot.dataset.theme = theme;
                    themeRoot.style.colorScheme = theme;
                    try {
                        localStorage.setItem(THEME_KEY, theme);
                    } catch { /* sin almacenamiento */ }
                    syncThemeControls();

                    if (shouldAnimate) {
                        themeTransitionTimer = window.setTimeout(() => {
                            themeRoot.classList.remove('theme-transitioning');
                        }, 320);
                    }
                };

                syncThemeControls();

                themeToggle?.addEventListener('click', () => {
                    applyTheme(isDark() ? 'light' : 'dark');
                });

                themeSwitch?.addEventListener('click', () => {
                    applyTheme(isDark() ? 'light' : 'dark');
                    closeMenus();
                });
            })();
        </script>
    @endpush
@endonce
