<form method="GET" action="{{ url()->current() }}" class="form-grid" data-estudiante-filtros>
    <div class="form-group">
        <label for="buscar">Buscar paciente</label>
        <input id="buscar" name="buscar" class="form-control" value="{{ request('buscar') }}" placeholder="Nombre, apellido o CI" maxlength="255">
    </div>
    @if($mostrarClinica ?? true)
        <div class="form-group">
            <label for="clinica_id">Especialidad</label>
            <select id="clinica_id" name="clinica_id" class="form-control">
                <option value="">Todas mis especialidades</option>
                @foreach($clinicas as $clinica)
                    <option value="{{ $clinica->id }}" @selected(request('clinica_id') == $clinica->id)>{{ $clinica->especialidad }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="form-group">
        <label for="periodo_id">Periodo</label>
        <select id="periodo_id" name="periodo_id" class="form-control">
            <option value="">Todos los periodos</option>
            @foreach($periodos as $periodo)
                <option value="{{ $periodo->id }}" @selected(request('periodo_id') == $periodo->id)>{{ $periodo->nombre }} · {{ $periodo->gestion }}-{{ $periodo->semestre }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <div class="actions"><button class="btn btn-primary">Filtrar</button><button type="reset" class="btn btn-secondary">Limpiar</button></div>
    </div>
</form>
<p data-estudiante-status role="status" aria-live="polite"></p>
@pushOnce('scripts', 'estudiante-pacientes')
    <script src="{{ asset('js/estudiante-pacientes.js') }}" defer></script>
@endPushOnce
