<div class="seguimiento-summary">
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Historia</span><span class="seguimiento-summary__value">#{{ $registro->id }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Paciente</span><span class="seguimiento-summary__value">{{ $registro->paciente?->nombre_completo }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Archivos</span><span class="seguimiento-summary__value">{{ $registro->archivos->count() }}</span></div>
    <div class="seguimiento-summary__item"><span class="seguimiento-summary__label">Estado</span><span class="seguimiento-summary__value">{{ $cerrada ? 'Solo consulta' : 'Disponible' }}</span></div>
</div>

@if($cerrada)
    <div class="alert alert-warning">La historia est&aacute; cerrada. Los archivos permanecen disponibles para consulta.</div>
@endif

@if($puedeEditarArchivos)
    <details class="seguimiento-compose">
        <summary>+ Adjuntar un nuevo archivo</summary>
        <form action="{{ route('historias.archivos.subir', $registro->id) }}" method="POST" enctype="multipart/form-data" class="form-grid" data-seguimiento-form>
            @csrf
            <div class="form-group">
                <label for="seguimiento-archivo-{{ $registro->id }}">Documento</label>
                <input id="seguimiento-archivo-{{ $registro->id }}" name="archivo" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <small>PDF, JPG o PNG. M&aacute;ximo 10 MB.</small>
            </div>
            <div><button type="submit" class="btn btn-primary">Subir archivo</button></div>
        </form>
    </details>
@endif

<div class="table-wrap seguimiento-table-wrap">
    <table class="seguimiento-table">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Formato</th>
                <th>Tama&ntilde;o</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registro->archivos as $archivo)
                <tr>
                    <td>
                        <div class="seguimiento-file">
                            <span class="seguimiento-file__preview">
                                @if(in_array($archivo->extension, ['jpg', 'jpeg', 'png'], true))
                                    <img src="{{ route('historias.archivos.descargar', ['id' => $registro->id, 'archivo' => $archivo->id]) }}" alt="" loading="lazy">
                                @else
                                    PDF
                                @endif
                            </span>
                            <span>
                                <span class="seguimiento-table__primary">{{ $archivo->nombre_original }}</span>
                                <span class="seguimiento-table__secondary">Documento #{{ $archivo->id }}</span>
                            </span>
                        </div>
                    </td>
                    <td><span class="badge badge-muted">{{ strtoupper($archivo->extension) }}</span></td>
                    <td class="table-value">
                        @if($archivo->tamano_bytes >= 1048576)
                            {{ number_format($archivo->tamano_bytes / 1048576, 2) }} MB
                        @elseif($archivo->tamano_bytes >= 1024)
                            {{ number_format($archivo->tamano_bytes / 1024, 1) }} KB
                        @else
                            {{ $archivo->tamano_bytes }} B
                        @endif
                    </td>
                    <td>{{ $archivo->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="seguimiento-actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('historias.archivos.descargar', ['id' => $registro->id, 'archivo' => $archivo->id]) }}" target="_blank" rel="noopener">Ver</a>
                            @if($puedeEditarArchivos)
                                <form action="{{ route('historias.archivos.eliminar', ['id' => $registro->id, 'archivo' => $archivo->id]) }}" method="POST" data-seguimiento-form data-confirm="&iquest;Eliminar el archivo {{ $archivo->nombre_original }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="seguimiento-empty-cell">Esta historia no tiene archivos adjuntos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
