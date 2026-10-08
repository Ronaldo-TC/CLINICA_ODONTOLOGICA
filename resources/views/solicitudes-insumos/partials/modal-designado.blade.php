<div class="modal-backdrop" id="modalSolicitudDesignado">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="designado-titulo">
        <form id="formSolicitudDesignado" data-catalogo="{{ route('solicitudes-insumos.catalogo-designado') }}" data-store="{{ route('solicitudes-insumos.store') }}">
            @csrf
            <div class="modal-header"><h3 id="designado-titulo">Solicitar insumos del tratamiento</h3><button type="button" class="modal-close" data-designado-close aria-label="Cerrar">×</button></div>
            <div class="modal-body">
                <p id="designado-paciente"></p>
                <div class="form-grid">
                    @isset($pacientesDesignados)
                        <div id="designado-elegir-paciente" hidden>
                            <div class="form-group">
                                <label for="designado-paciente-select">Paciente designado <span class="req">*</span></label>
                                <select id="designado-paciente-select" class="form-control">
                                    <option value="">Seleccione un paciente</option>
                                    @foreach($pacientesDesignados as $asignacion)
                                        <option value="{{ $asignacion->id }}">{{ $asignacion->paciente->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endisset
                    <div class="form-group"><label for="designado-clinica">Clínica asignada <span class="req">*</span></label><select id="designado-clinica" class="form-control" required><option value="">Seleccione una clínica</option></select></div>
                    <div class="form-group"><label for="designado-tratamiento">Tratamiento <span class="req">*</span></label><select id="designado-tratamiento" class="form-control" required><option value="">Seleccione un tratamiento</option></select></div>
                    <div class="form-group"><label for="designado-pieza">Pieza dental (opcional)</label><input id="designado-pieza" type="number" min="0" max="99" class="form-control"></div>
                </div>
                <p>Indique las cantidades que necesita. Solo se muestran los insumos asignados al tratamiento de esta clínica.</p>
                <div class="table-wrap"><table><thead><tr><th>Insumo</th><th>Unidad</th><th>Disponible</th><th>Cantidad prevista</th><th>Solicitar</th></tr></thead><tbody id="designado-materiales"></tbody></table></div>
                <p id="designado-error" class="form-error" role="alert"></p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-designado-close>Cancelar</button><button type="submit" class="btn btn-primary" id="designado-guardar">Enviar al docente</button></div>
        </form>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/solicitud-designado.js') }}" defer></script>
@endpush
