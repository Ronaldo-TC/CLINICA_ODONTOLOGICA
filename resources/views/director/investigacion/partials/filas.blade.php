@forelse ($data as $registro)
<tr>
    <td>{{ $registro->genero ?? '—' }}</td>
    <td>{{ $registro->grupo_etario ?? '—' }}</td>
    <td>{{ $registro->especialidad ?? '—' }}</td>
    <td>{{ $registro->periodo ?? '—' }}</td>
    <td>{{ $registro->gestion ?? '—' }}</td>
    <td>{{ $registro->semestre ?? '—' }}</td>
    <td>{{ $registro->motivo_consulta ?? '—' }}</td>
    <td>{{ $registro->estado ?? '—' }}</td>
    <td>{{ $registro->autorizado ? 'Sí' : 'No' }}</td>
</tr>
@empty
<tr>
    <td colspan="9">
        <div class="empty-state">No hay datos para los filtros seleccionados.</div>
    </td>
</tr>
@endforelse