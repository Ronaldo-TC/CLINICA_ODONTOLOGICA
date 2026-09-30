@extends('layouts.app')

@section('title', 'Solicitudes de insumos')
@section('page-title', 'Solicitudes de insumos')
@section('page-subtitle', 'Revisa y autoriza solicitudes de tus clínicas')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Solicitudes clínicas</h2><span class="card-meta">{{ $insumos->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.insumos-solicitados') }}" class="card-body form-grid">
        <div class="form-group"><label for="estado">Estado</label><select class="form-control" id="estado" name="estado">
                <option value="">Todos</option>
                <option value="pendiente" @selected(request('estado')==='pendiente' )>Pendientes</option>
                <option value="autorizado" @selected(request('estado')==='autorizado' )>Autorizados</option>
                <option value="rechazado" @selected(request('estado')==='rechazado' )>Rechazados</option>
            </select></div>
        <div class="form-group"><label for="clinicaId">ID de clínica</label><input class="form-control" id="clinicaId" type="number" min="1" name="clinica_id" value="{{ request('clinica_id') }}"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.insumos-solicitados') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Solicitud</th>
                    <th>Clínica</th>
                    <th>Paciente</th>
                    <th>Insumo</th>
                    <th>Detalle solicitado</th>
                    <th>Estado</th>
                    <th>Decisión docente</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($insumos as $solicitud)
                <tr>
                    <td>#{{ $solicitud->id }}<br>{{ $solicitud->created_at?->format('d/m/Y') }}</td>
                    <td>{{ $solicitud->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $solicitud->remision?->paciente?->nombre_completo ?? '—' }}</td>
                    <td>{{ $solicitud->insumoTratamiento?->nombre ?? '—' }}</td>
                    <td>
                        @foreach ($solicitud->detalles as $detalle)
                        <label style="display:grid; gap:4px; margin-bottom:8px;">
                            <span>{{ $detalle->insumo?->nombre ?? 'Insumo' }} · solicitado {{ $detalle->cantidad_solicitada }}</span>
                            <input class="form-control" form="decision-insumo-{{ $solicitud->id }}" type="number" min="0" step="0.01" name="detalles[{{ $loop->index }}][cantidad_entregada]" value="{{ $detalle->cantidad_entregada ?? $detalle->cantidad_solicitada }}">
                            <input form="decision-insumo-{{ $solicitud->id }}" type="hidden" name="detalles[{{ $loop->index }}][id]" value="{{ $detalle->id }}">
                        </label>
                        @endforeach
                    </td>
                    <td>{{ $solicitud->estado ?? 'Pendiente' }}</td>
                    <td>
                        <form id="decision-insumo-{{ $solicitud->id }}" class="form-autorizar-insumo" method="POST" action="{{ route('docente.autorizar-insumos', $solicitud->id) }}" style="display:grid; gap:8px; min-width:180px;">
                            @csrf
                            <input type="hidden" name="docente_autoriza" value="{{ session('user_id') }}">
                            <select class="form-control" name="autorizado" required>
                                <option value="1">Autorizar</option>
                                <option value="0">Rechazar</option>
                            </select>
                            <input class="form-control" name="observacion" maxlength="500" placeholder="Observación">
                            <button class="btn btn-primary btn-sm" type="submit">Guardar decisión</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">No hay solicitudes para mostrar.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $insumos->withQueryString()->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.form-autorizar-insumo').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button');
            button.disabled = true;
            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(form.action, {
                    method: 'POST',
                    body: new URLSearchParams(new FormData(form))
                });
                if (!ok || !data.success) throw new Error(data.message || 'No se pudo guardar la decisión.');
                window.mostrarToast('success', data.message);
                window.location.reload();
            } catch (error) {
                window.mostrarToast('error', error.message);
            } finally {
                button.disabled = false;
            }
        });
    });
</script>
@endpush