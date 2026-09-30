<div class="modal-backdrop" id="modalPaciente" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalPacienteTitle"
        style="max-width: 640px;">

        <div class="modal-header">
            <h3 id="modalPacienteTitle">Nuevo paciente</h3>
            <button type="button" class="modal-close" onclick="cerrarModalPaciente()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formPaciente" method="POST" action="{{ route('admin.pacientes.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formPacienteMethod" value="POST">
            <input type="hidden" name="paciente_id" id="formPacienteId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="m_pac_ci">
                            CI <span class="req">*</span>
                        </label>
                        <input type="text" name="ci" id="m_pac_ci"
                            class="form-control" maxlength="50" required>
                        <span class="form-error" data-error="ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_pac_genero">
                            Género <span class="req">*</span>
                        </label>
                        <select name="genero" id="m_pac_genero" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <span class="form-error" data-error="genero"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_pac_nombre">
                            Nombre <span class="req">*</span>
                        </label>
                        <input type="text" name="nombre" id="m_pac_nombre"
                            class="form-control" maxlength="50" required>
                        <span class="form-error" data-error="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_pac_apellido_paterno">
                            Apellido paterno <span class="req">*</span>
                        </label>
                        <input type="text" name="apellido_paterno" id="m_pac_apellido_paterno"
                            class="form-control" maxlength="50" required>
                        <span class="form-error" data-error="apellido_paterno"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_pac_apellido_materno">
                            Apellido materno <span class="req">*</span>
                        </label>
                        <input type="text" name="apellido_materno" id="m_pac_apellido_materno"
                            class="form-control" maxlength="50" required>
                        <span class="form-error" data-error="apellido_materno"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_pac_fecha_nacimiento">
                            Fecha de nacimiento <span class="req">*</span>
                        </label>
                        <input type="date" name="fecha_nacimiento" id="m_pac_fecha_nacimiento"
                            class="form-control" max="{{ date('Y-m-d') }}" required>
                        <span class="form-error" data-error="fecha_nacimiento"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalPaciente()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarPaciente">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarPacienteText">Crear paciente</span>
                </button>
            </div>
        </form>
    </div>
</div>