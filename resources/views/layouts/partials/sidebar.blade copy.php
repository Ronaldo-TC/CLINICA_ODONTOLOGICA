@php
// ============================================================
// Datos cargados desde la sesión personalizada y sus roles activos.
// ============================================================
/** @var \App\Models\Usuario|null $user */
$user = $sidebarUser;

$role = $sidebarRole;
$name = $user->nombre_completo ?? session('user_name', 'Usuario');
$username = $user->nombre_usuario ?? session('user_username', '');

// Iniciales para el avatar
$initials = collect(explode(' ', trim($name)))
->take(2)
->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
->implode('');

// ============================================================
// Iconos SVG
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
'user-plus' => '
<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
<circle cx="8.5" cy="7" r="4" />
<line x1="20" y1="8" x2="20" y2="14" />
<line x1="23" y1="11" x2="17" y2="11" />',
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
];

$currentRoute = request()->route()?->getName() ?? '';
$menu = $sidebarMenu;
@endphp

<aside class="sidebar">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <div class="logo">O</div>
        <div class="brand-text">
            <span class="brand-title">Clínica Odontológica</span>
            <span class="brand-sub">UPEA · UTIC</span>
        </div>
    </div>

    {{-- Usuario --}}
    <!-- <div class="sidebar-user">
        <div class="avatar">{{ $initials ?: 'U' }}</div>
        <div class="user-info">
            <div class="user-name">{{ $name }}</div>
            <div class="user-role">{{ $role }}</div>
        </div>
    </div> -->

    {{-- Navegación --}}
    <nav class="sidebar-nav">
        <span class="nav-label">Menú</span>

        @forelse ($menu as $item)
        @php
        $hasRoute = !empty($item['route']);
        $href = $hasRoute ? route($item['route']) : '#';
        $pattern = $item['pattern'] ?? null;

        $isActive = $hasRoute && (
        $currentRoute === $item['route']
        || ($pattern && request()->routeIs($pattern))
        );
        @endphp

        <a href="{{ $href }}"
            class="nav-item {{ $isActive ? 'active' : '' }} {{ !$hasRoute ? 'disabled' : '' }}"
            @if (!$hasRoute) onclick="event.preventDefault()" aria-disabled="true" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                {!! $icons[$item['icon']] ?? $icons['grid'] !!}
            </svg>
            <span>{{ $item['label'] }}</span>
        </a>
        @empty
        <p class="nav-empty">No hay opciones disponibles para tu rol.</p>
        @endforelse
    </nav>

    {{-- Logout --}}
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>