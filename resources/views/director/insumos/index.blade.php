@extends('layouts.app')

@section('title', 'Insumos')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Inventario de Insumos</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Administra los insumos y materiales de cada clínica
        </p>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-insumos">{{ $insumos->total() }}</strong> insumos
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroBuscarInsumo">Buscar</label>
                <input type="text" id="filtroBuscarInsumo" class="form-control"
                    placeholder="Nombre del insumo…"
                    value="{{ request('buscar') }}">
            </div>

            <div class="form-group">
                <label for="filtroClinicaInsumo">Clínica</label>
                <select id="filtroClinicaInsumo" class="form-control">
                    <option value="">— Todas —</option>
                    @foreach ($clinicas as $c)
                    <option value="{{ $c->id }}" @selected(request('clinica_id')==$c->id)>
                        {{ $c->especialidad }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="filtroBajoStock">Stock</label>
                <select id="filtroBajoStock" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="1" @selected(request('bajo_stock')=='1' )>Solo bajo stock</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaInsumos()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosInsumos()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de insumos</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusInsumo">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaInsumos(true)" title="Actualizar">
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
                    <th>Tipo</th>
                    <th>Clínica</th>
                    <th>Cantidad</th>
                    <th>Stock mín.</th>
                    <th>Costo unit.</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyInsumos">
                @include('director.insumos.partials.filas', ['insumos' => $insumos])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionInsumos">
        @if ($insumos->hasPages())
        <span class="info">
            Mostrando <strong>{{ $insumos->firstItem() }}</strong>–<strong>{{ $insumos->lastItem() }}</strong>
            de <strong>{{ $insumos->total() }}</strong> registros
        </span>
        <div>{{ $insumos->withQueryString()->links() }}</div>
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
    const URL_TABLA_INSUMOS = "{{ route('director.insumos.tabla') }}";
    const URL_BASE_INSUMOS = "{{ url('admin/insumos') }}";
    const URL_TRATAMIENTOS_X_CLINICA = "{{ url('admin/insumos/tratamientos-por-clinica') }}";

    console.log('URL_TABLA_INSUMOS:', URL_TABLA_INSUMOS);
    console.log('URL_TRATAMIENTOS_X_CLINICA:', URL_TRATAMIENTOS_X_CLINICA);

    /* ============================================================
       HELPERS
       ============================================================ */
    function escapeHtmlInsumo(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* ============================================================
       CARGAR TIPOS DE INSUMO POR CLÍNICA
       ============================================================ */
    async function cargarTratamientosPorClinica(clinicaId, selectId, valorSeleccionado = '') {
        console.log('cargarTratamientosPorClinica() llamado:', {
            clinicaId,
            selectId,
            valorSeleccionado
        });

        const select = document.getElementById(selectId);
        if (!select) {
            console.error('No se encontró el select con id:', selectId);
            return;
        }

        select.innerHTML = '<option value="">— Cargando… —</option>';
        select.disabled = true;

        if (!clinicaId) {
            select.innerHTML = '<option value="">— Primero selecciona una clínica —</option>';
            select.disabled = false;
            return;
        }

        const url = `${URL_TRATAMIENTOS_X_CLINICA}/${clinicaId}`;
        console.log('Fetch URL:', url);

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            console.log('Status:', res.status);

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }

            const data = await res.json();
            console.log('Data recibida:', data);

            const items = data.data || [];

            if (items.length === 0) {
                select.innerHTML = '<option value="">— Sin tipos registrados para esta clínica —</option>';
                select.disabled = false;
                return;
            }

            select.innerHTML = '<option value="">— Seleccionar —</option>' +
                items.map(it => `
                    <option value="${it.id}" ${String(it.id) === String(valorSeleccionado) ? 'selected' : ''}>
                        ${escapeHtmlInsumo(it.nombre)}
                    </option>
                `).join('');
            select.disabled = false;

            console.log('Select actualizado con', items.length, 'opciones');
        } catch (e) {
            console.error('Error cargando tipos:', e);
            select.innerHTML = '<option value="">— Error al cargar tipos —</option>';
            select.disabled = false;
        }
    }

    // Exponer globalmente para debug
    window.cargarTratamientosPorClinica = cargarTratamientosPorClinica;

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaInsumos = async function(manual = false) {
        const tbody = document.getElementById('tbodyInsumos');
        const status = document.getElementById('tablaStatusInsumo');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            const params = new URLSearchParams();
            ['filtroBuscarInsumo', 'filtroClinicaInsumo', 'filtroBajoStock'].forEach(elId => {
                const el = document.getElementById(elId);
                if (el && el.value !== '') {
                    const key = elId.replace('filtro', '').toLowerCase();
                    params.append(key === 'buscar' ? 'buscar' : (key === 'clinicaid' ? 'clinica_id' : 'bajo_stock'), el.value);
                }
            });

            const res = await fetch(`${URL_TABLA_INSUMOS}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (tbody) tbody.innerHTML = data.filas;
            const pag = document.getElementById('paginacionInsumos');
            if (pag) pag.innerHTML = data.paginacion || '';
            const total = document.getElementById('total-insumos');
            if (total) total.textContent = data.total;

            if (manual && window.mostrarToast) window.mostrarToast('success', 'Insumos actualizados');
        } catch (e) {
            console.error('Error recargando tabla:', e);
            if (window.mostrarToast) window.mostrarToast('error', e.message);
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosInsumos = function() {
        document.getElementById('filtroBuscarInsumo').value = '';
        document.getElementById('filtroClinicaInsumo').value = '';
        document.getElementById('filtroBajoStock').value = '';
        window.recargarTablaInsumos();
    };

    /* ============================================================
       MODAL
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalInsumo');
        const form = document.getElementById('formInsumo');
        const titleEl = document.getElementById('modalInsumoTitle');
        const btnText = document.getElementById('btnGuardarInsumoText');
        const methodEl = document.getElementById('formInsumoMethod');
        const idEl = document.getElementById('formInsumoId');

        if (!backdrop || !form) {
            console.error('Modal no encontrado');
            return;
        }

        // ✅ LISTENER DEL SELECT DE CLÍNICA
        const selClinica = document.getElementById('m_ins_clinica_id');
        if (selClinica) {
            selClinica.addEventListener('change', function() {
                console.log('Clínica cambiada a:', this.value);
                cargarTratamientosPorClinica(this.value, 'm_ins_insumo_tratamiento_id');
            });
            console.log('Listener de clínica registrado');
        } else {
            console.error('No se encontró m_ins_clinica_id');
        }

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function resetearFormulario() {
            form.reset();
            idEl.value = '';
            methodEl.value = 'POST';
            form.action = "{{ route('admin.insumos.guardar') }}";

            const selTipos = document.getElementById('m_ins_insumo_tratamiento_id');
            if (selTipos) {
                selTipos.innerHTML = '<option value="">— Primero selecciona una clínica —</option>';
                selTipos.disabled = false;
            }
            limpiarErrores();
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => document.getElementById('m_ins_nombre')?.focus(), 100);
        }

        window.abrirModalInsumo = function() {
            resetearFormulario();
            titleEl.textContent = 'Nuevo insumo';
            btnText.textContent = 'Crear insumo';
            abrir();
        };

        window.abrirModalEditarInsumo = async function(id) {
            resetearFormulario();
            titleEl.textContent = 'Editar insumo';
            btnText.textContent = 'Guardar cambios';

            try {
                const res = await fetch(`${URL_BASE_INSUMOS}/${id}/json`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok || !data.insumo) throw new Error('No se pudo cargar');

                const i = data.insumo;
                idEl.value = i.id;
                methodEl.value = 'PUT';
                form.action = `${URL_BASE_INSUMOS}/${i.id}`;

                document.getElementById('m_ins_nombre').value = i.nombre || '';
                document.getElementById('m_ins_cantidad_numerica').value = i.cantidad_numerica || 0;
                document.getElementById('m_ins_cantidad').value = i.cantidad || '';
                document.getElementById('m_ins_stock_minimo').value = i.stock_minimo || 0;
                document.getElementById('m_ins_costo_unitario').value = i.costo_unitario || 0;

                const clinicaSelect = document.getElementById('m_ins_clinica_id');
                clinicaSelect.value = i.clinica_id || '';

                if (i.clinica_id) {
                    await cargarTratamientosPorClinica(
                        i.clinica_id,
                        'm_ins_insumo_tratamiento_id',
                        i.insumo_tratamiento_id || ''
                    );
                }

                abrir();
            } catch (e) {
                console.error(e);
                if (window.mostrarToast) window.mostrarToast('error', e.message);
            }
        };

        window.cerrarModalInsumo = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalInsumo();
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarInsumo');
            const formData = new FormData(form);
            const isEdit = methodEl.value === 'PUT';

            btn.disabled = true;
            btnText.textContent = 'Guardando…';
            limpiarErrores();

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token'),
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    window.cerrarModalInsumo();
                    await window.recargarTablaInsumos();
                    if (window.mostrarToast) {
                        window.mostrarToast('success', data.message || (isEdit ? 'Insumo actualizado' : 'Insumo creado'));
                    }
                    return;
                }

                if (res.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([campo, mensajes]) => {
                        const span = form.querySelector(`[data-error="${campo}"]`);
                        if (span) span.textContent = mensajes[0];
                        form.querySelector(`[name="${campo}"]`)?.classList.add('is-invalid');
                    });
                    if (window.mostrarToast) window.mostrarToast('warning', 'Revisa los campos marcados');
                    return;
                }

                if (window.mostrarToast) window.mostrarToast('error', data.message || 'Error al guardar');
            } catch (err) {
                console.error(err);
                if (window.mostrarToast) window.mostrarToast('error', err.message);
            } finally {
                btn.disabled = false;
                btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear insumo';
            }
        });
    })();

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaInsumos();

        document.getElementById('filtroBuscarInsumo')?.addEventListener('input', function() {
            clearTimeout(window._debounceBuscar);
            window._debounceBuscar = setTimeout(() => window.recargarTablaInsumos(), 300);
        });

        ['filtroClinicaInsumo', 'filtroBajoStock'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', window.recargarTablaInsumos);
        });
    });
</script>
@endpush