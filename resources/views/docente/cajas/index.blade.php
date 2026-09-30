@extends('layouts.app')

@section('title', 'Cajas')

@section('content')

{{-- ==================== HEADER ==================== --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
    <div>
        <h1 style="font:600 20px -apple-system, sans-serif; letter-spacing:-0.02em;">Control de Cajas</h1>
        <p style="font:400 13px -apple-system, sans-serif; color:var(--muted); margin-top:3px;">
            Registro de cobros y pagos de los tratamientos
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="abrirModalCaja()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Nuevo registro
    </button>
</div>

{{-- ==================== TOTALES ==================== --}}
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        </div>
        <div class="metric-value" id="total-costo">Bs {{ number_format($totales['total'] ?? 0, 2) }}</div>
        <div class="metric-label">Total facturado</div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #16a34a, #15803d);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M20 6L9 17l-5-5" />
            </svg>
        </div>
        <div class="metric-value" id="total-cancelado">Bs {{ number_format($totales['cancelado'] ?? 0, 2) }}</div>
        <div class="metric-label">Total cancelado</div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
        </div>
        <div class="metric-value" id="total-saldo">Bs {{ number_format($totales['saldo'] ?? 0, 2) }}</div>
        <div class="metric-label">Saldo pendiente</div>
    </div>
</div>

{{-- ==================== FILTROS ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Filtros de búsqueda</h2>
        <span class="card-meta">
            Total: <strong id="total-cajas">{{ $cajas->total() }}</strong> registros
        </span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label for="filtroFechaInicio">Fecha inicio</label>
                <input type="date" id="filtroFechaInicio" class="form-control"
                    value="{{ request('fecha_inicio') }}">
            </div>

            <div class="form-group">
                <label for="filtroFechaFin">Fecha fin</label>
                <input type="date" id="filtroFechaFin" class="form-control"
                    value="{{ request('fecha_fin') }}">
            </div>

            <div class="form-group">
                <label for="filtroTipoPago">Tipo de pago</label>
                <select id="filtroTipoPago" class="form-control">
                    <option value="">— Todos —</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="QR">QR</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Tarjeta">Tarjeta</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="recargarTablaCajas()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    Buscar
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosCajas()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TABLA ==================== --}}
<div class="card">
    <div class="card-header">
        <h2>Listado de movimientos</h2>
        <div class="card-header-actions" style="display:flex; align-items:center; gap:10px;">
            <span class="tabla-status" id="tablaStatusCaja">
                <span class="dot"></span>
                Actualizando…
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="recargarTablaCajas(true)">
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
                    <th>Recibo</th>
                    <th>Paciente</th>
                    <th>Tipo pago</th>
                    <th>Total</th>
                    <th>Cancelado</th>
                    <th>Saldo</th>
                    <th>Fecha</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyCajas">
                @include('admin.cajas.partials.filas', ['cajas' => $cajas])
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap" id="paginacionCajas">
        @if ($cajas->hasPages())
        <span class="info">
            Mostrando <strong>{{ $cajas->firstItem() }}</strong>–<strong>{{ $cajas->lastItem() }}</strong>
            de <strong>{{ $cajas->total() }}</strong> registros
        </span>
        <div>{{ $cajas->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

{{-- ==================== MODAL ==================== --}}
@include('admin.cajas.partials.modal')

@endsection

@push('scripts')
<script>
    /* ============================================================
   CONFIG
   ============================================================ */
    const URL_TABLA_CAJAS = "{{ route('admin.cajas.tabla') }}";
    const URL_BUSCAR_PACIENTES = "{{ route('admin.cajas.buscar.pacientes') }}";

    /* ============================================================
       HELPERS
       ============================================================ */
    function formatBs(v) {
        const n = Number(v) || 0;
        return 'Bs ' + n.toLocaleString('es-BO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function iniciales(nombre) {
        return (nombre || '')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(w => (w[0] || '').toUpperCase())
            .join('');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ============================================================
       AUTOCOMPLETE DE PACIENTES CON AUTO-SELECCIÓN
       ============================================================ */
    (function() {
        const wrap = document.getElementById('pacienteAutocompleteWrap');
        const input = document.getElementById('m_buscar_paciente');
        const dropdown = document.getElementById('pacienteDropdown');
        const btnLimpiar = document.getElementById('btnLimpiarPaciente');
        const chipWrap = document.getElementById('pacienteSeleccionadoContainer');

        const hiddenId = document.getElementById('m_caja_paciente_id');
        const hiddenCi = document.getElementById('m_caja_ci');
        const hiddenNombre = document.getElementById('m_caja_paciente_nombre_completo');

        if (!wrap || !input || !dropdown) return;

        let timer = null;
        let controller = null;
        let indiceActivo = -1;
        let pacientes = [];

        /* ---------- Render dropdown ---------- */
        function renderDropdown(lista) {
            pacientes = lista || [];
            indiceActivo = -1;

            if (pacientes.length === 0) {
                dropdown.innerHTML = `
                <div class="autocomplete-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Sin resultados para "<strong>${escapeHtml(input.value)}</strong>"
                </div>`;
                dropdown.classList.add('open');
                return;
            }

            dropdown.innerHTML = pacientes.map((p, i) => `
            <div class="autocomplete-item" data-index="${i}">
                <div class="autocomplete-avatar">${iniciales(p.nombre_completo)}</div>
                <div class="autocomplete-info">
                    <div class="autocomplete-name">${escapeHtml(p.nombre_completo)}</div>
                    <div class="autocomplete-meta">CI: ${escapeHtml(p.ci)}${p.genero ? ' · ' + escapeHtml(p.genero) : ''}</div>
                </div>
            </div>
        `).join('');

            dropdown.classList.add('open');
        }

        /* ---------- Seleccionar ---------- */
        function seleccionarPaciente(p) {
            hiddenId.value = p.id;
            hiddenCi.value = p.ci;
            hiddenNombre.value = p.nombre_completo;

            input.value = '';
            dropdown.classList.remove('open');
            wrap.classList.add('has-value');
            wrap.classList.remove('has-error');

            chipWrap.innerHTML = `
            <div class="paciente-seleccionado">
                <div class="avatar">${iniciales(p.nombre_completo)}</div>
                <div class="info">
                    <div class="nombre">${escapeHtml(p.nombre_completo)}</div>
                    <div class="detalles">CI: ${escapeHtml(p.ci)}${p.genero ? ' · ' + escapeHtml(p.genero) : ''}</div>
                </div>
                <button type="button" class="quitar" onclick="quitarPacienteCaja()" title="Quitar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
        `;

            window.mostrarToast('success', `Paciente: ${p.nombre_completo}`);
        }

        window.quitarPacienteCaja = function() {
            hiddenId.value = '';
            hiddenCi.value = '';
            hiddenNombre.value = '';
            input.value = '';
            chipWrap.innerHTML = '';
            wrap.classList.remove('has-value');
            wrap.classList.remove('has-error');
            dropdown.classList.remove('open');
        };

        btnLimpiar?.addEventListener('click', window.quitarPacienteCaja);

        /* ============================================================
           BÚSQUEDA + AUTO-SELECCIÓN
           ============================================================
           - autoseleccionar=true → si hay 1 solo resultado → selecciona
           - autoseleccionar=true → si el término coincide con un CI exacto → selecciona
        */
        async function buscar(termino, autoseleccionar = false) {
            if (controller) controller.abort();
            controller = new AbortController();

            wrap.classList.add('loading');

            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(
                    `${URL_BUSCAR_PACIENTES}?q=${encodeURIComponent(termino)}`, {
                        signal: controller.signal
                    }
                );

                const lista = (ok && data.success) ? (data.pacientes || []) : [];

                // ✅ Auto-selección: único resultado
                if (autoseleccionar && lista.length === 1) {
                    seleccionarPaciente(lista[0]);
                    return;
                }

                // ✅ Auto-selección: CI exacto
                if (autoseleccionar && lista.length > 0) {
                    const exacto = lista.find(p => String(p.ci).trim() === String(termino).trim());
                    if (exacto) {
                        seleccionarPaciente(exacto);
                        return;
                    }
                }

                renderDropdown(lista);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    console.error(e);
                    renderDropdown([]);
                }
            } finally {
                wrap.classList.remove('loading');
            }
        }

        /* ---------- Input: debounce ---------- */
        input.addEventListener('input', function() {
            const termino = this.value.trim();
            clearTimeout(timer);

            if (termino.length < 2) {
                dropdown.classList.remove('open');
                return;
            }

            timer = setTimeout(() => buscar(termino, false), 250);
        });

        /* ---------- Blur: intentar auto-seleccionar ---------- */
        input.addEventListener('blur', function() {
            setTimeout(() => {
                const termino = input.value.trim();
                if (termino.length >= 2) {
                    buscar(termino, true);
                }
            }, 200);
        });

        /* ---------- Teclado ---------- */
        input.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.autocomplete-item');
            const dropdownOpen = dropdown.classList.contains('open');

            if (e.key === 'ArrowDown' && dropdownOpen && items.length) {
                e.preventDefault();
                indiceActivo = Math.min(indiceActivo + 1, items.length - 1);
                items.forEach((it, i) => it.classList.toggle('active', i === indiceActivo));
                items[indiceActivo]?.scrollIntoView({
                    block: 'nearest'
                });
                return;
            }

            if (e.key === 'ArrowUp' && dropdownOpen && items.length) {
                e.preventDefault();
                indiceActivo = Math.max(indiceActivo - 1, 0);
                items.forEach((it, i) => it.classList.toggle('active', i === indiceActivo));
                items[indiceActivo]?.scrollIntoView({
                    block: 'nearest'
                });
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();

                if (dropdownOpen && indiceActivo >= 0 && pacientes[indiceActivo]) {
                    seleccionarPaciente(pacientes[indiceActivo]);
                    return;
                }

                const termino = input.value.trim();
                if (termino.length >= 2) {
                    buscar(termino, true);
                }
                return;
            }

            if (e.key === 'Escape') {
                dropdown.classList.remove('open');
            }
        });

        /* ---------- Click en un ítem ---------- */
        dropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.autocomplete-item');
            if (!item) return;
            const idx = Number(item.dataset.index);
            if (pacientes[idx]) seleccionarPaciente(pacientes[idx]);
        });

        /* ---------- Cerrar al hacer clic fuera ---------- */
        document.addEventListener('click', function(e) {
            if (!wrap.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    })();

    /* ============================================================
       AUTOCOMPLETAR COSTO AL ELEGIR TRATAMIENTO
       ============================================================ */
    document.getElementById('m_caja_tratamiento_id')?.addEventListener('change', function() {
        const costo = this.options[this.selectedIndex]?.dataset?.costo;
        if (costo && Number(costo) > 0) {
            document.getElementById('m_caja_costo_total').value = Number(costo).toFixed(2);
        }
    });

    /* ============================================================
       MODAL: ABRIR / CERRAR
       ============================================================ */
    (function() {
        const backdrop = document.getElementById('modalCaja');
        const form = document.getElementById('formCaja');
        if (!backdrop || !form) return;

        function limpiarErrores() {
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
            document.getElementById('pacienteAutocompleteWrap')?.classList.remove('has-error');
        }

        function resetearFormulario() {
            form.reset();
            limpiarErrores();

            // Reset autocomplete
            document.getElementById('m_caja_paciente_id').value = '';
            document.getElementById('m_caja_ci').value = '';
            document.getElementById('m_caja_paciente_nombre_completo').value = '';
            document.getElementById('m_buscar_paciente').value = '';
            document.getElementById('pacienteDropdown').classList.remove('open');
            document.getElementById('pacienteAutocompleteWrap')?.classList.remove('has-value');
            document.getElementById('pacienteSeleccionadoContainer').innerHTML = '';

            // Reset usuario
            document.getElementById('m_caja_usuario_id').value = "{{ auth()->id() }}";

            // Reset montos
            document.getElementById('m_caja_costo_total').value = '0';
            document.getElementById('m_caja_cancelado').value = '0';
            document.getElementById('m_caja_monto').value = '0';
        }

        function abrir() {
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => document.getElementById('m_caja_recibo')?.focus(), 100);
        }

        window.abrirModalCaja = function() {
            resetearFormulario();
            abrir();
        };

        window.cerrarModalCaja = function() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        backdrop.addEventListener('mousedown', function(e) {
            if (e.target === backdrop) window.cerrarModalCaja();
        });

        /* ---------- SUBMIT ---------- */
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const nombrePaciente = document.getElementById('m_caja_paciente_nombre_completo').value;

            if (!nombrePaciente || !nombrePaciente.trim()) {
                const wrap = document.getElementById('pacienteAutocompleteWrap');
                const inputBuscar = document.getElementById('m_buscar_paciente');

                wrap?.classList.add('has-error');
                inputBuscar?.focus();
                setTimeout(() => wrap?.classList.remove('has-error'), 2000);

                window.mostrarToast('warning', 'Debes seleccionar un paciente antes de guardar.');
                return;
            }

            const btn = document.getElementById('btnGuardarCaja');
            const formData = new FormData(form);

            btn.disabled = true;
            btn.querySelector('span').textContent = 'Guardando…';
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
                    window.cerrarModalCaja();
                    await window.recargarTablaCajas();
                    window.mostrarToast('success', data.message || 'Registro creado');
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
                btn.querySelector('span').textContent = 'Crear registro';
            }
        });
    })();

    /* ============================================================
       RECARGAR TABLA
       ============================================================ */
    window.recargarTablaCajas = async function(manual = false) {
        const tbody = document.getElementById('tbodyCajas');
        const status = document.getElementById('tablaStatusCaja');

        tbody?.classList.add('loading');
        status?.classList.add('visible');

        try {
            const params = new URLSearchParams();
            const filtros = {
                fecha_inicio: 'filtroFechaInicio',
                fecha_fin: 'filtroFechaFin',
                tipo_pago: 'filtroTipoPago',
            };
            Object.entries(filtros).forEach(([key, elId]) => {
                const el = document.getElementById(elId);
                if (el && el.value !== '') params.append(key, el.value);
            });

            const {
                ok,
                data
            } = await window.fetchAjax(`${URL_TABLA_CAJAS}?${params}`);
            if (!ok) throw new Error('Error al cargar');

            tbody.innerHTML = data.filas;
            document.getElementById('paginacionCajas').innerHTML = data.paginacion || '';
            document.getElementById('total-cajas').textContent = data.total;

            if (data.totales) {
                document.getElementById('total-costo').textContent = formatBs(data.totales.total);
                document.getElementById('total-cancelado').textContent = formatBs(data.totales.cancelado);
                document.getElementById('total-saldo').textContent = formatBs(data.totales.saldo);
            }

            requestAnimationFrame(() => {
                tbody.querySelectorAll('tr').forEach((tr, i) => {
                    tr.style.animation = 'none';
                    tr.offsetHeight;
                    tr.style.animation = 'filaEntra 0.35s ease-out backwards';
                    tr.style.animationDelay = `${Math.min(i * 0.03, 0.3)}s`;
                });
            });

            if (manual) window.mostrarToast('success', 'Cajas actualizadas');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="9" class="empty-state">Error: ${e.message}</td></tr>`;
            window.mostrarToast('error', e.message || 'Error');
        } finally {
            tbody?.classList.remove('loading');
            status?.classList.remove('visible');
        }
    };

    window.limpiarFiltrosCajas = function() {
        document.getElementById('filtroFechaInicio').value = '';
        document.getElementById('filtroFechaFin').value = '';
        document.getElementById('filtroTipoPago').value = '';
        window.recargarTablaCajas();
    };

    /* ============================================================
       VER DETALLE
       ============================================================ */
    window.verDetalleCaja = async function(id) {
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(`{{ url('admin/cajas') }}/${id}/json`);
            if (!ok || !data.caja) throw new Error('No se pudo cargar');

            const c = data.caja;
            const saldo = (c.costo_total || 0) - (c.cancelado || 0);
            alert(
                `Recibo: #${c.recibo}\n` +
                `Paciente: ${c.paciente_nombre_completo || '—'}\n` +
                `Tratamiento: ${c.tratamiento || '—'}\n` +
                `Clínica: ${c.clinica || '—'}\n` +
                `Total: Bs ${Number(c.costo_total || 0).toFixed(2)}\n` +
                `Cancelado: Bs ${Number(c.cancelado || 0).toFixed(2)}\n` +
                `Saldo: Bs ${saldo.toFixed(2)}\n` +
                `Tipo de pago: ${c.tipo_pago || '—'}`
            );
        } catch (e) {
            window.mostrarToast('error', e.message || 'Error');
        }
    };

    /* ============================================================
       INICIALIZACIÓN
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function() {
        window.recargarTablaCajas();

        ['filtroFechaInicio', 'filtroFechaFin', 'filtroTipoPago'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', window.recargarTablaCajas);
        });
    });
</script>
@endpush