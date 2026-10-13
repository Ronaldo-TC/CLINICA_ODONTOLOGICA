@extends('layouts.app')

@section('title', 'Especialidades')
@section('page-title', 'Asignación de Especialidades')
@section('page-subtitle', 'Gestiona las clínicas asignadas a estudiantes y docentes')

@section('content')

{{-- ============================================================
         TOOLBAR
         ============================================================ --}}
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <span class="toolbar-dot live"></span>
        <span>Total: <strong id="total-especialidades">{{ $asignaciones->total() }}</strong> asignaciones</span>
    </div>

    <div class="toolbar-actions">
        @can('clinicas.gestionar')
        <button type="button" class="btn btn-primary" onclick="abrirModalCrear()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Nueva asignación
        </button>
        @endcan
    </div>
</div>

{{-- ============================================================
         FILTROS
         ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros</h2>
    </div>

    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscar">Buscar</label>
                <input type="text" id="filtroBuscar" class="form-control"
                    placeholder="Nombre o CI...">
            </div>

            <div class="form-group">
                <label for="filtroClinica">Clínica</label>
                <select id="filtroClinica" class="form-control">
                    <option value="">Todas las clínicas</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}">
                        {{ $c->modulo }} — {{ $c->especialidad }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroEstado">Estado</label>
                <select id="filtroEstado" class="form-control">
                    <option value="">Todos</option>
                    <option value="1">Habilitadas</option>
                    <option value="0">Deshabilitadas</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
         BARRA DE ACCIONES EN LOTE (aparece al seleccionar filas)
         ============================================================ --}}
<div id="bulkBar" class="card" style="display:none;">
    <div class="card-body" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <span class="badge badge-info">
            <strong id="bulkCount">0</strong> seleccionadas
        </span>
        <button type="button" class="btn btn-primary btn-sm" onclick="bulkAccion(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            Habilitar
        </button>
        <button type="button" class="btn btn-danger btn-sm" onclick="bulkAccion(0)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
            Deshabilitar
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="limpiarSeleccion()">
            Cancelar
        </button>
    </div>
</div>

{{-- ============================================================
         TABLA
         ============================================================ --}}
<div class="card">
    <div class="card-header">
        <h2>Asignaciones</h2>
        <div class="card-header-actions">
            <span class="tabla-status" id="tablaStatus">
                <span class="dot"></span> Actualizando...
            </span>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="checkAll" aria-label="Seleccionar todo"
                            onchange="toggleCheckAll(this)">
                    </th>
                    <th>Usuario</th>
                    <th>CI</th>
                    <th>Clínica</th>
                    <th>Módulo</th>
                    <th>Rotación</th>
                    <th>Resp. admisión</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyEspecialidades">
                @include('especialidades.partials.filas', ['asignaciones' => $asignaciones])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionEspecialidades">
        {{ $asignaciones->links() }}
    </div>
</div>

{{-- Modal --}}
@include('especialidades.partials.modal', [
'usuarios' => $usuarios,
'clinicas' => $clinicas,
])

@endsection

