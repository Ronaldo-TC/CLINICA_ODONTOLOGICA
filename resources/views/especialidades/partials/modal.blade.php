<div class="modal-backdrop" id="modalEspecialidad" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalEspecialidadTitle">

        <div class="modal-header">
            <h3 id="modalEspecialidadTitle">Nueva asignación</h3>
            <button type="button" class="modal-close" onclick="cerrarModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formEspecialidad" method="POST" action="{{ route('especialidades.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formEspecialidadMethod" value="POST">

            <div class="modal-body">

                {{-- ============================================================
                     USUARIO — Autocomplete por CI / nombre / usuario
                     ============================================================ --}}
                <div class="form-group"
                    style="margin-bottom:16px; position:relative;"
                    id="usuarioAutocompleteWrap">

                    <label for="e_usuario_buscar">
                        Usuario <span class="req">*</span>
                        <small style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted);">
                            (busca por CI, nombre o usuario)
                        </small>
                    </label>

                    {{-- Input visible de búsqueda --}}
                    <input type="text"
                        id="e_usuario_buscar"
                        class="form-control"
                        placeholder="Escribe al menos 2 caracteres..."
                        autocomplete="off"
                        spellcheck="false">

                    {{-- Input oculto con el ID real del usuario seleccionado --}}
                    <input type="hidden" name="usuario_id" id="e_usuario_id">

                    {{-- Spinner de carga --}}
                    <div id="e_usuario_spinner"
                        style="display:none; position:absolute; right:14px; top:42px;
                                width:16px; height:16px;
                                border:2px solid rgba(6,182,212,.2);
                                border-top-color:#06b6d4;
                                border-radius:50%;
                                animation:e_uc_spin .8s linear infinite;">
                    </div>

                    {{-- Botón limpiar --}}
                    <button type="button"
                        id="e_usuario_clear"
                        aria-label="Limpiar usuario"
                        style="display:none; position:absolute; right:12px; top:40px;
                                   width:24px; height:24px; border:0; background:transparent;
                                   color:#60736f; cursor:pointer; border-radius:6px; padding:0;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>

                    {{-- Dropdown de resultados --}}
                    <div id="e_usuario_dropdown"
                        style="display:none; position:absolute; top:100%; left:0; right:0;
                                margin-top:4px; max-height:320px; overflow-y:auto; z-index:50;
                                background:#fff; border:1px solid var(--line);
                                border-radius:12px;
                                box-shadow:0 12px 32px rgba(24,37,45,.14);
                                padding:6px;">
                    </div>

                    {{-- Vista previa del usuario seleccionado --}}
                    <div id="e_usuario_seleccionado"
                        style="display:none; margin-top:8px; padding:10px 14px;
                                background:var(--surface-soft);
                                border:1px solid var(--line);
                                border-radius:10px;
                                font-size:13px;">
                    </div>

                    <span class="form-error" data-error="usuario_id"></span>
                </div>

                {{-- ============================================================
                     CLÍNICAS
                     ============================================================ --}}
                <div class="form-group" style="margin-bottom:16px;">
                    <label>
                        Clínicas <span class="req">*</span>
                        <small style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted);">
                            (puedes seleccionar varias)
                        </small>
                    </label>

                    @php
                    // Agrupar clínicas por módulo
                    $porModulo = $clinicas->groupBy('modulo');
                    @endphp

                    @foreach ($porModulo as $modulo => $items)
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase;
                                        color:var(--muted); letter-spacing:.08em; margin-bottom:6px;">
                            {{ $modulo ?: 'Sin módulo' }}
                        </div>

                        <div class="checkbox-grid">
                            @foreach ($items as $c)
                            <label class="checkbox-item">
                                <input type="checkbox"
                                    name="clinica_ids[]"
                                    value="{{ $c->id }}">
                                <span>
                                    {{ $c->especialidad }}
                                    @if ($c->curso > 0)
                                    <small style="color:var(--muted);">({{ $c->curso }}°)</small>
                                    @endif
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <span class="form-error" data-error="clinica_ids"></span>
                </div>

                {{-- ============================================================
                     ROTACIÓN Y FECHA
                     ============================================================ --}}
                <div class="form-grid">
                    <div class="form-group">
                        <label for="e_rotacion">Rotación <span class="req">*</span></label>
                        <input type="number"
                            name="rotacion"
                            id="e_rotacion"
                            class="form-control"
                            value="0"
                            min="-1"
                            max="100"
                            required>
                        <small style="font-size:11px;color:var(--muted);">
                            -1 = sin rotación, 0+ = número de rotación
                        </small>
                        <span class="form-error" data-error="rotacion"></span>
                    </div>

                    <div class="form-group">
                        <label for="e_fecha_inicio">Fecha inicio</label>
                        <input type="date"
                            name="fecha_inicio"
                            id="e_fecha_inicio"
                            class="form-control">
                        <span class="form-error" data-error="fecha_inicio"></span>
                    </div>
                </div>

                {{-- ============================================================
                     RESPONSABLE DE ADMISIÓN
                     ============================================================ --}}
                <div class="switch-item" style="margin-top:16px;">
                    <input type="checkbox" name="responsable_admision" id="e_responsable" value="1">
                    <span class="switch-track"><span class="switch-thumb"></span></span>
                    <span class="switch-label">Es responsable de admisión</span>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarEspecialidad">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span id="btnGuardarEspecialidadText">Asignar</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     JS del autocomplete — autocontenido, sin depender del index
     ============================================================ --}}
