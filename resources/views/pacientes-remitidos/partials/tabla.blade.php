{{-- ============================================================
     TABLA DE REMISIONES (usada por index y por AJAX)
     ============================================================ --}}
<div class="table-wrap" id="remitidos-tabla-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Paciente</th>
                <th>Especialidad destino</th>
                <th>Docente</th>
                <th>Estudiante</th>
                <th>Historias</th>
                <th>Insumos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @include('pacientes-remitidos.partials.filas', ['items' => $items])
        </tbody>
    </table>
</div>

<div id="remitidos-paginacion">
    {{ $items->withQueryString()->links() }}
</div>
