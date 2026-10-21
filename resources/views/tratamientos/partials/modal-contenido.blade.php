@if ($modo === 'precio')
<form action="{{ route('tratamientos.precio', $tratamiento) }}" data-tratamiento-config-form data-modo="precio">
    <div class="modal-body">
        <div class="form-grid">
            <div class="form-group" style="grid-column:1 / -1;">
                <label>Tratamiento</label>
                <div><strong>{{ $tratamiento->nombre }}</strong></div>
                <small style="color:var(--muted);">{{ $tratamiento->clinica->especialidad }}</small>
            </div>
            <div class="form-group">
                <label for="modal-precio-costo">Precio (Bs) <span class="req">*</span></label>
                <input class="form-control" id="modal-precio-costo" name="costo" type="number"
                    min="0" max="99999999.99" step="0.01" required
                    value="{{ $tratamiento->precio_asignado ? $tratamiento->costo : '' }}">
                <span class="form-error" data-error-for="costo"></span>
            </div>
        </div>
        <div class="alert alert-danger" data-configuracion-error role="alert" style="display:none; margin-top:18px;"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-tratamiento-config-close>Cancelar</button>
        <button type="submit" class="btn btn-primary" data-configuracion-submit>Guardar precio</button>
    </div>
</form>
@elseif ($modo === 'insumos')
<form action="{{ route('tratamientos.insumos', $tratamiento) }}" data-tratamiento-config-form data-modo="insumos">
    <div class="modal-body">
        <p style="color:var(--muted); margin-bottom:18px;">
            Seleccione los materiales de <strong>{{ $tratamiento->clinica->especialidad }}</strong> y la cantidad prevista para cada tratamiento.
        </p>
        @if ($insumosDisponibles->isEmpty())
        <div class="empty-state">
            <p>No hay insumos registrados en esta clínica.</p>
        </div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Asignar</th><th>Insumo</th><th>Unidad</th><th>Cantidad</th></tr>
                </thead>
                <tbody>
                @foreach ($insumosDisponibles as $insumo)
                    @php
                        $asignado = $tratamiento->insumos->firstWhere('id', $insumo->id);
                    @endphp
                    <tr data-insumo-row>
                        <td>
                            <input type="checkbox" value="{{ $insumo->id }}"
                                aria-label="Asignar {{ $insumo->nombre }}"
                                data-insumo-check @checked($asignado !== null)>
                        </td>
                        <td><strong>{{ $insumo->nombre }}</strong></td>
                        <td>{{ $insumo->cantidad ?: 'unidades' }}</td>
                        <td>
                            <input type="number" class="form-control" min="0.01" max="99999999.99"
                                step="0.01" required value="{{ $asignado?->pivot->cantidad ?? 1 }}"
                                aria-label="Cantidad de {{ $insumo->nombre }}"
                                data-insumo-cantidad @disabled($asignado === null)>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
        <div class="alert alert-danger" data-configuracion-error role="alert" style="display:none; margin-top:18px;"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-tratamiento-config-close>Cancelar</button>
        @unless ($insumosDisponibles->isEmpty())
        <button type="submit" class="btn btn-primary" data-configuracion-submit>Guardar insumos</button>
        @endunless
    </div>
</form>
@else
<div class="modal-body">
    <div class="form-grid">
        <div class="form-group">
            <label>Nombre</label>
            <div><strong>{{ $tratamiento->nombre }}</strong></div>
        </div>
        <div class="form-group">
            <label>Clínica</label>
            <div>{{ $tratamiento->clinica->especialidad }}</div>
            <small style="color:var(--muted);">{{ $tratamiento->clinica->modulo }}</small>
        </div>
        <div class="form-group" style="grid-column:1 / -1;">
            <label>Descripción</label>
            <div>{{ $tratamiento->descripcion ?: 'Sin descripción.' }}</div>
        </div>
        <div class="form-group">
            <label>Precio</label>
            <div>
                @if ($tratamiento->precio_asignado)
                <strong>{{ number_format($tratamiento->costo, 2) }} Bs</strong>
                @else
                <span class="badge badge-warning">Pendiente</span>
                @endif
            </div>
        </div>
        <div class="form-group">
            <label>Tratamientos realizados</label>
            <div><span class="badge badge-muted">{{ $tratamiento->tratamientos_realizados_count }}</span></div>
        </div>
    </div>

    <div style="margin-top:22px;">
        <h4 style="margin-bottom:12px;">Insumos asignados</h4>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Insumo</th><th>Cantidad</th><th>Unidad</th></tr></thead>
                <tbody>
                @forelse ($tratamiento->insumos as $insumo)
                <tr>
                    <td>{{ $insumo->nombre }}</td>
                    <td>{{ number_format($insumo->pivot->cantidad, 2) }}</td>
                    <td>{{ $insumo->cantidad ?: 'unidades' }}</td>
                </tr>
                @empty
                <tr><td colspan="3">Sin insumos asignados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-tratamiento-config-close>Cerrar</button>
</div>
@endif
