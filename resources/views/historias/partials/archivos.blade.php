{{--
    ============================================================
    ARCHIVOS ADJUNTOS DE LA HISTORIA CLÍNICA
    ============================================================
    Galería con vista previa (imágenes en línea, PDF embebido).
    Uso:
      @include('historias.partials.archivos', ['historiaId' => $historia->id])

    API pública: window.HistoriaArchivos.cargar(id)
--}}
@php
    $historiaId = $historiaId ?? ($registro->id ?? ($model->id ?? null));
@endphp

@if ($historiaId)
    <div class="archivos-panel" id="archivosPanel"
        data-historia="{{ $historiaId }}"
        data-url-listar="{{ route('historias.archivos', ['id' => $historiaId]) }}"
        data-url-subir="{{ route('historias.archivos.subir', ['id' => $historiaId]) }}"
        data-url-eliminar="{{ url('historias/' . $historiaId . '/archivos') }}"
        data-csrf="{{ csrf_token() }}">

        <div class="sol-items-head">
            <label style="margin:0;">Archivos adjuntos</label>
            @can('historias.editar')
                <label class="btn btn-sm btn-secondary" for="archivoInput" style="cursor:pointer;">
                    + Adjuntar archivo
                </label>
                <input type="file" id="archivoInput" name="archivo"
                    accept=".pdf,.jpg,.jpeg,.png" hidden>
            @endcan
        </div>

        <p class="odontograma-hint">PDF, JPG o PNG · máximo 10 MB por archivo.</p>

        <div id="archivosLista" class="archivos-lista" aria-live="polite">
            <p class="notif-cargando">Cargando archivos…</p>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                (() => {
                    'use strict';

                    const panel = document.getElementById('archivosPanel');
                    if (!panel) return;

                    const URL_LISTAR = panel.dataset.urlListar;
                    const URL_SUBIR = panel.dataset.urlSubir;
                    const URL_ELIMINAR = panel.dataset.urlEliminar;
                    const CSRF = panel.dataset.csrf;
                    const lista = document.getElementById('archivosLista');
                    const input = document.getElementById('archivoInput');

                    const escapar = (v) => (v ?? '').toString()
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

                    function pintar(archivos) {
                        if (!lista) return;

                        if (!archivos || archivos.length === 0) {
                            lista.innerHTML = `
                                <div class="empty-state">
                                    <p>Sin archivos adjuntos todavía.</p>
                                </div>`;
                            return;
                        }

                        lista.innerHTML = archivos.map((a) => {
                            let vista = '';

                            if (a.es_imagen) {
                                vista = `<img src="${escapar(a.url)}" alt="${escapar(a.nombre_original)}" loading="lazy">`;
                            } else if (a.es_pdf) {
                                vista = `<embed src="${escapar(a.url)}#toolbar=0" type="application/pdf">`;
                            } else {
                                vista = `<span class="archivo-icono">FILE</span>`;
                            }

                            return `
                                <figure class="archivo-item" data-id="${a.id}">
                                    <div class="archivo-preview">${vista}</div>
                                    <figcaption class="archivo-meta">
                                        <a href="${escapar(a.url)}" target="_blank" rel="noopener"
                                            class="archivo-nombre" title="${escapar(a.nombre_original)}">
                                            ${escapar(a.nombre_original)}
                                        </a>
                                        <span class="archivo-detalle">${escapar(a.tamano_legible)} · ${escapar(a.fecha || '')}</span>
                                        <button type="button" class="btn btn-sm btn-danger" data-eliminar="${a.id}">
                                            Eliminar
                                        </button>
                                    </figcaption>
                                </figure>`;
                        }).join('');
                    }

                    async function cargar() {
                        try {
                            const res = await fetch(URL_LISTAR, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) throw new Error();
                            const data = await res.json();
                            pintar(data.archivos || []);
                        } catch (err) {
                            if (lista) {
                                lista.innerHTML = '<div class="empty-state"><p>No se pudieron cargar los archivos.</p></div>';
                            }
                        }
                    }

                    input?.addEventListener('change', async () => {
                        const archivo = input.files?.[0];
                        if (!archivo) return;

                        const datos = new FormData();
                        datos.append('archivo', archivo);

                        try {
                            const res = await fetch(URL_SUBIR, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: datos,
                                credentials: 'same-origin',
                            });

                            const data = await res.json().catch(() => ({}));

                            if (!res.ok || !data.success) {
                                throw new Error(data.message || 'No se pudo subir el archivo.');
                            }

                            window.mostrarToast?.('success', data.message);
                            await cargar();
                        } catch (err) {
                            window.mostrarToast?.('error', err.message || 'Error al subir.');
                        } finally {
                            input.value = '';
                        }
                    });

                    lista?.addEventListener('click', async (e) => {
                        const boton = e.target.closest('[data-eliminar]');
                        if (!boton) return;
                        const nombreArchivo = boton.closest('.archivo-item')?.querySelector('.archivo-nombre')?.textContent.trim();
                        if (!await window.confirmarEliminacion(nombreArchivo || 'Archivo seleccionado')) return;

                        const id = boton.dataset.eliminar;

                        try {
                            const res = await fetch(`${URL_ELIMINAR}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            const data = await res.json();
                            if (!res.ok || !data.success) throw new Error(data.message);

                            window.mostrarToast?.('success', data.message);
                            await cargar();
                        } catch (err) {
                            window.mostrarToast?.('error', err.message || 'No se pudo eliminar.');
                        }
                    });

                    window.HistoriaArchivos = { cargar };

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', cargar);
                    } else {
                        cargar();
                    }
                })();
            </script>
        @endpush
    @endonce
@endif
