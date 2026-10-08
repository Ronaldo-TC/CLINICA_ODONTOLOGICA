{{--
    ============================================================
    SIDEBAR · Navegación principal de la Clínica Odontológica UPEA
    ============================================================
    Datos inyectados por el View Composer (AppServiceProvider):
      @var \App\Models\Usuario|null $sidebarUser
      @var string|null              $sidebarRole      (descripción legible)
      @var string|null              $sidebarRoleName  (admin, docente, ...)
      @var array                    $sidebarMenu

    Funcionalidades incluidas:
      · Filtro rápido de opciones (con atajo "/")
      · Submenús plegables con estado recordado (localStorage)
      · Indicador de sección activa + scroll automático al ítem activo
      · Navegación con teclado (↑ ↓ Inicio Fin)
      · Tooltips (title) en ítems recortados
      · Backdrop y cierre automático en móvil
      · Menú exportado a JSON para el buscador global del topbar
--}}
@php
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

$user = $sidebarUser ?? null;
$roleLabel = $sidebarRole ?? null;
$roleName = $sidebarRoleName ?? null;
$menu = is_array($sidebarMenu ?? null) ? $sidebarMenu : [];

$name = $user->nombre_completo ?? 'Usuario';
$userFoto = $user->foto ?? null;

$initials = collect(explode(' ', trim($name)))
->filter()
->take(2)
->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
->implode('');

$avatarUrl = $userFoto ? asset('storage/' . $userFoto) : null;

// ============================================================
// Iconos SVG (stroke · viewBox 24x24)
// ============================================================
$icons = [
'grid' => '
<rect x="3" y="3" width="7" height="7" />
<rect x="14" y="3" width="7" height="7" />
<rect x="14" y="14" width="7" height="7" />
<rect x="3" y="14" width="7" height="7" />',
'users' => '
<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
<circle cx="9" cy="7" r="4" />
<path d="M23 21v-2a4 4 0 0 0-3-3.87" />
<path d="M16 3.13a4 4 0 0 1 0 7.75" />',
'user' => '
<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
<circle cx="12" cy="7" r="4" />',
'user-plus' => '
<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
<circle cx="8.5" cy="7" r="4" />
<line x1="20" y1="8" x2="20" y2="14" />
<line x1="23" y1="11" x2="17" y2="11" />',
'shield' => '
<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />',
'layers' => '
<polygon points="12 2 2 7 12 12 22 7 12 2" />
<polyline points="2 17 12 22 22 17" />
<polyline points="2 12 12 17 22 12" />',
'chart' => '
<line x1="18" y1="20" x2="18" y2="10" />
<line x1="12" y1="20" x2="12" y2="4" />
<line x1="6" y1="20" x2="6" y2="14" />',
'settings' => '
<circle cx="12" cy="12" r="3" />
<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />',
'trending' => '
<polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
<polyline points="17 6 23 6 23 12" />',
'hospital' => '
<path d="M12 6v4" />
<path d="M14 8h-4" />
<path d="M3 21V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14" />
<path d="M3 21h18" />
<path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4" />',
'folder' => '
<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />',
'file-text' => '
<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
<polyline points="14 2 14 8 20 8" />
<line x1="16" y1="13" x2="8" y2="13" />
<line x1="16" y1="17" x2="8" y2="17" />',
'check-square' => '
<polyline points="9 11 12 14 22 4" />
<path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />',
'credit-card' => '
<rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
<line x1="1" y1="10" x2="23" y2="10" />',
'package' => '
<line x1="16.5" y1="9.4" x2="7.5" y2="4.21" />
<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
<polyline points="3.27 6.96 12 12.01 20.73 6.96" />
<line x1="12" y1="22.08" x2="12" y2="12" />',
'activity' => '
<polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />',
'clipboard' => '
<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
<rect x="8" y="2" width="8" height="4" rx="1" ry="1" />',
'receipt' => '
<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z" />
<line x1="8" y1="8" x2="16" y2="8" />
<line x1="8" y1="12" x2="16" y2="12" />
<line x1="8" y1="16" x2="13" y2="16" />',
'inbox' => '
<polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
<path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />',
'image' => '
<rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
<circle cx="8.5" cy="8.5" r="1.5" />
<polyline points="21 15 16 10 5 21" />',
'code' => '
<polyline points="16 18 22 12 16 6" />
<polyline points="8 6 2 12 8 18" />',
'briefcase' => '
<rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />',
'key' => '
<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />',
'database' => '
<ellipse cx="12" cy="5" rx="9" ry="3" />
<path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
<path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />',
'book' => '
<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />',
'bell' => '
<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
<path d="M13.73 21a2 2 0 0 1-3.46 0" />',
'log-out' => '
<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
<polyline points="16 17 21 12 16 7" />
<line x1="21" y1="12" x2="9" y2="12" />',
'search' => '
<circle cx="11" cy="11" r="8" />
<line x1="21" y1="21" x2="16.65" y2="16.65" />',
'x' => '
<line x1="18" y1="6" x2="6" y2="18" />
<line x1="6" y1="6" x2="18" y2="18" />',
];