@push('scripts')
<script>
    /* ============================================================
   CONFIGURACIÓN GLOBAL
   ============================================================ */
    const URL_TABLA = "{{ route('especialidades.tabla') }}";
    const URL_BASE = "{{ route('especialidades.index') }}";
    const URL_STORE = "{{ route('especialidades.store') }}";
    const URL_BULK = "{{ route('especialidades.bulk-update') }}";

    /* ============================================================
       FILTROS AJAX
       ============================================================ */
    const recargarEspecialidades = window.debounce(() => {
        window.recargarTablaGenerica({
            urlTabla: URL_TABLA,
            tbodyId: 'tbodyEspecialidades',
            paginacionId: 'paginacionEspecialidades',
            totalId: 'total-especialidades',
            filtros: {
                buscar: 'filtroBuscar',
                clinica_id: 'filtroClinica',
                estado: 'filtroEstado',
            },
            extra: () => limpiarSeleccion(),
        });
    }, 300);

    document.getElementById('filtroBuscar')?.addEventListener('input', recargarEspecialidades);
    document.getElementById('filtroClinica')?.addEventListener('change', recargarEspecialidades);
    document.getElementById('filtroEstado')?.addEventListener('change', recargarEspecialidades);

    /* ============================================================
       SELECCIÓN Y BULK UPDATE
       ============================================================ */
    function toggleCheckAll(source) {
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.checked = source.checked;
        });
        actualizarBulkBar();
    }

    function limpiarSeleccion() {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
        const checkAll = document.getElementById('checkAll');
        if (checkAll) checkAll.checked = false;
        actualizarBulkBar();
    }

    function actualizarBulkBar() {
        const seleccionadas = document.querySelectorAll('.row-check:checked');
        const bar = document.getElementById('bulkBar');
        const count = document.getElementById('bulkCount');

        if (seleccionadas.length > 0) {
            bar.style.display = 'block';
            count.textContent = seleccionadas.length;
        } else {
            bar.style.display = 'none';
        }
    }

    // Delegación: cambio en cualquier checkbox de fila
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-check')) {
            actualizarBulkBar();
        }
    });

    async function bulkAccion(habilitado) {
        const seleccionadas = Array.from(document.querySelectorAll('.row-check:checked'));

        if (seleccionadas.length === 0) {
            window.mostrarToast('warning', 'Selecciona al menos una asignación.');
            return;
        }

        // Agrupar por usuario_id (cada usuario puede tener varias clínicas)
        const porUsuario = {};
        seleccionadas.forEach(cb => {
            const usuarioId = cb.dataset.usuario;
            const clinicaId = cb.dataset.clinica;
            if (!porUsuario[usuarioId]) porUsuario[usuarioId] = [];
            porUsuario[usuarioId].push(clinicaId);
        });

        if (!confirm(`¿${habilitado ? 'Habilitar' : 'Deshabilitar'} ${seleccionadas.length} asignación(es)?`)) return;

        try {
            for (const [usuarioId, clinicaIds] of Object.entries(porUsuario)) {
                const {
                    ok,
                    data
                } = await window.fetchAjax(URL_BULK, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        usuario_id: parseInt(usuarioId, 10),
                        clinica_ids: clinicaIds.map(id => parseInt(id, 10)),
                        habilitado: !!habilitado,
                    }),
                });

                if (!ok || !data.success) {
                    window.mostrarToast('error', data?.message || 'Error en la operación.');
                    return;
                }
            }

            window.mostrarToast('success', 'Asignaciones actualizadas.');
            limpiarSeleccion();
            recargarEspecialidades();

        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red.');
        }
    }

    /* ============================================================
       MODAL: CREAR
       ============================================================ */
    const modalEspecialidad = document.getElementById('modalEspecialidad');
    const formEspecialidad = document.getElementById('formEspecialidad');
    const formEspecialidadMethod = document.getElementById('formEspecialidadMethod');
    const modalEspecialidadTitle = document.getElementById('modalEspecialidadTitle');
    const btnGuardarText = document.getElementById('btnGuardarEspecialidadText');

    function limpiarErrores() {
        formEspecialidad.querySelectorAll('[data-error]').forEach(el => el.textContent = '');
        formEspecialidad.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    function limpiarFormulario() {
        formEspecialidad.reset();
        formEspecialidad.querySelectorAll('input[name="clinica_ids[]"]').forEach(cb => cb.checked = false);
        limpiarErrores();
    }

    function abrirModal() {
        modalEspecialidad.classList.add('open');
        modalEspecialidad.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function cerrarModal() {
        modalEspecialidad.classList.remove('open');
        modalEspecialidad.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }
    window.cerrarModal = cerrarModal;

    window.abrirModalCrear = function() {
        limpiarFormulario();
        modalEspecialidadTitle.textContent = 'Nueva asignación de especialidades';
        formEspecialidadMethod.value = 'POST';
        formEspecialidad.action = URL_STORE;
        btnGuardarText.textContent = 'Asignar';
        abrirModal();
    };

    /* ============================================================
       SUBMIT DEL FORMULARIO
       ============================================================ */
    formEspecialidad?.addEventListener('submit', async function(e) {
        e.preventDefault();
        limpiarErrores();

        const btnSubmit = document.getElementById('btnGuardarEspecialidad');
        btnSubmit.disabled = true;

        try {
            const formData = new FormData(formEspecialidad);

            // Normalizar checkbox responsable_admision (FormData no envía si no está marcado)
            if (!formData.has('responsable_admision')) {
                formData.append('responsable_admision', '0');
            }

            const {
                ok,
                data
            } = await window.fetchAjax(formEspecialidad.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-HTTP-Method-Override': formEspecialidadMethod.value,
                    'Accept': 'application/json',
                },
            });

            if (ok && data.success) {
                window.mostrarToast('success', data.message || 'Asignación creada.');
                cerrarModal();
                recargarEspecialidades();
                return;
            }

            if (data?.errors) {
                Object.entries(data.errors).forEach(([campo, mensajes]) => {
                    const span = formEspecialidad.querySelector(`[data-error="${campo}"]`);
                    if (span) span.textContent = Array.isArray(mensajes) ? mensajes[0] : mensajes;
                });
                window.mostrarToast('error', 'Revisa los campos.');
                return;
            }

            window.mostrarToast('error', data?.message || 'Error al guardar.');

        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red.');
        } finally {
            btnSubmit.disabled = false;
        }
    });

    /* ============================================================
       HABILITAR / DESHABILITAR INDIVIDUAL (delegado)
       ============================================================ */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-toggle-especialidad');
        if (!btn) return;

        e.preventDefault();
        const usuarioId = btn.dataset.usuario;
        const clinicaId = btn.dataset.clinica;
        const habilitar = btn.dataset.habilitar === '1';

        btn.disabled = true;
        btn.style.opacity = '0.5';

        try {
            const {
                ok,
                data
            } = await window.fetchAjax(
                `${URL_BASE}/${usuarioId}/${clinicaId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        habilitado: habilitar ? 1 : 0
                    }),
                }
            );

            if (ok && data.success) {
                window.mostrarToast('success', data.message || 'Actualizado.');
                recargarEspecialidades();
            } else {
                window.mostrarToast('error', data?.message || 'Error.');
            }
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red.');
        } finally {
            btn.disabled = false;
            btn.style.opacity = '';
        }
    });

    /* ============================================================
       ELIMINAR ASIGNACIÓN (delegado)
       ============================================================ */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-eliminar-especialidad');
        if (!btn) return;

        e.preventDefault();
        const usuarioId = btn.dataset.usuario;
        const clinicaId = btn.dataset.clinica;
        const nombre = btn.dataset.nombre || 'la asignación';

        if (!await window.confirmarEliminacion(nombre)) return;

        btn.disabled = true;
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(
                `${URL_BASE}/${usuarioId}/${clinicaId}`, {
                    method: 'DELETE'
                }
            );

            if (ok && data.success) {
                window.mostrarToast('success', data.message || 'Eliminada.');
                recargarEspecialidades();
            } else {
                window.mostrarToast('error', data?.message || 'Error.');
            }
        } catch (err) {
            window.mostrarToast('error', err.message || 'Error de red.');
        } finally {
            btn.disabled = false;
        }
    });

    /* ============================================================
       Cerrar modal al hacer clic fuera
       ============================================================ */
    modalEspecialidad?.addEventListener('click', function(e) {
        if (e.target === modalEspecialidad) cerrarModal();
    });
</script>
@endpush
