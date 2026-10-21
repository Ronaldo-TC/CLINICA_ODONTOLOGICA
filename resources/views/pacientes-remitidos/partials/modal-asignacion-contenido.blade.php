<form method="POST"
    action="{{ route('pacientes-remitidos.asignar', $registro->id) }}"
    id="formAsignacionRemision">
    @csrf

    <div class="modal-body">
        <div class="form-grid" style="margin-bottom:18px;">
            <div class="form-group">
                <label>Paciente</label>
                <strong>{{ $registro->paciente?->nombre_completo ?? '—' }}</strong>
                <small class="text-muted">CI: {{ $registro->paciente?->ci ?? '—' }}</small>
            </div>

            <div class="form-group">
                <label>Especialidad destino</label>
                <span class="badge badge-info">{{ $registro->clinica?->especialidad ?? '—' }}</span>
            </div>
        </div>

        <div class="alert alert-info" style="margin-bottom:18px;">
            Al confirmar, la historia clínica se creará y autorizará automáticamente.
        </div>

        <div class="alert alert-danger" data-asignacion-error role="alert" hidden></div>

        <div class="form-grid">
            <div class="form-group">
                <label for="modal-buscar-estudiante">
                    Buscar estudiante por CI o nombre <span class="req">*</span>
                </label>

                <div class="autocomplete-wrap" data-estudiante-buscador>
                    <input type="hidden" name="estudiante_id" data-estudiante-id>
                    <input type="search"
                        id="modal-buscar-estudiante"
                        class="form-control autocomplete-input"
                        placeholder="Escriba el CI o el nombre..."
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="modal-resultados-estudiantes"
                        autofocus
                        data-estudiante-busqueda>

                    <div id="modal-resultados-estudiantes"
                        class="autocomplete-dropdown"
                        role="listbox"
                        aria-label="Resultados de estudiantes"
                        data-estudiante-resultados
                        hidden>
                        <div class="autocomplete-item autocomplete-empty" data-estudiante-vacio>
                            Escriba el CI o nombre del estudiante.
                        </div>

                    @foreach ($personas as $persona)
                    @if ($persona->hasAnyRole(['estudiante', 'interno']))
                        <button type="button"
                            class="autocomplete-item"
                            style="width:100%;border:0;background:transparent;color:var(--ink);font:inherit;text-align:left;"
                            role="option"
                            data-estudiante-opcion
                            data-id="{{ $persona->id }}"
                            data-busqueda="{{ $persona->ci }} {{ $persona->nombre_completo }}"
                            data-etiqueta="{{ $persona->nombre_completo }} · CI {{ $persona->ci }}"
                            hidden>
                            <span class="autocomplete-avatar">{{ mb_substr($persona->nombre_completo, 0, 1) }}</span>
                            <span class="autocomplete-info">
                                <span class="autocomplete-name">{{ $persona->nombre_completo }}</span>
                                <span class="autocomplete-meta">CI {{ $persona->ci }}</span>
                            </span>
                        </button>
                    @endif
                    @endforeach
                    </div>
                </div>

                <small class="text-muted">Busque y seleccione personal habilitado de esta especialidad.</small>
                <span class="form-error" data-error-for="estudiante_id"></span>
            </div>

            <div class="form-group">
                @if ($docenteActual)
                <label>Docente responsable</label>
                <strong>{{ $docenteActual->nombre_completo }}</strong>
                <small class="text-muted">La atención quedará a su cargo.</small>
                @else
                <label for="modal-asignacion-docente">
                    Docente responsable <span class="req">*</span>
                </label>
                <select id="modal-asignacion-docente" name="docente_id" class="form-control" required>
                    <option value="">Seleccione un docente...</option>
                    @foreach ($personas as $persona)
                    @if ($persona->hasAnyRole(['docente', 'jefe-catedra']))
                    <option value="{{ $persona->id }}">{{ $persona->nombre_completo }}</option>
                    @endif
                    @endforeach
                </select>
                @endif
                <span class="form-error" data-error-for="docente_id"></span>
            </div>

            <div class="form-group">
                @if ($registro->periodo_id)
                <label>Periodo de la remisión</label>
                <strong>{{ $registro->periodo?->nombre_completo ?? '—' }}</strong>
                @else
                <label for="modal-asignacion-periodo">
                    Periodo <span class="req">*</span>
                </label>
                <select id="modal-asignacion-periodo" name="periodo_id" class="form-control" required>
                    <option value="">Seleccione un periodo...</option>
                    @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}">{{ $periodo->nombre_completo }}</option>
                    @endforeach
                </select>
                @endif
                <span class="form-error" data-error-for="periodo_id"></span>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-asignacion-close>Cancelar</button>
        <button type="submit" class="btn btn-primary" data-asignacion-submit>
            Asignar, crear y autorizar historia
        </button>
    </div>
</form>
