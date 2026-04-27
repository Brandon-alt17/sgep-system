<!-- MODAL EDITAR -->
<style>
    /* Estilos específicos para el modal */
    #modal-editar-aprendiz {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    #modal-editar-aprendiz.hidden {
        display: none !important;
    }

    #modal-editar-aprendiz:not(.hidden) {
        display: flex !important;
    }

    /* Overlay que captura clicks fuera del modal */
    #modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99998;
        cursor: pointer;
    }

    /* Contenedor del modal - debe estar por encima del overlay */
    .modal-container {
        position: relative;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 99999;
        transition: all 0.3s ease;
    }

    /* Asegurar que todo el contenido del modal sea clickeable */
    .modal-container * {
        pointer-events: auto;
    }

    /* Tamaño para pestaña Aprendiz (más ancho) */
    .modal-container.aprendiz-size {
        max-width: 768px;
        width: 80%;
        max-height: 85vh;  /* Reducido de 90vh a 85vh */
    }

    /* Tamaño para pestaña Empresa (30% más pequeño) */
    .modal-container.empresa-size {
        max-width: 768px;  /* 600px - 30% = 420px */
        width: 85%;
    }

    @media (max-width: 640px) {
        .modal-container.aprendiz-size,
        .modal-container.empresa-size {
            width: 95%;
            margin: 1rem;
        }
    }

    /* Estilos de tabs */
    .tab-button {
        padding-bottom: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
    }

    .tab-button.active {
        border-bottom-color: #cacaca;
        color: #1f2937;
    }

    .tab-button.inactive {
        color: #abb6ca;
    }

    .tab-button.inactive:hover {
        color: #374151;
    }

    /* Grid para formularios */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-field {
        margin-bottom: 0.5rem;
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #abb6ca;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
        pointer-events: auto;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    /* Dividers */
    .section-divider {
        border-top: 1px solid #f3f4f6;
        margin: 1rem 0;
    }

    /* Asegurar que los botones sean clickeables */
    button, 
    .modal-container button,
    .modal-container input,
    .modal-container select,
    .modal-container a {
        pointer-events: auto !important;
        cursor: pointer;
    }

    /* Botones personalizados */
    .btn-cancelar {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-cancelar:hover {
        background-color: #f9fafb;
        color: #1f2937;
        border-color: #d1d5db;
    }

    .btn-guardar {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: white;
        background-color: #00bca5;
        border: none;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        cursor: pointer;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .btn-guardar:hover {
        background-color: #009b88;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .btn-guardar:active {
        transform: translateY(0);
}
    
</style>

<div id="modal-editar-aprendiz" class="hidden">
    <!-- Overlay separado con su propio z-index -->
    <div id="modal-overlay" onclick="cerrarModal()"></div>
    
    <!-- Contenedor del modal con tamaño dinámico -->
    <div id="modal-container" class="modal-container aprendiz-size">
        <!-- Header -->
        <div class="p-6 pb-2">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Editar datos — <?= e($aprendiz['nombre_completo']) ?></h2>
                    <p class="text-sm text-gray-500 mt-1">Actualiza la información personal del aprendiz y de la empresa co-formadora.</p>
                </div>
                <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition text-2xl leading-none">&times;</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="px-6 border-b border-gray-100">
            <div class="flex gap-6">
                <button type="button" onclick="cambiarTab('aprendiz')" id="tab-aprendiz" class="tab-button active py-2 text-sm font-medium">
                    Datos del aprendiz
                </button>
                <button type="button" onclick="cambiarTab('empresa')" id="tab-empresa" class="tab-button inactive py-2 text-sm font-medium">
                    Empresa co-formadora
                </button>
            </div>
        </div>

        <!-- FORM (resto del contenido igual) -->
        <form method="POST" action="<?= e(APP_BASE_PATH) ?>/aprendices/update" class="p-6 pt-4">
            <input type="hidden" name="id" value="<?= (int)$aprendiz['id'] ?>">

            <!-- ===================== -->
            <!-- DATOS APRENDIZ (Más campos, más ancho) -->
            <!-- ===================== -->
            <div id="contenido-aprendiz">
                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" value="<?= e($aprendiz['nombre_completo']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Tipo de documento</label>
                        <input type="text" name="tipo_documento" value="<?= e($aprendiz['tipo_documento']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">N° documento</label>
                        <input type="text" name="numero_documento" value="<?= e($aprendiz['numero_documento']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" value="<?= e($aprendiz['telefono']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Email personal</label>
                        <input type="email" name="correo_personal" value="<?= e($aprendiz['correo_personal'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Email institucional</label>
                        <input type="email" name="correo_institucional" value="<?= e($aprendiz['correo_institucional'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Dirección de domicilio</label>
                        <input type="text" name="direccion_domicilio" value="<?= e($aprendiz['direccion_domicilio'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Alternativa etapa productiva</label>
                        <input type="text" name="alternativa_ep" value="<?= e($aprendiz['alternativa_ep'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Grupo</label>
                        <input type="text" name="ficha" value="<?= e($aprendiz['ficha'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Instructor seguimiento</label>
                        <input type="text" name="nombre_instructor_seguimiento" value="<?= e($aprendiz['nombre_instructor_seguimiento'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Teléfono instructor seguimiento</label>
                        <input type="text" name="telefono_instructor_seguimiento" value="<?= e($aprendiz['telefono_instructor_seguimiento'] ?? '') ?>" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-input">
                            <option value="Pendiente por iniciar" <?= ($aprendiz['estado'] ?? '') === 'Pendiente por iniciar' ? 'selected' : '' ?>>Pendiente por iniciar</option>
                            <option value="En ejecución" <?= ($aprendiz['estado'] ?? '') === 'En ejecución' ? 'selected' : 'En ejecución' ?>>En ejecución</option>
                            <option value="Certificado" <?= ($aprendiz['estado'] ?? '') === 'Certificado' ? 'selected' : 'Certificado' ?>>Certificado</option>
                            <option value="Aplazada" <?= ($aprendiz['estado'] ?? '') === 'Aplazada' ? 'selected' : 'Aplazada' ?>>Aplazada</option>
                            <option value="Finalizada" <?= ($aprendiz['estado'] ?? '') === 'Finalizada' ? 'selected' : 'Finalizada' ?>>Finalizada</option>
                            <option value="Por certificar" <?= ($aprendiz['estado'] ?? '') === 'Por certificar' ? 'selected' : 'Por certificar' ?>>Por certificar</option>
                            <option value="Pendiente por comité" <?= ($aprendiz['estado'] ?? '') === 'Pendiente por comité' ? 'selected' : 'Pendiente por comité' ?>>Pendiente por comité</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ===================== -->
            <!-- DATOS EMPRESA (Menos campos, más compacto) -->
            <!-- ===================== -->
            <div id="contenido-empresa" class="hidden">
                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label">Razón social</label>
                        <input type="text" name="empresa_nombre" value="<?= e($aprendiz['empresa_nombre'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">NIT</label>
                        <input type="text" name="nit" value="<?= e($aprendiz['nit'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" value="<?= e($aprendiz['direccion'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Supervisor</label>
                        <input type="text" name="nombre_jefe" value="<?= e($aprendiz['nombre_jefe'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Cargo del supervisor</label>
                        <input type="text" name="cargo_jefe" value="<?= e($aprendiz['cargo_jefe'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Teléfono de contacto</label>
                        <input type="text" name="telefono_jefe" value="<?= e($aprendiz['telefono_jefe'] ?? '') ?>" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModal()" class="btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn-guardar">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>