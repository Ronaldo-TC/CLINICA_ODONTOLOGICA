@forelse ($esterilizaciones as $esterilizacion)
<tr>
    <td>{{ $esterilizacion->id }}</td>
    <td>{{ $esterilizacion->estudiante?->nombre_completo ?? '—' }}</td>
    <td>{{ $esterilizacion->clinica?->especialidad ?? '—' }}</td>
    <td>{{ $esterilizacion->cantidad_sobres }}</td>
    <td>{{ $esterilizacion->cantidad_paquetes }}</td>
    <td>{{ $esterilizacion->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
    <td>{{ $esterilizacion->fecha_recojo?->format('d/m/Y') ?? '—' }}</td>
    <td>{{ $esterilizacion->habilitada ? 'Habilitada' : 'No habilitada' }}</td>
    <td>{{ $esterilizacion->autorizaciones->count() }}</td>
</tr>
@empty
<tr>
    <td colspan="9">
        <div class="empty-state">No hay esterilizaciones para los filtros seleccionados.</div>
    </td>
</tr>
@endforelse