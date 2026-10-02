<div class="modal-backdrop" id="modalKardexDetalle">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalKardexTitulo">
        <div class="modal-header">
            <h3 id="modalKardexTitulo">Detalle del paciente</h3>
            <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="metrics-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-value" id="kardex-total-admisiones">0</span>
                        <span class="stat-label">Admisiones</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-value" id="kardex-total-historias">0</span>
                        <span class="stat-label">Historias clínicas</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-value" id="kardex-total-tratamientos">0</span>
                        <span class="stat-label">Tratamientos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-value" id="kardex-total-pagado">0,00 Bs</span>
                        <span class="stat-label">Total pagado</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-value" id="kardex-saldo-pendiente">0,00 Bs</span>
                        <span class="stat-label">Saldo pendiente</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Admisiones</h2>
                    <span class="badge badge-info" id="kardex-badge-admisiones">0</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periodo</th>
                                <th>Estado civil</th>
                                <th>Ocupación</th>
                                <th>Registro</th>
                            </tr>
                        </thead>
                        <tbody id="kardex-admisiones"></tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Historias clínicas</h2>
                    <span class="badge badge-info" id="kardex-badge-historias">0</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Clínica</th>
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th>Autorizado</th>
                            </tr>
                        </thead>
                        <tbody id="kardex-historias"></tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Tratamientos realizados</h2>
                    <span class="badge badge-info" id="kardex-badge-tratamientos">0</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tratamiento</th>
                                <th>Clínica</th>
                                <th>Pieza</th>
                                <th>Estado</th>
                                <th>Costo cobrado</th>
                            </tr>
                        </thead>
                        <tbody id="kardex-tratamientos"></tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Pagos</h2>
                    <span class="badge badge-info" id="kardex-badge-pagos">0</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Recibo</th>
                                <th>Descripción</th>
                                <th>Tipo de pago</th>
                                <th>Costo total</th>
                                <th>Cancelado</th>
                            </tr>
                        </thead>
                        <tbody id="kardex-pagos"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        'use strict';

        const $modal = document.getElementById('modalKardexDetalle');
        if (!$modal) return;

        const $titulo = document.getElementById('modalKardexTitulo');
        const $admisiones = document.getElementById('kardex-admisiones');
        const $historias = document.getElementById('kardex-historias');
        const $tratamientos = document.getElementById('kardex-tratamientos');
        const $pagos = document.getElementById('kardex-pagos');

        /* ---------------- Utilidades ---------------- */
        const escapar = (valor) => String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const dinero = (valor) => `${(parseFloat(valor) || 0).toFixed(2)} Bs`;

        const fecha = (valor) => {
            if (!valor) return '—';
            const texto = String(valor).substring(0, 10);
            const partes = texto.split('-');
            return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : texto;
        };

        const sinDatos = (columnas, mensaje) =>
            `<tr><td colspan="${columnas}"><div class="empty-state"><p>${mensaje}</p></div></td></tr>`;

        function pintarAdmisiones(lista) {
            if (!lista.length) {
                $admisiones.innerHTML = sinDatos(5, 'Sin admisiones registradas');
                return;
            }
            $admisiones.innerHTML = lista.map((a) => `
                <tr>
                    <td><code>${escapar(a.id)}</code></td>
                    <td>${escapar(a.periodo || '—')}</td>
                    <td>${escapar(a.estado_civil || '—')}</td>
                    <td>${escapar(a.ocupacion || '—')}</td>
                    <td>${fecha(a.created_at)}</td>
                </tr>`).join('');
        }

        function pintarHistorias(lista) {
            if (!lista.length) {
                $historias.innerHTML = sinDatos(5, 'Sin historias clínicas registradas');
                return;
            }
            $historias.innerHTML = lista.map((h) => `
                <tr>
                    <td><code>${escapar(h.id)}</code></td>
                    <td>${escapar(h.clinica || '—')}</td>
                    <td>${escapar(h.periodo || '—')}</td>
                    <td><span class="badge badge-muted">${escapar(h.estado || '—')}</span></td>
                    <td>${Number(h.autorizado) === 1
                        ? '<span class="badge badge-success">Sí</span>'
                        : '<span class="badge badge-warning">No</span>'}</td>
                </tr>`).join('');
        }

        function pintarTratamientos(lista) {
            if (!lista.length) {
                $tratamientos.innerHTML = sinDatos(6, 'Sin tratamientos realizados');
                return;
            }
            $tratamientos.innerHTML = lista.map((t) => `
                <tr>
                    <td><code>${escapar(t.id)}</code></td>
                    <td>${escapar(t.tratamiento || '—')}</td>
                    <td>${escapar(t.clinica || '—')}</td>
                    <td>${escapar(t.pieza_dental || '—')}</td>
                    <td><span class="badge badge-muted">${escapar(t.estado || '—')}</span></td>
                    <td>${dinero(t.costo_cobrado)}</td>
                </tr>`).join('');
        }

        function pintarPagos(lista) {
            if (!lista.length) {
                $pagos.innerHTML = sinDatos(5, 'Sin pagos registrados');
                return;
            }
            $pagos.innerHTML = lista.map((p) => `
                <tr>
                    <td><code>${escapar(p.recibo)}</code></td>
                    <td>${escapar(p.descripcion || '—')}</td>
                    <td>${escapar(p.tipo_pago || '—')}</td>
                    <td>${dinero(p.costo_total)}</td>
                    <td>${dinero(p.cancelado)}</td>
                </tr>`).join('');
        }

        /* ---------------- API pública ---------------- */
        window.KardexDetalle = {
            abrir(data) {
                const registro = data.registro || {};
                const nombre = [registro.nombre, registro.apellido_paterno, registro.apellido_materno]
                    .filter(Boolean).join(' ');

                if ($titulo) {
                    $titulo.textContent = nombre
                        ? `Kardex de ${nombre}${registro.ci ? ` (CI ${registro.ci})` : ''}`
                        : 'Detalle del paciente';
                }

                const totales = {
                    'kardex-total-admisiones': data.total_admisiones ?? 0,
                    'kardex-total-historias': data.total_historias ?? 0,
                    'kardex-total-tratamientos': data.total_tratamientos ?? 0,
                };
                Object.entries(totales).forEach(([id, valor]) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = valor;
                });

                const pagadoEl = document.getElementById('kardex-total-pagado');
                if (pagadoEl) pagadoEl.textContent = dinero(data.total_pagado);
                const saldoEl = document.getElementById('kardex-saldo-pendiente');
                if (saldoEl) saldoEl.textContent = dinero(data.saldo_pendiente);

                const admisiones = data.admisiones || [];
                const historias = data.historias || [];
                const tratamientos = data.tratamientos || [];
                const pagos = data.pagos || [];

                const badges = {
                    'kardex-badge-admisiones': admisiones.length,
                    'kardex-badge-historias': historias.length,
                    'kardex-badge-tratamientos': tratamientos.length,
                    'kardex-badge-pagos': pagos.length,
                };
                Object.entries(badges).forEach(([id, valor]) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = valor;
                });

                pintarAdmisiones(admisiones);
                pintarHistorias(historias);
                pintarTratamientos(tratamientos);
                pintarPagos(pagos);

                $modal.classList.add('open');
                document.body.classList.add('modal-open');
            },

            cerrar() {
                $modal.classList.remove('open');
                document.body.classList.remove('modal-open');
            },
        };

        /* ---------------- Cierre del modal ---------------- */
        $modal.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]') || e.target === $modal) {
                window.KardexDetalle.cerrar();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) {
                window.KardexDetalle.cerrar();
            }
        });
    })();
</script>
@endpush