<script>
    (function() {
        // Evitar doble inicialización si el modal se renderiza varias veces
        if (window.__usuarioAutocompleteInit) return;
        window.__usuarioAutocompleteInit = true;

        const URL_BUSCAR_USUARIOS = "{{ route('especialidades.buscar-usuarios') }}";

        // Referencias DOM (se resuelven al momento de usarse porque el modal
        // se incluye en cada render y las referencias podrían ser nulas antes)
        const getEl = (id) => document.getElementById(id);

        let timer = null;
        let activeIdx = -1;
        let results = [];

        // ─── Escapar HTML ─────────────────────────────────────────
        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[c]);
        }

        // ─── Reset visual completo ────────────────────────────────
        function resetAutocomplete() {
            const input = getEl('e_usuario_buscar');
            const inputId = getEl('e_usuario_id');
            const dropdown = getEl('e_usuario_dropdown');
            const preview = getEl('e_usuario_seleccionado');
            const clearBtn = getEl('e_usuario_clear');
            const spinner = getEl('e_usuario_spinner');

            if (input) input.value = '';
            if (inputId) inputId.value = '';
            if (dropdown) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
            }
            if (preview) preview.style.display = 'none';
            if (clearBtn) clearBtn.style.display = 'none';
            if (spinner) spinner.style.display = 'none';

            results = [];
            activeIdx = -1;
        }
        window.resetUsuarioAutocomplete = resetAutocomplete;

        // ─── Render de resultados ─────────────────────────────────
        function renderResults() {
            const dropdown = getEl('e_usuario_dropdown');
            if (!dropdown) return;

            if (results.length === 0) {
                dropdown.innerHTML = `
                <div style="padding:20px; text-align:center; color:var(--muted); font-size:13px;">
                    Sin resultados para esa búsqueda.
                </div>`;
                return;
            }

            dropdown.innerHTML = results.map((u, i) => `
            <div class="autocomplete-item"
                 data-index="${i}"
                 data-id="${escapeHtml(u.id)}"
                 data-nombre="${escapeHtml(u.nombre_completo)}"
                 data-ci="${escapeHtml(u.ci || '')}"
                 data-roles="${escapeHtml(u.roles || '')}"
                 style="display:flex; align-items:center; gap:12px;
                        padding:10px 12px; cursor:pointer; border-radius:8px;
                        transition:background .12s ease;">
                <div style="width:36px; height:36px; border-radius:50%;
                            background:linear-gradient(135deg,#06b6d4,#d946ef);
                            display:grid; place-items:center; color:#fff;
                            font:700 13px -apple-system,sans-serif; flex-shrink:0;">
                    ${escapeHtml(u.initials || '?')}
                </div>
                <div style="display:flex; flex-direction:column; min-width:0; flex:1;">
                    <span style="font-weight:600; color:var(--ink);
                                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${escapeHtml(u.nombre_completo)}
                    </span>
                    <span style="font-size:11.5px; color:var(--muted); margin-top:2px;
                                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        CI: ${escapeHtml(u.ci || '—')} · ${escapeHtml(u.roles || '')}
                    </span>
                </div>
            </div>
        `).join('');

            dropdown.querySelectorAll('.autocomplete-item').forEach(el => {
                el.addEventListener('mouseenter', () => {
                    activeIdx = parseInt(el.dataset.index, 10);
                    updateActive();
                });
                el.addEventListener('click', () => seleccionar(el));
            });
        }

        function updateActive() {
            const dropdown = getEl('e_usuario_dropdown');
            if (!dropdown) return;

            dropdown.querySelectorAll('.autocomplete-item').forEach((el, i) => {
                if (i === activeIdx) {
                    el.style.background = 'var(--surface-soft)';
                    el.scrollIntoView({
                        block: 'nearest'
                    });
                } else {
                    el.style.background = '';
                }
            });
        }

        // ─── Búsqueda ─────────────────────────────────────────────
        async function buscar(q) {
            const dropdown = getEl('e_usuario_dropdown');
            const spinner = getEl('e_usuario_spinner');
            if (!dropdown) return;

            spinner && (spinner.style.display = 'block');
            dropdown.style.display = 'block';
            dropdown.innerHTML = '<div style="padding:16px;text-align:center;color:var(--muted);font-size:13px;">Buscando...</div>';

            try {
                const res = await fetch(
                    `${URL_BUSCAR_USUARIOS}?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    }
                );

                if (!res.ok) throw new Error('Error al buscar usuarios');

                const data = await res.json();
                results = data.results || [];
                activeIdx = -1;
                renderResults();

            } catch (err) {
                dropdown.innerHTML = `
                <div style="padding:16px;text-align:center;color:#dc2626;font-size:13px;">
                    ${escapeHtml(err.message)}
                </div>`;
            } finally {
                spinner && (spinner.style.display = 'none');
            }
        }

        // ─── Selección ────────────────────────────────────────────
        function seleccionar(el) {
            const input = getEl('e_usuario_buscar');
            const inputId = getEl('e_usuario_id');
            const dropdown = getEl('e_usuario_dropdown');
            const preview = getEl('e_usuario_seleccionado');
            const clearBtn = getEl('e_usuario_clear');

            const id = el.dataset.id;
            const nombre = el.dataset.nombre;
            const ci = el.dataset.ci;
            const roles = el.dataset.roles;

            if (input) input.value = nombre;
            if (inputId) inputId.value = id;

            if (preview) {
                preview.innerHTML = `
                <div style="display:flex; align-items:center; gap:10px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                         stroke="#16a34a" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span>
                        <strong>${escapeHtml(nombre)}</strong>
                        · CI ${escapeHtml(ci || '—')}
                        · <em>${escapeHtml(roles || '')}</em>
                    </span>
                </div>`;
                preview.style.display = 'block';
            }

            if (dropdown) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
            }
            if (clearBtn) clearBtn.style.display = 'block';

            // Limpiar error de validación
            const err = document.querySelector('[data-error="usuario_id"]');
            if (err) err.textContent = '';
        }

        // ─── Listeners (delegados, para funcionar aunque el modal se re-renderice) ──
        document.addEventListener('input', function(e) {
            if (e.target.id !== 'e_usuario_buscar') return;

            const q = e.target.value.trim();
            const inputId = getEl('e_usuario_id');
            const preview = getEl('e_usuario_seleccionado');
            const clearBtn = getEl('e_usuario_clear');
            const dropdown = getEl('e_usuario_dropdown');

            // Si el usuario edita después de haber seleccionado, invalidar
            if (inputId) inputId.value = '';
            if (preview) preview.style.display = 'none';

            if (q.length < 2) {
                if (dropdown) {
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                }
                if (clearBtn) clearBtn.style.display = 'none';
                return;
            }

            if (clearBtn) clearBtn.style.display = 'block';

            clearTimeout(timer);
            timer = setTimeout(() => buscar(q), 250);
        });

        document.addEventListener('keydown', function(e) {
            if (e.target.id !== 'e_usuario_buscar') return;

            const dropdown = getEl('e_usuario_dropdown');
            if (!dropdown || dropdown.style.display === 'none' || results.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, results.length - 1);
                updateActive();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, 0);
                updateActive();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIdx >= 0) {
                    const el = dropdown.querySelector(`.autocomplete-item[data-index="${activeIdx}"]`);
                    if (el) seleccionar(el);
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });

        document.addEventListener('click', function(e) {
            // Cerrar dropdown al hacer clic fuera
            const wrap = getEl('usuarioAutocompleteWrap');
            if (wrap && !wrap.contains(e.target)) {
                const dropdown = getEl('e_usuario_dropdown');
                if (dropdown) dropdown.style.display = 'none';
            }

            // Botón limpiar
            if (e.target.closest('#e_usuario_clear')) {
                resetAutocomplete();
                getEl('e_usuario_buscar')?.focus();
            }
        });

        // ─── Validación al enviar el form ─────────────────────────
        document.addEventListener('submit', function(e) {
            if (e.target.id !== 'formEspecialidad') return;

            const inputId = getEl('e_usuario_id');
            if (!inputId || !inputId.value) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.mostrarToast?.('warning', 'Selecciona un usuario de la lista.');
                getEl('e_usuario_buscar')?.focus();
            }
        }, true); // ← captura: se ejecuta antes del listener del index
    })();
</script>

<style>
    @keyframes e_uc_spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>