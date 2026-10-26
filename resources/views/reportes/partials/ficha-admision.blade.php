<article class="report-admission">
    <header class="admission-heading">
        <h2>Ficha de admisión #{{ $ficha->id }}</h2>
        <p>{{ $ficha->fecha_admision?->format('d/m/Y') ?? $ficha->created_at?->format('d/m/Y') }} · {{ ucfirst(str_replace('_', ' ', (string) $ficha->estado)) }}</p>
    </header>

    <div class="admission-body">
        <section class="admission-block">
            <h3 class="admission-block-title">Contacto y datos complementarios</h3>
            <dl class="report-grid compact-facts">
                @foreach(['telefono' => 'Teléfono', 'estado_civil' => 'Estado civil', 'ocupacion' => 'Ocupación', 'direccion' => 'Dirección', 'emergencia' => 'Contacto de emergencia', 'telefono_emergencia' => 'Teléfono de emergencia'] as $campo => $etiqueta)
                    <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo ?: 'Sin registrar' }}</dd></div>
                @endforeach
            </dl>
        </section>

        <div class="clinical-columns">
            <section class="clinical-card">
                <h3>Motivo e historia de la enfermedad actual</h3>
                <p><span class="clinical-label">Motivo de consulta:</span> {{ $ficha->motivo_consulta ?: $ficha->motivoConsulta?->nombre ?: 'Sin registrar' }}</p>
                <p><span class="clinical-label">Historia de la enfermedad actual:</span> {{ $ficha->historia_enfermedad_actual ?: 'Sin registrar' }}</p>
            </section>
            <section class="clinical-card">
                <h3>Antecedentes clínicos</h3>
                <p><span class="clinical-label">Antecedentes patológicos personales:</span> {{ $ficha->antecedentes ?: 'Sin registrar' }}</p>
                @if($ficha->gmh)<p><span class="clinical-label">Historia médica general:</span> {{ $ficha->gmh }}</p>@endif
            </section>
        </div>

        <section class="admission-block">
            <h3 class="admission-block-title">Estado físico general y signos vitales</h3>
            <dl class="report-grid compact-facts">
                <div class="report-field"><dt>Estado físico general</dt><dd>{{ $ficha->gpe ?: 'Sin registrar' }}</dd></div>
                @foreach(['peso_kg' => ['Peso', 'kg'], 'talla_mts' => ['Talla', 'm'], 'temperatura_c' => ['Temperatura', '°C'], 'pulso' => ['Pulso', 'lat/min'], 'presion_arterial' => ['Presión arterial', 'mmHg'], 'frecuencia_respiratoria' => ['Frecuencia respiratoria', 'ciclos/min']] as $campo => [$etiqueta, $unidad])
                    <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ filled($ficha->$campo) ? $ficha->$campo.' '.$unidad : 'Sin registrar' }}</dd></div>
                @endforeach
            </dl>
            @if($ficha->signos_vitales)<p><span class="clinical-label">Observaciones:</span> {{ $ficha->signos_vitales }}</p>@endif
        </section>

        <div class="clinical-columns">
            <section class="clinical-card">
                <h3>Examen extraoral</h3>
                <dl class="report-grid compact-facts">
                    @foreach(['asimetria_facial' => 'Asimetría facial', 'cambio_color' => 'Cambio de color', 'abultamiento' => 'Abultamiento', 'ganglios_palpables' => 'Ganglios palpables', 'ruidos_articulares' => 'Ruidos ATM'] as $campo => $etiqueta)
                        <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo === null ? 'Sin registrar' : ($ficha->$campo ? 'Sí' : 'No') }}</dd></div>
                    @endforeach
                </dl>
                @foreach(['ee' => 'Observaciones extraorales', 'cn' => 'Ganglios cervicales', 'atm' => 'ATM'] as $campo => $etiqueta)
                    @if(filled($ficha->$campo))<p><span class="clinical-label">{{ $etiqueta }}:</span> {{ $ficha->$campo }}</p>@endif
                @endforeach
            </section>
            <section class="clinical-card">
                <h3>Examen intraoral</h3>
                <dl class="report-grid compact-facts">
                    @foreach(['bm_estado' => 'Mucosa bucal', 'gm_estado' => 'Mucosa gingival', 'calculo' => 'Cálculos', 'oclusion' => 'Oclusión'] as $campo => $etiqueta)
                        <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo ?: 'Sin registrar' }}</dd></div>
                    @endforeach
                </dl>
                @if($ficha->bm)<p><span class="clinical-label">Mucosa bucal:</span> {{ $ficha->bm }}</p>@endif
                @if($ficha->gm)<p><span class="clinical-label">Mucosa gingival:</span> {{ $ficha->gm }}</p>@endif
            </section>
        </div>

        @php
            $mostrarVisor3d = ($mostrar3d ?? false) && ! ($esPdf ?? false);
            $odontogramaComponenteId = 'ficha-odontograma-'.$ficha->id;
            $odontogramaPanel2dId = $odontogramaComponenteId.'-panel-2d';
            $odontogramaPanel3dId = $odontogramaComponenteId.'-panel-3d';
        @endphp
        <section class="odontogram-panel">
            <h3 class="admission-block-title">Odontograma · {{ ucfirst($ficha->odontograma_denticion ?? 'adulto') }}</h3>
            @if($mostrarVisor3d)
                <div class="ficha-odontograma-selector" role="tablist" aria-label="Tipo de visualización del odontograma">
                    <button type="button" role="tab" aria-selected="true" aria-controls="{{ $odontogramaPanel2dId }}" data-ficha-odontograma-tab="2d">
                        Vista 2D
                    </button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="{{ $odontogramaPanel3dId }}" data-ficha-odontograma-tab="3d">
                        Vista 3D
                    </button>
                </div>
                <div id="{{ $odontogramaPanel2dId }}" role="tabpanel" data-ficha-odontograma-panel="2d">
            @endif

            <x-odontograma :valor="$ficha->odontograma_pintado" :denticion="$ficha->odontograma_denticion ?? 'adulto'" :lectura="true" :mostrar-leyenda="false" :id="$odontogramaComponenteId" :imagenes="$imagenesOdontograma ?? []" :es-pdf="$esPdf ?? false" />

            @if($mostrarVisor3d)
                </div>
                <div
                    id="{{ $odontogramaPanel3dId }}"
                    class="ficha-odontograma-3d"
                    role="tabpanel"
                    data-ficha-odontograma-visor
                    data-source-id="{{ $odontogramaComponenteId }}"
                    data-model-url="{{ asset('models/odontograma.glb') }}"
                    hidden
                >
                    <div class="ficha-odontograma-3d-toolbar" aria-label="Controles del odontograma 3D">
                        <label>
                            Dentición visible
                            <select data-ficha-3d-denticion>
                                <option value="adulto" @selected(($ficha->odontograma_denticion ?? 'adulto') === 'adulto')>Adulta</option>
                                <option value="infantil" @selected(($ficha->odontograma_denticion ?? 'adulto') === 'infantil')>Infantil</option>
                                <option value="mixto" @selected(($ficha->odontograma_denticion ?? 'adulto') === 'mixto')>Mixta</option>
                            </select>
                        </label>
                        <label>
                            Ángulo
                            <select data-ficha-3d-vista>
                                <option value="perspectiva">Perspectiva</option>
                                <option value="frontal">Frontal</option>
                                <option value="superior">Desde arriba</option>
                                <option value="inferior">Desde abajo</option>
                                <option value="derecha">Lateral derecha</option>
                                <option value="izquierda">Lateral izquierda</option>
                                <option value="posterior">Posterior</option>
                            </select>
                        </label>
                        <label class="ficha-odontograma-check"><input type="checkbox" data-ficha-3d-encias checked> Encías</label>
                        <label class="ficha-odontograma-check"><input type="checkbox" data-ficha-3d-raices> Raíces</label>
                        <label class="ficha-odontograma-check"><input type="checkbox" data-ficha-3d-huesos> Hueso</label>
                        <label class="ficha-odontograma-check"><input type="checkbox" data-ficha-3d-giro> Giro automático</label>
                        <button type="button" class="btn btn-secondary" data-ficha-3d-centrar>Centrar</button>
                        <button type="button" class="btn btn-secondary" data-ficha-3d-pantalla>Pantalla completa</button>
                    </div>
                    <div class="ficha-odontograma-3d-stage">
                        <canvas data-ficha-3d-canvas aria-label="Modelo dental tridimensional de solo lectura"></canvas>
                        <p class="ficha-odontograma-3d-status" data-ficha-3d-status role="status" aria-live="polite">El modelo 3D se cargará al abrir esta vista.</p>
                    </div>
                    <p class="ficha-odontograma-3d-detalle" data-ficha-3d-detalle>Selecciona una pieza para consultar sus condiciones registradas.</p>
                </div>
            @endif
            @isset($hallazgosFicha)
                @if(count($hallazgosFicha))
                <table><thead><tr><th>Pieza FDI</th><th>Superficie / marca</th><th>Condición registrada</th></tr></thead><tbody>
                    @foreach($hallazgosFicha as [$pieza, $cara, $condicion])
                        <tr><td>{{ $pieza }}</td><td>{{ $cara }}</td><td>{{ $condicion }}</td></tr>
                    @endforeach
                </tbody></table>
                @else
                    <p class="patient-empty">Sin hallazgos registrados en el odontograma.</p>
                @endif
            @endisset
            @if($ficha->pieza_dental)<p><span class="clinical-label">Pieza(s) de referencia:</span> {{ $ficha->pieza_dental }}</p>@endif
        </section>

        <div class="clinical-columns">
            <section class="clinical-card">
                <h3>Diagnóstico y examen complementario</h3>
                <p><span class="clinical-label">Diagnóstico(s) presuntivo(s):</span> {{ $ficha->diagnostico ?: 'Sin registrar' }}</p>
                <p><span class="clinical-label">Examen requerido:</span> {{ $ficha->examen ?: 'Sin registrar' }}</p>
            </section>
            <section class="clinical-card">
                <h3>Área(s) derivada(s) · Clínicas</h3>
                @php($areas = $ficha->remisiones->where('habilitado', true)->pluck('clinica.especialidad')->filter()->unique()->implode(', '))
                <p>{{ $areas ?: $ficha->area_derivada ?: 'Sin áreas derivadas registradas.' }}</p>
            </section>
        </div>

        @if($soloFicha)
        <section class="admission-block">
            <h3 class="admission-block-title">Declaración, consentimientos y firmas</h3>
            <p class="consent-line">Declaro que he contestado las preguntas con honestidad y en base a mi conocimiento. Acepto ser derivado(a) a la especialidad que corresponde.</p>
            <p class="consent-line">Uso de datos: {{ $ficha->consentimiento_datos ? 'Sí' : 'No' }} · Fotografías: {{ $ficha->consentimiento_fotografias ? 'Sí' : 'No' }} · Investigación: {{ $ficha->consentimiento_investigacion ? 'Sí' : 'No' }}</p>
            <div class="report-signatures">
                <div><strong>Firma del paciente o responsable</strong>
                    @if($ficha->firma_imagen)<img src="{{ $ficha->firma_imagen }}" alt="Firma del paciente o responsable" style="max-width:220px;width:100%;height:52px;object-fit:contain;display:block">@else<p>Sin firma registrada.</p>@endif
                </div>
                <div><strong>Registro de la ficha</strong><p>{{ $ficha->registradoPor?->nombre_completo ?? $ficha->estudiante?->nombre_completo ?? 'Sin registrar' }}</p>
                    @if($ficha->estudiante)<p>Estudiante: {{ $ficha->estudiante->nombre_completo }}</p>@endif
                    @if($ficha->responsable)<p>Responsable: {{ $ficha->responsable->nombre_completo }}</p>@endif
                    <p>{{ $ficha->fecha_autorizacion ? 'Ficha autorizada el '.$ficha->fecha_autorizacion->format('d/m/Y H:i') : 'Ficha pendiente de autorización' }}</p>
                </div>
            </div>
        </section>
        @endif
    </div>
</article>
