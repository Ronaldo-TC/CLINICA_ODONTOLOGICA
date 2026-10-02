@extends('layouts.app')

@section('title', 'Detalle del Insumo')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">
        <a href="{{ route('insumos.index') }}" class="btn btn-secondary btn-sm">
            ← Volver al listado
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $insumo->nombre }}</h2>
    </div>
    <div class="card-body">
        <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div><strong>Clínica:</strong> {{ $insumo->clinica->especialidad ?? '—' }}</div>
            <div><strong>Tipo:</strong> {{ $insumo->insumoTratamiento->nombre ?? '—' }}</div>
            <div><strong>Stock actual:</strong> {{ number_format($insumo->cantidad_numerica, 2) }} {{ $insumo->cantidad }}</div>
            <div><strong>Stock mínimo:</strong> {{ number_format($insumo->stock_minimo, 2) }}</div>
            <div><strong>Costo unitario:</strong> {{ number_format($insumo->costo_unitario, 2) }} Bs</div>
            <div><strong>Valor total:</strong> {{ number_format($insumo->cantidad_numerica * $insumo->costo_unitario, 2) }} Bs</div>
        </div>
    </div>
</div>
@endsection