@extends('layouts.app')

@section('title', 'Detalle de bitácora')

@section('content')
<div class="dashboard-toolbar">
    <div class="toolbar-info">Evento #{{ $registro->id }} · Registro de auditoría</div>
    <div class="toolbar-actions">
        <a class="btn btn-secondary" href="{{ route('bitacoras.index') }}"><x-icono nombre="historia" /> Volver a Bitácoras</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $registro->descripcion }}</h2>
        <span class="badge badge-{{ in_array($registro->estado, ['exito', 'success', 'ok'], true) ? 'success' : (in_array($registro->estado, ['error', 'fallo'], true) ? 'danger' : 'muted') }}">{{ $registro->estado_nombre }}</span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group"><label>Realizado por</label><strong>{{ $registro->usuario_nombre }}</strong><small>{{ $registro->usuario_cuenta }}</small></div>
            <div class="form-group"><label>Fecha y hora</label><span>{{ $registro->fecha_formateada }}</span></div>
            <div class="form-group"><label>Tipo de acción</label><span>{{ ucfirst($registro->tipo) }}</span></div>
            <div class="form-group"><label>Dirección IP</label><code>{{ $registro->ip }}</code></div>
        </div>

        @if (!empty($registro->detalles['registros']))
        <h3 class="section-title">Registros afectados</h3>
        <div class="form-grid">
            @foreach ($registro->detalles['registros'] as $nombre => $id)
            <div class="form-group"><label>{{ \Illuminate\Support\Str::headline($nombre) }}</label><span>#{{ $id }}</span></div>
            @endforeach
        </div>
        @endif

        <h3 class="section-title">Detalle completo del evento</h3>
        @if (isset($registro->detalles['resultado']))
        <p>{{ $registro->detalles['resultado'] }}</p>
        @elseif ($registro->detalles)
        <div class="form-grid">
            @foreach ($registro->detalles as $nombre => $valor)
                @if (is_scalar($valor))
                <div class="form-group"><label>{{ \Illuminate\Support\Str::headline($nombre) }}</label><span>{{ $valor }}</span></div>
                @endif
            @endforeach
        </div>
        @else
        <p class="table-code-wrap">{{ $registro->datos }}</p>
        @endif
    </div>
</div>
@endsection
