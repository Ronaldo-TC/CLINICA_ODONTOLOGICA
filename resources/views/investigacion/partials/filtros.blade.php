{{-- Filtros del dataset anonimizado (AJAX con debounce de 300 ms) --}}
<div class="form-grid">
    <div class="form-group">
        <label for="filtro-anio">Año</label>
        <select id="filtro-anio" class="form-control">
            <option value="">Todos los años</option>
            @foreach ($anios as $anio)
            <option value="{{ $anio }}">{{ $anio }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="filtro-clinica">Clínica / Especialidad</label>
        <select id="filtro-clinica" class="form-control">
            <option value="">Todas las clínicas</option>
            @foreach ($clinicas as $clinica)
            <option value="{{ $clinica->id }}">
                {{ $clinica->especialidad ?: $clinica->modulo }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="filtro-edad-min">Edad mínima</label>
        <input type="number" id="filtro-edad-min" class="form-control"
            min="0" max="120" step="1" placeholder="0" autocomplete="off">
    </div>

    <div class="form-group">
        <label for="filtro-edad-max">Edad máxima</label>
        <input type="number" id="filtro-edad-max" class="form-control"
            min="0" max="120" step="1" placeholder="120" autocomplete="off">
    </div>

    <div class="form-group">
        <label for="filtro-genero">Género</label>
        <select id="filtro-genero" class="form-control">
            <option value="">Todos</option>
            @foreach ($generos as $genero)
            <option value="{{ $genero }}">{{ $genero }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="filtro-motivo">Motivo de consulta</label>
        <select id="filtro-motivo" class="form-control">
            <option value="">Todos</option>
            @foreach ($motivos as $motivo)
            <option value="{{ $motivo }}">{{ $motivo }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-actions">
        <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
            Limpiar
        </button>
    </div>
</div>
