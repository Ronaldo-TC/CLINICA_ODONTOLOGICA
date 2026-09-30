<div class="modal-backdrop" id="modalInsumo" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalInsumoTitle"
        style="max-width: 640px;">

        <div class="modal-header">
            <h3 id="modalInsumoTitle">Nuevo insumo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalInsumo()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formInsumo" method="POST" action="{{ route('admin.insumos.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formInsumoMethod" value="POST">
            <input type="hidden" name="insumo_id" id="formInsumoId" value="">

            <div class="modal-body">
                <div class="form-grid">

                    {{-- Nombre --}}
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_ins_nombre">
                            Nombre <span class="req">*</span>
                        </label>
                        <input type="text" name="nombre" id="m_ins_nombre"
                            class="form-control" maxlength="100" required
                            placeholder="Ej: Guantes de látex, Anestesia…">
                        <span class="form-error" data-error="nombre"></span>
                    </div>

                    {{-- Clínica --}}
                    <div class="form-group">
                        <label for="m_ins_clinica_id">
                            Clínica <span class="req">*</span>
                        </label>
                        <select name="clinica_id" id="m_ins_clinica_id" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach ($clinicas as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="clinica_id"></span>
                    </div>

                    {{-- Tipo de insumo (select dinámico) --}}
                    <div class="form-group">
                        <label for="m_ins_insumos_tratamiento_id">
                            Tipo de insumo <span class="req">*</span>
                        </label>
                        <select name="insumo_tratamiento_id" id="m_ins_insumo_tratamiento_id"
                            class="form-control" required>
                            <option value="">— Primero selecciona una clínica —</option>
                        </select>
                        <span class="form-error" data-error="insumo_tratamiento_id"></span>
                    </div>

                    {{-- Cantidad --}}
                    <div class="form-group">
                        <label for="m_ins_cantidad_numerica">
                            Cantidad <span class="req">*</span>
                        </label>
                        <input type="number" name="cantidad_numerica" id="m_ins_cantidad_numerica"
                            class="form-control" step="0.01" min="0" required
                            placeholder="0">
                        <span class="form-error" data-error="cantidad_numerica"></span>
                    </div>

                    {{-- Unidad --}}
                    <div class="form-group">
                        <label for="m_ins_cantidad">Unidad / Presentación</label>
                        <input type="text" name="cantidad" id="m_ins_cantidad"
                            class="form-control" maxlength="100"
                            placeholder="Ej: caja, unidad, par…">
                        <span class="form-error" data-error="cantidad"></span>
                    </div>

                    {{-- Stock mínimo --}}
                    <div class="form-group">
                        <label for="m_ins_stock_minimo">
                            Stock mínimo <span class="req">*</span>
                        </label>
                        <input type="number" name="stock_minimo" id="m_ins_stock_minimo"
                            class="form-control" step="0.01" min="0" required
                            placeholder="0">
                        <span class="form-error" data-error="stock_minimo"></span>
                    </div>

                    {{-- Costo unitario --}}
                    <div class="form-group">
                        <label for="m_ins_costo_unitario">
                            Costo unitario (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" name="costo_unitario" id="m_ins_costo_unitario"
                            class="form-control" step="0.01" min="0" required
                            placeholder="0.00">
                        <span class="form-error" data-error="costo_unitario"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalInsumo()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarInsumo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarInsumoText">Crear insumo</span>
                </button>
            </div>
        </form>
    </div>
</div>