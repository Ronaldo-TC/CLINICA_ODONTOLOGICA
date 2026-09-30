<div class="modal-backdrop" id="modalClinica" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalClinicaTitle"
        style="max-width: 560px;">

        <div class="modal-header">
            <h3 id="modalClinicaTitle">Nueva clínica</h3>
            <button type="button" class="modal-close" onclick="cerrarModalClinica()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formClinica" method="POST" action="{{ route('admin.clinicas.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formClinicaMethod" value="POST">
            <input type="hidden" name="clinica_id" id="formClinicaId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_clinica_modulo">
                            Módulo <span class="req">*</span>
                        </label>
                        <input type="text" name="modulo" id="m_clinica_modulo"
                            class="form-control" maxlength="200" required
                            placeholder="Ej: Clínica Integral, Clínica de Especialidades…">
                        <span class="form-error" data-error="modulo"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_clinica_especialidad">
                            Especialidad <span class="req">*</span>
                        </label>
                        <input type="text" name="especialidad" id="m_clinica_especialidad"
                            class="form-control" maxlength="200" required
                            placeholder="Ej: Odontología General, Rehabilitación Oral…">
                        <span class="form-error" data-error="especialidad"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_clinica_curso">Curso</label>
                        <input type="number" name="curso" id="m_clinica_curso"
                            class="form-control" min="-1" max="10"
                            placeholder="-1 (no asignado)">
                        <span class="form-error" data-error="curso"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalClinica()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarClinica">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarClinicaText">Crear clínica</span>
                </button>
            </div>
        </form>
    </div>
</div>