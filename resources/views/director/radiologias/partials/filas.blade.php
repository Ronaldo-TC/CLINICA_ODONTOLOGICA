@forelse ($radiologias as $radiologia)
<tr>
    <td>{{ $radiologia->id }}</td>
    <td>{{ $radiologia->paciente?->nombre_completo ?? '—' }}</td>
    <td>{{ $radiologia->usuario?->nombre_completo ?? '—' }}</td>
    <td>{{ $radiologia->fecha_toma?->format('d/m/Y H:i') ?? '—' }}</td>
    <td>{{ $radiologia->estado ?? '—' }}</td>
    <td>{{ $radiologia->descripcion ?? '—' }}</td>
    <td>{{ $radiologia->observacion ?? '—' }}</td>
</tr>
@empty
<tr>
    <td colspan="7">
        <div class="empty-state">No hay radiologías para los filtros seleccionados.</div>
    </td>
</tr>
@endforelse