{{-- ============================================================
     MODAL DE INSUMO (crear / editar)
     ============================================================ --}}
<div class="modal-backdrop" id="modalInsumo">
    <div class="modal modal-lg" role="dialog" aria-modal="true"
        aria-labelledby="modalInsumoTitulo">
        <form id="formInsumo" novalidate
            data-store="{{ route('insumos.store') }}"
            data-update="{{ route('insumos.update', '__INSUMO__') }}"
            data-tratamientos="{{ route('insumos.tratamientos-por-clinica', '__CLINICA__') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="insumo_id" id="formInsumoId" value="">

            <div class="modal-header">
                <h3 id="modalInsumoTitulo">Nuevo Insumo</h3>
                <button type="button" class="modal-close"
                    data-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
                    {{-- Clínica --}}
                    <div class="form-group">
                        <label for="clinica_id">
                            Clínica <span class="req">*</span>
                        </label>
                        <select id="clinica_id" name="clinica_id" class="form-control" required>
                            <option value="">Seleccione una clínica...</option>
                            @foreach ($clinicas as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error-for="clinica_id"></span>
                    </div>

                    {{-- Tratamiento de la clínica --}}
                    <div class="form-group">
                        <label for="insumo-tratamiento">
                            Insumo para el tratamiento <span class="req">*</span>
                        </label>
                        <select id="insumo-tratamiento" name="tratamiento_id"
                            class="form-control" required disabled>
                            <option value="">Primero seleccione una clínica</option>
                        </select>
                        <span class="form-error" data-error-for="tratamiento_id"></span>
                        <small id="insumo-tratamientos-actuales" style="color:var(--muted);"></small>
                    </div>

                    {{-- Nombre --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="nombre">
                            Nombre del insumo <span class="req">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre"
                            class="form-control" required maxlength="100"
                            placeholder="Ej: Resina compuesta A2">
                        <span class="form-error" data-error-for="nombre"></span>
                    </div>

                    {{-- Cantidad numérica --}}
                    <div class="form-group">
                        <label for="cantidad_numerica">
                            Cantidad <span class="req">*</span>
                        </label>
                        <input type="number" id="cantidad_numerica" name="cantidad_numerica"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="cantidad_numerica"></span>
                    </div>

                    {{-- Unidad --}}
                    <div class="form-group">
                        <label for="cantidad">Medida Volumétrica</label>
                        <input type="text" id="cantidad" name="cantidad"
                            class="form-control" maxlength="100"
                            placeholder="Ej: ml, cm³, gramos, unidades">
                        <span class="form-error" data-error-for="cantidad"></span>
                    </div>

                    {{-- Stock mínimo --}}
                    <div class="form-group">
                        <label for="stock_minimo">
                            Stock mínimo <span class="req">*</span>
                        </label>
                        <input type="number" id="stock_minimo" name="stock_minimo"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="stock_minimo"></span>
                    </div>

                    {{-- Costo unitario --}}
                    <div class="form-group">
                        <label for="costo_unitario">
                            Costo unitario (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" id="costo_unitario" name="costo_unitario"
                            class="form-control" required min="0" step="0.01"
                            placeholder="0.00">
                        <span class="form-error" data-error-for="costo_unitario"></span>
                    </div>
                </div>

                {{-- Alerta general de errores --}}
                <div id="formInsumoError" class="alert alert-danger"
                    style="display:none; margin-top:18px;">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formInsumoErrorText">Corrige los errores del formulario.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarInsumo">
                    <span id="btnGuardarInsumoText">Guardar Insumo</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/insumo-registro.js') }}" defer></script>
@endpush