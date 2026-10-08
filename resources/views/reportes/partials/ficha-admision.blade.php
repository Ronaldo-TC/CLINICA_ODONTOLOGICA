<article class="report-admission">
    <h2>Ficha de admisión #{{ $ficha->id }}</h2>
    <p class="muted">Fecha: {{ $ficha->fecha_admision?->format('d/m/Y') ?? $ficha->created_at?->format('d/m/Y') }} · Periodo: {{ $ficha->periodo?->nombre ?? 'Sin periodo' }} · Estado: {{ ucfirst(str_replace('_', ' ', (string) $ficha->estado)) }}</p>
    <h3>Contacto y datos complementarios</h3>
    <dl class="report-grid">
        @foreach(['telefono' => 'Teléfono', 'estado_civil' => 'Estado civil', 'ocupacion' => 'Ocupación', 'direccion' => 'Dirección', 'emergencia' => 'Contacto de emergencia', 'telefono_emergencia' => 'Teléfono de emergencia'] as $campo => $etiqueta)
            <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo ?: 'Sin registrar' }}</dd></div>
        @endforeach
    </dl>
    <h3>Motivo de consulta</h3>
    <p>{{ $ficha->motivo_consulta ?: $ficha->motivoConsulta?->nombre ?: 'Sin registrar' }}</p>
    <h3>Historia de la enfermedad actual</h3>
    <p>{{ $ficha->historia_enfermedad_actual ?: 'Sin registrar' }}</p>
    <h3>Antecedentes patológicos personales</h3>
    <p>{{ $ficha->antecedentes ?: 'Sin registrar' }}</p>
    @if($ficha->gmh)<p><strong>Historia médica general:</strong> {{ $ficha->gmh }}</p>@endif
    <h3>Estado físico general y signos vitales</h3>
    <dl class="report-grid">
        <div class="report-field"><dt>Estado físico general</dt><dd>{{ $ficha->gpe ?: 'Sin registrar' }}</dd></div>
        @foreach(['peso_kg' => ['Peso', 'kg'], 'talla_mts' => ['Talla', 'm'], 'temperatura_c' => ['Temperatura', '°C'], 'pulso' => ['Pulso', 'lat/min'], 'presion_arterial' => ['Presión arterial', 'mmHg'], 'frecuencia_respiratoria' => ['Frecuencia respiratoria', 'ciclos/min']] as $campo => [$etiqueta, $unidad])
            <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ filled($ficha->$campo) ? $ficha->$campo.' '.$unidad : 'Sin registrar' }}</dd></div>
        @endforeach
    </dl>
    @if($ficha->signos_vitales)<p><strong>Observaciones de signos vitales:</strong> {{ $ficha->signos_vitales }}</p>@endif
    <h3>Examen extraoral</h3>
    <dl class="report-grid">
        @foreach(['asimetria_facial' => 'Asimetría facial', 'cambio_color' => 'Cambio de color', 'abultamiento' => 'Abultamiento', 'ganglios_palpables' => 'Ganglios cervicales palpables', 'ruidos_articulares' => 'ATM · Ruidos articulares'] as $campo => $etiqueta)
            <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo === null ? 'Sin registrar' : ($ficha->$campo ? 'Sí' : 'No') }}</dd></div>
        @endforeach
    </dl>
    @foreach(['ee' => 'Observaciones extraorales', 'cn' => 'Observaciones de ganglios cervicales', 'atm' => 'Observaciones de ATM'] as $campo => $etiqueta)
        @if(filled($ficha->$campo))<p><strong>{{ $etiqueta }}:</strong> {{ $ficha->$campo }}</p>@endif
    @endforeach
    <h3>Examen intraoral</h3>
    <dl class="report-grid">
        @foreach(['bm_estado' => 'Mucosa bucal', 'gm_estado' => 'Mucosa gingival', 'calculo' => 'Cálculos', 'oclusion' => 'Oclusión'] as $campo => $etiqueta)
            <div class="report-field"><dt>{{ $etiqueta }}</dt><dd>{{ $ficha->$campo ?: 'Sin registrar' }}</dd></div>
        @endforeach
    </dl>
    @if($ficha->bm)<p><strong>Observaciones de mucosa bucal:</strong> {{ $ficha->bm }}</p>@endif
    @if($ficha->gm)<p><strong>Observaciones de mucosa gingival:</strong> {{ $ficha->gm }}</p>@endif
    <h3>Odontograma · {{ ucfirst($ficha->odontograma_denticion ?? 'adulto') }}</h3>
    <x-odontograma :valor="$ficha->odontograma_pintado" :denticion="$ficha->odontograma_denticion ?? 'adulto'" :lectura="true" :mostrar-leyenda="false" :id="'ficha-odontograma-'.$ficha->id" :imagenes="$imagenesOdontograma ?? []" />
    @isset($hallazgosFicha)
        <table><thead><tr><th>Pieza FDI</th><th>Superficie / marca</th><th>Condición registrada</th></tr></thead><tbody>
            @forelse($hallazgosFicha as [$pieza, $cara, $condicion])
                <tr><td>{{ $pieza }}</td><td>{{ $cara }}</td><td>{{ $condicion }}</td></tr>
            @empty
                <tr><td colspan="3">Sin hallazgos registrados en el odontograma.</td></tr>
            @endforelse
        </tbody></table>
    @endisset
    @if($ficha->pieza_dental)<p><strong>Pieza(s) de referencia:</strong> {{ $ficha->pieza_dental }}</p>@endif
    <h3>Diagnóstico(s) presuntivo(s)</h3>
    <p>{{ $ficha->diagnostico ?: 'Sin registrar' }}</p>
    <h3>Área(s) derivada(s) · Clínicas</h3>
    @php($areas = $ficha->remisiones->where('habilitado', true)->pluck('clinica.especialidad')->filter()->unique()->implode(', '))
    <p>{{ $areas ?: $ficha->area_derivada ?: 'Sin áreas derivadas registradas.' }}</p>
    <h3>Examen complementario requerido</h3>
    <p>{{ $ficha->examen ?: 'Sin registrar' }}</p>
    <h3>Declaración y consentimientos</h3>
    <p>Declaro que he contestado las preguntas con honestidad y en base a mi conocimiento. Acepto ser derivado(a) a la especialidad que corresponde.</p>
    <p>Uso de datos: {{ $ficha->consentimiento_datos ? 'Sí' : 'No' }} · Fotografías: {{ $ficha->consentimiento_fotografias ? 'Sí' : 'No' }} · Investigación: {{ $ficha->consentimiento_investigacion ? 'Sí' : 'No' }}</p>
    <div class="report-signatures">
        <div><strong>Firma del paciente o responsable</strong>
            @if($ficha->firma_imagen)<img src="{{ $ficha->firma_imagen }}" alt="Firma del paciente o responsable" style="max-width:220px;width:100%;height:65px;object-fit:contain;display:block">@else<p>Sin firma registrada.</p>@endif
        </div>
        <div><strong>Registro de la ficha</strong><p>{{ $ficha->registradoPor?->nombre_completo ?? $ficha->estudiante?->nombre_completo ?? 'Sin registrar' }}</p>
            @if($ficha->estudiante)<p>Estudiante: {{ $ficha->estudiante->nombre_completo }}</p>@endif
            @if($ficha->responsable)<p>Responsable: {{ $ficha->responsable->nombre_completo }}</p>@endif
            <p>{{ $ficha->fecha_autorizacion ? 'Ficha autorizada el '.$ficha->fecha_autorizacion->format('d/m/Y H:i') : 'Ficha pendiente de autorización' }}</p>
        </div>
    </div>
</article>