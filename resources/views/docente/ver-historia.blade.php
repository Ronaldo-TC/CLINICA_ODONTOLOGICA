@extends('layouts.app')

@section('title', 'Revisión de historia clínica')
@section('page-title', 'Historia clínica #' . $historia->id)
@section('page-subtitle', 'Paciente: ' . ($historia->paciente?->nombre_completo ?? 'Sin nombre'))

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Resumen clínico</h2><a class="btn btn-secondary btn-sm" href="{{ route('docente.historias-clinicas') }}">Volver a historias</a>
    </div>
    <div class="card-body form-grid">
        <div><strong>Paciente</strong><br>{{ $historia->paciente?->nombre_completo ?? '—' }}</div>
        <div><strong>Clínica</strong><br>{{ $historia->clinica?->especialidad ?? '—' }}</div>
        <div><strong>Estudiante</strong><br>{{ $historia->estudiante?->nombre_completo ?? '—' }}</div>
        <div><strong>Estado</strong><br>{{ $historia->estado ?? '—' }}</div>
        <div><strong>Inicio</strong><br>{{ $historia->fecha_inicio?->format('d/m/Y') ?? '—' }}</div>
        <div><strong>Revisión</strong><br>{{ $historia->revision_docente ?: 'Sin observaciones' }}</div>
    </div>
</section>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Evaluar trabajo del estudiante</h2>
    </div>
    <form class="form-ajax" method="POST" action="{{ route('docente.evaluar-estudiante', $historia->id) }}" style="padding:16px; display:grid; gap:12px;">
        @csrf
        <div class="form-group"><label for="revisionDocente">Observación docente</label><textarea class="form-control" id="revisionDocente" name="revision_docente" minlength="10" required>{{ old('revision_docente', $historia->revision_docente) }}</textarea></div>
        <div class="form-grid">
            <div class="form-group"><label for="estadoRevision">Evaluación</label><select class="form-control" id="estadoRevision" name="estado_revision" required>
                    <option value="1" @selected($historia->estado_revision)>Aprobado</option>
                    <option value="0" @selected(! $historia->estado_revision)>Requiere corrección</option>
                </select></div>
            <div class="form-group"><label for="autorizado">Autorizar historia</label><select class="form-control" id="autorizado" name="autorizado" required>
                    <option value="1" @selected($historia->autorizado)>Sí</option>
                    <option value="0" @selected(! $historia->autorizado)>No</option>
                </select></div>
        </div>
        <button class="btn btn-primary" type="submit">Guardar evaluación</button>
    </form>
</section>

<section class="card" style="margin-top:16px;">
    <div class="card-header">
        <h2>Tratamientos y avances</h2>
    </div>
    <div class="card-body" style="display:grid; gap:16px;">
        @forelse ($historia->tratamientosRealizados as $tratamiento)
        <article style="border-bottom:1px solid var(--line); padding-bottom:16px;">
            <h3>{{ $tratamiento->tratamiento?->nombre ?? 'Tratamiento #' . $tratamiento->id }}</h3>
            <p>Estado: {{ $tratamiento->estado }} · Pieza: {{ $tratamiento->pieza_dental ?? '—' }}</p>
            @foreach ($tratamiento->sesionesAtencion as $sesion)
            <p>{{ $sesion->fecha?->format('d/m/Y') }} · {{ $sesion->descripcion }} @if($sesion->autorizado) · Revisada @endif</p>
            @endforeach
            @if ($tratamiento->estado !== 'completado')
            <form class="form-ajax" method="POST" action="{{ route('docente.registrar-avance') }}" style="display:grid; gap:8px; max-width:640px; margin-top:12px;">
                @csrf
                <input type="hidden" name="tratamiento_realizado_id" value="{{ $tratamiento->id }}">
                <input type="hidden" name="estudiante_id" value="{{ $historia->estudiante_id }}">
                <div class="form-grid">
                    <div class="form-group"><label>Fecha</label><input class="form-control" type="date" name="fecha" value="{{ now()->toDateString() }}" required></div>
                    <div class="form-group"><label>Próximo estado</label><select class="form-control" name="estado_tratamiento">
                            <option value="">Mantener estado</option>
                            <option value="en_proceso">En proceso</option>
                            <option value="completado">Completado</option>
                        </select></div>
                </div>
                <div class="form-group"><label>Avance realizado</label><textarea class="form-control" name="descripcion" minlength="5" required></textarea></div>
                <div class="form-group"><label>Observación para el estudiante</label><textarea class="form-control" name="observacion_docente"></textarea></div>
                <label><input type="checkbox" name="autorizado" value="1"> Aprobar este avance</label>
                <button class="btn btn-primary" type="submit">Registrar avance</button>
            </form>
            <form class="form-ajax" method="POST" action="{{ route('docente.finalizar-tratamiento', $tratamiento->id) }}" style="margin-top:8px;">
                @csrf
                <input type="hidden" name="observacion" value="Tratamiento finalizado por docente">
                <button class="btn btn-secondary" type="submit">Finalizar tratamiento</button>
            </form>
            @endif
        </article>
        @empty
        <p class="empty-state">Esta historia todavía no tiene tratamientos registrados.</p>
        @endforelse
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.form-ajax').forEach((form) => {
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
                if (!ok || !data.success) throw new Error(data.message || 'No se pudo guardar el cambio.');
                window.mostrarToast('success', data.message || 'Cambio guardado.');
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