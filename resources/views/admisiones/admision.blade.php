@extends('layouts.app')
@section('title', isset($admision) ? 'Editar admisión' : (($odontograma3d ?? false) ? 'Nueva admisión 3D' : 'Nueva admisión'))
@section('page-title', isset($admision) ? 'Editar admisión' : (($odontograma3d ?? false) ? 'Nueva admisión 3D' : 'Nueva admisión'))
@section('page-subtitle', 'Ficha de evaluación inicial del paciente')

@section('content')
@php
    $ficha = $admision ?? null;
    $seleccionadas = old('clinica_ids', $ficha?->remisiones->where('habilitado', true)->pluck('clinica_id')->all() ?? []);
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
        <div class="card-header"><h2>{{ isset($admision) ? 'Ficha de admisión #'.$admision->id : (($odontograma3d ?? false) ? 'Nueva admisión 3D' : 'Nueva Admisión') }}</h2></div>
        <div class="card-body form-grid">
            <div class="form-group" style="grid-column:1/-1">
                <label for="adm-paciente-buscar">Paciente <span class="req">*</span></label>
                <div class="autocomplete-wrap {{ $ficha?->paciente_id ? 'has-value' : '' }}" id="wrapPaciente">
                    <input type="hidden" name="paciente_id" id="adm-paciente-id" value="{{ $ficha?->paciente_id ?? old('paciente_id') }}">
                    <input type="text" id="adm-paciente-buscar" class="form-control autocomplete-input" value="{{ $ficha?->paciente?->nombre_completo }}" placeholder="Buscar por nombre o CI..." autocomplete="off" required @readonly(isset($admision))>
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
                </div>
                <div id="cargaPacienteEstado" role="status" aria-live="polite" style="display:none; margin-top:10px;">
                    <span id="cargaPacienteMensaje"></span>
                    <button type="button" id="btnReintentarPaciente" class="btn btn-sm btn-secondary" hidden>Reintentar carga</button>
                </div>
                <span class="form-error" data-error-for="paciente_id"></span>
            </div>
            <div class="form-group">
                <label for="adm-periodo">Periodo</label>
                <select id="adm-periodo" name="periodo_id" class="form-control">
                    <option value="">Sin periodo</option>
                    @foreach($periodos as $periodo)
                        <option value="{{ $periodo->id }}" @selected((string) old('periodo_id', $ficha?->periodo_id) === (string) $periodo->id)>{{ $periodo->nombre }} ({{ $periodo->gestion }}-{{ $periodo->semestre }})</option>
                    @endforeach
                </select>
                <span class="form-error" data-error-for="periodo_id"></span>
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
        <div class="card-header"><h2>Motivo y antecedentes</h2></div>
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
        <div class="card-header"><h2>Estado físico general y signos vitales</h2></div>
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
        <div class="card-header"><h2>Examen extraoral</h2></div>
        <div class="card-body form-grid">
            @foreach(['asimetria_facial' => 'Asimetría facial', 'cambio_color' => 'Cambio de color', 'abultamiento' => 'Abultamiento', 'ganglios_palpables' => 'Ganglios cervicales palpables', 'ruidos_articulares' => 'ATM · Ruidos articulares'] as $campo => $etiqueta)
                @php($valor = old($campo, $ficha?->$campo))
                <div class="form-group">
                    <label for="adm-{{ $campo }}">{{ $etiqueta }}</label>
                    <select id="adm-{{ $campo }}" name="{{ $campo }}" class="form-control">
                        <option value="">Sin registrar</option>
                        <option value="1" @selected($valor !== null && $valor !== '' && (bool) $valor)>Sí</option>
                        <option value="0" @selected($valor !== null && $valor !== '' && ! (bool) $valor)>No</option>
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
        <div class="card-header"><h2>Examen intraoral</h2></div>
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
        <div class="card-header"><h2>Odontograma</h2></div>
        <div class="card-body">
            @if($odontograma3d ?? false)
                @include('admisiones.partials.odontograma-3d')
                <details id="adm-odontograma-alternativo">
                    <summary>Ver o editar también en 2D</summary>
            @endif
            <x-odontograma nombre="odontograma_pintado" id="adm-odontograma" :valor="old('odontograma_pintado', $ficha?->odontograma_pintado)" :denticion="old('odontograma_denticion', $ficha?->odontograma_denticion ?? 'adulto')" />
            @if($odontograma3d ?? false)
                </details>
            @endif
            <div class="form-group" style="margin-top:18px;">
                <label for="adm-odontograma-observaciones">Observaciones del odontograma</label>
                <textarea id="adm-odontograma-observaciones" name="odontograma" class="form-control" rows="2" maxlength="10000">{{ old('odontograma', $ficha?->odontograma) }}</textarea>
                <span class="form-error" data-error-for="odontograma"></span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2>Diagnóstico y derivación</h2></div>
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
                        <label class="checkbox-item"><input type="checkbox" name="clinica_ids[]" value="{{ $clinica->id }}" @checked(in_array($clinica->id, $seleccionadas))> <span>{{ $clinica->especialidad }}</span></label>
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
        <div class="card-header"><h2>Declaración y firma</h2></div>
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

@push('scripts')
<script type="module">
    import { inicializarFormularioAdmision } from @json(asset('js/admision-autocompletado.js'));

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
                },
            },
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar, { once: true });
    } else {
        inicializar();
    }
</script>
@endpush
