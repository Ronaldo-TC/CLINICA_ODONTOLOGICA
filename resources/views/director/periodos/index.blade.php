@extends('layouts.app')

@section('title', 'Periodos Académicos')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Periodos Académicos</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra las gestiones y semestres de la Carrera de Odontología
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-periodos">{{ $periodos->total() }}</strong> periodos
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscarPeriodo">Buscar</label>
                <input type="text" id="filtroBuscarPeriodo" class="form-control"
                    placeholder="Nombre del periodo…"
                    value="{{ request('buscar') }}">
            </div>

            <div class="form-group">
                <label for="filtroGestion">Gestión</label>
                <select id="filtroGestion" class="form-control">
                    <option value="">— Todas —</option>
                    @php
                    $gestiones = \DB::table('periodos')->distinct()->orderByDesc('gestion')->pluck('gestion');
                    @endphp
                    @foreach ($gestiones as $g)
                    <option value="{{ $g }}" @selected(request('gestion')==$g)>{{ $g }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroSemestre">Semestre</label>
                <select id="filtroSemestre" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="1" @selected(request('semestre')==='1' )>Semestre 1</option>
                    <option value="2" @selected(request('semestre')==='2' )>Semestre 2</option>
                </select>
            </div>

            <div class="form-group">
                <label for="filtroHabilitadoPeriodo">Estado</label>
                <select id="filtroHabilitadoPeriodo" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="1" @selected(request('habilitado')==='1' )>Habilitados</option>
                    <option value="0" @selected(request('habilitado')==='0' )>Deshabilitados</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaPeriodos()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosPeriodos()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de periodos</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusPeriodo">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaPeriodos(true)" title="Actualizar">
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
                    <th>Nombre</th>
                    <th>Gestión</th>
                    <th>Semestre</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyPeriodos">
                @include('director.periodos.partials.filas', ['periodos' => $periodos])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionPeriodos">
        @if ($periodos->hasPages())
        <span class="info">
            Mostrando <strong>{{ $periodos->firstItem() }}</strong>–<strong>{{ $periodos->lastItem() }}</strong>
            de <strong>{{ $periodos->total() }}</strong> registros
        </span>
        <div>{{ $periodos->withQueryString()->links() }}</div>
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
    const URL_TABLA_PERIODOS = "{{ route('director.periodos.tabla') }}";
    const URL_BASE_PERIODOS = "{{ url('admin/periodos') }}";

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaPeriodos = async function(manual = false) {
        const tbody = document.getElementById('tbodyPeriodos');
        const status = document.getElementById('tablaStatusPeriodo');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_PERIODOS,
                tbodyId: 'tbodyPeriodos',
                paginacionId: 'paginacionPeriodos',
                totalId: 'total-periodos',
                filtros: {
                    buscar: 'filtroBuscarPeriodo',
                    gestion: 'filtroGestion',
                    semestre: 'filtroSemestre',
                    habilitado: 'filtroHabilitadoPeriodo',
                },
            });

            requestAnimationFrame(() => {
                tbody?.querySelectorAll('tr').forEach((tr, i) => {
                    tr.style.animation = 'none';
                    tr.offsetHeight;
                    tr.style.animation = 'filaEntra 0.35s ease-out backwards';
                    tr.style.animationDelay = `${Math.min(i * 0.03, 0.3)}s`;
                });
            });

            if (manual) window.mostrarToast('success', 'Periodos actualizados');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosPeriodos = function() {
        document.getElementById('filtroBuscarPeriodo').value = '';
        document.getElementById('filtroGestion').value = '';
        document.getElementById('filtroSemestre').value = '';
        document.getElementById('filtroHabilitadoPeriodo').value = '';
        window.recargarTablaPeriodos();
    };

    /* ============================================================
       MODAL: CREAR/EDITAR PERIODO
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalPeriodo');
        const form = document.getElementById('formPeriodo');
        const titleEl = document.getElementById('modalPeriodoTitle');
        const btnText = document.getElementById('btnGuardarPeriodoText');
        const methodEl = document.getElementById('formPeriodoMethod');
        const idEl = document.getElementById('formPeriodoId');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.periodos.guardar') }}";
            document.getElementById('m_per_habilitado').checked = true;
            limpiarErrores();
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                form.querySelector('input:not([type="hidden"])')?.focus();
            }, 100);
        }

        window.abrirModalPeriodo = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo periodo';
            btnText.textContent = 'Crear periodo';
            abrir();
        };

        window.abrirModalEditarPeriodo = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar periodo';
            btnText.textContent = 'Guardar cambios';

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_PERIODOS}/${id}/json`);
                if (!ok || !data.periodo) throw new Error('No se pudo cargar');

                const p = data.periodo;
                idEl.value = p.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_PERIODOS}/${p.id}`;

                document.getElementById('m_per_nombre').value = p.nombre || '';
                document.getElementById('m_per_gestion').value = p.gestion || '';
                document.getElementById('m_per_semestre').value = p.semestre || '';
                document.getElementById('m_per_fecha_inicio').value = p.fecha_inicio ?
                    String(p.fecha_inicio).substring(0, 10) : '';
                document.getElementById('m_per_fecha_fin').value = p.fecha_fin ?
                    String(p.fecha_fin).substring(0, 10) : '';
                document.getElementById('m_per_habilitado').checked = !!p.habilitado;

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar');
            }
        };

        window.cerrarModalPeriodo = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };
        /* ============================================================
           TOGGLE HABILITAR / DESHABILITAR PERIODO
           ============================================================ */
        window.toggleHabilitadoPeriodo = async function(id, estaHabilitado) {
            const accion = estaHabilitado ? 'deshabilitar' : 'habilitar';

            if (!confirm(`¿Está seguro de ${accion} este periodo?`)) return;

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(
                    `{{ url('admin/periodos') }}/${id}/toggle`, {
                        method: 'PATCH'
                    }
                );

                if (ok && data.success) {
                    window.mostrarToast('success', data.message || `Periodo ${accion}do`);
                    await window.recargarTablaPeriodos();
                } else {
                    window.mostrarToast('error', data.message || 'No se pudo cambiar el estado');
                }
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error de red');
            }
        };
        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalPeriodo();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarPeriodo');
            const formData = new FormData(form);
            const isEdit = methodEl.value === 'PUT';

            btn.disabled = true;
            btnText.textContent = 'Guardando…';
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
                    window.cerrarModalPeriodo();
                    await window.recargarTablaPeriodos();
                    window.mostrarToast('success', data.message || (isEdit ? 'Periodo actualizado' : 'Periodo creado'));
                    return;
                }

                if (status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([campo, mensajes]) => {
                        const span = form.querySelector(`[data-error="${campo}"]`);
                        if (span) span.textContent = mensajes[0];
                        form.querySelector(`[name="${campo}"]`)?.classList.add('is-invalid');
                    });
                    window.mostrarToast('warning', 'Revisa los campos marcados');
                    return;
                }

                window.mostrarToast('error', data.message || 'Error al guardar');
            } catch (err) {
                window.mostrarToast('error', err.message || 'Error de red');
            } finally {
                btn.disabled = false;
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear periodo';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaPeriodos();

        document.getElementById('filtroBuscarPeriodo')
            ?.addEventListener('input', window.debounce(window.recargarTablaPeriodos, 300));

        ['filtroGestion', 'filtroSemestre', 'filtroHabilitadoPeriodo'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', window.recargarTablaPeriodos);
        });
    });
</script>
@endpush