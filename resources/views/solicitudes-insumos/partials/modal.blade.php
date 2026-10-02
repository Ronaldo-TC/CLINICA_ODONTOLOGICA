{{--
    ============================================================
    MODAL DE SOLICITUD DE INSUMOS (crear / editar)
    ============================================================
    Estructura enviada a POST /solicitudes-insumos:
      {
        clinica_id, insumo_tratamiento_id, pieza,
        tratamiento_realizado_id,
        detalles: [{ insumo_id, cantidad_solicitada }]
      }
    API pública: window.SolicitudModal.abrirCrear() / .abrirEdicion(data)
--}}
<div class="modal-backdrop" id="modalSolicitud">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalSolicitudTitulo">
        <form id="formSolicitud" novalidate>
            @csrf
            <input type="hidden" id="formSolicitudId" value="">
            <input type="hidden" id="solTratamientoId" name="tratamiento_realizado_id" value="">

            <div class="modal-header">
                <h3 id="modalSolicitudTitulo">Nueva Solicitud de Insumos</h3>
                <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                {{-- ---------- Datos generales ---------- --}}
                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="form-group">
                        <label for="solClinica">Clínica <span class="req">*</span></label>
                        <select id="solClinica" name="clinica_id" class="form-control" required>
                            <option value="">Seleccione una clínica…</option>
                            @foreach ($clinicas as $c)
                                <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="solTipoInsumo">Tipo de insumo <span class="req">*</span></label>
                        <select id="solTipoInsumo" name="insumo_tratamiento_id" class="form-control" required disabled>
                            <option value="">Primero seleccione una clínica</option>
                        </select>
                        <span class="form-error" data-error-for="insumo_tratamiento_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="solPieza">Pieza dental</label>
                        <input type="number" id="solPieza" name="pieza" class="form-control"
                            min="0" max="99" step="1" placeholder="Ej: 16 (opcional)">
                        <span class="form-error" data-error-for="pieza"></span>
                    </div>

                    {{-- Autocomplete de tratamiento realizado (opcional) --}}
                    <div class="form-group">
                        <label for="solTratamientoInput">Tratamiento realizado</label>
                        <div class="autocomplete-wrap" data-autocomplete="tratamiento">
                            <input type="text" id="solTratamientoInput" class="form-control autocomplete-input"
                                placeholder="Buscar tratamiento (opcional)…" autocomplete="off"
                                role="combobox" aria-expanded="false" aria-autocomplete="list">
                            <span class="autocomplete-loading"></span>
                            <button type="button" class="autocomplete-clear" data-autocomplete-clear hidden
                                aria-label="Quitar tratamiento">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="autocomplete-dropdown" data-autocomplete-dropdown hidden role="listbox"></div>
                        </div>
                        <span class="form-error" data-error-for="tratamiento_realizado_id"></span>
                    </div>
                </div>

                {{-- ---------- Detalle de insumos ---------- --}}
                <div class="sol-items-head">
                    <label style="margin:0;">Insumos solicitados <span class="req">*</span></label>
                    <button type="button" class="btn btn-sm btn-secondary" id="btnAgregarItem">
                        + Agregar insumo
                    </button>
                </div>

                <div class="table-wrap sol-items-wrap">
                    <table class="sol-items-table">
                        <thead>
                            <tr>
                                <th style="min-width:230px;">Insumo</th>
                                <th style="width:110px;">Stock</th>
                                <th style="width:130px;">Cantidad</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItemsSolicitud">
                            {{-- JS agrega las filas --}}
                        </tbody>
                    </table>
                </div>
                <span class="form-error" data-error-for="detalles"></span>

                <div class="sol-resumen" id="solResumen" hidden>
                    <span id="solResumenTotal">0</span> insumo(s) ·
                    <span id="solResumenCosto">Bs 0.00</span> estimado
                </div>

                <div id="formSolicitudError" class="alert alert-danger" style="display:none; margin-top:16px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formSolicitudErrorText">Corrige los errores del formulario.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarSolicitud">
                    <span id="btnGuardarSolicitudText">Enviar Solicitud</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            store: @json(route('solicitudes-insumos.store')),
            tipos: (clinicaId) => `/insumos/tipos-por-clinica/${clinicaId}`,
            insumos: (clinicaId, q) => `/insumos/buscar-insumos/${clinicaId}?q=${encodeURIComponent(q)}`,
            tratamientos: (clinicaId, q) =>
                `/solicitudes-insumos/buscar-tratamientos?clinica_id=${encodeURIComponent(clinicaId || '')}&q=${encodeURIComponent(q)}`,
        };

        const $modal = document.getElementById('modalSolicitud');
        const $form = document.getElementById('formSolicitud');
        const $titulo = document.getElementById('modalSolicitudTitulo');
        const $id = document.getElementById('formSolicitudId');
        const $clinica = document.getElementById('solClinica');
        const $tipo = document.getElementById('solTipoInsumo');
        const $pieza = document.getElementById('solPieza');
        const $tbody = document.getElementById('tbodyItemsSolicitud');
        const $btnAgregar = document.getElementById('btnAgregarItem');
        const $btnGuardar = document.getElementById('btnGuardarSolicitud');
        const $btnGuardarText = document.getElementById('btnGuardarSolicitudText');
        const $errorBox = document.getElementById('formSolicitudError');
        const $errorText = document.getElementById('formSolicitudErrorText');
        const $resumen = document.getElementById('solResumen');
        const $resumenTotal = document.getElementById('solResumenTotal');
        const $resumenCosto = document.getElementById('solResumenCosto');

        let contadorFilas = 0;
        let tipoSeleccionado = '';

        /* ============================================================
           Utilidades
           ============================================================ */
        const escapar = (valor) => (valor ?? '')
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const num = (valor) => {
            const n = parseFloat(valor);
            return Number.isFinite(n) ? n : 0;
        };

        function toast(tipo, mensaje) {
            window.SolicitudesLista?.toast(tipo, mensaje);
        }

        function setLoading(estado) {
            $btnGuardar.disabled = estado;
            $btnGuardarText.textContent = estado
                ? 'Guardando…'
                : ($id.value ? 'Actualizar Solicitud' : 'Enviar Solicitud');
        }

        function limpiarErrores() {
            $modal.querySelectorAll('[data-error-for]').forEach((el) => { el.textContent = ''; });
            $modal.querySelectorAll('.form-control').forEach((el) => el.classList.remove('is-invalid'));
            $errorBox.style.display = 'none';
        }

        function mostrarErrores(errors) {
            Object.entries(errors || {}).forEach(([campo, mensajes]) => {
                const clave = campo.split('.')[0];
                const $err = $modal.querySelector(`[data-error-for="${clave}"]`);
                const texto = Array.isArray(mensajes) ? mensajes[0] : mensajes;
                if ($err) $err.textContent = texto;

                const $input = $modal.querySelector(`[name="${campo}"]`)
                    || $modal.querySelector(`[name^="${clave}"]`);
                if ($input) $input.classList.add('is-invalid');
            });
            $errorText.textContent = 'Corrige los errores marcados en rojo.';
            $errorBox.style.display = 'flex';
        }

        function abrir() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => ($id.value ? $pieza : $clinica)?.focus(), 80);
        }

        function cerrar() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }

        function reset() {
            $form.reset();
            $id.value = '';
            $tbody.innerHTML = '';
            contadorFilas = 0;
            tipoSeleccionado = '';
            $tipo.innerHTML = '<option value="">Primero seleccione una clínica</option>';
            $tipo.disabled = true;
            limpiarErrores();
            resetTratamiento();
            agregarFila();
            actualizarResumen();
        }

        /* ============================================================
           Tipos de insumo por clínica
           ============================================================ */
        async function cargarTipos(clinicaId, seleccionar) {
            tipoSeleccionado = seleccionar || '';

            if (!clinicaId) {
                $tipo.innerHTML = '<option value="">Primero seleccione una clínica</option>';
                $tipo.disabled = true;
                return;
            }

            $tipo.disabled = true;
            $tipo.innerHTML = '<option value="">Cargando…</option>';

            try {
                const res = await fetch(URLS.tipos(clinicaId), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error();
                const tipos = await res.json();

                $tipo.innerHTML = '<option value="">Seleccione un tipo…</option>';
                tipos.forEach((t) => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.nombre;
                    if (String(t.id) === String(tipoSeleccionado)) opt.selected = true;
                    $tipo.appendChild(opt);
                });

                // Si la clínica sólo tiene un tipo, se preselecciona
                if (!tipoSeleccionado && tipos.length === 1) {
                    $tipo.value = tipos[0].id;
                    tipoSeleccionado = String(tipos[0].id);
                }
            } catch (err) {
                $tipo.innerHTML = '<option value="">Error al cargar tipos</option>';
            } finally {
                $tipo.disabled = false;
            }
        }

        $clinica?.addEventListener('change', () => cargarTipos($clinica.value, ''));

        /* ============================================================
           Filas de insumos con autocomplete
           ============================================================ */
        function agregarFila(datos = null) {
            contadorFilas += 1;
            const fila = contadorFilas;

            const tr = document.createElement('tr');
            tr.dataset.fila = String(fila);
            tr.innerHTML = `
                <td>
                    <div class="autocomplete-wrap" data-autocomplete="insumo">
                        <input type="text" class="form-control autocomplete-input"
                            placeholder="Buscar insumo…" autocomplete="off"
                            role="combobox" aria-expanded="false" aria-autocomplete="list"
                            value="${escapar(datos?.insumo_nombre || '')}">
                        <span class="autocomplete-loading" hidden><span class="spinner"></span></span>
                        <button type="button" class="autocomplete-clear" data-autocomplete-clear hidden
                            aria-label="Quitar insumo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                        <div class="autocomplete-dropdown" data-autocomplete-dropdown hidden role="listbox"></div>
                    </div>
                    <input type="hidden" class="item-insumo-id" name="detalles[${fila}][insumo_id]"
                        value="${datos?.insumo_id ?? ''}">
                    <input type="hidden" class="item-costo" value="${num(datos?.costo)}">
                </td>
                <td>
                    <span class="badge badge-muted item-stock">
                        ${datos ? num(datos.stock_actual).toFixed(2) : '—'}
                    </span>
                    <div class="item-unidad">${escapar(datos?.unidad || '')}</div>
                </td>
                <td>
                    <input type="number" class="form-control item-cantidad"
                        name="detalles[${fila}][cantidad_solicitada]"
                        min="0.01" step="0.01" required
                        value="${datos ? num(datos.cantidad_solicitada).toFixed(2) : '1.00'}">
                </td>
                <td>
                    <button type="button" class="btn-item-remove" data-remove-fila
                        title="Eliminar fila" aria-label="Eliminar fila">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                            <path d="M10 11v6M14 11v6" />
                        </svg>
                    </button>
                </td>`;

            $tbody.appendChild(tr);

            const wrap = tr.querySelector('[data-autocomplete="insumo"]');
            inicializarAutocomplete(wrap, {
                buscar: (q) => {
                    const clinicaId = $clinica.value;
                    if (!clinicaId) {
                        toast('warning', 'Selecciona primero una clínica.');
                        return Promise.resolve([]);
                    }
                    return fetch(URLS.insumos(clinicaId, q), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    }).then((r) => (r.ok ? r.json() : []));
                },
                alElegir: (item) => {
                    tr.querySelector('.item-insumo-id').value = item.id;
                    tr.querySelector('.item-costo').value = num(item.costo);
                    tr.querySelector('.item-stock').textContent = num(item.stock).toFixed(2);
                    tr.querySelector('.item-unidad').textContent = item.unidad || '';
                    actualizarResumen();
                },
                alLimpiar: () => {
                    tr.querySelector('.item-insumo-id').value = '';
                    tr.querySelector('.item-costo').value = '0';
                    tr.querySelector('.item-stock').textContent = '—';
                    tr.querySelector('.item-unidad').textContent = '';
                    actualizarResumen();
                },
            });

            if (datos?.insumo_nombre) {
                wrap.classList.add('has-value');
            }

            return tr;
        }

        $btnAgregar?.addEventListener('click', () => {
            if (!$clinica.value) {
                toast('warning', 'Selecciona primero una clínica.');
                return;
            }
            const tr = agregarFila();
            tr.querySelector('.autocomplete-input')?.focus();
        });

        $tbody?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-fila]');
            if (!btn) return;

            const filas = $tbody.querySelectorAll('tr');
            if (filas.length <= 1) {
                toast('warning', 'Debe quedar al menos una fila.');
                return;
            }
            btn.closest('tr').remove();
            actualizarResumen();
        });

        $tbody?.addEventListener('input', (e) => {
            if (e.target.classList.contains('item-cantidad')) actualizarResumen();
        });

        /* ============================================================
           Autocomplete reutilizable (debounce + AbortController)
           ============================================================ */
        function inicializarAutocomplete(wrap, opciones) {
            const $input = wrap.querySelector('.autocomplete-input');
            const $dropdown = wrap.querySelector('[data-autocomplete-dropdown]');
            const $clear = wrap.querySelector('[data-autocomplete-clear]');
            const $loading = wrap.querySelector('.autocomplete-loading');

            let timer = null;
            let controller = null;
            let indice = -1;
            let resultados = [];

            const cerrarDropdown = () => {
                $dropdown.classList.remove('open');
                $dropdown.innerHTML = '';
                $input.setAttribute('aria-expanded', 'false');
                indice = -1;
            };

            const pintar = () => {
                if (resultados.length === 0) {
                    $dropdown.innerHTML = '<div class="autocomplete-item autocomplete-empty">Sin resultados</div>';
                    $dropdown.classList.add('open');
                    return;
                }

                $dropdown.innerHTML = resultados.map((item, i) => `
                    <div class="autocomplete-item ${i === indice ? 'active' : ''}"
                        data-index="${i}" role="option">
                        <div class="autocomplete-info">
                            <span class="autocomplete-name">${escapar(item.texto)}</span>
                            <span class="autocomplete-item-extra">${escapar(item.extra || '')}</span>
                        </div>
                    </div>`).join('');
                $dropdown.classList.add('open');
            };

            const elegir = (item) => {
                $input.value = item.texto;
                wrap.classList.add('has-value');
                cerrarDropdown();
                opciones.alElegir(item);
            };

            async function buscar(termino) {
                if (controller) controller.abort();
                controller = new AbortController();
                wrap.classList.add('loading');

                try {
                    const data = await opciones.buscar(termino);
                    resultados = Array.isArray(data) ? data : [];
                    indice = -1;
                    pintar();
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        resultados = [];
                        pintar();
                    }
                } finally {
                    wrap.classList.remove('loading');
                }
            }

            $input.addEventListener('input', () => {
                const termino = $input.value.trim();

                // Al escribir se invalida la selección previa
                opciones.alLimpiar();

                if (!termino) {
                    wrap.classList.remove('has-value');
                    cerrarDropdown();
                    return;
                }

                clearTimeout(timer);
                timer = setTimeout(() => buscar(termino), 300);
            });

            $input.addEventListener('keydown', (e) => {
                if ($dropdown.hidden || resultados.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    indice = (indice + 1) % resultados.length;
                    pintar();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    indice = (indice - 1 + resultados.length) % resultados.length;
                    pintar();
                } else if (e.key === 'Enter' && indice >= 0) {
                    e.preventDefault();
                    elegir(resultados[indice]);
                } else if (e.key === 'Escape') {
                    cerrarDropdown();
                }
            });

            $dropdown.addEventListener('click', (e) => {
                const item = e.target.closest('[data-index]');
                if (!item) return;
                elegir(resultados[Number(item.dataset.index)]);
            });

            $clear?.addEventListener('click', () => {
                $input.value = '';
                wrap.classList.remove('has-value');
                cerrarDropdown();
                opciones.alLimpiar();
                $input.focus();
            });

            document.addEventListener('click', (e) => {
                if (!wrap.contains(e.target)) cerrarDropdown();
            });
        }

        /* ============================================================
           Tratamiento realizado (opcional)
           ============================================================ */
        const $tratWrap = $modal.querySelector('[data-autocomplete="tratamiento"]');
        const $tratInput = document.getElementById('solTratamientoInput');
        const $tratId = document.getElementById('solTratamientoId');

        if ($tratWrap) {
            inicializarAutocomplete($tratWrap, {
                buscar: (q) => fetch(URLS.tratamientos($clinica.value, q), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                }).then((r) => (r.ok ? r.json() : [])),
                alElegir: (item) => {
                    $tratId.value = item.id;
                    if (item.pieza && !$pieza.value) $pieza.value = item.pieza;
                },
                alLimpiar: () => { $tratId.value = ''; },
            });
        }

        function resetTratamiento() {
            if ($tratInput) $tratInput.value = '';
            if ($tratId) $tratId.value = '';
            $tratWrap?.classList.remove('has-value');
        }

        /* ============================================================
           Resumen
           ============================================================ */
        function actualizarResumen() {
            const filas = Array.from($tbody.querySelectorAll('tr'));
            let total = 0;
            let costo = 0;

            filas.forEach((tr) => {
                const cantidad = num(tr.querySelector('.item-cantidad')?.value);
                if (tr.querySelector('.item-insumo-id')?.value) {
                    total += 1;
                    costo += cantidad * num(tr.querySelector('.item-costo')?.value);
                }
            });

            $resumen.hidden = total === 0;
            $resumenTotal.textContent = String(total);
            $resumenCosto.textContent = 'Bs ' + costo.toFixed(2);
        }

        /* ============================================================
           Submit
           ============================================================ */
        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            limpiarErrores();

            if (!$clinica.value) {
                mostrarErrores({ clinica_id: ['Debe seleccionar una clínica.'] });
                return;
            }
            if (!$tipo.value) {
                mostrarErrores({ insumo_tratamiento_id: ['Debe seleccionar un tipo de insumo.'] });
                return;
            }

            const detalles = [];
            let errorFila = null;

            $tbody.querySelectorAll('tr').forEach((tr) => {
                const insumoId = tr.querySelector('.item-insumo-id')?.value;
                const cantidad = num(tr.querySelector('.item-cantidad')?.value);

                if (!insumoId && cantidad <= 0) return;      // fila vacía: se ignora
                if (!insumoId) {
                    errorFila = errorFila || 'Hay una fila sin insumo seleccionado.';
                    return;
                }
                if (cantidad <= 0) {
                    errorFila = errorFila || 'Todas las cantidades deben ser mayores a 0.';
                    return;
                }

                detalles.push({
                    insumo_id: parseInt(insumoId, 10),
                    cantidad_solicitada: cantidad,
                });
            });

            if (errorFila) {
                $errorText.textContent = errorFila;
                $errorBox.style.display = 'flex';
                return;
            }

            if (detalles.length === 0) {
                mostrarErrores({ detalles: ['Debe agregar al menos un insumo.'] });
                return;
            }

            const id = $id.value;
            const payload = {
                clinica_id: parseInt($clinica.value, 10),
                insumo_tratamiento_id: parseInt($tipo.value, 10),
                pieza: $pieza.value === '' ? 0 : parseInt($pieza.value, 10),
                tratamiento_realizado_id: $tratId.value ? parseInt($tratId.value, 10) : null,
                detalles,
            };

            setLoading(true);

            try {
                const res = await fetch(id ? `${URLS.store}/${id}` : URLS.store, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (res.status === 422) {
                    mostrarErrores(data.errors || {});
                    throw new Error(data.message || 'Revisa los campos del formulario.');
                }
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo guardar la solicitud.');
                }

                cerrar();
                toast('success', data.message || 'Solicitud registrada.');
                window.dispatchEvent(new Event('solicitud:guardada'));
            } catch (err) {
                if (!$errorBox.style.display || $errorBox.style.display === 'none') {
                    $errorText.textContent = err.message || 'Error inesperado.';
                    $errorBox.style.display = 'flex';
                }
            } finally {
                setLoading(false);
            }
        });

        /* ============================================================
           Cierre del modal
           ============================================================ */
        $modal?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]')) cerrar();
            if (e.target === $modal) cerrar();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) cerrar();
        });

        /* ============================================================
           API pública
           ============================================================ */
        window.SolicitudModal = {
            abrirCrear() {
                reset();
                $titulo.textContent = 'Nueva Solicitud de Insumos';
                $btnGuardarText.textContent = 'Enviar Solicitud';
                abrir();
            },

            abrirEdicion(data) {
                const sol = data.registro || data;
                reset();

                $titulo.textContent = 'Editar Solicitud #' + (sol.id ?? '');
                $btnGuardarText.textContent = 'Actualizar Solicitud';
                $id.value = sol.id ?? '';

                // Clínica + tipos (la carga de tipos es asíncrona)
                $clinica.value = sol.clinica_id ?? '';
                cargarTipos(sol.clinica_id ?? '', sol.insumo_tratamiento_id ?? '');

                $pieza.value = sol.pieza ?? '';
                $tratId.value = sol.tratamiento_realizado_id ?? '';
                if (sol.tratamiento_realizado_id) {
                    $tratInput.value = 'Tratamiento #' + sol.tratamiento_realizado_id;
                    $tratWrap?.classList.add('has-value');
                }

                // Detalles
                $tbody.innerHTML = '';
                contadorFilas = 0;

                const detalles = data.detalles || [];
                if (detalles.length === 0) {
                    agregarFila();
                } else {
                    detalles.forEach((d) => agregarFila(d));
                }

                actualizarResumen();
                abrir();
            },

            cerrar,
        };
    })();
</script>
@endpush
