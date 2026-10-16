@extends('layouts.app')
@section('title', 'Reposición de insumos')
@section('content')
<div class="card">
    <div class="card-header"><h2>Reposición de insumos</h2><span id="reposicion-total">{{ $registros->total() }} solicitudes</span></div>
    <div class="card-body">
        <p>Jefatura valida la necesidad, Dirección autoriza el abastecimiento y el encargado registra la recepción. El stock aumenta al recibir el material.</p>
        <label for="reposicion-estado">Estado</label>
        <select id="reposicion-estado" class="form-control">
            <option value="">Todos</option>
            @foreach(['pendiente' => 'Pendiente de Jefatura', 'autorizado' => 'Pendiente de Dirección', 'aprobado' => 'Pendiente de recepción', 'parcial' => 'Recepción parcial', 'recibido' => 'Recibida', 'rechazado' => 'Rechazada'] as $estado => $texto)
                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Solicitud</th><th>Materiales</th><th>Estado</th><th>Aprobaciones</th><th>Acciones</th></tr></thead>
        <tbody id="reposicion-filas">@include('insumos.partials.reposiciones', ['registros' => $registros])</tbody>
    </table></div>
    <div id="reposicion-paginacion">{{ $registros->links() }}</div>
</div>
@if(auth()->user()->hasAnyRole(['insumos', 'admin']))
<div class="card">
    <div class="card-header"><h2>Nueva solicitud de reposición</h2></div>
    <form action="{{ route('insumos.reposiciones-crear') }}" method="POST" data-reposicion-form class="card-body">
        @csrf
        <div class="form-group"><label for="reposicion-justificacion">Justificación</label><textarea id="reposicion-justificacion" name="observaciones" class="form-control" minlength="10" maxlength="2000" required></textarea></div>
        <div id="reposicion-materiales"></div>
        <template id="reposicion-material-template">
            <div class="form-grid" data-material>
                <div class="form-group"><label>Insumo y clínica</label><select class="form-control" data-insumo required>
                    <option value="">Seleccione un insumo</option>
                    @foreach($insumos as $insumo)
                        <option value="{{ $insumo->id }}">{{ $insumo->nombre }} · {{ $insumo->clinica?->especialidad }} · stock {{ $insumo->cantidad_numerica }} / mínimo {{ $insumo->stock_minimo }}</option>
                    @endforeach
                </select></div>
                <div class="form-group"><label>Cantidad solicitada</label><input data-cantidad class="form-control" type="number" min="0.01" step="0.01" max="99999999.99" required></div>
                <button type="button" class="btn btn-secondary" data-quitar>Quitar material</button>
            </div>
        </template>
        <div class="actions"><button type="button" class="btn btn-secondary" id="reposicion-agregar">Agregar material</button><button class="btn btn-primary" type="submit">Enviar a Jefatura y Dirección</button></div>
    </form>
</div>
@endif
<p id="reposicion-error" class="form-error" role="alert"></p>
@endsection
@push('scripts')
<script>
(() => {
    const filas = document.getElementById('reposicion-filas');
    const paginacion = document.getElementById('reposicion-paginacion');
    const estado = document.getElementById('reposicion-estado');
    const error = document.getElementById('reposicion-error');
    const materiales = document.getElementById('reposicion-materiales');
    let ultimoUrl = @json(route('insumos.reposiciones-tabla', request()->only('estado', 'page')));
    let carga = null;
    async function cargar(url = ultimoUrl) {
        carga?.abort();
        carga = new AbortController();
        try {
            const respuesta = await fetch(url, {signal: carga.signal, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const datos = await respuesta.json();
            if (!respuesta.ok) throw new Error(datos.message || 'No se pudo actualizar la lista.');
            filas.innerHTML = datos.filas;
            paginacion.innerHTML = datos.paginacion;
            document.getElementById('reposicion-total').textContent = `${datos.total} solicitudes`;
            ultimoUrl = url;
        } catch (e) { if (e.name !== 'AbortError') error.textContent = e.message; }
    }
    function agregar() {
        materiales.append(document.getElementById('reposicion-material-template').content.cloneNode(true));
    }
    document.getElementById('reposicion-agregar')?.addEventListener('click', agregar);
    if (materiales) agregar();
    materiales?.addEventListener('click', e => {
        if (e.target.closest('[data-quitar]') && materiales.children.length > 1) e.target.closest('[data-material]').remove();
    });
    estado.addEventListener('change', () => {
        const url = new URL(@json(route('insumos.reposiciones-tabla')));
        if (estado.value) url.searchParams.set('estado', estado.value);
        cargar(url.toString());
    });
    paginacion.addEventListener('click', e => {
        const enlace = e.target.closest('a[href]');
        if (!enlace) return;
        e.preventDefault();
        const url = new URL(enlace.href);
        url.pathname = new URL(@json(route('insumos.reposiciones-tabla'))).pathname;
        cargar(url.toString());
    });
    document.addEventListener('submit', async e => {
        const formulario = e.target.closest('[data-reposicion-form]');
        if (!formulario) return;
        e.preventDefault();
        error.textContent = '';
        const boton = e.submitter;
        if (boton?.disabled) return;
        if (boton) boton.disabled = true;
        const datos = new FormData(formulario);
        if (formulario.contains(materiales)) {
            [...materiales.querySelectorAll('[data-material]')].forEach((fila, i) => {
                datos.append(`detalles[${i}][insumo_id]`, fila.querySelector('[data-insumo]').value);
                datos.append(`detalles[${i}][cantidad_solicitada]`, fila.querySelector('[data-cantidad]').value);
            });
        }
        try {
            const respuesta = await fetch(formulario.action, {method: 'POST', body: datos, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const resultado = await respuesta.json();
            if (!respuesta.ok) throw new Error(Object.values(resultado.errors || {}).flat().join(' ') || resultado.message || 'No se pudo guardar.');
            window.mostrarToast?.('success', resultado.message);
            if (formulario.contains(materiales)) { formulario.reset(); materiales.innerHTML = ''; agregar(); }
            await cargar();
        } catch (e) { error.textContent = e.message; }
        finally { if (boton) boton.disabled = false; }
    });
})();
</script>
@endpush
