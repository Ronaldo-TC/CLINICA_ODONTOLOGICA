@extends('layouts.app')

@section('title', 'Notificaciones')
@section('page-title', 'Notificaciones')
@section('page-subtitle', 'Avisos internos del sistema')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot {{ $totalNoLeidas > 0 ? 'live' : '' }}"></span>
        <span id="crud-total-label">
            {{ $totalNoLeidas }} sin leer · {{ $registros->total() }} en total
        </span>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('notificaciones.index', ['filtro' => 'todas']) }}"
            class="btn btn-sm {{ $filtro === 'todas' ? 'btn-primary' : 'btn-secondary' }}">Todas</a>
        <a href="{{ route('notificaciones.index', ['filtro' => 'no_leidas']) }}"
            class="btn btn-sm {{ $filtro === 'no_leidas' ? 'btn-primary' : 'btn-secondary' }}">Sin leer</a>
        <a href="{{ route('notificaciones.index', ['filtro' => 'leidas']) }}"
            class="btn btn-sm {{ $filtro === 'leidas' ? 'btn-primary' : 'btn-secondary' }}">Leídas</a>

        @can('reportes.ver')
        <a href="{{ route('bitacoras.index') }}" class="btn btn-sm btn-secondary">Ver bitácora</a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bandeja de notificaciones</h2>
        <button type="button" class="btn btn-sm btn-secondary" id="btnLeerTodas">
            <x-icono nombre="leer" /> Marcar todas como leídas
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:120px;">Fecha</th>
                    <th style="width:130px;">Tipo</th>
                    <th>Título</th>
                    <th>Detalle</th>
                    <th style="width:150px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyNotificaciones">
                @forelse ($registros as $n)
                <tr data-id="{{ $n->id }}" class="{{ $n->leida ? '' : 'notificacion-no-leida' }}">
                    <td>
                        <small>{{ $n->created_at?->format('d/m/Y H:i') }}</small>
                        <br>
                        <small style="color: var(--muted);">{{ $n->tiempo_relativo }}</small>
                    </td>
                    <td>
                        <span class="badge badge-{{ $n->tono === 'danger' ? 'danger' : ($n->tono === 'warning' ? 'warning' : ($n->tono === 'success' ? 'success' : 'info')) }}">
                            {{ str_replace('_', ' ', $n->tipo) }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $n->titulo }}</strong>
                        @unless ($n->leida)
                        <span class="badge badge-info" title="Sin leer">nueva</span>
                        @endunless
                    </td>
                    <td><small>{{ $n->mensaje }}</small></td>
                    <td>
                        <div class="actions">
                            @if ($n->url)
                            <a href="{{ $n->url }}" class="btn btn-sm btn-secondary"
                                data-action="abrir" data-id="{{ $n->id }}"><x-icono nombre="abrir" /> Abrir</a>
                            @endif
                            @unless ($n->leida)
                            <button type="button" class="btn btn-sm btn-primary"
                                data-action="leer" data-id="{{ $n->id }}"><x-icono nombre="leer" /> Marcar leída</button>
                            @endunless
                            <button type="button" class="btn btn-sm btn-danger"
                                data-action="eliminar" data-id="{{ $n->id }}"><x-icono nombre="eliminar" /> Eliminar</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <p>No tienes notificaciones{{ $filtro === 'no_leidas' ? ' sin leer' : '' }}.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $registros->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const URL_BASE = @json(url('notificaciones'));

        const toast = (tipo, mensaje) => window.mostrarToast?.(tipo, mensaje);

        function actualizarContador(noLeidas) {
            const globo = document.getElementById('contadorNotificaciones');
            if (!globo) return;
            globo.textContent = noLeidas > 99 ? '99+' : String(noLeidas);
            globo.hidden = !(noLeidas > 0);
            const punto = document.querySelector('[data-notif-dot]');
            if (punto) punto.hidden = !(noLeidas > 0);
        }

        document.addEventListener('click', async (e) => {
            const boton = e.target.closest('[data-action]');
            if (!boton) return;

            const accion = boton.dataset.action;
            const id = boton.dataset.id;

            if (accion === 'abrir') {
                // Marca como leída y sigue el enlace
                try {
                    await fetch(`${URL_BASE}/${id}/leer`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                } catch { /* el enlace continúa */ }
                return;
            }

            if (accion === 'leer') {
                e.preventDefault();
                try {
                    const res = await fetch(`${URL_BASE}/${id}/leer`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message);
                    actualizarContador(data.no_leidas);
                    window.location.reload();
                } catch (err) {
                    toast('error', err.message || 'No se pudo marcar como leída.');
                }
                return;
            }

            if (accion === 'eliminar') {
                e.preventDefault();
                if (!await window.confirmarEliminacion('Notificación seleccionada')) return;
                try {
                    const res = await fetch(`${URL_BASE}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message);
                    actualizarContador(data.no_leidas);
                    boton.closest('tr')?.remove();
                    toast('success', data.message || 'Notificación eliminada.');
                } catch (err) {
                    toast('error', err.message || 'No se pudo eliminar.');
                }
            }
        });

        document.getElementById('btnLeerTodas')?.addEventListener('click', async () => {
            try {
                const res = await fetch(`${URL_BASE}/leer-todas`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message);
                actualizarContador(0);
                toast('success', data.message);
                window.location.reload();
            } catch (err) {
                toast('error', err.message || 'No se pudo actualizar.');
            }
        });
    })();
</script>
@endpush
