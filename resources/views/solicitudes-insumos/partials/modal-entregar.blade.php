<div class="modal-backdrop" id="modalEntregar">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <form id="formEntregar" novalidate>
            @csrf
            <input type="hidden" name="solicitud_id" id="formEntregarSolicitudId" value="">

            <div class="modal-header">
                <h3 id="modalEntregarTitulo">Entregar Insumos</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <p style="color:var(--muted); margin-bottom:14px;">
                    Confirma las cantidades a entregar. El stock se descontará automáticamente.
                </p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Stock actual</th>
                                <th>Solicitado</th>
                                <th>Entregar</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEntregar">
                            {{-- JS llena esto --}}
                        </tbody>
                    </table>
                </div>

                <div id="formEntregarError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formEntregarErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnConfirmarEntrega">
                    <span id="btnConfirmarEntregaText">Confirmar Entrega</span>
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
        const BASE = @json(url('solicitudes-insumos'));

        const $modal = document.getElementById('modalEntregar');
        const $form = document.getElementById('formEntregar');
        const $tbody = document.getElementById('tbodyEntregar');
        const $id = document.getElementById('formEntregarSolicitudId');
        const $btnGuard = document.getElementById('btnConfirmarEntrega');
        const $btnText = document.getElementById('btnConfirmarEntregaText');
        const $errorBox = document.getElementById('formEntregarError');
        const $errorTxt = document.getElementById('formEntregarErrorText');

        window.SolicitudEntregarModal = {
            abrir(data) {
                resetForm();

                const sol = data.registro || data;
                $id.value = sol.id;

                const detalles = data.detalles || [];

                if (detalles.length === 0) {
                    $tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Sin detalles</td></tr>';
                } else {
                    $tbody.innerHTML = detalles.map((d) => {
                        const pendiente = d.cantidad_solicitada - d.cantidad_entregada;
                        return `
                        <tr data-detalle-id="${d.id ?? ''}">
                            <td>
                                <strong>${d.insumo_nombre}</strong>
                                <br>
                                <small style="color:var(--muted);">${d.unidad || ''}</small>
                            </td>
                            <td>
                                <span class="badge ${d.stock_actual > 0 ? 'badge-info' : 'badge-danger'}">
                                    ${Number(d.stock_actual).toFixed(2)}
                                </span>
                            </td>
                            <td>
                                <strong>${Number(d.cantidad_solicitada).toFixed(2)}</strong>
                            </td>
                            <td>
                                <input type="number"
                                       name="detalles[${d.id}][cantidad_entregada]"
                                       class="form-control"
                                       min="0"
                                       max="${d.cantidad_solicitada}"
                                       step="0.01"
                                       value="${Number(pendiente).toFixed(2)}"
                                       data-detalle-id="${d.id}"
                                       data-stock="${d.stock_actual}"
                                       data-solicitada="${d.cantidad_solicitada}"
                                       style="max-width:120px;">
                                <input type="hidden" name="detalles[${d.id}][detalle_id]" value="${d.id}">
                            </td>
                        </tr>
                    `;
                    }).join('');
                }

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            if (!id) return;

            // Recolectar detalles
            const detalles = [];
            let errorValidacion = null;

            $tbody.querySelectorAll('input[name^="detalles"]').forEach((input) => {
                if (!input.name.includes('cantidad_entregada')) return;

                const cantidad = parseFloat(input.value) || 0;
                const stock = parseFloat(input.dataset.stock) || 0;
                const detalleId = parseInt(input.dataset.detalleId, 10);

                if (cantidad < 0) {
                    errorValidacion = 'La cantidad no puede ser negativa.';
                }
                if (cantidad > stock) {
                    errorValidacion = `No hay stock suficiente (disponible: ${stock}).`;
                }

                if (cantidad > 0) {
                    detalles.push({
                        detalle_id: detalleId,
                        cantidad_entregada: cantidad,
                    });
                }
            });

            if (errorValidacion) {
                $errorTxt.textContent = errorValidacion;
                $errorBox.style.display = 'flex';
                return;
            }

            if (detalles.length === 0) {
                $errorTxt.textContent = 'Debes entregar al menos un insumo.';
                $errorBox.style.display = 'flex';
                return;
            }

            setLoading(true);

            try {
                const res = await fetch(`${BASE}/${id}/entregar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        detalles
                    }),
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al entregar.');
                }

                closeModal();
                window.SolicitudesLista?.toast('success', data.message || 'Entregado correctamente.');
                window.dispatchEvent(new Event('solicitud:guardada'));
            } catch (err) {
                $errorTxt.textContent = err.message || 'Error inesperado.';
                $errorBox.style.display = 'flex';
            } finally {
                setLoading(false);
            }
        });

        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';
            $tbody.innerHTML = '';
            clearErrors();
        }

        function clearErrors() {
            $errorBox.style.display = 'none';
        }

        function setLoading(loading) {
            $btnGuard.disabled = loading;
            $btnText.textContent = loading ? 'Entregando...' : 'Confirmar Entrega';
        }

        $modal?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]')) closeModal();
            if (e.target === $modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) closeModal();
        });
    })();
</script>
@endpush