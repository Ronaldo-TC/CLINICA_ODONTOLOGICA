@extends('layouts.app')

@section('title', 'Esterilizaciones')
@section('page-title', 'Esterilizaciones')
@section('page-subtitle', 'Consulta y autoriza sobres de estudiantes en tus clínicas')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Solicitudes de esterilización</h2><span class="card-meta">{{ $esterilizaciones->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.esterilizaciones') }}" class="card-body form-grid">
        <div class="form-group"><label for="estudianteId">ID de estudiante</label><input class="form-control" id="estudianteId" type="number" min="1" name="estudiante_id" value="{{ request('estudiante_id') }}"></div>
        <div class="form-group"><label for="habilitada">Estado</label><select class="form-control" id="habilitada" name="habilitada">
                <option value="">Todos</option>
                <option value="1" @selected(request('habilitada')==='1' )>Activas</option>
                <option value="0" @selected(request('habilitada')==='0' )>Inactivas</option>
            </select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.esterilizaciones') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Clínica</th>
                    <th>Sobres</th>
                    <th>Paquetes</th>
                    <th>Entrega</th>
                    <th>Autorizaciones</th>
                    <th>Autorizar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($esterilizaciones as $esterilizacion)
                <tr>
                    <td><a href="{{ route('docente.esterilizacion.estudiante', $esterilizacion->estudiante_id) }}">{{ $esterilizacion->estudiante?->nombre_completo ?? '—' }}</a></td>
                    <td>{{ $esterilizacion->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $esterilizacion->cantidad_sobres }}</td>
                    <td>{{ $esterilizacion->cantidad_paquetes }}</td>
                    <td>{{ $esterilizacion->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
                    <td>@forelse ($esterilizacion->autorizaciones as $autorizacion)<div>Sobre {{ $autorizacion->sobre }}: {{ $autorizacion->estado ? 'Aprobado' : 'Rechazado' }}</div>@empty<span>Pendiente</span>@endforelse</td>
                    <td>
                        <form class="form-autorizar-esterilizacion" method="POST" action="{{ route('docente.autorizar-esterilizacion') }}" style="display:grid; gap:8px; min-width:200px;">
                            @csrf
                            <input type="hidden" name="esterilizacion_id" value="{{ $esterilizacion->id }}">
                            <label class="form-group">Remisión asociada<input class="form-control" type="number" name="remision_id" min="1" required></label>
                            <label class="form-group">Sobre<input class="form-control" type="number" name="sobre" min="1" max="{{ max(1, $esterilizacion->cantidad_sobres) }}" required></label>
                            <select class="form-control" name="estado" required>
                                <option value="1">Aprobar</option>
                                <option value="0">Rechazar</option>
                            </select>
                            <input class="form-control" name="descripcion" maxlength="200" placeholder="Observación">
                            <button class="btn btn-primary btn-sm" type="submit">Firmar autorización</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">No hay solicitudes de esterilización en tus clínicas.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $esterilizaciones->withQueryString()->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.form-autorizar-esterilizacion').forEach((form) => {
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
                if (!ok || !data.success) throw new Error(data.message || 'No se pudo registrar la autorización.');
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