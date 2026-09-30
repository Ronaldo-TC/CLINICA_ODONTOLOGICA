@extends('layouts.app')

@section('title', 'Tratamientos')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Tratamientos</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra el catálogo de tratamientos por clínica o especialidad
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-tratamientos">{{ $tratamientos->total() }}</strong> tratamientos
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscarTratamiento">Buscar</label>
                <input type="text" id="filtroBuscarTratamiento" class="form-control"
                    placeholder="Nombre del tratamiento…"
                    value="{{ request('buscar') }}">
            </div>

            <div class="form-group">
                <label for="filtroClinicaTratamiento">Clínica</label>
                <select id="filtroClinicaTratamiento" class="form-control">
                    <option value="">— Todas —</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}" @selected(request('clinica_id')==$c->id)>
                        {{ $c->especialidad }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaTratamientos()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosTratamientos()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de tratamientos</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusTratamiento">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaTratamientos(true)" title="Actualizar">
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
                    <th>Clínica</th>
                    <th>Costo</th>
                    <th>% PG</th>
                    <th>Descripción</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyTratamientos">
                @include('director.tratamientos.partials.filas', ['tratamientos' => $tratamientos])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionTratamientos">
        @if ($tratamientos->hasPages())
        <span class="info">
            Mostrando <strong>{{ $tratamientos->firstItem() }}</strong>–<strong>{{ $tratamientos->lastItem() }}</strong>
            de <strong>{{ $tratamientos->total() }}</strong> registros
        </span>
        <div>{{ $tratamientos->withQueryString()->links() }}</div>
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
    const URL_TABLA_TRATAMIENTOS = "{{ route('director.tratamientos.tabla') }}";
    const URL_BASE_TRATAMIENTOS = "{{ url('admin/tratamientos') }}";

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaTratamientos = async function(manual = false) {
        const tbody = document.getElementById('tbodyTratamientos');
        const status = document.getElementById('tablaStatusTratamiento');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_TRATAMIENTOS,
                tbodyId: 'tbodyTratamientos',
                paginacionId: 'paginacionTratamientos',
                totalId: 'total-tratamientos',
                filtros: {
                    buscar: 'filtroBuscarTratamiento',
                    clinica_id: 'filtroClinicaTratamiento',
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

            if (manual) window.mostrarToast('success', 'Tratamientos actualizados');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosTratamientos = function() {
        document.getElementById('filtroBuscarTratamiento').value = '';
        document.getElementById('filtroClinicaTratamiento').value = '';
        window.recargarTablaTratamientos();
    };

    /* ============================================================
       MODAL: CREAR/EDITAR TRATAMIENTO
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalTratamiento');
        const form = document.getElementById('formTratamiento');
        const titleEl = document.getElementById('modalTratamientoTitle');
        const btnText = document.getElementById('btnGuardarTratamientoText');
        const methodEl = document.getElementById('formTratamientoMethod');
        const idEl = document.getElementById('formTratamientoId');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.tratamientos.guardar') }}";
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

        window.abrirModalTratamiento = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo tratamiento';
            btnText.textContent = 'Crear tratamiento';
            abrir();
        };

        window.abrirModalEditarTratamiento = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar tratamiento';
            btnText.textContent = 'Guardar cambios';

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_TRATAMIENTOS}/${id}/json`);
                if (!ok || !data.tratamiento) throw new Error('No se pudo cargar');

                const t = data.tratamiento;
                idEl.value = t.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_TRATAMIENTOS}/${t.id}`;

                document.getElementById('m_tra_nombre').value = t.nombre || '';
                document.getElementById('m_tra_costo').value = t.costo || 0;
                document.getElementById('m_tra_clinica_id').value = t.clinica_id || '';
                document.getElementById('m_tra_porcentaje_pg').value = t.porcentaje_pg || '';
                document.getElementById('m_tra_descripcion').value = t.descripcion || '';

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar');
            }
        };

        window.cerrarModalTratamiento = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalTratamiento();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarTratamiento');
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
                    window.cerrarModalTratamiento();
                    await window.recargarTablaTratamientos();
                    window.mostrarToast('success', data.message || (isEdit ? 'Tratamiento actualizado' : 'Tratamiento creado'));
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
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear tratamiento';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaTratamientos();

        document.getElementById('filtroBuscarTratamiento')
            ?.addEventListener('input', window.debounce(window.recargarTablaTratamientos, 300));

        document.getElementById('filtroClinicaTratamiento')
            ?.addEventListener('change', window.recargarTablaTratamientos);
    });
</script>
@endpush