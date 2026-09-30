@extends('layouts.app')

@section('title', 'Historias Clínicas')

@section('content')

{{-- Barra de progreso superior --}}
<div class="loading-bar" id="loadingBar" aria-hidden="true"></div>

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Gestión de Historias Clínicas</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra las historias clínicas por especialidad, estudiantes y docentes
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-historias">{{ $historias->total() }}</strong> historias
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscar">Buscar paciente</label>
                <input type="text" id="filtroBuscar" class="form-control"
                    placeholder="CI, nombre o apellido"
                    value="{{ request('buscar') }}"
                    autocomplete="off">
            </div>

            <div class="form-group">
                <label for="filtroClinica">Especialidad</label>
                <select id="filtroClinica" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}" @selected((string)request('clinica_id')===(string)$c->id)>
                        {{ $c->especialidad }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroPeriodo">Periodo</label>
                <select id="filtroPeriodo" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($periodos as $p)
                    <option value="{{ $p->id }}" @selected((string)request('periodo_id')===(string)$p->id)>
                        {{ $p->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroEstado">Estado</label>
                <select id="filtroEstado" class="form-control">
                    <option value="">Todos</option>
                    <option value="abierta" @selected(request('estado')==='abierta' )>Abierta</option>
                    <option value="en_revision" @selected(request('estado')==='en_revision' )>En revisión</option>
                    <option value="autorizada" @selected(request('estado')==='autorizada' )>Autorizada</option>
                    <option value="cerrada" @selected(request('estado')==='cerrada' )>Cerrada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="filtroAutorizado">Autorización</label>
                <select id="filtroAutorizado" class="form-control">
                    <option value="">Todas</option>
                    <option value="1" @selected(request('autorizado')==='1' )>Autorizadas</option>
                    <option value="0" @selected(request('autorizado')==='0' )>No autorizadas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="filtroDocente">Docente</label>
                <select id="filtroDocente" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($docentes as $d)
                    <option value="{{ $d->id }}" @selected((string)request('docente_id')===(string)$d->id)>
                        {{ $d->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroEstudiante">Estudiante</label>
                <select id="filtroEstudiante" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($estudiantes as $e)
                    <option value="{{ $e->id }}" @selected((string)request('estudiante_id')===(string)$e->id)>
                        {{ $e->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTabla(true)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de historias clínicas</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="tablaStatus" aria-live="polite">
                <span class="dot"></span>
                Actualizando
            </span>
            <button type="button" class="btn btn-secondary btn-sm btn-actualizar"
                id="btnActualizarTabla" onclick="recargarTabla(true)" title="Actualizar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <polyline points="23 4 23 10 17 10" />
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                </svg>
                Actualizar
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Paciente</th>
                    <th>Especialidad</th>
                    <th>Periodo</th>
                    <th>Estudiante</th>
                    <th>Docente</th>
                    <th>Estado</th>
                    <th>Autorizada</th>
                    <th>Inicio</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyHistorias">
                @include('director.historias.partials.filas', ['historias' => $historias])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionHistorias">
        @if ($historias->hasPages())
        <span class="info">
            Mostrando <strong>{{ $historias->firstItem() }}</strong> - <strong>{{ $historias->lastItem() }}</strong>
            de <strong>{{ $historias->total() }}</strong> registros
        </span>
        <div>{{ $historias->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

{{-- ==================== MODAL ==================== --}}

@endsection

@push('scripts')
<script>
    /* ============================================================
       CONFIG
       ============================================================ */
    const URL_TABLA_HISTORIAS = "{{ route('director.historias.tabla') }}";
    const URL_BASE_HISTORIAS = "{{ url('director/historias') }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";

    /* ============================================================
       BARRA DE PROGRESO
       ============================================================ */
    let loadingBarTimeout = null;

    function mostrarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        clearTimeout(loadingBarTimeout);
        bar.classList.remove('complete');
        bar.classList.add('active');
    }

    function completarBarraCarga() {
        const bar = document.getElementById('loadingBar');
        if (!bar) return;
        bar.classList.remove('active');
        bar.classList.add('complete');
        loadingBarTimeout = setTimeout(() => {
            bar.classList.remove('complete');
            bar.style.width = '0';
        }, 400);
    }

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    let recargandoTabla = false;

    window.recargarTabla = async function(manual = false) {
        if (recargandoTabla) return;
        recargandoTabla = true;

        const tbody = document.getElementById('tbodyHistorias');
        const status = document.getElementById('tablaStatus');
        const btnActualizar = document.getElementById('btnActualizarTabla');

        mostrarBarraCarga();
        tbody?.classList.add('loading');
        status?.classList.add('visible');
        btnActualizar?.classList.add('spinning');
        if (btnActualizar) btnActualizar.disabled = true;

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_HISTORIAS,
                tbodyId: 'tbodyHistorias',
                paginacionId: 'paginacionHistorias',
                totalId: 'total-historias',
                filtros: {
                    buscar: 'filtroBuscar',
                    clinica_id: 'filtroClinica',
                    periodo_id: 'filtroPeriodo',
                    estado: 'filtroEstado',
                    autorizado: 'filtroAutorizado',
                    docente_id: 'filtroDocente',
                    estudiante_id: 'filtroEstudiante',
                },
            });

            tbody?.querySelectorAll('tr').forEach((tr, i) => {
                tr.style.animation = 'none';
                void tr.offsetHeight;
                tr.style.animation = '';
                tr.style.animationDelay = `${Math.min(i * 0.03, 0.4)}s`;
            });

            if (manual) window.mostrarToast('success', 'Tabla actualizada');
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error al recargar la tabla');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
            btnActualizar?.classList.remove('spinning');
            if (btnActualizar) btnActualizar.disabled = false;
            completarBarraCarga();
            recargandoTabla = false;
        }
    };

    /* ============================================================
       FILTROS
       ============================================================ */
    window.limpiarFiltros = function() {
        ['filtroBuscar', 'filtroClinica', 'filtroPeriodo', 'filtroEstado',
            'filtroAutorizado', 'filtroDocente', 'filtroEstudiante'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        window.recargarTabla();
    };

    /* ============================================================
       MODAL
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalHistoria');
        const form = document.getElementById('formHistoria');
        const titleEl = document.getElementById('modalHistoriaTitle');
        const btnTextEl = document.getElementById('btnGuardarHistoriaText');
        const methodEl = document.getElementById('formHistoriaMethod');
        const idEl = document.getElementById('formHistoriaId');
        const tbodyTrat = document.getElementById('tbodyTratamientosPropuestos');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function filaTratamiento(tr = {}) {
            const tr_ = document.createElement('tr');
            tr_.innerHTML = `
                <td>
                    <select name="tratamientos[][tratamiento_id]" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        ${window.__TRATAMIENTOS_OPTIONS__ || ''}
                    </select>
                    <input type="hidden" name="tratamientos[][id]" value="${tr.id || ''}">
                </td>
                <td><input type="text" name="tratamientos[][pieza_dental]" class="form-control" maxlength="20" value="${tr.pieza_dental || ''}"></td>
                <td><input type="number" step="0.01" min="0" name="tratamientos[][costo_cobrado]" class="form-control" value="${tr.costo_cobrado || 0}"></td>
                <td><input type="text" name="tratamientos[][observacion]" class="form-control" maxlength="500" value="${tr.observacion || ''}"></td>
                <td class="text-right">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">Quitar</button>
                </td>
            `;
            if (tr.tratamiento_id) {
                tr_.querySelector('select').value = tr.tratamiento_id;
            }
            return tr_;
        }

        window.agregarTratamientoPropuesto = function(data = {}) {
            tbodyTrat?.appendChild(filaTratamiento(data));
        };

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.historias.guardar') }}";
            tbodyTrat.innerHTML = '';
            limpiarErrores();
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                form.querySelector('input:not([type="hidden"]), select')?.focus();
            }, 100);
        }

        window.abrirModalCrear = function() {
            resetearFormulario();
            titleEl.textContent = 'Nueva historia clínica';
            btnTextEl.textContent = 'Crear historia';
            abrir();
        };

        window.abrirModalEditar = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar historia clínica';
            btnTextEl.textContent = 'Guardar cambios';
            methodEl.value = 'PUT';
            form.action = `${URL_BASE_HISTORIAS}/${id}`;

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_HISTORIAS}/${id}/json`);
                if (!ok || !data.historia) throw new Error('No se pudo cargar la historia');

                const h = data.historia;
                idEl.value = h.id;
                document.getElementById('m_paciente_id').value = h.paciente_id || '';
                document.getElementById('m_admision_id').value = h.admision_id || '';
                document.getElementById('m_clinica_id').value = h.clinica_id || '';
                document.getElementById('m_periodo_id').value = h.periodo_id || '';
                document.getElementById('m_estudiante_id').value = h.estudiante_id || '';
                document.getElementById('m_docente_id').value = h.docente_id || '';
                document.getElementById('m_fecha_inicio').value = h.fecha_inicio || '';
                document.getElementById('m_fecha_fin').value = h.fecha_fin || '';
                document.getElementById('m_revision_docente').value = h.revision_docente || '';

                tbodyTrat.innerHTML = '';
                (h.tratamientos || []).forEach(t => window.agregarTratamientoPropuesto(t));

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar historia');
            }
        };

        window.cerrarModal = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && backdrop.classList.contains('open')) {
                window.cerrarModal();
            }
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarHistoria');
            const formData = new FormData(form);
            const isEdit = methodEl.value === 'PUT';

            formData.append('_token', CSRF_TOKEN);

            btn.disabled = true;
            btnTextEl.textContent = 'Guardando';
            limpiarErrores();

            try {
                const {
                    ok,
                    status,
                    data
                } = await window.fetchAjax(form.action, {
                    method: 'POST',
                    body: formData,
                });

                if (ok && data.success) {
                    window.cerrarModal();
                    await window.recargarTabla();
                    window.mostrarToast('success', data.message || (isEdit ? 'Historia actualizada' : 'Historia creada'));
                    return;
                }

                if (status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([campo, mensajes]) => {
                        const span = form.querySelector(`[data-error="${campo}"]`);
                        if (span) span.textContent = mensajes[0];
                        const input = form.querySelector(`[name="${campo}"], [name="${campo}[]"]`);
                        input?.classList.add('is-invalid');
                    });
                    window.mostrarToast('warning', 'Revise los campos marcados');
                    return;
                }

                window.mostrarToast('error', data.message || 'Error al guardar');
            } catch (err) {
                window.mostrarToast('error', err.message || 'Error de red');
            } finally {
                btn.disabled = false;
                btnTextEl.textContent = isEdit ? 'Guardar cambios' : 'Crear historia';
            }
        });
    })();

    /* ============================================================
       ACCIONES SOBRE HISTORIAS
       ============================================================ */
    window.autorizarHistoria = async function(id) {
        if (!confirm('Confirma autorizar esta historia clínica?')) return;
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(`${URL_BASE_HISTORIAS}/${id}/autorizar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
            });
            if (ok && data.success) {
                await window.recargarTabla();
                window.mostrarToast('success', data.message);
            } else {
                window.mostrarToast('error', data.message || 'Error al autorizar');
            }
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red');
        }
    };

    window.cerrarHistoria = async function(id) {
        if (!confirm('Confirma cerrar esta historia clínica?')) return;
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(`${URL_BASE_HISTORIAS}/${id}/cerrar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
            });
            if (ok && data.success) {
                await window.recargarTabla();
                window.mostrarToast('success', data.message);
            } else {
                window.mostrarToast('error', data.message || 'Error al cerrar');
            }
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red');
        }
    };

    window.verHistorialPaciente = async function(pacienteId) {
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(
                `${URL_BASE_HISTORIAS}/paciente/${pacienteId}/historial`
            );
            if (!ok || !data.success) throw new Error('No se pudo cargar el historial');

            const cont = document.getElementById('contenidoHistorialPaciente');
            const modal = document.getElementById('modalHistorialPaciente');
            if (!cont || !modal) return;

            const p = data.paciente;
            const historias = data.historias || [];

            let html = `
                <div style="margin-bottom:16px;">
                    <h4 style="font:600 16px -apple-system, sans-serif;">${p.nombre_completo}</h4>
                    <p style="font:400 12px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
                        CI: ${p.ci || '-'} | Genero: ${p.genero || '-'} | Nacimiento: ${p.fecha_nacimiento || '-'}
                    </p>
                </div>
            `;

            if (historias.length === 0) {
                html += '<p class="text-muted">El paciente no tiene historias clínicas registradas.</p>';
            } else {
                html += '<div class="timeline-hc">';
                historias.forEach(h => {
                    const badge = h.autorizado ? 'badge-success' : 'badge-warning';
                    const estadoTxt = h.autorizado ? 'Autorizada' : 'No autorizada';
                    html += `
                        <div class="timeline-item" style="border-left:3px solid var(--primary); padding-left:14px; margin-bottom:18px;">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                                <strong>${h.clinica || '-'}</strong>
                                <span class="badge ${badge}">${estadoTxt}</span>
                            </div>
                            <p style="font:400 12px -apple-system, sans-serif; color:var(--muted); margin-top:4px;">
                                Periodo: ${h.periodo || '-'} | Inicio: ${h.fecha_inicio || '-'} | Fin: ${h.fecha_fin || '-'}
                            </p>
                            <p style="font:400 12px -apple-system, sans-serif; margin-top:4px;">
                                Estudiante: ${h.estudiante || '-'} | Docente: ${h.docente || '-'}
                            </p>
                            ${h.tratamientos && h.tratamientos.length ? `
                                <ul style="margin-top:8px; font:400 12px -apple-system, sans-serif;">
                                    ${h.tratamientos.map(t => `
                                        <li>
                                            ${t.nombre || '-'} - Pieza ${t.pieza_dental || '-'} -
                                            <span class="text-muted">${t.estado || '-'}</span>
                                            - Bs ${t.costo || 0}
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : ''}
                        </div>
                    `;
                });
                html += '</div>';
            }

            cont.innerHTML = html;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error al cargar historial');
        }
    };

    window.cerrarModalHistorial = function() {
        const modal = document.getElementById('modalHistorialPaciente');
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    /* ============================================================
       INICIALIZACION
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('tbodyHistorias');
        tbody?.querySelectorAll('tr').forEach((tr, i) => {
            tr.style.animationDelay = `${Math.min(i * 0.03, 0.4)}s`;
        });

        document.getElementById('filtroBuscar')
            ?.addEventListener('input', window.debounce(() => window.recargarTabla(), 350));

        ['filtroClinica', 'filtroPeriodo', 'filtroEstado', 'filtroAutorizado',
            'filtroDocente', 'filtroEstudiante'
        ].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => window.recargarTabla());
        });

        if (!window.__fetchAjaxWrapped) {
            const originalFetch = window.fetchAjax;
            window.fetchAjax = async function(url, opts) {
                mostrarBarraCarga();
                try {
                    return await originalFetch(url, opts);
                } finally {
                    completarBarraCarga();
                }
            };
            window.__fetchAjaxWrapped = true;
        }
    });
</script>
@endpush