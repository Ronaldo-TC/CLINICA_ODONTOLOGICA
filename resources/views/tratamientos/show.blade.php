@extends('layouts.app')

@section('title', 'Configuración del tratamiento')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <strong>{{ $tratamiento->nombre }}</strong>
        <span class="badge badge-info">{{ $tratamiento->clinica->especialidad }} ({{ $tratamiento->clinica->modulo }})</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('tratamientos.index', ['clinica_id' => $tratamiento->clinica_id]) }}">Volver a tratamientos</a>
</div>

@if (session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="card" id="precio">
    <div class="card-header"><h2>Precio del tratamiento</h2></div>
    <div class="card-body">
        <p style="margin-bottom:18px;">{{ $tratamiento->descripcion ?: 'Sin descripción.' }}</p>
        @if ($tratamiento->precio_asignado)
        <p style="margin-bottom:18px;"><strong>{{ number_format($tratamiento->costo, 2) }} Bs</strong> · PG: {{ $tratamiento->porcentaje_pg ?: '—' }}</p>
        @else
        <p style="margin-bottom:18px;"><span class="badge badge-warning">Precio pendiente de asignación por Caja</span></p>
        @endif
        @can('tratamientos.precios')
        <form method="POST" action="{{ route('tratamientos.precio', $tratamiento) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-group">
                    <label for="precio-costo">Precio (Bs) <span class="req">*</span></label>
                    <input class="form-control" id="precio-costo" name="costo" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('costo', $tratamiento->precio_asignado ? $tratamiento->costo : '') }}">
                </div>
                <div class="form-group">
                    <label for="precio-pg">% PG</label>
                    <input class="form-control" id="precio-pg" name="porcentaje_pg" maxlength="100" value="{{ old('porcentaje_pg', $tratamiento->porcentaje_pg) }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar precio</button>
        </form>
        @endcan
    </div>
</div>

<div class="card" id="insumos">
    <div class="card-header"><h2>Insumos del tratamiento</h2></div>
    <div class="card-body">
        @can('tratamientos.insumos')
        <p style="color:var(--muted); margin-bottom:18px;">Seleccione los materiales de esta clínica y la cantidad prevista para cada tratamiento. Esta configuración no descuenta existencias.</p>
        @if ($insumosDisponibles->isEmpty())
        <p>No hay insumos registrados en esta clínica. Registre primero los materiales en el catálogo de Insumos.</p>
        @canany(['insumos.ver', 'insumos.gestionar'])
        <a class="btn btn-secondary" href="{{ route('insumos.index') }}">Ir al catálogo de insumos</a>
        @endcanany
        @else
        <form method="POST" action="{{ route('tratamientos.insumos', $tratamiento) }}" id="formInsumosTratamiento">
            @csrf
            @method('PUT')
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Asignar</th><th>Insumo</th><th>Unidad</th><th>Cantidad por tratamiento</th></tr></thead>
                    <tbody>
                    @foreach ($insumosDisponibles as $insumo)
                        @php
                            $seleccion = collect(old('insumos', $tratamiento->insumos->map(fn ($material) => ['insumo_id' => $material->id, 'cantidad' => $material->pivot->cantidad])->all()))->firstWhere('insumo_id', $insumo->id);
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="insumos[{{ $loop->index }}][insumo_id]" value="{{ $insumo->id }}" aria-label="Asignar {{ $insumo->nombre }}" @checked($seleccion !== null) data-insumo-check></td>
                            <td><strong>{{ $insumo->nombre }}</strong></td>
                            <td>{{ $insumo->cantidad ?: 'unidades' }}</td>
                            <td><input type="number" class="form-control" name="insumos[{{ $loop->index }}][cantidad]" min="0.01" max="99999999.99" step="0.01" required aria-label="Cantidad de {{ $insumo->nombre }}" value="{{ $seleccion['cantidad'] ?? 1 }}" @disabled($seleccion === null) data-insumo-cantidad></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:18px;">Guardar insumos</button>
        </form>
        @endif
        @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Insumo</th><th>Cantidad por tratamiento</th><th>Unidad</th></tr></thead>
                <tbody>
                @forelse ($tratamiento->insumos as $insumo)
                <tr><td>{{ $insumo->nombre }}</td><td>{{ number_format($insumo->pivot->cantidad, 2) }}</td><td>{{ $insumo->cantidad ?: 'unidades' }}</td></tr>
                @empty
                <tr><td colspan="3">Sin insumos asignados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('formInsumosTratamiento')?.addEventListener('change', (event) => {
    if (event.target.matches('[data-insumo-check]')) {
        event.target.closest('tr').querySelector('[data-insumo-cantidad]').disabled = !event.target.checked;
    }
});
</script>
@endpush
