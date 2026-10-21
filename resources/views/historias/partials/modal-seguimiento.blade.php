<div class="modal-backdrop" id="modalSeguimientoHistoria" data-seguimiento-base="{{ url('/historias') }}">
    <div class="modal modal-lg seguimiento-modal" role="dialog" aria-modal="true" aria-labelledby="modalSeguimientoTitulo" tabindex="-1">
        <div class="modal-header seguimiento-modal__header">
            <div>
                <span class="seguimiento-modal__eyebrow">Historia cl&iacute;nica</span>
                <h3 id="modalSeguimientoTitulo">Seguimiento de la historia cl&iacute;nica</h3>
            </div>
            <button type="button" class="modal-close" data-seguimiento-close aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body seguimiento-modal__body" id="modalSeguimientoBody" aria-live="polite">
            <p class="notif-cargando">Selecciona una opci&oacute;n de seguimiento.</p>
        </div>
        <div class="modal-footer">
            <a class="btn btn-primary" id="modalSeguimientoCompleto" href="#" hidden>Abrir ficha m&eacute;dica completa</a>
            <button type="button" class="btn btn-secondary" data-seguimiento-close>Cerrar</button>
        </div>
    </div>
</div>

@pushOnce('styles', 'historias-seguimiento')
<style>
    #modalSeguimientoHistoria .seguimiento-modal {
        max-width: min(1180px, calc(100vw - 40px));
    }

    #modalSeguimientoHistoria .seguimiento-modal__header {
        padding-block: 16px;
    }

    #modalSeguimientoHistoria .seguimiento-modal__header > div {
        min-width: 0;
    }

    #modalSeguimientoHistoria .seguimiento-modal__eyebrow {
        display: block;
        margin-bottom: 3px;
        color: var(--muted);
        font: 700 10px -apple-system, sans-serif;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    #modalSeguimientoHistoria .seguimiento-modal__body {
        background: var(--surface-soft);
    }

    #modalSeguimientoHistoria .seguimiento-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    #modalSeguimientoHistoria .seguimiento-summary__item {
        min-width: 0;
        padding: 12px 14px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
    }

    #modalSeguimientoHistoria .seguimiento-summary__label {
        display: block;
        margin-bottom: 4px;
        color: var(--muted);
        font: 700 10px -apple-system, sans-serif;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    #modalSeguimientoHistoria .seguimiento-summary__value {
        display: block;
        overflow: hidden;
        color: var(--ink);
        font-size: 13px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #modalSeguimientoHistoria .seguimiento-table-wrap {
        overflow: auto;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--surface);
    }

    #modalSeguimientoHistoria .seguimiento-table {
        min-width: 820px;
        margin: 0;
    }

    #modalSeguimientoHistoria .seguimiento-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    #modalSeguimientoHistoria .seguimiento-table tbody td {
        vertical-align: top;
    }

    #modalSeguimientoHistoria .seguimiento-table__primary {
        display: block;
        color: var(--ink);
        font-weight: 700;
    }

    #modalSeguimientoHistoria .seguimiento-table__secondary {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }

    #modalSeguimientoHistoria .seguimiento-actions {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        flex-wrap: wrap;
    }

    #modalSeguimientoHistoria .seguimiento-actions form {
        margin: 0;
    }

    #modalSeguimientoHistoria .seguimiento-detail-row > td {
        padding: 0;
        background: color-mix(in srgb, var(--surface-soft) 65%, var(--surface));
    }

    #modalSeguimientoHistoria .seguimiento-details {
        border: 0;
    }

    #modalSeguimientoHistoria .seguimiento-details > summary,
    #modalSeguimientoHistoria .seguimiento-compose > summary {
        cursor: pointer;
        color: var(--teal-dark);
        font-size: 12px;
        font-weight: 700;
        list-style-position: inside;
    }

    #modalSeguimientoHistoria .seguimiento-details > summary {
        padding: 11px 16px;
    }

    #modalSeguimientoHistoria .seguimiento-details[open] > summary {
        border-bottom: 1px solid var(--line);
    }

    #modalSeguimientoHistoria .seguimiento-details__body {
        display: grid;
        gap: 14px;
        padding: 16px;
    }

    #modalSeguimientoHistoria .seguimiento-compose,
    #modalSeguimientoHistoria .seguimiento-form-panel {
        margin-bottom: 16px;
        padding: 14px 16px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--surface);
    }

    #modalSeguimientoHistoria .seguimiento-compose[open] > summary {
        margin-bottom: 14px;
    }

    #modalSeguimientoHistoria .seguimiento-form-panel {
        margin: 0;
    }

    #modalSeguimientoHistoria .seguimiento-form-panel h4 {
        margin: 0 0 10px;
        color: var(--ink);
        font-size: 13px;
    }

    #modalSeguimientoHistoria .seguimiento-form-panel .form-grid {
        margin: 0;
    }

    #modalSeguimientoHistoria .seguimiento-subtable {
        overflow-x: auto;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
    }

    #modalSeguimientoHistoria .seguimiento-subtable table {
        min-width: 620px;
    }

    #modalSeguimientoHistoria .seguimiento-file {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 220px;
    }

    #modalSeguimientoHistoria .seguimiento-file__preview {
        display: grid;
        width: 42px;
        height: 42px;
        overflow: hidden;
        place-items: center;
        flex: 0 0 42px;
        border: 1px solid var(--line);
        border-radius: 9px;
        background: var(--surface-soft);
        color: var(--muted);
        font-size: 10px;
        font-weight: 800;
    }

    #modalSeguimientoHistoria .seguimiento-file__preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    #modalSeguimientoHistoria .seguimiento-empty-cell {
        padding: 36px 20px;
        text-align: center;
        color: var(--muted);
    }

    @media (max-width: 720px) {
        #modalSeguimientoHistoria .seguimiento-modal {
            max-width: 100%;
        }

        #modalSeguimientoHistoria .seguimiento-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #modalSeguimientoHistoria .seguimiento-table-wrap {
            margin-inline: 0;
            padding: 0;
        }

        #modalSeguimientoHistoria .seguimiento-details__body {
            padding: 12px;
        }
    }
</style>
@endPushOnce

@push('scripts')
<script src="{{ asset('js/historias-seguimiento.js') }}" defer></script>
@endpush
