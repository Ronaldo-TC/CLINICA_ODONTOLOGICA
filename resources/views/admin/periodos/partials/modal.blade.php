<div class="modal-backdrop" id="modalPeriodo" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalPeriodoTitle"
        style="max-width: 640px;">

        <div class="modal-header">
            <h3 id="modalPeriodoTitle">Nuevo periodo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalPeriodo()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formPeriodo" method="POST" action="{{ route('admin.periodos.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formPeriodoMethod" value="POST">
            <input type="hidden" name="periodo_id" id="formPeriodoId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_per_nombre">
                            Nombre <span class="req">*</span>
                        </label>
                        <input type="text" name="nombre" id="m_per_nombre"
                            class="form-control" maxlength="100" required
                            placeholder="Ej: Gestión 2026 - Semestre 1">
                        <span class="form-error" data-error="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_per_gestion">
                            Gestión <span class="req">*</span>
                        </label>
                        <input type="number" name="gestion" id="m_per_gestion"
                            class="form-control" min="2000" max="2100" required
                            placeholder="{{ date('Y') }}">
                        <span class="form-error" data-error="gestion"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_per_semestre">
                            Semestre <span class="req">*</span>
                        </label>
                        <select name="semestre" id="m_per_semestre" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                        <span class="form-error" data-error="semestre"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_per_fecha_inicio">
                            Fecha de inicio <span class="req">*</span>
                        </label>
                        <input type="date" name="fecha_inicio" id="m_per_fecha_inicio"
                            class="form-control" required>
                        <span class="form-error" data-error="fecha_inicio"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_per_fecha_fin">
                            Fecha de fin <span class="req">*</span>
                        </label>
                        <input type="date" name="fecha_fin" id="m_per_fecha_fin"
                            class="form-control" required>
                        <span class="form-error" data-error="fecha_fin"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="m_per_habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Periodo habilitado</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalPeriodo()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarPeriodo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarPeriodoText">Crear periodo</span>
                </button>
            </div>
        </form>
    </div>
</div>