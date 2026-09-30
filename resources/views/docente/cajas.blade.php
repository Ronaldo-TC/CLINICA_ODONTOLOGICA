@extends('layouts.app')

@section('title', 'Cajas de mi clínica')
@section('page-title', 'Cajas')
@section('page-subtitle', 'Consulta y autorización de cobros de tus clínicas')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Movimientos de caja</h2><span class="card-meta">{{ $cajas->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.cajas') }}" class="card-body form-grid">
        <div class="form-group"><label for="clinicaId">ID de clínica</label><input class="form-control" id="clinicaId" type="number" name="clinica_id" min="1" value="{{ request('clinica_id') }}"></div>
        <div class="form-group"><label for="habilitada">Estado</label><select class="form-control" id="habilitada" name="habilitada">
                <option value="">Todos</option>
                <option value="1" @selected(request('habilitada')==='1' )>Autorizadas</option>
                <option value="0" @selected(request('habilitada')==='0' )>Pendientes</option>
            </select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.cajas') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Recibo</th>
                    <th>Paciente</th>
                    <th>Clínica</th>
                    <th>Tratamiento</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Autorización</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cajas as $caja)
                <tr>
                    <td>{{ $caja->recibo ?? $caja->id }}</td>
                    <td>{{ $caja->remision?->paciente?->nombre_completo ?? $caja->paciente_nombre_completo ?? '—' }}</td>
                    <td>{{ $caja->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $caja->tratamiento?->nombre ?? '—' }}</td>
                    <td>Bs {{ number_format((float)$caja->costo_total, 2) }}</td>
                    <td>Bs {{ number_format((float)$caja->cancelado, 2) }}</td>
                    <td>Bs {{ number_format(max(0, (float)$caja->costo_total - (float)$caja->cancelado), 2) }}</td>
                    <td>{{ $caja->habilitada ? 'Autorizada' : 'Pendiente' }}</td>
                    <td>
                        <form class="form-autorizar-caja" method="POST" action="{{ route('docente.autorizar-caja', $caja->id) }}" style="display:grid; gap:8px; min-width:180px;">
                            @csrf
                            <select class="form-control" name="habilitada" required>
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
                    <td colspan="9">
                        <div class="empty-state">No hay movimientos de caja en tus clínicas.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $cajas->withQueryString()->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.form-autorizar-caja').forEach((form) => {
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
                if (!ok || !data.success) throw new Error(data.message || 'No se pudo actualizar la caja.');
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