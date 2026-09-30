@extends('layouts.app')

@section('title', 'Clínicas')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Clínicas y Especialidades</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra las clínicas y especialidades de la Carrera de Odontología
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-clinicas">{{ $clinicas->total() }}</strong> clínicas
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscarClinica">Buscar</label>
                <input type="text" id="filtroBuscarClinica" class="form-control"
                    placeholder="Especialidad o módulo…"
                    value="{{ request('buscar') }}">
            </div>

            <div class="form-group">
                <label for="filtroModulo">Módulo</label>
                <select id="filtroModulo" class="form-control">
                    <option value="">— Todos —</option>
                    @php
                    $modulos = \DB::table('clinicas')->distinct()->pluck('modulo')->filter()->sort();
                    @endphp
                    @foreach ($modulos as $m)
                    <option value="{{ $m }}" @selected(request('modulo')===$m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaClinicas()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosClinicas()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de clínicas</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusClinica">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaClinicas(true)" title="Actualizar">
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
                    <th>Módulo</th>
                    <th>Especialidad</th>
                    <th>Curso</th>
                    <th>Creada</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyClinicas">
                @include('director.clinicas.partials.filas', ['clinicas' => $clinicas])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionClinicas">
        @if ($clinicas->hasPages())
        <span class="info">
            Mostrando <strong>{{ $clinicas->firstItem() }}</strong>–<strong>{{ $clinicas->lastItem() }}</strong>
            de <strong>{{ $clinicas->total() }}</strong> registros
        </span>
        <div>{{ $clinicas->withQueryString()->links() }}</div>
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
    const URL_TABLA_CLINICAS = "{{ route('director.clinicas.tabla') }}";
    const URL_BASE_CLINICAS = "{{ url('admin/clinicas') }}";

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaClinicas = async function(manual = false) {
        const tbody = document.getElementById('tbodyClinicas');
        const status = document.getElementById('tablaStatusClinica');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_CLINICAS,
                tbodyId: 'tbodyClinicas',
                paginacionId: 'paginacionClinicas',
                totalId: 'total-clinicas',
                filtros: {
                    buscar: 'filtroBuscarClinica',
                    modulo: 'filtroModulo',
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

            if (manual) window.mostrarToast('success', 'Clínicas actualizadas');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosClinicas = function() {
        document.getElementById('filtroBuscarClinica').value = '';
        document.getElementById('filtroModulo').value = '';
        window.recargarTablaClinicas();
    };

    /* ============================================================
       MODAL: CREAR/EDITAR CLÍNICA
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalClinica');
        const form = document.getElementById('formClinica');
        const titleEl = document.getElementById('modalClinicaTitle');
        const btnText = document.getElementById('btnGuardarClinicaText');
        const methodEl = document.getElementById('formClinicaMethod');
        const idEl = document.getElementById('formClinicaId');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.clinicas.guardar') }}";
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

        window.abrirModalClinica = function() {
            resetearFormulario();
            titleEl.textContent = 'Nueva clínica';
            btnText.textContent = 'Crear clínica';
            abrir();
        };

        window.abrirModalEditarClinica = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar clínica';
            btnText.textContent = 'Guardar cambios';

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_CLINICAS}/${id}/json`);
                if (!ok || !data.clinica) throw new Error('No se pudo cargar la clínica');

                const c = data.clinica;
                idEl.value = c.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_CLINICAS}/${c.id}`;

                document.getElementById('m_clinica_modulo').value = c.modulo || '';
                document.getElementById('m_clinica_especialidad').value = c.especialidad || '';
                document.getElementById('m_clinica_curso').value = c.curso ?? -1;

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar la clínica');
            }
        };

        window.cerrarModalClinica = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalClinica();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarClinica');
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
                    window.cerrarModalClinica();
                    await window.recargarTablaClinicas();
                    window.mostrarToast('success', data.message || (isEdit ? 'Clínica actualizada' : 'Clínica creada'));
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
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear clínica';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaClinicas();

        document.getElementById('filtroBuscarClinica')
            ?.addEventListener('input', window.debounce(window.recargarTablaClinicas, 300));

        document.getElementById('filtroModulo')
            ?.addEventListener('change', window.recargarTablaClinicas);
    });
</script>
@endpush