$icon = static fn (string $key): string => $icons[$key] ?? $icons['grid'];

$currentRoute = request()->route()?->getName() ?? '';

// ============================================================
// Contexto del rol → color del indicador
// ============================================================
$roleToneMap = [
'admin' => 'teal',
'administrador' => 'teal',
'director' => 'fuchsia',
'docente' => 'cyan',
'estudiante' => 'amber',
'subadmin' => 'cyan',
];
$roleTone = $roleToneMap[strtolower((string) $roleName)] ?? 'teal';

// ============================================================
// Índice aplanado para el buscador global del topbar
// ============================================================
$navIndex = [];
$section = '';
$searchable = 0;

foreach ($menu as $item) {
if (($item['type'] ?? 'link') === 'label') {
$section = trim((string) ($item['label'] ?? ''));

continue;
}

$children = [];
foreach (($item['children'] ?? []) as $child) {
$childRoute = $child['route'] ?? null;
if (! is_string($childRoute) || $childRoute === '' || ! RouteFacade::has($childRoute)) {
continue;
}
$children[] = [
'label' => (string) ($child['label'] ?? ''),
'href' => route($childRoute),
'icon' => $child['icon'] ?? 'grid',
];
$searchable++;
}

$route = $item['route'] ?? null;
$hasRoute = is_string($route) && $route !== '' && RouteFacade::has($route);

if ($hasRoute) {
$searchable++;
}

$navIndex[] = [
'label' => (string) ($item['label'] ?? ''),
'section' => $section,
'icon' => $item['icon'] ?? 'grid',
'href' => $hasRoute ? route($route) : null,
'badge' => isset($item['badge']) ? (string) $item['badge'] : null,
'children' => $children,
];
}

$navIndex = array_values(array_filter($navIndex, static function (array $entry): bool {
return $entry['href'] !== null || ! empty($entry['children']);
}));
@endphp

