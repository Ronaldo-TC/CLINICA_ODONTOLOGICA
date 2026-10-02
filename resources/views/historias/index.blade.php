@extends('layouts.app')

@section('title', 'Historias Clínicas')

@section('content')

<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span id="crud-total-label">{{ $registros->total() }} historias registradas</span>
    </div>
    <div class="toolbar-actions">
        @can('historias.crear')
        <button type="button" class="btn btn-primary" id="btnNuevaHistoria">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva Historia
        </button>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Historias Clínicas</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="crud-status">
                <span class="dot"></span>
                <span id="crud-status-text">Cargando...</span>
            </span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body" style="padding-bottom: 14px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" id="filtro-buscar" class="form-control"
                    placeholder="Paciente, CI o archivo..."
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label>Clínica</label>
                <select id="filtro-clinica" class="form-control">
                    <option value="">Todas las clínicas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Periodo</label>
                <select id="filtro-periodo" class="form-control">
                    <option value="">Todos los periodos</option>
                    @foreach ($periodos as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->gestion }}-{{ $p->semestre }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Clínica</th>
                    <th>Periodo</th>
                    <th>Estudiante</th>
                    <th>Docente</th>
                    <th>Tratamientos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyHistorias">
                @include('historias.partials.filas')
            </tbody>
        </table>
    </div>

    <div id="paginacionWrap"></div>
</div>

@include('historias.partials.modal')

{{-- ============ MODAL DE ARCHIVOS ADJUNTOS ============ --}}
<div class="modal-backdrop" id="modalArchivos">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalArchivosTitulo">
        <div class="modal-header">
            <h3 id="modalArchivosTitulo">Archivos adjuntos</h3>
            <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body" id="modalArchivosBody">
            <p class="notif-cargando">Selecciona una historia para ver sus archivos.</p>
        </div>
        <div class="modal-footer">
            @can('historias.editar')
            <label class="btn btn-primary" for="archivoGlobalInput" style="cursor:pointer;">
                + Adjuntar archivo
            </label>
            <input type="file" id="archivoGlobalInput" accept=".pdf,.jpg,.jpeg,.png" hidden>
            @endcan
            <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            tabla: @json(route('historias.tabla')),
            store: @json(route('historias.store')),
            datos: (id) => `/historias/${id}/datos`,
            update: (id) => `/historias/${id}`,
            destroy: (id) => `/historias/${id}`,
        };

        const $tbody = document.getElementById('tbodyHistorias');
        const $pagWrap = document.getElementById('paginacionWrap');
        const $status = document.getElementById('crud-status');
        const $statusText = document.getElementById('crud-status-text');
        const $totalLabel = document.getElementById('crud-total-label');

        const $filtroBuscar = document.getElementById('filtro-buscar');
        const $filtroClinica = document.getElementById('filtro-clinica');
        const $filtroPeriodo = document.getElementById('filtro-periodo');
        const $btnLimpiar = document.getElementById('btnLimpiarFiltros');

        let debounceTimer = null;
        let cargando = false;

        function toast(type, message) {
            const colores = {
                success: 'linear-gradient(135deg, #16a34a, #15803d)',
                error: 'linear-gradient(135deg, #dc2626, #b91c1c)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                info: 'linear-gradient(135deg, #06b6d4, #0891b2)',
            };
            const el = document.createElement('div');
            el.textContent = message;
            Object.assign(el.style, {
                position: 'fixed',
                bottom: '24px',
                right: '24px',
                padding: '13px 20px',
                borderRadius: '12px',
                background: colores[type] || colores.info,
                color: '#fff',
                font: '600 13.5px -apple-system, sans-serif',
                boxShadow: '0 10px 32px rgba(0,0,0,0.18)',
                zIndex: '3000',
                opacity: '0',
                transform: 'translateY(20px) scale(0.95)',
                transition: 'all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1)',
                maxWidth: 'min(360px, calc(100vw - 32px))',
                wordBreak: 'break-word',
            });
            document.body.appendChild(el);
            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
            });
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px) scale(0.95)';
                setTimeout(() => el.remove(), 300);
            }, 3200);
        }

        function setStatus(state, text) {
            if (!$status) return;
            if (state === 'idle') {
                $status.classList.remove('visible');
                return;
            }
            $status.classList.add('visible');
            if ($statusText) $statusText.textContent = text || 'Cargando...';
        }

        async function cargarTabla(page = 1) {
            if (cargando) return;
            cargando = true;

            setStatus('loading', 'Cargando...');
            $tbody?.classList.add('loading');

            const params = new URLSearchParams();
            if ($filtroBuscar?.value.trim()) params.set('buscar', $filtroBuscar.value.trim());
            if ($filtroClinica?.value) params.set('clinica_id', $filtroClinica.value);
            if ($filtroPeriodo?.value) params.set('periodo_id', $filtroPeriodo.value);
            if (page > 1) params.set('page', page);

            try {
                const res = await fetch(`${URLS.tabla}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                $tbody.innerHTML = data.filas || '';
                $pagWrap.innerHTML = data.paginacion || '';
                $totalLabel.textContent = `${data.total} historias registradas`;

                setStatus('idle');
            } catch (err) {
                console.error('[Historias] Error:', err);
                $tbody.innerHTML = `
                <tr><td colspan="9">
                    <div class="empty-state"><p>Error al cargar historias.</p></div>
                </td></tr>`;
                setStatus('error', 'Error al cargar');
            } finally {
                $tbody?.classList.remove('loading');
                cargando = false;
            }
        }

        $filtroBuscar?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => cargarTabla(1), 300);
        });
        $filtroClinica?.addEventListener('change', () => cargarTabla(1));
        $filtroPeriodo?.addEventListener('change', () => cargarTabla(1));

        $btnLimpiar?.addEventListener('click', () => {
            $filtroBuscar.value = '';
            $filtroClinica.value = '';
            $filtroPeriodo.value = '';
            cargarTabla(1);
        });

        document.addEventListener('click', (e) => {
            const pagLink = e.target.closest('#paginacionWrap a');
            if (pagLink) {
                e.preventDefault();
                const url = new URL(pagLink.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                cargarTabla(page);
                return;
            }

            if (e.target.closest('#btnNuevaHistoria')) {
                HistoriaModal.abrirCrear();
                return;
            }

            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const id = actionBtn.dataset.id;
            const nombre = actionBtn.dataset.nombre || 'esta historia';

            switch (action) {
                case 'editar':
                    abrirEdicion(id);
                    break;
                case 'eliminar':
                    eliminar(id, nombre);
                    break;
                case 'archivos':
                    abrirArchivos(id);
                    break;
            }
        });

        /* ============================================================
           ARCHIVOS ADJUNTOS
           ============================================================ */
        const $modalArchivos = document.getElementById('modalArchivos');
        const $inputArchivo = document.getElementById('archivoGlobalInput');
        let historiaArchivosActual = null;

        async function abrirArchivos(id) {
            if (!$modalArchivos) return;

            historiaArchivosActual = id;

            const cuerpo = document.getElementById('modalArchivosBody');
            const titulo = document.getElementById('modalArchivosTitulo');

            titulo.textContent = 'Archivos de la historia #' + id;
            cuerpo.innerHTML = '<p class="notif-cargando">Cargando…</p>';
            $modalArchivos.classList.add('open');
            document.body.classList.add('modal-open');

            try {
                const res = await fetch(`/historias/${id}/archivos`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                const archivos = data.archivos || [];

                if (archivos.length === 0) {
                    cuerpo.innerHTML = '<div class="empty-state"><p>Sin archivos adjuntos todavía.</p></div>';
                } else {
                    cuerpo.innerHTML = `
                        <div class="archivos-lista">
                            ${archivos.map((a) => `
                                <figure class="archivo-item" data-id="${a.id}">
                                    <div class="archivo-preview">
                                        ${a.es_imagen
                                            ? `<img src="${a.url}" alt="" loading="lazy">`
                                            : `<embed src="${a.url}#toolbar=0" type="application/pdf">`}
                                    </div>
                                    <figcaption class="archivo-meta">
                                        <a href="${a.url}" target="_blank" rel="noopener" class="archivo-nombre">${a.nombre_original}</a>
                                        <span class="archivo-detalle">${a.tamano_legible} · ${a.fecha || ''}</span>
                                    </figcaption>
                                </figure>`).join('')}
                        </div>`;
                }
            } catch (err) {
                cuerpo.innerHTML = '<div class="empty-state"><p>No se pudieron cargar los archivos.</p></div>';
            }
        }

        $modalArchivos?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]') || e.target === $modalArchivos) {
                $modalArchivos.classList.remove('open');
                document.body.classList.remove('modal-open');
            }
        });

        /* Subida del archivo seleccionado para la historia abierta */
        $inputArchivo?.addEventListener('change', async () => {
            const archivo = $inputArchivo.files?.[0];
            if (!archivo || !historiaArchivosActual) return;

            const datos = new FormData();
            datos.append('archivo', archivo);

            try {
                const res = await fetch(`/historias/${historiaArchivosActual}/archivos`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: datos,
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo subir el archivo.');
                }

                window.SolicitudesLista?.toast?.('success', data.message);
                window.mostrarToast?.('success', data.message);
                await abrirArchivos(historiaArchivosActual);
            } catch (err) {
                window.mostrarToast?.('error', err.message || 'Error al subir el archivo.');
            } finally {
                $inputArchivo.value = '';
            }
        });

        async function abrirEdicion(id) {
            setStatus('loading', 'Cargando datos...');

            try {
                const res = await fetch(URLS.datos(id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                HistoriaModal.abrirEdicion(data);
                setStatus('idle');
            } catch (err) {
                console.error('[Historias] Error:', err);
                toast('error', 'No se pudieron cargar los datos.');
                setStatus('idle');
            }
        }

        async function eliminar(id, nombre) {
            if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) return;

            setStatus('loading', 'Eliminando...');

            try {
                const res = await fetch(URLS.destroy(id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al eliminar.');
                }

                toast('success', data.message || 'Historia eliminada.');
                cargarTabla(1);
            } catch (err) {
                console.error('[Historias] Error:', err);
                toast('error', err.message || 'No se pudo eliminar.');
                setStatus('idle');
            }
        }

        window.addEventListener('historia:guardada', () => {
            cargarTabla(1);
        });

        window.HistoriasLista = {
            cargar: cargarTabla,
            toast: toast
        };

    })();
</script>
@endpush