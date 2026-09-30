<div class="modal-backdrop" id="modalCaja" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalCajaTitle"
        style="max-width: 720px;">

        <div class="modal-header">
            <h3 id="modalCajaTitle">Nuevo registro de caja</h3>
            <button type="button" class="modal-close" onclick="cerrarModalCaja()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formCaja" method="POST" action="{{ route('admin.cajas.guardar') }}">
            @csrf

            {{-- Campos ocultos que se llenan al seleccionar paciente --}}
            <input type="hidden" name="paciente_id" id="m_caja_paciente_id" value="">
            <input type="hidden" name="ci" id="m_caja_ci" value="">
            <input type="hidden" name="paciente_nombre_completo" id="m_caja_paciente_nombre_completo" value="">
            <input type="hidden" name="usuario_id" id="m_caja_usuario_id" value="{{ session('user_id') }}">

            <div class="modal-body">
                <div class="form-grid">

                    {{-- ==================== Nº RECIBO ==================== --}}
                    <div class="form-group">
                        <label for="m_caja_recibo">
                            Nº Recibo <span class="req">*</span>
                        </label>
                        <input type="number" name="recibo" id="m_caja_recibo"
                            class="form-control" min="1" required>
                        <span class="form-error" data-error="recibo"></span>
                    </div>

                    {{-- ==================== TIPO DE PAGO ==================== --}}
                    <div class="form-group">
                        <label for="m_caja_tipo_pago">
                            Tipo de pago <span class="req">*</span>
                        </label>
                        <select name="tipo_pago" id="m_caja_tipo_pago" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="QR">QR</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                        <span class="form-error" data-error="tipo_pago"></span>
                    </div>

                    {{-- ==================== BUSCADOR DE PACIENTE ==================== --}}
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_buscar_paciente">
                            Paciente <span class="req">*</span>
                        </label>

                        <div class="autocomplete-wrap" id="pacienteAutocompleteWrap">
                            <input type="text"
                                id="m_buscar_paciente"
                                class="form-control autocomplete-input"
                                placeholder="Buscar por CI o nombre… (mínimo 2 caracteres)"
                                autocomplete="off">

                            <button type="button"
                                class="autocomplete-clear"
                                id="btnLimpiarPaciente"
                                title="Quitar selección">
                                <svg viewBox="0 0 24 24">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>

                            <span class="autocomplete-loading"></span>

                            <div class="autocomplete-dropdown" id="pacienteDropdown"></div>
                        </div>

                        <div id="pacienteSeleccionadoContainer"></div>

                        <span class="form-error" data-error="paciente_nombre_completo"></span>
                    </div>

                    {{-- ==================== TRATAMIENTO ==================== --}}
                    <div class="form-group">
                        <label for="m_caja_tratamiento_id">
                            Tratamiento <span class="req">*</span>
                        </label>
                        <select name="tratamiento_id" id="m_caja_tratamiento_id" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach ($tratamientos as $tr)
                            <option value="{{ $tr->id }}" data-costo="{{ $tr->costo }}">
                                {{ $tr->nombre }} — Bs {{ number_format($tr->costo, 2) }}
                            </option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="tratamiento_id"></span>
                    </div>

                    {{-- ==================== CLÍNICA ==================== --}}
                    <div class="form-group">
                        <label for="m_caja_clinica_id">
                            Clínica <span class="req">*</span>
                        </label>
                        <select name="clinica_id" id="m_caja_clinica_id" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach ($clinicas as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="clinica_id"></span>
                    </div>

                    {{-- ==================== MONTOS ==================== --}}
                    <div class="form-group">
                        <label for="m_caja_costo_total">
                            Costo total (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" name="costo_total" id="m_caja_costo_total"
                            class="form-control" step="0.01" min="0" required value="0">
                        <span class="form-error" data-error="costo_total"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_caja_cancelado">
                            Cancelado (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" name="cancelado" id="m_caja_cancelado"
                            class="form-control" step="0.01" min="0" required value="0">
                        <span class="form-error" data-error="cancelado"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_caja_monto">
                            Monto (Bs) <span class="req">*</span>
                        </label>
                        <input type="number" name="monto" id="m_caja_monto"
                            class="form-control" min="0" required value="0">
                        <span class="form-error" data-error="monto"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_caja_grado">Grado / Especialidad</label>
                        <input type="text" name="grado" id="m_caja_grado"
                            class="form-control" maxlength="100">
                        <span class="form-error" data-error="grado"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_caja_descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="m_caja_descripcion"
                            class="form-control" maxlength="100"
                            placeholder="Observaciones (opcional)">
                        <span class="form-error" data-error="descripcion"></span>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCaja()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarCaja">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span>Crear registro</span>
                </button>
            </div>
        </form>
    </div>
</div>