<aside class="sidebar"
    id="appSidebar"
    data-current-route="{{ $currentRoute }}"
    aria-label="Navegación principal">
    <script type="application/json" id="navMenuData">
        {
            !!json_encode($navIndex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!
        }
    </script>

    {{-- ================= MARCA ================= --}}
    <div class="sidebar-brand">
        <span class="brand-logo" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5.6c-1.5-1.4-3.4-1.9-4.9-1.2C5.2 5.1 4.4 7 4.7 9.4c.3 2.3 1.2 3.9 1.8 6.2.5 1.7.6 3.5.9 4.5.2.7 1.1.8 1.5.1.5-1 .8-2.3 1.3-3.5.4-.9 1-1.4 1.8-1.4s1.4.5 1.8 1.4c.5 1.2.8 2.5 1.3 3.5.4.7 1.3.6 1.5-.1.3-1 .4-2.8.9-4.5.6-2.3 1.5-3.9 1.8-6.2.3-2.4-.5-4.3-2.4-5-1.5-.7-3.4-.2-4.9 1.2Z" />
            </svg>
        </span>
        <span class="brand-text">
            <span class="brand-title">Clínica Odontológica</span>
            <span class="brand-sub">UPEA · UTIC</span>
        </span>
        <button type="button"
            class="sidebar-close"
            data-sidebar-close
            aria-label="Cerrar menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                {!! $icon('x') !!}
            </svg>
        </button>
    </div>

    {{-- ================= TARJETA DE USUARIO ================= --}}
    @if ($user)
        <div class="sidebar-user">
            <span class="user-avatar-wrap">
                @if ($avatarUrl)
                    <img class="avatar avatar-md"
                        src="{{ $avatarUrl }}"
                        alt="Foto de {{ $name }}"
                        loading="lazy"
                        onerror="this.remove();">
                @endif
                <span class="avatar avatar-md" aria-hidden="true">{{ $initials }}</span>
            </span>
            <span class="user-info">
                <span class="user-name" title="{{ $name }}">{{ $name }}</span>
                <span class="user-role">
                    <span class="role-dot role-dot--{{ $roleTone }}" aria-hidden="true"></span>
                    <span title="{{ $roleLabel ?? 'Sin rol asignado' }}">{{ $roleLabel ?? 'Sin rol asignado' }}</span>
                </span>
            </span>
            @if (RouteFacade::has('auth.profile'))
                <a href="{{ route('auth.profile') }}"
                    class="user-quick"
                    title="Ir a mi perfil"
                    aria-label="Ir a mi perfil">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        {!! $icon('user') !!}
                    </svg>
                </a>
            @endif
        </div>
    @endif

    {{-- ================= FILTRO RÁPIDO ================= --}}
    <!-- @if ($searchable > 0)
        <div class="sidebar-tools">
            <div class="sidebar-filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {!! $icon('search') !!}
                </svg>
                <input type="search"
                    id="sidebarFilter"
                    placeholder="Filtrar opciones…"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Filtrar opciones del menú"
                    aria-controls="sidebarNav">
                <button type="button"
                    class="sidebar-filter-clear"
                    data-sidebar-filter-clear
                    hidden
                    aria-label="Limpiar filtro">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        {!! $icon('x') !!}
                    </svg>
                </button>
            </div>
            <p class="sidebar-hint">Pulsa <kbd class="kbd">/</kbd> para filtrar · <kbd class="kbd">Ctrl</kbd><kbd class="kbd">K</kbd> para buscar</p>
        </div>
    @endif -->

    {{-- ================= NAVEGACIÓN ================= --}}
    <nav class="sidebar-nav" id="sidebarNav">
        @forelse ($menu as $item)
        @php
        $type = $item['type'] ?? 'link';
        $label = (string) ($item['label'] ?? '');
        $iconKey = $item['icon'] ?? 'grid';
        $badge = $item['badge'] ?? null;
        $children = $item['children'] ?? [];
        @endphp

        @if ($type === 'label')
        @if (trim($label) !== '')
        <p class="nav-label" data-nav-section>{{ $label }}</p>
        @endif
        @continue
        @endif

        {{-- ------------- Grupo con submenú ------------- --}}
        @if (! empty($children))
        @php
        $groupActive = false;
        foreach ($children as $child) {
        $childRoute = $child['route'] ?? null;
        $childPattern = $child['pattern'] ?? null;
        if (is_string($childRoute) && $childRoute !== '' && $currentRoute === $childRoute) {
        $groupActive = true;
        break;
        }
        if ($childPattern && request()->routeIs($childPattern)) {
        $groupActive = true;
        break;
        }
        }
        $groupId = 'nav-group-' . Str::slug($label ?: 'grupo');
        $groupTerms = mb_strtolower($label . ' ' . collect($children)->pluck('label')->implode(' '));
        @endphp

        <div class="nav-group {{ $groupActive ? 'is-active' : '' }}"
            data-nav-group
            data-nav-group-id="{{ $groupId }}"
            data-nav-search="{{ $groupTerms }}">
            <button type="button"
                class="nav-item nav-item-toggle"
                aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                aria-controls="{{ $groupId }}"
                data-nav-search="{{ $groupTerms }}">
                <span class="nav-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        {!! $icon($iconKey) !!}
                    </svg>
                </span>
                <span class="nav-text" title="{{ $label }}">{{ $label }}</span>
                @if ($badge)
                <span class="nav-badge">{{ $badge }}</span>
                @endif
                <span class="nav-caret" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </span>
            </button>

            <div class="nav-children" id="{{ $groupId }}" role="group">
                @foreach ($children as $child)
                @php
                $childRoute = $child['route'] ?? null;
                $childLabel = (string) ($child['label'] ?? '');
                $childPattern = $child['pattern'] ?? null;
                $childHasRoute = is_string($childRoute) && $childRoute !== '' && RouteFacade::has($childRoute);
                $childHref = $childHasRoute ? route($childRoute) : '#';
                $childActive = $childHasRoute && (
                $currentRoute === $childRoute
                || ($childPattern && request()->routeIs($childPattern))
                );
                @endphp
                <a href="{{ $childHref }}"
                    class="nav-item nav-item-child {{ $childActive ? 'active' : '' }} {{ $childHasRoute ? '' : 'disabled' }}"
                    @if ($childActive) aria-current="page" @endif
                    @if (! $childHasRoute) onclick="event.preventDefault()" aria-disabled="true" tabindex="-1" @endif
                    data-nav-search="{{ mb_strtolower($childLabel) }}">
                    <span class="nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            {!! $icon($child['icon'] ?? 'grid') !!}
                        </svg>
                    </span>
                    <span class="nav-text" title="{{ $childLabel }}">{{ $childLabel }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @continue
        @endif

        {{-- ------------- Enlace simple ------------- --}}
        @php
        $route = $item['route'] ?? null;
        $pattern = $item['pattern'] ?? null;
        $exclude = $item['exclude'] ?? null;
        $hasRoute = is_string($route) && $route !== '' && RouteFacade::has($route);
        $href = $hasRoute ? route($route) : '#';
        $excluido = $exclude && request()->routeIs($exclude);
        $isActive = $hasRoute && ! $excluido && (
        $currentRoute === $route
        || ($pattern && request()->routeIs($pattern))
        );
        @endphp

        <a href="{{ $href }}"
            class="nav-item {{ $isActive ? 'active' : '' }} {{ $hasRoute ? '' : 'disabled' }}"
            @if ($isActive) aria-current="page" @endif
            @if (! $hasRoute) onclick="event.preventDefault()" aria-disabled="true" tabindex="-1" @endif
            data-nav-search="{{ mb_strtolower($label) }}">
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icon($iconKey) !!}
                </svg>
            </span>
            <span class="nav-text" title="{{ $label }}">{{ $label }}</span>
            @if ($badge)
            <span class="nav-badge">{{ $badge }}</span>
            @endif
        </a>
        @empty
        <p class="nav-empty">No hay opciones disponibles para tu rol.</p>
        @endforelse

        <p class="nav-empty nav-empty--filter" data-nav-filter-empty hidden>
            Sin coincidencias para tu búsqueda.
        </p>
    </nav>

    {{-- ================= PIE ================= --}}
    <div class="sidebar-footer">
        <button type="button"
            class="sidebar-shortcut"
            data-open-palette
            title="Buscar en el sistema (Ctrl + K)">
            <span class="shortcut-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icon('search') !!}
                </svg>
            </span>
            <span class="shortcut-text">Buscar</span>
            <kbd class="kbd">Ctrl</kbd><kbd class="kbd">K</kbd>
        </button>

        <form method="POST" action="{{ route('auth.logout') }}" class="sidebar-logout">
            @csrf
            <button type="submit" class="logout-btn" title="Cerrar sesión">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {!! $icon('log-out') !!}
                </svg>
                <span>Cerrar sesión</span>
            </button>
        </form>

        <p class="sidebar-meta">
            <span class="meta-dot" aria-hidden="true"></span>
            {{ $roleName ? ucfirst((string) $roleName) : 'Sesión activa' }}
        </p>
    </div>
</aside>

@once
@push('scripts')
<script>
    /* ============================================================
               SIDEBAR · filtro, submenús, teclado y drawer móvil
               ============================================================ */
    (function() {
        const sidebar = document.getElementById('appSidebar');
        if (!sidebar || sidebar.dataset.ready === '1') return;

        const STORAGE_GROUPS = 'upea:nav:groups';
        const MOBILE_QUERY = window.matchMedia('(max-width: 1024px)');
        const filterInput = document.getElementById('sidebarFilter');
        const filterClear = sidebar.querySelector('[data-sidebar-filter-clear]');
        const filterEmpty = sidebar.querySelector('[data-nav-filter-empty]');
        const nav = document.getElementById('sidebarNav');
        const backdrop = () => document.getElementById('sidebarBackdrop');

        /* ---------------- Utilidades ---------------- */
        const readGroups = () => {
            try {
                const parsed = JSON.parse(localStorage.getItem(STORAGE_GROUPS) || '{}');
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch {
                return {};
            }
        };

        const writeGroups = (state) => {
            try {
                localStorage.setItem(STORAGE_GROUPS, JSON.stringify(state));
            } catch {
                /* almacenamiento no disponible */ }
        };

        const setExpanded = (group, expanded) => {
            const toggle = group.querySelector('.nav-item-toggle');
            group.classList.toggle('is-open', expanded);
            if (toggle) toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        };

        const isMobile = () => MOBILE_QUERY.matches;

        /* ---------------- Estado inicial de submenús ---------------- */
        const storedGroups = readGroups();
        sidebar.querySelectorAll('[data-nav-group]').forEach((group) => {
            const remembered = storedGroups[group.dataset.navGroupId || ''];
            const active = group.classList.contains('is-active');
            setExpanded(group, typeof remembered === 'boolean' ? remembered : active);
        });

        /* ---------------- Toggle de submenús (acordeón en escritorio) ---------------- */
        sidebar.addEventListener('click', (event) => {
            const toggle = event.target.closest('.nav-item-toggle');
            if (!toggle) return;

            event.preventDefault();
            const group = toggle.closest('[data-nav-group]');
            if (!group) return;

            const willOpen = !group.classList.contains('is-open');

            if (willOpen && !isMobile()) {
                sidebar.querySelectorAll('[data-nav-group].is-open').forEach((other) => {
                    if (other !== group) setExpanded(other, false);
                });
            }

            setExpanded(group, willOpen);

            const state = readGroups();
            state[group.dataset.navGroupId || ''] = willOpen;
            writeGroups(state);
        });

        /* ---------------- Filtro rápido ---------------- */
        const applyFilter = (rawTerm) => {
            const term = (rawTerm || '').trim().toLowerCase();
            let visible = 0;

            sidebar.querySelectorAll('[data-nav-group]').forEach((group) => {
                const childrenMatch = Array.from(group.querySelectorAll('.nav-item-child')).some((child) => {
                    const match = term === '' || (child.dataset.navSearch || '').includes(term);
                    child.hidden = !match;
                    return match;
                });
                const selfMatch = (group.dataset.navSearch || '').includes(term);
                const show = term === '' || childrenMatch || selfMatch;
                group.hidden = !show;
                if (show) visible++;
                if (term !== '' && show) setExpanded(group, true);
            });

            sidebar.querySelectorAll('.sidebar-nav > .nav-item').forEach((link) => {
                const match = term === '' || (link.dataset.navSearch || '').includes(term);
                link.hidden = !match;
                if (match) visible++;
            });

            sidebar.querySelectorAll('[data-nav-section]').forEach((sectionLabel) => {
                let hasContent = false;
                let node = sectionLabel.nextElementSibling;
                while (node && !node.hasAttribute('data-nav-section')) {
                    if (!node.hidden) {
                        hasContent = true;
                        break;
                    }
                    node = node.nextElementSibling;
                }
                sectionLabel.hidden = !hasContent;
            });

            if (filterClear) filterClear.hidden = term === '';
            if (filterEmpty) filterEmpty.hidden = !(term !== '' && visible === 0);
        };

        if (filterInput) {
            filterInput.addEventListener('input', (event) => applyFilter(event.target.value));
            filterInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                filterInput.value = '';
                applyFilter('');
                filterInput.blur();
            });
        }

        if (filterClear) {
            filterClear.addEventListener('click', () => {
                if (filterInput) {
                    filterInput.value = '';
                    filterInput.focus();
                }
                applyFilter('');
            });
        }

        /* ---------------- Atajo "/" para filtrar ---------------- */
        document.addEventListener('keydown', (event) => {
            if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) return;

            const target = event.target;
            const tag = (target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable) return;
            if (event.key !== '/' || !filterInput) return;

            event.preventDefault();
            if (isMobile()) window.upeaSidebar?.open();
            filterInput.focus();
            filterInput.select();
        });

        /* ---------------- Navegación con teclado ---------------- */
        const focusables = () => Array.from(
            sidebar.querySelectorAll('.sidebar-nav .nav-item')
        ).filter((el) => !el.hidden && !el.hasAttribute('tabindex') && el.offsetParent !== null);

        if (nav) {
            nav.addEventListener('keydown', (event) => {
                const items = focusables();
                if (items.length === 0) return;

                const current = items.indexOf(document.activeElement);
                let next = null;

                switch (event.key) {
                    case 'ArrowDown':
                        next = items[(current + 1 + items.length) % items.length];
                        break;
                    case 'ArrowUp':
                        next = items[(current - 1 + items.length) % items.length];
                        break;
                    case 'Home':
                        next = items[0];
                        break;
                    case 'End':
                        next = items[items.length - 1];
                        break;
                    default:
                        return;
                }

                event.preventDefault();
                next.focus();
            });
        }

        /* ---------------- Scroll hacia el ítem activo ---------------- */
        requestAnimationFrame(() => {
            const active = sidebar.querySelector('.sidebar-nav .nav-item.active');
            if (!active) return;

            const box = sidebar.getBoundingClientRect();
            const el = active.getBoundingClientRect();
            if (el.top < box.top + 80 || el.bottom > box.bottom - 80) {
                active.scrollIntoView({
                    block: 'center'
                });
            }
        });

        /* ---------------- Drawer móvil ---------------- */
        const topbarToggle = document.querySelector('.topbar-mobile-toggle');

        const open = () => {
            sidebar.classList.add('is-open');
            const layer = backdrop();
            if (layer) layer.hidden = false;
            if (topbarToggle) topbarToggle.setAttribute('aria-expanded', 'true');
            if (isMobile()) document.body.classList.add('drawer-open');
        };

        const close = () => {
            sidebar.classList.remove('is-open');
            const layer = backdrop();
            if (layer) layer.hidden = true;
            if (topbarToggle) topbarToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('drawer-open');
        };

        sidebar.classList.remove('open');

        document.querySelectorAll('[data-sidebar-open]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                sidebar.classList.contains('is-open') ? close() : open();
            });
        });

        sidebar.querySelectorAll('[data-sidebar-close]').forEach((trigger) => {
            trigger.addEventListener('click', close);
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest && event.target.closest('#sidebarBackdrop')) close();
        });

        sidebar.addEventListener('click', (event) => {
            if (!isMobile()) return;
            if (event.target.closest('a.nav-item')) close();
        });

        const handleViewportChange = () => {
            if (isMobile()) return;
            close();
        };

        if (typeof MOBILE_QUERY.addEventListener === 'function') {
            MOBILE_QUERY.addEventListener('change', handleViewportChange);
        } else if (typeof MOBILE_QUERY.addListener === 'function') {
            MOBILE_QUERY.addListener(handleViewportChange);
        }

        /* ---------------- API pública ---------------- */
        window.upeaSidebar = Object.assign(window.upeaSidebar || {}, {
            open,
            close,
            toggle: () => (sidebar.classList.contains('is-open') ? close() : open()),
            filter: applyFilter,
            expandAll: () => sidebar.querySelectorAll('[data-nav-group]').forEach((group) => setExpanded(group, true)),
            collapseAll: () => sidebar.querySelectorAll('[data-nav-group]').forEach((group) => setExpanded(group, false)),
        });

        sidebar.dataset.ready = '1';
    })();
</script>
@endpush
@endonce