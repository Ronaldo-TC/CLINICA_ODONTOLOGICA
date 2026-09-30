@extends('layouts.app')

@section('title', 'Pacientes derivados')
@section('page-title', 'Pacientes derivados')
@section('page-subtitle', 'Revisa derivaciones de tus clínicas y asígnalas a un estudiante')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Derivaciones disponibles</h2><span class="card-meta">{{ $remisiones->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('docente.pacientes-derivados') }}" class="card-body form-grid">
        <div class="form-group"><label for="clinicaId">Clínica</label><select class="form-control" id="clinicaId" name="clinica_id">
                <option value="">Todas mis clínicas</option>@foreach ($clinicas as $clinica)<option value="{{ $clinica->id }}" @selected((string)request('clinica_id')===(string)$clinica->id)>{{ $clinica->especialidad }}</option>@endforeach
            </select></div>
        <div class="form-group"><label for="buscar">Paciente</label><input class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o CI"></div>
        <div class="form-group"><label for="estado">Asignación</label><select class="form-control" id="estado" name="estado">
                <option value="">Todas</option>
                <option value="pendiente" @selected(request('estado')==='pendiente' )>Pendientes</option>
                <option value="asignado" @selected(request('estado')==='asignado' )>Asignadas</option>
            </select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-secondary" href="{{ route('docente.pacientes-derivados') }}">Limpiar</a></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Clínica</th>
                    <th>Periodo</th>
                    <th>Derivado por</th>
                    <th>Fecha</th>
                    <th>Asignar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($remisiones as $remision)
                <tr>
                    <td>{{ $remision->paciente?->nombre_completo ?? '—' }}<br><small>CI: {{ $remision->paciente?->ci ?? '—' }}</small></td>
                    <td>{{ $remision->clinica?->especialidad ?? '—' }}</td>
                    <td>{{ $remision->periodo?->nombre ?? '—' }}</td>
                    <td>{{ $remision->admision?->estudiante?->nombre_completo ?? '—' }}</td>
                    <td>{{ $remision->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @if (! $remision->historiasClinicas->isNotEmpty())
                        <form class="form-designar" action="{{ route('docente.designar-paciente') }}" method="POST" style="display:grid; gap:8px; min-width:220px;">
                            @csrf
                            <input type="hidden" name="remision_id" value="{{ $remision->id }}">
                            <input type="hidden" name="clinica_id" value="{{ $remision->clinica_id }}">
                            <select class="form-control select-estudiante" name="estudiante_id" data-clinica="{{ $remision->clinica_id }}" required>
                                <option value="">Cargando estudiantes...</option>
                            </select>
                            <button class="btn btn-primary btn-sm" type="submit">Designar paciente</button>
                        </form>
                        @else
                        <span>Ya asignado</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">No hay derivaciones para mostrar.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $remisiones->withQueryString()->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
    const URL_ESTUDIANTES_CLINICA = "{{ url('/docente/estudiantes/clinica') }}";

    document.querySelectorAll('.select-estudiante').forEach(async (select) => {
        try {
            const {
                ok,
                data
            } = await window.fetchAjax(`${URL_ESTUDIANTES_CLINICA}/${select.dataset.clinica}`);
            if (!ok || !data.success) throw new Error();
            select.replaceChildren(new Option('Selecciona un estudiante', ''));
            data.data.forEach((estudiante) => select.add(new Option(`${estudiante.nombre_completo} (${estudiante.tipo})`, estudiante.id)));
        } catch {
            select.replaceChildren(new Option('No se pudieron cargar estudiantes', ''));
        }
    });

    document.querySelectorAll('.form-designar').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            try {
                const {
                    ok,
                    data
                } = await window.fetchAjax(form.action, {
                    method: 'POST',
                    body: new URLSearchParams(new FormData(form)),
                });
                if (!ok || !data.success) throw new Error(data.message || 'No se pudo designar el paciente.');
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