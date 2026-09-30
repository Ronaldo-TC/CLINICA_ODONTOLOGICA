<div class="modal-backdrop" id="modalDetalleCaja" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalDetalleCajaTitle"
        style="max-width: 560px;">

        <div class="modal-header">
            <h3 id="modalDetalleCajaTitle">Detalle del movimiento</h3>
            <button type="button" class="modal-close" onclick="cerrarDetalleCaja()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <div class="modal-body" id="detalleCajaBody">
            {{-- Se llena dinámicamente vía JS --}}
            <div class="empty-state" style="padding: 40px 20px;">
                <p>Cargando…</p>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarDetalleCaja()">Cerrar</button>
        </div>
    </div>
</div>