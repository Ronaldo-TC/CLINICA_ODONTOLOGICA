<div class="modal-backdrop" id="modalUsuario" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalUsuarioTitle">

        <div class="modal-header">
            <h3 id="modalUsuarioTitle">Nuevo usuario</h3>
            <button type="button" class="modal-close" onclick="cerrarModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <form id="formUsuario" method="POST" action="{{ route('admin.usuarios.guardar') }}">
            @csrf
            <input type="hidden" name="_method" id="formUsuarioMethod" value="POST">
            <input type="hidden" name="usuario_id" id="formUsuarioId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="m_ci">CI <span class="req">*</span></label>
                        <input type="text" name="ci" id="m_ci" class="form-control" maxlength="50" required>
                        <span class="form-error" data-error="ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_nombre_usuario">Nombre de usuario <span class="req">*</span></label>
                        <input type="text" name="nombre_usuario" id="m_nombre_usuario"
                            class="form-control" maxlength="20" required>
                        <span class="form-error" data-error="nombre_usuario"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_nombre_completo">Nombre completo <span class="req">*</span></label>
                        <input type="text" name="nombre_completo" id="m_nombre_completo"
                            class="form-control" maxlength="200" required>
                        <span class="form-error" data-error="nombre_completo"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_tipo">Tipo <span class="req">*</span></label>
                        <select name="tipo" id="m_tipo" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach (['admin','subadmin','director','jefatura','docente','estudiante','interno','cajero','personal'] as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" data-error="tipo"></span>
                    </div>

                    <div class="form-group">
                        <label for="m_descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="m_descripcion"
                            class="form-control" maxlength="300" placeholder="Opcional">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_contrasena">
                            Contraseña
                            <span class="req" id="reqContrasena">*</span>
                            <small id="hintContrasena"
                                style="display:none; font-weight:400; text-transform:none; letter-spacing:0; color:var(--muted);">
                                (dejar en blanco para no cambiar)
                            </small>
                        </label>
                        <div class="password-wrap">
                            <input type="password" name="contrasena" id="m_contrasena"
                                class="form-control" minlength="6" autocomplete="new-password">

                            <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Mostrar contraseña">
                                <svg class="eye-on" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>

                        <span class="form-error" data-error="contrasena"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="m_info">Información adicional</label>
                        <input type="text" name="info" id="m_info"
                            class="form-control" maxlength="300" placeholder="Opcional">
                        <span class="form-error" data-error="info"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Roles</label>
                        <div class="checkbox-grid">
                            @foreach ($roles as $rol)
                            <label class="checkbox-item">
                                <input type="checkbox" name="roles[]" value="{{ $rol->id }}"
                                    data-rol-id="{{ $rol->id }}">
                                <span>{{ $rol->nombre }}</span>
                            </label>
                            @endforeach
                        </div>
                        <span class="form-error" data-error="roles"></span>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="switch-item">
                            <input type="checkbox" name="habilitado" id="m_habilitado" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Usuario habilitado</span>
                        </label>

                        <label class="switch-item">
                            <input type="checkbox" name="permitir_login" id="m_permitir_login" value="1" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Permitir inicio de sesión</span>
                        </label>

                        <label class="switch-item">
                            <input type="checkbox" name="multilogin" id="m_multilogin" value="1">
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Permitir múltiples sesiones simultáneas</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <span id="btnGuardarUsuarioText">Crear usuario</span>
                </button>
            </div>
        </form>
    </div>
</div>