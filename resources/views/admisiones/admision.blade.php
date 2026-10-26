@extends('layouts.app')
@section('title', isset($admision) ? 'Editar admisión' : 'Nueva admisión')
@section('page-title', isset($admision) ? 'Editar admisión' : 'Nueva admisión')
@section('page-subtitle', 'Ficha de evaluación inicial del paciente')

@section('content')
@php
$ficha = $admision ?? null;
$pacienteSeleccionado = $ficha?->paciente ?? $pacienteInicial ?? null;
$pacienteSeleccionadoId = old('paciente_id', $ficha?->paciente_id ?? $pacienteSeleccionado?->id);
$seleccionadas = old('clinica_ids', $ficha?->remisiones->where('habilitado', true)->pluck('clinica_id')->all() ?? []);
$vistaOdontogramaInicial = ($odontograma3d ?? false) ? '3d' : '2d';
@endphp
<div class="dashboard-toolbar">
    <a href="{{ route('admisiones.index') }}" class="btn btn-secondary">Volver a Admisiones</a>
    @isset($admision)
    <a href="{{ route('admisiones.ficha-pdf', ['admision' => $admision->id, 'formato' => 'pdf']) }}" class="btn btn-secondary">PDF de la ficha</a>
    @endisset
</div>
<form id="formAdmision" method="POST" action="{{ isset($admision) ? route('admisiones.update', $admision->id) : route('admisiones.store') }}">
    @csrf
    @isset($admision) @method('PUT') @endisset
    <input type="hidden" name="actualizar_derivaciones" value="1">
    <input type="hidden" name="odontograma_pintado_anterior" value="{{ $ficha?->odontograma_pintado }}">
    <div class="card">
        <div class="card-header">
            <h2>{{ isset($admision) ? 'Ficha de admisión #'.$admision->id : 'Nueva admisión' }}</h2>
        </div>
        <div class="card-body form-grid">
            <div class="form-group" style="grid-column:1/-1">
                <label for="adm-paciente-buscar">Paciente <span class="req">*</span></label>
                <div class="autocomplete-wrap {{ $pacienteSeleccionadoId ? 'has-value' : '' }}" id="wrapPaciente">
                    <input type="hidden" name="paciente_id" id="adm-paciente-id" value="{{ $pacienteSeleccionadoId }}">
                    <input type="text" id="adm-paciente-buscar" class="form-control autocomplete-input" value="{{ $pacienteSeleccionado?->nombre_completo }}" placeholder="Buscar por nombre o CI..." autocomplete="off" required @readonly(isset($admision))>
                    <button type="button" class="autocomplete-clear" aria-label="Limpiar paciente" @if(isset($admision)) disabled hidden style="display:none;" @endif>×</button>
                    <div class="autocomplete-dropdown" id="dropdownPaciente"></div>
                </div>
                <div id="datosPacienteResumen" class="alert alert-info" style="display:none; margin-top:10px;">
                    <strong id="datosPacienteNombre">Paciente</strong>
                    <div id="datosPacienteMeta" style="margin-top:6px; color:var(--muted); font-size:13px;"></div>
                    <p id="datosPacienteOrigen" style="margin-top:10px; margin-bottom:0;"></p>
                    @canany(['admisiones.ver', 'admisiones.gestionar'])
                    <a id="datosPacienteFicha" class="btn btn-sm btn-secondary" href="#" style="display:none; margin-top:10px;">Ver última ficha</a>
                    <a id="datosPacienteReporte" class="btn btn-sm btn-secondary" href="#" style="display:none; margin-top:10px;" target="_blank" rel="noopener">Ver seguimiento del paciente</a>
                    @endcanany
                    @canany(['historias.ver', 'historias.gestionar'])
                    <a id="datosPacienteHistoria" class="btn btn-sm btn-secondary" href="#" style="display:none; margin-top:10px;">Ver historia clínica</a>
                    @endcanany
                </div>
                <div id="cargaPacienteEstado" role="status" aria-live="polite" style="display:none; margin-top:10px;">
                    <span id="cargaPacienteMensaje"></span>
                    <button type="button" id="btnReintentarPaciente" class="btn btn-sm btn-secondary" hidden>Reintentar carga</button>
                </div>
                <span class="form-error" data-error-for="paciente_id"></span>
            </div>
            <div class="form-group">
                <label for="adm-motivo">Motivo de consulta</label>
                <select id="adm-motivo" name="motivo_consulta_id" class="form-control">
                    <option value="">Sin motivo</option>
                    @php($motivoSeleccionado = old('motivo_consulta_id', $ficha?->motivo_consulta_id))
                    @if(filled($motivoSeleccionado) && ! $motivos->contains('id', $motivoSeleccionado))
                    <option value="{{ $motivoSeleccionado }}" selected>Motivo registrado en la ficha</option>
                    @endif
                    @foreach($motivos as $motivo)
                    <option value="{{ $motivo->id }}" @selected((string) old('motivo_consulta_id', $ficha?->motivo_consulta_id) === (string) $motivo->id)>{{ $motivo->nombre }}</option>
                    @endforeach
                </select>
                <span class="form-error" data-error-for="motivo_consulta_id"></span>
            </div>
            @isset($admision)
            @foreach(['telefono' => ['Teléfono', 30], 'estado_civil' => ['Estado civil', 200], 'escuela' => ['Escuela', 200], 'ocupacion' => ['Ocupación', 200], 'direccion' => ['Dirección', 200], 'emergencia' => ['Contacto de emergencia', 200], 'telefono_emergencia' => ['Teléfono de emergencia', 30]] as $campo => [$etiqueta, $limite])
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <input type="{{ str_starts_with($campo, 'telefono') ? 'tel' : 'text' }}" name="{{ $campo }}" id="adm-{{ $campo }}" maxlength="{{ $limite }}" class="form-control" value="{{ old($campo, $ficha?->$campo) }}">
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
            @endisset
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Motivo y antecedentes</h2>
        </div>
        <div class="card-body">
            @foreach(['motivo_consulta' => 'Motivo de consulta', 'historia_enfermedad_actual' => 'Historia de la enfermedad actual', 'antecedentes' => 'Antecedentes patológicos personales', 'gmh' => 'Historia médica general'] as $campo => $etiqueta)
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <textarea id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control" rows="3" maxlength="10000">{{ old($campo, $ficha?->$campo) }}</textarea>
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Estado físico general y signos vitales</h2>
        </div>
        <div class="card-body form-grid">
            <div class="form-group">
                <label for="adm-gpe">Estado físico general <span class="req">*</span></label>
                <select name="gpe" id="adm-gpe" class="form-control" required>
                    <option value="">Seleccione</option>
                    @foreach(['Bueno', 'Regular', 'Malo'] as $estado)
                    <option value="{{ $estado }}" @selected(old('gpe', $ficha?->gpe) === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
                <span class="form-error" data-error-for="gpe"></span>
            </div>
            @foreach(['peso_kg' => ['Peso (kg)', '0.01', 9999.99], 'talla_mts' => ['Talla (m)', '0.01', 9.99], 'temperatura_c' => ['Temperatura (°C)', '0.1', 100], 'pulso' => ['Pulso (por minuto)', '1', 1000], 'frecuencia_respiratoria' => ['Frecuencia respiratoria · F.R. (ciclos/min)', '1', 1000]] as $campo => [$etiqueta, $paso, $maximo])
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <input type="number" id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control" min="0" max="{{ $maximo }}" step="{{ $paso }}" value="{{ old($campo, $ficha?->$campo) }}">
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
            <div class="form-group">
                <label for="adm-presion_arterial">Presión arterial · P.A. (mmHg)</label>
                <input type="text" id="adm-presion_arterial" name="presion_arterial" class="form-control" maxlength="20" placeholder="Ej.: 120/80" value="{{ old('presion_arterial', $ficha?->presion_arterial) }}">
                <span class="form-error" data-error-for="presion_arterial"></span>
            </div>
            <div class="form-group">
                <label for="adm-signos_vitales">Observaciones de signos vitales</label>
                <textarea id="adm-signos_vitales" name="signos_vitales" class="form-control" rows="2" maxlength="10000">{{ old('signos_vitales', $ficha?->signos_vitales) }}</textarea>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Examen extraoral</h2>
        </div>
        <div class="card-body form-grid">
            @foreach(['asimetria_facial' => 'Asimetría facial', 'cambio_color' => 'Cambio de color', 'abultamiento' => 'Abultamiento', 'ganglios_palpables' => 'Ganglios cervicales palpables', 'ruidos_articulares' => 'ATM · Ruidos articulares'] as $campo => $etiqueta)
            @php($valor = old($campo, $ficha?->$campo))
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <select id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control">
                    <option value="">Sin registrar</option>
                    <option value="1" @selected($valor !==null && $valor !=='' && (bool) $valor)>Sí</option>
                    <option value="0" @selected($valor !==null && $valor !=='' && ! (bool) $valor)>No</option>
                </select>
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
            @foreach(['ee' => 'Observaciones del examen extraoral', 'cn' => 'Observaciones de ganglios cervicales', 'atm' => 'Observaciones de la articulación temporomandibular (ATM)'] as $campo => $etiqueta)
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <textarea id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control" rows="2" maxlength="10000">{{ old($campo, $ficha?->$campo) }}</textarea>
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Examen intraoral</h2>
        </div>
        <div class="card-body form-grid">
            @foreach(['bm' => 'Mucosa bucal', 'gm' => 'Mucosa gingival'] as $campo => $etiqueta)
            <div class="form-group">
                <label for="adm-{{ $campo }}_estado">{{ $etiqueta }}</label>
                <select id="adm-{{ $campo }}_estado" name="{{ $campo }}_estado" class="form-control">
                    <option value="">Sin registrar</option>
                    @foreach(['Sin alteraciones aparentes', 'Con alteraciones'] as $estado)
                    <option value="{{ $estado }}" @selected(old($campo.'_estado', $ficha?->{$campo.'_estado'}) === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
                <span class="form-error" data-error-for="{{ $campo }}_estado"></span>
                <label for="adm-{{ $campo }}">Observaciones · {{ $etiqueta }}</label>
                <textarea id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control" rows="2" maxlength="10000">{{ old($campo, $ficha?->$campo) }}</textarea>
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
            @foreach(['calculo' => ['Cálculos', ['No', 'Sí']], 'oclusion' => ['Oclusión', ['Normal', 'Sobreoclusión', 'Mordida abierta', 'Articulación cruzada', 'Mordida bis a bis']]] as $campo => [$etiqueta, $opciones])
            @php($valorSeleccionado = old($campo, $ficha?->$campo))
            <div class="form-group">
                <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                <select id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control">
                    <option value="">Sin registrar</option>
                    @if(filled($valorSeleccionado) && ! in_array($valorSeleccionado, $opciones, true))
                    <option value="{{ $valorSeleccionado }}" selected>{{ $valorSeleccionado }}</option>
                    @endif
                    @foreach($opciones as $opcion)
                    <option value="{{ $opcion }}" @selected(old($campo, $ficha?->$campo) === $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
                <span class="form-error" data-error-for="{{ $campo }}"></span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Odontograma</h2>
        </div>
        <div class="card-body">
            <div class="admision-view-switcher" role="tablist" aria-label="Vista del odontograma">
                <button type="button" id="adm-odontograma-tab-2d" class="admision-view-tab"
                    role="tab" aria-controls="adm-odontograma-vista-2d"
                    aria-selected="{{ $vistaOdontogramaInicial === '2d' ? 'true' : 'false' }}"
                    tabindex="{{ $vistaOdontogramaInicial === '2d' ? '0' : '-1' }}" data-admision-odontograma-vista="2d">
                    <span aria-hidden="true">2D</span>
                    Vista 2D
                </button>
                <button type="button" id="adm-odontograma-tab-3d" class="admision-view-tab"
                    role="tab" aria-controls="adm-odontograma-vista-3d"
                    aria-selected="{{ $vistaOdontogramaInicial === '3d' ? 'true' : 'false' }}"
                    tabindex="{{ $vistaOdontogramaInicial === '3d' ? '0' : '-1' }}" data-admision-odontograma-vista="3d">
                    <span aria-hidden="true">3D</span>
                    Vista 3D
                </button>
            </div>
            <p class="admision-view-help">Puede alternar entre ambas vistas; las marcas realizadas se conservan en el mismo odontograma.</p>

            <div id="adm-odontograma-vista-2d" class="admision-view-panel" role="tabpanel"
                aria-labelledby="adm-odontograma-tab-2d" @if($vistaOdontogramaInicial !== '2d') hidden @endif>
                <x-odontograma nombre="odontograma_pintado" id="adm-odontograma" :valor="old('odontograma_pintado', $ficha?->odontograma_pintado)" :denticion="old('odontograma_denticion', $ficha?->odontograma_denticion ?? 'adulto')" />
            </div>
            <div id="adm-odontograma-vista-3d" class="admision-view-panel" role="tabpanel"
                aria-labelledby="adm-odontograma-tab-3d" @if($vistaOdontogramaInicial !== '3d') hidden @endif>
                @include('admisiones.partials.odontograma-3d')
            </div>
            <div class="form-group" style="margin-top:18px;">
                <label for="adm-odontograma-observaciones">Observaciones del odontograma</label>
                <textarea id="adm-odontograma-observaciones" name="odontograma" class="form-control" rows="2" maxlength="10000">{{ old('odontograma', $ficha?->odontograma) }}</textarea>
                <span class="form-error" data-error-for="odontograma"></span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Diagnóstico y derivación</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="adm-pieza_dental">Piezas dentales</label>
                <input type="text" id="adm-pieza_dental" name="pieza_dental" class="form-control" maxlength="200" value="{{ old('pieza_dental', $ficha?->pieza_dental) }}">
                <span class="form-error" data-error-for="pieza_dental"></span>
            </div>
            <div class="form-group">
                <label for="adm-diagnostico">Diagnóstico(s) presuntivo(s)</label>
                <textarea id="adm-diagnostico" name="diagnostico" rows="3" maxlength="10000" class="form-control">{{ old('diagnostico', $ficha?->diagnostico) }}</textarea>
                <span class="form-error" data-error-for="diagnostico"></span>
            </div>
            <fieldset class="form-group">
                <legend>Área(s) derivada(s) · Clínicas</legend>
                <div class="checkbox-grid">
                    @foreach($clinicas as $clinica)
                    <div class="form-group">
                        <label class="checkbox-item"><input type="checkbox" name="clinica_ids[]" value="{{ $clinica->id }}" @checked(in_array($clinica->id, $seleccionadas))> <span>{{ $clinica->especialidad }}</span></label>
                        <label for="propuesta-{{ $clinica->id }}">Tratamiento sugerido (opcional)</label>
                        <select id="propuesta-{{ $clinica->id }}" name="tratamientos_propuestos[{{ $clinica->id }}]" class="form-control">
                            <option value="">Sin propuesta inicial</option>
                            @foreach($tratamientosPropuestos->where('clinica_id', $clinica->id) as $tratamiento)
                            <option value="{{ $tratamiento->id }}" @selected((string) old('tratamientos_propuestos.'.$clinica->id, $ficha?->remisiones->firstWhere('clinica_id', $clinica->id)?->tratamiento_propuesto_id) === (string) $tratamiento->id)>{{ $tratamiento->nombre }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error-for="tratamientos_propuestos.{{ $clinica->id }}"></span>
                        <label for="observacion-propuesta-{{ $clinica->id }}">Observación de la propuesta</label>
                        <input id="observacion-propuesta-{{ $clinica->id }}" name="observaciones_propuestas[{{ $clinica->id }}]" class="form-control" maxlength="2000" value="{{ old('observaciones_propuestas.'.$clinica->id, $ficha?->remisiones->firstWhere('clinica_id', $clinica->id)?->observacion_propuesta) }}">
                    </div>
                    @endforeach
                </div>
                <span class="form-error" data-error-for="clinica_ids"></span>
            </fieldset>
            <div class="form-group">
                <label for="adm-examen">Examen complementario requerido</label>
                <textarea id="adm-examen" name="examen" rows="2" maxlength="10000" class="form-control">{{ old('examen', $ficha?->examen) }}</textarea>
                <span class="form-error" data-error-for="examen"></span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Declaración y firma</h2>
        </div>
        <div class="card-body">
            <p>Declaro que he contestado las preguntas con honestidad y en base a mi conocimiento. Acepto ser derivado(a) a la especialidad que corresponde.</p>
            <div class="checkbox-grid">
                @foreach(['consentimiento_datos' => 'Consiento el tratamiento de mis datos para la atención clínica', 'consentimiento_fotografias' => 'Autorizo el uso de fotografías con fines didácticos', 'consentimiento_investigacion' => 'Autorizo el uso de datos con fines de investigación'] as $campo => $etiqueta)
                <label class="checkbox-item"><input type="checkbox" name="{{ $campo }}" value="1" @checked(old($campo, $ficha?->$campo))> <span>{{ $etiqueta }}</span></label>
                @endforeach
            </div>
            <label>Firma del paciente o responsable</label>
            <x-firma-digital nombre="firma" id="adm-firma" :valor="old('firma', $ficha?->firma_imagen)" />
            <span class="form-error" data-error-for="firma"></span>
        </div>
    </div>
    <div id="formAdmisionError" class="alert alert-danger" role="alert" style="display:none"><span id="formAdmisionErrorText"></span></div>
    <div class="form-actions">
        <a href="{{ route('admisiones.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btnGuardarAdmision" disabled>{{ isset($admision) ? 'Actualizar admisión' : 'Registrar admisión' }}</button>
    </div>
</form>
@endsection

@push('styles')
<style>
    .admision-view-switcher {
        display: inline-grid;
        grid-template-columns: repeat(2, minmax(120px, 1fr));
        gap: 4px;
        padding: 4px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--surface-soft);
    }

    .admision-view-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 8px 14px;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: var(--muted);
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background var(--transition-fast), color var(--transition-fast), box-shadow var(--transition-fast);
    }

    .admision-view-tab span {
        display: grid;
        min-width: 30px;
        height: 26px;
        place-items: center;
        border-radius: 7px;
        background: color-mix(in srgb, currentColor 10%, transparent);
        font-size: 11px;
        font-weight: 800;
    }

    .admision-view-tab[aria-selected="true"] {
        background: var(--surface);
        color: var(--teal-dark);
        box-shadow: 0 2px 8px color-mix(in srgb, var(--ink) 10%, transparent);
    }

    .admision-view-tab:focus-visible {
        outline: none;
        box-shadow: var(--focus-ring);
    }

    .admision-view-help {
        margin: 10px 0 18px;
        color: var(--muted);
        font-size: 12px;
    }

    .admision-view-panel[hidden] {
        display: none;
    }

    @media (max-width: 520px) {
        .admision-view-switcher {
            display: grid;
            width: 100%;
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script type="module">
    import {
        inicializarFormularioAdmision
    } from @json(asset('js/admision-autocompletado.js'));

    const cambiarVistaOdontograma = (vista, enfocar = false) => {
        const botones = [...document.querySelectorAll('[data-admision-odontograma-vista]')];
        botones.forEach((boton) => {
            const activo = boton.dataset.admisionOdontogramaVista === vista;
            boton.setAttribute('aria-selected', String(activo));
            boton.tabIndex = activo ? 0 : -1;
        });

        ['2d', '3d'].forEach((tipo) => {
            const panel = document.getElementById(`adm-odontograma-vista-${tipo}`);
            if (panel) panel.hidden = tipo !== vista;
        });

        if (vista === '3d') window.dispatchEvent(new Event('resize'));
        if (enfocar) botones.find((boton) => boton.dataset.admisionOdontogramaVista === vista)?.focus();
    };

    document.querySelectorAll('[data-admision-odontograma-vista]').forEach((boton) => {
        boton.addEventListener('click', () => cambiarVistaOdontograma(boton.dataset.admisionOdontogramaVista));
        boton.addEventListener('keydown', (evento) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(evento.key)) return;
            evento.preventDefault();
            cambiarVistaOdontograma(boton.dataset.admisionOdontogramaVista === '2d' ? '3d' : '2d', true);
        });
    });

    const inicializar = () => {
        const form = document.getElementById('formAdmision');
        window.Odontograma?.initTodo(form);
        window.FirmaDigital?.initTodo(form);
        inicializarFormularioAdmision({
            form,
            odontograma: window.Odontograma,
            firma: window.FirmaDigital,
            config: {
                modo: @json(isset($admision) ? 'editar' : 'crear'),
                preservarInicial: @json(session()->hasOldInput()),
                campos: @json($camposAutocompletar),
                urls: {
                    buscarPacientes: @json(route('admisiones.buscar-pacientes')),
                    datosPaciente: @json(route('admisiones.buscar-pacientes.datos', ['paciente' => '__ID__'])),
                    ficha: @json(route('admisiones.show', ['admision' => '__ID__'])),
                    reporte: @json(route('admisiones.reporte-paciente', ['admision' => '__ID__'])),
                    historia: @json(route('historias.show', ['historia' => '__ID__'])),
                },
            },
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar, {
            once: true
        });
    } else {
        inicializar();
    }
</script>
@endpush
