@extends('layouts.app')

@section('title', "Permisos: {$rol->nombre}")

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">
            Permisos del rol:
            <span style="background: linear-gradient(135deg, #06b6d4, #d946ef); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">
                {{ $rol->nombre }}
            </span>
        </h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            {{ $rol->descripcion ?? 'Sin descripción' }}
        </p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
        </svg>
        Volver
    </a>
</div>

<form id="formPermisos" method="POST" action="{{ route('admin.roles.permisos.guardar', $rol->id) }}">
    @csrf

    @php
    $porModulo = $permisos->groupBy('modulo');
    @endphp

    @forelse ($porModulo as $modulo => $items)
    <div class="card">
        <div class="card-header">
            <h2 style="text-transform: capitalize;">{{ $modulo ?: 'General' }}</h2>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleModulo('{{ $modulo }}', true)">
                    Todos
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleModulo('{{ $modulo }}', false)">
                    Ninguno
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="checkbox-grid" data-modulo="{{ $modulo }}">
                @foreach ($items as $p)
                <label class="checkbox-item">
                    <input type="checkbox"
                        name="permisos[]"
                        value="{{ $p->id }}"
                        data-modulo="{{ $modulo }}"
                        @checked(in_array($p->id, $permisosAsignados))>
                    <span>
                        <strong>{{ $p->codigo }}</strong><br>
                        <small style="color:var(--muted);">{{ $p->descripcion }}</small>
                    </span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <p>No hay permisos registrados en el sistema.</p>
            </div>
        </div>
    </div>
    @endforelse

    <div style="position: sticky; bottom: 20px; z-index: 10;">
        <div class="card" style="box-shadow: 0 12px 36px rgba(24,37,45,0.12);">
            <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge badge-info" id="contadorSeleccionados">0 permisos seleccionados</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPermisos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        Guardar permisos
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formPermisos');
        const contador = document.getElementById('contadorSeleccionados');
        const btn = document.getElementById('btnGuardarPermisos');

        function actualizarContador() {
            const n = form.querySelectorAll('input[name="permisos[]"]:checked').length;
            contador.textContent = `${n} permiso${n !== 1 ? 's' : ''} seleccionado${n !== 1 ? 's' : ''}`;
        }

        window.toggleModulo = function(modulo, checked) {
            form.querySelectorAll(`input[data-modulo="${modulo}"]`).forEach(cb => {
                cb.checked = checked;
            });
            actualizarContador();
        };

        form.addEventListener('change', actualizarContador);
        actualizarContador();

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            btn.disabled = true;

            const formData = new FormData(form);
            // Aseguramos el token CSRF como campo del form (ya viene por @csrf)
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ||
                formData.get('_token');

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    if (window.mostrarToast) window.mostrarToast('success', data.message || 'Permisos actualizados');
                    setTimeout(() => window.location.href = "{{ route('admin.roles.index') }}", 800);
                } else if (res.status === 422 && data.errors) {
                    const primerError = Object.values(data.errors)[0]?.[0] || 'Datos inválidos';
                    if (window.mostrarToast) window.mostrarToast('warning', primerError);
                } else {
                    if (window.mostrarToast) window.mostrarToast('error', data.message || 'Error al guardar');
                }
            } catch (err) {
                if (window.mostrarToast) window.mostrarToast('error', err.message || 'Error de red');
            } finally {
                btn.disabled = false;
            }
        });
    });
</script>
@endpush