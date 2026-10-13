@extends('layouts.app')

@section('title', 'Mi perfil')
@section('page-title', 'Mi perfil')
@section('page-subtitle', 'Actualiza tus datos personales y configuración')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h3>Datos personales</h3>
            <p class="card-meta">Los cambios se aplican únicamente a tu cuenta.</p>
        </div>
    </div>
    <div class="card-body">
        @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('auth.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- FOTO DE PERFIL --}}
            <div class="form-group profile-photo">
                <div class="profile-avatar-wrap">
                    <span aria-hidden="true" class="profile-avatar">
                        {{ $user->initials }}
                    </span>
                    <img id="avatar-preview"
                        class="profile-avatar-image"
                        @if ($user->foto)
                        src="{{ asset('storage/' . $user->foto) }}"
                        @else
                        hidden
                        @endif
                        alt="Foto de perfil"
                        onerror="this.hidden = true;">

                    <label for="foto"
                        class="profile-avatar-edit"
                        title="Cambiar foto de perfil">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                </div>

                <p class="form-hint" style="margin-top: 12px;">
                    Haz clic en el ícono para cambiar tu foto (máx. 2MB). Formatos: JPG, PNG, GIF, WEBP.
                </p>

                @if($user->foto)
                <button type="button"
                    onclick="if(confirm('¿Eliminar foto de perfil?')) document.getElementById('delete-avatar-form').submit();"
                    class="btn btn-sm btn-danger"
                    style="margin-top: 5px;">
                    Eliminar foto
                </button>
                @endif

                @error('foto') <span class="form-error" style="display:block;">{{ $message }}</span> @enderror
            </div>

            {{-- ESTADÍSTICAS --}}
            <div class="account-stats">
                <div class="account-stat">
                    <div class="account-stat-value">{{ $stats['sesiones_activas'] }}</div>
                    <div class="form-hint">Sesiones activas</div>
                </div>
                <div class="account-stat">
                    <div class="account-stat-value">{{ $stats['total_sesiones'] }}</div>
                    <div class="form-hint">Total de sesiones</div>
                </div>
                <div class="account-stat">
                    <div class="account-stat-value account-stat-value--text">
                        {{ $stats['ultimo_login'] ? \Carbon\Carbon::parse($stats['ultimo_login'])->diffForHumans() : 'Nunca' }}
                    </div>
                    <div class="form-hint">Último acceso</div>
                </div>
            </div>

            {{-- DATOS BÁSICOS --}}
            <h4 class="section-title">Información Básica</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre_completo">Nombre completo</label>
                    <input class="form-control" id="nombre_completo" name="nombre_completo" type="text"
                        maxlength="200" value="{{ old('nombre_completo', $user->nombre_completo) }}" required>
                    @error('nombre_completo') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="nombre_usuario">Nombre de usuario</label>
                    <input class="form-control" id="nombre_usuario" name="nombre_usuario" type="text"
                        maxlength="20" value="{{ old('nombre_usuario', $user->nombre_usuario) }}" required>
                    @error('nombre_usuario') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="ci">CI</label>
                    <input class="form-control" id="ci" name="ci" type="text" maxlength="50"
                        value="{{ old('ci', $user->ci) }}" required>
                    @error('ci') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción / Cargo</label>
                    <input class="form-control" id="descripcion" name="descripcion" type="text"
                        maxlength="300" value="{{ old('descripcion', $user->descripcion) }}"
                        placeholder="Ej: Odontólogo General">
                    @error('descripcion') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- CONFIGURACIÓN DE SEGURIDAD --}}
            <h4 class="section-title">Seguridad y Acceso</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="ip_permitida">IP Permitida (Opcional)</label>
                    <input class="form-control" id="ip_permitida" name="ip_permitida" type="text"
                        maxlength="300" value="{{ old('ip_permitida', $user->ip_permitida) }}"
                        placeholder="Ej: 192.168.1.100, 10.0.0.0/24">
                    <small class="form-hint">
                        Restringe el inicio de sesión a IPs específicas. Separa múltiples IPs con coma. Soporta rangos CIDR.
                    </small>
                    @error('ip_permitida') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Multilogin</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                        <input type="checkbox" id="multilogin" name="multilogin" value="1"
                            {{ old('multilogin', $user->multilogin) ? 'checked' : '' }}
                            style="width: 20px; height: 20px;">
                        <label for="multilogin" style="font-weight: normal; cursor: pointer;">
                            Permitir iniciar sesión desde múltiples dispositivos
                        </label>
                    </div>
                    <small class="form-hint">
                        Si se desactiva, se cerrará la sesión en otros dispositivos al iniciar sesión.
                    </small>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Guardar cambios</button>
            </div>
        </form>

        {{-- Formulario oculto para eliminar avatar --}}
        @if($user->foto)
        <form id="delete-avatar-form" method="POST" action="{{ route('auth.profile.avatar.delete') }}" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
        @endif
    </div>
</div>

{{-- Script para previsualización de imagen --}}
<script>
    document.getElementById('foto')?.addEventListener('change', function() {
        const file = this.files?.[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            const preview = document.getElementById('avatar-preview');
            preview.src = reader.result;
            preview.hidden = false;
        });
        reader.addEventListener('error', () => {
            alert('No se pudo leer la imagen seleccionada.');
            this.value = '';
        });
        reader.readAsDataURL(file);
    });
</script>
@endsection
