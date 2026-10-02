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

    {{-- ==================== GRID DE 2 COLUMNAS ==================== --}}
    <div class="permisos-grid">
        @forelse ($porModulo as $modulo => $items)
        @php
        $moduloKey = $modulo ?: '__general__';
        $seleccionadosModulo = $items
        ->filter(fn($p) => in_array((int) $p->id, $permisosAsignados, true))
        ->count();
        @endphp

        <div class="card permisos-card" data-modulo-card="{{ $moduloKey }}">
            <div class="card-header">
                <h2 style="text-transform: capitalize;">
                    {{ $modulo ?: 'General' }}
                    <span class="badge badge-info badge-modulo"
                        data-modulo="{{ $moduloKey }}"
                        style="margin-left:8px;">
                        {{ $seleccionadosModulo }} / {{ $items->count() }}
                    </span>
                </h2>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button"
                        class="btn btn-secondary btn-sm js-toggle-modulo"
                        data-modulo="{{ $moduloKey }}"
                        data-checked="1">
                        Todos
                    </button>
                    <button type="button"
                        class="btn btn-secondary btn-sm js-toggle-modulo"
                        data-modulo="{{ $moduloKey }}"
                        data-checked="0">
                        Ninguno
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="checkbox-grid" data-modulo="{{ $moduloKey }}">
                    @foreach ($items as $p)
                    <label class="checkbox-item">
                        <input type="checkbox"
                            name="permisos[]"
                            value="{{ $p->id }}"
                            data-modulo="{{ $moduloKey }}"
                            @checked(in_array((int) $p->id, $permisosAsignados, true))>
                        <span>
                            <strong>{{ $p->codigo }}</strong><br>
                            <small style="color:var(--muted);">{{ $p->descripcion }}</small>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- ✅ Botón de guardado por módulo --}}
            <div class="permisos-card-footer">
                <span class="permisos-card-status" data-status-modulo="{{ $moduloKey }}"></span>
                <button type="button"
                    class="btn btn-primary btn-sm js-guardar-modulo"
                    data-modulo="{{ $moduloKey }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    Guardar módulo
                </button>
            </div>
        </div>
        @empty
        <div class="card permisos-card">
            <div class="card-body">
                <div class="empty-state">
                    <p>No hay permisos registrados en el sistema.</p>
                    <p style="font-size:12px; margin-top:8px;">
                        Ejecuta <code>php artisan db:seed --class=CatalogSeeder</code> para sembrar el catálogo.
                    </p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    {{-- ==================== BARRA STICKY GLOBAL ==================== --}}
    <div style="position: sticky; bottom: 20px; z-index: 10; margin-top: 20px;">
        <div class="card" style="box-shadow: 0 12px 36px rgba(24,37,45,0.12);">
            <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge badge-info" id="contadorSeleccionados">0 permisos seleccionados</span>
                    <span class="badge badge-warning" id="contadorCambios" style="display:none;">0 cambios sin guardar</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPermisos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        Guardar todo
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('styles')
<style>
    .permisos-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        align-items: start;
    }

    .permisos-card {
        margin-bottom: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .permisos-card .card-body {
        flex: 1;
        overflow: visible;
    }

    .permisos-card .checkbox-grid {
        grid-template-columns: 1fr;
        max-height: none;
    }

    /* Footer con botón de guardado por módulo */
    .permisos-card-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 10px 18px;
        border-top: 1px solid var(--line);
        background: rgba(248, 250, 252, 0.5);
        flex-shrink: 0;
    }

    .permisos-card-status {
        font-size: 12px;
        color: var(--muted);
        margin-right: auto;
    }

    .permisos-card-status.is-dirty {
        color: var(--warning);
        font-weight: 600;
    }

    .permisos-card-status.is-saved {
        color: var(--success);
        font-weight: 600;
    }

    .permisos-card .checkbox-item {
        padding: 8px 10px;
        align-items: flex-start;
    }

    .permisos-card .checkbox-item input[type="checkbox"] {
        margin-top: 3px;
    }

    .permisos-card .checkbox-item span {
        font-size: 12.5px;
        line-height: 1.4;
    }

    .permisos-card .checkbox-item strong {
        font-size: 12.5px;
        color: var(--ink);
    }

    .permisos-card .checkbox-item small {
        font-size: 11.5px;
        color: var(--muted);
    }

    @media (max-width: 900px) {
        .permisos-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formPermisos');
        const contador = document.getElementById('contadorSeleccionados');
        const contCambios = document.getElementById('contadorCambios');
        const btnGlobal = document.getElementById('btnGuardarPermisos');

        // Estado "sucio" por módulo (cambios locales sin guardar)
        const dirtyModules = new Set();

        // Snapshot inicial de cada módulo (para detectar cambios)
        const initialSnapshot = {};
        document.querySelectorAll('.checkbox-grid[data-modulo]').forEach(grid => {
            const mod = grid.dataset.modulo;
            initialSnapshot[mod] = new Set(
                Array.from(grid.querySelectorAll('input[type="checkbox"]:checked'))
                .map(cb => cb.value)
            );
        });

        function actualizarContadorGlobal() {
            const n = form.querySelectorAll('input[name="permisos[]"]:checked').length;
            contador.textContent = `${n} permiso${n !== 1 ? 's' : ''} seleccionado${n !== 1 ? 's' : ''}`;
        }

        function actualizarBadgesModulo() {
            document.querySelectorAll('.checkbox-grid[data-modulo]').forEach(grid => {
                const mod = grid.dataset.modulo;
                const total = grid.querySelectorAll('input[type="checkbox"]').length;
                const marcados = grid.querySelectorAll('input[type="checkbox"]:checked').length;
                const badge = document.querySelector(`.badge-modulo[data-modulo="${mod}"]`);
                if (badge) badge.textContent = `${marcados} / ${total}`;
            });
        }

        function moduloEstaDirty(mod) {
            const grid = document.querySelector(`.checkbox-grid[data-modulo="${mod}"]`);
            if (!grid) return false;

            const actual = new Set(
                Array.from(grid.querySelectorAll('input[type="checkbox"]:checked'))
                .map(cb => cb.value)
            );
            const inicial = initialSnapshot[mod] || new Set();

            if (actual.size !== inicial.size) return true;
            for (const v of actual)
                if (!inicial.has(v)) return true;
            return false;
        }

        function actualizarEstadoModulo(mod) {
            const dirty = moduloEstaDirty(mod);
            const status = document.querySelector(`.permisos-card-status[data-status-modulo="${mod}"]`);

            if (status) {
                if (dirty) {
                    status.textContent = '● Cambios sin guardar';
                    status.classList.add('is-dirty');
                    status.classList.remove('is-saved');
                } else {
                    status.textContent = '';
                    status.classList.remove('is-dirty', 'is-saved');
                }
            }

            if (dirty) dirtyModules.add(mod);
            else dirtyModules.delete(mod);

            // Contador global de cambios sin guardar
            if (dirtyModules.size > 0) {
                contCambios.style.display = '';
                contCambios.textContent = `${dirtyModules.size} módulo${dirtyModules.size !== 1 ? 's' : ''} sin guardar`;
            } else {
                contCambios.style.display = 'none';
            }
        }

        function actualizarTodo() {
            actualizarContadorGlobal();
            actualizarBadgesModulo();
            document.querySelectorAll('.checkbox-grid[data-modulo]').forEach(g => {
                actualizarEstadoModulo(g.dataset.modulo);
            });
        }

        // ── Toggle Todos / Ninguno ──
        document.querySelectorAll('.js-toggle-modulo').forEach(b => {
            b.addEventListener('click', () => {
                const mod = b.dataset.modulo;
                const checked = b.dataset.checked === '1';
                form.querySelectorAll(`input[data-modulo="${mod}"]`).forEach(cb => {
                    cb.checked = checked;
                });
                actualizarTodo();
            });
        });

        // ── Cambio en cualquier checkbox ──
        form.addEventListener('change', actualizarTodo);
        actualizarTodo();

        // ── Guardado por módulo (AJAX) ──
        document.querySelectorAll('.js-guardar-modulo').forEach(btn => {
            btn.addEventListener('click', async () => {
                const mod = btn.dataset.modulo;
                const originalText = btn.innerHTML;

                btn.disabled = true;
                btn.classList.add('spinning');

                // Recolectar solo los IDs de este módulo
                const ids = Array.from(
                    form.querySelectorAll(`input[data-modulo="${mod}"]:checked`)
                ).map(cb => cb.value);

                const body = new URLSearchParams();
                body.append('_token', form.querySelector('input[name="_token"]').value);
                body.append('modulo', mod);
                ids.forEach(id => body.append('permisos[]', id));

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        credentials: 'same-origin',
                    });

                    const data = await res.json().catch(() => ({}));

                    if (res.ok && data.success) {
                        // Actualizar snapshot
                        const grid = document.querySelector(`.checkbox-grid[data-modulo="${mod}"]`);
                        initialSnapshot[mod] = new Set(
                            Array.from(grid.querySelectorAll('input[type="checkbox"]:checked'))
                            .map(cb => cb.value)
                        );
                        actualizarEstadoModulo(mod);

                        const status = document.querySelector(`.permisos-card-status[data-status-modulo="${mod}"]`);
                        if (status) {
                            status.textContent = '✓ Guardado';
                            status.classList.remove('is-dirty');
                            status.classList.add('is-saved');
                            setTimeout(() => {
                                status.textContent = '';
                                status.classList.remove('is-saved');
                            }, 2500);
                        }

                        if (window.mostrarToast) {
                            window.mostrarToast('success', data.message || `Permisos de "${mod}" guardados`);
                        }
                    } else {
                        if (window.mostrarToast) {
                            window.mostrarToast('error', data.message || 'Error al guardar el módulo');
                        }
                    }
                } catch (err) {
                    if (window.mostrarToast) {
                        window.mostrarToast('error', err.message || 'Error de red');
                    }
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('spinning');
                    btn.innerHTML = originalText;
                }
            });
        });

        // ── Guardado global (todo) ──
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            btnGlobal.disabled = true;
            btnGlobal.classList.add('spinning');

            const formData = new FormData(form);
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
                    dirtyModules.clear();
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
                btnGlobal.disabled = false;
                btnGlobal.classList.remove('spinning');
            }
        });

        // ── Aviso al salir con cambios sin guardar ──
        window.addEventListener('beforeunload', e => {
            if (dirtyModules.size > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
</script>
@endpush