@extends('layouts.app')

@section('title', 'Asignar paciente remitido')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Asignar atención · Remisión #{{ $registro->id }}</h2>
    </div>

    <div class="card-body">
        {{-- ============================================================
             RESUMEN DE LA REMISIÓN
             ============================================================ --}}
        <div class="form-grid form-grid--spaced">
            <div class="form-group">
                <label>Paciente</label>
                <strong>{{ $registro->paciente?->nombre_completo ?? '—' }}</strong>
                <small class="text-muted">CI: {{ $registro->paciente?->ci ?? '—' }}</small>
            </div>

            <div class="form-group">
                <label>Especialidad destino</label>
                <span class="badge badge-info">{{ $registro->clinica?->especialidad ?? '—' }}</span>
            </div>
        </div>

        <div class="alert alert-info mb-20">
            Al asignar al estudiante se creará y autorizará automáticamente la historia clínica de esta especialidad.
        </div>

        @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        {{-- ============================================================
             FORMULARIO DE ASIGNACIÓN
             ============================================================ --}}
        <form method="POST"
            action="{{ route('pacientes-remitidos.asignar', $registro->id) }}"
            class="form-grid"
            id="asignacion-form">
            @csrf

            {{-- ---------- Buscador de estudiante ---------- --}}
            <div class="form-group" id="estudiante-buscador">
                <label for="buscar-estudiante">
                    Buscar estudiante por CI o nombre <span class="req">*</span>
                </label>

                <div class="autocomplete" id="estudiante-autocomplete">
                    <input
                        id="buscar-estudiante"
                        type="search"
                        class="form-control"
                        placeholder="Escriba el CI o el nombre..."
                        required
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="resultados-estudiantes"
                        aria-autocomplete="list">

                    {{-- Ventana flotante de resultados --}}
                    <div id="resultados-estudiantes"
                        class="autocomplete-panel"
                        role="listbox"
                        aria-label="Resultados de estudiantes"
                        hidden>
                        <div class="autocomplete-empty" data-empty hidden>
                            No se encontraron estudiantes con ese CI o nombre.
                        </div>

                        @foreach ($personas as $persona)
                        @if ($persona->hasAnyRole(['estudiante', 'interno']))
                        <button
                            type="button"
                            class="autocomplete-option"
                            role="option"
                            aria-selected="false"
                            data-estudiante-opcion
                            data-estudiante-id="{{ $persona->id }}"
                            data-estudiante-busqueda="{{ $persona->ci }} {{ $persona->nombre_completo }}"
                            data-estudiante-etiqueta="{{ $persona->nombre_completo }} · CI {{ $persona->ci }}"
                            hidden>
                            <span class="autocomplete-avatar">
                                {{ mb_substr($persona->nombre_completo, 0, 1) }}
                            </span>
                            <span class="autocomplete-info">
                                <span class="autocomplete-name">{{ $persona->nombre_completo }}</span>
                                <span class="autocomplete-meta">CI {{ $persona->ci }}</span>
                            </span>
                        </button>
                        @endif
                        @endforeach
                    </div>
                </div>

                <small class="text-muted">
                    Escriba para filtrar los estudiantes de esta especialidad.
                </small>
            </div>

            {{-- ---------- Estudiante seleccionado ---------- --}}
            <div class="form-group">
                <input type="hidden"
                    id="asignacion-estudiante"
                    name="estudiante_id"
                    value="{{ old('estudiante_id') }}">

                <div id="estudiante-seleccionado"
                    class="estudiante-seleccionado"
                    role="status"
                    hidden>
                    <span class="estudiante-seleccionado-avatar" id="estudiante-seleccionado-avatar"></span>
                    <span class="estudiante-seleccionado-info">
                        <span class="estudiante-seleccionado-label">Estudiante asignado</span>
                        <span class="estudiante-seleccionado-nombre" id="estudiante-seleccionado-texto"></span>
                    </span>
                    <button type="button"
                        id="cambiar-estudiante"
                        class="btn btn-sm btn-secondary">
                        Cambiar
                    </button>
                </div>

                <small class="text-muted">
                    Solo aparecen estudiantes e internos habilitados en esta especialidad.
                </small>

                @error('estudiante_id')
                <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- ---------- Docente responsable ---------- --}}
            <div class="form-group">
                @if ($docenteActual)
                <label>Docente responsable</label>
                <strong>{{ $docenteActual->nombre_completo }}</strong>
                <small class="text-muted">La atención quedará a su cargo.</small>
                @else
                <label for="asignacion-docente">
                    Docente responsable <span class="req">*</span>
                </label>
                <select id="asignacion-docente"
                    name="docente_id"
                    class="form-control"
                    required>
                    <option value="">Seleccione un docente...</option>
                    @foreach ($personas as $persona)
                    @if ($persona->hasAnyRole(['docente', 'jefe-catedra']))
                    <option value="{{ $persona->id }}"
                        @selected((string) old('docente_id')===(string) $persona->id)>
                        {{ $persona->nombre_completo }}
                    </option>
                    @endif
                    @endforeach
                </select>
                @endif

                @error('docente_id')
                <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- ---------- Acciones ---------- --}}
            <div class="actions actions--full">
                <button type="submit" class="btn btn-primary">
                    Asignar, crear y autorizar historia
                </button>
                <a class="btn btn-secondary"
                    href="{{ route('pacientes-remitidos.index') }}">
                    Volver a pacientes remitidos
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

