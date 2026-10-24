<div class="modal-backdrop" id="modalPaciente">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalPacienteTitulo">
        <form id="formPaciente">
            @csrf
            <input type="hidden" name="_method" id="formPacienteMethod" value="POST">
            <input type="hidden" name="paciente_id" id="formPacienteId" value="">

            <div class="modal-header">
                <h3 id="modalPacienteTitulo">Nuevo Paciente</h3>
                <button type="button" class="modal-close" data-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="paciente-ci">CI <span class="req">*</span></label>
                        <input type="text" id="paciente-ci" name="ci" required
                            class="form-control" maxlength="50"
                            placeholder="Ej: 12345678">
                        <span class="form-error" data-error-for="ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="paciente-genero">Género</label>
                        <select id="paciente-genero" name="genero" class="form-control">
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <span class="form-error" data-error-for="genero"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="paciente-nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="paciente-nombre" name="nombre" required
                            class="form-control" maxlength="100"
                            placeholder="Ej: Juan">
                        <span class="form-error" data-error-for="nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="paciente-apellido-paterno">Apellido paterno</label>
                        <input type="text" id="paciente-apellido-paterno" name="apellido_paterno"
                            class="form-control" maxlength="100"
                            placeholder="Ej: Pérez">
                        <span class="form-error" data-error-for="apellido_paterno"></span>
                    </div>

                    <div class="form-group">
                        <label for="paciente-apellido-materno">Apellido materno</label>
                        <input type="text" id="paciente-apellido-materno" name="apellido_materno"
                            class="form-control" maxlength="100"
                            placeholder="Ej: Mamani">
                        <span class="form-error" data-error-for="apellido_materno"></span>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="paciente-fecha-nacimiento">Fecha de nacimiento <span class="req">*</span></label>
                        <input type="date" id="paciente-fecha-nacimiento" name="fecha_nacimiento" required
                            class="form-control" max="{{ date('Y-m-d') }}">
                        <span class="form-error" data-error-for="fecha_nacimiento"></span>
                    </div>
                </div>

                <div class="form-grid" style="margin-top:18px;">
                    {{-- Estado Civil con opciones --}}
                    <div class="form-group">
                        <label for="paciente-estado_civil">Estado civil</label>
                        <select id="paciente-estado_civil" name="estado_civil" class="form-control">
                            <option value="">Seleccione...</option>
                            <option value="Soltero">Soltero</option>
                            <option value="Casado">Casado</option>
                            <option value="Viudo">Viudo</option>
                            <option value="Divorciado">Divorciado</option>
                            <option value="Separado">Separado</option>
                            <option value="Unión libre">Unión libre</option>
                            <option value="Conviviente">Conviviente</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <span class="form-error" data-error-for="estado_civil"></span>
                    </div>

                    @foreach(['telefono' => ['Teléfono', 30, true], 'ocupacion' => ['Ocupación', 200, false], 'direccion' => ['Dirección', 200, true], 'emergencia' => ['Contacto de emergencia', 200, true], 'telefono_emergencia' => ['Teléfono de emergencia', 30, true]] as $campo => [$etiqueta, $limite, $obligatorio])
                    <div class="form-group">
                        <label for="paciente-{{ $campo }}">{{ $etiqueta }} @if($obligatorio)<span class="req">*</span>@endif</label>
                        <input type="{{ str_starts_with($campo, 'telefono') ? 'tel' : 'text' }}" id="paciente-{{ $campo }}" name="{{ $campo }}" @if($obligatorio)required @endif class="form-control" maxlength="{{ $limite }}">
                        <span class="form-error" data-error-for="{{ $campo }}"></span>
                    </div>
                    @endforeach
                </div>

                <div id="formPacienteError" class="alert alert-danger" style="display:none; margin-top:18px;">
                    <span class="alert-icon"[STRIPPED 39 bytes]"0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </span>
                    <span id="formPacienteErrorText">Corrige los errores.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnGuardarPaciente">
                    <span id="btnGuardarPacienteText">Guardar Paciente</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const URLS = {
            store: @json(route('pacientes.store')),
            update: (id) => `/pacientes/${id}`,
        };

        const $modal = document.getElementById('modalPaciente');
        const $form = document.getElementById('formPaciente');
        const $titulo = document.getElementById('modalPacienteTitulo');
        const $id = document.getElementById('formPacienteId');
        const $btnGuard = document.getElementById('btnGuardarPaciente');
        const $btnText = document.getElementById('btnGuardarPacienteText');
        const $errorBox = document.getElementById('formPacienteError');
        const $errorTxt = document.getElementById('formPacienteErrorText');

        window.PacienteModal = {
            abrirCrear() {
                resetForm();
                $titulo.textContent = 'Nuevo Paciente';
                $id.value = '';
                $btnText.textContent = 'Registrar Paciente';
                openModal();
            },

            abrirEdicion(data) {
                resetForm();
                $titulo.textContent = 'Editar Paciente';
                $id.value = data.id;
                $btnText.textContent = 'Actualizar Paciente';

                const p = data.registro || data;

                ['telefono', 'estado_civil', 'ocupacion', 'direccion', 'emergencia', 'telefono_emergencia'].forEach((campo) => {
                    $form.elements.namedItem(campo).value = p[campo] ?? '';
                });

                $form.ci.value = p.ci ?? '';
                $form.nombre.value = p.nombre ?? '';
                $form.apellido_paterno.value = p.apellido_paterno ?? '';
                $form.apellido_materno.value = p.apellido_materno ?? '';
                $form.genero.value = p.genero ?? '';
                $form.fecha_nacimiento.value = p.fecha_nacimiento ?
                    p.fecha_nacimiento.substring(0, 10) :
                    '';

                openModal();
            },

            cerrar() {
                closeModal();
            },
        };

        $form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = $id.value;
            const isEdit = !!id;
            const url = isEdit ? URLS.update(id) : URLS.store;

            const formData = new FormData($form);
            if (isEdit) formData.set('_method', 'PUT');

            setLoading(true);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (res.status === 422) {
                    mostrarErrores(data.errors || {});
                    throw new Error(data.message || 'Revisa los campos.');
                }

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al guardar.');
                }

                closeModal();
                window.PacientesLista?.toast('success', data.message || 'Guardado correctamente.');
                window.dispatchEvent(new Event('paciente:guardado'));
            } catch (err) {
                if (Object.keys(collectErrors()).length === 0) {
                    $errorTxt.textContent = err.message || 'Error inesperado.';
                    $errorBox.style.display = 'flex';
                }
            } finally {
                setLoading(false);
            }
        });

        function openModal() {
            $modal.classList.add('open');
            document.body.classList.add('modal-open');
            setTimeout(() => $form.ci?.focus(), 100);
        }

        function closeModal() {
            $modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }

        function resetForm() {
            $form.reset();
            $id.value = '';
            clearErrors();
            $errorBox.style.display = 'none';
        }

        function clearErrors() {
            document.querySelectorAll('[data-error-for]').forEach((el) => el.textContent = '');
            $errorBox.style.display = 'none';
        }

        function mostrarErrores(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const $err = document.querySelector(`[data-error-for="${field}"]`);
                if ($err) $err.textContent = Array.isArray(messages) ? messages[0] : messages;
            });
            $errorTxt.textContent = 'Corrige los errores marcados en rojo.';
            $errorBox.style.display = 'flex';
        }

        function collectErrors() {
            const errors = {};
            document.querySelectorAll('[data-error-for]').forEach((el) => {
                if (el.textContent.trim()) errors[el.dataset.errorFor] = el.textContent;
            });
            return errors;
        }

        function setLoading(loading) {
            $btnGuard.disabled = loading;
            $btnText.textContent = loading ?
                'Guardando...' :
                ($id.value ? 'Actualizar Paciente' : 'Registrar Paciente');
        }

        $modal?.addEventListener('click', (e) => {
            if (e.target.closest('[data-modal-close]')) closeModal();
            if (e.target === $modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && $modal.classList.contains('open')) closeModal();
        });
    })();
</script>
@endpush