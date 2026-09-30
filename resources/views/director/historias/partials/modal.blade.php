@php
$clinicasModal = \App\Models\Clinica::orderBy('especialidad')->get();
$periodosModal = \App\Models\Periodo::orderByDesc('gestion')->orderByDesc('semestre')->get();
$docentesModal = \App\Models\Usuario::whereIn('tipo', ['docente','jefatura'])
->orderBy('nombre_completo')->get();
$estudiantesModal = \App\Models\Usuario::whereIn('tipo', ['estudiante','interno'])
->orderBy('nombre_completo')->get();
$tratamientosModal = \App\Models\Tratamiento::orderBy('nombre')->get();
@endphp

<script>
    window.__TRATAMIENTOS_OPTIONS__ = `{!! $tratamientosModal->map(function ($t) {
        return '<option value="' . $t->id . '">' . e($t->nombre) . '</option>';
    })->implode('') !!}`;
</script>

<div class="modal-backdrop" id="modalHistoria" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalHistoriaTitle">

        <div class="modal-header">
            <h3 id="modalHistoriaTitle">Nueva historia clinica</h3>
            <button type="button" class="modal-close" onclick="cerrarModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formHistoria" method="POST" action="{{ route('admin.historias.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formHistoriaMethod" value="POST">
            <input type="hidden" name="id" id="formHistoriaId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="m_admision_id">ID Admision <span class="req">*</span></label>
                        <input type="number" name="admision_id" id="m_admision_id" class="form-control" required>
                        <span class="form-error" data-error="admision_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_paciente_id">ID Paciente <span class="req">*</span></label>
                        <input type="number" name="paciente_id" id="m_paciente_id" class="form-control" required>
                        <span class="form-error" data-error="paciente_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_clinica_id">Especialidad <span class="req">*</span></label>
                        <select name="clinica_id" id="m_clinica_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach ($clinicasModal as $c)
                            <option value="{{ $c->id }}">{{ $c->especialidad }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="clinica_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_periodo_id">Periodo <span class="req">*</span></label>
                        <select name="periodo_id" id="m_periodo_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach ($periodosModal as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="periodo_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_estudiante_id">Estudiante <span class="req">*</span></label>
                        <select name="estudiante_id" id="m_estudiante_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach ($estudiantesModal as $e)
                            <option value="{{ $e->id }}">{{ $e->nombre_completo }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="estudiante_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_docente_id">Docente <span class="req">*</span></label>
                        <select name="docente_id" id="m_docente_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach ($docentesModal as $d)
                            <option value="{{ $d->id }}">{{ $d->nombre_completo }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="docente_id"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_fecha_inicio">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" id="m_fecha_inicio" class="form-control">
                        <span class="form-error" data-error="fecha_inicio"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_fecha_fin">Fecha de fin</label>
                        <input type="date" name="fecha_fin" id="m_fecha_fin" class="form-control">
                        <span class="form-error" data-error="fecha_fin"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_revision_docente">Revision del docente</label>
                        <textarea name="revision_docente" id="m_revision_docente" class="form-control"
                            rows="3" maxlength="2000" placeholder="Observaciones u observaciones del docente"></textarea>
                        <span class="form-error" data-error="revision_docente"></span>
                    </div>
                </div>

                <hr style="margin:20px 0; border:none; border-top:1px solid var(--border);">

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="font:600 14px -apple-system, sans-serif;">Tratamientos propuestos</h4>
                    <button type="button" class="btn btn-secondary btn-sm"
                        onclick="agregarTratamientoPropuesto()">
                        Agregar tratamiento
                    </button>
                </div>

                <div class="table-wrap" style="max-height:280px; overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tratamiento</th>
                                <th>Pieza</th>
                                <th>Costo (Bs)</th>
                                <th>Observacion</th>
                                <th class="text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyTratamientosPropuestos"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarHistoria">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarHistoriaText">Crear historia</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal del historial completo del paciente --}}
<div class="modal-backdrop" id="modalHistorialPaciente" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalHistorialPacienteTitle">
        <div class="modal-header">
            <h3 id="modalHistorialPacienteTitle">Historial del paciente</h3>
            <button type="button" class="modal-close" onclick="cerrarModalHistorial()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body" id="contenidoHistorialPaciente">
            <p class="text-muted">Cargando historial</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalHistorial()">Cerrar</button>
        </div>
    </div>
</div>