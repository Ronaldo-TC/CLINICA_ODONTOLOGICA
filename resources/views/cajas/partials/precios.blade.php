<div class="card" style="margin-bottom:18px;">
    <div class="card-header"><h2>Precios de tratamientos por clínica</h2></div>
    <div class="card-body">
        <form id="formPrecioCaja" data-precio="{{ route('tratamientos.precio', '__ID__') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group"><label for="tarifa-clinica">Clínica</label><select id="tarifa-clinica" class="form-control" required><option value="">Seleccione una clínica</option>@foreach($clinicas as $clinica)<option value="{{ $clinica->id }}">{{ $clinica->especialidad }} ({{ $clinica->modulo }})</option>@endforeach</select></div>
                <div class="form-group"><label for="tarifa-tratamiento">Tratamiento</label><select id="tarifa-tratamiento" class="form-control" required disabled><option value="">Seleccione primero la clínica</option></select></div>
                <div class="form-group"><label for="tarifa-costo">Precio del tratamiento (Bs)</label><input id="tarifa-costo" class="form-control" type="number" min="0" max="99999999.99" step="0.01" required></div>
            </div>
            <button class="btn btn-primary" type="submit">Guardar precio</button><p id="tarifa-resultado" role="status"></p>
        </form>
    </div>
</div>
<script type="application/json" id="catalogoPreciosCaja">@json($catalogoTratamientos)</script>
@push('scripts')
<script src="{{ asset('js/caja-precios.js') }}" defer></script>
@endpush
