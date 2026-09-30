@php
$role = session('user_role');
$name = session('user_name', 'Usuario');
$username = session('user_username', '');
$userFoto = session('user_foto');

$initials = collect(explode(' ', trim($name)))
->filter()
->take(2)
->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
->implode('');

// URL del avatar: foto subida o fallback a UI Avatars
$avatarUrl = $userFoto
? asset('storage/' . $userFoto)
: 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0d6efd&color=fff&size=64&bold=true';

$roleLabels = [
'admin' => 'Administrador',
'subadmin' => 'Subadministrador',
'director' => 'Director de Carrera',
'jefatura-clinicas' => 'Jefatura de Clínicas',
'jefe-catedra' => 'Jefe de Cátedra',
'docente' => 'Docente',
'estudiante' => 'Estudiante',
'interno' => 'Interno de Odontología',
'cajas' => 'Cajero',
'esterilizacion' => 'Esterilización',
'insumos' => 'Insumos',
'radiologia' => 'Radiología',
'investigacion' => 'Instituto de Investigación',
];
@endphp

<header class="topbar">
    {{-- Botón móvil --}}
    <button class="topbar-mobile-toggle"
        onclick="document.querySelector('.sidebar').classList.toggle('open')"
        aria-label="Abrir menú">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    </button>

    {{-- Título / Breadcrumb --}}
    <div class="topbar-title">
        <h2 class="topbar-heading">@yield('page-title', 'Panel')</h2>
        <p class="topbar-sub">@yield('page-subtitle', 'Bienvenido al sistema')</p>
    </div>

    {{-- Acciones derecha --}}
    <div class="topbar-actions">

        {{-- Buscador --}}
        <div class="topbar-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" placeholder="Buscar paciente, usuario..." aria-label="Buscar">
        </div>

        {{-- Notificaciones --}}
        <button class="topbar-icon-btn" aria-label="Notificaciones">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="badge-dot"></span>
        </button>

        {{-- Toggle tema --}}
        <button class="topbar-icon-btn theme-toggle"
            id="themeToggle" type="button"
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

        {{-- Menú de usuario --}}
        <div class="topbar-user" id="userMenu">
            <button class="topbar-user-btn"
                onclick="toggleUserMenu()"
                aria-label="Menú de usuario"
                aria-haspopup="true"
                aria-expanded="false">
                <img class="topbar-avatar"
                    src="{{ $avatarUrl }}"
                    alt="Foto de {{ $name }}"
                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=0d6efd&color=fff&size=64&bold=true';">
                <div class="topbar-user-info">
                    <span class="topbar-user-name">{{ $name }}</span>
                    <span class="topbar-user-role">{{ $roleLabels[$role] ?? $role }}</span>
                </div>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="topbar-dropdown" id="userDropdown" role="menu">
                <div class="dropdown-header">
                    <img class="topbar-avatar lg"
                        src="{{ $avatarUrl }}"
                        alt="Foto de {{ $name }}"
                        onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=0d6efd&color=fff&size=128&bold=true';">
                    <div>
                        <div class="dropdown-name">{{ $name }}</div>
                        <div class="dropdown-email">{{ $username }}</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('auth.profile') }}" class="dropdown-item" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Mi perfil
                </a>
                <a href="{{ route('auth.settings') }}" class="dropdown-item" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    Configuración
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item danger" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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

{{-- JS: alternar menú de usuario --}}
<script>
    function toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        const btn = document.querySelector('.topbar-user-btn');
        const isOpen = dropdown.classList.toggle('open');
        if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    // Cerrar el menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('userMenu');
        const dropdown = document.getElementById('userDropdown');
        const btn = document.querySelector('.topbar-user-btn');
        if (menu && dropdown && !menu.contains(e.target)) {
            dropdown.classList.remove('open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const dropdown = document.getElementById('userDropdown');
            const btn = document.querySelector('.topbar-user-btn');
            if (dropdown) dropdown.classList.remove('open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });
</script>