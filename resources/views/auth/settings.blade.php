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
                        type="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres, con mayúsculas, minúsculas y números">


                    @error('new_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="new_password_confirmation">Confirmar nueva contraseña</label>
                    <input class="form-control" id="new_password_confirmation" name="new_password_confirmation"
                        type="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres, con mayúsculas, minúsculas y números">
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
        @if(isset($sesionesActivas) && $sesionesActivas->count() > 1)
        <form method="POST" action="{{ route('auth.settings.sessions.close-others') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('¿Cerrar todas las otras sesiones? Tendrás que volver a iniciar sesión en esos dispositivos.')">
                Cerrar otras sesiones
            </button>
        </form>
        @endif
    </div>
    <div class="card-body">
        @if(!isset($sesionesActivas) || $sesionesActivas->isEmpty())
        <p class="account-empty">No hay sesiones activas registradas.</p>
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
                    @php $esActual = $sesion->id === session()->getId(); @endphp
                    <tr @if($esActual) class="is-current" @endif>
                        <td>
                            {{ $sesion->ip ?? 'N/A' }}
                            @if($esActual)
                            <span class="badge badge-success">Actual</span>
                            @endif
                        </td>
                        <td title="{{ $sesion->user_agent }}">
                            {{ \Illuminate\Support\Str::limit($sesion->user_agent ?? 'Desconocido', 60) }}
                        </td>
                        <td>
                            {{ $sesion->inicio ? \Carbon\Carbon::parse($sesion->inicio)->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td>
                            @if(!$esActual)
                            <form method="POST"
                                action="{{ route('auth.settings.sessions.close', $sesion->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Cerrar esta sesión?')">
                                    Cerrar
                                </button>
                            </form>
                            @else
                            <span class="card-meta">—</span>
                            @endif
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
        @if(!isset($bitacora) || $bitacora->isEmpty())
        <p class="account-empty">No hay actividad registrada.</p>
        @else
        <ul class="activity-list">
            @foreach($bitacora as $entrada)
            @php
            $tipoRaw = $entrada->tipo ?? 'info';
            $tipo = strtoupper($tipoRaw);
            $datosRaw = $entrada->datos ?? '';
            $json = json_decode($datosRaw, true);
            $esJson = json_last_error() === JSON_ERROR_NONE && is_array($json);

            $badgeClass = match(true) {
            str_contains($tipo, 'LOGIN') => 'badge-success',
            str_contains($tipo, 'LOGOUT') => 'badge-warning',
            str_contains($tipo, 'INVESTIG') || str_contains($tipo, 'CONSULTA') => 'badge-info',
            str_contains($tipo, 'FALLIDO') || str_contains($tipo, 'BLOQUEADO') || str_contains($tipo, 'FAILED') => 'badge-danger',
            default => 'badge-muted'
            };

            // Título y detalle bonito
            $titulo = $tipoRaw;
            $detalle = '';

            if ($esJson) {
            if (($json['accion'] ?? '') === 'consulta_dataset') {
            $titulo = 'Consulta de dataset';
            $filtros = $json['filtros'] ?? [];
            $registros = $json['registros'] ?? 0;
            $detalle = $registros . ' registros encontrados' . (empty($filtros) ? ' • sin filtros' : ' • ' . count($filtros) . ' filtro(s)');
            } elseif (isset($json['descripcion'])) {
            $titulo = $json['descripcion'];
            $usuario = $json['usuario']['nombre'] ?? $json['usuario']['cuenta'] ?? '';
            $ruta = $json['ruta'] ?? '';
            $detalle = trim($usuario . ($ruta ? " • $ruta" : '') . " • " . ($json['resultado'] ?? ''));
            } else {
            $titulo = $json['accion'] ?? $json['tipo'] ?? $tipoRaw;
            $detalle = ($json['resultado'] ?? $json['descripcion'] ?? '');
            }
            } else {
            $detalle = $datosRaw;
            }
            @endphp
            <li class="activity-item">
                <div class="activity-detail">
                    <div class="activity-badges">
                        <span class="badge {{ $badgeClass }}">{{ $titulo }}</span>
                        @if($esJson && isset($json['codigo_http']))
                        <span class="badge badge-muted">{{ $json['codigo_http'] }} {{ $json['metodo'] ?? '' }}</span>
                        @endif
                    </div>
                    @if($detalle)
                    <div class="activity-text">{{ \Illuminate\Support\Str::limit($detalle, 150) }}</div>
                    @endif

                    {{-- Detalle JSON expandible solo si es investigación --}}

                </div>
                <span class="activity-time">
                    {{ $entrada->fecha ? \Carbon\Carbon::parse($entrada->fecha)->diffForHumans() : '' }}
                </span>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('new_password');
        const strengthDiv = document.getElementById('password-strength');
        if (!input || !strengthDiv) return;

        input.addEventListener('input', (e) => {
            const password = e.target.value;
            if (!password) {
                strengthDiv.innerHTML = '';
                return;
            }

            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^a-zA-Z\d]/.test(password)) score++;

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

            // score va de 0 a 5, levels de 0 a 4
            const index = Math.max(0, Math.min(score - 1, 4));
            const level = levels[index];

            strengthDiv.innerHTML = `
            <div class="strength-bar">
                <div class="strength-fill" style="background: ${level.color}; width: ${level.width};"></div>
            </div>
            <span class="strength-label" style="color: ${level.color};">
                ${level.text}
            </span>
        `;
        });
    });
</script>
@endpush