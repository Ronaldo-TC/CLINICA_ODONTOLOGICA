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
            <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                <div style="position: relative; display: inline-block;">
                    <img id="avatar-preview"
                        src="{{ $user->foto ? asset('storage/' . $user->foto) : 'https://ui-avatars.com/api/?name='.urlencode($user->nombre_completo).'&background=random&size=150' }}"
                        alt="Foto de perfil"
                        style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">

                    <label for="foto"
                        title="Cambiar foto de perfil"
                        style="position: absolute; bottom: 0; right: 0; background: #007bff; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: background 0.2s;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </label>
                    <input type="file" id="foto" name="foto" accept="image/*" style="display: none;" onchange="previewImage(this)">
                </div>

                <p style="margin-top: 10px; font-size: 12px; color: #666;">
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
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #007bff;">{{ $stats['sesiones_activas'] }}</div>
                    <div style="font-size: 12px; color: #666;">Sesiones activas</div>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">{{ $stats['total_sesiones'] }}</div>
                    <div style="font-size: 12px; color: #666;">Total de sesiones</div>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #333;">
                        {{ $stats['ultimo_login'] ? \Carbon\Carbon::parse($stats['ultimo_login'])->diffForHumans() : 'Nunca' }}
                    </div>
                    <div style="font-size: 12px; color: #666;">Último acceso</div>
                </div>
            </div>

            {{-- DATOS BÁSICOS --}}
            <h4 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Información Básica</h4>
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
            <h4 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Seguridad y Acceso</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="ip_permitida">IP Permitida (Opcional)</label>
                    <input class="form-control" id="ip_permitida" name="ip_permitida" type="text"
                        maxlength="300" value="{{ old('ip_permitida', $user->ip_permitida) }}"
                        placeholder="Ej: 192.168.1.100, 10.0.0.0/24">
                    <small style="color: #666;">
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
                    <small style="color: #666;">
                        Si se desactiva, se cerrará la sesión en otros dispositivos al iniciar sesión.
                    </small>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 30px; text-align: right;">
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
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('foto')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            this.value = '';
        }
    });
</script>
@endsection