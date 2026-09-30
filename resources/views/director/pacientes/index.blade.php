@extends('layouts.app')

@section('title', 'Pacientes')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Gestión de Pacientes</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra los datos de los pacientes de la clínica
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-pacientes">{{ $pacientes->total() }}</strong> pacientes
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscarPaciente">Buscar</label>
                <input type="text" id="filtroBuscarPaciente" class="form-control"
                    placeholder="CI, nombre o apellido…"
                    value="{{ request('buscar') }}">
            </div>

            <div class="form-group">
                <label for="filtroGenero">Género</label>
                <select id="filtroGenero" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="Masculino" @selected(request('genero')==='Masculino' )>Masculino</option>
                    <option value="Femenino" @selected(request('genero')==='Femenino' )>Femenino</option>
                    <option value="Otro" @selected(request('genero')==='Otro' )>Otro</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaPacientes()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosPacientes()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de pacientes</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusPaciente">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaPacientes(true)" title="Actualizar">
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
                    <th>CI</th>
                    <th>Nombre completo</th>
                    <th>Género</th>
                    <th>Edad</th>
                    <th>Fecha de nacimiento</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyPacientes">
                @include('director.pacientes.partials.filas', ['pacientes' => $pacientes])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionPacientes">
        @if ($pacientes->hasPages())
        <span class="info">
            Mostrando <strong>{{ $pacientes->firstItem() }}</strong>–<strong>{{ $pacientes->lastItem() }}</strong>
            de <strong>{{ $pacientes->total() }}</strong> registros
        </span>
        <div>{{ $pacientes->withQueryString()->links() }}</div>
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
    const URL_TABLA_PACIENTES = "{{ route('director.pacientes.tabla') }}";
    const URL_BASE_PACIENTES = "{{ url('director/pacientes') }}";

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaPacientes = async function(manual = false) {
        const tbody = document.getElementById('tbodyPacientes');
        const status = document.getElementById('tablaStatusPaciente');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            await window.recargarTablaGenerica({
                urlTabla: URL_TABLA_PACIENTES,
                tbodyId: 'tbodyPacientes',
                paginacionId: 'paginacionPacientes',
                totalId: 'total-pacientes',
                filtros: {
                    buscar: 'filtroBuscarPaciente',
                    genero: 'filtroGenero',
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

            if (manual) window.mostrarToast('success', 'Pacientes actualizados');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosPacientes = function() {
        document.getElementById('filtroBuscarPaciente').value = '';
        document.getElementById('filtroGenero').value = '';
        window.recargarTablaPacientes();
    };

    /* ============================================================
       MODAL: CREAR/EDITAR PACIENTE
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalPaciente');
        const form = document.getElementById('formPaciente');
        const titleEl = document.getElementById('modalPacienteTitle');
        const btnText = document.getElementById('btnGuardarPacienteText');
        const methodEl = document.getElementById('formPacienteMethod');
        const idEl = document.getElementById('formPacienteId');

        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.pacientes.guardar') }}";
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

        window.abrirModalPaciente = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo paciente';
            btnText.textContent = 'Crear paciente';
            abrir();
        };

        window.abrirModalEditarPaciente = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar paciente';
            btnText.textContent = 'Guardar cambios';

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(`${URL_BASE_PACIENTES}/${id}/json`);
                if (!ok || !data.paciente) throw new Error('No se pudo cargar');

                const p = data.paciente;
                idEl.value = p.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_PACIENTES}/${p.id}`;

                document.getElementById('m_pac_ci').value = p.ci || '';
                document.getElementById('m_pac_nombre').value = p.nombre || '';
                document.getElementById('m_pac_apellido_paterno').value = p.apellido_paterno || '';
                document.getElementById('m_pac_apellido_materno').value = p.apellido_materno || '';
                document.getElementById('m_pac_genero').value = p.genero || '';
                document.getElementById('m_pac_fecha_nacimiento').value = p.fecha_nacimiento ?
                    String(p.fecha_nacimiento).substring(0, 10) :
                    '';

                abrir();
            } catch (e) {
                window.mostrarToast('error', e.message || 'Error al cargar');
            }
        };

        window.cerrarModalPaciente = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalPaciente();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnGuardarPaciente');
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
                    window.cerrarModalPaciente();
                    await window.recargarTablaPacientes();
                    window.mostrarToast('success', data.message || (isEdit ? 'Paciente actualizado' : 'Paciente creado'));
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
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear paciente';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaPacientes();

        document.getElementById('filtroBuscarPaciente')
            ?.addEventListener('input', window.debounce(window.recargarTablaPacientes, 300));

        document.getElementById('filtroGenero')
            ?.addEventListener('change', window.recargarTablaPacientes);
    });
</script>
@endpush