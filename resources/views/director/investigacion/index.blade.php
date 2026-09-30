@extends('layouts.app')

@section('title', 'Investigación')
@section('page-title', 'Datos para investigación')
@section('page-subtitle', 'Estadísticas clínicas anonimizadas')

@section('content')
<section class="card">
    <div class="card-header">
        <h2>Registros anonimizados</h2>
        <span class="card-meta">No se muestran nombres ni documentos de identidad</span>
    </div>
    <div class="card-body form-grid">
        <div class="form-group">
            <label for="filtroClinica">ID de clínica</label>
            <input class="form-control" id="filtroClinica" type="number" min="1">
        </div>
        <div class="form-group">
            <label for="filtroPeriodo">ID de periodo</label>
            <input class="form-control" id="filtroPeriodo" type="number" min="1">
        </div>
        <div class="form-group">
            <label for="filtroGenero">Género</label>
            <input class="form-control" id="filtroGenero" type="text">
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="button" id="btnFiltrarInvestigacion">Filtrar</button>
            <button class="btn btn-secondary" type="button" id="btnLimpiarInvestigacion">Limpiar</button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Género</th>
                    <th>Grupo etario</th>
                    <th>Especialidad</th>
                    <th>Periodo</th>
                    <th>Gestión</th>
                    <th>Semestre</th>
                    <th>Motivo de consulta</th>
                    <th>Estado</th>
                    <th>Autorizada</th>
                </tr>
            </thead>
            <tbody id="tbodyInvestigacion">
                <tr>
                    <td colspan="9">Cargando datos...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const URL_DATOS_INVESTIGACION = "{{ route('director.investigacion.datos.anonimizados') }}";

    async function cargarInvestigacion() {
        const params = new URLSearchParams();
        const filtros = {
            clinica_id: 'filtroClinica',
            periodo_id: 'filtroPeriodo',
            genero: 'filtroGenero',
        };

        Object.entries(filtros).forEach(([key, id]) => {
            const value = document.getElementById(id).value.trim();
            if (value) params.set(key, value);
        });

        const {
            ok,
            data
        } = await window.fetchAjax(`${URL_DATOS_INVESTIGACION}?${params}`);
        if (!ok || !Array.isArray(data)) {
            window.mostrarToast('error', 'No se pudieron cargar los datos anonimizados.');
            return;
        }

        const columns = ['genero', 'grupo_etario', 'especialidad', 'periodo', 'gestion', 'semestre', 'motivo_consulta', 'estado', 'autorizado'];
        const body = document.getElementById('tbodyInvestigacion');
        body.replaceChildren();

        if (!data.length) {
            const row = body.insertRow();
            const cell = row.insertCell();
            cell.colSpan = columns.length;
            cell.textContent = 'No hay datos para los filtros seleccionados.';
            return;
        }

        data.forEach((record) => {
            const row = body.insertRow();
            columns.forEach((column) => {
                const cell = row.insertCell();
                cell.textContent = column === 'autorizado' ?
                    (record[column] ? 'Sí' : 'No') :
                    (record[column] ?? '—');
            });
        });
    }

    document.getElementById('btnFiltrarInvestigacion').addEventListener('click', cargarInvestigacion);
    document.getElementById('btnLimpiarInvestigacion').addEventListener('click', () => {
        ['filtroClinica', 'filtroPeriodo', 'filtroGenero'].forEach((id) => {
            document.getElementById(id).value = '';
        });
        cargarInvestigacion();
    });
    document.addEventListener('DOMContentLoaded', cargarInvestigacion);
</script>
@endpush