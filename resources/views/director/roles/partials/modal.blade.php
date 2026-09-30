<div class="modal-backdrop" id="modalRol" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalRolTitle"
        style="max-width: 520px;">

        <div class="modal-header">
            <h3 id="modalRolTitle">Nuevo rol</h3>
            <button type="button" class="modal-close" onclick="cerrarModalRol()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formRol" method="POST" action="{{ route('admin.roles.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formRolMethod" value="POST">
            <input type="hidden" name="rol_id" id="formRolId" value="">

            <div class="modal-body">
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label for="m_rol_nombre">
                            Nombre del rol <span class="req">*</span>
                        </label>
                        <input type="text" name="nombre" id="m_rol_nombre" class="form-control"
                            maxlength="50" required placeholder="Ej: docente, estudiante, cajero…">
                        <span class="form-error" data-error="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_rol_descripcion">Descripción</label>
                        <textarea name="descripcion" id="m_rol_descripcion" class="form-control"
                            maxlength="200" placeholder="Descripción breve del rol (opcional)"></textarea>
                        <span class="form-error" data-error="descripcion"></span>
                    </div>

                    <div class="form-group">
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="m_rol_habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Rol habilitado</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalRol()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarRol">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarRolText">Crear rol</span>
                </button>
            </div>
        </form>
    </div>
</div>