{{-- ============================================================
     ESTILOS
     ============================================================ --}}
@push('styles')
<style>
    /* ---- Utilidades locales ---- */
    .text-muted {
        color: var(--muted);
    }

    .mb-20 {
        margin-bottom: 20px;
    }

    .form-grid--spaced {
        margin-bottom: 20px;
    }

    .actions--full {
        grid-column: 1 / -1;
    }

    /* ============================================================
       AUTOCOMPLETE (ventana flotante de resultados)
       ============================================================ */
    .autocomplete {
        position: relative;
    }

    .autocomplete-panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 50;
        max-height: 280px;
        overflow-y: auto;
        padding: 6px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow:
            0 12px 32px rgba(15, 23, 42, 0.12),
            0 2px 6px rgba(15, 23, 42, 0.06);
        animation: autocompleteIn 0.15s ease-out;
    }

    .autocomplete-panel[hidden] {
        display: none !important;
    }

    @keyframes autocompleteIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ---- Opción individual ---- */
    .autocomplete-option {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 10px 12px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        text-align: left;
        cursor: pointer;
        transition: background 0.12s ease;
        font-family: inherit;
        font-size: 14px;
        color: var(--ink, #18252d);
    }

    .autocomplete-option:hover,
    .autocomplete-option:focus-visible {
        background: #f1f5f9;
        outline: none;
    }

    .autocomplete-option[aria-selected="true"] {
        background: rgba(6, 182, 212, 0.12);
    }

    .autocomplete-option[hidden] {
        display: none !important;
    }

    /* ---- Avatar circular ---- */
    .autocomplete-avatar {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-weight: 600;
        font-size: 14px;
        color: #fff;
        background: linear-gradient(135deg, #06b6d4, #d946ef);
        text-transform: uppercase;
    }

    .autocomplete-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .autocomplete-name {
        font-weight: 600;
        font-size: 14px;
        color: #18252d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .autocomplete-meta {
        font-size: 12px;
        color: #64748b;
    }

    /* ---- Mensaje vacío ---- */
    .autocomplete-empty {
        padding: 14px 12px;
        font-size: 13px;
        color: #64748b;
        text-align: center;
    }

    .autocomplete-empty[hidden] {
        display: none !important;
    }

    /* ============================================================
       ESTUDIANTE SELECCIONADO
       ============================================================ */
    .estudiante-seleccionado {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: rgba(220, 252, 231, 0.7);
        border: 1px solid rgba(34, 197, 94, 0.35);
        border-radius: 12px;
    }

    .estudiante-seleccionado[hidden] {
        display: none !important;
    }

    .estudiante-seleccionado-avatar {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: 16px;
        color: #fff;
        background: linear-gradient(135deg, #06b6d4, #d946ef);
        text-transform: uppercase;
    }

    .estudiante-seleccionado-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 0;
    }

    .estudiante-seleccionado-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #15803d;
        font-weight: 700;
    }

    .estudiante-seleccionado-nombre {
        font-size: 14.5px;
        font-weight: 600;
        color: #14532d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endpush

{{-- ============================================================
     SCRIPT
     ============================================================ --}}
@push('scripts')
<script>
    (() => {
        'use strict';

        // ----------------------------------------------------------
        // Referencias DOM
        // ----------------------------------------------------------
        const searchInput = document.getElementById('buscar-estudiante');
        const studentIdInput = document.getElementById('asignacion-estudiante');
        const resultsContainer = document.getElementById('resultados-estudiantes');
        const emptyMessage = resultsContainer?.querySelector('[data-empty]');
        const selectedStudent = document.getElementById('estudiante-seleccionado');
        const selectedStudentText = document.getElementById('estudiante-seleccionado-texto');
        const selectedStudentAvatar = document.getElementById('estudiante-seleccionado-avatar');
        const changeStudentButton = document.getElementById('cambiar-estudiante');
        const studentSearchGroup = document.getElementById('estudiante-buscador');
        const assignmentForm = document.getElementById('asignacion-form');

        if (!searchInput || !studentIdInput || !resultsContainer || !selectedStudent ||
            !selectedStudentText || !changeStudentButton || !studentSearchGroup ||
            !assignmentForm) return;

        // ----------------------------------------------------------
        // Estado
        // ----------------------------------------------------------
        const studentOptions = Array.from(
            resultsContainer.querySelectorAll('[data-estudiante-opcion]')
        );

        let activeIndex = -1; // navegación con teclado

        // ----------------------------------------------------------
        // Utilidades
        // ----------------------------------------------------------
        const normalize = (value) =>
            value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase();

        const visibleOptions = () =>
            studentOptions.filter((o) => !o.hidden);

        const openPanel = () => {
            resultsContainer.hidden = false;
            searchInput.setAttribute('aria-expanded', 'true');
        };

        const closePanel = () => {
            resultsContainer.hidden = true;
            searchInput.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
            studentOptions.forEach((o) => o.setAttribute('aria-selected', 'false'));
        };

        // ----------------------------------------------------------
        // Filtrado
        // ----------------------------------------------------------
        const filterStudents = () => {
            const terms = normalize(searchInput.value).trim().split(/\s+/).filter(Boolean);
            let matches = 0;

            studentOptions.forEach((option) => {
                const searchable = normalize(
                    option.dataset.estudianteBusqueda || option.textContent
                );
                const isMatch = terms.length === 0 || terms.every((t) => searchable.includes(t));

                option.hidden = !isMatch;
                if (isMatch) matches++;
            });

            // Mensaje de "sin resultados"
            if (emptyMessage) emptyMessage.hidden = matches > 0;

            // Solo abrir el panel si el input tiene foco
            if (document.activeElement === searchInput) {
                openPanel();
            }
        };

        // ----------------------------------------------------------
        // Navegación con teclado
        // ----------------------------------------------------------
        const moveActive = (direction) => {
            const options = visibleOptions();
            if (options.length === 0) return;

            activeIndex = (activeIndex + direction + options.length) % options.length;

            options.forEach((o) => o.setAttribute('aria-selected', 'false'));
            const current = options[activeIndex];
            current.setAttribute('aria-selected', 'true');
            current.scrollIntoView({
                block: 'nearest'
            });
        };

        // ----------------------------------------------------------
        // Selección / limpieza
        // ----------------------------------------------------------
        const clearStudentSelection = () => {
            studentIdInput.value = '';
            selectedStudent.hidden = true;
            studentSearchGroup.hidden = false;
            searchInput.required = true;
            searchInput.setCustomValidity('');
            searchInput.value = '';
            searchInput.focus();
        };

        const selectStudent = (option) => {
            studentOptions.forEach((o) => o.setAttribute('aria-selected', 'false'));
            option.setAttribute('aria-selected', 'true');

            studentIdInput.value = option.dataset.estudianteId;
            selectedStudentText.textContent = option.dataset.estudianteEtiqueta;

            // Avatar con inicial
            if (selectedStudentAvatar) {
                selectedStudentAvatar.textContent =
                    (option.dataset.estudianteEtiqueta || '?').trim().charAt(0);
            }

            selectedStudent.hidden = false;
            studentSearchGroup.hidden = true;
            searchInput.required = false;
            searchInput.setCustomValidity('');
            closePanel();
        };

        // ----------------------------------------------------------
        // Eventos
        // ----------------------------------------------------------
        searchInput.addEventListener('input', () => {
            searchInput.setCustomValidity('');
            filterStudents();
        });

        searchInput.addEventListener('focus', () => {
            filterStudents();
            openPanel();
        });

        searchInput.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    moveActive(1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    moveActive(-1);
                    break;
                case 'Enter': {
                    const options = visibleOptions();
                    if (options.length > 0) {
                        e.preventDefault();
                        const target = activeIndex >= 0 ? options[activeIndex] : options[0];
                        selectStudent(target);
                    }
                    break;
                }
                case 'Escape':
                    closePanel();
                    searchInput.blur();
                    break;
            }
        });

        // Cerrar panel al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#estudiante-autocomplete')) {
                closePanel();
            }
        });

        studentOptions.forEach((option) => {
            option.addEventListener('click', () => selectStudent(option));
        });

        changeStudentButton.addEventListener('click', () => {
            clearStudentSelection();
            closePanel();
        });

        assignmentForm.addEventListener('submit', (event) => {
            if (studentIdInput.value) return;

            searchInput.setCustomValidity('Busque y seleccione un estudiante antes de continuar.');
            searchInput.reportValidity();
            event.preventDefault();
        });

        // ----------------------------------------------------------
        // Estado inicial (old() / recarga)
        // ----------------------------------------------------------
        const previousSelection = studentOptions.find(
            (option) => option.dataset.estudianteId === studentIdInput.value
        );

        if (previousSelection) {
            selectStudent(previousSelection);
        } else {
            studentIdInput.value = '';
            studentOptions.forEach((option) => {
                option.setAttribute('aria-selected', 'false');
                option.hidden = true;
            });
            closePanel();
        }
    })();
</script>
@endpush
