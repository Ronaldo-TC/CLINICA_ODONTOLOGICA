{{-- Filas anonimizadas: NUNCA datos identificables del paciente --}}
@forelse ($items as $fila)
<tr data-codigo="{{ $fila['codigo'] }}">
    <td><code>{{ $fila['codigo'] }}</code></td>

    <td>
        @if ($fila['edad'] !== null)
        {{ $fila['edad'] }} años
        @else
        —
        @endif
    </td>

    <td><span class="badge badge-muted">{{ $fila['genero'] }}</span></td>
    <td>{{ $fila['motivo'] }}</td>
    <td>{{ $fila['especialidad'] }}</td>
    <td>{{ $fila['tratamiento'] }}</td>
    <td><span class="badge badge-info">{{ $fila['periodo'] }}</span></td>
    <td><span class="badge badge-success">{{ $fila['estado'] }}</span></td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18" />
                <path d="M18 17V9" />
                <path d="M13 17V5" />
                <path d="M8 17v-3" />
            </svg>
            <p>No hay registros anonimizados con consentimiento de investigación</p>
        </div>
    </td>
</tr>
@endforelse
