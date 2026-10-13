@extends('layouts.app')

@section('title', 'Configuración')
@section('page-title', 'Configuración')
@section('page-subtitle', 'Administra la seguridad de tu cuenta')

@section('content')
{{-- CAMBIO DE CONTRASEÑA --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3>Cambiar contraseña</h3>
            <p class="card-meta">Confirma tu contraseña actual para guardar una nueva.</p>
        </div>
    </div>
    <div class="card-body">
        @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('auth.settings.password') }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="current_password">Contraseña actual</label>
                    <input class="form-control" id="current_password" name="current_password"
                        type="password" required autocomplete="current-password">
                    @error('current_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input class="form-control" id="new_password" name="new_password"
                        type="password" minlength="8" required autocomplete="new-password">
                    <div id="password-strength" style="margin-top: 6px;"></div>
                    <small class="form-hint">
                        Mínimo 8 caracteres, con al menos una mayúscula, una minúscula y un número.
                    </small>
                    @error('new_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation">Confirmar nueva contraseña</label>
                    <input class="form-control" id="new_password_confirmation" name="new_password_confirmation"
                        type="password" minlength="8" required autocomplete="new-password">
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
            </div>
        </form>
    </div>
</div>

{{-- SESIONES ACTIVAS --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3>Sesiones activas</h3>
            <p class="card-meta">Dispositivos con acceso a tu cuenta.</p>
        </div>
        @if($sesionesActivas->count() > 1)
        <form method="POST" action="{{ route('auth.settings.sessions.close-others') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('¿Cerrar todas las otras sesiones?')">
                Cerrar otras sesiones
            </button>
        </form>
        @endif
    </div>
    <div class="card-body">
        @if($sesionesActivas->isEmpty())
        <p class="account-empty">
            No hay sesiones activas registradas.
        </p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Dispositivo</th>
                        <th>Inicio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sesionesActivas as $sesion)
                    <tr>
                        <td>
                            {{ $sesion->ip }}
                        </td>
                        <td>
                            {{ \Illuminate\Support\Str::limit($sesion->user_agent, 50) }}
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($sesion->inicio)->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <form method="POST"
                                action="{{ route('auth.settings.sessions.close', $sesion->id) }}"
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Cerrar esta sesión?')">
                                    Cerrar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- BITÁCORA DE ACTIVIDAD --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3>Actividad reciente</h3>
            <p class="card-meta">Últimos movimientos en tu cuenta.</p>
        </div>
    </div>
    <div class="card-body">
        @if($bitacora->isEmpty())
        <p class="account-empty">
            No hay actividad registrada.
        </p>
        @else
        <ul class="activity-list">
            @foreach($bitacora as $entrada)
            @php
            $tipo = $entrada->tipo;
            if (\Illuminate\Support\Str::contains($tipo, ['LOGIN'])) {
            $badgeClass = 'badge-success';
            } elseif (\Illuminate\Support\Str::contains($tipo, ['LOGOUT'])) {
            $badgeClass = 'badge-warning';
            } elseif (\Illuminate\Support\Str::contains($tipo, ['FALLIDO', 'BLOQUEADO'])) {
            $badgeClass = 'badge-danger';
            } else {
            $badgeClass = 'badge-muted';
            }
            @endphp
            <li>
                <div class="activity-detail">
                    <span class="badge {{ $badgeClass }}">
                        {{ $tipo }}
                    </span>
                    <span>{{ $entrada->datos }}</span>
                </div>
                <span class="activity-time">
                    {{ \Carbon\Carbon::parse($entrada->fecha)->diffForHumans() }}
                </span>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>

{{-- Script para medidor de fortaleza de contraseña --}}
<script>
    document.getElementById('new_password')?.addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthDiv = document.getElementById('password-strength');

        if (!password) {
            strengthDiv.innerHTML = '';
            return;
        }

        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;

        const levels = [{
                text: 'Muy débil',
                color: '#dc3545',
                width: '20%'
            },
            {
                text: 'Débil',
                color: '#fd7e14',
                width: '40%'
            },
            {
                text: 'Regular',
                color: '#ffc107',
                width: '60%'
            },
            {
                text: 'Fuerte',
                color: '#28a745',
                width: '80%'
            },
            {
                text: 'Muy fuerte',
                color: '#20c997',
                width: '100%'
            },
        ];

        const level = levels[Math.min(strength, 4)];

        strengthDiv.innerHTML = `
            <div style="background: var(--surface-soft); border-radius: 4px; height: 6px; overflow: hidden;">
                <div style="background: ${level.color}; width: ${level.width}; height: 100%; transition: all 0.3s;"></div>
            </div>
            <span style="color: ${level.color}; font-weight: bold; font-size: 12px; margin-top: 4px; display: inline-block;">
                ${level.text}
            </span>
        `;
    });
</script>
@endsection
