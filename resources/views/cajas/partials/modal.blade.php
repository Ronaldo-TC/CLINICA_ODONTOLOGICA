<div class="modal-backdrop" id="modalCaja">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalCajaTitulo">
        <form id="formCaja" data-store="{{ route('cajas.store') }}" data-update="{{ route('cajas.update', '__ID__') }}" data-ficha="{{ route('cajas.buscar-ficha') }}" data-pacientes="{{ route('cajas.buscar-pacientes') }}">
            @csrf
            <input type="hidden" name="caja_id" id="formCajaId">
            <input type="hidden" name="tratamiento_id">
            <div class="modal-header"><h3 id="modalCajaTitulo">Nuevo recibo</h3><button type="button" class="modal-close" data-caja-close aria-label="Cerrar">×</button></div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1 / -1;">
                        <label for="caja-paciente-buscar">Buscar por CI o nombre del estudiante o paciente</label>
                        <div class="autocomplete-wrap" id="caja-paciente-busqueda">
                            <input type="search" id="caja-paciente-buscar" class="form-control" maxlength="100" autocomplete="off" placeholder="CI o nombre del estudiante, CI o nombre del paciente" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="caja-paciente-resultados" aria-describedby="caja-paciente-buscar-ayuda">
                            <div id="caja-paciente-resultados" class="autocomplete-dropdown" role="listbox" aria-label="Pacientes y fichas de admisión encontrados" hidden></div>
                        </div>
                        <small id="caja-paciente-buscar-ayuda">Escriba al menos dos caracteres y seleccione la ficha correspondiente, o busque directamente por número de ficha.</small>
                        <p id="caja-paciente-buscar-estado" role="status" aria-live="polite"></p>
                    </div>
                    <div class="form-group"><label for="caja-ficha">Número de ficha de admisión <span class="req">*</span></label><input type="number" id="caja-ficha" name="ficha" min="1" class="form-control" required aria-describedby="caja-ficha-ayuda"><small id="caja-ficha-ayuda">Al ingresar la ficha se completan automáticamente el estudiante y el paciente designados.</small></div>
                    <div class="form-group"><label for="caja-clinica">Clínica <span class="req">*</span></label><select id="caja-clinica" name="clinica_id" class="form-control" required><option value="">Seleccione una clínica</option>@foreach($clinicas as $clinica)<option value="{{ $clinica->id }}">{{ $clinica->especialidad }}</option>@endforeach</select></div>
                    <div class="form-group" style="grid-column:1 / -1;"><label for="caja-atencion">Tratamiento autorizado del paciente <span class="req">*</span></label><select id="caja-atencion" name="tratamiento_realizado_id" class="form-control" required><option value="">Busque primero la ficha</option></select></div>
                    <div class="form-group"><label for="caja-estudiante">Estudiante designado</label><input id="caja-estudiante" class="form-control" readonly></div>
                    <div class="form-group"><label for="caja-paciente">Paciente designado</label><input id="caja-paciente" class="form-control" readonly></div>
                    <div class="form-group"><label for="caja-recibo">Número de recibo</label><input id="caja-recibo" name="recibo" type="number" class="form-control" readonly aria-describedby="caja-recibo-ayuda"><small id="caja-recibo-ayuda">Se asigna automáticamente al registrar el pago.</small></div>
                    <div class="form-group"><label for="caja-servicio">Servicio pagado</label><select id="caja-servicio" name="tipo" class="form-control"><option value="tratamiento">Tratamiento clínico</option><option value="radiologia">Estudio radiológico</option></select></div>
                    <div class="form-group"><label for="caja-tipo-pago">Tipo de pago</label><select id="caja-tipo-pago" name="tipo_pago" class="form-control"><option value="Efectivo">Efectivo</option><option value="Transferencia">Transferencia</option><option value="Tarjeta">Tarjeta</option><option value="QR">QR</option></select></div>
                    <div class="form-group"><label for="caja-costo-total">Costo autorizado (Bs)</label><input id="caja-costo-total" class="form-control" readonly></div>
                    <div class="form-group"><label for="caja-anterior">Cancelado en otros recibos (Bs)</label><input id="caja-anterior" class="form-control" readonly value="0.00"></div>
                    <div class="form-group"><label for="caja-cancelado">Pago de este recibo (Bs) <span class="req">*</span></label><input id="caja-cancelado" name="cancelado" type="number" min="0" max="99999999.99" step="0.01" class="form-control" required value="0"></div>
                    <div class="form-group"><label for="caja-total-cancelado">Total cancelado (Bs)</label><input id="caja-total-cancelado" class="form-control" readonly value="0.00"></div>
                    <div class="form-group"><label for="caja-saldo">Saldo pendiente (Bs)</label><input id="caja-saldo" class="form-control" readonly></div>
                    <div class="form-group"><label for="caja-descripcion">Descripción</label><input id="caja-descripcion" name="descripcion" class="form-control" maxlength="100"></div>
                    <div class="form-group"><label><input type="checkbox" name="habilitada" value="1" checked> Recibo habilitado</label></div>
                </div>
                <p id="formCajaErrorText" class="form-error" role="alert"></p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-caja-close>Cancelar</button><button type="submit" class="btn btn-primary" id="btnGuardarCaja">Guardar recibo</button></div>
        </form>
    </div>
</div>
@push('styles')
<style>
    #caja-paciente-resultados .autocomplete-item { width: 100%; border: 0; background: transparent; color: var(--ink); font: inherit; text-align: left; }
    #caja-paciente-resultados .autocomplete-item:hover,
    #caja-paciente-resultados .autocomplete-item.active { background: var(--surface-soft); }
    #caja-paciente-resultados .autocomplete-info { pointer-events: none; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/caja-recibos.js') }}" defer></script>
@endpush
