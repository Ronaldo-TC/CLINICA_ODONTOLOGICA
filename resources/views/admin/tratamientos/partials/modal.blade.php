<div class="modal-backdrop" id="modalTratamiento" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTratamientoTitle"
        style="max-width: 640px;">

        <div class="modal-header">
            <h3 id="modalTratamientoTitle">Nuevo tratamiento</h3>
            <button type="button" class="modal-close" onclick="cerrarModalTratamiento()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formTratamiento" method="POST" action="{{ route('admin.tratamientos.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formTratamientoMethod" value="POST">
            <input type="hidden" name="tratamiento_id" id="formTratamientoId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_tra_nombre">
                            Nombre <span class="req">*</span>
                        </label>
                        <input type="text" name="nombre" id="m_tra_nombre"
                            class="form-control" maxlength="100" required
                            placeholder="Ej: Limpieza dental, Endodoncia unirradicular…">
                        <span class="form-error" data-error="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_tra_clinica_id">
                            Clínica <span class="req">*</span>
                        </label>
                        <select name="clinica_id" id="m_tra_clinica_id" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach ($clinicas as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="clinica_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_tra_costo">
                            Costo (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" name="costo" id="m_tra_costo"
                            class="form-control" step="0.01" min="0" required
                            placeholder="0.00">
                        <span class="form-error" data-error="costo"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_tra_porcentaje_pg">Porcentaje PG</label>
                        <input type="text" name="porcentaje_pg" id="m_tra_porcentaje_pg"
                            class="form-control" maxlength="100"
                            placeholder="Ej: 50, 30-70…">
                        <span class="form-error" data-error="porcentaje_pg"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_tra_descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="m_tra_descripcion"
                            class="form-control" maxlength="100"
                            placeholder="Descripción breve (opcional)">
                        <span class="form-error" data-error="descripcion"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalTratamiento()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarTratamiento">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarTratamientoText">Crear tratamiento</span>
                </button>
            </div>
        </form>
    </div>
</div>